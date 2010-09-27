#!/usr/webconfig/bin/php -q
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

require_once(dirname($_SERVER['argv'][0]) . '/dmcrypt.inc.php');

$dmcrypt_fh = null;

// Fatal error
function fatal_error($code, $error, $filename = null, $line = 0)
{
	global $dmcrypt_fh;
	global $dmcrypt_state;

	$buffer = '';
	if ($filename != null) $buffer = sprintf('%s:%d: ', basename($filename), $line);
	$buffer .= $error;
	$dmcrypt_state['status'] = $buffer;
	echo("$buffer\n");
	if ($dmcrypt_fh != null) {
		SerializeState($dmcrypt_fh, $dmcrypt_state);
		fclose($dmcrypt_fh);
	}
	exit($code);
}

// Register custom error handler
set_error_handler('fatal_error');

// Debug mode?
$ph = popen('/usr/bin/tty', 'r');
list($tty) = chop(fgets($ph, 4096));
pclose($ph);
if ($tty != 'not a tty') $debug = true;

// Must be run as root
if (posix_getuid() != 0) {
	fatal_error(1, 'Must be run as superuser (root)');
}

// Ensure we are the only instance running
if (file_exists(DMCRYPT_LOCKFILE)) {
	$fh = @fopen(DMCRYPT_LOCKFILE, 'r');
	list($pid) = fscanf($fh, '%d');

	// Perhaps this is a stale lock file?
	if (!file_exists("/proc/$pid")) {
		// Yes, the process 'appears' to no longer be running...
		@unlink(DMCRYPT_LOCKFILE);
	} else {
		// Only one instance can run at a time
		fatal_error(1, 'A dm-crypt operation is already in progress');
	}

	fclose($fh);
} else {
	// Grab the lock ASAP...
	touch(DMCRYPT_LOCKFILE);
}

// Register a shutdown function so we can do some clean-up
function dmcrypt_shutdown()
{
	@unlink(DMCRYPT_LOCKFILE);
}

register_shutdown_function('dmcrypt_shutdown');

// Lock state file, write serialized state
function serialize_state()
{
	global $dmcrypt_fh;
	global $dmcrypt_state;

	if ($dmcrypt_fh == null)
		fatal_error(1, 'State serialization failure');

	if (SerializeState($dmcrypt_fh, $dmcrypt_state) == false)
		fatal_error(1, 'State serialization failure');
}

// Save our PID to the lock file
$fh = @fopen(DMCRYPT_LOCKFILE, 'w');

fprintf($fh, "%d\n", posix_getpid());
fclose($fh);

// Open state file
$dmcrypt_fh = @fopen(DMCRYPT_STATE, 'a+');
chown(DMCRYPT_STATE, 'webconfig');
chgrp(DMCRYPT_STATE, 'webconfig');
chmod(DMCRYPT_STATE, 0750);

// Load modules
$dmcrypt_state['status'] = 'LoadModules';
serialize_state();

if (LoadModules() != 0)
	fatal_error(1, 'LoadModules failed');

// Load configuration
$config = LoadConfiguration();

if ($debug) print_r($config);

if (!$config['loaded'])
	fatal_error(1, 'No configuration found/loaded');

// Validate operation and encrypted volume name
$operation = array();
$operation[] = 'create';
$operation[] = 'delete';
$operation[] = 'mount';
$operation[] = 'unmount';

if (!isset($_SERVER['argv'][1]))
	fatal_error(1, 'Required argument missing: operation');

if (!in_array($_SERVER['argv'][1], $operation))
	fatal_error(1, 'Invalid argument: operation');

$dmcrypt_state['operation'] = $_SERVER['argv'][1];
serialize_state();

if (!isset($_SERVER['argv'][2]))
	fatal_error(1, 'Required argument missing: name');

$dmcrypt_state['volume'] = $_SERVER['argv'][2];
serialize_state();

