<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2008 Point Clark Networks.
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
require_once("../../api/FirewallIncoming.class.php");
require_once("../../api/OpenVpn.class.php");
require_once("../../api/Ssl.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-openvpn.png", WEB_LANG_PAGE_INTRO, true);
WebCheckUserDatabase();
WebCheckCertificates();

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$openvpn = new OpenVpn();
$firewall = new FirewallIncoming();

try {
	$port = isset($_POST['port']) ? $_POST['port'] : "";

	if ($_POST['protocol'] == OpenVpn::CONSTANT_PROTOCOL_UDP)
		$protocol = Firewall::CONSTANT_PROTOCOL_UDP;
	else
		$protocol = Firewall::CONSTANT_PROTOCOL_TCP;

	$domain = isset($_POST['domain']) ? $_POST['domain'] : "";
	$dnsserver = isset($_POST['dnsserver']) ? $_POST['dnsserver'] : "";
	$winsserver = isset($_POST['winsserver']) ? $_POST['winsserver'] : "";

	if (isset($_POST['EnableBoot'])) {
		$openvpn->SetBootState(true);
	} else if (isset($_POST['DisableBoot'])) {
		$openvpn->SetBootState(false);
	} else if (isset($_POST['StartDaemon'])) {
		$openvpn->SetRunningState(true);
	} else if (isset($_POST['StopDaemon'])) {
		$openvpn->SetRunningState(false);
	} else if (isset($_POST['UpdateConfig'])) {
		$openvpn->SetDomain($domain);
		$openvpn->SetWinsServer($winsserver);
		$openvpn->SetDnsServer($dnsserver);
	} else if (isset($_POST['DisableFirewall'])) {
		$firewall->DeleteAllowPort($protocol, $port);
		$firewall->Restart();
	} else if (isset($_POST['EnableFirewall'])) {
		// Enable firewall rule if it is disabled
		// Create firewall rule it does not exist
		$vpnstate = $firewall->CheckPort($protocol, $port);

		if ($vpnstate == Firewall::CONSTANT_DISABLED)
			$firewall->ToggleEnableAllowPort("OpenVPN", $protocol, $port);
		else if ($vpnstate == Firewall::CONSTANT_NOT_CONFIGURED)
			$firewall->AddAllowPort("OpenVPN", $protocol, $port);

		$firewall->Restart();
	}

	$errors = $openvpn->GetValidationErrors(true);

	if (empty($errors)) {
		$domain = '';
		$dnsserver = '';
		$winsserver = '';
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

WebDialogDaemon("openvpn");
SanityCheck();
DisplayConfig($domain, $dnsserver, $winsserver);
WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S 
/////////////////////////////////////////////////////////////////////////////// 

///////////////////////////////////////////////////////////////////////////////
//
// DisplayConfig()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayConfig($domain, $dnsserver, $winsserver)
{
	global $openvpn;

	$network = new Network(); // Locale

	try {
		$domain = empty($domain) ? $openvpn->GetDomain() : $domain;
		$winsserver = empty($winsserver) ? $openvpn->GetWinsServer() : $winsserver;
		$dnsserver =  empty($dnsserver) ? $openvpn->GetDnsServer() : $dnsserver;
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	WebFormOpen();
	WebTableOpen(WEB_LANG_PAGE_TITLE, "450");
	echo "
	  <tr>
		<td class='mytablesubheader' nowrap>" . NETWORK_LANG_DOMAIN . "</td>
		<td><input type='text' name='domain' value='$domain' /></td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' nowrap>" . NETWORK_LANG_WINS_SERVER . "</td>
		<td><input type='text' name='winsserver' value='$winsserver' /></td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' nowrap>" . NETWORK_LANG_DNS_SERVER . "</td>
		<td><input type='text' name='dnsserver' value='$dnsserver' /></td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' nowrap>&#160; </td>
		<td>" . WebButtonUpdate("UpdateConfig") . "</td>
	  </tr>
	";
	WebTableClose("450");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// SanityCheck()
//
///////////////////////////////////////////////////////////////////////////////

function SanityCheck()
{
	global $openvpn;
	global $firewall;

	// Make sure the firewall is in a sensible state.
	// If the OpenVPN server is running, it should allow OpenVPN connections.
	// If *not* running, we should turn off incoming connections. 
	//----------------------------------------------------------------------

	try {
		$port = $openvpn->GetClientPort();
		$protocol = $openvpn->GetClientProtocol();

		if ($protocol == OpenVpn::CONSTANT_PROTOCOL_UDP)
			$fwprotocol = Firewall::CONSTANT_PROTOCOL_UDP;
		else
			$fwprotocol = Firewall::CONSTANT_PROTOCOL_TCP;

		$mode = $firewall->GetMode();
		$portstate = $firewall->CheckPort($fwprotocol, $port);
		$isvpnallowed = ($portstate == Firewall::CONSTANT_ENABLED) ? true : false;

	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	$isvpnrunning = $openvpn->GetRunningState();

	if ($isvpnrunning) {
		if (($mode != Firewall::CONSTANT_TRUSTEDSTANDALONE) && (!$isvpnallowed)) {
			$warning = WEB_LANG_FIREWALL_ENABLE;
			$warning .= "<br /><p align='center'>";
			$warning .= WebButtonToggle("EnableFirewall", LOCALE_LANG_ENABLE);
			$warning .= "</p>";

			WebFormOpen();
			echo "
				<input type='hidden' name='port' value='$port'>
				<input type='hidden' name='protocol' value='$protocol'>
			";
			WebDialogWarning($warning);
			WebFormClose();
		}
	}
}

// vim: syntax=php ts=4
?>
