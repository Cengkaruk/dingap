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
require_once("../../api/Firewall.class.php");
require_once("../../api/FirewallOutgoing.class.php");
require_once("firewall-common.inc.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-firewall-block.png", WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$firewall = new FirewallOutgoing();

$port_nickname = isset($_POST['port_nickname']) ? $_POST['port_nickname'] : "";
$port_protocol = isset($_POST['port_protocol']) ? $_POST['port_protocol'] : "";
$port = isset($_POST['port']) ? $_POST['port'] : "";
$range_nickname = isset($_POST['range_nickname']) ? $_POST['range_nickname'] : "";
$range_protocol = isset($_POST['range_protocol']) ? $_POST['range_protocol'] : "";
$range_from = isset($_POST['range_from']) ? $_POST['range_from'] : "";
$range_to = isset($_POST['range_to']) ? $_POST['range_to'] : "";
$block_nickname = isset($_POST['block_nickname']) ? $_POST['block_nickname'] : "";
$block_target = isset($_POST['block_target']) ? $_POST['block_target'] : "";

try {
	if ($_POST['AddBlockStandardService']) {
		$firewall->AddBlockStandardService($_POST['standard_service']);
		$firewall->Restart();
	} else if ($_POST['AddBlockPort']) {
		$firewall->AddBlockPort($port_nickname, $port_protocol, $port);
		$firewall->Restart();
	} else if ($_POST['AddBlockPortRange']) {
		$firewall->AddBlockPortRange($range_nickname, $range_protocol, $range_from, $range_to);
		$firewall->Restart();
	} else if ($_POST['DeleteBlockPort']) {
		list($protocol, $port) = explode("|", key($_POST['DeleteBlockPort']));
		$firewall->DeleteBlockPort($protocol, $port);
		$firewall->Restart();
	} else if ($_POST['DeleteBlockPortRange']) {
		list($protocol, $from, $to) = explode("|", key($_POST['DeleteBlockPortRange']));
		$firewall->DeleteBlockPortRange($protocol, $from, $to);
		$firewall->Restart();
	} else if ($_POST['DeleteBlockDestination']) {
		$firewall->DeleteBlockDestination(key($_POST['DeleteBlockDestination']));
		$firewall->Restart();
	} else if ($_POST['AddBlockDestination']) {
		$firewall->AddBlockDestination($block_nickname, $block_target);
		$firewall->Restart();
	} else if ($_POST['AddBlockCommonDestination']) {
		$firewall->AddBlockCommonDestination($_POST['commonservice']);
		$firewall->Restart();
	} else if ($_POST['ToggleBlockPort']) {
		list($enabled, $protocol, $port) = explode("|", key($_POST['ToggleBlockPort']));
		$firewall->ToggleEnableBlockPort(($enabled) ? false : true, $protocol, $port);
		$firewall->Restart();
	} else if ($_POST['ToggleBlockPortRange']) {
		list($enabled, $protocol, $from, $to) = explode("|", key($_POST['ToggleBlockPortRange']));
		$firewall->ToggleEnableBlockPortRange(($enabled) ? false : true, $protocol, $from, $to);
		$firewall->Restart();
	} else if ($_POST['ToggleBlockDestination']) {
		list($enabled, $host) = explode("|", key($_POST['ToggleBlockDestination']));
		$firewall->ToggleEnableBlockDestination(($enabled) ? false : true, $host);
		$firewall->Restart();
	} else if ($_POST['UpdateEgressMode']) {
		$state = ($_POST['egressmode'] == 'enabled') ? true : false;
		$firewall->SetEgressState($state);
		$firewall->Restart();
	}

    $errors = $firewall->GetValidationErrors(true);

    if (empty($errors)) {
		$port_nickname = "";
		$port_protocol = "";
		$port = "";
		$range_nickname = "";
		$range_protocol = "";
		$range_from = "";
		$range_to = "";
		$block_nickname = "";
		$block_target = "";
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

DisplayEgressMode();
DisplayPortRules($port_nickname, $port_protocol, $port, $range_nickname, $range_protocol, $range_from, $range_to);
DisplayDomainRules($block_nickname, $block_target);
WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayEgressMode()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayEgressMode()
{
	global $firewall;

	try {
		$egressstate = $firewall->GetEgressState();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	$egressmode = ($egressstate) ? 'enabled' : 'disabled';
	$on_off_options['enabled'] = WEB_LANG_BLOCK_ALL_AND_SPECIFY_ALLOW_DESTINATIONS;
	$on_off_options['disabled'] = WEB_LANG_ALLOW_ALL_AND_SPECIFY_BLOCK_DESTINATIONS;
	
	$modehtml = WebDropDownHash("egressmode", $egressmode, $on_off_options) . " &#160; " .
				WebButtonUpdate("UpdateEgressMode");

	WebFormOpen();
	WebTableOpen(WEB_LANG_BLOCK_MODE, "100%");
	echo "
		<tr>
			<td align='center'>$modehtml</td>
		</tr>
	";
	WebTableClose("100%");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayPortRules()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayPortRules($port_nickname, $port_protocol, $port, $range_nickname, $range_protocol, $range_from, $range_to)
{
	global $firewall;

	try {
		$ports = $firewall->GetBlockPorts();
		$ranges = $firewall->GetBlockPortRanges();
		$servicelist = $firewall->GetStandardServiceList();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	$list = "";
	$index = 0;

	if ($ports) {
		foreach ($ports as $rule) {
			$name = (strlen($rule['name'])) ? $rule['name'] : "-";

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
					<td nowrap>$rule[service]</td>
					<td nowrap>$rule[protocol]</td>
					<td nowrap>$rule[port]</td>
					<td nowrap>" .
					WebButtonDelete("DeleteBlockPort[$rule[protocol]|$rule[port]]") .
					WebButtonToggle("ToggleBlockPort[$rule[enabled]|$rule[protocol]|$rule[port]]", $toggle) . "
					</td>
				</tr>
			";
		}
	}

	if ($ranges) {
		foreach ($ranges as $rule) {
			$name = (strlen($rule['name'])) ? $rule['name'] : "-";

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
					<td nowrap>&#160; </td>
					<td nowrap>$rule[protocol]</td>
					<td nowrap>$rule[from] : $rule[to]</td>
					<td nowrap>" .
						WebButtonDelete("DeleteBlockPortRange[$rule[protocol]|$rule[from]|$rule[to]]") .
						WebButtonToggle("ToggleBlockPortRange[$rule[enabled]|$rule[protocol]|$rule[from]|$rule[to]]", $toggle) . "
					</td>
				</tr>
			";
		}
	}

	if (!$list)
		$list .= "<tr><td colspan='6' align='center'>" . FIREWALL_LANG_ERRMSG_RULES_NOT_DEFINED . "</td></tr>\n";

	$protocols = array('TCP', 'UDP', 'GRE', 'ESP', 'AH');

    $standard_dropdown = WebDropDownArray("standard_service", '', $servicelist);
    $port_protocol_dropdown = WebDropDownArray("port_protocol", $port_protocol, $protocols);
    $range_protocol_dropdown = WebDropDownArray("range_protocol", $range_protocol, $protocols);

	WebFormOpen();
	WebTableOpen(WEB_LANG_DESTINATION_PORTS, "100%");
	WebTableHeader(
		"&nbsp;|" .
		FIREWALL_LANG_NICKNAME . "|" . 
		FIREWALL_LANG_SERVICE . "|" . 
		FIREWALL_LANG_PROTOCOL . "|" .
		FIREWALL_LANG_PORT . "|" .
		"&nbsp; "
	);

	echo $list;
	echo "
		<tr>
			<td class='mytableheader' colspan='100'>" . LOCALE_LANG_ADD . "</td>
		</tr>
		<tr>
			<td colspan='2' class='mytablesubheader' nowrap>" . FIREWALL_LANG_STANDARD_SERVICES . "</td>
			<td nowrap colspan='3'>$standard_dropdown</td>
			<td nowrap>" .  WebButtonAdd('AddBlockStandardService') . " </td>
		</tr>
		<tr>
			<td colspan='2' class='mytablesubheader' nowrap>" . FIREWALL_LANG_NICKNAME . " / " . FIREWALL_LANG_PORT . "</td>
			<td nowrap colspan='3'>
				<input type='text' name='port_nickname' value='$port_nickname' />&#160;
				$port_protocol_dropdown
				<input type='text' name='port' value='$port' style='width:50px' />
			</td>
			<td nowrap>" . WebButtonAdd('AddBlockPort') . "</td>
		</tr>
		<tr>
			<td colspan='2' class='mytablesubheader' nowrap>" . FIREWALL_LANG_NICKNAME . " / " . FIREWALL_LANG_PORT_RANGE . "</td>
			<td nowrap colspan='3'>
				<input type='text' name='range_nickname' value='$range_nickname' />&#160;
				$range_protocol_dropdown
				<input type='text' name='range_from' value='$range_from' style='width:50px' />&#160;:&#160;
				<input type='text' name='range_to' value='$range_to' style='width:50px' />
			</td>
			<td nowrap>" . WebButtonAdd('AddBlockPortRange') . "</td>
		</tr>
	";
	WebTableClose("100%");
	WebFormClose();
}


///////////////////////////////////////////////////////////////////////////////
//
// DisplayDomainRules()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayDomainRules($block_nickname, $block_target)
{
	global $firewall;

	try {
		$hosts = $firewall->GetBlockHosts();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	$list = "";
	$index = 0;

	if ($hosts) {
		foreach ($hosts as $domain) {
			if (strlen($domain['metainfo']))
				$name = $domain['metainfo'];
			else
				$name = (strlen($domain['name'])) ? $domain['name'] : "-";

			if ($domain['enabled']) {
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
					<td nowrap>$domain[host]</td>
					<td nowrap>" .
						WebButtonDelete("DeleteBlockDestination[$domain[host]]") .
						WebButtonToggle("ToggleBlockDestination[$domain[enabled]|$domain[host]]", $toggle) . "
					</td>
				</tr>
			";
		}
	}

	WebFormOpen();
	WebTableOpen(WEB_LANG_DESTINATION_DOMAINS, "100%");
	WebTableHeader("|" . FIREWALL_LANG_NICKNAME . "|" . FIREWALL_LANG_DOMAIN_IP . "|");
	echo $list;
	echo "
	<tr>
		<td>&nbsp; </td>
		<td nowrap><input type='text' name='block_nickname' value='$block_nickname'  /></td>
		<td nowrap><input type='text' name='block_target' value='$block_target' /></td>
		<td nowrap>" . WebButtonAdd('AddBlockDestination') . "</td>
	</tr>
	";
	WebTableClose("100%");
	WebFormClose();
}

// vim: ts=4
?>
