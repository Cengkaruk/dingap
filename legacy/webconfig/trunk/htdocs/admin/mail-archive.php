<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2003-2007 Point Clark Networks.
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
require_once("../../api/Archive.class.php");
require_once("../../api/ClearDirectory.class.php");
require_once("../../api/UserManager.class.php");
require_once("../../api/Postfix.class.php");
require_once("../../api/Cyrus.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE, "default", '', 'Initialize("' . $_SERVER['HTTP_USER_AGENT'] . '")');
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-mail-archive.png", WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Initialize archive mailbox and database
//
///////////////////////////////////////////////////////////////////////////////

try {
	$archive = new Archive();
	$archive->InitializeMailbox();
	$archive->RunBootstrap();
} catch (Exception $e) {
    WebDialogWarning($e->GetMessage());
	WebFooter();
	exit();
}

///////////////////////////////////////////////////////////////////////////////
//
// Set display variables
//
///////////////////////////////////////////////////////////////////////////////

$show_filter = false;
$show_search = false;
$show_archive = false;
$hide_summary = false;
$message = "";

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

if (isset($_POST['ArchiveNow']) && !isset($_POST['Cancel'])) {
	if (!isset($_SESSION['system_login'])) {
		WebDialogWarning(LOCALE_LANG_ACCESS_DENIED);
	} else {
		if (isset($_POST['Confirm'])) {
			try {
				$archive->ArchiveData($_POST['filename'], true);
			} catch (Exception $e) {
				WebDialogWarning($e->GetMessage());
				DisplayArchiveNow();
			}
		} else {
			DisplayArchiveNow();
		}
	}
} else if (isset($_POST['DeleteArchive']) && !isset($_POST['Cancel'])) {
	$filename = key($_POST['DeleteArchive']);
	$show_archive = true;
	if (!isset($_SESSION['system_login'])) {
		WebDialogWarning(LOCALE_LANG_ACCESS_DENIED);
	} else {
		if (isset($_POST['Confirm'])) {
			try {
				$archive->DeleteArchive($filename);
			} catch (Exception $e) {
				$hide_summary = true;
				WebDialogWarning($e->GetMessage());
				DisplayDeleteArchive($filename);
			}
		} else {
			$hide_summary = true;
			DisplayDeleteArchive($filename);
		}
	}
} else if (isset($_POST['UpdateSettings'])) {
	try {
		if (!isset($_SESSION['system_login'])) {
			WebDialogWarning(LOCALE_LANG_ACCESS_DENIED);
		} else {
			$archive->SetPolicy($_POST['policy']);
			$archive->SetAttachmentPolicy($_POST['attachments']);
			$archive->SetAutoArchivePolicy($_POST['auto']);
			$archive->SetArchiveEncryption($_POST['encrypt']);
			if (isset($_POST['encrypt']) && $_POST['encrypt'])
				$archive->SetEncryptionPassword($_POST['password']);
			try {
				$archive->SetStatus($_POST['status']);
			} catch (ValidationException $e) {
				WebDialogWarning($e->GetMessage() . " - " . WebUrlJump("organization.php", LOCALE_LANG_CONFIGURE));
			}
		}
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
} else if (isset($_REQUEST['conf'])) {
	$show_filter = true;
} else if (isset($_REQUEST['Search']) && !isset($_POST['Cancel'])) {
	$rs = null;
	$show_search = true;
	if (isset($_REQUEST['Send'])) {
		try {
			$archive->SpawnRestoreMessage($_POST['db'], $_POST['msgids'], $_POST['to']); 
			WebDialogInfo(WEB_LANG_MESSAGE_QUEUED);
		} catch (Exception $e) {
			WebDialogWarning($e->GetMessage());
		}
	}
	if (isset($_REQUEST['Submit']) || isset($_REQUEST['NavSubmit'])) {
		try {
			# New search
			if (isset($_REQUEST['Submit'])) {
				$_REQUEST['offset'] = 0;
				$_POST['offset'] = 0;
			}
			$rs = $archive->Search(
				$_REQUEST['db'], $_REQUEST['field'], $_REQUEST['criteria'],
				$_REQUEST['regex'], $_REQUEST['logical'], $_REQUEST['max'], $_REQUEST['offset']
			);
		} catch (Exception $e) {
			WebDialogWarning($e->GetMessage());
		}
	}
} else if (isset($_POST['Reset'])) {
	$show_archive = true;
	if (isset($_POST['Confirm']) && !isset($_POST['Cancel'])) {
		try {
			$archive->ResetSearch();
		} catch (Exception $e) {
			WebDialogWarning($e->GetMessage());
		}
	} else if (!isset($_POST['Cancel'])) {
		$hide_summary = true;
		DisplayReset();
	}
} else if (isset($_POST['RestoreArchive']) && !isset($_POST['Back'])) {
	try {
		if (key($_POST['RestoreArchive']))
			$archive->RestoreArchive(key($_POST['RestoreArchive']));
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	$show_archive = true;
} else if (isset($_REQUEST['View']) && !isset($_POST['Cancel'])) {
	try {
		$message = $archive->GetArchivedEmail($_REQUEST['db'], key($_REQUEST['View']));
		if (isset($_POST['Send'])) {
			try {
				$archive->SpawnRestoreMessage($_REQUEST['db'], key($_REQUEST['View']), $_POST['to']); 
				WebDialogInfo(WEB_LANG_MESSAGE_QUEUED);
			} catch (Exception $e) {
				WebDialogWarning($e->GetMessage());
			}
		}
		$show_message = true;
	} catch (Exception $e) {
		$show_search = true;
		WebDialogWarning($e->GetMessage());
	}
} else if (isset($_POST['UpdateFilter'])) {
	$show_filter = true;
	try {
		$archive->SetRecipientAttachmentPolicy($_POST['attach_recipient']);
		$archive->SetSenderAttachmentPolicy($_POST['attach_sender']);
		$postfix = new Postfix();
		$archive_email = $archive->GetArchiveAddress();
		# Bit of work to do before we submit the array to update recipient/sender bcc maps
		$allrecipient = array_keys($_POST['allrecipient']);
		$allsender = array_keys($_POST['allsender']);
		$recipient_map = Array();
		$sender_map = Array();
		foreach ($_POST['domains'] as $domain) {
			# Populate the recipient/sender arrays
			# If all is not selected
			if (! in_array($domain, $allrecipient))
				$recipient[$domain] = array_keys($_POST['recipient'][$domain]); 
			else
				$recipient_map[0] = "@$domain " . $archive_email; 
			if (! in_array($domain, $allsender))
				$sender[$domain] = array_keys($_POST['sender'][$domain]); 
			else
				$sender_map[0] = "@$domain " . $archive_email; 
		}
		# Now populate maps
		foreach ($recipient as $domain => $user) {
			for ($index = 0; $index < count($user); $index++)
				$recipient_map[] = $user[$index] . "@" . $domain . " " . $archive_email;
		}
		foreach ($sender as $domain => $user) {
			for ($index = 0; $index < count($user); $index++)
				$sender_map[] = $user[$index] . "@" . $domain . " " . $archive_email;
		}
		$postfix->SetRecipientBccMaps(Archive::FILE_RECIPIENT_BCC, $recipient_map, true);
		$postfix->SetSenderBccMaps(Archive::FILE_SENDER_BCC, $sender_map, true);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

SanityCheck();

if ($show_message) {
	DisplayMessage($message);
} else if ($show_search) {
	DisplaySearch($rs);
} else if ($show_filter) {
	DisplayFilter();
} else if ($show_archive) {
	DisplayArchive($hide_summary);
} else {
	DisplaySummary();
}

WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplaySummary()
//
///////////////////////////////////////////////////////////////////////////////

function DisplaySummary()
{
	global $archive;

	$activetab = isset($_REQUEST['activetab']) ? $_REQUEST['activetab'] : 'settings';

	try {
		$current = $archive->GetCurrentStats();
		$search = $archive->GetSearchStats();
	} catch (Exception $e) {
	// FIXME: the MySQL bootstrap fails on first page load, but fine after the fact.
	//	WebDialogWarning($e->GetMessage());
	}

	$tabinfo['settings']['title'] = WEB_LANG_CURRENT_SETTINGS;
	$tabinfo['settings']['contents'] = GetSettings();
	$tabinfo['current']['title'] = WEB_LANG_CURRENT_STATS;
	$tabinfo['current']['contents'] = GetCurrentTable($current);
	$tabinfo['search']['title'] = WEB_LANG_SEARCH_STATS;
	$tabinfo['search']['contents'] = GetSearchTable($search);

	echo "<div style='width:80%'>";
	WebTab(WEB_LANG_PAGE_TITLE, $tabinfo, $activetab);
	echo "</div>";
}

///////////////////////////////////////////////////////////////////////////////
//
// GetSettings()
//
///////////////////////////////////////////////////////////////////////////////

function GetSettings()
{
	global $archive;

	# Policy
	if ($archive->GetPolicy() == 0)
		$policy_options = "<option value='0' SELECTED>" . WEB_LANG_ALL . "</option>" .
					   "<option value='1'>" . WEB_LANG_FILTER . "</option>";
	else
		$policy_options = "<option value='0'>" . WEB_LANG_ALL . "</option>" .
					   "<option value='1' SELECTED>" . WEB_LANG_FILTER . "</option>";

	# Schedule
	foreach ($archive->GetArchiveScheduleOptions() as $key => $value) {
		if ($key == $archive->GetAutoArchivePolicy())
			$auto_options .= "<option value='$key' SELECTED>$value</option>\n";
		else
			$auto_options .= "<option value='$key'>$value</option>\n";
	}

	# Attachments
	foreach ($archive->GetArchiveAttachmentOptions() as $key => $value) {
		if ($key == $archive->GetAttachmentPolicy())
			$attach_options .= "<option value='$key' SELECTED>$value</option>\n";
		else
			$attach_options .= "<option value='$key'>$value</option>\n";
	}

	# Encryption
	if ($archive->GetArchiveEncryption()) {
		$encrypt_options = "<option value='1' SELECTED>" . LOCALE_LANG_YES . "</option>" .
					   "<option value='0'>" . LOCALE_LANG_NO . "</option>";
	} else {
		$encrypt_options = "<option value='1'>" . LOCALE_LANG_YES . "</option>" .
					   "<option value='0' SELECTED>" . LOCALE_LANG_NO . "</option>";
	}

	$contents = "
	  <form action='mail-archive.php' method='post' enctype='multipart/form-data'>
	    <table cellspacing='0' cellpadding='5' width='100%' border='0' class='tablebody'>
		  <tr>
			<td width='40%' class='mytablesubheader'>" . WEB_LANG_ARCHIVE . "</td>
			<td>" . WebDropDownEnabledDisabled("status", $archive->GetStatus()) . "</td>
          </tr>
		  <tr>
			<td class='mytablesubheader'>" . WEB_LANG_POLICY . "</td>
            <td>
              <select id='policy' name='policy' onChange='togglepolicy();'>$policy_options</select>
			  <span id='config'>(<a href='" . $_SERVER['PHP_SELF'] . "?conf=1'>" . LOCALE_LANG_CONFIGURE . "</a>)</span>
            </td>
          </tr>
		  <tr>
			<td class='mytablesubheader'>" . WEB_LANG_DISCARD_ATTACHMENTS . "</td>
			<td><select id='attachments' name='attachments'>$attach_options</select></td>
          </tr>
		  <tr>
			<td class='mytablesubheader'>" . WEB_LANG_AUTO_ARCHIVE . "</td>
            <td><select name='auto'>$auto_options</select></td>
          </tr>
		  <tr>
			<td class='mytablesubheader'>" . WEB_LANG_ENCRYPT . "</td>
            <td>
              <select id='encrypt' name='encrypt' onChange='toggleencrypt();'>$encrypt_options</select>
            </td>
          </tr>
		  <tr>
			<td class='mytablesubheader'>" . WEB_LANG_PASSWORD . "</td>
		    <td><input id='password' type='password' name='password' value='" . $archive->GetEncryptionPassword() . "' style='width:180px' /></td>
          </tr>
		  <tr>
			<td class='mytablesubheader'>&#160;</td>
            <td>
			  <input type='hidden' name='activetab' value='settings'>
			  " . WebButtonUpdate("UpdateSettings") . "
            </td>
          </tr>
        </table>
      </form>
	";

	if ($archive->GetPolicy() == 0) {
		$contents .= "<script type=\"text/javascript\" language=\"JavaScript\">enable('attachments');</script>";
		$contents .= "<script type=\"text/javascript\" language=\"JavaScript\">hide('config');</script>";
	} else {
		$contents .= "<script type=\"text/javascript\" language=\"JavaScript\">disable('attachments');</script>";
		$contents .= "<script type=\"text/javascript\" language=\"JavaScript\">show('config', true);</script>";
	}

	if ($archive->GetArchiveEncryption())
		$contents .= "<script type=\"text/javascript\" language=\"JavaScript\">enable('password');</script>";
	else
		$contents .= "<script type=\"text/javascript\" language=\"JavaScript\">disable('password');</script>";

	return $contents;
}

///////////////////////////////////////////////////////////////////////////////
//
// GetCurrentTable($current)
//
///////////////////////////////////////////////////////////////////////////////

function GetCurrentTable($current)
{
	global $archive;

	if ($current['messages'] > 0)
		$buttons = " " . WebButton("ArchiveNow", WEB_LANG_ARCHIVE_NOW, WEBCONFIG_ICON_CALENDAR);
	else
		$buttons = "";

	$contents = "
		<form action='mail-archive.php' method='post' enctype='multipart/form-data'>
		<table cellspacing='0' cellpadding='5' width='100%' border='0' class='tablebody'>
			<tr>
				<td class='mytablesubheader' width='40%' nowrap>" . WEB_LANG_ESTIMATED_SIZE . "</td>
				<td id='archive_stat_size'>" . $archive->GetFormattedBytes($current['size'], 1) . "</td>
			</tr>
			<tr>
				<td class='mytablesubheader' nowrap>" . WEB_LANG_LAST_ARCHIVE . "</td>
				<td>" . $current['last'] . "</td>
			</tr>
			<tr>
				<td class='mytablesubheader' nowrap>" . WEB_LANG_NUM_MESSAGES . "</td>
				<td id='archive_stat_total'>" . $current['messages'] . "</td>
			</tr>
			<tr>
				<td class='mytablesubheader' nowrap>" . WEB_LANG_NUM_ATTACHMENTS . "</td>
				<td id='archive_stat_attach'>" . $current['attachments'] . "</td>
			</tr>
			<tr>
				<td class='mytablesubheader' nowrap width='25%'>" . LOCALE_LANG_STATUS . "</td>
				<td id='archive_state'>" . WEB_LANG_STATUS_IDLE . "</td>
			</tr>
			<tr id='archive_row_progress' style='display: none;'>
				<td class='mytablesubheader'>&#160;</td>
				<td>
				<div id='archive_progress_bar' style='width: 100%;' class='progressbarbg'><div id='archive_progress_percent' align='center' class='progressbarpercent'>0%</div></div>
				</td>
			</tr>
			<tr>
				<td class='mytablesubheader'>&nbsp; </td>
				<td>
					<input type='hidden' name='db' value='" . Archive::DB_NAME_CURRENT . "' />
					<input type='hidden' name='activetab' value='current'>" .
					WebButtonSearch("Search") . $buttons . "
				</td>
			</tr>
		</table>
		</form>
	";

	return $contents;
}

///////////////////////////////////////////////////////////////////////////////
//
// GetSearchTable($search)
//
///////////////////////////////////////////////////////////////////////////////

function GetSearchTable($search)
{
	global $archive;

	$contents = "
		<form action='mail-archive.php' method='post' enctype='multipart/form-data'>
		<table cellspacing='0' cellpadding='5' width='100%' border='0' class='tablebody'>
			<tr>
				<td class='mytablesubheader' width='40%' nowrap>" . WEB_LANG_ESTIMATED_SIZE . "</td>
				<td>" . $archive->GetFormattedBytes($search['size'], 1) . "</td>
			</tr>
			<tr>
				<td class='mytablesubheader' nowrap>" . WEB_LANG_NUM_MESSAGES . "</td>
				<td>" . $search['messages'] . "</td>
			</tr>
			<tr>
				<td class='mytablesubheader' nowrap>" . WEB_LANG_NUM_ATTACHMENTS . "</td>
				<td>" . $search['attachments'] . "</td>
			</tr>
			<tr>
				<td class='mytablesubheader' valign='top'>" . LOCALE_LANG_ACTION . "</td>
				<td nowrap>
					<input type='hidden' name='db' value='" . Archive::DB_NAME_SEARCH. "' />
					<input type='hidden' name='activetab' value='search'>" .
					WebButtonSearch("Search") . " " . WebButton("RestoreArchive", WEB_LANG_RESTORE_ARCHIVE, WEBCONFIG_ICON_CALENDAR) . "
				</td>
			</tr>
		</table>
		</form>
	";

	return $contents;
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayArchive($hide_summary)
//
///////////////////////////////////////////////////////////////////////////////

function DisplayArchive($hide_summary)
{
	global $archive;

	try {
		$stats = $archive->GetSearchStats();
		$list = $archive->GetList();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	$archivelist = "";

	$index = 0;
	foreach($list as $search) {
		$action = WebButtonDelete("DeleteArchive[" . $search['filename']. "]");
		# 1 = good sym link
		if ($search['status'] > 0) {
			$action .= WebButton("RestoreArchive[" . $search['filename'] . "]", WEB_LANG_RESTORE, WEBCONFIG_ICON_OK);
			$statusclass = 'iconenabled';
			$rowclass = 'rowenabled';
		} else {
			$statusclass = 'icondisabled';
			$rowclass = 'rowdisabled';
		}
		$rowclass .= ($index % 2) ? 'alt' : '';
		if ($search['size'])
			$size = $archive->GetFormattedBytes($search['size'], 1); 
		else
			$size = "---";
			

		$archivelist .= "
			<tr class='$rowclass'>
				<td class='$statusclass'>&#160; </td>
				<td>{$search['filename']}</td>
				<td>" . date("Y-m-d H:i", $search['modified']) . "</td>
				<td align='right'>$size</td>
				<td nowrap>$action</td>
			</tr>
		";
		$index++;
	}

	if (! $archivelist) 
		$archivelist = "<tr><td colspan='6' align='center'>" . WEB_LANG_NO_DATA . "</td></tr>";

	WebFormOpen();
	if (! $hide_summary) {
		WebTableOpen(WEB_LANG_SEARCH_STATS, "80%");
		echo "
			<tr>
				<td class='mytablesubheader' nowrap>" . WEB_LANG_ESTIMATED_SIZE . "</td>
				<td>" . $archive->GetFormattedBytes($stats['size'], 1) . "</td>
			</tr>
			<tr>
				<td class='mytablesubheader' nowrap>" . WEB_LANG_NUM_MESSAGES . "</td>
				<td>" . $stats['messages'] . "</td>
			</tr>
			<tr>
				<td class='mytablesubheader' nowrap>" . WEB_LANG_NUM_ATTACHMENTS . "</td>
				<td>" . $stats['attachments'] . "</td>
			</tr>
			<tr>
				<td class='mytablesubheader' width='25%' nowrap>" . LOCALE_LANG_STATUS . "</td>
				<td id='archive_state'>" . WEB_LANG_STATUS_IDLE . "</td>
			</tr>
			<tr id='archive_row_progress' style='display: none;'>
				<td class='mytablesubheader'>&#160;</td>
				<td>
					<div id='archive_progress_bar' style='width: 100%;' class='progressbarbg'><div id='archive_progress_percent' align='center' class='progressbarpercent'>0%</div></div>
				</td>
			</tr>
			<tr>
				<td class='mytablesubheader' valign='top'>" . LOCALE_LANG_ACTION . "</td>
				<td nowrap>
					<input type='hidden' name='activetab' value='search'>" .
					WebButtonReset("Reset") . WebButton("RestoreArchive", WEB_LANG_REFRESH, WEBCONFIG_ICON_UPDATE) .
					WebButtonBack("Back") . "
				</td>
			</tr>
		";
		WebTableClose("80%");
	}
	
	WebTableOpen(WEB_LANG_SEARCH_TITLE, "100%");
	echo "
		<tr>
			<td class='mytableheader' nowrap>&#160;</td>
			<td class='mytableheader' nowrap>" . WEB_LANG_FILENAME . "</td>
			<td class='mytableheader' nowrap>" . LOCALE_LANG_DATE . '/' . LOCALE_LANG_TIME . "</td>
			<td class='mytableheader' align='right'>" . WEB_LANG_SIZE . "</td>
			<td class='mytableheader'>" . LOCALE_LANG_ACTION  . "</td>
		</tr>
	";
	echo $archivelist;
	WebTableClose("100%");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayArchiveNow()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayArchiveNow()
{
	WebFormOpen();
	WebTableOpen(WEB_LANG_ARCHIVE_NOW, "50%");
	echo "
		<tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_FILENAME . "</td>
			<td>
				<input type='text' name='filename' style='width: 200px' />
				<input type='hidden' name='ArchiveNow' value='1' />
				<input type='hidden' name='activetab' value='current'>
			</td>
		</tr>
		<tr>
			<td class='mytablesubheader'>&#160;</td>
			<td>" . WebButtonConfirm("Confirm") . " " . WebButtonCancel("Cancel") . "</td>
		</tr>
	";
	WebTableClose("50%");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayDeleteArchive($filename)
//
///////////////////////////////////////////////////////////////////////////////

function DisplayDeleteArchive($filename)
{
	WebFormOpen();
	WebTableOpen(WEB_LANG_DELETE_FILE, "50%");
	echo "
		<tr>
			<td class='mytablesubheader'>" . WEB_LANG_FILENAME . "</td>
			<td>
				$filename
				<input type='hidden' name='DeleteArchive[$filename]' value='1' />
				<input type='hidden' name='RestoreArchive' value='1' />
			</td>
		</tr>
		<tr>
			<td class='mytablesubheader'>&#160;</td>
			<td>" . WebButtonConfirm("Confirm") . " " . WebButtonCancel("Cancel") . "</td>
		</tr>
	";
	WebTableClose("50%");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayFilter()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayFilter()
{
	global $archive;

	$postfix = new Postfix();

	$domains[] = $postfix->GetDomain();
	$virtual = $postfix->GetVirtualDomains();
	$domains = array_merge($domains, $virtual);
	$destinations = $postfix->GetDestinations(true);
	$domains = array_merge($domains, $destinations);
	$fwlist = $postfix->GetForwarders();
	$forwards = Array();
	foreach($fwlist as $forward)
		$forwards[] = $forward['domain'];
	$domains = array_merge($domains, $forwards);
	$domains = array_unique($domains);
	$attopt = $archive->GetArchiveAttachmentOptions();

	try {
		$usermanager = new UserManager();
		$users = $usermanager->GetAllUsers(ClearDirectory::SERVICE_TYPE_EMAIL);

		$postfix = new Postfix();
        $useraccess = $postfix->GetVirtualUserList();
		try {
			$raw = $postfix->GetRecipientBccMapContents(Archive::FILE_RECIPIENT_BCC);
			# Stil here...reformat array
			foreach ($raw as $entry) {
				$split = split(" ", $entry);
				$recipient_bcc[] = $split[0]; 
			}
		} catch (FileNoMatchException $ignore) {}
		try {
        	$raw = $postfix->GetSenderBccMapsContents(Archive::FILE_SENDER_BCC);
			# Stil here...reformat array
			foreach ($raw as $entry) {
				$split = split(" ", $entry);
				$sender_bcc[] = $split[0]; 
			}
		} catch (FileNoMatchException $ignore) {}
    } catch (EngineException $e) {
        WebDialogWarning($e->GetMessage());
    }

    foreach ($domains as $domain) {
		$domainheader .= "<td colspan='2' align='center' class='mytableheader' nowrap>";
		$domainheader .= "$domain<input type='hidden' name='domains[]' value='$domain' /></td>";
		$filterheader .= "<td align='center' class='mytablealt' width='8%'>" . WEBCONFIG_ICON_INBOUND . "</td>";
		$filterheader .= "<td align='center' class='mytablealt' width='8%'>" . WEBCONFIG_ICON_OUTBOUND . "</td>";
		# Reset options
		$opt_recipient = "";
		$opt_sender = "";
		foreach ($attopt as $key => $value) {
			if ($key == $archive->GetRecipientAttachmentPolicy($domain))
				$opt_recipient .= "<option value='$key' SELECTED>$value</option>\n";
			else
				$opt_recipient .= "<option value='$key'>$value</option>\n";
			if ($key == $archive->GetSenderAttachmentPolicy($domain))
				$opt_sender .= "<option value='$key' SELECTED>$value</option>\n";
			else
				$opt_sender .= "<option value='$key'>$value</option>\n";
		}
		$filterattach .= "<td align='center'>
                            <select name='attach_recipient[$domain]' style='width:75px'>$opt_recipient</select>
                          </td>\n
						 <td align='center'>
                           <select name='attach_sender[$domain]' style='width:75px'>$opt_sender</select>
                         </td>\n
		";
		if (in_array("@$domain", $recipient_bcc))
			$filterall .= "<td align='center'><input type='checkbox' name='allrecipient[$domain]' CHECKED /></td>\n";
		else
			$filterall .= "<td align='center'><input type='checkbox' name='allrecipient[$domain]' /></td>\n";
		if (in_array("@$domain", $sender_bcc))
			$filterall .= "<td align='center'><input type='checkbox' name='allsender[$domain]' CHECKED /></td>\n";
		else
			$filterall .= "<td align='center'><input type='checkbox' name='allsender[$domain]' /></td>\n";
	}
    foreach ($users as $user) {
		$userdata .= "<tr>\n
                <td class='mytablesubheader'>$user</td>\n
		";
    	foreach ($domains as $domain) {
			if (in_array($domain, $forwards))
				$disabled = ' disabled';
			else
				$disabled = '';
			if (in_array("$user@$domain", $recipient_bcc))
				$userdata .= "<td align='center'><input type='checkbox' name='recipient[$domain][$user]' CHECKED $disabled /></td>\n";
			else
				$userdata .= "<td align='center'><input type='checkbox' name='recipient[$domain][$user]' $disabled /></td>\n";
			if (in_array("$user@$domain", $sender_bcc))
				$userdata .= "<td align='center'><input type='checkbox' name='sender[$domain][$user]' $disabled CHECKED /></td>\n";
			else
				$userdata .= "<td align='center'><input type='checkbox' name='sender[$domain][$user]' $disabled /></td>\n";
		}
		$userdata .= "</tr>\n";
	}
	WebFormOpen();
	WebTableOpen(WEB_LANG_ARCHIVE_FILTER, "100%");
	echo "<tr>
            <td class='mytableheader'>" . WEB_LANG_DOMAIN_SETTINGS . "</td>
            $domainheader
          </tr>
	";
	echo "<tr>
            <td class='mytablesubheader'>" . WEB_LANG_IN_OUT . "</td>
            $filterheader
          </tr>
	";
	echo "<tr>
            <td class='mytablesubheader'>" . WEB_LANG_DISCARD_ATTACHMENTS . "</td>
            $filterattach
          </tr>
	";
	echo "<tr>
            <td class='mytablesubheader'>" . WEB_LANG_ALL_USERS . "</td>
            $filterall
          </tr>
	";
	echo "<tr>
            <td colspan='11' class='mytableheader'>" . WEB_LANG_USER_SETTINGS . "</td>
          </tr>
          $userdata
		";
	echo "
          <tr>
		    <td class='mytablesubheader'>&#160;</td>
	        <td colspan='6' align='center' nowrap>" .
              WebButtonUpdate("UpdateFilter") . WebButtonBack("Cancel") . "
            </td>
          </tr>
	";
	WebTableClose("100%");
	WebFormClose();

}

///////////////////////////////////////////////////////////////////////////////
//
// DisplaySearch($rs)
//
///////////////////////////////////////////////////////////////////////////////

function DisplaySearch($rs) {

	global $archive;

	$logical_and = '';
	$logical_or = '';
	if (!isset($_REQUEST['logical']))
		$logical_and = 'CHECKED';  # Default
	else if ($_REQUEST['logical'] == 'AND')
		$logical_and = 'CHECKED';
	else if ($_REQUEST['logical'] == 'OR')
		$logical_or = 'CHECKED';
	$db_current = '';
	$db_search = '';
	$activetab = "<input type='hidden' name='activetab' value='current' />";
	if ($_REQUEST['db'] == Archive::DB_NAME_CURRENT) {
		$db_current = 'CHECKED';
	} else {
		$activetab = "<input type='hidden' name='activetab' value='search' />";
		$db_search = 'CHECKED';
	}

	$max = $archive->GetMaxResultOptions();
	$max_options = "";
	foreach ($max as $key => $desc) {
		if ($_REQUEST['max'] == $key)
			$max_options .= "<option value='$key' SELECTED>$desc</option>\n";
		else
			$max_options .= "<option value='$key'>$desc</option>\n";
	}
	$extrahtml = '';
	for ($rule = 0; $rule < 5; $rule++) {
		$extrahtml .= "
			<input type='hidden' name='field[$rule]' value='" . $_REQUEST['field'][$rule] . "' />
			<input type='hidden' name='criteria[$rule]' value='" . $_REQUEST['criteria'][$rule] . "' />
			<input type='hidden' name='regex[$rule]' value='" . $_REQUEST['regex'][$rule] . "' />
		";
	}
	$fields = $archive->GetSearchFieldOptions();
	$criteria = $archive->GetSearchCriteriaOptions();
	for ($rule = 0; $rule < 5; $rule++) {
		$field_options = "";
		$criteria_options = "";
		foreach ($fields as $key => $desc) {
			if ($_REQUEST['field'][$rule] == $key)
				$field_options .= "<option value='$key' SELECTED>$desc</option>\n";
			else
				$field_options .= "<option value='$key'>$desc</option>\n";
		}
		foreach ($criteria as $key => $desc) {
			if ($_REQUEST['criteria'][$rule] == $key)
				$criteria_options .= "<option value='$key' SELECTED>$desc</option>\n";
			else
				$criteria_options .= "<option value='$key'>$desc</option>\n";
		}
		if ($rule == 0) {
			$icons = "<a href=\"javascript:show('c" . ($rule + 1) . "', true)\">" . WEBCONFIG_ICON_ADD . "</a>&#160;&#160;"; 
			$icons .= WEBCONFIG_ICON_DELETE;
		} else if ($rule == 4) {
			$icons = WEBCONFIG_ICON_ADD . "&#160;&#160;";
			$icons .= "<a href=\"javascript:hide('c$rule')\">" . WEBCONFIG_ICON_DELETE . "</a>"; 
		} else {
			$icons = "<a href=\"javascript:show('c" . ($rule + 1) . "', true)\">" . WEBCONFIG_ICON_ADD . "</a>&#160;&#160;"; 
			$icons .= "<a href=\"javascript:hide('c$rule')\">" . WEBCONFIG_ICON_DELETE . "</a>"; 
		}
		$searchform .= "<tr id='c$rule'>\n";
	    $searchform .= "<td><select name='field[$rule]' style='width: 100px'>$field_options</select></td>";
	    $searchform .= "<td><select name='criteria[$rule]' style='width: 100px'>$criteria_options</select></td>";
	    $searchform .= "<td><input id='c$rule.regex' type='text' name='regex[$rule]' style='width: 200px' value=\"" . $_REQUEST['regex'][$rule] . "\" /></td>";
	    $searchform .= "<td nowrap>$icons</td>";
		$searchform .= "</tr>\n";
	}
	$nav = '';
	if ($rs)
		$nav .=	WebButton("Deliver", WEB_LANG_DELIVER_ALL, WEBCONFIG_ICON_EMAIL, Array('type' => 'button', 'onclick' => 'showsend()'));
	if (isset($_REQUEST['offset']) && $_REQUEST['offset'] > 0) {
		$nav .= WebButtonPrevious("NavSubmit", $_POST['max']);
		$nav .= "<input type='hidden' name='offset' value='" . ($_POST['offset'] - $_POST['max']) . "' />\n";
	}
	if (isset($rs) && count($rs) == $_REQUEST['max']) {
		$nav .= WebButtonNext("NavSubmit", $_POST['max']);
		$nav .= "<input type='hidden' name='offset' value='" . ($_POST['offset'] + $_POST['max']) . "' />\n";
	}
	$searchform .= "
		<tr>
	      <td colspan='4' align='right'>" . $nav .
            WebButtonSearch("Submit") . WebButtonBack("Cancel") . "
	        <input type='hidden' name='Search' value='1' />
		    $activetab
          </td>
        </tr>
	";

	$msgids = '';
	if ($rs) {
		$rowcounter = 0;
		foreach ($rs as $mesg) {
			$rowclass = 'rowenabled';
			$rowclass .= ($rowcounter % 2) ? 'alt' : '';

			# trim lengths
			if (strlen($mesg['sender']) < 25)
				$sender = htmlspecialchars($mesg['sender']);
			else
				$sender = htmlspecialchars(substr($mesg['sender'], 0, 22)) . "...";
			if (strlen($mesg['recipient']) < 25)
				$recipient = htmlspecialchars($mesg['recipient']);
			else
				$recipient = htmlspecialchars(substr($mesg['recipient'], 0, 22)) . "...";
			$searchresults .= "<tr class='$rowclass'>
				<td>" . $mesg['subject'] . "</td>
				<td>" . $sender . "</td>
				<td>" . $recipient . "</td>
				<td>" . date("Y-m-d H:i", $mesg['sent']) . "</td>
            	<td>" .
				WebButton("View[" . $mesg['id'] . "]", WEB_LANG_VIEW, WEBCONFIG_ICON_SEARCH) . "
				</td>
				</tr>\n
			";
			$msgids .= $mesg['id'] . '|';
			$rowcounter++;
		}
		$msgids = substr($msgids, 0, (strlen($msgids) - 1)); 
	} else {
		$searchresults .= "<tr><td colspan='5' align='center'>" . WEB_LANG_NO_DATA . "</td></tr>";
	}

	if ($rs) {
		echo "<div id='send' STYLE='width:100%;'>";
		WebFormOpen();
		WebTableOpen(WEB_LANG_DELIVER_ALL, "80%");
		echo "
			<tr>
				<td width='100' class='mytablesubheader' nowrap>" . ARCHIVE_LANG_TO . "</td> 
				<td>
                  <input type='text' name='to' value='" . $_POST['to'] . "' style='width:300px'>
                  <input type='hidden' name='msgids' value='$msgids' />
                </td>
			</tr>
			<tr>
				<td width='100' class='mytablesubheader'>&#160;</td> 
				<td>" . 
					WebButton("Send", LOCALE_LANG_SEND, WEBCONFIG_ICON_EMAIL) .
					WebButtonCancel("Cancel", Array('type' => 'button', 'onclick' => 'hidesend()')) . "
					<input type='hidden' name='Search' value='1' />
					<input type='hidden' name='NavSubmit' value='1' />
					<input type='hidden' name='db' value='" . $_REQUEST['db'] . "' />
					<input type='hidden' name='max' value='" . $_REQUEST['max'] . "' />
					<input type='hidden' name='logical' value='" . $_REQUEST['logical'] . "' />" .
                    $extrahtml . "
				</td>
			</tr>
		";
		WebTableClose("80%");
		WebFormClose();
		echo "</div>";

	}

	WebFormOpen();
	WebTableOpen(WEB_LANG_FILTER_RULES, "80%");
	echo "
	  <tr>
        <td colspan='2' align='left'>
          <input type='radio' name='db' value='" . Archive::DB_NAME_CURRENT . "' $db_current />" .
		  WEB_LANG_SEARCH_CURRENT . "
          <input type='radio' name='db' value='" . Archive::DB_NAME_SEARCH . "' $db_search />" .
		  WEB_LANG_SEARCH_ARCHIVE . "
        </td>
        <td colspan='2'>" .
		  WEB_LANG_MAX_RESULTS . ":&#160;<select name='max' style='width: 50px'>$max_options</select>
        </td>
      </tr>
      <tr>
        <td colspan='4' align='left'>
          <input type='radio' name='logical' value='AND' $logical_and />" . WEB_LANG_MATCH_ALL . "
          <input type='radio' name='logical' value='OR' $logical_or />" . WEB_LANG_MATCH_ANY . "
        </td>
      </tr>" .
      $searchform
	;
	WebTableClose("80%");
	WebFormClose();
	echo '<br />';
	WebFormOpen();
	WebTableOpen(WEB_LANG_SEARCH_RESULTS, "100%");
	echo "
		<tr class='mytableheader'>
		  <td width='25%'>" . ARCHIVE_LANG_SUBJECT . "</td>
		  <td>" . ARCHIVE_LANG_FROM . "</td>
		  <td>" . ARCHIVE_LANG_TO . "</td>
		  <td>" . ARCHIVE_LANG_DATE . "</td>
		  <td>" . LOCALE_LANG_ACTION . "</td>
		</tr>" .
		$searchresults
	;
	WebTableClose("100%");
	echo "	<input type='hidden' name='logical' value='" . $_REQUEST['logical'] . "' />
			<input type='hidden' name='max' value='" . $_REQUEST['max'] . "' />
			<input type='hidden' name='offset' value='" . $_REQUEST['offset'] . "' />
			<input type='hidden' name='db' value='" . $_REQUEST['db'] . "' />" . 
			$extrahtml
	;
	for ($rule = 0; $rule < 5; $rule++) {
		echo "
			<input type='hidden' name='field[$rule]' value='" . $_REQUEST['field'][$rule] . "' />
			<input type='hidden' name='criteria[$rule]' value='" . $_REQUEST['criteria'][$rule] . "' />
			<input type='hidden' name='regex[$rule]' value='" . $_REQUEST['regex'][$rule] . "' />
		";
	}
	WebFormClose();

	for ($rule = 1; $rule < 5; $rule++) {
		if (! $_REQUEST['regex'][$rule])
			echo "<script type=\"text/javascript\" language=\"JavaScript\">hide('c$rule');</script>";
	}

	if (!isset($_POST['Deliver']))
		echo "<script type=\"text/javascript\" language=\"JavaScript\">hidesend();</script>";
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayMessage($message)
//
///////////////////////////////////////////////////////////////////////////////

function DisplayMessage($mesg) 
{
	global $archive;
 
	$body = "";
	$attachments = "";

	foreach ($mesg['attachments'] as $attachment) {
		if (isset($attachment['filename'])) {
			$attachments .= $attachment['filename'] . " (" . $archive->GetFormattedBytes($attachment['size'], 1) . ")<br />";
		} else {
			# Don't deal with inline graphics for now
			if ($attachment['encoding'] == 'base64')
				continue;
			# If html, just get data between body tags
			if (eregi("html", $attachment['type'])) {
				$input = new DOMDocument();
				$output = new DOMDocument();

				try {
					# Load/parse HTML in to a DOM document
					$input->loadHTML(utf8_decode($attachment['data']));

					# Find any <style>...</style> elements and append them to output document
					$styles = $input->getElementsByTagName('style');
					for($i = 0; $i < $styles->length; $i++)
						$output->appendChild($output->importNode($styles->item($i), true));

					# Delete any <style>...</style> elements from input document
					while($style = $input->getElementsByTagName('style')->item(0))
						$style->parentNode->removeChild($style);

					# Delete any <script>...</script> elements from input document
					while($script = $input->getElementsByTagName('script')->item(0))
						$script->parentNode->removeChild($script);

					# Append <body>...</body> child elements to output document
					$nodes = $input->getElementsByTagName('body')->item(0)->childNodes;
					for($i = 0; $i < $nodes->length; $i++)
						$output->appendChild($output->importNode($nodes->item($i), true));

					# Save clean output HTML
					$body = '<pre>' . $output->saveHTML() . '</pre>';
				} catch(Exception $e) {
					WebDialogWarning($e->GetMessage());
				}
			} else {
				$body .= "<pre>" . $attachment['data'] . "</pre>";
			}
		}
	}

	if ($body == '')
		$body = '<pre>' . $mesg['body'] . '</pre>'; 

	if (! $attachments)
		$attachments = "----";

	$search_string = "&logical=" . $_REQUEST['logical'] . "&db=" . $_REQUEST['db'] .
				     "&max=" . $_REQUEST['max'] . "&offset=" . $_REQUEST['offset'];
	$hidden = '';
	for ($rule = 0; $rule < 5; $rule++) {
		$hidden .= "<input type='hidden' name='field[$rule]' value='" . $_REQUEST['field'][$rule] . "' /";
		$hidden .= "<input type='hidden' name='criteria[$rule]' value='" . $_REQUEST['criteria'][$rule] . "' /";
		$hidden .= "<input type='hidden' name='regex[$rule]' value='" . $_REQUEST['regex'][$rule] . "' /";
		$search_string .= "&field[$rule]=" . $_REQUEST['field'][$rule] .
			"&criteria[$rule]=" . $_REQUEST['criteria'][$rule] .
			"&regex[$rule]=" . $_REQUEST['regex'][$rule];
	}

	$navigate = "
		<table width='60'>
			<tr>
				<td colspan='2' align='center'><a href='" . $_SERVER['PHP_SELF'] . "'>" . LOCALE_LANG_BACK . "</a></td>
			</tr>
			<tr>
				<td align='left'><a href='" . $_SERVER['PHP_SELF'] . "?View[" . ($mesg['id'] - 1) . "]$search_string'>" . WEBCONFIG_ICON_PREVIOUS . "</a></td>
				<td align='right'><a href='" . $_SERVER['PHP_SELF'] . "?View[" . ($mesg['id'] + 1) . "]$search_string'>" . WEBCONFIG_ICON_NEXT . "</a></td>
			</tr>
			<tr>
				<td colspan='2' align='center'><a href='" . $_SERVER['PHP_SELF'] . "?Search=1&NavSubmit=1$search_string'>" . WEBCONFIG_ICON_SEARCH . "</a></td>
			</tr>
		</table>
	";

	echo "<div id='send'>";
	WebFormOpen();
	WebTableOpen(WEB_LANG_RESEND, "440");
	echo "
		<tr>
			<td width='100' class='mytablesubheader' nowrap>" . ARCHIVE_LANG_TO . "</td> 
			<td><input type='text' name='to' value='" . htmlspecialchars($mesg['recipient']) . "' style='width:300px'></td>
		</tr>
		<tr>
			<td width='100' class='mytablesubheader'>&#160;</td> 
			<td>" . 
				WebButton("Send", LOCALE_LANG_SEND, WEBCONFIG_ICON_EMAIL) . "
				<input type='hidden' name='View[" . $mesg['id']. "]' value='" . $mesg['id'] . "' />
				<input type='hidden' name='db' value='" . $_REQUEST['db'] . "' />
				<input type='hidden' name='max' value='" . $_REQUEST['max'] . "' />
				<input type='hidden' name='logical' value='" . $_REQUEST['logical'] . "' />
				$extrahtml" . WebButtonCancel("Ignore") . "$hidden
			</td>
		</tr>
	";
	WebTableClose("440");
	WebFormClose();
	echo "</div>";

	WebTableOpen(WEB_LANG_SEARCH_RESULTS, "100%");
	echo "
		<tr>
			<td width='20%' class='mytablesubheader'>" . ARCHIVE_LANG_TO . "</td> 
			<td>" . htmlspecialchars($mesg['recipient']) . "</td>
			<td width='15%' rowspan='4'>" . $navigate . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader'>" . ARCHIVE_LANG_FROM . "</td> 
			<td>" . htmlspecialchars($mesg['sender']) . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader'>" . ARCHIVE_LANG_CC . "</td> 
			<td>" . htmlspecialchars($mesg['cc']) . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader'>" . ARCHIVE_LANG_SUBJECT . "</td> 
			<td>" . htmlspecialchars($mesg['subject']) . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader'>" . ARCHIVE_LANG_DATE . "</td> 
			<td>" . date("Y-m-d H:i:s", $mesg['sent']) . "</td>
		</tr>
		<tr id='header'>
			<td valign='top' class='mytablesubheader' nowrap>" . WEB_LANG_ORIGINAL_HEADER . "</td> 
			<td colspan='2'><a href=\"javascript:noheader()\">" . WEBCONFIG_ICON_DELETE . "</a>
			<br />" . ereg_replace("\n", "<br />", htmlspecialchars($mesg['header'])) . "</td>
		</tr>
		<tr id='noheader'>
			<td valign='top' class='mytablesubheader' nowrap>" . WEB_LANG_ORIGINAL_HEADER . "</td> 
			<td colspan='2'><a href=\"javascript:header()\">" . WEBCONFIG_ICON_ADD . "</a></td>
		</tr>
		<tr>
			<td valign='top' class='mytablesubheader' nowrap>" . WEB_LANG_ATTACHMENTS . "</td> 
			<td colspan='2'>$attachments</td>
		</tr>
		<tr>
			<td valign='top' class='mytablesubheader' nowrap>" . LOCALE_LANG_ACTION . "</td> 
			<td colspan='2'><a href=\"javascript:showsend()\">" . WEBCONFIG_ICON_EMAIL . "&#160;" . WEB_LANG_RESEND . "</a></td>
		</tr>
		<tr>
			<td style='border-top: 1px solid #BBBBBB;' colspan='3'>$body</td>
		</tr>
	";
	WebTableClose("100%");

	echo "<script type=\"text/javascript\" language=\"JavaScript\">noheader();</script>";
	echo "<script type=\"text/javascript\" language=\"JavaScript\">hidesend();</script>";
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayReset()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayReset()
{
	WebFormOpen();
	WebTableOpen(LOCALE_LANG_CONFIRM, "450");
	echo "
	  <tr>
		<td align='center'>
		  <input type='hidden' name='Reset' value='1'>
		  <p>" . WEBCONFIG_ICON_WARNING . " " . WEB_LANG_CONFIRM_RESET . "</p>" .
		  WebButtonReset("Confirm") . " " . WebButtonCancel("Cancel") . "
		</td>
	  </tr>
	";
	WebTableClose("450");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// SanityCheck()
//
///////////////////////////////////////////////////////////////////////////////

function SanityCheck()
{
	// Make sure IMAP server is running
	global $archive;

	try {
		$cyrus = new Cyrus();
		$isrunning = $cyrus->GetRunningState();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

	if (! $isrunning && $archive->CheckImapStatus()) {
		WebDialogWarning(
			WEB_LANG_IMAP_SERVER_NOT_RUNNING . " " .  
			WebUrlJump("/admin/mail-pop-imap.php", LOCALE_LANG_CONFIGURE)
		);
	}
}

// vim: ts=4
?>
