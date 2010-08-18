<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2009 Point Clark Networks
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
// the userimport page.
//
///////////////////////////////////////////////////////////////////////////////

require_once('../../gui/Webconfig.inc.php');
require_once('../../scripts/userimport.inc.php');
require_once(GlobalGetLanguageTemplate('import.php'));

define('IMPORT_IDLE', 0);
define('IMPORT_RUNNING', 1);
define('IMPORT_INTERRUPT', 2);

$status = IMPORT_IDLE;
$progress = 0;

// Unserialize the import state file (if it exists)
if (file_exists(IMPORT_STATE)) {
	if (($fh = @fopen(IMPORT_STATE, 'r'))) {
		UnserializeState($fh, $import_state);
		fclose($fh);
	}
}

// Determine the import state
if (file_exists(IMPORT_LOCKFILE)) {
	if (($fh = @fopen(IMPORT_LOCKFILE, 'r'))) {
		list($pid) = fscanf($fh, '%d');

		if (!file_exists("/proc/$pid"))
			$status = IMPORT_INTERRUPT;
		else
			$status = IMPORT_RUNNING;

		fclose($fh);
	}
}


// Calculate the completed percentage if possible
if($import_state['count'] != 0 && $import_state['total'] != 0)
	$progress = sprintf('%.00f', $import_state['count'] * 100 / $import_state['total']);

if ($progress == 0)
	echo("try {\nClearProgress();\n} catch (ignore) {}\n");
else
	echo("try {\nUpdateProgress($progress);\n} catch (ignore) {}\n");

if (isset($import_state['errors']))
	$errors = $import_state['errors'];
else
	$errors = Array();

if (isset($import_state['logs']))
	$logs = $import_state['logs'];
else
	$logs = Array();

if ($progress == 100) {
	if (count($errors) == 0)
		$human_status = WEB_LANG_STATUS_COMPLETE;
	else
		$human_status = WEB_LANG_STATUS_COMPLETE_WITH_ERRORS;
} else {
	$human_status = WEB_LANG_STATUS_IDLE;
}


switch ($status)
{
case IMPORT_RUNNING:
	if ($progress == 100) {
		if (count($errors) == 0)
			$human_status = WEB_LANG_STATUS_COMPLETE;
		else
			$human_status = WEB_LANG_STATUS_COMPLETE_WITH_ERRORS;
	//} else if ($progress == -1) {
	//	$human_status = WEB_LANG_STATUS_INIT . '...';
	} else {
		$human_status = WEB_LANG_STATUS_IN_PROGRESS . '...';
	}
?>
try {
	document.getElementById('import_row_progress').style.display = 'table-row';
} catch (e) {
  try {
    document.getElementById('import_row_progress').style.display = 'block';
  } catch (ignore) {}
}
<?
	break;

case IMPORT_INTERRUPT:
	$human_status = WEB_LANG_STATUS_INTERRUPTED;
?>
try {
  document.getElementById('import_row_progress').style.display = 'table-row';
} catch (e) {
  try {
    document.getElementById('import_row_progress').style.display = 'block';
  } catch (ignore) {}
}
<?
	break;

default:
}

?>
try {
  document.getElementById('import_state').firstChild.nodeValue = '<? echo $human_status; ?>';
} catch (ignore) {}
try {
  var display_logs = document.getElementById('status');
} catch (ignore) {}
<?
	$logsanderrors = $errors + $logs;
	ksort($logsanderrors);
	$tag = '';
	foreach ($logsanderrors as $entry)
		$tag .= "$entry<br />";
	echo "if (document.getElementById('status')) display_logs.innerHTML = '<pre>$tag</pre>';";

// vim: ts=4
?>
