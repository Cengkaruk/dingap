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

// Maximum loop devices
define('DMCRYPT_MAX_LOOP_DEV', 8);

// Key size in bits
define('DMCRYPT_KEY_SIZE', 256);

// Encrypted volume configuration file
define('DMCRYPT_CONFIG', '/etc/dmcrypt.conf');

// Directory which contains raw encrypted files
define('DMCRYPT_DATA_DIR', '%s/.dmcrypt');

// Mount point directory
define('DMCRYPT_MOUNT_POINT', '/mnt/dmcrypt/%s');

// Device mapper location
define('DMCRYPT_DEVMAPPER', '/dev/mapper/%s');

// Loop device location
define('DMCRYPT_DEVLOOP', '/dev/loop%d');

// DM-CRYPT PHP state file
define('DMCRYPT_STATE', '/tmp/.dmcrypt.state');

// DM-CRYPT script lock file
define('DMCRYPT_LOCKFILE', '/var/run/dmcrypt.pid');

// DD command
define('DMCRYPT_BIN_DD', '/bin/dd if=/dev/zero of=' . DMCRYPT_DATA_DIR . '/%s bs=1k count=%d');

// MKDIR command
define('DMCRYPT_BIN_MKDIR', '/bin/mkdir -p %s');

// RMDIR command
define('DMCRYPT_BIN_RMDIR', '/bin/rmdir %s');

// RM command
define('DMCRYPT_BIN_RM', '/bin/rm -f %s');

// Loop device status command
define('DMCRYPT_BIN_LOOP_STATUS', '/sbin/losetup ' . DMCRYPT_DEVLOOP);

// Attach new loop device command
define('DMCRYPT_BIN_LOOP_ATTACH', '/sbin/losetup ' . DMCRYPT_DEVLOOP . ' ' . DMCRYPT_DATA_DIR . '/%s');

// Detach loop device command
define('DMCRYPT_BIN_LOOP_DETACH', '/sbin/losetup -d ' . DMCRYPT_DEVLOOP);

// Create/start encrypted volume
define('DMCRYPT_BIN_CRYPTSETUP_CREATE', '/sbin/cryptsetup -c aes -s ' . DMCRYPT_KEY_SIZE . ' -h aes-cbc-essiv:sha256 -d %s create %s %s');

// Remove/stop encrypted volume
define('DMCRYPT_BIN_CRYPTSETUP_REMOVE', '/sbin/cryptsetup remove %s');

// Retrieve encrypted volume status
define('DMCRYPT_BIN_CRYPTSETUP_STATUS', '/sbin/cryptsetup status %s');

// Make filesystem (format encrypted volume)
define('DMCRYPT_BIN_MKFS', '/sbin/mkfs.ext3 -q ' . DMCRYPT_DEVMAPPER . ' -L "%s"');

// Verify filesystem
define('DMCRYPT_BIN_FSCK', '/sbin/fsck.ext3 -y ' . DMCRYPT_DEVMAPPER);

// Mount encrypted volume
define('DMCRYPT_BIN_MOUNT', '/bin/mount ' . DMCRYPT_DEVMAPPER . ' %s');

// Unmount encrypted volume
define('DMCRYPT_BIN_UNMOUNT', '/bin/umount %s');

// Mounted filesystems
define('DMCRYPT_ETC_MTAB', '/etc/mtab');

// Global state array
$dmcrypt_state = array();

function ResetState(&$state)
{
	$state['status'] = '-';
	$state['operation'] = '-';
	$state['volume'] = '-';
}

ResetState($dmcrypt_state);

// Debug mode?
if (!isset($debug)) $debug = false;

// Debug execute prompt
function ShellExecute($command, $mode = 'r')
{
	global $debug;

	if ($debug) {
		echo("Execute [$command]? (CTRL-C to abort)");
		$fh = fopen('/dev/stdin', 'r');
		if (!$fh) exit("Error opening /dev/stdin!\n");
		fgets($fh); fclose($fh);
	}

	return popen($command, $mode);
}

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

