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
require_once("../../api/Group.class.php");
require_once("../../api/GroupManager.class.php");
require_once("../../api/UserManager.class.php");
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

$groupmanager = new GroupManager();

$group_name = isset($_POST['groupname']) ? $_POST['groupname'] : "";
$description = isset($_POST['description']) ? $_POST['description'] : "";
$group_description = '';

// Flag to show main screen summary
$displayedit = false;

if (isset($_POST['AddGroup'])) {
	try {
		$group = new Group($_POST['groupname']);
		$group->IsValidGroupName($_POST['groupname']);
		$group->IsValidDescription($_POST['description']);

		if (count($group->GetValidationErrors()) == 0) {
			$group->Add($_POST['description']);
			$group_name = $_POST['groupname'];
			$displayedit = true;
		} else {
			WebDialogWarning($group->GetValidationErrors());
		}
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
} else if (isset($_POST['EditGroup'])) {
	try {
		$group_name = key($_POST['EditGroup']);
		$displayedit = true;
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
} else if (isset($_POST['UpdateGroup'])) {
	try {
		$edit_name = strtolower($_POST['edit_name']);
		$edit_description = $_POST['edit_description'];
		$members = isset($_POST['members']) ? array_keys($_POST['members']) : array();

		$group = new Group($edit_name);
		$group->IsValidGroupName($edit_name);
		$group->IsValidDescription($edit_description);

		if (count($group->GetValidationErrors()) == 0) {
			$group->SetDescription($edit_description);
			$group->SetMembers($members);
		} else {
			$group_name = $edit_name;
			$group_description = $edit_description;
			$displayedit = true;
			WebDialogWarning($group->GetValidationErrors());
		}
	} catch (Exception $e) {
		$displayedit = true;
		WebDialogWarning($e->GetMessage());
	}
} else if (isset($_POST['Confirm'])) {
	try {
		$group_in_use = false;

		// TODO: handle this a different way in 5.1
		if (file_exists("../../api/Flexshare.class.php")) {
			require_once("../../api/Flexshare.class.php");
			$flexshare = new Flexshare();
			$grouplist = $flexshare->GetShareSummary();
			foreach ($grouplist as $id => $info) {
				if ($info['Group'] == $_POST['group']) {
					$group_in_use = true;
					break;
				}
			}
		}

		if ($group_in_use) {
			WebDialogWarning(WEB_LANG_GROUP_EXISTS_IN_FLEXSHARE);
		} else {
			$group = new Group($_POST['group']);
			$group->Delete();
		}

	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
}

if (isset($_POST['DeleteGroup'])) {
	DisplayDeleteGroup(key($_POST['DeleteGroup']));
} else if ($displayedit) {
	DisplayEdit($group_name, $group_description);
} else {
	DisplayGroups($group_name, $description);
	DisplaySpecialGroups();
}

Webfooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplaySpecialGroups()
//
///////////////////////////////////////////////////////////////////////////////

function DisplaySpecialGroups()
{
	global $groupmanager;

	try {
		$groups = $groupmanager->GetGroupList(GroupManager::TYPE_BUILTIN);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

	$index = 0;
	$rowdata = "";

	foreach($groups as $gid => $group) {
		$rowclass = 'rowenabled';
		$rowclass .= ($index % 2) ? 'alt' : '';

		$rowdata .= "
			<tr class='" . $rowclass . "'>
				<td width='20%'>" . $group["group"] . "</td>
				<td width='40%'>" . $group["description"] . "</td>
				<td width='40%' nowrap>" . WebButtonEdit("EditGroup[" . $group["group"] . "]") . "</td>
			</tr>\n
		";

		$index++;
	}

	if (! $rowdata)
		$rowdata = "<tr><td colspan='3' align='center'>" . WEB_LANG_NO_GROUPS_DEFINED . "</td></tr>";

	WebFormOpen();
	WebTableOpen(WEB_LANG_BUILTIN_GROUPS, "70%");
	WebTableHeader(GROUP_LANG_GROUP . "|" . LOCALE_LANG_DESCRIPTION . "|");
	WebTableBody($rowdata);
	WebTableClose("70%");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayGroups()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayGroups($groupname, $description) 
{
	global $groupmanager;

	try {
		$groups = $groupmanager->GetGroupList(GroupManager::TYPE_USER_DEFINED);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

	$index = 0;
	$rowdata = "";

	foreach ($groups as $gid => $group) {
		$rowclass = 'rowenabled';
		$rowclass .= ($index % 2) ? 'alt' : '';

		$rowdata .= "
			<tr class='" . $rowclass . "'>
				<td width='20%'>" . $group["group"] . "</td>
				<td width='40%'>" . $group["description"] . "</td>
				<td width='40%' nowrap>" . 
					WebButtonEdit("EditGroup[" . $group["group"] . "]") . 
					WebButtonDelete("DeleteGroup[" . $group["group"] . "]") . "
				</td>
			</tr>\n
		";

		$index++;
	}

	WebFormOpen();
	WebTableOpen(WEB_LANG_USER_DEFINED_GROUPS, "70%");
	WebTableHeader(GROUP_LANG_GROUP . "|" . LOCALE_LANG_DESCRIPTION . "|");
	WebTableBody($rowdata);
	echo "
		<tr>
			<td><input type='text' name='groupname' value='$groupname'></td>
			<td><input type='text' name='description' value='$description'></td>
			<td nowrap>" . WebButtonAdd("AddGroup") . "</td>
		</tr>
	";
	WebTableClose("70%");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayAdd()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayAdd($groupname, $description) 
{
	WebFormOpen();
	WebTableOpen(WEB_LANG_ADD_GROUP, "70%");
	WebTableClose("70%");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayEdit()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayEdit($name, $description)
{
	$group = new Group($name);
	$usermanager = new UserManager();

	$users = array();
	$members = array();

	try {
		$users = $usermanager->GetAllUsers();
		$members = $group->GetMembers();

		if (empty($description))
			$description = $group->GetDescription();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	$index = 0;
	$memberdata = "";

	foreach ($users as $user) {
		$index++;
		$checked = in_array($user, $members) ? "checked" : "";
		$memberdata .= "<td width='33%'><input type='checkbox' name='members[$user]' $checked>$user</td>";

		if ($index %3 == 0 && $index < count($users))
			$memberdata .= "</tr><tr>";
		else if ($index == count($users)+1)
			$memberdata .= "</tr>";
	}

	if (empty($memberdata))
		$memberdata = '---';
	else
		$memberdata = "
				<table width='100%' cellpadding='0' cellspacing='0' class='mytablecheckboxes'>
				  <tr>
					$memberdata
				  </tr>
				</table>
	";

	WebFormOpen();
	WebTableOpen(WEB_LANG_EDIT_GROUP, "100%");
	echo "
		<tr>
			<td class='mytablesubheader' nowrap>" . GROUP_LANG_GROUP . "</td>
			<td>$name <input type='hidden' name='edit_name' value='$name' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . LOCALE_LANG_DESCRIPTION . "</td>
			<td><input type='text' name='edit_description' value='$description' style='width:350px' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap valign='top'>" . GROUP_LANG_MEMBERS . "</td>
			<td>$memberdata</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>&#160;</td>
			<td nowrap>" .  WebButtonUpdate("UpdateGroup") . " " . WebButtonCancel("Cancel") . "</td>
		</tr>
	";
	WebTableClose("100%");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayDeleteGroup()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayDeleteGroup($group)
{
	WebFormOpen();
	WebTableOpen(LOCALE_LANG_CONFIRM, "70%");
	echo "
	  <tr>
		<td align='center'>
		  <input type='hidden' name='group' value='$group'>
		  <p>" . WEBCONFIG_ICON_WARNING . " " . WEB_LANG_ARE_YOU_SURE_DELETE . " <b><i>" . $group . "</i></b></p>" .
		  WebButtonDelete("Confirm") . " " . WebButtonCancel("Cancel") . "
		</td>
	  </tr>
	";
	WebTableClose("70%");
	WebFormClose();
}

// vi: syntax=php ts=4
?>
