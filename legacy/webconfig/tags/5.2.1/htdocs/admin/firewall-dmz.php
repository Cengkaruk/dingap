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
require_once("../../api/FirewallDmz.class.php");
require_once("../../api/Firewall.class.php");
require_once("../../api/Network.class.php");
require_once("firewall-common.inc.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-firewall-dmz.png", WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$firewall = new FirewallDmz();
$network = new Network(); // Language tags

$dmz_nickname = isset($_POST['dmz_nickname']) ? $_POST['dmz_nickname'] : "";
$dmz_ip = isset($_POST['dmz_ip']) ? $_POST['dmz_ip'] : "";
$dmz_protocol = isset($_POST['dmz_protocol']) ? $_POST['dmz_protocol'] : "";
$dmz_port = isset($_POST['dmz_port']) ? $_POST['dmz_port'] : "";
$all_nickname = isset($_POST['all_nickname']) ? $_POST['all_nickname'] : "";
$all_ip = isset($_POST['all_ip']) ? $_POST['all_ip'] : "";

$pinhole_nickname = isset($_POST['pinhole_nickname']) ? $_POST['pinhole_nickname'] : "";
$pinhole_ip = isset($_POST['pinhole_ip']) ? $_POST['pinhole_ip'] : "";
$pinhole_protocol = isset($_POST['pinhole_protocol']) ? $_POST['pinhole_protocol'] : "";
$pinhole_port = isset($_POST['pinhole_port']) ? $_POST['pinhole_port'] : "";
$pinhole_all_nickname = isset($_POST['pinhole_all_nickname']) ? $_POST['pinhole_all_nickname'] : "";
$pinhole_all_ip = isset($_POST['pinhole_all_ip']) ? $_POST['pinhole_all_ip'] : "";

try {
	if (isset($_POST['AddForwardPort'])) {
		$firewall->AddForwardPort($dmz_nickname, $dmz_ip, $dmz_protocol, $dmz_port);
		$firewall->Restart();
	} else if (isset($_POST['AddForward'])) {
		$firewall->AddForwardPort($all_nickname, $all_ip, Firewall::CONSTANT_ALL_PROTOCOLS, Firewall::CONSTANT_ALL_PORTS);
		$firewall->Restart();
	} else if (isset($_POST['DeleteForwardPort'])) {
		list($_POST['ip'], $_POST['protocol'], $_POST['port']) = explode("|", key($_POST['DeleteForwardPort']));
		$firewall->DeleteForwardPort($_POST['ip'], $_POST['protocol'], $_POST['port']);
		$firewall->Restart();
	} else if (isset($_POST['AddPinholePort'])) {
		$firewall->AddPinholePort($pinhole_nickname, $pinhole_ip, $pinhole_protocol, $pinhole_port);
		$firewall->Restart();
	} else if (isset($_POST['AddPinhole'])) {
		$firewall->AddPinholePort($pinhole_all_nickname, $pinhole_all_ip, Firewall::CONSTANT_ALL_PROTOCOLS, Firewall::CONSTANT_ALL_PORTS);
		$firewall->Restart();
	} else if (isset($_POST['DeletePinholePort'])) {
		list($_POST['ip'], $_POST['protocol'], $_POST['port']) = explode("|", key($_POST['DeletePinholePort']));
		$firewall->DeletePinholePort($_POST['ip'], $_POST['protocol'], $_POST['port']);
		$firewall->Restart();
	} else if (isset($_POST['ToggleForwardPort'])) {
		list($_POST['enabled'], $_POST['ip'], $_POST['protocol'], $_POST['port']) = explode(
			"|", key($_POST['ToggleForwardPort'])
		);
		$firewall->ToggleEnableForwardPort(($_POST['enabled']) ? false : true,
		$_POST['ip'], $_POST['protocol'], $_POST['port']);
		$firewall->Restart();
	} else if (isset($_POST['TogglePinholePort'])) {
		list($_POST['enabled'], $_POST['ip'], $_POST['protocol'], $_POST['port']) = explode(
			"|", key($_POST['TogglePinholePort'])
		);
		$firewall->ToggleEnablePinholePort(($_POST['enabled']) ? false : true,
			$_POST['ip'], $_POST['protocol'], $_POST['port']);
		$firewall->Restart();
	}

    $errors = $firewall->GetValidationErrors(true);

    if (empty($errors)) {
		$dmz_nickname = "";
		$dmz_ip = "";
		$dmz_protocol = "";
		$dmz_port = "";
		$all_nickname = "";
		$all_ip = "";
		$pinhole_nickname = "";
		$pinhole_ip = "";
		$pinhole_protocol = "";
		$pinhole_port = "";
		$pinhole_all_nickname = "";
		$pinhole_all_ip = "";
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

DisplayDmz();
DisplayAdd($dmz_nickname, $dmz_ip, $dmz_protocol, $dmz_port, $all_nickname, $all_ip);
DisplayDmzPinhole();
DisplayAddPinhole($pinhole_nickname, $pinhole_ip, $pinhole_protocol, $pinhole_port, $pinhole_all_nickname, $pinhole_all_ip);

WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayDmz()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayDmz()
{
	global $firewall;

	try {
		$ports = $firewall->GetForwardPorts();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	$list = "";
	$index = 0;

	if ($ports) {
		foreach ($ports as $rule) {
			$ip = $rule['ip'];
			$name = (strlen($rule['name'])) ? $rule['name'] : "-";
			$port = $rule['port'];
			$protocol = $rule['protocol'];
			if ($port == Firewall::CONSTANT_ALL_PORTS)
				$port = WEB_LANG_ALL_PORTS;
			if ($protocol == Firewall::CONSTANT_ALL_PROTOCOLS)
		 		$protocol = WEB_LANG_ALL_PROTOCOLS;

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
					<td nowrap>$name</td>
					<td nowrap>$ip</td>
					<td nowrap>$protocol</td>
					<td nowrap>$port</td>
					<td nowrap>" .
						WebButtonDelete("DeleteForwardPort[$rule[ip]|$rule[protocol]|$rule[port]]") .
						WebButtonToggle("ToggleForwardPort[$rule[enabled]|$rule[ip]|$rule[protocol]|$rule[port]]", $toggle) . "
					</td>
				</tr>
			";
		}
	}

	if (!$list)
		$list .= "<tr><td colspan='5' align='center'>" . FIREWALL_LANG_ERRMSG_RULES_NOT_DEFINED . "</td></tr>";

	WebFormOpen();
	WebTableOpen(WEB_LANG_DELETE_RULE_TITLE, "100%");
	WebTableHeader(
		"&nbsp;|" . 
		FIREWALL_LANG_NICKNAME . "|" . 
		NETWORK_LANG_IP . "|" . 
		FIREWALL_LANG_PROTOCOL . "|" .
		FIREWALL_LANG_PORT . "|" . 
		"&nbsp;"
	);
	echo $list;
	WebTableClose("100%");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayDmzPinhole()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayDmzPinhole()
{
	global $firewall;

	try {
		$ports = $firewall->GetPinholePorts(&$errors[]);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	$list = "";
	$index = 0;

	if ($ports) {
		foreach ($ports as $rule) {
			$ip = $rule['ip'];
			$name = (strlen($rule['name'])) ? $rule['name'] : "-";
			$port = $rule['port'];
			$protocol = $rule['protocol'];
			if ($port == Firewall::CONSTANT_ALL_PORTS)
				$port = WEB_LANG_ALL_PORTS;
			if ($protocol == Firewall::CONSTANT_ALL_PROTOCOLS)
				$protocol = WEB_LANG_ALL_PROTOCOLS;

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
					<td nowrap>$name</td>
					<td nowrap>$ip</td>
					<td nowrap>$protocol</td>
					<td nowrap>$port</td>
					<td nowrap>" .
						WebButtonDelete("DeletePinholePort[$rule[ip]|$rule[protocol]|$rule[port]]") .
						WebButtonToggle("TogglePinholePort[$rule[enabled]|$rule[ip]|$rule[protocol]|$rule[port]]", $toggle) . "
					</td>
				</tr>
			";
		}
	}

	if (!$list)
		$list .= "<tr><td colspan='5' align='center'>" . FIREWALL_LANG_ERRMSG_RULES_NOT_DEFINED . "</td></tr>";

	WebFormOpen();
	WebTableOpen(WEB_LANG_DELETE_PINHOLE_TITLE, "100%");
	WebTableHeader(
		"&nbsp;|" .
		FIREWALL_LANG_NICKNAME . "|" . 
		NETWORK_LANG_IP . "|" .
		FIREWALL_LANG_PROTOCOL . "|" . 
		FIREWALL_LANG_PORT . "|" .
		"&nbsp;"
	);
	echo $list;
	WebTableClose("100%");
	WebFormClose();
}


///////////////////////////////////////////////////////////////////////////////
//
// DisplayAdd()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayAdd($dmz_nickname, $dmz_ip, $dmz_protocol, $dmz_port, $all_nickname, $all_ip)
{
	WebFormOpen();
	WebTableOpen(WEB_LANG_ADD_RULE_TITLE, "100%");
	WebTableHeader(
		FIREWALL_LANG_NICKNAME . "|" . 
		NETWORK_LANG_IP . "|" . 
		FIREWALL_LANG_PROTOCOL . "|" . 
		FIREWALL_LANG_PORT . "|"
	);
	echo "
	 <tr>
	   <td width='30%'><input type='text' name='dmz_nickname' value='$dmz_nickname' /></td>
	   <td width='15%'><input type='text' name='dmz_ip' value='$dmz_ip' style='width:100px' /></td>
	   <td width='20%'>" . WebDropDownArray("dmz_protocol", $dmz_protocol, array('TCP', 'UDP')) . "</td>
	   <td width='15%'><input type='text' name='dmz_port' value='$dmz_port' style='width:40px' /></td>
	   <td width='20%' nowrap>" . WebButtonAdd('AddForwardPort') . "</td>
	 </tr>
	 <tr>
	   <td><input type='text' name='all_nickname' value='$all_nickname' /></td>
	   <td><input type='text' name='all_ip' value='$all_ip' style='width:100px' /></td>
	   <td>" . WEB_LANG_ALL_PROTOCOLS . "</td>
	   <td>" . WEB_LANG_ALL_PORTS . "</td>
	   <td nowrap>" . WebButtonAdd('AddForward') . "</td>
	 </tr>
	";
	WebTableClose("100%");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayAddPinhole()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayAddPinhole($pinhole_nickname, $pinhole_ip, $pinhole_protocol, $pinhole_port, $pinhole_all_nickname, $pinhole_all_ip)
{
	WebFormOpen();
	WebTableOpen(WEB_LANG_ADD_PINHOLE_TITLE, "100%");
	WebTableHeader(
		FIREWALL_LANG_NICKNAME . "|" . NETWORK_LANG_IP . "|" . FIREWALL_LANG_PROTOCOL . "|" . FIREWALL_LANG_PORT . "|"
	);
	echo "
	 <tr>
	   <td width='30%'><input type='text' name='pinhole_nickname' value='$pinhole_nickname' /></td>
	   <td width='15%'><input type='text' name='pinhole_ip' value='$pinhole_ip' style='width:100px' /></td>
	   <td width='20%'>" . WebDropDownArray("pinhole_protocol", $pinhole_protocol, array('TCP', 'UDP')) . "</td>
	   <td width='15%'><input type='text' name='pinhole_port' value='$pinhole_port' style='width:40px' /></td>
	   <td width='20%' nowrap>" . WebButtonAdd('AddPinholePort') . "</td>
	 </tr>
	 <tr>
	   <td><input type='text' name='pinhole_all_nickname' value='$pinhole_all_nickname' /></td>
	   <td><input type='text' name='pinhole_all_ip' value='$pinhole_all_ip' style='width:100px' /></td>
	   <td>" . WEB_LANG_ALL_PROTOCOLS . "</td>
	   <td>" . WEB_LANG_ALL_PORTS . "</td>
	   <td nowrap>" . WebButtonAdd('AddPinhole') . "</td>
	 </tr>
	";
	WebTableClose("100%");
	WebFormClose();
}

?>