// Lock state file, read, and unserialize status
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

// Is there a dmcrypt operation running?
function IsRunning()
{
	if (!file_exists(DMCRYPT_LOCKFILE)) return false;

	$fh = @fopen(DMCRYPT_LOCKFILE, 'r');
	list($pid) = fscanf($fh, '%d');
	fclose($fh);

	// Perhaps this is a stale lock file?
	if (!file_exists("/proc/$pid")) {
		// Yes, the process 'appears' to no longer be running...
		return false;
	}

	return true;
}

// Load required kernel modules
function LoadModules()
{
	$modules = array('aes-i586', 'dm-crypt', 'loop');

	foreach ($modules as $module) {
		$ph = popen("/sbin/modprobe $module", 'r');
		if (!$ph || pclose($ph) != 0) return -1;
	}

	return 0;
}

// Is device mounted?
function IsDeviceMounted($volume)
{
	$mounted = false;
	$fh = fopen(DMCRYPT_ETC_MTAB, 'r');
	if (!$fh) return false;

	while (!feof($fh)) {
		$parts = explode(' ', chop(fgets($fh)));
		if (count($parts) < 2) continue;
		if ($parts[1] != $volume['device']) continue;
		$mounted = true;
		break;
	}

	fclose($fh);
	return $mounted;
}

// Is encrypted volume mounted?
function IsEncryptedVolumeMounted($volume)
{
	$ph = ShellExecute(sprintf('egrep -q "^' . DMCRYPT_DEVMAPPER . ' " ' . DMCRYPT_ETC_MTAB, $volume['name']));
	if (!$ph) return false;
	return (pclose($ph) == 0) ? true : false;
}

// Load configuration
function LoadConfiguration()
{
	$config = array();
	$config['loaded'] = false;
	$config['volume'] = array();

	clearstatcache();
	if (!file_exists(DMCRYPT_CONFIG)) return $config;

	$fh = fopen(DMCRYPT_CONFIG, 'r');
	if (!$fh) return $config;

	while (!feof($fh)) {
		$buffer = chop(fgets($fh, 4096));
		if (preg_match('/^\s*#/', $buffer) || substr_count($buffer, '|') != 2) continue;
		list($name, $mount_point, $device) = explode('|', $buffer);
		if (!strlen($name)) continue;
		$volume = array();
		$volume['name'] = $name;
		$volume['mount_point'] = $mount_point;
		$volume['device'] = $device;
		$config['volume'][] = $volume;
	}

	$config['loaded'] = true;
	fclose($fh);
	return $config;
}

// Create blank volume file
function CreateBlankVolume($volume, $size_kb)
{
	$ph = ShellExecute(sprintf(DMCRYPT_BIN_MKDIR, sprintf(DMCRYPT_DATA_DIR, $volume['device'])));
	if (!$ph) return -1;
	pclose($ph);

	$ph = ShellExecute(sprintf(DMCRYPT_BIN_DD, $volume['device'], $volume['name'], $size_kb));
	if (!$ph) return -1;
	return pclose($ph);
}

// Return next available loop device
function GetNextLoopDevice()
{
	for ($id = 0; $id < DMCRYPT_MAX_LOOP_DEV; $id++) {
		$ph = ShellExecute(sprintf(DMCRYPT_BIN_LOOP_STATUS, $id));
		if (!$ph) break;
		if (pclose($ph) != 0) return $id;
	}

	return -1;
}

// Attach new loop device
function AttachLoopDevice($volume, $id)
{
	$ph = ShellExecute(sprintf(DMCRYPT_BIN_LOOP_ATTACH, $id, $volume['device'], $volume['name']));
	if (!$ph) return -1;
	return pclose($ph);
}

