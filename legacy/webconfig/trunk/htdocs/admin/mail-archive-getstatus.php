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
// This script is called via AJAX and generates Javascript code.
//
///////////////////////////////////////////////////////////////////////////////

require_once('../../gui/Webconfig.inc.php');
require_once('../../scripts/archive.inc.php');
require_once(GlobalGetLanguageTemplate('mail-archive.php'));

define('ARCHIVE_IDLE', 0);
define('ARCHIVE_RUNNING', 1);
define('ARCHIVE_INTERRUPT', 2);

$status = ARCHIVE_IDLE;
$progress = 0;

// Unserialize the scanner state file (if it exists)
if (file_exists(ARCHIVE_STATE)) {
	if (($fh = @fopen(ARCHIVE_STATE, 'r'))) {
		UnserializeState($fh, $archive_state);
		fclose($fh);
	}
}

// Determin the scanner's state
if (file_exists(ARCHIVE_LOCKFILE)) {
	if (($fh = @fopen(ARCHIVE_LOCKFILE, 'r'))) {
		list($pid) = fscanf($fh, '%d');

		if (!file_exists("/proc/$pid"))
			$status = ARCHIVE_INTERRUPT;
		else
			$status = ARCHIVE_RUNNING;

		fclose($fh);
	}
}


// Calculate the completed percentage if possible
if($archive_state['count'] != 0 || $archive_state['total'] != 0)
	$progress = sprintf('%.02f', $archive_state['count'] * 100 / $archive_state['total']);

if ($progress == 0)
	echo("try {\nClearProgress();\n} catch (ignore) {}\n");
else
	echo("try {\nUpdateProgress($progress);\n} catch (ignore) {}\n");

$human_status = WEB_LANG_STATUS_IDLE;

switch ($status)
{
case ARCHIVE_RUNNING:
	if ($progress == 0)
		$human_status = WEB_LANG_STATUS_INITIALIZING;
	else if ($progress == 100)
		$human_status = WEB_LANG_STATUS_COMPLETE;
	else
		$human_status = WEB_LANG_STATUS_IN_PROGRESS;
?>
try {
	document.getElementById('archive_row_progress').style.display = 'table-row';
} catch (e) {
  try {
    document.getElementById('archive_row_progress').style.display = 'block';
  } catch (ignore) {}
}
<?
	break;

case ARCHIVE_INTERRUPT:
	$human_status = WEB_LANG_STATUS_INTERRUPTED;
?>
try {
  document.getElementById('archive_row_progress').style.display = 'table-row';
} catch (e) {
  try {
    document.getElementById('archive_row_progress').style.display = 'block';
  } catch (ignore) {}
}
<?
	break;

default:
?>
try {
  document.getElementById('archive_row_progress').style.display = 'none';
} catch (ignore) {}
<?
}

if ($status == ARCHIVE_RUNNING && $progress == 100) {
# Force stats to 0 on main page
?>
try {
  document.getElementById('archive_stat_size').firstChild.nodeValue = '0';
  document.getElementById('archive_stat_total').firstChild.nodeValue = '0';
  document.getElementById('archive_stat_attach').firstChild.nodeValue = '0';
} catch (ignore) {}
<?
}

?>
try {
  document.getElementById('archive_state').firstChild.nodeValue = '<? echo $human_status; ?>';
} catch (ignore) {}
<?
// vim: ts=4
?>
