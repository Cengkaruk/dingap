<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2002-2005 Point Clark Networks.
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

require_once('../../gui/Webconfig.inc.php');
require_once('../../api/Webconfig.class.php');
require_once('../../api/UserManager.class.php');
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-intro.png", WEB_LANG_PAGE_INTRO);
WebCheckUserDatabase();

///////////////////////////////////////////////////////////////////////////////
//
// Handle update
//
///////////////////////////////////////////////////////////////////////////////

$usermanager = new UserManager();
$webconfig = new Webconfig();

$admin = isset($_POST['EditAdmin']) ? key($_POST['EditAdmin']) : "";

try {
	if (isset($_POST['SetSubAdminMode'])) {

		$state = isset($_POST['admins_enabled']) ? true : false;
		$webconfig->SetAdminAccessState($state);

	} else if (isset($_POST['SetPages'])) {

		// TODO: fix this hardcoded pages
		$new_pages = array_map("FixPageLinks", array_keys($_POST['pages']));
		$new_pages[] = "/include/getimage.php";
		$new_pages[] = "/index.php";

		$webconfig->SetValidPages(key($_POST['SetPages']), $new_pages);

	} else if (isset($_POST['DeleteAdmin'])) {

		$webconfig->SetValidPages(key($_POST['DeleteAdmin']), "");

	} else if (isset($_POST['AddAdmin'])) {

		$admin = strtolower($_POST['new_admin']);
		$new_pages = array("/admin/index.php","/index.php");
		$webconfig->SetValidPages($admin, $new_pages);

	}
} catch (Exception $e) {
	WebDialogWarning($e->GetMessage());
}

if (! empty($admin)) {
	DisplayEditAdmin($admin);
} else {
	DisplayAdmins();
}

WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayAdmins()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayAdmins()
{
	global $webconfig;
	global $usermanager;

	try {
		$admins = $webconfig->GetAdminList();
		$userlist = $usermanager->GetAllUsers(UserManager::TYPE_WEBCONFIG);
		$allowsubadmins  = $webconfig->GetAdminAccessState() ? " checked" : "";
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	$user_options = "";

	foreach ($userlist as $user) {
		if ( !in_array($user, $admins) && ($user != "root"))
			$user_options .= "<option value='$user'>$user</option>\n";
	}

	if ($user_options) {
		$user_dropdown = "
			<tr>
			<td><select name='new_admin'>$user_options</select></td>
			<td>" . WebButtonAdd("AddAdmin") . "</td>
			</tr>\n";

	} else {
		if (count($admins) == 0)
			$user_dropdown = "<tr><td align='center'>" . WEB_LANG_NO_USERS_EXIST . "</td></tr>";
	}

	$rows = "";
	$index = 0;
	foreach($admins as $admin) {
		$rowclass = 'rowenabled';
		$rowclass .= ($index % 2) ? 'alt' : '';
		$rows .= "
			 <tr class='$rowclass'>
			 <td>$admin</td>
			 <td>" . WebButtonEdit("EditAdmin[$admin]") . WebButtonDelete("DeleteAdmin[$admin]") . "</td>
			 </tr>\n";
		$index++;
	}

	// HTML
	//-----

	WebFormOpen();
	WebTableOpen(WEB_LANG_PAGE_TITLE, "450");
	echo "
		<tr>
			<td colspan='2' class='mytableheader'>" . WEB_LANG_ADMINISTRATOR_ACCESS . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . LOCALE_LANG_STATUS . "</td>
			<td>
				<input type='checkbox' name='admins_enabled' value='1' ".$allowsubadmins." /> &nbsp;
				" . WebButtonUpdate("SetSubAdminMode") . "
			</td>
		</tr>
		<tr>
			<td colspan='2' class='mytableheader'>" . WEB_LANG_ADMINISTRATOR . "</td>
		</tr>
		$rows
		$user_dropdown
	";
	WebTableClose("450");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayEditAdmin()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayEditAdmin($username)
{
	global $webconfig;

	try {
		$validpageinfo = $webconfig->GetValidPages($username);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

	$pagedata = WebMenuFetch();

	$valid_pages = $validpageinfo[Webconfig::TYPE_USER_ADMIN];
	$splitrowsize = count($pagedata) / 2;
	$colnum = 1;
	$rowcount = 0;
	$index = 0;
	$lastsection = "";
	$lastsubsection = "";
	$cols[1] = "";
	$cols[2] = "";
	$newcolumn = false;

	$userpages = $validpageinfo['regular'];

	foreach ($pagedata as $page) {
		if (in_array($page['url'], $userpages))
			continue;

		$rowcount++;

		if ($rowcount >= $splitrowsize && $colnum != 2) {
			$colnum = 2;
			$newcolumn = true;
			$cols[$colnum] .= "<tr><td colspan='2' class='mytableheader'>" . $page['section'] . "</td></tr>\n";
			$cols[$colnum] .= "<tr><td colspan='2' class='mytablealt'>&#160; <b>" . $page['subsection'] . "</b></td></tr>\n";
		}

		if ($page['section'] != $lastsection) {
			$lastsection = $page['section'];
			if (!$newcolumn)
				$cols[$colnum] .= "<tr><td colspan='2' class='mytableheader'>" . $page['section'] . "</td></tr>\n";
		}

		if ($page['subsection'] != $lastsubsection) {
			$lastsubsection = $page['subsection'];
			if (!$newcolumn)
				$cols[$colnum] .= "<tr><td colspan='2' class='mytablealt'>&#160; <b>" . $page['subsection'] . "</b></td></tr>\n";
		}

		$name = str_replace("/", "_", $page['url']);
		$checked = ((in_array($page['url'], $valid_pages)) ? " checked " : "");
		$cols[$colnum] .= "
		  <tr>
			<td>&#160; &#160; &#160; " . $page['title'] . "</td>
			<td><input id='acl$index' type='checkbox' name='pages[" . $name . "]' value='1' " . $checked . " /></td>
		  </tr>\n";
		$index++;
		// Reset flag
		$newcolumn = false;
	}

	WebFormOpen();
	WebTableOpen(WEB_LANG_ADMINISTRATOR . " - " . $username, "95%");
	echo "
		<tr>
			<td width='50%' valign='top'>
				<table width='100%' border='0' cellspacing='0' cellpadding='3' class='mytable'>
					$cols[1]
				</table>
			</td>
			<td width='50%' valign='top'>
				<table width='100%' border='0' cellspacing='0' cellpadding='3' class='mytable'>
					$cols[2]
				</table>
			</td>
		</tr>
		<tr>
			<td colspan='2' align='center'>" . 
				WebButton('AllLists', LOCALE_LANG_SELECT_ALL, WEBCONFIG_ICON_CHECKMARK, array('type' => 'button', 'onclick' => 'SelectAll()')) .
	      WebButton('NoLists', LOCALE_LANG_SELECT_NONE, WEBCONFIG_ICON_XMARK, array('type' => 'button', 'onclick' => 'SelectNone()')) .
				WebButtonUpdate("SetPages[$username]") .
				WebButtonCancel("Cancel") . "
			</td>
		</tr>
	";
	WebTableClose("95%");
	WebFormClose();
}


function FixPageLinks($link)
{
	return str_replace("_", "/", $link);
}

// vim: syntax=php ts=4
?>
