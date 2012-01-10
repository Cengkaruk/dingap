<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2010 Point Clark Networks.
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
require_once("../../api/PasswordPolicy.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-groups.png", WEB_LANG_PAGE_INTRO);
WebCheckUserDatabase();

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$policy = new PasswordPolicy();

if (isset($_POST['UpdatePolicy'])) {
	try {
		$policyobject = array(
			'maximumAge' => $_POST['maximumAge'],
			'minimumAge' => $_POST['minimumAge'],
			'minimumLength' => $_POST['minimumLength'],
			'historySize' => $_POST['historySize'],
			'badPasswordLockout' => $_POST['badPasswordLockout']
		);

		$policy->SetDefaultPolicy($policyobject);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

DisplayPolicy();
Webfooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayPolicy()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayPolicy()
{
	global $policy;

	try {
		$info = $policy->GetDefaultPolicy();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

	$length_options = array(5,6,7,8,9,10,11,12,13,14);

	$min_age_options = array(
		PasswordPolicy::CONSTANT_MODIFY_ANY_TIME => PASSWORDPOLICY_LANG_MODIFY_ANY_TIME,
		86400 => 1 . " " . LOCALE_LANG_DAY, 
		172800 => 2 . " " . LOCALE_LANG_DAYS, 
		259200 => 3 . " " . LOCALE_LANG_DAYS, 
		345600 => 4 . " " . LOCALE_LANG_DAYS, 
		432000 => 5 . " " . LOCALE_LANG_DAYS, 
		864000 => 10 . " " . LOCALE_LANG_DAYS, 
		1728000 => 20 . " " . LOCALE_LANG_DAYS, 
		2592000 => 30 . " " . LOCALE_LANG_DAYS, 
		5184000 => 60 . " " . LOCALE_LANG_DAYS, 
		7776000 => 90 . " " . LOCALE_LANG_DAYS, 
		10368000 => 120 . " " . LOCALE_LANG_DAYS, 
		12960000 => 150 . " " . LOCALE_LANG_DAYS, 
		15552000 => 180 . " " . LOCALE_LANG_DAYS, 
		31536000 => 1 . " " . LOCALE_LANG_YEAR, 
		63072000 => 2 . " " . LOCALE_LANG_YEARS, 
		94608000 => 3 . " " . LOCALE_LANG_YEARS, 
	);

	$max_age_options = array(
		86400 => 1 . " " . LOCALE_LANG_DAY, 
		172800 => 2 . " " . LOCALE_LANG_DAYS, 
		259200 => 3 . " " . LOCALE_LANG_DAYS, 
		345600 => 4 . " " . LOCALE_LANG_DAYS, 
		432000 => 5 . " " . LOCALE_LANG_DAYS, 
		864000 => 10 . " " . LOCALE_LANG_DAYS, 
		1728000 => 20 . " " . LOCALE_LANG_DAYS, 
		2592000 => 30 . " " . LOCALE_LANG_DAYS, 
		5184000 => 60 . " " . LOCALE_LANG_DAYS, 
		7776000 => 90 . " " . LOCALE_LANG_DAYS, 
		10368000 => 120 . " " . LOCALE_LANG_DAYS, 
		12960000 => 150 . " " . LOCALE_LANG_DAYS, 
		15552000 => 180 . " " . LOCALE_LANG_DAYS, 
		31536000 => 1 . " " . LOCALE_LANG_YEAR, 
		63072000 => 2 . " " . LOCALE_LANG_YEARS, 
		94608000 => 3 . " " . LOCALE_LANG_YEARS, 
		PasswordPolicy::CONSTANT_NO_EXPIRE => PASSWORDPOLICY_LANG_NO_EXPIRE,
	);

	$history_options = array(
		PasswordPolicy::CONSTANT_NO_HISTORY => PASSWORDPOLICY_LANG_NO_HISTORY,
		2 => 2,
		3 => 3,
		4 => 4,
		5 => 5,
		10 => 10,
		15 => 15,
		20 => 20,
		25 => 25
	);	

	WebFormOpen();
	WebTableOpen(WEB_LANG_PAGE_TITLE);
	echo "
		<tr>
			<td class='mytablesubheader'>" . PASSWORDPOLICY_LANG_MINIMUM_PASSWORD_LENGTH . "</td>
			<td>" . WebDropDownArray("minimumLength", $info['minimumLength'], $length_options) . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader' width='280'>" . PASSWORDPOLICY_LANG_MINIMUM_PASSWORD_AGE . "</td>
			<td>" . WebDropDownHash("minimumAge", $info['minimumAge'], $min_age_options) . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader'>" . PASSWORDPOLICY_LANG_MAXIMUM_PASSWORD_AGE . "</td>
			<td>" . WebDropDownHash("maximumAge", $info['maximumAge'], $max_age_options) . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader'>" . PASSWORDPOLICY_LANG_HISTORY_SIZE . "</td>
			<td>" . WebDropDownHash("historySize", $info['historySize'], $history_options) . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader'>" . "Password Lockout" . "</td>
			<td>" . WebDropDownEnabledDisabled("badPasswordLockout", $info['badPasswordLockout']) . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader'>&nbsp; </td>
			<td>" . WebButtonUpdate("UpdatePolicy") . "</td>
		</tr>
	";
	WebTableClose();
	WebFormClose();
}

// vi: syntax=php ts=4
?>
