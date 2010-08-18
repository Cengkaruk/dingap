<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2003-2009 Point Clark Networks.
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
require_once("../../api/IfaceManager.class.php");
require_once("../../api/Firewall.class.php");
require_once("../../api/FirewallMultiWan.class.php");
require_once("../../api/Syswatch.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-multiwan.png", WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$multiwan = new FirewallMultiWan();
$firewall = new Firewall();
$syswatch = new Syswatch();
$wanifs = $multiwan->GetExternalInterfaces();
$interface = new Iface("null"); // Just for locale
$network = new Network(); // Just for locale

$dest_nickname = isset($_POST['dest_nickname']) ? $_POST['dest_nickname'] : "";
$dest_protocol = isset($_POST['dest_protocol']) ? $_POST['dest_protocol'] : "";
$dest_port = isset($_POST['dest_port']) ? $_POST['dest_port'] : "";
$dest_ifn = isset($_POST['dest_ifn']) ? $_POST['dest_ifn'] : "";
$sbr_nickname = isset($_POST['sbr_nickname']) ? $_POST['sbr_nickname'] : "";
$sbr_ip = isset($_POST['sbr_ip']) ? $_POST['sbr_ip'] : "";
$sbr_ifn = isset($_POST['sbr_ifn']) ? $_POST['sbr_ifn'] : "";

try {
	if (isset($_POST['UpdateMultiWan'])) {
		$multiwan->SetDynamicDnsInterface($_POST['dynamicdns']);
		foreach ($_POST['weight'] as $interface => $value)
			$multiwan->SetInterfaceWeight($interface, $value);
		$firewall->Restart();
	} else if (isset($_POST['AddSourceBasedRoute'])) {
		$multiwan->AddSourceBasedRoute($sbr_nickname, $sbr_ip, $sbr_ifn);
		$firewall->Restart();
	} else if (isset($_POST['DeleteSourceBasedRoute'])) {
		list($source, $ifn) = explode("|", key($_POST['DeleteSourceBasedRoute']));
		$multiwan->DeleteSourceBasedRoute($source, $ifn);
		$firewall->Restart();
	} else if (isset($_POST['ToggleSourceBasedRoute'])) {
		list($enabled, $source, $ifn) = explode("|", key($_POST['ToggleSourceBasedRoute']));
		$multiwan->ToggleEnableSourceBasedRoute(($enabled) ? false : true, $source, $ifn);
		$firewall->Restart();
	} else if (isset($_POST['AddPortRule'])) {
		$multiwan->AddDestinationPortRule($dest_nickname, $dest_protocol, $dest_port, $dest_ifn);
		$firewall->Restart();
	} else if (isset($_POST['DeletePortRule'])) {
		list($proto, $port, $ifn) = explode("|", key($_POST['DeletePortRule']));
		$multiwan->DeleteDestinationPortRule($proto, $port, $ifn);
		$firewall->Restart();
	} else if (isset($_POST['TogglePortRule'])) {
		list($enabled, $proto, $port, $ifn) = explode("|", key($_POST['TogglePortRule']));
		$multiwan->ToggleEnablePortRule(($enabled) ? false : true, $proto, $port, $ifn);
		$firewall->Restart();
	} else if (isset($_POST['EnableMultiWan'])) {
		$enabled = ($multiwan->IsEnabled()) ? false : true;
		$firewall->Restart();
		$syswatch->Reset();
	}

	$errors = $multiwan->GetValidationErrors(true);

	if (empty($errors)) {
		$dest_nickname = "";
		$dest_protocol = "";
		$dest_port = "";
		$dest_ifn = "";
		$sbr_nickname = "";
		$sbr_ip = "";
		$sbr_ifn = "";
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
	// TODO: it should be possible to enable/disable mulitwan
	$multiwan->EnableMultiWan(true);
} catch (Exception $e) {
	WebDialogWarning($e->GetMessage());
}

if (count($wanifs) < 2) {
	WebDialogWarning(WEB_LANG_PAGE_WANIF);
} else {
	$multiwan->SanityCheckDmz(false);
}


DisplayInterfaceWeights();
DisplaySourceBasedRoutes($sbr_nickname, $sbr_ip, $sbr_ifn);
DisplayPortRules($dest_nickname, $dest_protocol, $dest_port, $dest_ifn);

WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayInterfaceWeights()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayInterfaceWeights()
{
	global $multiwan;
	global $wanifs;
	global $syswatch;

	try {
		$dnsif = $multiwan->GetDynamicDnsInterface();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	try {
		$working = $syswatch->GetWorkingExternalInterfaces();
		$inuse = $syswatch->GetInUseExternalInterfaces();
	} catch (SyswatchUnknownStateException $e) {
		$working = null;
		$inuse = null;
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	$list = "";
	$weight_options = array(1,2,3,4,5,6,7,8,9,10,15,20,25,30,35,40,45,50,75,100,200);

	if ($dnsif == '') 
		$dnsif = $wanifs[0];

	foreach ($wanifs as $ifn) {
		$interface = new Iface($ifn);
		$ip = $interface->GetLiveIp($ifn);
		$weight = $multiwan->GetInterfaceWeight($ifn);

		if (is_null($working))
			$status = LOCALE_LANG_UNKNOWN;
		else if (in_array($ifn, $working))
			$status = "<span class='ok'>" . NETWORK_LANG_CONNECTED . "</span>";
		else
			$status = "<span class='alert'>" . NETWORK_LANG_OFFLINE . "</span>";

		if ($ifn == $dnsif)
			$dyndns = "<input type='radio' name='dynamicdns' value='$ifn' checked>";
		else
			$dyndns = "<input type='radio' name='dynamicdns' value='$ifn'>";

		if (is_null($inuse))
			$active = LOCALE_LANG_UNKNOWN;
		else if (in_array($ifn, $inuse))
			$active = "<span class='ok'>" . WEB_LANG_IN_USE . "</span>";
		else
			$active = "<span class='alert'>" . WEB_LANG_NOT_IN_USE . "</span>";

		$list .= "
		 <tr>
		  <td nowrap>$ifn</td>
		  <td nowrap>$ip</td>
		  <td nowrap>$status</td>
		  <td nowrap>$active</td>
		  <td nowrap>$dyndns</td>
		  <td nowrap>" . WebDropDownArray("weight[$ifn]", $weight, $weight_options) . "</td>
		 </tr>
		";
	}

	if (!$list)
		return;

	WebFormOpen();
	WebTableOpen(WEB_LANG_UPDATE_WEIGHT_TITLE, "100%");
	WebTableHeader(
		IFACE_LANG_INTERFACE . "|" . 
		NETWORK_LANG_IP . "|" .
		NETWORK_LANG_CONNECTION_STATUS . "|" .
		WEB_LANG_MULTIWAN_STATUS . "|" .
		WEB_LANG_DYNAMIC_DNS . "|" .
		FIREWALLMULTIWAN_LANG_WEIGHT
	);
	echo "
		$list
		<tr>
			<td colspan='4'>&nbsp; </td>
			<td colspan='2' align='center'>" . WebButtonUpdate("UpdateMultiWan") . "</td>
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

function DisplayPortRules($dest_nickname, $dest_protocol, $dest_port, $dest_ifn)
{
	global $multiwan;
	global $wanifs;

	try {
		$rules = $multiwan->GetPortRules();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	$list = "";
	$index = 0;

	if ($rules) {
		foreach ($rules as $rule) {
			$name = (strlen($rule['name'])) ? $rule['name'] : "-";
			$proto = strtoupper(getprotobynumber($rule['proto']));

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
					<td>$name</td>
					<td>$proto</td>
					<td>$rule[port]</td>
					<td>$rule[ifn]</td>
					<td nowrap>" .
						WebButtonDelete("DeletePortRule[$rule[proto]|$rule[port]|$rule[ifn]]") .
						WebButtonToggle("TogglePortRule[$rule[enabled]|$rule[proto]|$rule[port]|$rule[ifn]]", $toggle) . "
					</td>
				</tr>
			";
		}
	}

	if (!$list)
		$list .= "<tr><td colspan='5' align='center'>" . FIREWALL_LANG_ERRMSG_RULES_NOT_DEFINED . "</td></tr>";

	$list .= "
		<tr>
			<td>&nbsp;</td>
			<td><input type='text' name='dest_nickname' value='$dest_nickname' size='20' /></td>
			<td>" . WebDropDownArray("dest_protocol", $dest_protocol, array('TCP', 'UDP')) . "</td>
			<td><input type='text' name='dest_port' value='$dest_port' size='6' /></td>
			<td>" . WebDropDownArray("dest_ifn", $dest_ifn, $wanifs) . "</td>
			<td nowrap>" . WebButtonAdd('AddPortRule') . "</td>
		</tr>
	";

	WebFormOpen();
	WebTableOpen(WEB_LANG_ADD_PORT_RULE, "100%");
	WebTableHeader(
		"&nbsp;|" .
		FIREWALL_LANG_NICKNAME . "|" . 
		FIREWALL_LANG_PROTOCOL . "|" . 
		FIREWALL_LANG_PORT . "|" .
		IFACE_LANG_INTERFACE . "|" . 
		"&nbsp;"
	);
	echo $list;
	WebTableClose("100%");
	WebFormClose();
}


///////////////////////////////////////////////////////////////////////////////
//
// DisplaySourceBasedRules()
//
///////////////////////////////////////////////////////////////////////////////

function DisplaySourceBasedRoutes($sbr_nickname, $sbr_ip, $sbr_ifn)
{
	global $multiwan;
	global $wanifs;

	try {
		$rules = $multiwan->GetSourceBasedRoutes();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	$list = "";
	$index = 0;

	if ($rules) {
		foreach ($rules as $rule) {
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
					<td>$name</td>
					<td>$rule[source]</td>
					<td>$rule[ifn]</td>
					<td nowrap>" .
						WebButtonDelete("DeleteSourceBasedRoute[$rule[source]|$rule[ifn]]") .
						WebButtonToggle("ToggleSourceBasedRoute[$rule[enabled]|$rule[source]|$rule[ifn]]", $toggle) . "
					</td>
				</tr>
			";
		}
	}

	if (!$list)
		$list .= "<tr><td colspan='4' align='center'>" . FIREWALL_LANG_ERRMSG_RULES_NOT_DEFINED . "</td></tr>";

	$list .= "
		<tr>
			<td>&nbsp;</td>
			<td><input type='text' name='sbr_nickname' value='$sbr_nickname' size='20' /></td>
			<td><input type='text' name='sbr_ip' value='$sbr_ip' size='15' /></td>
			<td>" . WebDropDownArray("sbr_ifn", $sbr_ifn, $wanifs) . "</td>
			<td nowrap>" . WebButtonAdd('AddSourceBasedRoute') . "</td>
		</tr>
	";

	WebFormOpen();
	WebTableOpen(WEB_LANG_ADD_SBR, "100%");
	WebTableHeader(
		"&nbsp;|" .
		FIREWALL_LANG_NICKNAME . "|" . 
		NETWORK_LANG_IP . "|" . 
		IFACE_LANG_INTERFACE . "|" . 
		"&nbsp;"
	);
	echo $list;
	WebTableClose("100%");
	WebFormClose();
}

// vi: ts=4
?>
