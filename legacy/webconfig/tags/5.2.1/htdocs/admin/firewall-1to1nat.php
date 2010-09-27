<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2003-2006 Point Clark Networks.
//
///////////////////////////////////////////////////////////////////////////////
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//
///////////////////////////////////////////////////////////////////////////////

require_once("../../gui/Webconfig.inc.php");
require_once("../../api/Iface.class.php");
require_once("../../api/Firewall.class.php");
require_once("../../api/FirewallOneToOneNat.class.php");
require_once("../../api/FirewallMultiWan.class.php");
require_once("firewall-common.inc.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-firewall-1to1nat.png", WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$firewall = new FirewallOneToOneNat();
$interface = new Iface("null"); // For locale

$all_nickname = isset($_POST['all_nickname']) ? $_POST['all_nickname'] : "";
$all_interface = isset($_POST['all_interface']) ? $_POST['all_interface'] : "";
$all_private_ip = isset($_POST['all_private_ip']) ? $_POST['all_private_ip'] : "";
$all_public_ip = isset($_POST['all_public_ip']) ? $_POST['all_public_ip'] : "";
$nickname = isset($_POST['nickname']) ? $_POST['nickname'] : "";
$interface = isset($_POST['interface']) ? $_POST['interface'] : "";
$private_ip = isset($_POST['private_ip']) ? $_POST['private_ip'] : "";
$public_ip = isset($_POST['public_ip']) ? $_POST['public_ip'] : "";
$protocol = isset($_POST['protocol']) ? $_POST['protocol'] : "";
$port_from = isset($_POST['port_from']) ? $_POST['port_from'] : "";
$port_to = isset($_POST['port_to']) ? $_POST['port_to'] : "";

try {
	if (isset($_POST['Add'])) {
		$firewall->Add($all_nickname, $all_public_ip, $all_private_ip, $all_interface);
		$firewall->Restart();
	} else if (isset($_POST['AddPort'])) {
		if (empty($port_to))
			$firewall->AddPort($nickname, $public_ip, $private_ip, $protocol, $port_from, $interface);
		else
			$firewall->AddPortRange($nickname, $public_ip, $private_ip, $protocol, $port_from, $port_to, $interface);
		$firewall->Restart();
	} else if (isset($_POST['Delete'])) {
		$pair = explode("|", key($_POST['Delete']));
		$firewall->Delete($pair[1], $pair[0], $pair[2]);
		$firewall->Restart();
	} else if (isset($_POST['DeletePort'])) {
		$pair = explode("|", key($_POST['DeletePort']));
		if (isset($pair[5]))
			$firewall->DeletePortRange($pair[1], $pair[0], $pair[2], $pair[3], $pair[4], $pair[5]);
		else
			$firewall->DeletePort($pair[1], $pair[0], $pair[2], $pair[3], $pair[4]);
		$firewall->Restart();
	} else if (isset($_POST['Toggle'])) {
		$pair = explode("|", key($_POST['Toggle']));
		$firewall->ToggleEnable(($pair[0]) ? false : true, $pair[2], $pair[1], $pair[3]);
		$firewall->Restart();
	} else if (isset($_POST['TogglePort'])) {
		$pair = explode("|", key($_POST['TogglePort']));
		if (isset($pair[6]))
			$firewall->ToggleEnablePortRange(($pair[0]) ? false : true, $pair[2], $pair[1], $pair[3], $pair[4], $pair[5], $pair[6]);
		else
			$firewall->ToggleEnablePort(($pair[0]) ? false : true, $pair[2], $pair[1], $pair[3], $pair[4], $pair[5]);
		$firewall->Restart();
	}

	$errors = $firewall->GetValidationErrors(true);

	if (empty($errors)) {
		$all_nickname = "";
		$all_interface = "";
		$all_private_ip = "";
		$all_public_ip = "";
		$nickname = "";
		$interface = "";
		$private_ip = "";
		$public_ip = "";
		$protocol = "";
		$port_from = "";
		$port_to = "";
		$port = "";
	} else {
		WebDialogWarning($errors);
    }

} catch (Exception $e) {
	WebDialogWarning($e->GetMessage());
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

try {
	$mode = $firewall->GetMode();
} catch (Exception $e) {
	WebDialogWarning($e->GetMessage());
}

if (($mode == Firewall::CONSTANT_TRUSTEDSTANDALONE) || ($mode == Firewall::CONSTANT_STANDALONE))
	DisplayModeWarning();

DisplayRules(
	$all_nickname, $all_interface, $all_private_ip, $all_public_ip,
	$nickname, $interface, $private_ip, $public_ip, $protocol, $port_from, $port_to, $port
);
WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayRules()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayRules(
	$all_nickname, $all_interface, $all_private_ip, $all_public_ip,
	$nickname, $interface, $private_ip, $public_ip, $protocol, $port_from, $port_to
)
{
	global $firewall;

	$multiwan = new FirewallMultiWan();

	try {
		$rules = $firewall->Get();
		$rulesoneport = $firewall->GetPort();
		$rulesportrange = $firewall->GetPortRange();
		$wanlist = $multiwan->GetExternalInterfaces();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

	$list = "";
	$listport = "";
	$index = 0;

	if ($rules) {
		foreach ($rules as $rule) {
			$pair = explode("|", $rule["host"]);
			$ifn = $rule["ifn"];
			$nick = (strlen($rule["name"])) ? $rule["name"] : "-";

            if ($rule['enabled']) {
                $toggle = LOCALE_LANG_DISABLE;
                $iconclass = "iconenabled";
                $rowclass = "rowenabled";
            } else {
                $toggle = LOCALE_LANG_ENABLE;
                $iconclass = "icondisabled";
                $rowclass = "rowdisabled";
            }

            $rowclass .= ($index % 2) ? "alt" : "";
            $index++;

            $list .= "
                <tr class='$rowclass'>
                    <td class='$iconclass'>&nbsp; </td>
					<td>$nick</td>
					<td>$ifn</td>
					<td>" . $pair[0] . "</td>
					<td>" . $pair[1] . "</td>
					<td>&#160;</td>
					<td>&#160;</td>
					<td nowrap>" .
						WebButtonDelete("Delete[$pair[0]|$pair[1]|$ifn]") . 
						WebButtonToggle("Toggle[$rule[enabled]|$pair[0]|$pair[1]|$ifn]", $toggle) . "
					</td>
				</tr>
			";
		}
	} else {
		$list .= "<tr><td colspan='7' align='center'>" . FIREWALL_LANG_ERRMSG_RULES_NOT_DEFINED . "</td></tr>\n";
	}


	if ($rulesoneport) {
		$index = 0;

		foreach ($rulesoneport as $rule) {
			$pair = explode("|", $rule['host']);
			$ifn = $rule["ifn"];
			$nick = (strlen($rule['name'])) ? $rule['name'] : "-";

            if ($rule['enabled']) {
                $toggle = LOCALE_LANG_DISABLE;
                $iconclass = "iconenabled";
                $rowclass = "rowenabled";
            } else {
                $toggle = LOCALE_LANG_ENABLE;
                $iconclass = "icondisabled";
                $rowclass = "rowdisabled";
            }

            $rowclass .= ($index % 2) ? "alt" : "";
            $index++;

			$listport .= "
                <tr class='$rowclass'>
                    <td class='$iconclass'>&nbsp; </td>
					<td>$nick</td>
					<td>$ifn</td>
					<td>" . $pair[0] . "</td>
					<td>" . $pair[1] . "</td>
					<td>" . $pair[2] . "</td>
					<td>" . $pair[3] . "</td>
					<td nowrap>" .
						WebButtonDelete("DeletePort[$pair[0]|$pair[1]|$pair[2]|$pair[3]|$ifn]") .
						WebButtonToggle("TogglePort[$rule[enabled]|$pair[0]|$pair[1]|$pair[2]|$pair[3]|$ifn]", $toggle) . "
					</td>
				</tr>
			";
		}
	}

	if ($rulesportrange) {
		$index = 0;

		foreach ($rulesportrange as $rule) {
			$pair = explode("|", $rule['host']);
			$ifn = $rule["ifn"];
			$nick = (strlen($rule['name'])) ? $rule['name'] : "-";

            if ($rule['enabled']) {
                $toggle = LOCALE_LANG_DISABLE;
                $iconclass = "iconenabled";
                $rowclass = "rowenabled";
            } else {
                $toggle = LOCALE_LANG_ENABLE;
                $iconclass = "icondisabled";
                $rowclass = "rowdisabled";
            }

            $rowclass .= ($index % 2) ? "alt" : "";
            $index++;

			$listport .= "
                <tr class='$rowclass'>
                    <td class='$iconclass'>&nbsp; </td>
					<td>$nick</td>
					<td>$ifn</td>
					<td>" . $pair[0] . "</td>
					<td>" . $pair[1] . "</td>
					<td>" . $pair[2] . "</td>
					<td>" . $pair[3] . " : " . $pair[4] . "</td>
					<td nowrap>" .
						WebButtonDelete("DeletePort[$pair[0]|$pair[1]|$pair[2]|$pair[3]|$pair[4]|$ifn]") . 
						WebButtonToggle("TogglePort[$rule[enabled]|$pair[0]|$pair[1]|$pair[2]|$pair[3]|$pair[4]|$ifn]", $toggle) . "
					</td>
				</tr>
			";
		}
	}

	if (empty($listport))
		$listport = "<tr><td colspan='7' align='center'>" . FIREWALL_LANG_ERRMSG_RULES_NOT_DEFINED . "</td></tr>\n";

	WebFormOpen();
	WebTableOpen(WEB_LANG_CONFIG_TITLE, "100%");
	WebTableHeader(
		"&nbsp;|" .
		FIREWALL_LANG_NICKNAME . "|" . 
		IFACE_LANG_INTERFACE . "|" . 
		WEB_LANG_PRIVATE_IP . "|" .
		WEB_LANG_PUBLIC_IP . "|||" . 
		"&nbsp;"
	);
	echo $list;
	echo "
		<tr>
			<td>&nbsp;</td>
			<td nowrap><input type='text' name='all_nickname' value='$all_nickname' /></td>
			<td nowrap>" . WebDropDownArray("all_interface", $all_interface, $wanlist) . "</td>
			<td nowrap><input type='text' style='width:100px' name='all_private_ip' value='$all_private_ip' /></td>
			<td nowrap><input type='text' style='width:100px' name='all_public_ip' value='$all_public_ip' /></td>
			<td>&#160;</td>
			<td>&#160;</td>
			<td nowrap>" . WebButtonAdd('Add') . "</td>
		</tr>
	";
	WebTableClose("100%");
	WebFormClose();

	WebFormOpen();
	WebTableOpen(WEB_LANG_CONFIG_TITLE . " - " . FIREWALL_LANG_PORT, "100%");
	WebTableHeader(
		"&nbsp;|" .
		FIREWALL_LANG_NICKNAME . "|" . 
		IFACE_LANG_INTERFACE . "|" . 
		WEB_LANG_PRIVATE_IP . "|" . 
		WEB_LANG_PUBLIC_IP . "|" .
		FIREWALL_LANG_PROTOCOL . "|" . 
		FIREWALL_LANG_PORT . "|" . 
		"&nbsp;"
	);
	echo $listport;
	echo "
		<tr>
			<td>&nbsp;</td>
			<td nowrap><input type='text' name='nickname' value='$nickname' /></td>
			<td nowrap>" . WebDropDownArray("interface", $interface, $wanlist) . "</td>
			<td nowrap><input type='text' style='width:100px' name='private_ip' value='$private_ip' /></td>
			<td nowrap><input type='text' style='width:100px' name='public_ip' value='$public_ip' /></td>
			<td nowrap>" . WebDropDownArray("protocol", $protocol, array('TCP', 'UDP')) . "</td>
			<td nowrap>
				<input type='text' style='width:30px' name='port_from' value='$port_from' /> :
				<input type='text' style='width:30px' name='port_to' value='$port_to' />
			</td>
			<td nowrap>" . WebButtonAdd('AddPort') . "</td>
		</tr>
	";
	WebTableClose("100%");
	WebFormClose();
}

// vim: ts=4
?>