// Detach loop device
function DetachLoopDevice($id)
{
	$ph = ShellExecute(sprintf(DMCRYPT_BIN_LOOP_DETACH, $id));
	if (!$ph) return -1;
	return pclose($ph);
}

// Return status of encrypted volume
function GetEncryptedVolumeStatus($volume)
{
	global $debug;

	$status = array('active' => false);
	$ph = ShellExecute(sprintf(DMCRYPT_BIN_CRYPTSETUP_STATUS, $volume['name']));
	if (!$ph) return $status;
	$buffer = chop(fgets($ph, 4096));
	if (!eregi('^.* is (.*)[\.:]$', $buffer, $parts)) {
		pclose($ph);
		return $status;
	}
	if ($parts[1] == 'active') $status['active'] = true;
	while (!feof($ph)) {
		$buffer = chop(fgets($ph, 4096));
		if (!eregi('^[[:space:]]*([a-z]*):[[:space:]]*(.*)$', $buffer, $parts)) break;
		$status[$parts[1]] = $parts[2];
	}
	if (isset($status['device'])) {
		if (sscanf($status['device'], DMCRYPT_DEVLOOP, $status['id']) != 1) $status['id'] = -1;
	}
	else $status['id'] = -1;
	ksort($status);
	pclose($ph);
	if ($debug) print_r($status);
	return $status;
}

// Create encrypted volume
function CreateEncryptedVolume($volume, $id, $key_file)
{
	$device = ($id != -1) ?
		sprintf(DMCRYPT_DEVLOOP, $id) : $volume['device'];

	$ph = ShellExecute(sprintf(DMCRYPT_BIN_CRYPTSETUP_CREATE, $key_file, $volume['name'], $device));
	if (!$ph) {
		unlink($key_file);
		return -1;
	}

	$rc = pclose($ph);
	unlink($key_file);
	return $rc;
}

// Remove encrypted volume
function RemoveEncryptedVolume($volume)
{
	$ph = ShellExecute(sprintf(DMCRYPT_BIN_CRYPTSETUP_REMOVE, $volume['name']));
	if (!$ph) return -1;
	return pclose($ph);
}

// Format encrypted volume
function FormatEncryptedVolume($volume)
{
	$ph = ShellExecute(sprintf(DMCRYPT_BIN_MKFS, $volume['name'], $volume['name']));
	if (!$ph) return -1;
	return pclose($ph);
}

// Verify encrypted volume
function VerifyEncryptedVolume($volume)
{
	$ph = ShellExecute(sprintf(DMCRYPT_BIN_FSCK, $volume['name']));
	if (!$ph) return -1;
	return pclose($ph);
}

// Mount encrypted volume
function MountEncryptedVolume($volume)
{
	$ph = ShellExecute(sprintf(DMCRYPT_BIN_MKDIR, $volume['mount_point']));
	if (!$ph) return -1;
	pclose($ph);

	$ph = ShellExecute(sprintf(DMCRYPT_BIN_MOUNT, $volume['name'], $volume['mount_point']));
	if (!$ph) return -1;
	return pclose($ph);
}

// Unmount encrypted volume
function UnmountEncryptedVolume($volume)
{
	$ph = ShellExecute(sprintf(DMCRYPT_BIN_UNMOUNT, $volume['mount_point']));
	if (!$ph) return -1;
	return pclose($ph);
}

// Delete encrypted volume
function DeleteEncryptedVolume($volume)
{
	if (IsDeviceMounted($volume)) {
		$ph = ShellExecute(sprintf(DMCRYPT_BIN_RM,
			sprintf(sprintf(DMCRYPT_DATA_DIR, $volume['device']) . '/%s', $volume['name'])));
		if (!$ph) return -1;
		$rc = pclose($ph);
		if ($rc != 0) return $rc;
	}

	$ph = ShellExecute(sprintf(DMCRYPT_BIN_RMDIR, $volume['mount_point']));
	if (!$ph) return -1;
	return pclose($ph);
}

// vi: ts=4
?>
