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

require_once("../../gui/Webconfig.inc.php");
require_once("../../api/Horde.class.php");
require_once("../../api/Syswatch.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-webmail.png", WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$horde = new Horde();

try {
	if (isset($_POST['UpdateConfig'])) {

		if (isset($_POST['htmlinline']))
			$horde->SetHtmlInlinePolicy(true);
		else
			$horde->SetHtmlInlinePolicy(false);

		if (isset($_POST['imagesinline']))
			$horde->SetImagesInlinePolicy(true);
		else
			$horde->SetImagesInlinePolicy(false);

		// We have to restart webconfig if the port changes.  Take special
		// care in only doing this restart when necessary.

		$currentport = $horde->GetAlternativePort();

		if ($currentport != $_POST['port']) {
			$horde->SetAlternativePort($_POST['port']);
			$syswatch = new Syswatch();
			$syswatch->ReconfigureSystem();
		}
	}

	if (isset($_POST['UpdateLogin'])) {
		$login_block = isset($_POST['login_block']) ? true : false;
		$horde->SetLoginBlockPolicy($login_block);
		$horde->SetLoginBlockCount($_POST['login_count']);
		$horde->SetLoginBlockTime($_POST['login_time']);
	}

	if (isset($_POST['AddLogo'])){
		if (empty($_FILES['logo']['name'])) { 
			WebDialogWarning(WEB_LANG_UPLOAD_LOGO_MISSING);
		} else {
			if (is_uploaded_file($_FILES['logo']['tmp_name'])) {
				$horde->ImportLogoImage($_FILES['logo']);
			} else {
				WebDialogWarning(WEB_LANG_UPLOAD_ERROR);
			}
		}
	}

	if (isset($_POST['DeleteLogo']))
		$horde->DeleteLogoImage();

	if (isset($_POST['UpdateLogoUrl']))
		$horde->SetLogoUrl($_POST['link']);

	// Check for validation errors
    $errors = $horde->GetValidationErrors(true);

    if (!empty($errors))
        WebDialogWarning($errors);

} catch (Exception $e) {
	WebDialogWarning($e->GetMessage());
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

DisplayServerSettings();
// TODO: Horde settings do not seem to be working
//DisplayLoginSettings();
DisplayLogoSettings();
WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayServerSettings()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayServerSettings()
{
	global $horde;

	try {
		$htmlinline = $horde->GetHtmlInlinePolicy();
		$imagesinline = $horde->GetImagesInlinePolicy();
		$port = $horde->GetAlternativePort();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

	$htmlinlinechecked = ($htmlinline) ? ' checked ' : '';
	$imagesinlinechecked = ($imagesinline) ? ' checked ' : '';

	WebFormOpen();
	WebTableOpen(WEB_LANG_SERVER_SETTINGS);
	echo "
		<tr>
			<td width='200' class='mytablesubheader' nowrap>" . WEB_LANG_SHOW_HTML_INLINE . "</td>
			<td><input type='checkbox' name='htmlinline' $htmlinlinechecked /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_SHOW_IMAGES_INLINE . "</td>
			<td><input type='checkbox' name='imagesinline' $imagesinlinechecked /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_ALTERNATE_PORT . "</td>
			<td><input type='text' name='port' size='8' value='$port' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>&nbsp; </td>
			<td>" . WebButtonUpdate("UpdateConfig") . "</td>
		</tr>
	";
	WebTableClose();
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayLoginSettings()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayLoginSettings()
{
	global $horde;

	try {
		$loginblock = $horde->GetLoginBlockPolicy();
		$login_count = $horde->GetLoginBlockCount();
		$login_time = $horde->GetLoginBlockTime();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

	$login_count_options = array(3, 4, 5, 6, 7, 8, 9, 10);
	$login_time_options = array(5, 10, 15, 20, 25, 30, 60, 120, 180);

	$blockchecked = ($loginblock) ? 'checked' : '';

	WebFormOpen();
	WebTableOpen(WEB_LANG_LOGIN_SETTINGS);
	echo "
		<tr>
			<td width='400' class='mytablesubheader' nowrap>" . WEB_LANG_BLOCK_LOGIN . "</td>
			<td><input type='checkbox' name='login_block' value='1' $blockchecked /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_BLOCK_LOGIN_COUNT . "</td>
			<td>" . WebDropdownArray("login_count", $login_count, $login_count_options) . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_BLOCK_LOGIN_DURATION . "</td>
			<td>" . WebDropdownArray("login_time", $login_time, $login_time_options) . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>&nbsp; </td>
			<td>" . WebButtonUpdate('UpdateLogin') . "</td>
		</tr>
	";
	WebTableClose();
	WebFormClose();

}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayLogoSettings()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayLogoSettings()
{
	global $horde;

	try {
		$logo = $horde->GetLogoImage();
		$url = $horde->GetLogoUrl();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

	if (empty($logo)) {
		$logo_output = "
			<tr>
				<td class='mytablesubheader' nowrap>" . WEB_LANG_LOGO_UPLOAD . "</td>
				<td><input type='file' name='logo' value=''/></td>
				<td>" . WebButtonAdd('AddLogo') . "</td>
			</tr>
		";
	} else {
		$_SESSION['getimage_path'] = dirname($logo);
		$logofile = basename($logo);

		$logo_output = "
			<tr>
				<td valign='top' class='mytablesubheader' nowrap>" . HORDE_LANG_LOGO . "</td>
				<td><img src='/include/getimage.php?source=$logofile&time=" . time() . " alt=''></td>
				<td valign='top'>" . WebButtonDelete('DeleteLogo') . "</td>
			</tr>
		";
	}

	WebFormOpen();
	WebTableOpen(WEB_LANG_LOGO_SETTINGS);
	echo "
		<tr>
			<td width='100' class='mytablesubheader' nowrap>" . WEB_LANG_LOGO_URL . "</td>
			<td width='250'><input type='text' size='30' name='link' value='$url'></td>
			<td>" . WebButtonUpdate('UpdateLogoUrl') . "</td>
		</tr>
		$logo_output
	";
	WebTableClose();
	WebFormClose();
}

// vim: syntax=php ts=4
?>
