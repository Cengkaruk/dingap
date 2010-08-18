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
require_once("../../api/FirewallIncoming.class.php");
require_once("../../api/Network.class.php");
require_once("../../api/Pptpd.class.php");
require_once("../../gui/PptpdReport.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-pptpd.png", WEB_LANG_PAGE_INTRO);
WebCheckUserDatabase();

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$pptpd = new Pptpd();
$report = new PptpdReport();

try {
	if (isset($_POST['EnableBoot'])) {
		$pptpd->SetBootState(true);
	} else if (isset($_POST['DisableBoot'])) {
		$pptpd->SetBootState(false);
	} else if (isset($_POST['StartDaemon'])) {
		$pptpd->SetRunningState(true);
	} else if (isset($_POST['StopDaemon'])) {
		$pptpd->SetRunningState(false);
	} else if (isset($_POST['UpdateConfig'])) {
		$pptpd->SetLocalIp($_POST['localip']);
		$pptpd->SetRemoteIp($_POST['remoteip']);
		$pptpd->SetKeySize($_POST['keysize']);
		$pptpd->SetDomain($_POST['domain']);
		$pptpd->SetWinsServer($_POST['winsserver']);
		$pptpd->SetDnsServer($_POST['dnsserver']);
		$pptpd->Reset();
	} else if (isset($_POST['EnableFirewall'])) {
		$firewall = new FirewallIncoming();
		$firewall->SetPptpServerState(true);
		$firewall->Restart();
	} else if (isset($_POST['DisableFirewall'])) {
		$firewall = new FirewallIncoming();
		$firewall->SetPptpServerState(false);
		$firewall->Restart();
	}
} catch (Exception $e) {
	WebDialogWarning($e->GetMessage());
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

WebDialogDaemon("pptpd");
SanityCheck();
DisplayConfig();
$report->GetFullReport(true);
WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayConfig()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayConfig()
{
	global $pptpd;

	$network = new Network(); // for locale

	try {
		$domain = $pptpd->GetDomain();
		$winsserver = $pptpd->GetWinsServer();
		$dnsserver = $pptpd->GetDnsServer();
		$keysize = $pptpd->GetKeySize();
		$localip = $pptpd->GetLocalIp();
		$remoteip = $pptpd->GetRemoteIp();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	WebFormOpen();
	WebTableOpen(WEB_LANG_CONFIG_TITLE, "450");
	echo "
	  <tr>
		<td class='mytablesubheader' nowrap>" . PPTPD_LANG_LOCALIP  . "</td>
		<td><input type='text' name='localip' value='$localip' /></td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' nowrap>" . PPTPD_LANG_REMOTEIP . "</td>
		<td><input type='text' name='remoteip' value='$remoteip' /></td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' nowrap>" . PPTPD_LANG_KEYSIZE . "</td>
		<td>" . WebDropDown("keysize", $keysize, "40|128") . "</td>
	  </tr>
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
	global $pptpd;

	// Make sure the firewall is in a sensible state.
	// If the PPTP server is running, it should allow PPTP connections.
	// If *not* running, we should turn off incoming connections.  Not only
	// is this more secure, but it allows PPTP-passthrough to work properly.
	//----------------------------------------------------------------------

	$firewall = new FirewallIncoming();

	$mode = $firewall->GetMode();
	$ispptpallowed = $firewall->GetPptpServerState();
	$ispptprunning = $pptpd->GetRunningState();

	// Running -- make sure firewall is on
	if ($ispptprunning) {
		if (($mode != Firewall::CONSTANT_TRUSTEDSTANDALONE) && (!$ispptpallowed)) {
			$warning = WEB_LANG_FIREWALL_ENABLE;
			$warning .= "<br /><p align='center'>";
			$warning .= WebButtonToggle("EnableFirewall", LOCALE_LANG_ENABLE);
			$warning .= "</p>";
			WebFormOpen();
			WebDialogWarning($warning);
			WebFormClose();
		}

	// Stopped -- make sure PPTP can pass through
	} else {
		if ($ispptpallowed) {
			$warning = WEB_LANG_FIREWALL_DISABLE;
			$warning .= "<br /><p align='center'>";
			$warning .= WebButtonToggle("DisableFirewall", LOCALE_LANG_DISABLE);
			$warning .= "</p>";
			WebFormOpen();
			WebDialogWarning($warning);
			WebFormClose();
		}
	}
}

// vim: syntax=php ts=4
?>
