<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2003-2007 Point Clark Networks.
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
require_once("../../api/FirewallForward.class.php");
require_once("../../api/FirewallIncoming.class.php");
require_once("firewall-common.inc.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-firewall-port-forward.png", WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$firewall = new FirewallForward();

$standard_nickname = isset($_POST['standard_nickname']) ? $_POST['standard_nickname'] : "";
$standard_service = isset($_POST['standard_service']) ? $_POST['standard_service'] : "";
$standard_ip = isset($_POST['standard_ip']) ? $_POST['standard_ip'] : "";
$port_nickname = isset($_POST['port_nickname']) ? $_POST['port_nickname'] : "";
$port_protocol = isset($_POST['port_protocol']) ? $_POST['port_protocol'] : "";
$port_from = isset($_POST['port_from']) ? $_POST['port_from'] : "";
$port_to = isset($_POST['port_to']) ? $_POST['port_to'] : "";
$port_ip = isset($_POST['port_ip']) ? $_POST['port_ip'] : "";
$range_nickname = isset($_POST['range_nickname']) ? $_POST['range_nickname'] : "";
$range_protocol = isset($_POST['range_protocol']) ? $_POST['range_protocol'] : "";
$range_low = isset($_POST['range_low']) ? $_POST['range_low'] : "";
$range_high = isset($_POST['range_high']) ? $_POST['range_high'] : "";
$range_ip = isset($_POST['range_ip']) ? $_POST['range_ip'] : "";

try {
	if ($_POST['AddForwardStandardService']) {
		$firewall->AddForwardStandardService($standard_nickname, $standard_service, $standard_ip);
		$firewall->Restart();
	} else if ($_POST['AddForwardPort']) {
		$firewall->AddForwardPort($port_nickname, $port_protocol, $port_from, $port_to, $port_ip);
		$firewall->Restart();
	} else if ($_POST['AddForwardPortRange']) {
		$firewall->AddForwardPortRange($range_nickname, $range_protocol, $range_low, $range_high, $range_ip);
		$firewall->Restart();
	} else if ($_POST['DeleteForwardPort']) {
		list($_POST['protocol'], $_POST['fromport'], $_POST['toport'], $_POST['toip']) = 
			explode("|", key($_POST['DeleteForwardPort'])
		);
		$firewall->DeleteForwardPort($_POST['protocol'], $_POST['fromport'], $_POST['toport'], $_POST['toip']);
		$firewall->Restart();
	} else if ($_POST['DeleteForwardPortRange']) {
		list($_POST['protocol'], $_POST['lowport'], $_POST['highport'],
			$_POST['toip']) = explode("|", key($_POST['DeleteForwardPortRange'])
		);
		$firewall->DeleteForwardPortRange($_POST['protocol'], $_POST['lowport'], $_POST['highport'], $_POST['toip']);
		$firewall->Restart();
	} else if ($_POST['DeletePptpServer']) {
		$firewall->SetPptpServer("");
		$firewall->Restart();
	} else if ($_POST['TogglePptpServer']) {
		list($_POST['enabled'], $_POST['ip']) = explode("|", key($_POST['TogglePptpServer']));
		$firewall->ToggleEnablePptpServer(($_POST['enabled']) ? false : true, $_POST['ip']);
		$firewall->Restart();
	} else if ($_POST['ToggleForwardPort']) {
		list($_POST['enabled'], $_POST['protocol'], $_POST['fromport'], $_POST['toport'],
			$_POST['toip']) = explode("|", key($_POST['ToggleForwardPort'])
		);
		$firewall->ToggleEnableForwardPort(
			($_POST['enabled']) ? false : true, $_POST['protocol'], $_POST['fromport'], $_POST['toport'], $_POST['toip']
		);
		$firewall->Restart();
	} else if ($_POST['ToggleForwardPortRange']) {
		list($_POST['enabled'], $_POST['protocol'], $_POST['lowport'], $_POST['highport'],
			$_POST['toip']) = explode("|", key($_POST['ToggleForwardPortRange'])
		);
		$firewall->ToggleEnableForwardPortRange(($_POST['enabled']) ? false : true,
		$_POST['protocol'], $_POST['lowport'], $_POST['highport'], $_POST['toip']);
		$firewall->Restart();
	}

	$errors = $firewall->GetValidationErrors(true);

	if (empty($errors)) {
		$standard_nickname = "";
		$standard_service = "";
		$standard_ip = "";
		$port_nickname = "";
		$port_protocol = "";
		$port_from = "";
		$port_to = "";
		$port_ip = "";
		$range_nickname = "";
		$range_protocol = "";
		$range_low = "";
		$range_high = "";
		$range_ip = "";
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

DisplayForward();
DisplayAdd(
	$standard_nickname, $standard_service, $standard_ip,
	$port_nickname, $port_protocol, $port_from, $port_to, $port_ip,
	$range_nickname, $range_protocol, $range_low, $range_high, $range_ip
);
WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayForward()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayForward()
{
	global $firewall;

	try {
		$ports = $firewall->GetForwardPorts();
		$ranges = $firewall->GetForwardPortRanges();
		$pptpfw = $firewall->GetPptpServer();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	$list = "";
	$index = 0;

	if ($ports) {
		foreach ($ports as $rule) {

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
					<td nowrap>$rule[nickname]</td>
					<td nowrap>$rule[service]</td>
					<td nowrap>$rule[protocol]</td>
					<td nowrap>$rule[fromport]</td>
					<td nowrap>" . WEBCONFIG_ICON_ARROWRIGHT . "</td>
					<td nowrap>$rule[toport]</td>
					<td nowrap>$rule[toip]</td>
					<td nowrap>" .
						WebButtonDelete("DeleteForwardPort[$rule[protocol]|$rule[fromport]|$rule[toport]|$rule[toip]]") .
						WebButtonToggle("ToggleForwardPort[$rule[enabled]|$rule[protocol]|$rule[fromport]|$rule[toport]|$rule[toip]]", $toggle) . "
					</td>
				</tr>
			";
		}
	}

	if ($ranges) {
		foreach ($ranges as $rule) {

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
					<td nowrap>$rule[nickname]</td>
					<td nowrap>$rule[service]</td>
					<td nowrap>$rule[protocol]</td>
					<td nowrap>$rule[lowport] : $rule[highport]</td>
					<td nowrap>" . WEBCONFIG_ICON_ARROWRIGHT . "</td>
					<td nowrap>$rule[lowport] : $rule[highport]</td>
					<td nowrap>$rule[toip]</td>
					<td nowrap>" .
						WebButtonDelete("DeleteForwardPortRange[$rule[protocol]|$rule[lowport]|$rule[highport]|$rule[toip]]") .
						WebButtonToggle("ToggleForwardPortRange[$rule[enabled]|$rule[protocol]|$rule[lowport]|$rule[highport]|$rule[toip]]", $toggle) . "
					</td>
				</tr>
			";
		}
	}

	if ($pptpfw) {
		$iconclass = "iconenabled";
		$rowclass = "rowenabled";
		$rowclass .= ($index % 2) ? "alt" : "";

		$list .= "
			<tr class='$rowclass'>
				<td class='$iconclass'>&nbsp; </td>
				<td nowrap>-</td>
				<td nowrap>PPTP</td>
				<td nowrap>GRE + TCP</td>
				<td nowrap>1723</td>
				<td nowrap>" . WEBCONFIG_ICON_ARROWRIGHT . "</td>
				<td nowrap>1723</td>
				<td nowrap>$pptpfw[host]</td>
				<td nowrap>" .  WebButtonDelete("DeletePptpServer") . "</td>
			</tr>
		";
	}

	if (!$list)
		$list .= "<tr><td colspan='8' align='center'>" . FIREWALL_LANG_ERRMSG_RULES_NOT_DEFINED . "</td></tr>\n";

	WebFormOpen();
	WebTableOpen(WEB_LANG_CONFIG_TITLE, "100%");
	WebTableHeader(
		"&nbsp;|" . FIREWALL_LANG_NICKNAME . "|" . FIREWALL_LANG_SERVICE . "|" .
		FIREWALL_LANG_PROTOCOL . "|" . WEB_LANG_FROM_PORT . "||" .
		WEB_LANG_TO_PORT . "|" . WEB_LANG_TO_IP . "|&nbsp; "
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

function DisplayAdd(
	$standard_nickname, $standard_service, $standard_ip,
	$port_nickname, $port_protocol, $port_from, $port_to, $port_ip,
	$range_nickname, $range_protocol, $range_low, $range_high, $range_ip
)
{
	global $firewall;

	try {
		$servicelist = $firewall->GetStandardServiceList();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}
	
	$protocols = array('TCP', 'UDP');

	$standard_dropdown = WebDropDownArray("standard_service", '', $servicelist);
	$port_protocol_dropdown = WebDropDownArray("port_protocol", $port_protocol, $protocols);
	$range_protocol_dropdown = WebDropDownArray("range_protocol", $range_protocol, $protocols);

	WebFormOpen();
	WebTableOpen(WEB_LANG_ADD_TITLE, "100%");
	WebTableHeader(	
		FIREWALL_LANG_NICKNAME . "|" . FIREWALL_LANG_SERVICE . "||||" . WEB_LANG_TO_IP . "|"
	);
	echo "
	 <tr>
	   <td nowrap><input type='text' name='standard_nickname' value='$standard_nickname' /></td>
	   <td colspan='2' nowrap>" . $standard_dropdown . "</td>
	   <td width='15'>" . WEBCONFIG_ICON_ARROWRIGHT . "</td>
	   <td>&#160; </td>
	   <td nowrap><input type='text' name='standard_ip' value='$standard_ip' /></td>
	   <td nowrap>". WebButtonAdd('AddForwardStandardService') . "</td>
	 </tr>
	";
	WebTableHeader(FIREWALL_LANG_NICKNAME . "|" . FIREWALL_LANG_PROTOCOL . "|" .
		WEB_LANG_FROM_PORT . "||" . WEB_LANG_TO_PORT . "|" . WEB_LANG_TO_IP . "|");
	echo "
	 <tr>
	   <td nowrap><input type='text' name='port_nickname' value='$port_nickname' /></td>
	   <td nowrap>" . $port_protocol_dropdown . "</td>
	   <td nowrap><input type='text' name='port_from' value='$port_from' style='width:40px'></td>
	   <td width='15'>" . WEBCONFIG_ICON_ARROWRIGHT . "</td>
	   <td nowrap><input type='text' name='port_to' value='$port_to' style='width:40px' /></td>
	   <td nowrap><input type='text' name='port_ip' value='$port_ip' /></td>
	   <td nowrap>". WebButtonAdd('AddForwardPort') ."</td>
	 </tr>
	";
	WebTableHeader(
		FIREWALL_LANG_NICKNAME . "|" . FIREWALL_LANG_PROTOCOL . "|" .
		FIREWALL_LANG_PORT_RANGE . "|||" . WEB_LANG_TO_IP . "|"
	);
	echo "
	 <tr>
	   <td nowrap><input type='text' name='range_nickname' value='$range_nickname' /></td>
	   <td nowrap>" . $range_protocol_dropdown . "</td>
	   <td nowrap>
		 <input type='text' name='range_low' value='$range_low' style='width:40px' /> :
		 <input type='text' name='range_high' value='$range_high' style='width:40px' />
	   </td>
	   <td width='15'>" . WEBCONFIG_ICON_ARROWRIGHT . "</td>
	   <td>&#160; </td>
	   <td nowrap><input type='text' name='range_ip' value='$range_ip' /></td>
	   <td nowrap>". WebButtonAdd('AddForwardPortRange') ."</td>
	 </tr>
	";
	WebTableClose("100%");
	WebFormClose();
}

// vim: ts=4
?>
