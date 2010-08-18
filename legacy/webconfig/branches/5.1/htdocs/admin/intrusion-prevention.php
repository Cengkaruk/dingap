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
require_once("../../api/Firewall.class.php");
require_once("../../api/Network.class.php");
require_once("../../api/Snort.class.php");
require_once("../../api/SnortSam.class.php");
require_once("../../gui/SnortSamReport.class.php");
require_once("../../api/IntrusionProtectionUpdates.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Subscription information
//
///////////////////////////////////////////////////////////////////////////////

// TODO: implement this better
require_once("clearcenter-status.inc.php");
$header = "<script type='text/javascript' src='/admin/clearcenter-status.js.php?service=" . IntrusionProtectionUpdates::CONSTANT_NAME . "'></script>\n";

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE, "default", $header);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-intrusion-prevention.png", WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$snort = new Snort();
$snortsam = new SnortSam();
$report = new SnortSamReport();

try {
	if (isset($_POST['EnableBoot'])) {
		$snortsam->SetBootState(true);
	} else if (isset($_POST['DisableBoot'])) {
		$snortsam->SetBootState(false);
	} else if (isset($_POST['StartDaemon'])) {
		$snortsam->SetRunningState(true);
		$snort->Reset();
	} else if (isset($_POST['StopDaemon'])) {
		$snortsam->SetRunningState(false);
		$snort->Reset();
	} else if (isset($_POST['StartSnort'])) {
		$snort->SetRunningState(true);
		$snort->SetBootState(true);
	} else if (isset($_POST['DeleteBlock'])) {
		$snortsam->DeleteBlockedCrc(key($_POST['DeleteBlock']));
		$snortsam->Reset();
	} else if (isset($_POST['AddWhitelist'])) {
		list($ip, $crc) = explode("|", key($_POST['AddWhitelist']));
		$snortsam->DeleteBlockedCrc($crc);
		$snortsam->AddWhitelistIp($ip);
		$snortsam->Reset();
	} else if (isset($_POST['InsertWhitelist']) && isset($_POST['ip'])) {
		// Remove from block list (if it exists)
		try {
			$snortsam->DeleteBlockedIp($_POST['ip']);
		} catch (Exception $e) {
			// Not fatal
		}
		$snortsam->AddWhitelistIp($_POST['ip']);
		$snortsam->Reset();
	} else if (isset($_POST['DeleteWhitelist'])) {
		$snortsam->DeleteWhitelistIp(key($_POST['DeleteWhitelist']));
		$snortsam->Reset();
	} else if (isset($_POST['ResetBlockList'])) {
		$snortsam->ResetBlocklist();
		$snortsam->Reset();
	}
} catch (Exception $e) {
	WebDialogWarning($e->GetMessage());
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

WebServiceStatus(IntrusionProtectionUpdates::CONSTANT_NAME, "ClearSDN Intrusion Protection Updates");
WebDialogDaemon("snortsam");
SanityCheck();
DisplayWhiteList();
$report->GetFullReport(true);
WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayWhiteList
//
///////////////////////////////////////////////////////////////////////////////

function DisplayWhiteList()
{
	global $snortsam;

	$whitelist = array();

	try {
		$whitelist = $snortsam->GetWhitelist();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

	$list = "";

	foreach ($whitelist as $ip) {
		$list .= "
			<tr>
				<td>$ip</td>
				<td nowrap>" . WebButtonDelete("DeleteWhitelist[$ip]") . "</td>
			</tr>
		";
	}

	$mynetwork = new Network(); // For locale tags

	WebFormOpen();
	WebTableOpen(SNORTSAM_LANG_WHITELIST, "300");
	WebTableHeader(NETWORK_LANG_IP . "|");
	echo "
		$list
		<tr>
			<td><input type='text' name='ip' size='20' /></td>
			<td nowrap>" . WebButtonAdd("InsertWhitelist") . "</td>
		</tr>
	";
	WebTableClose("300");
	WebFormClose();
}


///////////////////////////////////////////////////////////////////////////////
//
// SanityCheck()
//
///////////////////////////////////////////////////////////////////////////////

function SanityCheck()
{
	global $snort;
	global $snortsam;

	try {
		$issnortrunning = $snort->GetRunningState();
		$issamrunning = $snortsam->GetRunningState();

		if (!$issnortrunning && $issamrunning) {
			WebFormOpen();
			WebDialogWarning(WEB_LANG_SNORT_NOT_RUNNING . " -- " . WebButtonToggle("StartSnort", DAEMON_LANG_START));
			WebFormClose();
		}
	} catch (Exception $e) {
		// Not critical
	}
}

// vim: syntax=php ts=4
?>
