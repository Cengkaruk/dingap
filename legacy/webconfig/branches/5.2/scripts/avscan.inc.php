<?php
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

// List of directories to scan for viruses.  This file is managed by the
// webconfig antivirus class
define('AVSCAN_CONFIG', '/etc/avscan.conf');

// Filename of instance (PID) lock file
define('AVSCAN_LOCKFILE', '/var/run/avscan.pid');

// Location of ClamAV scanner
define('AVSCAN_SCANNER', '/usr/bin/clamscan');

// Location of scanner state/status file
define('AVSCAN_STATE', '/tmp/.avscan.state');

// Locating of quarantine directory
define('AVSCAN_QUARANTINE', '/var/lib/quarantine');

// Global state array
$av_state = array();

function ResetState(&$state)
{
	$state['rc'] = 0;
	$state['dir'] = '-';
	$state['filename'] = '-';
	$state['result'] = null;
	$state['count'] = 0;
	$state['total'] = 0;
	$state['error'] = array();
	$state['virus'] = array();
	$state['timestamp'] = 0;
}

ResetState($av_state);

// Lock state file, write serialized state
function SerializeState($fh, $state)
{
	if (flock($fh, LOCK_EX) === false) return false;
	if (ftruncate($fh, 0) === false) {
		flock($fh, LOCK_UN);
		return false;
	}
	if (fseek($fh, SEEK_SET, 0) == -1) {
		flock($fh, LOCK_UN);
		return false;
	}
	if (fwrite($fh, serialize($state)) === false) {
		flock($fh, LOCK_UN);
		return false;
	}
	fflush($fh);
	if (flock($fh, LOCK_UN) === false) return false;
	return true;
}

// Lock state file, read and unserialized status
function UnserializeState($fh, &$state)
{
	clearstatcache();
	$stats = fstat($fh);
	
	if ($stats['size'] == 0) {
		ResetState($state);
		return true;
	}

	if (flock($fh, LOCK_EX) === false) return false;
	if (fseek($fh, SEEK_SET, 0) == -1) {
		flock($fh, LOCK_UN);
		return false;
	}
	if (($contents = stream_get_contents($fh)) === false) {
		flock($fh, LOCK_UN);
		return false;
	}
	if (($state = unserialize($contents)) === false) {
		flock($fh, LOCK_UN);
		return false;
	}
	if (flock($fh, LOCK_UN) === false) return false;
	return true;
}

// Is there a scan running?
function IsScanRunning()
{
	if (!file_exists(AVSCAN_LOCKFILE)) return false;

	$fh = @fopen(AVSCAN_LOCKFILE, 'r');
	list($pid) = fscanf($fh, '%d');
	fclose($fh);

	// Perhaps this is a stale lock file?
	if (!file_exists("/proc/$pid")) {
		// Yes, the process 'appears' to no longer be running...
		return false;
	}

	return true;
}

// vi: ts=4
?>
