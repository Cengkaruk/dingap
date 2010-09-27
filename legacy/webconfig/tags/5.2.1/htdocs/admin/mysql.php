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
require_once("../../api/Mysql.class.php");
require_once("../../api/Software.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-mysql.png", WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$verify = isset($_POST['verify']) ? $_POST['verify'] : "";
$password = isset($_POST['password']) ? $_POST['password'] : "";
$oldpassword = isset($_POST['oldpassword']) ? $_POST['oldpassword'] : "";

$mysql = new Mysql();

try {
	if (isset($_POST['EnableBoot'])) {
		$mysql->SetBootState(true);
	} else if (isset($_POST['DisableBoot'])) {
		$mysql->SetBootState(false);
	} else if (isset($_POST['StartDaemon'])) {
		$mysql->SetRunningState(true);
		sleep(3);
	} else if (isset($_POST['StopDaemon'])) {
		$mysql->SetRunningState(false);
	} else if (isset($_POST['SetPassword'])) {
		$warning = VerifyPassword($password, $verify);
		if ($warning) {
			WebDialogWarning($warning);
		} else {
			$mysql->SetRootPassword($oldpassword, $password);
			$password = '';
			$oldpassword = '';
			$verify = '';
		}
	}
} catch (Exception $e) {
	WebDialogWarning($e->GetMessage());
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

WebDialogDaemon("mysqld");
DisplayConfig($oldpassword, $password, $verify);
WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayConfig()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayConfig($oldpassword, $password, $verify)
{
	global $mysql;

	// Bail if MySQL isn't running

	try {
		if (! $mysql->GetRunningState())
			return;
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	// Sanity check for blank password
	// Check for phpMyAdmin

	$phpmyadmin_installed = false;
	$ispasswordset = false;

	try {
		$phpmyadmin = new Software("phpMyAdmin");
		$phpmyadmin_installed = $phpmyadmin->IsInstalled();
		$ispasswordset = $mysql->IsRootPasswordSet();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	if ($ispasswordset) {

		if ($phpmyadmin_installed)
			WebDialogInfo(WEB_LANG_PHPMYADMIN_BLURB . " -- <a target='_blank' href='https://" . getenv("SERVER_ADDR") . ":81/mysql/'>" . LOCALE_LANG_GO . "</a>");

		WebFormOpen();
		WebTableOpen(WEB_LANG_PAGE_TITLE, "480");
		echo "
		  <tr>
			<td class='mytablesubheader' nowrap>" . LOCALE_LANG_OLD_PASSWORD . "</td>
			<td nowrap><input type='password' name='oldpassword' size='15' value='' /></td>
			<td width='200' rowspan='5' class='help'>" . WEB_LANG_PASSWORD_RESET . "</td>
		  </tr>
		  <tr>
			<td class='mytablesubheader' nowrap>" . LOCALE_LANG_PASSWORD . "</td>
			<td nowrap><input type='password' name='password' size='15' value='$password' /></td>
		  </tr>
		  <tr>
			<td class='mytablesubheader' nowrap>" . LOCALE_LANG_VERIFY . "</td>
			<td nowrap><input type='password' name='verify' size='15' value='$verify' /></td>
		  </tr>
		  <tr>
			<td class='mytablesubheader'>&#160; </td>
			<td>" . WebButtonUpdate("SetPassword") . "</td>
		  </tr>
		";
		WebTableClose("480");
		WebFormClose();

	} else {

		WebDialogWarning(WEB_LANG_PASSWORD_NOT_SET);

		WebFormOpen();
		WebTableOpen(WEB_LANG_PAGE_TITLE, "300");
		echo "<input type='hidden' name='oldpassword' value='' />";
		echo "
		  <tr>
			<td class='mytablesubheader' nowrap>" . LOCALE_LANG_PASSWORD . "</td>
			<td><input type='password' name='password' size='15' value='$password' /></td>
		  </tr>
		  <tr>
			<td class='mytablesubheader' nowrap>" . LOCALE_LANG_VERIFY . "</td>
			<td><input type='password' name='verify' size='15' value='$verify' /></td>
		  </tr>
		  <tr>
			<td class='mytablesubheader' nowrap>&#160; </td>
			<td>" . WebButtonUpdate("SetPassword") . "</td>
		  </tr>
		";
		WebTableClose("300");
		WebFormClose();
	}
}


// TODO: move this to webconfig.inc
function VerifyPassword($password, $verify)
{
	if (!$password || !$verify)
		return WEB_LANG_PASSWORD_REQUIRED;

	if ($password != $verify)
		return WEB_LANG_PASSWORD_VERIFY_MISMATCH;
}

// vim: ts=4
?>
