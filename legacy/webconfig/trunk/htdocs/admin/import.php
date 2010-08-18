<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2004-2009 Point Clark Networks.
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
require_once("../../api/ClearDirectory.class.php");
require_once("../../api/File.class.php");
require_once("../../api/Ldap.class.php");
require_once("../../api/UserImport.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();

try {
	if (isset($_POST['DownloadTemplate'])) {
		WebDownload(UserImport::PATH_TEMPLATE . $_POST['type']);
	} else if (isset($_POST['Export'])) {
		$userimport = new UserImport();
		$filename = $userimport->Export();
		WebDownload(COMMON_TEMP_DIR . "/" . $filename);
	}
} catch (Exception $e) {
	WebDialogWarning($e->GetMessage());
}

WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-import.png", WEB_LANG_PAGE_INTRO);
WebCheckUserDatabase();

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

// FIXME: are these necessary references?
$ldap = new Ldap();
$directory = new ClearDirectory();
$userimport = new UserImport();

$found_upload = false;
if (isset($_POST['Upload'])) {
	// Move the uploaded file to the upload directory
	if (isset($_FILES["csvimport"]) && !$_FILES["csvimport"]["error"] && is_uploaded_file($_FILES["csvimport"]["tmp_name"])) {
		try {
			$file = new File($_FILES["csvimport"]["tmp_name"]);
			$file->MoveTo(COMMON_TEMP_DIR . "/userimport-" . session_id() . ".csv");
			$found_upload = true;
		} catch (Exception $e) {
			WebDialogWarning($e->GetMessage());
		}
	}
}

if (isset($_POST['Import'])) {
	try {
		$userimport->SetCsvFile(COMMON_TEMP_DIR . "/userimport-" . session_id() . ".csv");
		$userimport->Import();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
} else if (isset($_POST['Cancel'])) {
	try {
		$file = new File(COMMON_TEMP_DIR . "/userimport-" . session_id() . ".csv", true);
		if ($file->Exists())
			$file->Delete();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

if (isset($_POST['Import']) || isset($_POST['ViewLog']) || $userimport->ImportInProgress()) {
	DisplayImportStatus();
} else if ($found_upload) {
	DisplayImportForm();
} else {
	try {
		$file = new File(COMMON_TEMP_DIR . "/userimport-" . session_id() . ".csv", true);
		if ($file->Exists())
			DisplayImportForm();
		else
			DisplayUploadForm();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
}

WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayImportStatus()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayImportStatus()
{
	WebFormOpen();
	WebTableOpen(WEB_LANG_IMPORT_USERS);
	echo "
		<tr>
		  <td class='mytablesubheader' nowrap width='150'>" . LOCALE_LANG_STATUS . "</td>
		  <td id='import_state'>" . WEB_LANG_STATUS_IDLE . "</td>
		</tr>
		<tr id='import_row_progress'>
		  <td class='mytablesubheader'>" . WEB_LANG_PERCENT_COMPLETE . "</td>
		  <td><div id='import_progress_percent' class='progressbarpercent'>0%</div></td>
		</tr>
		<tr>
		  <td class='mytableheader' colspan='2'>" . WEB_LANG_LOG . "</td>
		</tr>
		<tr>
		  <td colspan='2' id='status'><pre>&#160;</pre></td>
		</tr>
	";

	WebTableClose();
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayUploadForm()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayUploadForm()
{
	WebFormOpen();
	WebTableOpen(WEB_LANG_IMPORT_USERS);
	echo "
      <tr>
        <td class='mytablesubheader' width='20%'>" . WEB_LANG_FILE . "</td>
        <td width='80%'>
	      <input type='file' name='csvimport' size='40' />
        </td>
      </tr>
      <tr>
        <td class='mytablesubheader'>&#160;</td>
        <td>" . WebButton("Upload", WEB_LANG_UPLOAD, WEBCONFIG_ICON_UPDATE) . "</td>
      </tr>
    ";
	WebTableClose();
	WebFormClose();

	DisplayTemplateDownload();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayImportForm()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayImportForm()
{
	global $userimport;

	try {
		$userimport->SetCsvFile(COMMON_TEMP_DIR . "/userimport-" . session_id() . ".csv");
		$noofrecords = $userimport->GetNumberOfRecords();
		$size = $userimport->GetSize();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

	WebFormOpen();
	WebTableOpen(WEB_LANG_IMPORT_USERS);
	echo "
      <tr>
        <td width='40%' class='mytablesubheader'>" . WEB_LANG_NO_OF_RECORDS . "</td>
        <td width='60%'>$noofrecords</td>
      </tr>
      <tr>
        <td class='mytablesubheader'>" . WEB_LANG_FILE_SIZE . "</td>
        <td>$size</td>
      </tr>
      <tr>
        <td class='mytablesubheader'>&#160;</td>
        <td>" .
		  WebButton("Import", WEB_LANG_IMPORT_USERS, WEBCONFIG_ICON_UPDATE) . 
		  WebButtonCancel("Cancel") . "
        </td>
      </tr>
    ";
	WebTableClose();
	WebFormClose();

}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayTemplateDownload()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayTemplateDownload()
{
	global $userimport;
	$type_options = $userimport->GetTemplateTypes();
	WebFormOpen();
	echo "<div style='padding:20px 0px 20px 0px; text-align: center'>";
	echo WebDropDownHash("type", UserImport::FILE_ODS_TEMPLATE, $type_options);
    echo WebButton("DownloadTemplate", WEB_LANG_DOWNLOAD_TEMPLATE, WEBCONFIG_ICON_UPDATE);
	echo "</div>";
	echo "<div style='padding:0px 0px 20px 0px; text-align: center'>";
	echo WebButton("ViewLog", WEB_LANG_VIEW_LAST_IMPORT_LOG, WEBCONFIG_ICON_VIEW); 
	echo WebButton("Export", WEB_LANG_EXPORT, WEBCONFIG_ICON_DOWNLOAD); 
	echo "</div>";
	WebFormClose();
}

// vim: ts=4
?>
