<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2006-2009 Point Clark Networks.
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
require_once(GlobalGetLanguageTemplate(__FILE__));

// In the future, we might implement a more flexible system for managing
// which fields are user-editable.

$acl = array('password', 'verify', 'telephone', 'fax');

///////////////////////////////////////////////////////////////////////////////
//
// DisplayAdminPassword()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayAdminPassword($password, $verify)
{
	WebFormOpen();
	WebTableOpen(WEB_LANG_ROOT_PASSWORD_CHANGE);
	echo "
		<tr>
			<td class='mytablesubheader' nowrap>" . LOCALE_LANG_PASSWORD . "</td>
			<td><input size='30' type='password' name='password' value='$password' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . LOCALE_LANG_VERIFY . "</td>
			<td><input size='30' type='password' name='verify' value='$verify' /></td>
		</tr>
	";

	if (! WebIsSetup()) {
		echo "
			<tr>
				<td class='mytablesubheader' nowrap>&#160;</td>
				<td>" . WebButtonUpdate("UpdateAdminPassword") . "</td>
			</tr>
		";
	}

	WebTableClose();

	if (! WebIsSetup())
		WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayUser()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayUser($username, $userinfo, $password, $verify)
{
	global $acl;

	try {
		$user = new User($username);
		$currentinfo = $user->GetInfo();
	} catch (Exception $e) {
		WebDialogWarning($e->getMessage());
		return ;
	}

	foreach ($currentinfo as $field => $data) {
		$name[$field] = in_array($field, $acl) ? "name='userinfo[$field]'" : "name='$field' class='readonly' readonly";

		if (isset($userinfo[$field]))
			$value[$field] = $userinfo[$field];
		else if (isset($currentinfo[$field]))
			$value[$field] = $data;
		else
			$value[$field] = "";
	}

	// Show add user table
	//--------------------

	WebFormOpen();
	WebTableOpen(WEB_LANG_PAGE_TITLE);
	echo "
		<tr>
			<td colspan='2' class='mytableheader'>" . USER_LANG_USER_DETAILS . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . LOCALE_LANG_USERNAME . "</td>
			<td><input size='30' type='text' $name[uid] value='$value[uid]' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . USER_LANG_FIRST_NAME . "</td>
			<td><input size='30' type='text' $name[firstName] value='$value[firstName]' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . USER_LANG_LAST_NAME . "</td>
			<td><input size='30' type='text' $name[lastName] value='$value[lastName]' /></td>
		</tr>
		<tr>
			<td colspan='2' class='mytableheader'>" . LOCALE_LANG_PASSWORD . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . LOCALE_LANG_PASSWORD . "</td>
			<td><input size='30' type='password' name='password' value='$password' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . LOCALE_LANG_VERIFY . "</td>
			<td><input size='30' type='password' name='verify' value='$verify' /></td>
		</tr>
		<tr>
			<td colspan='2' class='mytableheader'>" . USER_LANG_PHONE_NUMBERS . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . ORGANIZATION_LANG_PHONE . "</td>
			<td><input size='30' type='text' $name[telephone] value='$value[telephone]' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . ORGANIZATION_LANG_FAX . "</td>
			<td><input size='30' type='text' $name[fax] value='$value[fax]' /></td>
		</tr>
		<tr>
			<td colspan='2' class='mytableheader'>" . ORGANIZATION_LANG_ADDRESS . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . ORGANIZATION_LANG_STREET . "</td>
			<td><input size='30' type='text' $name[street] value='$value[street]' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . ORGANIZATION_LANG_ROOM_NUMBER . "</td>
			<td><input size='30' type='text' $name[roomNumber] value='$value[roomNumber]' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . ORGANIZATION_LANG_CITY . "</td>
			<td><input size='30' type='text' $name[city] value='$value[city]' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . ORGANIZATION_LANG_REGION . "</td>
			<td><input size='30' type='text' $name[street] value='$value[street]' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . ORGANIZATION_LANG_COUNTRY . "</td>
			<td><input size='30' type='text' $name[country] value='$value[country]' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . ORGANIZATION_LANG_POSTAL_CODE . "</td>
			<td><input size='30' type='text' $name[postalCode] value='$value[postalCode]' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . ORGANIZATION_LANG_ORGANIZATION . "</td>
			<td><input size='30' type='text' $name[organization] value='$value[organization]' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . ORGANIZATION_LANG_ORGANIZATION_UNIT . "</td>
			<td><input size='30' type='text' $name[unit] value='$value[unit]' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>&#160;</td>
			<td>" . WebButtonUpdate("UpdateUser") . " " . WebButtonCancel("Cancel") . "</td>
		</tr>
	";
	WebTableClose();
	WebFormClose();
}

// vim: syntax=php ts=4
?>
