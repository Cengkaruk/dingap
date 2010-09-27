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
require_once("../../api/Firewall.class.php");
require_once("../../api/FirewallRule.class.php");
require_once("../../api/FirewallIncoming.class.php");
require_once("../../api/FirewallWifi.class.php");
require_once("../../api/Hostname.class.php");
require_once("../../api/HostnameChecker.class.php");
require_once("../../api/Iface.class.php");
require_once("../../api/IfaceManager.class.php");
require_once("../../api/Network.class.php");
require_once("../../api/Resolver.class.php");
require_once("../../api/Routes.class.php");
require_once("../../api/Syswatch.class.php");
require_once("network.inc.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

if (isset($_POST['Logout'])) {
	unset($_SESSION['system_login']);
	header("Location: http://127.0.0.1:82/admin/network.php?nocache=yes");
	exit;
}

if (isset($_POST['AddSourceBasedRoute'])) {
	header('Location: /admin/multiwan.php');
	exit;
}

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
// The console tool needs some real estate -- don't show the banner.
if (substr(getenv("HTTP_USER_AGENT"),0,4) != "Lynx")
	WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-network.png", WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$routes = new Routes();
$network = new Network();
$firewall = new Firewall();
$hostname = new Hostname();
$resolver = new Resolver();
$interfaces = new IfaceManager();

$eth = isset($_POST['eth']) ? $_POST['eth'] : "";

if (isset($_POST['Cancel'])) {

	unset($_POST);

} else if (isset($_POST['UpdateConfig'])) {

	try {
		// TODO: push this check down to hostname class, and localize
		if (! preg_match("/\./", $_POST['realhostname']))
			echo WebDialogWarning(NETWORK_LANG_ERRMSG_HOSTNAME_MUST_HAVE_A_PERIOD);
		else
			$hostname->Set($_POST['realhostname']);

		// Try to add hostname to /etc/hosts 
		try {
			$hostnamechecker = new HostnameChecker();
			$hostnamechecker->AutoFix(true);
		} catch (Exception $e) {
			// Not fatal
		}

		// Reload session since hostname is stored there.
		// TODO: it should not be necessary for a developer to manually handle the session this way.
		WebSetSession();

		$resolver->SetNameservers($_POST['ns']);
		$firewall->SetMode($_POST['mode']);

		// Open up port 81 when going into standalone mode.
		// Users will otherwise get locked out!

		if ($_POST['mode'] == Firewall::CONSTANT_STANDALONE) {
			$incoming = new FirewallIncoming();
			try {
				$incoming->AddAllowPort("webconfig", "TCP", "81");
			} catch (Exception $e) {
				// TODO: Rule may already exist.  Make this a custom exception
			}
		}

		$syswatch = new Syswatch();
		$syswatch->ReconfigureNetworkSettings();

	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

} else if (isset($_POST['DeleteInterface'])) {

	try {
		$interface = new Iface(key($_POST['DeleteInterface']));

		$interface->DeleteConfig();
		$firewall->RemoveInterfaceRole(key($_POST['DeleteInterface']));

		if ($routes->GetGatewayDevice() == key($_POST['DeleteInterface']))
			$routes->DeleteGatewayDevice();

		$syswatch = new Syswatch();
		$syswatch->Reset();

	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

} else if (isset($_POST['DeleteVirtual'])) {

	try {
		$interface = new Iface(key($_POST['DeleteVirtual']));
		$interface->DeleteVirtual();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

} else if (isset($_POST['SaveVirtual'])) {

	$eth = isset($_POST['eth']) ? $_POST['eth'] : "";
	$ip = isset($_POST['ip']) ? $_POST['ip'] : "";
	$netmask = isset($_POST['netmask']) ? $_POST['netmask'] : "";

	try {
		$interface = new Iface($eth);
		$interface->SaveVirtualConfig($ip, $netmask);
		$interface->Enable();
	} catch (ValidationException $e) {
		$_POST['EditVirtual'][$eth] = true;
		WebDialogWarning(WebCheckErrors($interface->GetValidationErrors(true)));
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

} else if (isset($_POST['SaveNetworkInterface'])) {
	$role = isset($_POST['role']) ? $_POST['role'] : "";
	$bootproto = isset($_POST['bootproto']) ? $_POST['bootproto'] : "";
	$ip = isset($_POST['ip']) ? $_POST['ip'] : "";
	$netmask = isset($_POST['netmask']) ? $_POST['netmask'] : "";
	$gateway = isset($_POST['gateway']) ? $_POST['gateway'] : "";
	$dhcp_hostname = isset($_POST['dhcp_hostname']) ? $_POST['dhcp_hostname'] : "";
	$peerdns = (isset($_POST['peerdns']) && ($_POST['peerdns'] == "on")) ? true : false; 
	$pppoe_peerdns = (isset($_POST['pppoe_peerdns']) && ($_POST['pppoe_peerdns'] == "on")) ? true : false; 
	$username = isset($_POST['username']) ? $_POST['username'] : "";
	$password = isset($_POST['password']) ? $_POST['password'] : "";
	$mtu = isset($_POST['mtu']) ? $_POST['mtu'] : "";

	// TODO: push this weirdness down into the API
	if ($bootproto == Iface::BOOTPROTO_PPPOE)
		$type = Iface::TYPE_PPPOE;
	else if (! empty($_POST['essid']))
		$type = Iface::TYPE_WIRELESS;
	else
		$type = Iface::TYPE_ETHERNET;

	$interface = new Iface($eth);

	try {
		// Wireless
		//---------

		if ($type == Iface::TYPE_WIRELESS) {

			$essid = isset($_POST['essid']) ? $_POST['essid'] : ""; 
			$mode = isset($_POST['mode']) ? $_POST['mode'] : ""; 
			$key = isset($_POST['key']) ? $_POST['key'] : ""; 
			$rate = isset($_POST['rate']) ? $_POST['rate'] : ""; 

			if ($bootproto == Iface::BOOTPROTO_DHCP) {
				$interface->SaveWirelessConfig(true, "", "", "", $essid, "1", $mode, $key, $rate, $peerdns);
			} else {
				$interface->SaveWirelessConfig(false, $ip, $netmask, $gateway, $essid, "1", $mode, $key, $rate, $peerdns, $mtu);
			}

		// PPPoE
		//------

		} else if ($bootproto == Iface::BOOTPROTO_PPPOE) {
			$firewall->RemoveInterfaceRole($eth);
			$eth = $interface->SavePppoeConfig($eth, $username, $password, $mtu, $pppoe_peerdns);

		// Ethernet
		//---------

		} else if ($bootproto == Iface::BOOTPROTO_DHCP) {
			$interface->SaveEthernetConfig(true, "", "", "", $dhcp_hostname, $peerdns);
		} else if ($bootproto == Iface::BOOTPROTO_STATIC) {
			$gateway_required = ($role == Firewall::CONSTANT_EXTERNAL) ? true : false;
			$interface->SaveEthernetConfig(false, $ip, $netmask, $gateway, "", false, $gateway_required);
		}

		// Reset the routes
		//-----------------

		if ($role == Firewall::CONSTANT_EXTERNAL)
			$routes->SetGatewayDevice($eth);
		else if ($routes->GetGatewayDevice() == $eth)
			$routes->DeleteGatewayDevice();

		// Set firewall roles
		//-------------------

		$firewall->SetInterfaceRole($eth, $role);

		// Enable interface 
		//-----------------

		// Response time can take too long on PPPoE and DHCP connections.

		if (($bootproto == Iface::BOOTPROTO_DHCP) || ($bootproto == Iface::BOOTPROTO_PPPOE))
			$interface->Enable(true);
		else
			$interface->Enable(false);

		// Restart syswatch
		//-----------------

		$syswatch = new Syswatch();
		$syswatch->Reset();

	} catch (ValidationException $e) {
		WebDialogWarning(WebCheckErrors($interface->GetValidationErrors(true)));
		$_POST['DisplayEdit'][$eth] = true;
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		$_POST['DisplayEdit'][$eth] = true;
	}
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

if (isset($_POST['DisplayEdit'])) {
	DisplayEdit(key($_POST['DisplayEdit']), $role, $bootproto, $ip, $netmask, $gateway, $dhcp_hostname,
    $peerdns, $username, $password, $mtu);
} else if (isset($_POST['EditPppoe'])) {
	DisplayEditPppoe(key($_POST['EditPppoe']), $role, $pppoe_peerdns, $username, $password, $mtu);
} else if (isset($_POST['EditVirtual'])) {
	DisplayVirtual(key($_POST['EditVirtual']), $ip, $netmask);
} else if (isset($_POST['AddVirtual'])) {
	DisplayVirtual("", $ip, $netmask);
} else if (isset($_POST['ConfirmDeleteVirtual'])) {
	DisplayConfirmDelete("DeleteVirtual", key($_POST['ConfirmDeleteVirtual']));
} else if (isset($_POST['ConfirmDeleteInterface'])) {
	DisplayConfirmDelete("DeleteInterface", key($_POST['ConfirmDeleteInterface']));
} else {
	// GetInterfaceDetails() takes time, so do it once here.
	try {
		$ethlist = $interfaces->GetInterfaceDetails();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

	SanityCheck($ethlist);
	DisplayNetwork($ethlist);
	DisplayInterfaces($ethlist);

	if (! WEBCONFIG_CONSOLE)
		DisplayAddVirtual();
}

if (WEBCONFIG_CONSOLE)
    echo WebUrlJump("dhcp.php", WEB_LANG_GO_TO_DHCP);

WebFooter();

// vim: ts=4
?>
