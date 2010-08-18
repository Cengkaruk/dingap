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
require_once("../../api/Fetchmail.class.php");
require_once("../../api/UserManager.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-mail-retrieval.png", WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$fetchmail = new Fetchmail();
$last = array("active" => true);

try {
	if (isset($_POST['EnableBoot'])) {
		$fetchmail->SetBootState(true);
	} else if (isset($_POST['DisableBoot'])) {
		$fetchmail->SetBootState(false);
	} else if (isset($_POST['StartDaemon'])) {
		$entries = $fetchmail->GetConfigEntries();
		if (count($entries) == 0)
			WebDialogWarning(WEB_LANG_NO_FETCHMAIL_ACCOUNTS_CONFIGURED);
		else
			$fetchmail->SetRunningState(true);
	} else if (isset($_POST['StopDaemon'])) {
		$fetchmail->SetRunningState(false);
	} else if (isset($_POST['DoAdd'])) {
		$field = $_POST['field'];
		$keep = isset($field["keep"]) ? true : false;
		$fetchmail->AddConfigEntry(
			$field["poll"],
			$field["protocol"],
			$field["ssl"],
			$field["username"],
			$field["password"],
			$field["is"],
			$keep
		);
		$fetchmail->Reset();
		unset($_POST);
	} else if (isset($_POST['DoUpdate'])) {
		$field = $_POST['field'];
		$keep = isset($field["keep"]) ? true : false;
		$active = (isset($field["active"]) && $field["active"]) ? true : false;
		$fetchmail->ReplaceConfigEntry(
			$field["start"],
			$field["length"],
			$field["poll"],
			$field["protocol"],
			$field["ssl"],
			$field["username"],
			$field["password"],
			$field["is"],
			$keep,
			$active
		);
		$fetchmail->Reset();
		unset($_POST);
	} else if (isset($_POST['DoGlobalUpdate'])) {
		$fetchmail->SetPollInterval($_POST['poll_interval'] * 60);
		$fetchmail->Reset();
	} else if (isset($_POST['DoToggle'])) {
		$fetchmail->ToggleConfigEntry(key($_POST['DoToggle']));
		$fetchmail->Reset();
	} else if (isset($_POST['DoDelete'])) {
		$info = explode("-", key($_POST['DoDelete']), 2);
		$fetchmail->DeleteConfigEntry($info[0], $info[1]);
		$fetchmail->Reset();
	} else if (isset($_POST['Cancel'])) {
		unset($_POST);
	}
} catch (Exception $e) {
	WebDialogWarning($e->GetMessage());
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

WebCheckUserDatabase();

if (isset($_POST['EditEntry'])) {
	DisplayEdit($_POST['EditEntry']);
} else {
	WebDialogDaemon("fetchmail");
	DisplayMain();
	DisplayAdd();
}

WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayMain()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayMain()
{
	global $last;
	global $fetchmail;

	try {
		$entries = $fetchmail->GetConfigEntries();
		$poll_interval = $fetchmail->GetPollInterval() / 60;
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	# Current list of maildrops
	# -------------------------

	$mailentries = "";
	$index = 0;

	foreach ($entries as $entry) {

		if ($entry['active']) {
			$iconclass = "iconenabled";
			$rowclass = "rowenabled";
		} else {
			$iconclass = "icondisabled";
			$rowclass = "rowdisabled";
		}

		$rowclass .= ($index % 2) ? "alt" : "";
		$index++;

		if ( (isset($entry["nodns"]) && $entry["nodns"]) || (isset($entry["localdomains"]) && $entry["localdomains"])) {
			$mailentries .= "
		 <tr class='$rowclass'>
		  <td class='$iconclass'>&nbsp; </td>
				<td>$entry[poll]</td>
				<td>$entry[protocol]</td>
				<td>$entry[username]</td>
				<td><i>multidrop</i></td>
				<td>$statusicon</td>
				<td>... </td>
			  </tr>
			";
		} else {
			$mailentries .= "
		 <tr class='$rowclass'>
		  <td class='$iconclass'>&nbsp; </td>
				<td>$entry[poll]</td>
				<td>$entry[protocol]" . (isset($entry['ssl']) && $entry['ssl'] ? ' - ' . FETCHMAIL_LANG_FIELD_SSL : '') . "</td>
				<td>$entry[username]</td>
				<td>$entry[is]</td>
				<td>$statusicon</td>
				<td>" . 
					WebButtonToggle("DoToggle[" . $entry["start"] . "]", ($entry['active']) ? LOCALE_LANG_DISABLE : LOCALE_LANG_ENABLE) .
					WebButtonEdit("EditEntry[" . $entry["start"] . "]") . 
					WebButtonDelete("DoDelete[" . $entry["start"] . "-" . $entry["length"] . "]") . "
				</td>
			  </tr>
			";
		}
	}

	if (!$mailentries)
		$mailentries = "<tr><td colspan='6' align='center'>" . LOCALE_LANG_ERRMSG_NO_ENTRIES . "</td></tr>";

	# Poll interval drop-down
	# -----------------------

	$intervals = array('1', '2', '3', '4', '5', '10', '15', '20', '30', '60', '120');
	$interval_dropdown = WebDropDownArray("poll_interval", $poll_interval, $intervals);

	# HTML
	# ----

	WebFormOpen();
	WebTableOpen(WEB_LANG_GLOBAL_CONFIG_TITLE, "350");
	echo "
	  <tr>
		<td align='center'>" . FETCHMAIL_LANG_POLL_INTERVAL . "&#160;  $interval_dropdown " . WEB_LANG_MINUTES . "</td>
		<td>" . WebButtonUpdate("DoGlobalUpdate") . "</td>
	  </tr>
	";
	WebTableClose("350");
	WebFormClose();

	WebFormOpen();
	WebTableOpen(WEB_LANG_MAIN_CONFIG_TITLE, "100%");
	WebTableHeader(
		"|" . 
		FETCHMAIL_LANG_FIELD_POLL . "|" .
		FETCHMAIL_LANG_FIELD_PROTOCOL . "|" .
		FETCHMAIL_LANG_FIELD_USERNAME . "|" .
		FETCHMAIL_LANG_FIELD_LOCALUSER . "|" .
		"|"
	);
	echo $mailentries;
	WebTableClose("100%");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayAdd()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayAdd()
{
	$users = new UserManager();

	try {
		$userlist = $users->GetAllUsers(UserManager::TYPE_EMAIL);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	$user_options = "";

	foreach ($userlist as $user) {
		$selected = (isset($_POST['field']['is']) && $_POST['field']['is'] == $user) ? "selected" : "";
		$user_options .= "<option value='$user' $selected>$user</option>\n";
	}

	// Bail if no users defined
	if (!$user_options)
		return;

	$user_dropdown = WebDropDownArray("field[is]", "", $users);

	// Add new fetchmail section
	if (isset($_POST['field']['poll']))
		$last['poll'] = $_POST['field']['poll'];
	if (isset($_POST['field']['username']))
		$last['username'] = $_POST['field']['username'];
	if (isset($_POST['field']['password']))
		$last['password'] = $_POST['field']['password'];
	if (isset($_POST['field']['ssl']))
		$last['ssl'] = $_POST['field']['ssl'];

	$last_keep = (isset($last["keep"]) && $last["keep"]) ? "checked" : "";
	$poll = isset($last['poll']) ? $last['poll'] : "";
	$username = isset($last['username']) ? $last['username'] : "";
	$password = isset($last['password']) ? $last['password'] : "";

	WebFormOpen();
	WebTableOpen(WEB_LANG_ADD_CONFIG_TITLE, "80%");
	echo "
	  <tr>
	  	<td class='mytablesubheader' nowrap>" . FETCHMAIL_LANG_FIELD_POLL . "</td>
		<td><input type='text' name='field[poll]' value='$poll' style='width:200px'/></td>
	  </tr>
	  <tr>
	  	<td class='mytablesubheader' nowrap>" . FETCHMAIL_LANG_FIELD_PROTOCOL . "</td>
		<td>
		  <select name='field[protocol]'>
			<option>pop3</option>
			<option>apop</option>
			<option>imap</option>
			<option>auto</option>
		  </select>
		</td>
	  </tr>
	  <tr>
	  	<td class='mytablesubheader' nowrap>" . FETCHMAIL_LANG_FIELD_SSL . "</td>
        <td>" . WebDropDownEnabledDisabled("field[ssl]", $ssl) . "</td>
	  </tr>
	  <tr>
	  	<td class='mytablesubheader' nowrap>" . FETCHMAIL_LANG_FIELD_USERNAME . "</td>
		<td><input type='text' name='field[username]' value='$username' style='width:200px'/></td>
	  </tr>
	  <tr>
	  	<td class='mytablesubheader' nowrap>" . FETCHMAIL_LANG_FIELD_PASSWORD . "</td>
		<td><input type='password' name='field[password]' value='$password' style='width:200px'/></td>
	  </tr>
	  <tr>
	  	<td class='mytablesubheader' nowrap>" . FETCHMAIL_LANG_FIELD_LOCALUSER . "</td>
		<td><select name='field[is]'>$user_options</select></td>
	  </tr>
	  <tr>
	  	<td class='mytablesubheader' nowrap>" . FETCHMAIL_LANG_FIELD_KEEP . "</td>
		<td><input type='checkbox' name='field[keep]' $last_keep /></td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' nowrap>&#160;</td>
		<td>" . WebButtonAdd("DoAdd") . "</td>
	  </tr>
	";
	WebTableClose("80%");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayEdit()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayEdit($EditEntry)
{
	global $fetchmail;

	$users = new UserManager();
	$entry = array();
	$user_options = "";

	try {
		$entries = $fetchmail->GetConfigEntries();
		$userlist = $users->GetAllUsers(UserManager::TYPE_EMAIL);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	foreach($entries as $poss_entry) {
		if ($poss_entry["start"] == key($EditEntry)) {
			$entry = $poss_entry;
			break;
		}
	}

	# User list
	# ---------

	foreach ($userlist as $user) {
		$selected = ($user == $entry['is']) ? "selected" : "";
		$user_options .= "<option value='$user' $selected>$user</option>\n";
	}

	$keep = ($entry["keep"]) ? "checked" : "";

	# HTML
	# ----

	WebFormOpen();
	echo "<input type='hidden' name='field[start]' value='$entry[start]' />";
	echo "<input type='hidden' name='field[length]' value='$entry[length]' />";
	echo "<input type='hidden' name='field[active]' value='$entry[active]' />";
	WebTableOpen(WEB_LANG_EDIT_ENTRY_TITLE, "100%");
	echo "
	  <tr>
	  	<td class='mytablesubheader' nowrap>" . FETCHMAIL_LANG_FIELD_POLL . "</td>
		<td><input type='text' name='field[poll]' value='$entry[poll]' style='width:200px'/></td>
	  </tr>
	  <tr>
	  	<td class='mytablesubheader' nowrap>" . FETCHMAIL_LANG_FIELD_PROTOCOL . "</td>
		<td>" . WebDropDownArray("field[protocol]", $entry['protocol'], array('pop3', 'apop', 'imap', 'auto')) . "</td>
	  </tr>
	  <tr>
	  	<td class='mytablesubheader' nowrap>" . FETCHMAIL_LANG_FIELD_SSL . "</td>
        <td>" . WebDropDownEnabledDisabled("field[ssl]", $entry['ssl']) . "</td>
	  </tr>
	  <tr>
	  	<td class='mytablesubheader' nowrap>" . FETCHMAIL_LANG_FIELD_USERNAME . "</td>
		<td><input type='text' name='field[username]' value='$entry[username]' style='width:200px'/></td>
	  </tr>
	  <tr>
	  	<td class='mytablesubheader' nowrap>" . FETCHMAIL_LANG_FIELD_PASSWORD . "</td>
		<td><input type='password' name='field[password]' value='$entry[password]' style='width:200px'/></td>
	  </tr>
	  <tr>
	  	<td class='mytablesubheader' nowrap>" . FETCHMAIL_LANG_FIELD_LOCALUSER . "</td>
		<td><select name='field[is]'>$user_options</select></td>
	  </tr>
	  <tr>
	  	<td class='mytablesubheader' nowrap>" . FETCHMAIL_LANG_FIELD_KEEP . "</td>
		<td><input type='checkbox' name='field[keep]' $keep /></td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' nowrap>&#160;</td>
		<td>" . WebButtonUpdate("DoUpdate") . WebButtonCancel("Cancel") . "</td>
	  </tr>
	";
	WebTableClose("100%");
	WebFormClose();
}

// vim: ts=4
?>
