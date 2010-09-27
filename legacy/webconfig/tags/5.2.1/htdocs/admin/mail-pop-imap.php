<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2003 Point Clark Networks.
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
require_once("../../api/Cyrus.class.php");
require_once("../../api/Kolab.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-mail-pop-imap.png", WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$cyrus = new Cyrus();

try {
	if (isset($_POST['EnableBoot'])) {
		$cyrus->SetBootState(true);
	} else if (isset($_POST['DisableBoot'])) {
		$cyrus->SetBootState(false);
	} else if (isset($_POST['StartDaemon'])) {
		$cyrus->SetRunningState(true);
		// Kolab creates the mailboxes, so it needs to be informed
		// when the Cyrus daemon has started up.
		$kolab = new Kolab();
		$kolab->Reset();
	} else if (isset($_POST['StopDaemon'])) {
		$cyrus->SetRunningState(false);
	} else if (isset($_POST['EnableService'])) {
		$cyrus->EnableService(key($_POST['EnableService']));
		$cyrus->Reset();
	} else if (isset($_POST['DisableService'])) {
		$cyrus->DisableService(key($_POST['DisableService']));
		$cyrus->Reset();
	} else if (isset($_POST['EnableIdled'])) {
		$cyrus->EnableIdled();
		$cyrus->Reset();
	} else if (isset($_POST['DisableIdled'])) {
		$cyrus->DisableIdled();
		$cyrus->Reset();
	} else if (isset($_POST['UpdateLogLevel'])) {
		$cyrus->SetLogLevel($_POST['loglevel']);
		$cyrus->Reset();
	}
} catch (Exception $e) {
    WebDialogWarning($e->GetMessage());
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

WebDialogDaemon("cyrus-imapd");
DisplayConfig();
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
	global $cyrus;

	try {
		$pop3senabled = $cyrus->GetServiceState("pop3s");
		$pop3enabled = $cyrus->GetServiceState("pop3");
		$imapenabled = $cyrus->GetServiceState("imap");
		$imapsenabled = $cyrus->GetServiceState("imaps");
		$idledenabled = $cyrus->GetIdledState();
		$cyrusrunning = $cyrus->GetRunningState();
		$loglevel = $cyrus->GetLogLevel();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	// Idle Service
	//-------------

	if ($idledenabled) {
		$idled = WebButtonToggle("DisableIdled", LOCALE_LANG_DISABLE);
		$idled_status = "<span class='ok'>" . LOCALE_LANG_ENABLED . "</span>";
	} else {
		$idled = WebButtonToggle("EnableIdled", LOCALE_LANG_ENABLE);
		$idled_status = "<span class='alert'>" . LOCALE_LANG_DISABLED . "</span>";
	}

	// POP3 Service
	//-------------

	if ($pop3enabled) {
		$pop3 = WebButtonToggle("DisableService[" . Cyrus::CONSTANT_SERVICE_POP3 . "]", LOCALE_LANG_DISABLE);
		$pop3_status = "<span class='ok'>" . LOCALE_LANG_ENABLED . "</span>";
	} else {
		$pop3 = WebButtonToggle("EnableService[" . Cyrus::CONSTANT_SERVICE_POP3 . "]", LOCALE_LANG_ENABLE);
		$pop3_status = "<span class='alert'>" . LOCALE_LANG_DISABLED . "</span>";
	}

	if ($pop3senabled) {
		$pop3s = WebButtonToggle("DisableService[" . Cyrus::CONSTANT_SERVICE_POP3S . "]", LOCALE_LANG_DISABLE);
		$pop3s_status = "<span class='ok'>" . LOCALE_LANG_ENABLED . "</span>";
	} else {
		$pop3s = WebButtonToggle("EnableService[" . Cyrus::CONSTANT_SERVICE_POP3S . "]", LOCALE_LANG_ENABLE);
		$pop3s_status = "<span class='alert'>" . LOCALE_LANG_DISABLED . "</span>";
	}

	// IMAP Service
	//-------------
		
	if ($imapenabled) {
		$imap = WebButtonToggle("DisableService[" . Cyrus::CONSTANT_SERVICE_IMAP . "]", LOCALE_LANG_DISABLE);
		$imap_status = "<span class='ok'>" . LOCALE_LANG_ENABLED . "</span>";
	} else {
		$imap = WebButtonToggle("EnableService[" . Cyrus::CONSTANT_SERVICE_IMAP . "]", LOCALE_LANG_ENABLE);
		$imap_status = "<span class='alert'>" . LOCALE_LANG_DISABLED . "</span>";
	}

	if ($imapsenabled) {
		$imaps = WebButtonToggle("DisableService[" . Cyrus::CONSTANT_SERVICE_IMAPS . "]", LOCALE_LANG_DISABLE);
		$imaps_status = "<span class='ok'>" . LOCALE_LANG_ENABLED . "</span>";
	} else {
		$imaps = WebButtonToggle("EnableService[" . Cyrus::CONSTANT_SERVICE_IMAPS . "]", LOCALE_LANG_ENABLE);
		$imaps_status = "<span class='alert'>" . LOCALE_LANG_DISABLED . "</span>";
	}

	// Log Level
	//----------

	if ($loglevel != Cyrus::CONSTANT_LEVEL_UNKNOWN) {
		$loglevel_options = array();
		$loglevel_options[Cyrus::CONSTANT_LEVEL_ALL] = WEB_LANG_DETAILED;
		$loglevel_options[Cyrus::CONSTANT_LEVEL_INFO] = WEB_LANG_NORMAL;
		
		$logoutput = "
		  <tr>
			<td colspan='3' class='mytableheader'>" . WEB_LANG_LOGGING_POLICY . "</td>
		  </tr>
		  <tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_LOG_LEVEL . "</td>
			<td nowrap>" . WebDropDownHash("loglevel", $loglevel, $loglevel_options) . "</td>
			<td nowrap>" . WebButtonUpdate("UpdateLogLevel") . "</td>
		  </tr>
		";
	}

	// Sanity Check
	//-------------

	if (($imapenabled || $imapsenabled || $pop3enabled || $pop3senabled) && (!$cyrusrunning))
		WebDialogWarning(WEB_LANG_PROTOCOL_SET_BUT_SERVER_NOT_RUNNING);

	WebFormOpen();
	WebTableOpen(WEB_LANG_PAGE_TITLE, "400");
	WebTableHeader(CYRUS_LANG_SERVICE . "|" . LOCALE_LANG_STATUS . "|");
	echo "
      <tr>
	    <td width='175' class='mytablesubheader' nowrap>" . CYRUS_LANG_POP3 . "</td>
        <td nowrap>$pop3_status</td>
        <td nowrap>$pop3</td>
      </tr>
      <tr>
	    <td class='mytablesubheader' nowrap>" . CYRUS_LANG_IMAP . "</td>
        <td nowrap>$imap_status</td>
	    <td nowrap>$imap</td>
      </tr>
      <tr>
	    <td class='mytablesubheader' nowrap>" . CYRUS_LANG_POP3S . "</td>
        <td nowrap>$pop3s_status</td>
        <td nowrap>$pop3s</td>
      </tr>
      <tr>
	    <td class='mytablesubheader' nowrap>" . CYRUS_LANG_IMAPS . "</td>
        <td nowrap>$imaps_status</td>
	    <td nowrap>$imaps</td>
      </tr>
      <tr>
		<td colspan='3' class='mytableheader'>" . WEB_LANG_MOBILE_AND_PUSH_EMAIL_SUPPORT . "</td>
      </tr>
      <tr>
	    <td class='mytablesubheader' nowrap>" . CYRUS_LANG_PUSH_EMAIL . "</td>
        <td nowrap>$idled_status</td>
	    <td nowrap>$idled</td>
      </tr>
	  $logoutput
	";
	WebTableClose("400");
	WebFormClose();
}

?>
