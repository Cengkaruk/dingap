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
//
// The use of Amavis complicates matters a bit.
//
///////////////////////////////////////////////////////////////////////////////

require_once("../../api/Postfix.class.php");

if (file_exists("../../api/Amavis.class.php")) {
	require_once("../../api/Amavis.class.php");
	$amavis = new Amavis();
} else {
	$amavis = "";
}

if (file_exists("../../api/ClamAv.class.php")) {
	require_once("../../api/ClamAv.class.php");
	$clamav = new ClamAv();
} else {
	$clamav = "";
}

if (file_exists("../../api/SpamAssassin.class.php")) {
	require_once("../../api/SpamAssassin.class.php");
	$spamassassin = new SpamAssassin();
} else {
	$spamassassin = "";
}

$filter = isset($_POST['filter']) ? $_POST['filter'] : "";

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// WebDialogMailFilter()
//
///////////////////////////////////////////////////////////////////////////////

function WebDialogMailFilter($filter)
{
	global $amavis;
	global $clamav;
	global $spamassassin;

	// Grab all the necessary data from the API
	//-----------------------------------------

	try {
		if ($amavis) {
			$amavis_installed = true;
			$amavis_daemon_onboot = $amavis->GetBootState();
			$amavis_daemon_running = $amavis->GetRunningState();
			$amavis_antispam_enabled = $amavis->GetAntispamState();
			$amavis_antivirus_enabled = $amavis->GetAntivirusState();
		} else {
			$amavis_installed = false;
			$amavis_daemon_onboot = false;
			$amavis_daemon_running = false;
			$amavis_antispam_enabled = false;
			$amavis_antivirus_enabled = false;
		}

		if ($clamav) {
			$clamav_installed = true;
			$clamav_daemon_onboot = $clamav->GetBootState();
			$clamav_daemon_running = $clamav->GetRunningState();
		} else {
			$clamav_installed = false;
			$clamav_daemon_onboot = false;
			$clamav_daemon_running = false;
		}

		if ($spamassassin) {
			$spamassassin_installed = true;
			$spamassassin_daemon_onboot = $spamassassin->GetBootState();
			$spamassassin_daemon_running = $spamassassin->GetRunningState();
		} else {
			$spamassassin_installed = false;
			$spamassassin_daemon_onboot = false;
			$spamassassin_daemon_running = false;
		}
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	// Display something sane to the user.
	//------------------------------------

	// ClamAv
	//-------

	if ($filter == "clamd") {
		if (!$clamav_installed)
			return;

		if ($clamav_daemon_running && $amavis_daemon_running && $amavis_antivirus_enabled) {
			$status_button = WebButtonToggle("StopDaemon", DAEMON_LANG_STOP);
			$status = "<span class='ok'><b>" . DAEMON_LANG_RUNNING . "</b></span>";
		} else {
			$status_button = WebButtonToggle("StartDaemon", DAEMON_LANG_START);
			$status = "<span class='alert'><b>" . DAEMON_LANG_STOPPED . "</b></span>";
		}

		if ($clamav_daemon_onboot && $amavis_daemon_onboot && $amavis_antivirus_enabled) {
			$onboot_button = WebButtonToggle("DisableBoot", DAEMON_LANG_TO_MANUAL);
			$onboot = "<span class='ok'><b>" . DAEMON_LANG_AUTOMATIC . "</b></span>";
		} else {
			$onboot_button = WebButtonToggle("EnableBoot", DAEMON_LANG_TO_AUTO);
			$onboot = "<span class='alert'><b>" . DAEMON_LANG_MANUAL . "</b></span>";
		}

	// Spamassassin
	//-------------

	} else if ($filter == "spamassassin") {
		if (!$spamassassin_installed)
			return;

		if ($amavis_daemon_running && $amavis_antispam_enabled) {
			$status_button = WebButtonToggle("StopDaemon", DAEMON_LANG_STOP);
			$status = "<span class='ok'><b>" . DAEMON_LANG_RUNNING . "</b></span>";
		} else {
			$status_button = WebButtonToggle("StartDaemon", DAEMON_LANG_START);
			$status = "<span class='alert'><b>" . DAEMON_LANG_STOPPED . "</b></span>";
		}

		if ($amavis_daemon_onboot && $amavis_antispam_enabled) {
			$onboot_button = WebButtonToggle("DisableBoot", DAEMON_LANG_TO_MANUAL);
			$onboot = "<span class='ok'><b>" . DAEMON_LANG_AUTOMATIC . "</b></span>";
		} else {
			$onboot_button = WebButtonToggle("EnableBoot", DAEMON_LANG_TO_AUTO);
			$onboot = "<span class='alert'><b>" . DAEMON_LANG_MANUAL . "</b></span>";
		}
	}

    // Build sub-table
    //----------------

    $content = "
		<form action='' method='post'>
		<input type='hidden' name='filter' value='$filter'>
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

	// Use the standard dialog-box
    //----------------------------
    // TODO: Merge with WebDialogDaemon

    WebDialogBox("dialogdaemon", WEBCONFIG_LANG_SERVER_STATUS, WEBCONFIG_DIALOG_ICON_DAEMON, $content);
}

// vim: syntax=php ts=4
?>
