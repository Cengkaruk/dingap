<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2006 Point Clark Networks.
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
require_once("../../api/IpSec.class.php");
require_once("../../api/DynamicVpn.class.php");
require_once("../../api/Routes.class.php");
require_once("../../api/Iface.class.php");
require_once("../../api/IfaceManager.class.php");
require_once("../../api/Firewall.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Subscription information
//
///////////////////////////////////////////////////////////////////////////////

require_once("clearcenter-status.inc.php");
$header = "<script type='text/javascript' src='/admin/clearcenter-status.js.php?service=" . DynamicVpn::CONSTANT_NAME . "'></script>\n";

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE, "default", $header);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-ipsec.png", WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$ipsec = new Ipsec();
$dynamicvpn = new DynamicVpn();

try {
	$vpnwatch = new Daemon("vpnwatchd");
	$vpnwatch_installed = $vpnwatch->IsInstalled();
} catch (Exception $e) {
	$vpnwatch_installed = false; 
}

if (isset($_POST['EnableBoot'])) {
	try {
		$ipsec->SetBootState(true);
		if ($vpnwatch_installed)
			$vpnwatch->SetBootState(true);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
} else if (isset($_POST['DisableBoot'])) {
	try {
		$ipsec->SetBootState(false);
		if ($vpnwatch_installed)
			$vpnwatch->SetBootState(false);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
} else if (isset($_POST['StartDaemon'])) {
	try {
		$ipsec->SetRunningState(true);
		if ($vpnwatch_installed)
			$vpnwatch->SetRunningState(true);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
} else if (isset($_POST['StopDaemon'])) {
	try {
		$ipsec->SetRunningState(false);
		if ($vpnwatch_installed)
			$vpnwatch->SetRunningState(false);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
} else if (isset($_POST['UpdateManualConnection'])) {
	try {
		$ipsec->SetConnection(
			$_POST['nickname'], $_POST['ipsec_hq'], $_POST['ipsec_hq_hop'], $_POST['ipsec_hq_subnet'],
			$_POST['ipsec_sat'], $_POST['ipsec_sat_hop'], $_POST['ipsec_sat_subnet'], $_POST['ipsec_secret']);
		$ipsec->Restart();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		$_POST['EditManualConnection'][$nickname] = true;
	}
} else if (isset($_POST['UpdateManagedConnection'])) {
	try {
		$dynamicvpn->SetConnection($_POST['remote'], $_POST['secret']);
		if ($vpnwatch_installed)
			$vpnwatch->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		$_POST['EditManagedConnection'][""] = true;
	}
} else if (isset($_POST['DeleteManagedConnection'])) {
	try {
		$dynamicvpn->DeleteConnection(key($_POST['DeleteManagedConnection']));
		if ($vpnwatch_installed)
			$vpnwatch->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
} else if (isset($_POST['DeleteManualConnection'])) {
	try {
		$ipsec->DeleteConnection(key($_POST['DeleteManualConnection']));
		$ipsec->Restart();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
} else if (isset($_POST['EnableFirewall'])) {
	try {
		if (file_exists("../../api/FirewallIncoming.class.php")) {
			require_once("../../api/FirewallIncoming.class.php");
			$firewall = new FirewallIncoming();
			$firewall->SetIpsecServerState(true);
			$firewall->Restart();
		}
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

if (isset($_POST['EditManagedConnection'])) {
	DisplayManagedEdit(key($_POST['EditManagedConnection']), $_POST['secret']);
} else if (isset($_POST['EditManualConnection'])) {
	DisplayManualEdit(
		key($_POST['EditManualConnection']), $_POST['ipsec_hq'], $_POST['ipsec_hq_hop'],
		$_POST['ipsec_hq_subnet'], $_POST['ipsec_sat'], $_POST['ipsec_sat_hop'],
		$_POST['ipsec_sat_subnet'], $_POST['ipsec_secret']
	);
} else if (isset($_POST['CreateManagedConnection'])) {
	DisplayManagedEdit("", "");
} else if (isset($_POST['CreateManualConnection'])) {
	DisplayManualEdit("", "", "", "", "", "", "", "");
} else {
	WebDialogDaemon("ipsec");
	SanityCheck();

	$showadvanced = isset($_REQUEST['showadvanced']) ? true : false;

	WebServiceStatus(DynamicVpn::CONSTANT_NAME, "ClearSDN Dynamic VPN");

	DisplayManagedSummary();
	DisplayManualSummary($showadvanced);
}

WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayNetworkInfo()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayNetworkInfo()
{
	$network = new Network();
	$firewall = new Firewall();
	$routes = new Routes();

	try {
		$defaultroute = $routes->GetDefault();
		$extif = $firewall->GetInterfaceDefinition(Firewall::CONSTANT_EXTERNAL);
		$lanif = $firewall->GetInterfaceDefinition(Firewall::CONSTANT_LAN);

		$interface = new Iface($extif);
		$ip = $interface->GetLiveIp();

		$interface = new Iface($lanif);
		$lanip = $interface->GetLiveIp();
		$lannetmask = $interface->GetLiveNetmask();
		$lannet = $network->GetNetworkAddress($lanip, $lannetmask);
	} catch (Exception $e) {
		// Not fatal
		return;
	}

	WebTableOpen(NETWORK_LANG_NETWORK, "400");
	echo "
	  <tr>
	    <td class='mytablesubheader' nowrap>" . NETWORK_LANG_IP . "</td>
	    <td>$ip</td>
	  </tr>
	  <tr>
	    <td class='mytablesubheader' nowrap>" . NETWORK_LANG_GATEWAY . "</td>
	    <td>$defaultroute</td>
	  </tr>
	  <tr>
	    <td class='mytablesubheader' nowrap>" . NETWORK_LANG_NETWORK . "</td>
	    <td>$lannet/$lannetmask</td>
	  </tr>
	";
	WebTableClose("400");
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayManual()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayManualSummary($showadvanced)
{
	global $ipsec;

	try {
		$connlist = $ipsec->GetConnectionData();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	// Only show manual connections if in advanced mode (or already defined)
	if ( (count($connlist) == 0) && (!$showadvanced)) {
		WebDialogInfo(WEB_LANG_SHOW_ADVANCED);
		return;
	}

	$connections = "";

	foreach ($connlist as $conninfo) {
		$connections .= "
		  <tr>
			<td>$conninfo[name]</td><td>$conninfo[left]</td><td>$conninfo[right]</td>
			<td>"
			. WebButtonEdit("EditManualConnection[$conninfo[name]]")
			. WebButtonDelete("DeleteManualConnection[$conninfo[name]]") . "
			</td>
		  </tr>\n
		";
	}

	WebFormOpen();
	WebTableOpen(WEB_LANG_STATIC_TITLE, "100%");
	WebTableHeader(
		IPSEC_LANG_CONNECTION_NAME . "|" . 
		IPSEC_LANG_HQ . "|" . 
		IPSEC_LANG_SATELLITE . "|"
	);
	echo "
	  $connections
	  <tr>
  	    <td colspan='3'>&#160; </td>
	    <td>" . WebButtonCreate("CreateManualConnection") . "</td>
	  </tr>
	";
	WebTableClose("100%");
	WebFormClose();
}


///////////////////////////////////////////////////////////////////////////////
//
// DisplayManaged()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayManagedSummary()
{
	global $dynamicvpn;

	$connlist = array();
	$connections = "";

	try {
		$connlist = $dynamicvpn->GetConnectionData();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	if ($connlist) {
		foreach ($connlist as $conninfo) {
			$status = $conninfo['status'];
			if ($status == DynamicVpn::STATUS_UP)
				$status_out = WEBCONFIG_ICON_CHECKMARK;
			elseif ($status == DynamicVpn::STATUS_INVALID)
				$status_out = LOCALE_LANG_INVALID;
			elseif ($status == DynamicVpn::STATUS_INIT)
				$status_out = LOCALE_LANG_INITIALIZING;
			else
				$status_out = WEBCONFIG_ICON_XMARK;

			$connections .= "
			  <tr>
				<td>$conninfo[remote_name]</td>
				<td>$conninfo[remote_ip]</td>
				<td>$conninfo[remote_lannetwork]</td>
				<td>$status_out</td>
				<td> " .
			      WebButtonEdit("EditManagedConnection[" . $conninfo['remote_id'] . "]") . " " .
				  WebButtonDelete("DeleteManagedConnection[" . $conninfo['remote_id'] . "]") . "
			    </td>
			  </tr>
			";
		}
	}

	WebFormOpen();
	WebTableOpen(WEB_LANG_DYNAMIC_VPN_CONNECTIONS, "100%");
	WebTableHeader(
	    IPSEC_LANG_TARGET_SERVER . "|" .
	    IPSEC_LANG_SATELLITE_IP . "|" .
	    IPSEC_LANG_SATELLITE_NETWORK . "|" .
	    LOCALE_LANG_STATUS . "|"
	);
	echo "
	  $connections
	  <tr>
	    <td colspan='4'>&#160; </td>
	    <td>" . WebButtonCreate("CreateManagedConnection") . "</td>
	  </tr>
	";
	WebTableClose("100%");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayManagedEdit()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayManagedEdit($remoteid, $secret)
{
	global $dynamicvpn;

	// Get settings for this system
	//-----------------------------

	try {
		$homeinfo = $dynamicvpn->GetInfo();
		$subscription = $dynamicvpn->GetSubscriptionStatus();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	if (!$subscription['subscribed']) {
		WebDialogWarning("Dynamic VPN requires a subscription!&nbsp; Follow the link to configure an unmanaged VPN connection - " .  WebUrlJump("ipsec.php?showadvanced", LOCALE_LANG_CONFIGURE)
		);
		return;
	}

	$homeid = $homeinfo['id'];
	$homeip = $homeinfo['ip'];
	$homename = $homeinfo['name'];

	// If this is an "edit", show connection info
	//-------------------------------------------

	if ($remoteid) {
		try {
			$conninfo = $dynamicvpn->GetConnection($remoteid);
		} catch (Exception $e) {
			WebDialogWarning($e->GetMessage());
			return;
		}

		$remote = "<input type='hidden' name='remote' value='$conninfo[remote_id]' />$conninfo[remote_ip] ($conninfo[remote_name])";
		$secret = $conninfo['secret'];

	// If this is a "create", show drop-down list
	//-------------------------------------------

	} else {

		// Get the list of all remote systems
		//-----------------------------------

		try {
			$remotelist = $dynamicvpn->GetRemoteList();
		} catch (Exception $e) {
			WebDialogWarning($e->GetMessage());
			return;
		}

		// Bail if no other remote machines exist!
		if (count($remotelist) <= 1) {
			WebDialogWarning(WEB_LANG_NO_REMOTE_SYSTEMS);
			return;
		}

		// Grab the settings for *all* configured connections
		//----------------------------------------------------

		$configuredlist = array();
		try {
			$configuredlist = $dynamicvpn->GetConnectionData();
		} catch (Exception $e) {
			WebDialogWarning($e->GetMessage());
			return;
		}

		$remotelist_dropdown = "";

		foreach ($remotelist as $remote) {

			// Don't show this machine in remote list
			//---------------------------------------

			if ($remote["id"] == $homeid)
				continue;

			// Don't show already configured connections in drop-down either
			//--------------------------------------------------------------

			$alreadyconfigured = false;
			if ($configuredlist) {
				foreach ($configuredlist as $configured) {
					if ($configured["remote_id"] == $remote["id"])
						$alreadyconfigured = true;
				}
			}

			if (! $alreadyconfigured)
				$remotelist_dropdown .= "<option value='$remote[id]'>$remote[ip] ($remote[name])</option>";
		}

		$remote = "<select name='remote'>$remotelist_dropdown</select>";
	}

	WebFormOpen();
	WebTableOpen(WEB_LANG_DYNAMIC_CONFIG_TITLE, "400");
	echo "
	  <tr>
	    <td class='mytablesubheader' nowrap width='120'>" . IPSEC_LANG_THIS_SERVER . "</td>
	    <td>$homeip ($homename)</td>
	  </tr>
	  <tr>
	    <td class='mytablesubheader' nowrap>" . IPSEC_LANG_TARGET_SERVER . "</td>
	    <td>$remote</td>
	  </tr>
	  <tr>
	    <td class='mytablesubheader' nowrap>" . IPSEC_LANG_SHARED_SECRET . "</td>
	    <td><input type=text name='secret' size='30' value='$secret' /></td>
	  </tr>
	  <tr>
	    <td class='mytablesubheader' nowrap>&#160;</td>
	    <td>". WebButtonUpdate("UpdateManagedConnection") . " " . WebButtonCancel("Cancel") . "</td>
	  </tr>
	";
	WebTableClose("400");
	WebFormClose();
}


///////////////////////////////////////////////////////////////////////////////
//
// DisplayManualEdit()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayManualEdit($nickname, $ipsec_hq, $ipsec_hq_hop, $ipsec_hq_subnet, $ipsec_sat, $ipsec_sat_hop, $ipsec_sat_subnet, $ipsec_secret)
{
	global $ipsec;

	WebDialogWarning("Unmanaged VPN connections should be considered <b class='alert'>experimental</b>.&nbsp; For mission
	critical environments, we recommend Dynamic VPN.&nbsp; Please see the User Guide for more information.");

	if ($nickname) {
		try {
			$conninfo = $ipsec->GetConnection($nickname);
		} catch (Exception $e) {
			WebDialogWarning($e->GetMessage());
			// Keep going...
		}

		$ipsec_hq         = $conninfo["left"];
		$ipsec_hq_hop     = $conninfo["leftnexthop"];
		$ipsec_hq_subnet  = $conninfo["leftsubnet"];
		$ipsec_sat        = $conninfo["right"];
		$ipsec_sat_hop    = $conninfo["rightnexthop"];
		$ipsec_sat_subnet = $conninfo["rightsubnet"];
		$ipsec_secret     = $conninfo["secret"];

		$nickname_display = "<input type='hidden' name='nickname' value='$nickname' />$nickname";
	} else {
		$nickname_display = "<input type='text' name='nickname' value='$nickname' />";
	}

	DisplayNetworkInfo();
	WebFormOpen();
	WebTableOpen(WEB_LANG_STATIC_CONFIG_TITLE, "100%");
	echo "
	  <tr>
        <td class='mytableheader' colspan='2'>" . IPSEC_LANG_CONNECTION_NAME . "</td>
      </tr>
      <tr>
        <td class='mytablesubheader' nowrap width='35%'>" . IPSEC_LANG_CONNECTION_NAME . "</td>
        <td width='65%'>$nickname_display</td>
      </tr>
	  <tr>
		<td colspan='2' class='mytableheader'>" . IPSEC_LANG_HQ . "</td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' nowrap>" . IPSEC_LANG_HQ_IP . "</td>
		<td><input type='text' size='30' name='ipsec_hq' value='$ipsec_hq' /></td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' nowrap>" . IPSEC_LANG_HQ_NEXT . "</td>
		<td><input type='text' size='30' name='ipsec_hq_hop' value='$ipsec_hq_hop' /></td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' nowrap>" . IPSEC_LANG_HQ_NETWORK . "</td>
		<td><input type='text' size='30' name='ipsec_hq_subnet' value='$ipsec_hq_subnet' /></td>
	  </tr>
	  <tr>
		<td colspan='2' class='mytableheader'>" . IPSEC_LANG_SATELLITE . "</td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' nowrap>" . IPSEC_LANG_SATELLITE_IP . "</td>
		<td><input type='text' size='30' name='ipsec_sat' value='$ipsec_sat' /></td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' nowrap>" . IPSEC_LANG_SATELLITE_NEXT . "</td>
		<td><input type='text' size='30' name='ipsec_sat_hop' value='$ipsec_sat_hop' /></td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' nowrap>" . IPSEC_LANG_SATELLITE_NETWORK . "</td>
		<td><input type='text' size='30' name='ipsec_sat_subnet' value='$ipsec_sat_subnet' /></td>
	  </tr>
	  <tr>
		<td colspan='2' class='mytableheader'>" . IPSEC_LANG_SHARED_SECRET . "</td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' nowrap>" . IPSEC_LANG_SHARED_SECRET . "</td>
		<td><input type=text name='ipsec_secret' size='30' value='$ipsec_secret' /></td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' nowrap>&#160;</td>
		<td>". WebButtonUpdate("UpdateManualConnection") . " " . WebButtonCancel("Cancel") . "</td>
	  </tr>
	";
	WebTableClose("100%");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// SanityCheck()
//
///////////////////////////////////////////////////////////////////////////////

function SanityCheck()
{
	global $dynamicvpn;
	global $ipsec;

	// Running -- make sure firewall is on
	if ($ipsec->GetRunningState()) {
		if (file_exists("../../api/FirewallIncoming.class.php")) {
			require_once("../../api/FirewallIncoming.class.php");
			$firewall = new FirewallIncoming();
			try {
				$mode = $firewall->GetMode();
				$isipsecallowed = $firewall->GetIpsecServerState();
			} catch (Exception $e) {
				WebDialogWarning($e->GetMessage());
			}

			if (($mode != Firewall::CONSTANT_TRUSTEDSTANDALONE) && (!$isipsecallowed)) {
				$errmsg = WEB_LANG_FIREWALL_ENABLE;
				$errmsg .= "<br /><p align='center'>";
				$errmsg .= WebButtonToggle("EnableFirewall", LOCALE_LANG_ENABLE);
				$errmsg .= "</p>";
				WebFormOpen();
				WebDialogWarning($errmsg);
				WebFormClose();
			}
		}
	}

	// Warn users that IPsec is not supported behind NAT
	try {
		$interfaces = new IfaceManager();
		$ethlist = $interfaces->GetExternalInterfaces();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

	$nat_list = "";

	foreach ($ethlist as $eth) {

		$network = new Network();

		try {
			$iface = new Iface($eth);
			$ip = $iface->GetLiveIp();
			if ( (!empty($ip)) && $network->IsPrivateIp($ip))
				$nat_list .= IFACE_LANG_INTERFACE . " -- " . $eth . "/" . $ip . "<br>";
		} catch (Exception $e) {
			// not fatal
		}
	}

	if (! empty($nat_list))
		WebDialogWarning(WEB_LANG_IPWEB_LANG_IPSEC_THROUGH_NAT_WARNING . "<br><br>" . $nat_list);
}

// vim: ts=4
?>
