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
// TODO: domain name should come from Organization 
// installer should at least use the hostname

require_once("../../gui/Webconfig.inc.php");
require_once("../../api/DnsMasq.class.php");
require_once("../../api/Iface.class.php");
require_once("../../api/Hosts.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
if (!WEBCONFIG_CONSOLE) 
	WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-dhcp.png", WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$hosts = new Hosts(); // Just for language tags
$dnsmasq = new Dnsmasq();
$lointerface = new Iface("lo"); // Just for language tags

$ip = "";
$mac = "";

try {
	if (isset($_POST['EnableDhcp'])) {

		$dnsmasq->SetDhcpState(true);
		$dnsmasq->SetRunningState(true);

	} elseif (isset($_POST['DisableDhcp'])) {

		$dnsmasq->SetDhcpState(false);

	} elseif (isset($_POST['UpdateConfig'])) {
		if ((bool)$_POST['authoritative'])
			$dnsmasq->SetAuthoritativeState(true);
		else
			$dnsmasq->SetAuthoritativeState(false);
	
		$dnsmasq->SetDomainName($_POST['domain']);
		$dnsmasq->Reset();
	
	} elseif (isset($_POST['DeleteSubnet'])) {
	
		$dnsmasq->DeleteSubnet(key($_POST['DeleteSubnet']));
		$dnsmasq->Reset();
	
	} elseif (isset($_POST['UpdateSubnet'])) {
	
		$interface = isset($_POST['interface']) ? $_POST['interface'] : "";
		$network = isset($_POST['network']) ? $_POST['network'] : "";
		$gateway = isset($_POST['gateway']) ? $_POST['gateway'] : "";
		$start = isset($_POST['start']) ? $_POST['start'] : "";
		$end = isset($_POST['end']) ? $_POST['end'] : "";
		$dns = isset($_POST['dns']) ? $_POST['dns'] : array();
		$wins = isset($_POST['wins']) ? $_POST['wins'] : "";
		$leasetime = isset($_POST['leasetime']) ? $_POST['leasetime'] : "";
		$tftp = isset($_POST['tftp']) ? $_POST['tftp'] : "";
		$ntp = isset($_POST['ntp']) ? $_POST['ntp'] : "";
	
		$dnsmasq->UpdateSubnet($interface, $gateway, $start, $end, $dns, $wins, $leasetime, $tftp, $ntp);
		$dnsmasq->Reset();
		unset($_POST);
	
	} elseif (isset($_POST['AddStatic'])) {

		$ip = $_POST['ip'];
		$mac = $_POST['mac'];
		$dnsmasq->AddStaticLease($mac, $ip);
		$dnsmasq->Reset();
		$ip = "";
		$mac = "";
	
	} elseif (isset($_POST['ConvertToStatic'])) {

		list($mac, $ip) = explode("|", key($_POST['ConvertToStatic']));
		$dnsmasq->AddStaticLease($mac, $ip);
		$dnsmasq->Reset();
		$ip = "";
		$mac = "";
	
	} elseif (isset($_POST['DeleteStatic'])) {

		$dnsmasq->DeleteStaticLease(key($_POST['DeleteStatic']));
		$dnsmasq->Reset();

	}

} catch (ValidationException $e) {
	WebDialogWarning(WebCheckErrors($dnsmasq->GetValidationErrors(true)));
} catch (Exception $e) {
	WebDialogWarning($e->GetMessage());
}
	
///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

if (isset($_POST['DisplayAddSubnet'])) {
	DisplaySubnet('add', key($_POST['DisplayAddSubnet']));
} else if (isset($_POST['DisplayUpdateSubnet'])) {
	DisplaySubnet('edit', key($_POST['DisplayUpdateSubnet']));
} else if (isset($_POST['UpdateSubnet'])) {
	DisplaySubnet('error', $interface, $network, $gateway, $start, $end, $dns, $wins, $leasetime, $tftp, $ntp);
} else {
	DisplayServerStatus();
	DisplayConfig();
	DisplayAllSubnets();

	if (!WEBCONFIG_CONSOLE) {
		DisplayDynamicLeases();
		DisplayStaticLeases($ip, $mac);
	} else {
		echo WebUrlJump("network.php", "Configure Network Settings");
	}

}


WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayServerStatus()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayServerStatus()
{
	global $dnsmasq;

	try {
		$is_enabled = $dnsmasq->GetDhcpState();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	// TODO: Merge with WebDialogDaemon

    if ($is_enabled) {
        $status_button = WebButtonToggle("DisableDhcp", DAEMON_LANG_STOP);
        $status = "<span class='ok'><b>" . DAEMON_LANG_RUNNING . "</b></span>";
    } else {
        $status_button = WebButtonToggle("EnableDhcp", DAEMON_LANG_START);
        $status = "<span class='alert'><b>" . DAEMON_LANG_STOPPED . "</b></span>";
    }

    $content = "
        <form action='' method='post'>
        <table width='100%' border='0' cellspacing='0' cellpadding='0' align='center'>
            <tr>
                <td nowrap align='right'><b>" . DAEMON_LANG_STATUS . " -</b>&#160; </td>
                <td nowrap><b>$status</b></td>
                <td width='10'>&#160; </td>
                <td width='100'>$status_button</td>
                <td width='10'>&#160; </td>
                <td rowspan='2'>" . DAEMON_LANG_WARNING_START . "</td>
            </tr>
        </table>
        </form>
    ";

	WebDialogBox("dialogdaemon", WEBCONFIG_LANG_SERVER_STATUS, WEBCONFIG_DIALOG_ICON_DAEMON, $content);
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayConfig()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayConfig()
{
	global $dnsmasq;

	try {
		$domain = $dnsmasq->GetDomainName();
		$is_authoritative = $dnsmasq->GetAuthoritativeState();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	WebFormOpen();
	WebTableOpen(WEB_LANG_TITLE_GLOBAL, "400");
	echo "
	<tr>
		<td class='mytablesubheader' nowrap>" . DNSMASQ_LANG_AUTHORITATIVE . "</td>
		<td>" . WebDropDownEnabledDisabled("authoritative", $is_authoritative) . "</td>
	</tr>
	<tr>
		<td class='mytablesubheader' nowrap>" . DNSMASQ_LANG_DOMAIN . "</td>
		<td><input type='text' name='domain' value='$domain' /></td>
	</tr>
	<tr>
		<td class='mytablesubheader'>&#160; </td>
		<td nowrap>" . WebButtonUpdate('UpdateConfig') . "</td>
	</tr>
	";
	WebTableClose("400");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayAllSubnets()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayAllSubnets()
{
	global $dnsmasq;

	try {
		$subnets = $dnsmasq->GetSubnets();
		$ethlist = $dnsmasq->GetDhcpInterfaces();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	// Load defaults in DHCP-less interfaces
	//--------------------------------------

	foreach ($ethlist as $eth) {
		if (!isset($subnets[$eth]["isconfigured"])) {
			try {
				$ethinfo = new Iface($eth);
				$ethip = $ethinfo->GetLiveIp();
				// Bail on interface if no IP exists
				if (! $ethip)
					continue;
				$netcheck = new Network();
				$ethnetmask = $ethinfo->GetLiveNetmask();
				$ethnetwork = $netcheck->GetNetworkAddress($ethip, $ethnetmask);
			} catch (Exception $e) {
				WebDialogWarning($e->GetMessage());
			}

			$subnets[$eth]["network"] = $ethnetwork;
			$subnets[$eth]["netmask"] = $ethnetmask;
			$subnets[$eth]["isvalid"] = true;
			$subnets[$eth]["isconfigured"] = false;
			$subnets[$eth]["start"] = "";
			$subnets[$eth]["end"] = "";
		}
	}

	// Loop through subnet info and display it in HTML table
	//------------------------------------------------------

	foreach ($subnets as $interface => $subnetinfo) {
		$network = $subnetinfo["network"];
		$netmask = $subnetinfo["netmask"];
		$start = $subnetinfo["start"];
		$end = $subnetinfo["end"];

		if (! $subnetinfo["isvalid"]) {
			$status = "<span class='alert'>" . LOCALE_LANG_INVALID . "</span>";
			$button = WebButtonDelete("DeleteSubnet[$interface]");
		} else if ($subnetinfo["isconfigured"]) {
			$status = "<span class='ok'>" . LOCALE_LANG_ENABLED . "</span>";
			$button = WebButtonEdit("DisplayUpdateSubnet[$interface]") .
					WebButtonDelete("DeleteSubnet[$interface]");
		} else {
			$status = "<span class='alert'>" . LOCALE_LANG_DISABLED . "</span>";
			$button = WebButtonAdd("DisplayAddSubnet[$interface]");
		}

		$thelist[] = "
			<tr>
				<td>$interface</td>
				<td>$network</td>
				<td>$status</td>
				<td>$start</td>
				<td>$end</td>
				<td>$button</td>
			</tr>
		";
	}

	sort($thelist);
	$thelist_output = implode("\n", $thelist);

	WebFormOpen();
	WebTableOpen(WEB_LANG_TITLE_SUBNET, "100%");
	WebTableHeader("|" . DNSMASQ_LANG_NETWORK . "|" . LOCALE_LANG_STATUS . "|" . DNSMASQ_LANG_LOW_IP . "|" . DNSMASQ_LANG_HIGH_IP . "|");
	echo $thelist_output;
	WebTableClose("100%");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplaySubnet()
//
///////////////////////////////////////////////////////////////////////////////

function DisplaySubnet(
	$type,
	$interface,
	$network = "",
	$gateway = "",
	$start = "",
	$end = "",
	$dns = "",
	$wins = "",
	$leasetime = "",
	$tftp = "",
	$ntp = "")
{
	global $dnsmasq;

	// Handy dropdown options box used in lots of places
	$leasetime_options = array();
	$leasetime_options[12] = 12 . " " . LOCALE_LANG_HOURS;
	$leasetime_options[24] = 24 . " " . LOCALE_LANG_HOURS;
	$leasetime_options[48] = 2 . " " . LOCALE_LANG_DAYS;
	$leasetime_options[72] = 3 . " " . LOCALE_LANG_DAYS;
	$leasetime_options[96] = 4 . " " . LOCALE_LANG_DAYS;
	$leasetime_options[120] = 5 . " " . LOCALE_LANG_DAYS;
	$leasetime_options[144] = 6 . " " . LOCALE_LANG_DAYS;
	$leasetime_options[168] = 7 . " " . LOCALE_LANG_DAYS;
	$leasetime_options[336] = 2 . " " . LOCALE_LANG_WEEKS;
	$leasetime_options[504] = 3 . " " . LOCALE_LANG_WEEKS;
	$leasetime_options[672] = 4 . " " . LOCALE_LANG_WEEKS;
	$leasetime_options[Dnsmasq::CONSTANT_UNLIMITED_LEASE] = LOCALE_LANG_UNLIMITED;

	// Adding a subnet - calculate sane default values
	//------------------------------------------------

	if ($type == "add") {

		try {
			$netcheck = new Network();
			$ethinfo = new Iface($interface);
			$ip = $ethinfo->GetLiveIp();
			$netmask = $ethinfo->GetLiveNetmask();
			$network = $netcheck->GetNetworkAddress($ip, $netmask);
			$broadcast = $netcheck->GetBroadcastAddress($ip, $netmask);
		} catch (Exception $e) {
			WebDialogWarning($e->GetMessage());
			return;
		}

		// Add some intelligent defaults
		$long_nw = ip2long($network);
		$long_bc = ip2long($broadcast);

		if (empty($start))
			$start = long2ip($long_bc - round(($long_bc - $long_nw )* 3 / 5,0) - 2);

		if (empty($end))
			$end = long2ip($long_bc - 1);

		$gateway = $ip;
		$leasetime = "24";
		$dns[] = $ip;

	// Updating a subnet - display existing values
	//--------------------------------------------

	} else if ($type == "edit") {

		try {
			$subnets = $dnsmasq->GetSubnets();
		} catch (Exception $e) {
			WebDialogWarning($e->GetMessage());
			return;
		}

		$gateway = isset($subnets[$interface]['gateway']) ? $subnets[$interface]['gateway'] : "";
		$network = isset($subnets[$interface]['network']) ? $subnets[$interface]['network'] : "";
		$start = isset($subnets[$interface]['start']) ? $subnets[$interface]['start'] : "";
		$end = isset($subnets[$interface]['end']) ? $subnets[$interface]['end'] : "";
		$wins = isset($subnets[$interface]['wins']) ? $subnets[$interface]['wins'] : "";
		$leasetime = isset($subnets[$interface]['leasetime']) ? $subnets[$interface]['leasetime'] : "";
		$dns = isset($subnets[$interface]['nameservers']) ? $subnets[$interface]['nameservers'] : array();
		$tftp = isset($subnets[$interface]['tftp']) ? $subnets[$interface]['tftp'] : "";
		$ntp = isset($subnets[$interface]['ntp']) ? $subnets[$interface]['ntp'] : "";
	} else {
		$nameservers = $dns;
	}

	$dns_out = "";

	for ($i = 0; $i < 3; $i++) {
		$server = isset($dns[$i]) ? $dns[$i] : "";
		$dns_out .= "
			<tr>
				<td class='mytablesubheader' nowrap>" . DNSMASQ_LANG_DNS . " #" . sprintf("%d", $i + 1) . "</td>
				<td><input type='text' name='dns[]' value='" . $server . "'></td>
			</tr>
		";
	}

	WebFormOpen();
	WebTableOpen(WEB_LANG_TITLE_SUBNET, "400");
	echo "
		<tr>
			<td class='mytablesubheader' nowrap>" . IFACE_LANG_INTERFACE . "</td>
			<td><input type='text' name='interface' value='$interface' readonly class='readonly' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . DNSMASQ_LANG_NETWORK . "</td>
			<td><input type='text' name='network' value='$network' readonly class='readonly' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . DNSMASQ_LANG_LEASE_TIME . "</td>
			<td>" . WebDropDownHash("leasetime", $leasetime, $leasetime_options) . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . DNSMASQ_LANG_ROUTER . "</td>
			<td><input type='text' name='gateway' value='$gateway' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . DNSMASQ_LANG_LOW_IP . "</td>
			<td><input type='text' name='start' value='$start' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . DNSMASQ_LANG_HIGH_IP . "</td>
			<td><input type='text' name='end' value='$end' /></td>
		</tr>
		$dns_out
		<tr>
			<td class='mytablesubheader' nowrap>" . DNSMASQ_LANG_NETBIOS . "</td>
			<td><input type='text' name='wins' value='$wins' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . DNSMASQ_LANG_TFTP . "</td>
			<td><input type='text' name='tftp' value='$tftp' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . DNSMASQ_LANG_NTP . "</td>
			<td><input type='text' name='ntp' value='$ntp' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader'>&#160; </td>
			<td>
				" . WebButtonUpdate("UpdateSubnet[$interface]") . "
				" . WebButtonCancel("") . "
			</td>
	  </tr>
	";
	WebTableClose("400");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayStaticLeases()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayStaticLeases($ip, $mac)
{
	global $dnsmasq;

	try {
		$leases = $dnsmasq->GetLeases();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	$rows = "";

	foreach ($leases as $key => $details) {
		if ($details['is_static']) {
			$id = $details['static_mac'];

			if ($details['is_active']) {
				$active = ($details['active_end'] == 0) ? LOCALE_LANG_UNLIMITED : strftime('%c', $details['active_end']);
				$action = WebButton("DeleteStatic[$id]", WEB_LANG_BUTTON_CHANGE_TO_DYNAMIC, WEBCONFIG_ICON_CONTINUE);
			} else {
				$active = "<span class='alert'>" . "Inactive" . "</span>";
				$action = WebButton("DeleteStatic[$id]", WEB_LANG_BUTTON_DELETE_STATIC_LEASE, WEBCONFIG_ICON_CONTINUE);
			}

			$rows .= "
				<tr>
					<td nowrap>" . $details['static_ip'] . "</td>
					<td nowrap>" . $details['static_mac'] . "</td>
					<td nowrap>" . $details['hostname'] . "</td>
					<td nowrap>" . $active . "</td>
					<td nowrap>" . $action . "</td>
				</tr>";
		}
	}

	if (! $rows)
		$rows = "<tr><td colspan='4' align='center'>" . WEB_LANG_NO_STATIC_LEASES . "</td></tr>";

	WebFormOpen();
	WebTableOpen(DNSMASQ_LANG_STATIC_LEASES, "100%");
	WebTableHeader(NETWORK_LANG_IP . "|" . NETWORK_LANG_MAC_ADDRESS . "|" . HOSTS_LANG_HOSTNAME . "|" . LOCALE_LANG_EXPIRES . "|");
	echo "
		$rows
		<tr>
			<td><input type='text' name='ip' value='$ip' size='17'></td>
			<td><input type='text' name='mac' value='$mac' size='17'></td>
			<td>&#160; </td>
			<td>&#160; </td>
			<td nowrap>" . WebButtonAdd("AddStatic") . "</td>
		</tr>
	";
	WebTableClose("100%");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayDynamicLeases()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayDynamicLeases()
{
	global $dnsmasq;

	try {
		$leases = $dnsmasq->GetLeases();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	$rows = "";

	foreach ($leases as $key => $details) {
		if ( ($details['is_active']) && (! $details['is_static'])) {
			$id = $details['active_mac'] . "|" . $details['active_ip'];
			$expires = ($details['active_end'] == 0) ? LOCALE_LANG_UNLIMITED : strftime('%c', $details['active_end']);
			$rows .= "
				<tr>
					<td width='125' nowrap>" . $details['active_ip'] . "</td>
					<td width='125' nowrap>" . $details['active_mac'] . "</td>
					<td nowrap>" . $details['hostname'] . "</td>
					<td width='180' nowrap>" . $expires . "</td>
					<td nowrap>" . WebButton("ConvertToStatic[$id]", WEB_LANG_BUTTON_CHANGE_TO_STATIC, WEBCONFIG_ICON_CONTINUE) . "</td> 
				</tr>";
		}
	}

	if (! $rows)
		$rows = "<tr><td colspan='6' align='center'>" . WEB_LANG_NO_ACTIVE_LEASES . "</td></tr>";

	WebFormOpen();
	WebTableOpen(DNSMASQ_LANG_DYNAMIC_LEASES, "100%");
	WebTableHeader(NETWORK_LANG_IP . "|" . NETWORK_LANG_MAC_ADDRESS . "|" . HOSTS_LANG_HOSTNAME . "|" . LOCALE_LANG_EXPIRES . "|");
	echo $rows;
	WebTableClose("100%");
	WebFormClose();
}

?>
