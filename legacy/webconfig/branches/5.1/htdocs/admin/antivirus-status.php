<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2007-2009 Point Clark Networks.
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
require_once("../../api/ClamAv.class.php");
require_once("../../api/Freshclam.class.php");
require_once("../../api/NtpTime.class.php");
require_once(GlobalGetLanguageTemplate('antivirus'));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-antivirus-report.png", WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$clamav = new ClamAv();
$freshclam = new Freshclam();

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

DisplayAntivirusStatus();
WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayAntivirusStatus()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayAntivirusStatus() 
{
	global $freshclam;
	global $clamav;

	try {
		if (file_exists("../../api/DansGuardianAv.class.php")) {
			require_once("../../api/DansGuardianAv.class.php");
			$dansguardianav = new DansGuardian();
			$dansguardian_running = $dansguardianav->GetRunningState();
		}

		if (file_exists("../../api/Antivirus.class.php")) {
			require_once("../../api/Antivirus.class.php");
			$antivirus = new Antivirus();
			$antivirus_scheduled = $antivirus->ScanScheduleExists();
		}

		if (file_exists("../../api/Amavis.class.php")) {
			require_once("../../api/Amavis.class.php");
			$amavis = new Amavis();
			$amavis_enabled = $amavis->GetAntivirusState();
			$amavis_running = $amavis->GetRunningState();
		}

		$clamav_running = $clamav->GetRunningState();

		$freshclam_running = $freshclam->GetRunningState();
		$freshclam_update_info = $freshclam->GetLastChangeInfo();
		$freshclam_last_check = $freshclam->GetLastCheck();

		$ntptime = new NtpTime();
		$timezone = $ntptime->GetTimeZone();
		date_default_timezone_set($timezone);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	if (empty($freshclam_last_check))
		$freshclamcheck = "...";
	else
		$freshclamcheck = strftime("%x %X", $freshclam_last_check);

	if (empty($freshclam_update_info['accessed']))
		$freshclamchange = "...";
	else
		$freshclamchange = strftime("%x %X", $freshclam_update_info['accessed']);
	if ($freshclam_running)
		$freshclamhtml = "<span class='ok'>" . DAEMON_LANG_RUNNING . "</span>";
	else
		$freshclamhtml = "<span class='alert'>" . DAEMON_LANG_STOPPED . "</span>";

	if ($clamav_running)
		$clamavhtml = "<span class='ok'>" . DAEMON_LANG_RUNNING . "</span>";
	else
		$clamavhtml = "<span class='alert'>" . DAEMON_LANG_STOPPED . "</span>";

	if (!isset($dansguardian_running))
		$dansguardianhtml = WEB_LANG_NOT_INSTALLED;
	else if ($dansguardian_running)
		$dansguardianhtml = "<span class='ok'>" . DAEMON_LANG_RUNNING . "</span>";
	else
		$dansguardianhtml = "<span class='alert'>" . DAEMON_LANG_STOPPED . "</span>";

	if (!isset($antivirus_scheduled))
		$filehtml = WEB_LANG_NOT_INSTALLED;
	else if ($antivirus_scheduled)
		$filehtml = "<span class='ok'>" . DAEMON_LANG_RUNNING . "</span>";
	else
		$filehtml = "<span class='alert'>" . DAEMON_LANG_STOPPED . "</span>";

	if (!isset($amavis_enabled) || !isset($amavis_running))
		$mailhtml = WEB_LANG_NOT_INSTALLED;
	else if ($amavis_enabled && $amavis_running)
		$mailhtml = "<span class='ok'>" . DAEMON_LANG_RUNNING . "</span>";
	else
		$mailhtml = "<span class='alert'>" . DAEMON_LANG_STOPPED . "</span>";

	WebFormOpen();
	WebTableOpen(WEB_LANG_STATUS_INFORMATION, "500");
	WebTableHeader(WEB_LANG_ANTIVIRUS_UPDATES . "|");
	echo "
		<tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_LAST_CHECK . "</td>
			<td>$freshclamcheck</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_LAST_CHANGE . "</td>
			<td>$freshclamchange</td>
		</tr>
	";
	WebTableHeader(WEB_LANG_ANTIVIRUS_STATUS . "|");
	echo "
		<tr>
			<td width='200' class='mytablesubheader' nowrap>" . WEB_LANG_ANTIVIRUS_ENGINE . "</td>
			<td>$clamavhtml</td>
		</tr>
		<tr>
			<td width='200' class='mytablesubheader' nowrap>" . WEB_LANG_ANTIVIRUS_UPDATES . "</td>
			<td>$freshclamhtml</td>
		</tr>
	";
	WebTableHeader(WEB_LANG_SERVICES_USING_ANTIVIRUS . "|");
	echo "
		<tr>
			<td width='200' class='mytablesubheader' nowrap>" . DAEMON_LANG_POSTFIX . "</td>
			<td>$mailhtml</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . DAEMON_LANG_DANSGUARDIAN . "</td>
			<td>$dansguardianhtml</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_FILE_ANTIVIRUS_SCANNER . "</td>
			<td>$filehtml</td>
		</tr>
	";
	WebTableClose("500");
	WebFormClose();
}

// vim: syntax=php ts=4
?>
