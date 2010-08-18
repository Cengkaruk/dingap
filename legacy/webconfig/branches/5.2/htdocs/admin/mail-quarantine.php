<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2008-2009 Point Clark Networks
//
///////////////////////////////////////////////////////////////////////////////
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 3
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
// Nov 2008 : Original work submitted to Point Clark Networks (W.H.Welch)
//
///////////////////////////////////////////////////////////////////////////////

require_once("../../gui/Webconfig.inc.php");
require_once("../../gui/QuarantineReport.class.php");
require_once("../../api/Mailzu.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-mail-quarantine.png", WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

RenderPage();
WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

function RenderPage()
{
	$mailzu = new Mailzu();

	try {
		$dbpass = $mailzu->GetPassword();
		$domain = $mailzu->GetDomain();
	} catch (Exception $e) {
		 WebDialogWarning($e->GetMessage());
	}

	if (empty($dbpass) || empty($domain)) {
		$links = '';

		if (file_exists('mail-antimalware.php'))
			$links .= "<br>- <a href='mail-antimalware.php'>" . MAILZU_LANG_ANTIMALWARE . "</a>";

		if (file_exists('mail-antispam.php'))
			$links .= "<br>- <a href='mail-antispam.php'>" . MAILZU_LANG_ANTISPAM . "</a>";

		WebDialogWarning(WEB_LANG_ANTISPAM_ANTIVIRUS_MUST_BE_RUNNING . $links);
		return;
	}

	if (! isset($_SESSION['sessionID'])){
		$_SESSION['sessionNav'] = null;
		$_SESSION['sessionID'] = $_SESSION['user_login'];
		$_SESSION['sessionName'] = $_SESSION['user_login'];
		$_SESSION['sessionMail'] = $_SESSION['user_login'] . '@' .  $domain;
		$_SESSION['sessionAdmin'] = false;
		$_SESSION['sessionMailAdmin'] = true;
	}

	WebDialogInfo(WEB_LANG_GO_TO_QUARANTINE . "<br><a href='/mailzu/messagesAdmin.php?ctype=A&searchOnly=0'>" . LOCALE_LANG_GO . "</a>");

	if (isset($_POST['GetDashboardSummary'])){
		$reporttype = 'GetFullReport';
		$toggle = REPORT_LANG_SUMMARY;
	} else {
		$reporttype = 'GetDashboardSummary';
		$toggle = LOCALE_LANG_SHOW_FULL_REPORT;
	}

	// TODO: fatal errors are not properly displayed in a warning bubble
	$report = new QuarantineReport();
	$report->$reporttype();

	WebFormOpen();
	echo "<div style='float:right'>" . WebButtonToggle($reporttype, $toggle). "</div>";
	WebFormClose();

	echo "<br/><br/>";
}

// vim: syntax=php ts=4
?>
