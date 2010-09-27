<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2007 Point Clark Networks.
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
require_once("../../api/Postfix.class.php");
require_once("../../api/Postgrey.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-mail-greylisting.png", WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$postfix = new Postfix();
$postgrey = new Postgrey();

try {
	if (isset($_POST['EnableGreylist'])) {
		$postgrey->SetState(true);
		$postgrey->Reset();
		$postfix->Reset();
	} else if (isset($_POST['DisableGreylist'])) {
		$postgrey->SetState(false);
		$postgrey->Reset();
		$postfix->Reset();
	} else if (isset($_POST['UpdateGreylist'])) {
		$postgrey->SetMaxAge($_POST['maxage']);
		$postgrey->SetDelay($_POST['delay']);
		$postgrey->Reset();
	}
} catch (Exception $e) {
	WebDialogWarning($e->GetMessage());
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

DisplayGreylistStatus();
DisplayGreylist();
WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayGreylistStatus
//
///////////////////////////////////////////////////////////////////////////////

function DisplayGreylistStatus()
{
	global $postgrey;

	try {
		$is_onboot = $postgrey->GetBootState();
		$is_running = $postgrey->GetRunningState();
		$is_configured = $postgrey->GetMailConfigurationState();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

	// TODO: Merge with WebDialogDaemon

	if ($is_onboot && $is_running && $is_configured) {
		$status_button = WebButtonToggle("DisableGreylist", DAEMON_LANG_STOP);
		$status = "<span class='ok'><b>" . DAEMON_LANG_RUNNING . "</b></span>";
	} else {
		$status_button = WebButtonToggle("EnableGreylist", DAEMON_LANG_START);
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
// DisplayGreylist
//
///////////////////////////////////////////////////////////////////////////////

function DisplayGreylist()
{
	global $postgrey;

	try {
		$delay = $postgrey->GetDelay();	
		$maxage = $postgrey->GetMaxAge();	
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	$delay_options = array(
		'120' => '2 ' . LOCALE_LANG_MINUTES,
		'180' => '3 ' . LOCALE_LANG_MINUTES,
		'240' => '4 ' . LOCALE_LANG_MINUTES,
		'300' => '5 ' . LOCALE_LANG_MINUTES . " - " . LOCALE_LANG_DEFAULT,
		'360' => '6 ' . LOCALE_LANG_MINUTES,
		'420' => '7 ' . LOCALE_LANG_MINUTES,
		'480' => '8 ' . LOCALE_LANG_MINUTES,
		'540' => '9 ' . LOCALE_LANG_MINUTES,
		'600' => '10 ' . LOCALE_LANG_MINUTES,
		'1200' => '20 ' . LOCALE_LANG_MINUTES,
		'1800' => '30 ' . LOCALE_LANG_MINUTES,
		'2400' => '40 ' . LOCALE_LANG_MINUTES,
		'3000' => '50 ' . LOCALE_LANG_MINUTES,
		'3600' => '60 ' . LOCALE_LANG_MINUTES,
	);

	$maxage_options = array(
		'5' => '5 ' . LOCALE_LANG_DAYS,
		'10' => '10 ' . LOCALE_LANG_DAYS,
		'15' => '15 ' . LOCALE_LANG_DAYS,
		'20' => '20 ' . LOCALE_LANG_DAYS,
		'25' => '25 ' . LOCALE_LANG_DAYS,
		'30' => '30 ' . LOCALE_LANG_DAYS,
		'35' => '35 ' . LOCALE_LANG_DAYS . " - " . LOCALE_LANG_DEFAULT,
		'40' => '40 ' . LOCALE_LANG_DAYS,
		'45' => '45 ' . LOCALE_LANG_DAYS,
		'50' => '50 ' . LOCALE_LANG_DAYS,
		'55' => '55 ' . LOCALE_LANG_DAYS,
		'60' => '60 ' . LOCALE_LANG_DAYS,
	);

	WebFormOpen();
	WebTableOpen(WEB_LANG_CONFIGURE_GREYLIST, "450");
	echo "
		<tr>
			<td width='200' class='mytablesubheader' nowrap>" . POSTGREY_LANG_DELAY . "</td>
			<td>" . WebDropDownHash("delay", $delay, $delay_options) . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . POSTGREY_LANG_MAX_AGE . "</td>
			<td>" . WebDropDownHash("maxage", $maxage, $maxage_options) . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>&nbsp; </td>
			<td>" . WebButtonUpdate("UpdateGreylist") . "</td>
		</tr>
	";
	WebTableClose("450");
	WebFormClose();
}

// vim: syntax=php ts=4
?>
