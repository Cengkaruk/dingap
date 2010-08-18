<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2006-2007 Point Clark Networks.
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
require_once("../../api/User.class.php");
require_once("../../api/PosixUser.class.php");
require_once("user.inc.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-users.png", WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$oldpassword = isset($_POST['oldpassword']) ? $_POST['oldpassword'] : null;
$password = isset($_POST['password']) ? $_POST['password'] : null;
$verify = isset($_POST['verify']) ? $_POST['verify'] : null;

if (isset($_POST['UpdateUser'])) {
	try {
		$user = new User($_SESSION['user_login']);
		$user->SetPassword($oldpassword, $password, $verify, $_SESSION['user_login']);

		WebDialogInfo(LOCALE_LANG_SYSTEM_UPDATED);

		$oldpassword = null;
		$password = null;
		$verify = null;
	} catch (Exception $e) {
		WebDialogWarning($e->getMessage());
	}

} else if (isset($_POST['UpdateAdminPassword']) && isset($_SESSION['system_login'])) {

	try {
		$user = new PosixUser("root");
		$user->SetPassword($password, $verify);
		WebDialogInfo(LOCALE_LANG_SYSTEM_UPDATED);
		$password = null;
		$verify = null;
	} catch (ValidationException $e) {
		WebDialogWarning(WebCheckErrors($user->GetValidationErrors(true)));
	} catch (Exception $e) {
		WebDialogWarning($e->getMessage());
	}

} else if (isset($_POST['Cancel'])) {
	$userinfo = null;
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

if (isset($_SESSION['system_login']))
	DisplayAdminPassword($password, $verify);
else
	DisplayUser($_SESSION['user_login'], $userinfo, $oldpassword, $password, $verify);

WebFooter();

// vim: syntax=php ts=4
?>
