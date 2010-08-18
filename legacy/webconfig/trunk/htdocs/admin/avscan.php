<?
///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2006 Point Clark Networks
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

///////////////////////////////////////////////////////////////////////////////
//
// This script is called via AJAX and generates Javascript code which updates
// the antivirus.php page.
//
///////////////////////////////////////////////////////////////////////////////

require_once('../../gui/Webconfig.inc.php');
require_once('../../scripts/avscan.inc.php');
require_once(GlobalGetLanguageTemplate('filescan.php'));

WebAuthenticate();

define('AVSCAN_IDLE', 0);
define('AVSCAN_SCANNING', 1);
define('AVSCAN_INTERRUPT', 2);

$status = AVSCAN_IDLE;
$progress = 0;

// Unserialize the scanner state file (if it exists)
if (file_exists(AVSCAN_STATE)) {
	if (($fh = @fopen(AVSCAN_STATE, 'r'))) {
		UnserializeState($fh, $av_state);
		fclose($fh);
	}
}

// Set the timestamp if available
if($av_state['timestamp'] != 0)
	$last_run = strftime('%D %T', $av_state['timestamp']);
else $last_run = WEB_LANG_UNKNOWN;

// Determin the scanner's state
if (file_exists(AVSCAN_LOCKFILE)) {
	if (($fh = @fopen(AVSCAN_LOCKFILE, 'r'))) {
		list($pid) = fscanf($fh, '%d');

		if (!file_exists("/proc/$pid"))
			$status = AVSCAN_INTERRUPT;
		else
			$status = AVSCAN_SCANNING;

		fclose($fh);
	}
}

// Calculate the completed percentage if possible
if($av_state['count'] != 0 || $av_state['total'] != 0)
	$progress = sprintf('%.02f', $av_state['count'] * 100 / $av_state['total']);

// ClamAV error codes as per clamscan(1) man page.
// TODO: Perhaps all possible error strings should be localized?
switch ($av_state['rc']) {
case 0:
	$result = WEB_LANG_NO_VIRUS_FOUND;
	break;
case 1:
	$result = WEB_LANG_VIRUS_FOUND;
	break;
case 40:
	$result = 'Unknown option passed';
	break;
case 50:
	$result = 'Database initialization error';
	break;
case 52:
	$result = 'Not supported file type';
	break;
case 53:
	$result = 'Can\'t open directory';
	break;
case 54:
	$result = 'Can\'t open file';
	break;
case 55:
	$result = 'Error reading file';
	break;
case 56:
	$result = 'Can\'t stat input file / directory';
	break;
case 57:
	$result = 'Can\'t get absolute path name of current working directory';
	break;
case 58:
	$result = 'I/O error, please check your file system';
	break;
case 59:
	$result = 'Can\'t get information about current user from /etc/passwd';
	break;
case 60:
	$result = 'Can\'t get  information about user (clamav) from /etc/passwd';
	break;
case 61:
	$result = 'Can\'t fork';
	break;
case 62:
	$result = 'Can\'t initialize logger';
	break;
case 63:
	$result = 'Can\'t create temporary files/directories (check permissions)';
	break;
case 64:
	$result = 'Can\'t write to temporary directory (please specify another one)';
	break;
case 70:
	$result = 'Can\'t allocate and clear memory (calloc)';
	break;
case 71:
	$result = 'Can\'t allocate memory (malloc)';
	break;
default:
	$result = WEB_LANG_UNKNOWN;
}

$human_status = WEB_LANG_STATUS_IDLE;
$last_run_label = WEB_LANG_LAST_RUN;

switch ($status)
{
case AVSCAN_SCANNING:
	if ($progress == 0) $human_status = WEB_LANG_STATUS_INITIALIZING;
	else $human_status = WEB_LANG_STATUS_SCANNING;
	$last_run_label = WEB_LANG_STARTED;
?>
try {
	document.getElementById('av_row_progress').style.display = 'table-row';
	document.getElementById('av_row_file').style.display = 'table-row';
} catch (e) {
	document.getElementById('av_row_progress').style.display = 'block';
	document.getElementById('av_row_file').style.display = 'block';
}
<?
	break;

case AVSCAN_INTERRUPT:
	$human_status = WEB_LANG_STATUS_INTERRUPTED;
?>
try {
	document.getElementById('av_row_progress').style.display = 'table-row';
	document.getElementById('av_row_file').style.display = 'table-row';
} catch (e) {
	document.getElementById('av_row_progress').style.display = 'block';
	document.getElementById('av_row_file').style.display = 'block';
}
<?
	break;

default:
?>
document.getElementById('av_row_progress').style.display = 'none';
document.getElementById('av_row_file').style.display = 'none';
<?
}

if ($progress == 0)
	echo("ClearProgress();\n");
else
	echo("UpdateProgress($progress);\n");

if (count($av_state['error']) == 0)
	echo("document.getElementById('av_errors').style.color = '#000';\n");
else 
	echo("document.getElementById('av_errors').style.color = '#f00';\n");

if (count($av_state['virus']) == 0) {
	echo("document.getElementById('av_viruses').style.color = '#000';\n");
?>
try {
	document.getElementById('av_row_novirus').style.display = 'table-row';
} catch (e) {
	document.getElementById('av_row_novirus').style.display = 'block';
}
<?
} else {
	echo("document.getElementById('av_viruses').style.color = '#f00';\n");
	echo("document.getElementById('av_row_novirus').style.display = 'none';\n");
}

// Insert viruses
foreach($av_state['virus'] as $hash => $details) {
	$action = str_replace("\n", '', WebButton("QuarantineVirus[$hash]", WEB_LANG_QUARANTINE, WEBCONFIG_ICON_CANCEL)) .  ' ' . str_replace("\n", '', WebButtonDelete("DeleteVirus[$hash]"));
	echo("InsertVirus('" . $details['filename'] . "', '" . $details['virus'] . "', \"$action\");\n");
}
?>
if (document.getElementById('av_dir').firstChild.nodeValue != '<? echo $av_state['dir']; ?>') ClearProgress();
document.getElementById('av_state').firstChild.nodeValue = '<? echo $human_status; ?>';
document.getElementById('av_last_run').firstChild.nodeValue = '<? echo $last_run; ?>';
document.getElementById('av_dir').firstChild.nodeValue = '<? echo $av_state['dir']; ?>';
document.getElementById('av_errors').firstChild.nodeValue = '<? echo count($av_state['error']); ?>';
document.getElementById('av_viruses').firstChild.nodeValue = '<? echo count($av_state['virus']); ?>';
document.getElementById('av_result').firstChild.nodeValue = '<? echo $result; ?>';
document.getElementById('av_label_last_run').firstChild.nodeValue = '<? echo $last_run_label; ?>';

