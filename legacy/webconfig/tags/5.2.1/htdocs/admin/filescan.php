<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2006-2007 Point Clark Networks
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
require_once('../../api/FileScan.class.php');
require_once('../../api/Freshclam.class.php');
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-filescan.png", WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$filescan = new FileScan();

try {
	if (isset($_POST['UpdateConfig'])) {

		$directories = $_POST['Directory'];
		$dirs_config = $filescan->GetDirectories();
		$dirs_preset = $filescan->GetDirectoryPresets();

		foreach ($dirs_preset as $dir => $label) {
			$hash = md5($dir);

			if (isset($directories[$hash]) && !in_array($dir, $dirs_config))
				$filescan->AddDirectory($dir);
			else if(!isset($directories[$hash]) && in_array($dir, $dirs_config))
				$filescan->RemoveDirectory($dir);
		}

		if ($filescan->ScanScheduleExists()) 
			$filescan->RemoveScanSchedule();

		if ($_POST['hour'] != "disabled") {
			$filescan->SetScanSchedule('0', $_POST['hour'], '*', '*', '*');
			$freshclam = new Freshclam();
			$freshclam->SetBootState(true);
			$freshclam->SetRunningState(true);
		}
	} else if (isset($_POST['Start'])) {
		// TODO: this GUI element could be done better in 5.0
		$dir_config = $filescan->GetDirectories();
		if (empty($dir_config))
			WebDialogWarning(WEB_LANG_SELECT_DIRECTORIES_TO_SCAN);
		else
			$filescan->StartScan();
	} else if (isset($_POST['Stop'])) {
		$filescan->StopScan();
	} else if (isset($_POST['QuarantineVirus'])) {
		$filescan->QuarantineVirus(key($_POST['QuarantineVirus']));
	} else if (isset($_POST['DeleteVirus'])) {
		$filescan->DeleteVirus(key($_POST['DeleteVirus']));
	} else if (isset($_POST['RestoreQuarantinedVirus'])) {
		$filescan->RestoreQuarantinedVirus(key($_POST['RestoreQuarantinedVirus']));
	} else if (isset($_POST['DeleteQuarantinedVirus'])) {
		$filescan->DeleteQuarantinedVirus(key($_POST['DeleteQuarantinedVirus']));
	}
} catch (Exception $e) {
	WebDialogWarning($e->GetMessage());
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

DisplayConfig();
DisplayScanProgress();
DisplayViruses();
DisplayQuarantine();
WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

// TODO: This is a version of WebDropDownHash that uses strcasecmp which fixes
// expressions like '*' == 0 evaluating true.  Also, this version will break
// after a match is found rather than incorrectly adding 'selected' to multiple
// drop down items.
function MyDropDownHash($variable, $value, $hash, $width = 0)
{
	$found = false;
	$options = "";

	foreach ($hash as $actual => $show) {
		if (strcasecmp($value, $actual) == 0 && !$found) {
			$options .= "<option value='$actual' selected>$show</option>\n";
			$found = true;
		} else {
			$options .= "<option value='$actual'>$show</option>\n";
		}
	}

	if (!$found)
		$options = "<option value='$value' selected>$value</option>\n" . $options;

	if ($width) {
		$width = $width . "px";
		return "<select style='width: $width' name='$variable'>$options</select>\n";
	} else {
		return "<select name='$variable'>$options</select>";
	}
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayScanProgress
//
// Displays the current status of the ClamAV virus scanner
//
///////////////////////////////////////////////////////////////////////////////

function DisplayScanProgress()
{
	// TODO: reimplement the progress bar

	WebFormOpen();
	WebTableOpen(WEB_LANG_SCAN_PROGRESS, "100%");
	echo "
		<tr>
			<td width='25%' class='mytablesubheader' nowrap>" . WEB_LANG_STATUS . "</td>
			<td id='av_state'>" . WEB_LANG_STATUS_IDLE . "</td>
		</tr>
		<tr id='av_row_file' style='display: none;'>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_DIRECTORY . "</td>
			<td id='av_dir'>-</td>
		</tr>
		<tr id='av_row_progress' style='display: none;'>
			<td class='mytablesubheader'>&nbsp;</td>
			<td><div id='av_progress_bar' style='width: 50%;' class='progressbarbg'><div id='av_progress_percent' align='left' class='progressbarpercent'>0%</div></div></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap id='av_label_last_run'>" . WEB_LANG_LAST_RUN . "</td>
			<td id='av_last_run'>" . WEB_LANG_NEVER_RUN . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>&nbsp;</td>
			<td>" .
				WebButton("Start", LOCALE_LANG_START, WEBCONFIG_ICON_TOGGLE) . 
				WebButton("Stop", LOCALE_LANG_CANCEL, WEBCONFIG_ICON_CANCEL) . "
			</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_ERRORS . "</td>
			<td id='av_errors'>0</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_VIRUSES_FOUND . "</td>
			<td id='av_viruses'>0</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_LAST_SCAN_RESULT . "</td>
			<td id='av_result'>" . WEB_LANG_NO_VIRUS_FOUND . "</td>
		</tr>
	";
	WebTableClose("100%");
	WebFormClose();

	echo "<span id='av_ready'></span>";
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayViruses
//
// Displays the found viruses table (if any).  Provides the user with the
// ability to delete or quarantine infected files.
//
///////////////////////////////////////////////////////////////////////////////

function DisplayViruses()
{
	WebFormOpen();
	WebTableOpen(WEB_LANG_SCAN_REPORT, "100%", 'av_report');
	WebTableHeader(WEB_LANG_FILE . '|' . WEB_LANG_DETECTED_VIRUS . '|');
	echo("<tr id='av_row_novirus'><td colspan='3' align='center'>" . WEB_LANG_NO_VIRUSES . "</td></tr>\n");
	WebTableClose("100%");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayQuarantine
//
// Displays quarantined viruses table (if any).  Provides the user with the
// ability to delete or restore infected files.
//
///////////////////////////////////////////////////////////////////////////////

function DisplayQuarantine()
{
	global $filescan;

	try {
		$viruses = $filescan->GetQuarantinedViruses();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;  
	}

	if (!count($viruses)) return;

	WebFormOpen();
	WebTableOpen(WEB_LANG_QUARANTINED_FILES, "100%");
	WebTableHeader(WEB_LANG_FILE . '|' . WEB_LANG_DETECTED_VIRUS . '|' . LOCALE_LANG_DATE . ' / ' . LOCALE_LANG_TIME . '|');

	foreach ($viruses as $hash => $virus) {
		echo '<tr><td>' . $virus['filename'] . '</td>';
		echo '<td>' . $virus['virus'] . '</td>';
		echo '<td>' . strftime('%D %T', $virus['timestamp']) . '</td>';
		echo '<td>' .  WebButton("RestoreQuarantinedVirus[$hash]", WEB_LANG_RESTORE, WEBCONFIG_ICON_TOGGLE);
		echo WebButtonDelete("DeleteQuarantinedVirus[$hash]") . '</td></tr>';
	}

	WebTableClose("100%");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayConfig
//
// Displays the configuration for the ClamAV file scanner.
//
///////////////////////////////////////////////////////////////////////////////

function DisplayConfig()
{
	global $filescan;

	try {
		list($hour, $day, $month) = $filescan->GetScanSchedule();
		$dir_config = $filescan->GetDirectories();
		$dir_preset = $filescan->GetDirectoryPresets();
		$schedule = $filescan->ScanScheduleExists();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		// return;  
	}

	$dir_html = null;

	$x = 0;

	foreach ($dir_preset as $dir => $label) {
		if ($x == 0) $dir_html .= '<tr>';

		$dir_html .= "<td width='33%' nowrap><input type='checkbox' name='Directory[";
		$dir_html .= md5($dir) . "]'";
		if (in_array($dir, $dir_config)) $dir_html .= ' checked';
		$dir_html .= ">$label</td>";

		if (++$x == 3) {
			$dir_html .= "</tr>\n";
			$x = 0;
		}
	}

	if ($dir_html == null) {
		$dir_html = "<tr><td align='center' colspan='3'>" . WEB_LANG_ERR_NO_PRESETS;
		$dir_html .= "</td></tr>\n";
	}

	// Populate daily scan dropdown
	$hours = array('disabled' => LOCALE_LANG_DISABLED);

	for ($i = 0; $i < 24; $i++) 
		$hours[$i] = sprintf('%02d:00', $i);

	if (! $schedule)
		$hour = "disabled";
	else if ($hour == "*") // version 4.1 could still have this set to *
		$hours['*'] = WEB_LANG_ALL;

	WebFormOpen();
	WebTableOpen(WEB_LANG_SCAN_CONFIG, '100%');
	echo "
		<tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_DAILY_SCAN . "</td>
			<td>" . MyDropDownHash('hour', $hour, $hours) . "</td>
		</tr>
		<tr>
			<td valign='top' class='mytablesubheader' nowrap>" . WEB_LANG_DIRECTORIES . "</td>
			<td>
				<table align='center' width='100%' cellpadding='3' cellspacing='0' class='mytablecheckboxes'>
					$dir_html
				</table>
			</td>
		</tr>
		<tr>
			<td class='mytablesubheader'>&nbsp; </td>
			<td>" . WebButtonUpdate('UpdateConfig') . "</td>
		</tr>
	";
	WebTableClose('100%');
	WebFormClose();
}

// vim: syntax=php ts=4
?>
