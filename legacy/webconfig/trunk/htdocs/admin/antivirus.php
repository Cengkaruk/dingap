<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2007-2009 Point Clark Networks.
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
require_once("../../api/ClamAv.class.php");
require_once("../../api/Freshclam.class.php");
require_once("../../api/AntimalwareUpdates.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Subscription information
//
///////////////////////////////////////////////////////////////////////////////

// TODO: implement this better
require_once("clearcenter-status.inc.php");
$header = "<script type='text/javascript' src='/admin/clearcenter-status.js.php?service=" . AntimalwareUpdates::CONSTANT_NAME ."'></script>\n";

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE, "default", $header);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-antimalware.png", WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$clamav = new ClamAv();
$freshclam = new Freshclam();

try {
	if (isset($_POST['UpdateConfig'])) {
        $freshclam->SetChecksPerDay($_POST['checks']);
		$clamav->SetArchiveBlockEncrypted((bool)$_POST['block_encrypted']);
		$clamav->SetMaxFiles($_POST['max_files']);
		$clamav->SetMaxFileSize($_POST['max_file_size']);
		$clamav->SetMaxRecursion($_POST['max_recursion']);
		$clamav->Reset();
	}
} catch (Exception $e) {
	WebDialogWarning($e->GetMessage());
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

WebServiceStatus(AntimalwareUpdates::CONSTANT_NAME, "ClearSDN Antimalware Updates");
DisplayAntimalwareConfiguration();
WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayAntimalwareConfiguration()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayAntimalwareConfiguration()
{
	global $clamav;
	global $freshclam;

	try {
		$checks = $freshclam->GetChecksPerDay();
		$max_files = $clamav->GetMaxFiles();
		$max_file_size = $clamav->GetMaxFileSize();
		$max_recursion = $clamav->GetMaxRecursion();
		$block_encrypted = $clamav->GetArchiveBlockEncrypted();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	$checks_options = array(
		'1' => WEB_LANG_DAILY,
		'2' => WEB_LANG_TWICE_A_DAY,
		'12' => WEB_LANG_EVERY_TWO_HOURS,
		'24' => WEB_LANG_EVERY_HOUR,
	);

	$max_files_options[0] = LOCALE_LANG_UNLIMITED;
	$max_files_options[100] = 100;
	$max_files_options[200] = 200;
	$max_files_options[300] = 300;
	$max_files_options[400] = 400;
	$max_files_options[500] = 500;
	$max_files_options[600] = 600;
	$max_files_options[700] = 700;
	$max_files_options[800] = 800;
	$max_files_options[900] = 900;
	$max_files_options[1000] = 1000;
	$max_files_options[2000] = 2000;
	$max_files_options[3000] = 3000;
	$max_files_options[4000] = 4000;
	$max_files_options[5000] = 5000;
	$max_files_options[10000] = 10000;

	if (isset($max_files_options[ClamAv::DEFAULT_ARCHIVE_MAX_FILES]))
		$max_files_options[ClamAv::DEFAULT_ARCHIVE_MAX_FILES] .= " - " . LOCALE_LANG_DEFAULT;
	else
		$max_files_options[ClamAv::DEFAULT_ARCHIVE_MAX_FILES] = 
			ClamAv::DEFAULT_ARCHIVE_MAX_FILES . " - " . LOCALE_LANG_DEFAULT;

	$max_file_size_options[0] = LOCALE_LANG_UNLIMITED;
	$max_file_size_options[2] = 2 . " " . LOCALE_LANG_MEGABYTES;
	$max_file_size_options[5] = 5 . " " . LOCALE_LANG_MEGABYTES;
	$max_file_size_options[10] = 10 . " " . LOCALE_LANG_MEGABYTES;
	$max_file_size_options[20] = 20 . " " . LOCALE_LANG_MEGABYTES;
	$max_file_size_options[30] = 30 . " " . LOCALE_LANG_MEGABYTES;
	$max_file_size_options[40] = 40 . " " . LOCALE_LANG_MEGABYTES;
	$max_file_size_options[50] = 50 . " " . LOCALE_LANG_MEGABYTES;
	$max_file_size_options[100] = 100 . " " . LOCALE_LANG_MEGABYTES;

	if (isset($max_file_size_options[ClamAv::DEFAULT_ARCHIVE_MAX_FILE_SIZE]))
		$max_file_size_options[ClamAv::DEFAULT_ARCHIVE_MAX_FILE_SIZE] .= " - " . LOCALE_LANG_DEFAULT;
	else
		$max_file_size_options[ClamAv::DEFAULT_ARCHIVE_MAX_FILE_SIZE] = 
			ClamAv::DEFAULT_ARCHIVE_MAX_FILE_SIZE . " " . LOCALE_LANG_MEGABYTES . " - " . LOCALE_LANG_DEFAULT;


	$max_recursion_options[0] = LOCALE_LANG_UNLIMITED;
	$max_recursion_options[1] = 1;
	$max_recursion_options[2] = 2;
	$max_recursion_options[3] = 3;
	$max_recursion_options[4] = 4;
	$max_recursion_options[5] = 5;
	$max_recursion_options[6] = 6;
	$max_recursion_options[7] = 7;
	$max_recursion_options[8] = 8;
	$max_recursion_options[9] = 9;
	$max_recursion_options[10] = 10;
	$max_recursion_options[15] = 15;
	$max_recursion_options[20] = 20;

	if (isset($max_recursion_options[ClamAv::DEFAULT_ARCHIVE_MAX_RECURSION]))
		$max_recursion_options[ClamAv::DEFAULT_ARCHIVE_MAX_RECURSION] .= " - " . LOCALE_LANG_DEFAULT;
	else
		$max_recursion_options[ClamAv::DEFAULT_ARCHIVE_MAX_RECURSION] = 
			ClamAv::DEFAULT_ARCHIVE_MAX_RECURSION . " - " . LOCALE_LANG_DEFAULT;

	// HTML
	//-----

	WebFormOpen();
	WebTableOpen(WEB_LANG_ANTIVIRUS_POLICIES, "500");
	echo "
		<tr>
			<td width='200' class='mytablesubheader' nowrap>" . CLAMAV_LANG_BLOCK_ENCRYPTED_ARCHIVES_POLICY . "</td>
			<td>" . WebDropDownEnabledDisabled("block_encrypted", $block_encrypted) . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . CLAMAV_LANG_MAXIMUM_FILES . "</td>
			<td>" . WebDropDownHash("max_files", $max_files, $max_files_options) . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . CLAMAV_LANG_MAXIMUM_FILE_SIZE . "</td>
			<td>" . WebDropDownHash("max_file_size", $max_file_size, $max_file_size_options) . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . CLAMAV_LANG_MAXIMUM_RECURSION . "</td>
			<td>" . WebDropDownHash("max_recursion", $max_recursion, $max_recursion_options) . "</td>
		</tr>
		<tr>
			<td width='200' class='mytablesubheader' nowrap>" . FRESHCLAM_LANG_UPDATE_INTERVAL . "</td>
			<td>" . WebDropDownHash('checks', $checks, $checks_options) . " </td>
		</tr>
		<tr>
			<td class='mytablesubheader'>&#160; </td>
			<td>" . WebButtonUpdate("UpdateConfig") . "</td>
		</tr>
	";
	WebTableClose("500");
	WebFormClose();
}

// vim: syntax=php ts=4
?>
