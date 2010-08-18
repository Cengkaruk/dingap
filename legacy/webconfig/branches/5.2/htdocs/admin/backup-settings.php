<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2004-2006 Point Clark Networks.
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
require_once("../../api/BackupRestore.class.php");
require_once("../../api/File.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();

$backup = new BackupRestore();

try {
	if (isset($_POST['Download'])) {
		WebDownload(BackupRestore::PATH_ARCHIVE . "/" . key($_POST['Download']));
	} else if (isset($_POST['Backup'])) {
		$archive = $backup->Backup();
		if ($archive) {
			$backup->Purge();
			WebDownload($archive);
		}
	}
} catch (Exception $e) {
	WebDialogWarning($e->GetMessage());
}

WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-backup-settings.png", WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////
//
// The "Upload" action is a bit unusual -- it requires both an action
// (moving the HTTP post temporary file) and a confirmation box (if move was
// successful).
//
///////////////////////////////////////////////////////////////////////////////

$show_upload_confirm = false;
$show_restore_complete = false;

try {
	if (isset($_POST['UploadConfirm'])) {
		$backup->RestoreByUpload($_POST['archive']);
		$show_restore_complete = true;
	} else if (isset($_POST['RestoreConfirm'])) {
		$backup->RestoreByArchive($_POST['archive']);
		$show_restore_complete = true;
	} else if (isset($_POST['Cancel'])) {
		// Purge any temporary files on a cancel
		$backup->Purge();
	} else if (isset($_POST['Upload'])) {
		// Move the uploaded file to the backuprestore upload directory
		if (isset($_FILES["archive"]) && !$_FILES["archive"]["error"] && is_uploaded_file($_FILES["archive"]["tmp_name"])) {
			try {
				$file = new File($_FILES["archive"]["tmp_name"]);
				$file->MoveTo(BackupRestore::PATH_UPLOAD . "/" . $_FILES["archive"]["name"]);
				$show_upload_confirm = true;
			} catch (Exception $e) {
				WebDialogWarning($e->GetMessage());
			}
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

if (isset($_POST['Restore'])) {
	DisplayConfirm("Restore", key($_POST['Restore']));
} else if ($show_upload_confirm) {
	DisplayConfirm("Upload", $_FILES["archive"]["name"]);
} else {
	if ($show_restore_complete) 
		WebDialogInfo(WEB_LANG_RESTORE_RUNNING);
	DisplayBackup();
	DisplayRestore();
	DisplayArchives();
}

WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayBackup()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayBackup()
{
	WebFormOpen();
	WebDialogInfo(WEB_LANG_BACKUP_HELP . " " . WebButton("Backup", WEB_LANG_BACKUP_NOW, WEBCONFIG_ICON_UPDATE));
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayRestore()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayRestore()
{
	$options['type'] = "file";

	WebFormOpen();
	WebTableOpen(WEB_LANG_RESTORE_TITLE, "100%");
	echo "
	  <tr>
	    <td align='center'>
	      <input type='file' name='archive' size='40' /> &#160;
	      <input type='hidden' name='MAX_FILE_SIZE' value='25600' />" .
		   WebButton("Upload", WEB_LANG_UPLOAD, WEBCONFIG_ICON_UPDATE) . "
	    </td>
	  </tr>
	";
	WebTableClose("100%");
	WebFormClose();
}


///////////////////////////////////////////////////////////////////////////////
//
// DisplayArchives()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayArchives()
{
	global $backup;

	try {
		$backups = $backup->GetArchiveList();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	$archivelist = "";

	foreach($backups as $archive) {
	    $restore = WebButton("Restore[" . $archive . "]", LOCALE_LANG_RESTORE, WEBCONFIG_ICON_TOGGLE);
	    $download = WebButton("Download[" . $archive . "]", LOCALE_LANG_DOWNLOAD, WEBCONFIG_ICON_UPDATE);

		$archivelist .= "
		  <tr>
		    <td>$archive</td>
		    <td>$download $restore</td>
		  </tr>
		";
	}

	if (! $archivelist) 
		return;

	WebFormOpen();
	WebTableOpen(WEB_LANG_ARCHIVES_TITLE, "100%");
	WebTableHeader(WEB_LANG_ARCHIVE . "|");
	echo $archivelist;
	WebTableClose("100%");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayConfirm()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayConfirm($action, $archive)
{
	$confirm = $action . "Confirm";

	WebFormOpen();
	WebTableOpen(LOCALE_LANG_CONFIRM, "400");
	echo "
      <tr>
        <td align='center'>
          <br />
          <p>" . WEBCONFIG_ICON_WARNING . " " . WEB_LANG_RESTORE_CONFIRM . "?</p>
		  <p>$archive</p>
          <p>" . WebButtonContinue($confirm) . " " . WebButtonCancel("Cancel") . "<br />
          <input type='hidden' name='archive' value='$archive' /></p>
        </td>
      </tr>
    ";
	WebTableClose("400");
	WebFormClose();
}

// vim: ts=4
?>
