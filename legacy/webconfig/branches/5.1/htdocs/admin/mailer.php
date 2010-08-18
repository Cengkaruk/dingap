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
// TODO: page hangs when port is listening, but invalid (e.g. port 443)

require_once("../../gui/Webconfig.inc.php");
require_once("../../api/Hostname.class.php");
require_once("../../api/Mailer.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-mailer.png", WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$ssl = isset($_POST['ssl']) ? $_POST['ssl'] : "";
$host = isset($_POST['host']) ? $_POST['host'] : "";
$port = isset($_POST['port']) ? $_POST['port'] : "";
$username = isset($_POST['username']) ? $_POST['username'] : "";
$password = isset($_POST['password']) ? $_POST['password'] : "";
$email = isset($_POST['email']) ? $_POST['email'] : "";

$mailer = new Mailer();

if (isset($_POST['UpdateSettings'])) {
	try {
		$mailer->SetSmtpHost($_POST['host']);
		$mailer->SetSmtpPort($_POST['port']);
		$mailer->SetSmtpSsl($_POST['ssl']);
		$mailer->SetSmtpUsername($_POST['username']);
		$mailer->SetSmtpPassword($_POST['password']);
		$mailer->SetSender($_POST['sender']);
	} catch (Exception $e) {
		WebDialogWarning($e->getMessage());
	}
} else if (isset($_POST['TestRelay'])) {
	try {
		$mailer->TestRelay($_POST['email']);
		WebDialogInfo(MAILER_LANG_TEST_SUCCESS);
	} catch (ValidationException $e) {
		WebDialogWarning($e->getMessage());
	} catch (Exception $e) {
		WebDialogWarning($e->getMessage());
	}
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

DisplaySettings($ssl, $host, $port, $username, $password, $email);
WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplaySettings()
//
///////////////////////////////////////////////////////////////////////////////

function DisplaySettings($ssl, $host, $port, $username, $password, $email)
{
	global $mailer;

	// TODO: fix validation handling
	try {
		$ssl = $mailer->GetSmtpSsl();
		$host = $mailer->GetSmtpHost();
		$port = $mailer->GetSmtpPort();
		$options = $mailer->GetSslOptions();
		$username = $mailer->GetSmtpUsername();
		$password = $mailer->GetSmtpPassword();
		$sender = $mailer->GetSender();
		if ($sender == null || $sender == "") {
			// Fill in default
			$hostname = new Hostname();
			$sender = "root@" . $hostname->Get();
		}
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	$ssl_options = '';

	foreach ($options as $key=>$value) {
		if ($ssl == $key)
			$ssl_options .= "<option value='" . $key . "' selected>" . $value . "</option>\n";
		else
			$ssl_options .= "<option value='" . $key . "'>" . $value . "</option>\n";
	}

	WebFormOpen();
	WebTableOpen(WEB_LANG_PAGE_TITLE, "80%");
	echo "
		<tr>
			<td class='mytableheader' colspan='2'>" . WEB_LANG_SETTINGS . "</td>
		</tr>
		<tr>
			<td width='200' class='mytablesubheader' nowrap>" . MAILER_LANG_HOST . "</td>
			<td><input type='text' name='host' size='35' value='$host'></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . MAILER_LANG_PORT . "</td>
			<td><input type='text' name='port' size='4' value='$port'></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . MAILER_LANG_SSL_TLS . "</td>
			<td><select name='ssl'>$ssl_options</select></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . MAILER_LANG_USERNAME . "</td>
			<td><input type='text' name='username' size='35' value='$username'></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . MAILER_LANG_PASSWORD . "</td>
			<td><input type='password' name='password' size='35' value='$password'></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . MAILER_LANG_SENDER . "</td>
			<td><input type='text' name='sender' size='35' value='$sender'></td>
		</tr>
		<tr>
			<td class='mytablesubheader'>&#160; </td>
			<td>" .  WebButtonUpdate("UpdateSettings") . "</td>
		</tr>
		<tr>
			<td class='mytableheader' colspan='2'>" . WEB_LANG_TEST . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . MAILER_LANG_TEST_EMAIL . "</td>
			<td nowrap><input type='text' name='email' size='35' value='$email'></td>
        </tr>
		<tr>
			<td class='mytablesubheader'>&#160; </td>
			<td>" .  WebButton("TestRelay", WEB_LANG_RUN_TEST_NOW, WEBCONFIG_ICON_CONTINUE) . "</td>
		</tr>
	";
	WebTableClose("80%");
	WebFormClose();
}

// vim: syntax=php ts=4
?>