$exists = false;
$volume = array();
foreach ($config['volume'] as $volume) {
	if ($volume['name'] != $_SERVER['argv'][2]) continue;
	$exists = true; break;
}

if (!$exists) fatal_error(1, 'Volume does not exist');

switch ($_SERVER['argv'][1]) {
case 'create':
	// XXX: Create a new encrypted volume

	if (!isset($_SERVER['argv'][3]))
		fatal_error(1, 'Required argument missing: size');
	if (!isset($_SERVER['argv'][4]))
		fatal_error(1, 'Required argument missing: key_file');

	clearstatcache();
	if (!file_exists($_SERVER['argv'][4]))
		fatal_error(1, 'Key file not found');

	$id = -1;
	if (IsDeviceMounted($volume)) {
		$dmcrypt_state['status'] = 'CreateBlankVolume';
		serialize_state();

		$rc = CreateBlankVolume($volume, $_SERVER['argv'][3]);
		if ($rc != 0) fatal_error($rc, 'CreateBlankVolume failed');

		$dmcrypt_state['status'] = 'GetNextLoopDevice';
		serialize_state();

		$rc = GetNextLoopDevice();
		if ($rc == -1) fatal_error($rc, 'GetNextLoopDevice failed');
		$id = $rc;

		$dmcrypt_state['status'] = 'AtttachLoopDevice';
		serialize_state();

		$rc = AttachLoopDevice($volume, $id);
		if ($rc != 0) fatal_error($rc, 'AttachLoopDevice failed');
	}

	$dmcrypt_state['status'] = 'CreateEncryptedVolume';
	serialize_state();

	$rc = CreateEncryptedVolume($volume, $id, $_SERVER['argv'][4]);
	if ($rc != 0) fatal_error($rc, 'CreateEncryptedVolume failed');

	$dmcrypt_state['status'] = 'FormatEncryptedVolume';
	serialize_state();

	$rc = FormatEncryptedVolume($volume);
	if ($rc != 0) fatal_error($rc, 'FormatEncryptedVolume failed ' . $volume);

	$dmcrypt_state['status'] = 'RemoveEncryptedVolume';
	serialize_state();

	$rc = RemoveEncryptedVolume($volume);
	if ($rc != 0) fatal_error($rc, 'RemoveEncryptedVolume failed');

	if ($id != -1) {
		$dmcrypt_state['status'] = 'DetachLoopDevice';
		serialize_state();

		$rc = DetachLoopDevice($id);
		if ($rc != 0) fatal_error($rc, 'DetachLoopDevice failed');
	}

	$dmcrypt_state['status'] = 'Success';
	serialize_state();

	break;

case 'delete':
	// XXX: Delete an existing encrypted volume, unmount and detach first if required

	if (IsEncryptedVolumeMounted($volume)) {
		$dmcrypt_state['status'] = 'UnmountEncryptedVolume';
		serialize_state();

		$rc = UnmountEncryptedVolume($volume);
		if ($rc != 0) fatal_error($rc, 'UnmountEncryptedVolume failed');
	}

	$status = GetEncryptedVolumeStatus($volume);

	if ($status['active']) {
		$dmcrypt_state['status'] = 'RemoveEncryptedVolume';
		serialize_state();

		$rc = RemoveEncryptedVolume($volume);
		if ($rc != 0) fatal_error($rc, 'RemoveEncryptedVolume failed');

		$dmcrypt_state['status'] = 'DetachLoopDevice';
		serialize_state();

		if ($status['id'] != -1) {
			$rc = DetachLoopDevice($status['id']);
			if ($rc != 0) fatal_error($rc, 'DetachLoopDevice failed');
		}
	}

	$dmcrypt_state['status'] = 'DeleteEncryptedVolume';
	serialize_state();

	$rc = DeleteEncryptedVolume($volume);
	if ($rc != 0) fatal_error($rc, 'DeleteEncryptedVolume failed');

	$dmcrypt_state['status'] = 'Success';
	serialize_state();

	break;

case 'mount':
	// XXX: Mount a previously configured encrypted volume

	if (!isset($_SERVER['argv'][3]))
		fatal_error(1, 'Required argument missing: key_file');

	clearstatcache();
	if (!file_exists($_SERVER['argv'][3]))
		fatal_error(1, 'Key file not found');

	$status = GetEncryptedVolumeStatus($volume);

	if ($status['active']) {
		if (IsEncryptedVolumeMounted($volume))
			fatal_error(1, 'Volume already mounted');
	}

	$id = -1;
	if (IsDeviceMounted($volume)) {
		$dmcrypt_state['status'] = 'GetNextLoopDevice';
		serialize_state();

		$rc = GetNextLoopDevice();
		if ($rc == -1) fatal_error($rc, 'GetNextLoopDevice failed');
		$id = $rc;

		$dmcrypt_state['status'] = 'AttachLoopDevice';
		serialize_state();

		$rc = AttachLoopDevice($volume, $id);
		if ($rc != 0) fatal_error($rc, 'AttachLoopDevice failed');
	}

	$dmcrypt_state['status'] = 'CreateEncryptedVolume';
	serialize_state();

	$rc = CreateEncryptedVolume($volume, $id, $_SERVER['argv'][3]);
	if ($rc != 0) fatal_error($rc, 'CreateEncryptedVolume failed');

	$dmcrypt_state['status'] = 'VerifyEncryptedVolume';
	serialize_state();

	$rc = VerifyEncryptedVolume($volume);
	if ($rc != 0) {
		$rc = RemoveEncryptedVolume($volume);
		if ($rc != 0) fatal_error($rc, 'RemoveEncryptedVolume failed');

		if ($id != -1) {
			$dmcrypt_state['status'] = 'DetachLoopDevice';
			serialize_state();

			$rc = DetachLoopDevice($id);
			if ($rc != 0) fatal_error($rc, 'DetachLoopDevice failed');
		}
		
		fatal_error($rc, 'VerifyEncryptedVolume failed');
	}

	$dmcrypt_state['status'] = 'MountEncryptedVolume';
	serialize_state();

	$rc = MountEncryptedVolume($volume);
	if ($rc != 0) {
		$rc = RemoveEncryptedVolume($volume);
		if ($rc != 0) fatal_error($rc, 'RemoveEncryptedVolume failed');

		if ($id != -1) {
			$dmcrypt_state['status'] = 'DetachLoopDevice';
			serialize_state();

			$rc = DetachLoopDevice($id);
			if ($rc != 0) fatal_error($rc, 'DetachLoopDevice failed');
		}
		
		fatal_error($rc, 'MountEncryptedVolume failed');
	}

	$dmcrypt_state['status'] = 'Success';
	serialize_state();

	break;

case 'unmount':
	// XXX: Umount an previously mounted encrypted volume

	if (!IsEncryptedVolumeMounted($volume))
		fatal_error(1, 'Volume not mounted');

	$rc = UnmountEncryptedVolume($volume);
	if ($rc != 0) fatal_error($rc, 'UnmountEncryptedVolume failed');

	$status = GetEncryptedVolumeStatus($volume);

	if ($status['active']) {
		$dmcrypt_state['status'] = 'RemoveEncryptedVolume';
		serialize_state();

		$rc = RemoveEncryptedVolume($volume);
		if ($rc != 0) fatal_error($rc, 'RemoveEncryptedVolume failed');

		if ($status['id'] != -1) {
			$dmcrypt_state['status'] = 'DetachLoopDevice';
			serialize_state();

			$rc = DetachLoopDevice($status['id']);
			if ($rc != 0) fatal_error($rc, 'DetachLoopDevice failed');
		}
	}

	$dmcrypt_state['status'] = 'Success';
	serialize_state();

	break;
}

// vi: ts=4 syntax=php
?>
