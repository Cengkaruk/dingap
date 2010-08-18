<?php
///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2007 Point Clark Networks
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

// Configuration settings.  This file is managed by the webconfig archive class
define('ARCHIVE_CONFIG', '/etc/archive.conf');

// Current working directory for archival.
define('ARCHIVE_CURRENT', '/var/archive/current');

// Snapshot working directory for archival.
define('ARCHIVE_SEARCH', '/var/archive/search');

// Links working directory for archival.
define('ARCHIVE_LINKS', '/var/archive/links');

// Flexshare directory for archival.
define('ARCHIVE_FLEXSHARE', '/var/flexshare/shares/mail-archives');

// Filename of instance (PID) lock file
define('ARCHIVE_LOCKFILE', '/var/run/archive.pid');

// Location of mysqldump 
define('ARCHIVE_MYSQLDUMP', '/usr/share/system-mysql/usr/bin/mysqldump');

// Location of mysql 
define('ARCHIVE_MYSQL', '/usr/share/system-mysql/usr/bin/mysql');

// Location of ln 
define('ARCHIVE_LN', '/bin/ln');

// Location of tar 
define('ARCHIVE_TAR', '/bin/tar');

// Location of openssl
define('ARCHIVE_OPENSSL', '/usr/bin/openssl');

// Location of scanner state/status file
define('ARCHIVE_STATE', '/tmp/.archive.state');

// Global state array
$archive_state = array();

function ResetState(&$state)
{
	$state['count'] = 0;
	$state['total'] = 0;
	$state['timestamp'] = 0;
}

ResetState($archive_state);

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
	if (($state = unserialize(fgets($fh, $stats['size'] + 1))) === false) {
		flock($fh, LOCK_UN);
		return false;
	}
	if (flock($fh, LOCK_UN) === false) return false;
	return true;
}

// Is there a archive running?
function IsArchiveRunning()
{
	if (!file_exists(ARCHIVE_LOCKFILE)) return false;

	$fh = @fopen(ARCHIVE_LOCKFILE, 'r');
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
