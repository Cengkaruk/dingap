<?php
///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2007-2008 Point Clark Networks
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

require_once('script.inc.php');

class ControlSocketException extends WebconfigScriptException
{
	const CODE_CREATE = 1;
	const CODE_CONNECT= 2;
	const CODE_READ = 3;
	const CODE_WRITE = 4;
	const CODE_INVALID_RESOURCE = 5;
	const CODE_ALREADY_CONNECTED = 6;

	public function __construct($code)
	{
		parent::__construct('ControlSocketException', $code);
	}
}

class ProtocolException extends WebconfigScriptException
{
	const CODE_VERSION = 1;
	const CODE_TIMEZONE = 2;
	const CODE_UNEXPECTED = 3;
	const CODE_SYNTAX_ERROR = 4;
	const CODE_ERROR = 5;
	const CODE_HANGUP = 6;

	public function __construct($code, $reason = null)
	{
		if ($reason == null)
			parent::__construct('ProtocolException', $code);
		else
			parent::__construct($reason, $code);
	}
}

class ProcessException extends WebconfigScriptException
{
	const CODE_EXEC = 1;
	const CODE_SELECT = 2;
	const CODE_NONBLOCK = 3;
	const CODE_INVALID_RESOURCE = 4;

	public function __construct($code)
	{
		parent::__construct('ProcessException', $code);
	}
}

class ControlException extends WebconfigScriptException
{
	const CODE_OPEN = 1;
	const CODE_READ = 2;
	const CODE_UNLINK = 3;
	const CODE_DD = 4;
	const CODE_STAT = 5;

	public function __construct($code)
	{
		parent::__construct('ControlException', $code);
	}
}

class ServiceException extends WebconfigScriptException
{
	const CODE_MISSING_CONTROL_SOCKET = 1;
	const CODE_GET_LOOP_DEVICE = 2;
	const CODE_CRYPTSETUP_CREATE = 3;
	const CODE_CRYPTSETUP_REMOVE = 4;
	const CODE_RESIZE2FS = 5;
	const CODE_VOLUME_INTEGRITY = 6;
	const CODE_VOLUME_MOUNT = 7;
	const CODE_FSCK = 8;
	const CODE_LOOP_STATUS = 9;
	const CODE_LOOP_ATTACH = 10;
	const CODE_NO_FREE_LOOP_DEVICE = 11;
	const CODE_MOUNT = 12;
	const CODE_UNMOUNT = 13;
	const CODE_RSYNC = 14;
	const CODE_SYNC_FSB = 15;
	const CODE_MKFS = 16;
	const CODE_MODPROBE = 17;
	const CODE_MKRELEASE = 18;
	const CODE_MKDIR_MOUNT = 19;
	const CODE_CONFIG_MISSING = 20;
	const CODE_CONFIG_INVALID = 21;
	const CODE_CONFIG_NOFSKEY = 22;
	const CODE_CONFIG_NODES_MISSING = 23;
	const CODE_CONFIG_NODES_EMPTY = 24;
	const CODE_CONFIG_NODES_INVALID = 25;
	const CODE_CONFIG_INCLUDE_EXCLUDE_OPEN = 26;
	const CODE_MYSQL_DUMP = 27;
	const CODE_MYSQL_RESTORE = 28;
	const CODE_MKDIR_DUMP = 29;
	const CODE_RESTORE_TO_INVALID = 30;
	const CODE_ISCSI_INVALID_TARGET = 31;
	const CODE_ISCSI_DISCOVERY = 32;
	const CODE_ISCSI_TARGET_NOT_FOUND = 33;
	const CODE_ISCSI_SET_AUTH_FAILURE = 34;
	const CODE_ISCSI_LOGIN = 35;
	const CODE_ISCSI_SESSIONS = 36;
	const CODE_ISCSI_DEVICE_NOT_FOUND = 37;
	const CODE_MKDIR_SNAPSHOT = 38;
	const CODE_INVALID_SNAPSHOT = 39;
	const CODE_DELETE_SNAPSHOT = 40;
	const CODE_MAIL_REINDEX = 41;
	const CODE_MAIL_REBUILD = 42;
	const CODE_VOLUME_FULL = 43;

	private $exitcode = -1;

	public function __construct($code, $exitcode = -1)
	{
		$this->exitcode = $exitcode;
		parent::__construct('ServiceException', $code);
	}

	public function GetExitCode()
	{
		return $this->exitcode;
	}
}

// Snapshot retention class
class Retention
{
	const DAILY = 'daily';
	const WEEKLY = 'weekly';
	const MONTHLY = 'monthly';
	const YEARLY = 'yearly';

	const TV_DAY = 86400;

	private $tv_debug = null;
	private $subdir = array(
		self::DAILY, self::WEEKLY,
		self::MONTHLY, self::YEARLY
	);

	function __construct($tv_debug = null)
	{
		$this->tv_debug = $tv_debug;
	}

	private function GetTime()
	{
		if ($this->tv_debug != null) return $this->tv_debug;
		else return time();
	}

	private function ScanDirectory($path)
	{
		$dh = opendir($path);
		if (!is_resource($dh)) return null;
		$snapshots = array();
		while (($file = readdir($dh)) !== false) {
			if (!is_dir("$path/$file")) continue;
			if (!preg_match('/^[0-9]+$/', $file)) continue;
			$snapshots[] = $file;
		}
		closedir($dh);
		sort($snapshots, SORT_NUMERIC);
		return $snapshots;
	}

	public function ScanSnapshots($path)
	{
		clearstatcache();
		$snapshots = array('path' => $path);
		foreach($this->subdir as $dir)
			$snapshots[$dir] = $this->ScanDirectory("$path/$dir");
		$snapshots['legacy'] = $this->ScanDirectory($path);
		return $snapshots;
	}

	public function MakeDirectories($path)
	{
		clearstatcache();
		foreach ($this->subdir as $dir) {
			$dir = "$path/$dir";
			if (is_dir($dir)) continue;
			if (mkdir($dir) === false) return false;
		}
		return true;
	}

	public function PurgeSnapshots(&$snapshots, $policy)
	{
		if (!is_array($snapshots) || !is_array($policy)) return false;
		if (!array_key_exists('path', $snapshots)) return false;
		$purged = 0;
		foreach ($this->subdir as $dir) {
			if (!array_key_exists($dir, $snapshots) ||
				!array_key_exists($dir, $policy)) return false;
			$purge = count($snapshots[$dir]) - $policy[$dir];
			if ($purge <= 0) continue;
			for ($i = 0; $i < $purge; $i++) {
				$path = sprintf('%s/%s/%s', $snapshots['path'],
					$dir, array_shift($snapshots[$dir]));
				$ph = popen("rm -rf $path", 'r');
				if (!is_resource($ph)) return false;;
				while (!feof($ph)) $buffer = fgets($ph, 8192);
				if (pclose($ph) == 0) $purged++;
				unset($buffer);
			}
		}
		return $purged;
	}

	public function GetSnapshot($snapshots, $oldest = true, $type = null, $with_path = false)
	{
		$tv = false;
		if (!is_array($snapshots)) return false;
		if ($type == null) {
			$all = array();
			foreach ($this->subdir as $dir) {
				foreach ($snapshots[$dir] as $snapshot)
					$all[$snapshot] = $dir;
			}
			if (!count($all)) return false;
			ksort($all, SORT_NUMERIC);
			if ($oldest) {
				reset($all);
				$tv = key($all);
				$type = current($all);
			} else {
				end($all);
				$tv = key($all);
				$type = current($all);
			}
		}
		else if (!array_key_exists($type, $snapshots)) return false;
		else {
			if (!count($snapshots[$type])) return false;
			if ($oldest) $tv = array_shift($snapshots[$type]);
			else $tv = array_pop($snapshots[$type]);
		}

		if ($tv === false) return false;
		if (!$with_path) return $tv;
		return sprintf('%s/%s/%s',
			$snapshots['path'], $type, $tv);
	}

	public function PreparePlan(&$snapshots, $policy, $path)
	{
		$snapshots = $this->ScanSnapshots($path);
		$this->PurgeSnapshots($snapshots, $policy);

		$tv_now = $this->GetTime();
		$lt_now = localtime($tv_now, true);

		$plan = array();
		foreach ($this->subdir as $dir) $plan[$dir] = null;

		if ($policy[self::DAILY] != 0) {
			$plan[self::DAILY]['src'] = 0;
			$plan[self::DAILY]['dst'] = sprintf('%s/%s', self::DAILY, $tv_now);

			$snapshot = sprintf('%s/%s', $path, $plan[self::DAILY]['dst']);
			if (mkdir($snapshot) === false)
				throw new ServiceException(ServiceException::CODE_MKDIR_SNAPSHOT);

			$snapshots = $this->ScanSnapshots($path);
			$this->PurgeSnapshots($snapshots, $policy);

			$c = count($snapshots[self::DAILY]) - 1;
			if ($c > 0) {
				$plan[self::DAILY]['src'] = sprintf('%s/%s',
					self::DAILY, $snapshots[self::DAILY][$c - 1]);
			}
		}

		$do_weekly = false;
		$last_weekly = $this->GetSnapshot($snapshots, false, self::WEEKLY);

		if ($policy[self::WEEKLY] != 0) {
			if ($lt_now['tm_wday'] == 0) $do_weekly = true;
			else if ($last_weekly !== false) {
				$days = ($tv_now - $last_weekly) / self::TV_DAY;
				if ($days >= 7) $do_weekly = true;
			}
		}

		if ($do_weekly) {
			$plan[self::WEEKLY]['src'] = 0;
			$plan[self::WEEKLY]['dst'] = sprintf('%s/%s', self::WEEKLY, $tv_now);

			$snapshot = sprintf('%s/%s', $path, $plan[self::WEEKLY]['dst']);
			if (mkdir($snapshot) === false)
				throw new ServiceException(ServiceException::CODE_MKDIR_SNAPSHOT);

			$snapshots = $this->ScanSnapshots($path);
			$this->PurgeSnapshots($snapshots, $policy);

			$last_daily = $this->GetSnapshot($snapshots, false, self::DAILY);
			$last_weekly = false;
			$c = count($snapshots[self::WEEKLY]) - 1;
			if ($c > 0) $last_weekly = $snapshots[self::WEEKLY][$c - 1];
			if ($last_daily !== false && $last_weekly !== false) {
				if ($last_daily > $last_weekly) {
					$plan[self::WEEKLY]['src'] = sprintf('%s/%s',
						self::DAILY, $last_daily);
				} else {
					$plan[self::WEEKLY]['src'] = sprintf('%s/%s',
						self::WEEKLY, $last_weekly);
				}
			} else if ($last_daily !== false) {
				$plan[self::WEEKLY]['src'] = sprintf('%s/%s',
					self::DAILY, $last_daily);
			} else if ($last_weekly !== false) {
				$plan[self::WEEKLY]['src'] = sprintf('%s/%s',
					self::WEEKLY, $last_weekly);
			}
		}

		$do_monthly = false;
		$last_monthly = false;
		foreach ($snapshots[self::MONTHLY] as $snapshot) {
			$lt_monthly = localtime($snapshot, true);
			if ($lt_monthly['tm_mon'] != $lt_now['tm_mon'] ||
				$lt_monthly['tm_year'] != $lt_now['tm_year']) continue;
			if ($last_monthly === false) $last_monthly = $snapshot;
			else if ($snapshot > $last_monthly) $last_monthly = $snapshot;
		}

		if ($policy[self::MONTHLY] != 0) {
			if ($lt_now['tm_mday'] == 0) $do_monthly = true;
			else if ($last_monthly === false) $do_monthly = true;
		}

		if ($do_monthly) {
			$plan[self::MONTHLY]['src'] = 0;
			$plan[self::MONTHLY]['dst'] = sprintf('%s/%s', self::MONTHLY, $tv_now);

			$snapshot = sprintf('%s/%s', $path, $plan[self::MONTHLY]['dst']);
			if (mkdir($snapshot) === false)
				throw new ServiceException(ServiceException::CODE_MKDIR_SNAPSHOT);

			$snapshots = $this->ScanSnapshots($path);
			$this->PurgeSnapshots($snapshots, $policy);

			$last_daily = $this->GetSnapshot($snapshots, false, self::DAILY);
			$last_weekly = $this->GetSnapshot($snapshots, false, self::WEEKLY);
			$last_monthly = false;
			$c = count($snapshots[self::MONTHLY]) - 1;
			if ($c > 0) $last_monthly = $snapshots[self::MONTHLY][$c - 1];
			if ($last_daily === false) $last_daily = '0';
			if ($last_weekly === false) $last_weekly = '0';
			if ($last_monthly === false) $last_monthly = '0';
			$tv_max = max($last_daily, $last_weekly, $last_monthly);
			if (strcmp($tv_now, $tv_max) &&
				in_array($tv_max, $snapshots[self::MONTHLY])) {
				$plan[self::MONTHLY]['src'] = sprintf('%s/%s',
					self::MONTHLY, $tv_max);
			}
			else if (in_array($tv_max, $snapshots[self::WEEKLY])) {
				$plan[self::MONTHLY]['src'] = sprintf('%s/%s',
					self::WEEKLY, $tv_max);
			}
			else if (in_array($tv_max, $snapshots[self::DAILY])) {
				$plan[self::MONTHLY]['src'] = sprintf('%s/%s',
					self::DAILY, $tv_max);
			}
		}

		$do_yearly = false;
		$last_yearly = $this->GetSnapshot($snapshots, false, self::YEARLY);

		if ($policy[self::YEARLY] != 0) {
			if ($lt_now['tm_mon'] == 0 && $lt_now['tm_mday'] == 0)
				$do_yearly = true;
			else if ($lt_now['tm_mon'] == 0) {
				if ($last_yearly === false) $do_yearly = true;
				else {
					$lt_yearly = localtime($last_yearly, true);
					if ($lt_yearly['tm_year'] < $lt_now['tm_year'])
						$do_yearly = true;
				}
			}
		}

		if ($do_yearly) {
			$plan[self::YEARLY]['src'] = 0;
			$plan[self::YEARLY]['dst'] = sprintf('%s/%s', self::YEARLY, $tv_now);

			$snapshot = sprintf('%s/%s', $path, $plan[self::YEARLY]['dst']);
			if (mkdir($snapshot) === false)
				throw new ServiceException(ServiceException::CODE_MKDIR_SNAPSHOT);

			$snapshots = $this->ScanSnapshots($path);
			$this->PurgeSnapshots($snapshots, $policy);

			$last_daily = $this->GetSnapshot($snapshots, false, self::DAILY);
			$last_weekly = $this->GetSnapshot($snapshots, false, self::WEEKLY);
			$last_monthly = $this->GetSnapshot($snapshots, false, self::MONTHLY);
			$last_yearly = false;
			$c = count($snapshots[self::YEARLY]) - 1;
			if ($c > 0) $last_yearly = $snapshots[self::YEARLY][$c - 1];
			if ($last_daily === false) $last_daily = '0';
			if ($last_weekly === false) $last_weekly = '0';
			if ($last_monthly === false) $last_monthly = '0';
			if ($last_yearly === false) $last_yearly = '0';
			$tv_max = max($last_daily, $last_weekly, $last_monthly, $last_yearly);
			if (strcmp($tv_now, $tv_max) &&
				in_array($tv_max, $snapshots[self::YEARLY])) {
				$plan[self::YEARLY]['src'] = sprintf('%s/%s',
					self::YEARLY, $tv_max);
			}
			else if (in_array($tv_max, $snapshots[self::MONTHLY])) {
				$plan[self::YEARLY]['src'] = sprintf('%s/%s',
					self::MONTHLY, $tv_max);
			}
			else if (in_array($tv_max, $snapshots[self::WEEKLY])) {
				$plan[self::YEARLY]['src'] = sprintf('%s/%s',
					self::WEEKLY, $tv_max);
			}
			else if (in_array($tv_max, $snapshots[self::DAILY])) {
				$plan[self::YEARLY]['src'] = sprintf('%s/%s',
					self::DAILY, $tv_max);
			}
		}

		return $plan;
	}
}

// Backup result codes
define('RBS_RESULT_SUCCESS', 0);
define('RBS_RESULT_GENERAL_FAILURE', 1);
define('RBS_RESULT_PROTOCOL_ERROR', 2);
define('RBS_RESULT_FIFO_ERROR', 3);
define('RBS_RESULT_SERVICE_ERROR', 4);
define('RBS_RESULT_SOCKET_ERROR', 5);
define('RBS_RESULT_PROCESS_ERROR', 6);
define('RBS_RESULT_VOLUME_FULL', 7);
define('RBS_RESULT_UNKNOWN_ERROR', -1);

// Backup configuration node types
define('RBS_TYPE_BASE', 100);
define('RBS_TYPE_FILEDIR', 101);
define('RBS_TYPE_DATABASE', 102);
define('RBS_TYPE_MAIL', 103);
define('RBS_TYPE_MAX', RBS_TYPE_MAIL);

// Backup configuration node sub-types: databases
define('RBS_SUBTYPE_DATABASE_BASE', 200);
define('RBS_SUBTYPE_DATABASE_MYSQL', 201);
define('RBS_SUBTYPE_DATABASE_PGSQL', 202);
define('RBS_SUBTYPE_DATABASE_MAX', RBS_SUBTYPE_DATABASE_PGSQL);

// Backup configuration node sub-types: mail servers
define('RBS_SUBTYPE_MAIL_BASE', 300);
define('RBS_SUBTYPE_MAIL_CYRUSIMAP', 301);
define('RBS_SUBTYPE_MAIL_MAX', RBS_SUBTYPE_MAIL_CYRUSIMAP);

// The mode operations
define('RBS_MODE_INVALID', 0);
define('RBS_MODE_BACKUP', 1);
define('RBS_MODE_RESTORE', 2);
define('RBS_MODE_MOUNT', 3);
define('RBS_MODE_RESET', 4);
define('RBS_MODE_HISTORY', 5);
define('RBS_MODE_DELETE', 6);

// Remote backup service script class
class RemoteBackupService extends WebconfigScript
{
	// Service configuration file
	const FILE_CONF = '/etc/rbs/rbs.conf';

	// Backup config node directory
	const DIR_CONFIG_NODES = '/etc/rbs/config.d';

	// Filesystem type
	const FS_TYPE = 'ext2';

	// Syslog facility
	const SYSLOG_FACILITY = LOG_LOCAL0;

	// Maximum loop devices
	const MAX_LOOP_DEV = 256;

	// Maximum historical session stats to store
	const MAX_SESSION_HISTORY = 60;

	// Maximum control socket command/reply length
	const MAX_CMD_LENGTH = 8192;

	// Suva/2 socket path
	const PATH_RBSDATA = '/var/lib/rbs';

	// Kernel release
	const RELEASE_KERNEL = '/bin/uname -r';

	// OS release
	const RELEASE_OS = '/etc/release';

	// Volume name
	const VOLUME_NAME = 'rbs';

	// Volume mount point
	const VOLUME_MOUNT_POINT = '/var/lib/rbs/mnt';

	// Sync file-system buffers
	const SYNC_FSB = '/bin/sync';

	// AES kernel module
	const AES_MODULE = 'aes_i586';

	// iSCSI portal
	const ISCSI_PORTAL = '127.0.0.1:3260';

	// iSCSI configuration parameters
	const ISCSI_NOOP_OUT_TIMEOUT = 0;
	const ISCSI_NOOP_OUT_INTERVAL = 0;
	const ISCSI_IDLE_TIMEOUT = 0;
	const ISCSI_REPLACEMENT_TIMEOUT = 480; // Default: 120
	const ISCSI_ABORT_TIMEOUT = 60; // Default: 15
	const ISCSI_CMDS_MAX = 16; // Default: 4
	const ISCSI_QUEUE_DEPTH = 32; // Default: 128
	const ISCSI_MAX_BURST_LENGTH = 4194048; // Default: 16776192
	const ISCSI_TCP_WINDOW_SIZE = 131072; // Default: 524288
	const ISCSI_MAX_RECV_SEGMENT_LENGTH = 16384; // Default: 65536
	const ISCSI_FIRST_BURST_LENGTH = 65536; // Default: 262144

	// I/O scheduler format
	const FORMAT_IO_SCHEDULER = '/sys/block/%s/queue/scheduler';

	// Check file-system with automatic repair
	const FORMAT_FSCK = '/sbin/fsck -f -t %s -y /dev/mapper/%s';

	// iSCSI device name
	const FORMAT_ISCSI = '/dev/%s';

	// iSCSI discovery format
	const FORMAT_ISCSI_DISCOVERY = '/sbin/iscsiadm --mode discovery --type sendtargets --portal %s';

	// iSCSI login format
	const FORMAT_ISCSI_LOGIN = '/sbin/iscsiadm --mode node --targetname %s --portal %s --login';

	// iSCSI logout format
	const FORMAT_ISCSI_LOGOUT = '/sbin/iscsiadm --mode node --targetname %s --portal %s --logout';

	// iSCSI update parameter format
	const FORMAT_ISCSI_UPDATE = '/sbin/iscsiadm --mode node --targetname %s --op update --name=%s --value %s';

	// iSCSI delete node format
	const FORMAT_ISCSI_DELETE = '/sbin/iscsiadm --mode node --targetname %s --op delete --portal %s';

	// iSCSI get sessions format
	const FORMAT_ISCSI_SESSIONS = '/sbin/iscsiadm --mode session --op show';

	// Loop device name
	const FORMAT_LOOP = '/dev/loop%d';

	// Loop device status command
	const FORMAT_LOOP_STATUS = '/sbin/losetup /dev/loop%d';

	// Attach loop device to a file
	const FORMAT_LOOP_ATTACH = '/sbin/losetup /dev/loop%d %s';

	// Detach loop device
	const FORMAT_LOOP_DETACH = '/sbin/losetup -d /dev/loop%d';

	// Grow EXT file-system
	const FORMAT_RESIZE2FS = '/sbin/resize2fs /dev/mapper/%s';

	// Make file-system
	const FORMAT_MKFS = '/sbin/mkfs -t %s -q /dev/mapper/%s -L "%s"';

	// Create/start encrypted volume
	const FORMAT_CRYPTSETUP_CREATE = '/sbin/cryptsetup -c aes -s 256 -h aes-cbc-essiv:sha256 -d %s create %s %s';

	// Remove/stop encrypted volume
	const FORMAT_CRYPTSETUP_REMOVE = '/sbin/cryptsetup remove %s';

	// Mount file-system
	const FORMAT_MOUNT = '/bin/mount%s -n /dev/mapper/%s %s';

	// Unmount encrypted volume
	const FORMAT_UNMOUNT = '/bin/umount %s';

	// Kill any process with open handles within our mount point
	const FORMAT_FUSER = '/sbin/fuser -skm %s';

	// Database SQL filename format
	const FORMAT_SQL_FILE = 'db-%d-%s.sql';

	// MySQL database dump format
	const FORMAT_MYSQL_BACKUP = '/usr/bin/mysqldump%s --create-options --extended-insert --delayed-insert --quick --databases %s > %s';

	// MySQL database restore format
	const FORMAT_MYSQL_RESTORE = '/bin/cat %s | /usr/bin/mysql%s -B';

	// Service start format
	const FORMAT_SERVICE_START = '/sbin/service %s start';

	// Service stop format
	const FORMAT_SERVICE_STOP = '/sbin/service %s stop';

	// Delete snapshot format
	const FORMAT_DELETE_SNAPSHOT = '/bin/rm -rf %s/%s';

	// Retrieve snapshot disk usage stats
	const FORMAT_DU_KILOBYTE = '/usr/bin/du -k --max-depth 1 %s%s';

	// Rsync command
	const PATH_RSYNC = '/usr/bin/rsync';

	// Rsync file/directory list for backup/restore
	const PATH_RSYNC_INCLUDE = '/var/lib/rbs/rsync-include.conf';

	// Rsync exclude file list
	const PATH_RSYNC_EXCLUDE = '/var/lib/rbs/rsync-exclude.conf';

	// Rsync backup/restore URI
	const RSYNC_URI = 'rsync://127.0.0.1:3250/rbs';

	// Rsync backup format
	const FORMAT_RSYNC_BACKUP = '%s -a%s --exclude-from=%s -r --files-from=%s --delete --numeric-ids --stats %s %s';

	// Rsync restore format
	const FORMAT_RSYNC_RESTORE = '%s -a%s --exclude-from=%s -r --files-from=%s --numeric-ids --stats %s %s';

	// Modprobe format
	const FORMAT_MODPROBE = '/sbin/modprobe %s';

	// Mount list
	const FILE_PROC_MOUNTS = '/proc/mounts';

	// Snapshot history format
	const FORMAT_SNAPSHOT_HISTORY = '%s/snapshot-history.data';

	// Session history file
	const FILE_SESSION_HISTORY = '/var/lib/rbs/session-history.data';

	// Delete snapshot queue file
	const FILE_SNAPSHOT_DELETE_QUEUE = '/var/lib/rbs/delete-queue.data';

	// Rebuild/reconstruct cyrus index
	const MAIL_REBUILD_INDEX = '/bin/su -s /bin/sh -c "/usr/lib/cyrus-imapd/reconstruct -r user" cyrus';

	// Rebuild/reconstruct cyrus squat files
	const MAIL_REBUILD_SQUAT = '/bin/su -s /bin/sh -c "/usr/lib/cyrus-imapd/squatter -r user" cyrus';

	// Suva/2 data session TTL (time-to-live while idle) in seconds
	const SESSION_TTL = 3600;

	// Session log file
	const SESSION_LOG = '/var/log/rbs-session.log';

	// Select time-out for socket select operations
	const TIMEOUT_SELECT = 2;

	// Mount-mode time-out in seconds
	const TIMEOUT_MOUNT = 1800;

	// iSCSI device discovery time-out in seconds
	const TIMEOUT_ISCSI_DEVICE = 120;

	// Socket re-connect timeout
	const TIMEOUT_SOCKET_RETRY = 10;

	// Control protocol version
	const PROTOCOL_VERSION = '2.1';

	// Control command: hello
	const CTRL_CMD_HELLO = 100;

	// Control command: provision
	const CTRL_CMD_PROVISION = 120;

	// Control command: request session
	const CTRL_CMD_SESSION = 130;

	// Control command: export, blocks client until server has created iSCSI portal
	const CTRL_CMD_EXPORT = 140;

	// Control command: mount, blocks client until server has mounted volume and execs rsyncd
	const CTRL_CMD_MOUNT = 141;

	// Control command: send volume stats
	const CTRL_CMD_VOL_STATS = 150;

	// Control command: request backup history
	const CTRL_CMD_LOAD_SNAPSHOTS = 160;

	// Control command: send backup history
	const CTRL_CMD_SAVE_SNAPSHOTS = 161;

	// Control command: delete snapshot
	const CTRL_CMD_DELETE_SNAPSHOT = 170;

	// Control command: is data process running?
	const CTRL_CMD_IS_BUSY = 180;

	// Control command: reset, erases all backup data!
	const CTRL_CMD_RESET = 190;

	// Control command: notify server that a backup is starting
	const CTRL_CMD_BACKUP_START = 200;

	// Control command: logout, quit session
	const CTRL_CMD_LOGOUT = 210;

	// Control command: make snapshot directories, prepare backup plan
	const CTRL_CMD_RETENTION_PREPARE = 220;

	// Control command: ping
	const CTRL_CMD_PING = 999;

	// Control reply: OK
	const CTRL_REPLY_OK = 500;

	// Control reply: Busy
	const CTRL_REPLY_BUSY = 501;

	// Control reply: session request
	const CTRL_REPLY_SESSION = 510;

	// Control reply: waiting for export
	const CTRL_REPLY_EXPORT_WAIT = 520;

	// Control reply: waiting for mount
	const CTRL_REPLY_MOUNT_WAIT = 521;

	// Control reply: backup data exported
	const CTRL_REPLY_EXPORTED = 530;

	// Control reply: backup data mounted
	const CTRL_REPLY_MOUNTED = 531;

	// Control reply: waiting for provision copy to complete
	const CTRL_REPLY_PROVISION_COPY = 540;

	// Control reply: waiting for provision grow to complete
	const CTRL_REPLY_PROVISION_GROW = 541;

	// Control reply: provision complete; client must init data (mkfs)
	const CTRL_REPLY_PROVISION_MKFS = 550;

	// Control reply: provision complete
	const CTRL_REPLY_PROVISION_OK = 560;

	// Control reply: volume usage stats
	const CTRL_REPLY_VOL_STATS = 570;

	// Control reply: snapshot list
	const CTRL_REPLY_SNAPSHOTS = 580;

	// Control reply: reset wait
	const CTRL_REPLY_RESET_WAIT = 590;

	// Control reply: retry last socket operation
	const CTRL_REPLY_RETRY = 600;

	// Control reply: error
	const CTRL_REPLY_ERROR = 900;

	// Status: control connect
	const STATUS_CONNECT_CONTROL = 'CONNECT_CONTROL';

	// Status: request session
	const STATUS_REQUEST_SESSION = 'REQUEST_SESSION';

	// Status: deleting snapshot
	const STATUS_DELETE_SNAPSHOT = 'DELETE_SNAPSHOT';

	// Status: 
	const STATUS_REQUEST_SNAPSHOT = 'REQUEST_SNAPSHOT';

	// Status: provision
	const STATUS_PROVISION = 'PROVISION';

	// Status: backup file has been exported
	const STATUS_EXPORTED = 'EXPORTED';

	// Status: waiting for export
	const STATUS_EXPORT_WAIT = 'EXPORT_WAIT';

	// Status: waiting for mount
	const STATUS_MOUNT_WAIT = 'MOUNT_WAIT';

	// Status: waiting for data reset
	const STATUS_RESET_WAIT = 'RESET_WAIT';

	// Status: waiting on provisioning copy
	const STATUS_PROVISION_COPY = 'PROVISION_COPY';

	// Status: waiting on provisioning grow
	const STATUS_PROVISION_GROW = 'PROVISION_GROW';

	// Status: mounting file-system
	const STATUS_FS_MOUNT = 'FS_MOUNT';

	// Status: file-system mounted
	const STATUS_FS_MOUNTED = 'FS_MOUNTED';

	// Status: verifying file-system
	const STATUS_FS_VERIFY = 'FS_VERIFY';

	// Status: extending file-system
	const STATUS_FS_EXTEND = 'FS_EXTEND';

	// Status: formatting file-system
	const STATUS_FS_FORMAT = 'FS_FORMAT';

	// Status: formatting file-system
	const STATUS_FS_SYNC = 'FS_SYNC';

	// Status: running rsync
	const STATUS_RSYNC = 'RSYNC';

	// Status: backing up database
	const STATUS_DB_BACKUP = 'DB_BACKUP';

	// Status: restoring database
	const STATUS_DB_RESTORE = 'DB_RESTORE';

	// Status: backing up mail server
	const STATUS_MAIL_BACKUP = 'MAIL_BACKUP';

	// Status: restoring mail server
	const STATUS_MAIL_RESTORE = 'MAIL_RESTORE';

	// Status: building mail indexes
	const STATUS_MAIL_REINDEX = 'MAIL_REINDEX';

	// Status: iSCSI portal login
	const STATUS_PORTAL_LOGIN = 'PORTAL_LOGIN';

	// Status: complete!
	const STATUS_COMPLETE = 'COMPLETE';

	// Status: creating snapshot directores, preparing retention plan
	const STATUS_RETENTION_PREPARE = 'RETENTION_PREPARE';

	// Error: control socket is busy
	const ERROR_HELLO_BUSY = 'HELLO_BUSY';

	// Error: maintenance mode
	const ERROR_HELLO_MAINT = 'HELLO_MAINT';

	// Error: missing backup file
	const ERROR_EXPORT_MISSING = 'EXPORT_MISSING';

	// Error: corrupt backup file
	const ERROR_EXPORT_CORRUPT = 'EXPORT_CORRUPT';

	// Error: export failed
	const ERROR_EXPORT_FAILED = 'EXPORT_FAILED';

	// Error: export timed-out
	const ERROR_EXPORT_TIMEOUT = 'EXPORT_TIMEOUT';

	// Error: not supported
	const ERROR_NOT_SUPPORTED = 'NOT_SUPPORTED';

	// Error: generic system error
	const ERROR_SYSTEM_ERROR = 'SYSTEM_ERROR';

	// Error: invalid mode
	const ERROR_INVALID_MODE = 'INVALID_MODE';

	// Error: no license
	const ERROR_NO_LICENSE = 'NO_LICENSE';

	// Error: service disabled
	const ERROR_SERVICE_DISABLED = 'SERVICE_DISABLED';

	// Internal state flags: file-system mounted
	const STATE_MOUNTED = 0x0001;

	// Internal state flags: cryptsetup started
	const STATE_CRYPTSETUP = 0x0002;

	// Internal state flags: iSCSI login
	const STATE_ISCSI = 0x0004;

	// Suva/2 backup control socket filename
	private static $path_socket_control;

	// Suva/2 backup control socket descriptor
	private $socket_control = -1;

	// Suva/2 backup data socket filename
	private static $path_socket_data;

	// Suva/2 backup data socket descriptor
	private $socket_data = -1;

	// iSCSI block device
	private $iscsi_device = null;

	// iSCSI target
	private $iscsi_target = false;

	// iSCSI username
	private $iscsi_user = false;

	// iSCSI password
	private $iscsi_passwd = false;

	// Is this a server?
	private $is_server;

	// Loop device id
	private $loop_id = -1;

	// Internal state of mount, cryptsetup, and iSCSI
	private $session_state = 0;

	// My PID
	private $my_pid = -1;

	// Volume name
	private $vol_name = self::VOLUME_NAME;

	// Volume mount point
	private $vol_mount = self::VOLUME_MOUNT_POINT;

	// Global configuration
	private $config = array();

	// Configuration nodes
	private $config_nodes = array();

	// Temporary dump files to delete on exit
	private $dump_files = array();

	// Session timestamp
	private $session_timestamp = null;

	public function __construct($name, $is_server = false)
	{
		parent::__construct($name);

		$this->my_pid = posix_getpid();

		// Initialize static members
		self::$path_socket_control = self::PATH_RBSDATA . '/rb-control.socket';
 		self::$path_socket_data = self::PATH_RBSDATA . '/rb-data.socket';

		// Set client/server modes from constructor parameters
		$this->is_server = $is_server;

		// Control socket is /dev/stdin if when run as a server
		if ($is_server) $this->socket_control = STDIN;
	}

	public function __destruct()
	{
		// If we're a child process or if we're signalling a running
		// process to unmount and exit, don't do anything below...
		if ($this->my_pid != posix_getpid()) return;
		if (!array_key_exists('mode', $this->state) ||
			$this->state['mode'] == RBS_MODE_INVALID) return;

		// Record time of termination
		if (is_resource($this->state_fh)) {
			$this->state['tm_completed'] = time();
			$this->SerializeState();
		}

		// Try to tear down the encrypted file-system if it was local to us
		if (array_key_exists('is_local_fs', $this->state) && $this->state['is_local_fs']) {
			try {
				$this->UnmountFilesystem();
			} catch (Exception $e) { };
			try {
				$this->RemoveEncryptedVolume();
			} catch (Exception $e) { };
			try {
				if ($this->loop_id != -1) $this->DetachLoopDevice();
			} catch (Exception $e) { };

			if (!$this->is_server) $this->iScsiLogout();
		}

		// Close control socket if connected
		if (is_resource($this->socket_control)) {
			switch(strtolower(get_resource_type($this->socket_control))) {
			case 'socket':
				socket_shutdown($this->socket_control);
				socket_close($this->socket_control);
			break;
			}
		}

		// Delete any temporary dump files
		foreach ($this->dump_files as $path) unlink($path);

		if (!$this->is_server) $this->SaveStateHistory();
	}

	// Reset state
	public final function ResetState()
	{
		$this->state['error_code'] = 0;
		$this->state['is_local_fs'] = true;
		$this->state['mode'] = RBS_MODE_INVALID;
		$this->state['status_code'] = 0;
		$this->state['status_data'] = null;
		$this->state['tm_completed'] = 0;
		$this->state['tm_started'] = time();
		$this->state['usage_stats'] = null;
	}

	// Load configuration
	public final function LoadConfiguration()
	{
		if (!file_exists(self::FILE_CONF))
			throw new ServiceException(ServiceException::CODE_CONFIG_MISSING);
		$this->config = parse_ini_file(self::FILE_CONF, true);
		if (!count($this->config))
			throw new ServiceException(ServiceException::CODE_CONFIG_INVALID);
		$this->LoadConfigurationNodes();
		return $this->config;
	}

	// Start a process
	public final function StartProcess($command, &$pipes, $env = null)
	{
		$this->LogMessage(sprintf('%s: %s', __FUNCTION__, $command), LOG_DEBUG);

		$descriptors = array(0 => array('pipe', 'r'),
			1 => array('pipe', 'w'), 2 => array('pipe', 'w'));
		$ph = proc_open($command, $descriptors, $pipes, null, $env);

		if (!is_resource($ph))
			throw new ProcessException(ProcessException::CODE_EXEC);

		return $ph;
	}

	// Is the supplied process handle running
	public final function IsProcessRunning($ph, &$status)
	{
		$status = proc_get_status($ph);
		return $status['running'];
	}

	// Wait on process execution to complete, log any output
	public final function WaitOnProcess($prefix, $ph, $stdout, $stderr, &$status, $ping = false)
	{
		if (!is_resource($ph) || !is_resource($stdout) || !is_resource($stderr)) return;

		$status = proc_get_status($ph);

		$buffer = array();
		$buffer[0] = '';
		$buffer[1] = '';

		if (stream_set_blocking($stdout, 0) === false ||
			stream_set_blocking($stderr, 0) === false) {
			proc_terminate($ph);
			fclose($stdout);
			fclose($stderr);
			proc_close($ph);
			throw new ProcessException(ProcessException::CODE_NONBLOCK);
		}

		while (true) {
			$r = array();
			$r[0] = $stdout;
			$r[1] = $stderr;
			$w = null;
			$e = null;
			$count = 0;
			if (($count = stream_select($r, $w, $e, self::TIMEOUT_SELECT, 0)) === false) {
				proc_terminate($ph);
				fclose($stdout);
				fclose($stderr);
				proc_close($ph);
				throw new ProcessException(ProcessException::CODE_SELECT);
			} else if ($count > 0) {
				foreach ($r as $id => $s) {
					if (($c = fgetc($s)) === false || !strlen($c)) continue;
					if (!strcmp($c, "\n")) {
						$level = LOG_NOTICE;
						if ($id == 1) $level = LOG_ERR;
						$this->LogMessage(sprintf('%s: %s', $prefix, $buffer[$id]), $level);
						$buffer[$id] = '';
					} else $buffer[$id] .= $c;
				}
			} else if ($ping && !$this->is_server) $this->ControlSendPing(true);

			if (!$status['running']) break;
			$status = proc_get_status($ph);
		}

		// Flush stdout/err descriptors
		$level = LOG_NOTICE;
		while(!feof($stdout) && ($c = fgetc($stdout)) !== false) {
			if (!strcmp($c, "\n")) {
				if(strlen(trim($buffer[0])))
					$this->LogMessage(sprintf('%s: %s', $prefix, $buffer[0]), $level);
				$buffer[0] = '';
			} else $buffer[0] .= $c;
		}
		if (strlen($buffer[0]))
			$this->LogMessage(sprintf('%s: %s', $prefix, $buffer[0]), $level);

		$level = LOG_ERR;
		while(!feof($stderr) && ($c = fgetc($stderr)) !== false) {
			if (!strcmp($c, "\n")) {
				if(strlen(trim($buffer[1])))
					$this->LogMessage(sprintf('%s: %s', $prefix, $buffer[1]), $level);
				$buffer[1] = '';
			} else $buffer[1] .= $c;
		}
		if (strlen($buffer[1]))
			$this->LogMessage(sprintf('%s: %s', $prefix, $buffer[1]), $level);

		return $status['exitcode'];
	}

	// Execute a process, doesn't return until complete and logs all output
	public final function ExecProcess($prefix, $command, $env = null, $ping = false)
	{
		$ph = $this->StartProcess($command, $pipes, $env);
		$this->WaitOnProcess($prefix, $ph, $pipes[1], $pipes[2], $status, $ping);
		foreach ($pipes as $pipe) fclose($pipe);
		proc_close($ph);
		return $status['exitcode'];
	}

	// Set loop device ID
	public final function SetLoopDeviceId($id)
	{
		$this->loop_id = $id;
	}

	// Get loop device ID
	public final function GetLoopDeviceId()
	{
		return $this->loop_id;
	}

	// Attach new loop device
	public final function AttachLoopDevice($device)
	{
		$loop_id = -1;

		try {
			for ($id = 0; $id < self::MAX_LOOP_DEV; $id++) {
				if ($this->ExecProcess('loop status', sprintf(self::FORMAT_LOOP_STATUS, $id)) == 0)
					continue;
				$loop_id = $id;
				break;
			}
		} catch (Exception $e) {
			throw new ServiceException(ServiceException::CODE_LOOP_STATUS);
		}

		if ($loop_id == -1)
			throw new ServiceException(ServiceException::CODE_NO_FREE_LOOP_DEVICE);

		try {
			$this->ExecProcess('loop attach', sprintf(self::FORMAT_LOOP_ATTACH, $loop_id, $device));
		} catch (Exception $e) {
			throw new ServiceException(ServiceException::CODE_LOOP_ATTACH);
		}

		$this->loop_id = $loop_id;
		return $loop_id;
	}

	// Sync file-system buffers
	public final function SyncFilesystem($status = false)
	{
		if ($status) $this->SetStatusCode(self::STATUS_FS_SYNC);
		if ($this->ExecProcess('sync', self::SYNC_FSB, null, true) != 0)
			throw new ServiceException(ServiceException::CODE_SYNC_FSB);
	}

	// Detach loop device
	public final function DetachLoopDevice()
	{
		try {
			$this->ExecProcess('loop detach', sprintf(self::FORMAT_LOOP_DETACH, $this->loop_id));
		} catch (Exception $e) {
			throw new ServiceException(ServiceException::CODE_LOOP_DETACH);
		}

		$this->loop_id = -1;
	}

	// Create encrypted volume
	public final function CreateEncryptedVolume($key_file)
	{
		$exitcode = 0;

		try {
			// Load AES module
			$this->ExecProcess('modprobe', sprintf(self::FORMAT_MODPROBE, self::AES_MODULE));
			$exitcode = $this->ExecProcess('cryptsetup create',
				sprintf(self::FORMAT_CRYPTSETUP_CREATE, $key_file, $this->vol_name,
				($this->loop_id == -1) ?
					$this->iscsi_device :
					sprintf(self::FORMAT_LOOP, $this->loop_id)));
			$this->session_state |= self::STATE_CRYPTSETUP;
		} catch (Exception $e) {
			throw new ServiceException(ServiceException::CODE_CRYPTSETUP_CREATE);
		}

		if ($exitcode != 0)
			throw new ServiceException(ServiceException::CODE_CRYPTSETUP_CREATE, $exitcode);
	}

	// Remove encrypted volume
	public final function RemoveEncryptedVolume($force = false)
	{
		if (!$force && !($this->session_state & self::STATE_CRYPTSETUP)) return;
		try {
			$this->ExecProcess('cryptsetup remove',
				sprintf(self::FORMAT_CRYPTSETUP_REMOVE, $this->vol_name));
			$this->session_state &= ~self::STATE_CRYPTSETUP;
		} catch (Exception $e) {
			throw new ServiceException(ServiceException::CODE_CRYPTSETUP_REMOVE);
		}
	}

	// Verify file-system
	public final function VerifyFilesystem()
	{
		$exitcode = 0;

		try {
			$this->SetStatusCode(self::STATUS_FS_VERIFY);
			$exitcode = $this->ExecProcess('fsck',
				sprintf(self::FORMAT_FSCK, self::FS_TYPE,
				$this->vol_name), null, true);
		} catch (ServiceException $e) {
			throw new ServiceException($e->getCode());
		} catch (Exception $e) {
			throw new ServiceException(ServiceException::CODE_FSCK);
		}

		if ($exitcode == 0 || $exitcode == 1) return;
		throw new ServiceException(ServiceException::CODE_FSCK, $exitcode);
	}

	// Format file-system
	public final function FormatFilesystem()
	{
		$exitcode = 0;

		try {
			$this->SetStatusCode(self::STATUS_FS_FORMAT);
			$exitcode = $this->ExecProcess('format',
				sprintf(self::FORMAT_MKFS, self::FS_TYPE,
				$this->vol_name, sprintf('rbs-%u', time())), null, true);
		} catch (Exception $e) {
			throw new ServiceException(ServiceException::CODE_MKFS);
		}

		if ($exitcode != 0)
			throw new ServiceException(ServiceException::CODE_MKFS, $exitcode);
	}


	// Extend an EXT file-system
	public final function ExtendFilesystem()
	{
		$exitcode = 0;

		try {
			$this->SyncFilesystem();
			$this->SetStatusCode(self::STATUS_FS_EXTEND);
			$exitcode = $this->ExecProcess('resize2fs',
				sprintf(self::FORMAT_RESIZE2FS, $this->vol_name), null, true);
		} catch (Exception $e) {
			throw new ServiceException(ServiceException::CODE_RESIZE2FS);
		}

		if ($exitcode != 0)
			throw new ServiceException(ServiceException::CODE_RESIZE2FS, $exitcode);
	}

	// Mount file-system
	public final function MountFilesystem($mount_options = null)
	{
		$exitcode = 0;

		try {
			$this->SetStatusCode(self::STATUS_FS_MOUNT);
			// Create mount point if it doesn't exist
			if (!file_exists($this->vol_mount) && mkdir($this->vol_mount, 0700, true) === false)
					throw new ServiceException(ServiceException::CODE_MKDIR_MOUNT);
			// Never update file/directory access timestamps, don't need it and don't
			// want the associated band-width overhead it causes...
			$options = array('noatime');
			if ($this->IsRestoreMode() || $this->IsMountMode() || $this->IsHistoryMode())
				$options[] = 'ro';
			if ($this->state['is_local_fs']) {
				// XXX: No network file-system specific options at this time...
			}
			if ($mount_options != null && is_array($mount_options))
				$options = array_unique(array_merge($options, $mount_options));
			$additional_flags = null;
			// Create mount '-o' options parameter if set
			if (count($options))
				$additional_flags = '-o ' . implode(',', $options);
			// Mount file-system
			$exitcode = $this->ExecProcess('mount',
				sprintf(self::FORMAT_MOUNT,
				($additional_flags != null) ? " $additional_flags" : '',
				$this->vol_name, $this->vol_mount));
			// Remember that we mounted this
			$this->session_state |= self::STATE_MOUNTED;
			$this->SetStatusCode(self::STATUS_FS_MOUNTED);
		} catch (ServiceException $e) {
			throw new ServiceException($e->getCode());
		} catch (Exception $e) {
			throw new ServiceException(ServiceException::CODE_MOUNT);
		}

		if ($exitcode != 0)
			throw new ServiceException(ServiceException::CODE_MOUNT, $exitcode);
	}

	// Unmount file-system
	public final function UnmountFilesystem($force = false)
	{
		if (!$force && !$this->IsVolumeMounted()) return;
		if (!$force && !($this->session_state & self::STATE_MOUNTED)) return;
		try {
			$this->ExecProcess('fuser', sprintf(self::FORMAT_FUSER, $this->vol_mount));
			$this->ExecProcess('unmount', sprintf(self::FORMAT_UNMOUNT, $this->vol_mount));
		} catch (Exception $e) {
			throw new ServiceException(ServiceException::CODE_UNMOUNT);
		}
	}

	// Rsync data
	public final function RsyncData($src, $dst = self::VOLUME_MOUNT_POINT, $link_dest = null, $status = self::STATUS_RSYNC)
	{
		$exitcode = 0;

		try {
			$this->SetStatusCode($status);

			$additional_flags = null;

			// Set-up rsync environment
			$env = null;

			if ($this->IsBackupMode() && $link_dest != null) {
				$additional_flags .= sprintf(' --link-dest=%s/%s',
					($this->state['is_local_fs']) ? '../..' : '', $link_dest);
			}

			if (!$this->state['is_local_fs']) {
				$additional_flags .= ' -z';
			} else {
				$additional_flags .= ' --whole-file';
			}

			$additional_flags .= ($this->debug ? ' -v' : ' -q');
			$exitcode = $this->ExecProcess('rsync',
				sprintf($this->IsRestoreMode() ? self::FORMAT_RSYNC_RESTORE : self::FORMAT_RSYNC_BACKUP,
				self::PATH_RSYNC, $additional_flags,
				self::PATH_RSYNC_EXCLUDE, self::PATH_RSYNC_INCLUDE, $src, $dst), $env, true);
		} catch (ServiceException $e) {
			throw new ServiceException($e->getCode());
		} catch (Exception $e) {
			throw new ServiceException(ServiceException::CODE_RSYNC);
		}

		// Throw an exception if rsync failed
		switch ($exitcode) {
		case 0:
		// XXX: Vanished files (24) are not considered fatal.
		case 24:
			$this->SyncFilesystem(true);
			break;
		case 12:
		// XXX: Data stream protocol error (12) really means filesystem full.
			throw new ServiceException(ServiceException::CODE_VOLUME_FULL, $exitcode);
		default:
			throw new ServiceException(ServiceException::CODE_RSYNC, $exitcode);
		}
	}

	// Set session timestamp
	public final function SetSessionTimestamp()
	{
		$this->session_timestamp = time();
		return $this->session_timestamp;
	}

	// Update error code in state file
	public final function SetErrorCode($error_code)
	{
		$this->state['error_code'] = $error_code;
		if (!is_resource($this->state_fh)) return;
		$this->SerializeState();
	}

	// Update status code in state file
	public final function SetStatusCode($status_code, $status_data = null)
	{
		$this->state['status_code'] = $status_code;
		$this->state['status_data'] = $status_data;
		$this->state['error_code'] = 0;
		if (!is_resource($this->state_fh)) return;
		$this->SerializeState();
	}

	// Set file-system usage stats
	public final function SetFilesystemStats($stats)
	{
		$this->state['snapshot'] = $this->session_timestamp;
		$this->state['usage_stats'] = $stats;
		$this->SerializeState();
	}

	// Set local or remote filesystem mode
	public final function SetFilesystemLocal($local = true)
	{
		$this->state['is_local_fs'] = $local;
		$this->SerializeState();
	}

	// Return filesystem mode
	public final function IsFilesystemLocal()
	{
		return $this->state['is_local_fs'];
	}

	// Set mode
	public final function SetMode($mode)
	{
		$this->state['mode'] = $mode;
		$this->SerializeState();
	}

	// Return session mode
	public final function IsBackupMode()
	{
		if (!array_key_exists('mode', $this->state))
			return RBS_MODE_INVALID;
		return ($this->state['mode'] == RBS_MODE_BACKUP);
	}

	// Set restore session mode
	public final function SetRestoreMode($restore = true)
	{
		$this->state['mode'] = $restore ? RBS_MODE_RESTORE : RBS_MODE_BACKUP;
		$this->SerializeState();
	}

	// Return session mode
	public final function IsRestoreMode()
	{
		if (!array_key_exists('mode', $this->state))
			return RBS_MODE_INVALID;
		return ($this->state['mode'] == RBS_MODE_RESTORE);
	}

	// Set reset mode
	public final function SetResetMode($enable = true)
	{
		$this->state['mode'] = $enable ? RBS_MODE_RESET : RBS_MODE_INVALID;
		$this->SerializeState();
	}

	// Return session mode
	public final function IsResetMode()
	{
		return ($this->state['mode'] == RBS_MODE_RESET);
	}

	// Set update history mode
	public final function SetHistoryMode($enable = true)
	{
		$this->state['mode'] = $enable ? RBS_MODE_HISTORY : RBS_MODE_INVALID;
		$this->SerializeState();
	}

	// Return session mode
	public final function IsHistoryMode()
	{
		return ($this->state['mode'] == RBS_MODE_HISTORY);
	}

	// Set delete snapshot mode
	public final function SetDeleteMode($enable = true)
	{
		$this->state['mode'] = $enable ? RBS_MODE_DELETE : RBS_MODE_INVALID;
		$this->SerializeState();
	}

	// Return session mode
	public final function IsDeleteMode()
	{
		return ($this->state['mode'] == RBS_MODE_DELETE);
	}

	// Set mount mode (true = mount, false = unmount)
	public final function SetMountMode($mount = false)
	{
		$this->state['mode'] = $mount ? RBS_MODE_MOUNT : RBS_MODE_INVALID;
		if ($mount) $this->state['is_local_fs'] = true;
		if ($this->IsRunning()) $this->SerializeState();
	}

	// Return mount mode
	public final function IsMountMode()
	{
		return ($this->state['mode'] == RBS_MODE_MOUNT);
	}

	// Set volume name
	public final function SetVolumeName($name)
	{
		$this->vol_name = $name;
	}

	// Set volume mount point
	public final function SetVolumeMountPoint($path)
	{
		$this->vol_mount = $path;
	}

	// Get volume mount point
	public final function GetVolumeMountPoint()
	{
		return $this->vol_mount;
	}

	// Is volume mounted
	public final function IsVolumeMounted()
	{
		$mounted = false;
		$fh = fopen(self::FILE_PROC_MOUNTS, 'r');
		if (!is_resource($fh)) return $mounted;
		while (!feof($fh)) {
			// /dev/sda1 /boot ext3 rw,data=ordered 0 0
			$entry = chop(fgets($fh, 4096));
			if (substr_count($entry, ' ') != 5) continue;
			list($device, $mount_point, $type, $options, $dump, $pass) = explode(' ', $entry, 6);
			if (strcmp($mount_point, $this->vol_mount) != 0) continue;
			$mounted = true;
			break;
		}
		fclose($fh);
		return $mounted;
	}

	// Process DIR_CONFIG_NODES directory and generate configuration array
	public final function LoadConfigurationNodes()
	{
		if (!is_dir(self::DIR_CONFIG_NODES))
			throw new ServiceException(ServiceException::CODE_CONFIG_NODES_MISSING);
		$dh = opendir(self::DIR_CONFIG_NODES);
		if (!is_resource($dh))
			throw new ServiceException(ServiceException::CODE_CONFIG_NODES_INVALID);
		$this->config_nodes = array();
		while (($file = readdir($dh)) !== false) {
			$path = sprintf('%s/%s', self::DIR_CONFIG_NODES, $file);
			if (is_dir($path)) continue;
			$config = parse_ini_file($path);
			if (!count($config)) {
				$this->LogMessage("Invalid backup configuration node: $file", LOG_WARNING);
				continue;
			}
			if (array_key_exists('enabled', $config) && !$config['enabled']) continue;
			if ($this->IsRestoreMode() &&
				(!array_key_exists('mode', $config) || $config['mode'] != RBS_MODE_RESTORE)) continue;
			if ($this->IsBackupMode() &&
				(!array_key_exists('mode', $config) || $config['mode'] != RBS_MODE_BACKUP)) continue;
			if (!array_key_exists('type', $config)) {
				$this->LogMessage("Missing type in configuration node: $file", LOG_WARNING);
				continue;
			}
			if ($config['type'] <= RBS_TYPE_BASE || $config['type'] > RBS_TYPE_MAX) {
				$this->LogMessage("Invalid type in configuration node: $file", LOG_WARNING);
				continue;
			}
			if (($config['type'] == RBS_TYPE_DATABASE || $config['type'] == RBS_TYPE_MAIL) &&
				!array_key_exists('sub-type', $config)) {
				$this->LogMessage("Missing required sub-type in configuration node: $file", LOG_WARNING);
				continue;
			}
			if ($config['type'] == RBS_TYPE_FILEDIR) {
				if (!array_key_exists('path', $config) || !strlen($config['path'])) {
					$this->LogMessage("Missing required path in configuration node: $file", LOG_WARNING);
					continue;
				}
				if (!preg_match('|^/|', $config['path'])) $config['path'] = '/' . $config['path'];
			}
			if ($config['type'] == RBS_TYPE_DATABASE) {
				if ($config['sub-type'] <= RBS_SUBTYPE_DATABASE_BASE || $config['sub-type'] > RBS_SUBTYPE_DATABASE_MAX) {
					$this->LogMessage("Invalid sub-type in configuration node: $file", LOG_WARNING);
					continue;
				}
				if ($config['sub-type'] == RBS_SUBTYPE_DATABASE_MYSQL || $config['sub-type'] == RBS_SUBTYPE_DATABASE_PGSQL) {
					if (!array_key_exists('db-name', $config) || !strlen($config['db-name'])) {
						$this->LogMessage("Missing required db-name in configuration node: $file", LOG_WARNING);
						continue;
					}
				}
			}
			if ($config['type'] == RBS_TYPE_MAIL) {
				if ($config['sub-type'] <= RBS_SUBTYPE_MAIL_BASE || $config['sub-type'] > RBS_SUBTYPE_MAIL_MAX) {
					$this->LogMessage("Invalid sub-type in configuration node: $file", LOG_WARNING);
					continue;
				}
			}
			$config['source'] = $file;
			$config['name'] = str_ireplace('.ini', '', $file);
			$this->config_nodes[] = $config;
		}
		closedir($dh);
	}

	public final function GetConfigurationNodes($type = 0, $sub_type = 0, $name = null)
	{
		if (!count($this->config_nodes))
			$this->LoadConfigurationNodes();
		if ($type == 0 && $sub_type == 0 && $name == null)
			return $this->config_nodes;
		$config_nodes = array();
		foreach ($this->config_nodes as $node) {
			if (($type == 0 || $type == $node['type']) &&
				($sub_type == 0 || $sub_type == $node['sub-type']) &&
				($name == null || !strcasecmp($name, $node['name']))) {
					if (!array_key_exists('enabled', $node)) $node['enabled'] = true;
					$config_nodes[] = $node;
			}
		}
		return $config_nodes;
	}

	public final function GenerateRsyncFiles()
	{
		if (!count($this->config_nodes))
			$this->LoadConfigurationNodes();
		if (!count($this->config_nodes))
			throw new ServiceException(ServiceException::CODE_CONFIG_NODES_EMPTY);

		// Build list of files/directories to include
		$fh_include = fopen(self::PATH_RSYNC_INCLUDE, 'w');
		$fh_exclude = fopen(self::PATH_RSYNC_EXCLUDE, 'w');

		if (!is_resource($fh_include) || !is_resource($fh_exclude))
			throw new ServiceException(ServiceException::CODE_CONFIG_INCLUDE_EXCLUDE_OPEN);

		// This array contains a list of paths we always want to rsync
		$auto_include = array();

		if (array_key_exists('rbs-temp', $this->config) &&
			strlen($this->config['rbs-temp']) && file_exists($this->config['rbs-temp']))
			$auto_include[] = $this->config['rbs-temp'];
		else $auto_include[] = '/tmp/rbs-temp';

		foreach ($auto_include as $pattern) fwrite($fh_include, "$pattern\n");

		// This array contains a list of paths we never want to rsync
		// TODO: Perhaps load presets from an external source?
		$auto_exclude = array();
		$auto_exclude[] = '/dev';
		$auto_exclude[] = '/etc/mnt';
		$auto_exclude[] = '/proc';
		$auto_exclude[] = '/sys';
		$auto_exclude[] = $this->vol_mount;
		$auto_exclude[] = '/tmp';
		$auto_exclude[] = '/var/tmp';
		$auto_exclude[] = '/var/lib/imap';
		$auto_exclude[] = '/var/run';
		$auto_exclude[] = '/var/spool/imap';

		foreach ($auto_exclude as $pattern) fwrite($fh_exclude, "$pattern\n");

		// Add files/directories to include/exclude files
		clearstatcache();
		foreach ($this->config_nodes as $config) {
			if ($config['type'] != RBS_TYPE_FILEDIR) continue;
			if (array_key_exists('exclude', $config) && $config['exclude'])
				fwrite($fh_exclude, $config['path'] . "\n");
			else if (!file_exists($config['path'])) {
				$this->LogMessage('No such file or directory: ' . $config['path'], LOG_WARNING);
				continue;
			}
			fwrite($fh_include, $config['path'] . "\n");
		}
		fclose($fh_include);
		fclose($fh_exclude);
	}

	// Backup database(s)
	public final function BackupDatabases()
	{
		if (!is_array($this->config_nodes) || !count($this->config_nodes)) return;

		if (array_key_exists('rbs-temp', $this->config) &&
			strlen($this->config['rbs-temp']) && file_exists($this->config['rbs-temp']))
			$dumpdir = $this->config['rbs-temp'];
		else $dumpdir = '/tmp/rbs-temp';

		if (!file_exists($dumpdir) && mkdir($dumpdir, 0700, true) === false)
			throw new ServiceException(ServiceException::CODE_MKDIR_DUMP);

		foreach ($this->config_nodes as $config) {
			if ($config['type'] != RBS_TYPE_DATABASE) continue;
			if (!array_key_exists('mode', $config) || $config['mode'] != RBS_MODE_BACKUP) continue;
			if (!array_key_exists('db-name', $config) || !strlen($config['db-name'])) {
				$this->LogMessage('Missing db-name in configuration node: ' . $config['source'], LOG_WARNING);
				continue;
			}
			$db_list = explode(':', trim($config['db-name'], ':'));
			foreach ($db_list as $db_name) {
				if (!strlen($db_name)) continue;
				$dumpfile = sprintf('%s/%s', $dumpdir,
					sprintf(self::FORMAT_SQL_FILE, $config['sub-type'], $db_name));
				if ($config['sub-type'] == RBS_SUBTYPE_DATABASE_MYSQL) {
					$exitcode = 0;

					try {
						$this->SetStatusCode(self::STATUS_DB_BACKUP, $db_name);
						$additional_flags = '';
						if (array_key_exists('username', $config) && strlen($config['username']))
							$additional_flags .= sprintf(' --user="%s"', $config['username']);
						if (array_key_exists('password', $config) && strlen($config['password']))
							$additional_flags .= sprintf(' --password="%s"', $config['password']);
						if (array_key_exists('dump-options', $config) && strlen($config['dump-options']))
							$additional_flags .= sprintf(' %s', $config['dump-options']);
						$exitcode = $this->ExecProcess('mysqldump',
							sprintf(self::FORMAT_MYSQL_BACKUP,
								$additional_flags, $db_name, $dumpfile), null, true);
					} catch (Exception $e) {
						throw new ServiceException(ServiceException::CODE_MYSQL_DUMP);
					}

					if ($exitcode != 0)
						throw new ServiceException(ServiceException::CODE_MYSQL_DUMP, $exitcode);

					// Remember to delete this temporary dump file after a successful backup
					$this->dump_files[] = $dumpfile;
				}
			}
		}
	}

	// Restore database(s)
	// XXX: This will update/replace data on the local database server(s).
	// Don't call this if you just want to retrieve the SQL backup file(s).
	public final function RestoreDatabases($restore_to)
	{
		if (!is_array($this->config_nodes) || !count($this->config_nodes)) return;

		$dumpdir = $restore_to;
		if (array_key_exists('rbs-temp', $this->config) &&
				strlen($this->config['rbs-temp']) && file_exists($this->config['rbs-temp']))
				$dumpdir .= $this->config['rbs-temp'];
		else $dumpdir .= '/tmp/rbs-temp';

		if (!file_exists($dumpdir) && mkdir($dumpdir, 0700, true) === false)
			throw new ServiceException(ServiceException::CODE_MKDIR_DUMP);

		foreach ($this->config_nodes as $config) {
			if ($config['type'] != RBS_TYPE_DATABASE) continue;
			if (!array_key_exists('mode', $config) || $config['mode'] != RBS_MODE_RESTORE) continue;
			if (!array_key_exists('db-name', $config) || !strlen($config['db-name'])) {
				$this->LogMessage('Missing db-name in configuration node: ' . $config['source'], LOG_WARNING);
				continue;
			}
			$db_list = explode(':', trim($config['db-name'], ':'));
			foreach ($db_list as $db_name) {
				if (!strlen($db_name)) continue;
				$dumpfile = sprintf('%s/%s', $dumpdir,
					sprintf(self::FORMAT_SQL_FILE, $config['sub-type'], $db_name));
				if (!file_exists($dumpfile)) {
					$this->LogMessage("No database dump file found: $dumpfile", LOG_WARNING);
					continue;
				}
				if ($config['sub-type'] == RBS_SUBTYPE_DATABASE_MYSQL) {
					$exitcode = 0;

					try {
						$this->SetStatusCode(self::STATUS_DB_RESTORE, $db_name);
						$additional_flags = '';
						if (array_key_exists('username', $config) && strlen($config['username']))
							$additional_flags .= sprintf(' --user="%s"', $config['username']);
						if (array_key_exists('password', $config) && strlen($config['password']))
							$additional_flags .= sprintf(' --password="%s"', $config['password']);
						if (array_key_exists('restore-options', $config) && strlen($config['restore-options']))
							$additional_flags .= sprintf(' %s', $config['restore-options']);
						$exitcode = $this->ExecProcess('mysql',
							sprintf(self::FORMAT_MYSQL_RESTORE,
								$dumpfile, $additional_flags), null, true);
					} catch (Exception $e) {
						throw new ServiceException(ServiceException::CODE_MYSQL_RESTORE);
					}

					if ($exitcode != 0)
						throw new ServiceException(ServiceException::CODE_MYSQL_RESTORE, $exitcode);

					// Delete temporary dump file after a successful restore
					unlink($dumpfile);
				}
			}
		}
	}

	// Backup mail server(s)
	public final function BackupMailServers($dst, $link_dest = null)
	{
		if (!is_array($this->config_nodes) || !count($this->config_nodes)) return;

		$fh_exclude = fopen(self::PATH_RSYNC_EXCLUDE, 'w');
		// Do not back-up cyrus index and quat files, they change too often and
		// they can be rebuilt on a restore.
		if (is_resource($fh_exclude)) {
			fwrite($fh_exclude, "cyrus.cache\n");
			fwrite($fh_exclude, "cyrus.header\n");
			fwrite($fh_exclude, "cyrus.index\n");
			fwrite($fh_exclude, "cyrus.squat\n");
			fclose($fh_exclude);
		}

		foreach ($this->config_nodes as $config) {
			if ($config['type'] != RBS_TYPE_MAIL) continue;
			if ($config['sub-type'] == RBS_SUBTYPE_MAIL_CYRUSIMAP) {
				if (!file_exists('/var/lib/imap') || !file_exists('/var/spool/imap')) {
					$this->LogMessage('Cyrus IMAP server does not seem to be installed', LOG_WARNING);
					continue;
				}
				$fh_include = fopen(self::PATH_RSYNC_INCLUDE, 'w');
				if (!is_resource($fh_include))
					throw new ServiceException(ServiceException::CODE_CONFIG_INCLUDE_EXCLUDE_OPEN);
				fwrite($fh_include, "/var/lib/imap\n");
				fwrite($fh_include, "/var/spool/imap\n");
				fclose($fh_include);

				// Transfer data while mail service is running...
				$this->RsyncData('/', $dst, $link_dest, self::STATUS_MAIL_BACKUP);

				$exitcode = $this->ExecProcess('imapd stop',
					sprintf(self::FORMAT_SERVICE_STOP, 'cyrus-imapd'));

				// XXX: If the service was not running?
				//if ($exitcode != 0)
				//	throw new ServiceException(ServiceException::CODE_SERVICE_STOP, $exitcode);

				try {
					// Transfer data once again while mail service stopped...
					$this->RsyncData('/', $dst, $link_dest, self::STATUS_MAIL_BACKUP);
				} catch (Exception $e) {
					$this->ExecProcess('imapd start',
						sprintf(self::FORMAT_SERVICE_START, 'cyrus-imapd'));
					throw $e;
				}

				$exitcode = $this->ExecProcess('imapd start',
					sprintf(self::FORMAT_SERVICE_START, 'cyrus-imapd'));

				if ($exitcode != 0)
					throw new ServiceException(ServiceException::CODE_SERVICE_STOP, $exitcode);
			}
		}
	}

	// iSCSI login
	public final function iScsiLogin()
	{
		$this->SetStatusCode(self::STATUS_PORTAL_LOGIN);

		if ($this->iscsi_target === false)
			throw new ServiceException(ServiceException::CODE_ISCSI_INVALID_TARGET);

		// Delete a previous target record (may not exist)
		$this->ExecProcess('iscsiadm', sprintf(self::FORMAT_ISCSI_DELETE,
			$this->iscsi_target, self::ISCSI_PORTAL));
		
		// Perform iSCSI discovery
		$target = false;
		$ph = popen(sprintf(self::FORMAT_ISCSI_DISCOVERY, self::ISCSI_PORTAL) . ' 2>&1', 'r');
		if (!is_resource($ph)) {
			$this->LogMessage('Unable to complete iSCSI discovery', LOG_ERR);
			throw new ServiceException(ServiceException::CODE_ISCSI_DISCOVERY);
		}
		while (!feof($ph)) {
			// 127.0.0.1:3260,1 iqn.2008-08.com.pointclark.rbs:test
			$buffer = rtrim(fgets($ph, 4096));
			$this->LogMessage($buffer, LOG_DEBUG);
			if (!preg_match('/^' . self::ISCSI_PORTAL . ',[0-9]+\s+(.*)$/',
				$buffer, $parts)) continue;
			if (!strcasecmp($parts[1], $this->iscsi_target)) {
				$target = true;
				break;
			}
		}
		if (pclose($ph) != 0)
			throw new ServiceException(ServiceException::CODE_ISCSI_DISCOVERY);
		if (!$target)
			throw new ServiceException(ServiceException::CODE_ISCSI_TARGET_NOT_FOUND);

		// Set authentication method, username, and password
		if ($this->ExecProcess('iscsiadm', sprintf(self::FORMAT_ISCSI_UPDATE,
			$this->iscsi_target, 'node.session.auth.authmethod', 'CHAP')) != 0)
			throw new ServiceException(ServiceException::CODE_ISCSI_SET_AUTH_FAILURE);
		if ($this->ExecProcess('iscsiadm', sprintf(self::FORMAT_ISCSI_UPDATE,
			$this->iscsi_target, 'node.session.auth.username', $this->iscsi_user)) != 0)
			throw new ServiceException(ServiceException::CODE_ISCSI_SET_AUTH_FAILURE);
		if ($this->ExecProcess('iscsiadm', sprintf(self::FORMAT_ISCSI_UPDATE,
			$this->iscsi_target, 'node.session.auth.password', $this->iscsi_passwd)) != 0)
			throw new ServiceException(ServiceException::CODE_ISCSI_SET_AUTH_FAILURE);

		// Enable fast abort (IET fix, but doesn't seem to work)
		$this->ExecProcess('iscsiadm', sprintf(self::FORMAT_ISCSI_UPDATE,
			$this->iscsi_target, 'node.session.iscsi.FastAbort', 'Yes'));

		// Set iSCSI parameters
		$this->ExecProcess('iscsiadm', sprintf(self::FORMAT_ISCSI_UPDATE,
			$this->iscsi_target, 'node.session.cmds_max',
			self::ISCSI_CMDS_MAX));
		$this->ExecProcess('iscsiadm', sprintf(self::FORMAT_ISCSI_UPDATE,
			$this->iscsi_target, 'node.session.queue_depth',
			self::ISCSI_QUEUE_DEPTH));
		$this->ExecProcess('iscsiadm', sprintf(self::FORMAT_ISCSI_UPDATE,
			$this->iscsi_target, 'node.session.timeo.replacement_timeout',
			self::ISCSI_REPLACEMENT_TIMEOUT));
		$this->ExecProcess('iscsiadm', sprintf(self::FORMAT_ISCSI_UPDATE,
			$this->iscsi_target, 'node.session.err_timeo.abort_timeout',
			self::ISCSI_ABORT_TIMEOUT));
		$this->ExecProcess('iscsiadm', sprintf(self::FORMAT_ISCSI_UPDATE,
			$this->iscsi_target, 'node.session.iscsi.FirstBurstLength',
			self::ISCSI_FIRST_BURST_LENGTH));
		$this->ExecProcess('iscsiadm', sprintf(self::FORMAT_ISCSI_UPDATE,
			$this->iscsi_target, 'node.session.iscsi.MaxBurstLength',
			self::ISCSI_MAX_BURST_LENGTH));
		$this->ExecProcess('iscsiadm', sprintf(self::FORMAT_ISCSI_UPDATE,
			$this->iscsi_target, 'node.conn[0].timeo.noop_out_timeout',
			self::ISCSI_NOOP_OUT_TIMEOUT));
		$this->ExecProcess('iscsiadm', sprintf(self::FORMAT_ISCSI_UPDATE,
			$this->iscsi_target, 'node.conn[0].timeo.noop_out_interval',
			self::ISCSI_NOOP_OUT_INTERVAL));
// XXX: No longer valid...
//		$this->ExecProcess('iscsiadm', sprintf(self::FORMAT_ISCSI_UPDATE,
//			$this->iscsi_target, 'node.conn[0].timeo.idle_timeout',
//			self::ISCSI_IDLE_TIMEOUT));
		$this->ExecProcess('iscsiadm', sprintf(self::FORMAT_ISCSI_UPDATE,
			$this->iscsi_target, 'node.conn[0].tcp.window_size',
			self::ISCSI_TCP_WINDOW_SIZE));
		$this->ExecProcess('iscsiadm', sprintf(self::FORMAT_ISCSI_UPDATE,
			$this->iscsi_target, 'node.conn[0].iscsi.MaxRecvDataSegmentLength',
			self::ISCSI_MAX_RECV_SEGMENT_LENGTH));

		// Login
		if ($this->ExecProcess('iscsiadm', sprintf(self::FORMAT_ISCSI_LOGIN,
			$this->iscsi_target, self::ISCSI_PORTAL)) != 0)
			throw new ServiceException(ServiceException::CODE_ISCSI_LOGIN);
		$this->session_state |= self::STATE_ISCSI;

		// Retrieve iSCSI block device name
		for ($i = 0; $i < self::TIMEOUT_ISCSI_DEVICE && $this->iscsi_device == null; $i++) {
			sleep(1);
			$ph = popen(self::FORMAT_ISCSI_SESSIONS . ' --print 3', 'r');
			if (!is_resource($ph)) {
				$this->LogMessage('Error running: ' . self::FORMAT_ISCSI_SESSIONS, LOG_DEBUG);
				throw new ServiceException(ServiceException::CODE_ISCSI_SESSIONS);
			}
			while (!feof($ph)) {
				// \t\t\tAttached scsi disk sdb
				if (!preg_match('/^\t\t\tAttached scsi disk (sd[a-z]{1}).*$/',
					fgets($ph, 4096), $parts)) continue;
				$this->iscsi_device = sprintf(self::FORMAT_ISCSI, $parts[1]);
				break;
			}
			if (pclose($ph) != 0) {
				$this->LogMessage('Error code returned: ' . self::FORMAT_ISCSI_SESSIONS, LOG_DEBUG);
				throw new ServiceException(ServiceException::CODE_ISCSI_SESSIONS);
			}
		}
		if ($this->iscsi_device == null) {
			$this->LogMessage('ISCSI device null.', LOG_DEBUG);
			throw new ServiceException(ServiceException::CODE_ISCSI_SESSIONS);
		}

		for ($i = 0; $i < self::TIMEOUT_ISCSI_DEVICE; $i++) {
			if (@stat($this->iscsi_device) === false) { sleep(1); continue; }
			$this->LogMessage(sprintf('iSCSI block device: %s', $this->iscsi_device), LOG_DEBUG);
			return;
		}
		// iSCSI device didn't appear after 10 seconds...
		throw new ServiceException(ServiceException::CODE_ISCSI_DEVICE_NOT_FOUND);
	}

	// iSCSI logout
	public final function iScsiLogout($force = false)
	{
		if (!$force && !($this->session_state & self::STATE_ISCSI)) return;
		$ph = popen(self::FORMAT_ISCSI_SESSIONS, 'r');
		if (!is_resource($ph))
			throw new ServiceException(ServiceException::CODE_ISCSI_SESSIONS);
		while (!feof($ph)) {
			// ^tcp: \[[0-9]*\] 127.0.0.1:3260,[0-9]* iqn.2008-08.com.pointclark.rbs:test$
			if (!preg_match(sprintf('/^tcp: \\[[0-9]+\\] %s,[0-9]+ (.*)$/',
				self::ISCSI_PORTAL), fgets($ph, 4096), $parts)) continue;
			$this->ExecProcess('iscsiadm', sprintf(self::FORMAT_ISCSI_LOGOUT,
				$parts[1], self::ISCSI_PORTAL));
		}
		$this->session_state &= ~self::STATE_ISCSI;
	}

	// Restore mail server(s)
	public final function RestoreMailServers($restore_to)
	{
		if (!is_array($this->config_nodes) || !count($this->config_nodes)) return;

		$fh_exclude = fopen(self::PATH_RSYNC_EXCLUDE, 'w');
		if (is_resource($fh_exclude)) fclose($fh_exclude);

		foreach ($this->config_nodes as $config) {
			if ($config['type'] != RBS_TYPE_MAIL) continue;
			if ($config['sub-type'] == RBS_SUBTYPE_MAIL_CYRUSIMAP) {
				$fh_include = fopen(self::PATH_RSYNC_INCLUDE, 'w');
				if (!is_resource($fh_include))
					throw new ServiceException(ServiceException::CODE_CONFIG_INCLUDE_EXCLUDE_OPEN);
				fwrite($fh_include, "/var/lib/imap\n");
				fwrite($fh_include, "/var/spool/imap\n");
				fclose($fh_include);

				if ($restore_to == '/') {
					$exitcode = $this->ExecProcess('imapd stop',
							sprintf(self::FORMAT_SERVICE_STOP, 'cyrus-imapd'));
				}

// XXX: If the service is not currently running...
//				if ($exitcode != 0)
//					throw new ServiceException(ServiceException::CODE_SERVICE_STOP, $exitcode);

				try {
					if ($this->IsFilesystemLocal())
						$this->RsyncData(self::VOLUME_MOUNT_POINT, $restore_to, null, self::STATUS_MAIL_RESTORE);
					else {
						$this->ControlBusyWait();
						$this->RsyncData(self::RSYNC_URI, $restore_to, null, self::STATUS_MAIL_RESTORE);
					}
				} catch (Exception $e) {
					if ($restore_to != '/') throw $e;
					$this->ExecProcess('imapd start',
						sprintf(self::FORMAT_SERVICE_START, 'cyrus-imapd'));
					throw $e;
				}

				if ($restore_to == '/') {
					$this->SetStatusCode(self::STATUS_MAIL_REINDEX);

					$exitcode = $this->ExecProcess('imapd reindex', MAIL_REBUILD_INDEX);
					if ($exitcode != 0)
						throw new ServiceException(ServiceException::CODE_MAIL_REINDEX, $exitcode);

					$exitcode = $this->ExecProcess('imapd rebuild', MAIL_REBUILD_SQUAT);
					if ($exitcode != 0)
						throw new ServiceException(ServiceException::CODE_MAIL_REBUILD, $exitcode);

					$exitcode = $this->ExecProcess('imapd start',
							sprintf(self::FORMAT_SERVICE_START, 'cyrus-imapd'));
					if ($exitcode != 0)
						throw new ServiceException(ServiceException::CODE_SERVICE_STOP, $exitcode);
				}
			}
		}
	}

	// Read a control packet
	public final function ControlSocketRead(&$code, &$data, $retry = false)
	{
		if (!is_resource($this->socket_control))
			throw new ControlSocketException(ControlSocketException::CODE_INVALID_RESOURCE);

		// Read response
		switch(strtolower(get_resource_type($this->socket_control))) {
		case 'socket':
			for ($i = 0; $i < 3600; $i++) {
				if (($buffer = socket_read($this->socket_control,
					self::MAX_CMD_LENGTH, PHP_NORMAL_READ)) !== false) break;

				if (!$retry)
					throw new ControlSocketException(ControlSocketException::CODE_READ);

				socket_close($this->socket_control);
				$this->socket_control = null;
				sleep(self::TIMEOUT_SOCKET_RETRY);

				$sd = socket_create(AF_UNIX, SOCK_STREAM, 0);
				if (!is_resource($sd))
					throw new ControlSocketException(ControlSocketException::CODE_CREATE);
				socket_set_option($sd, SOL_SOCKET, SO_SNDTIMEO,
					array('sec' => 5, 'usec' => 0));
				socket_set_option($sd, SOL_SOCKET, SO_RCVTIMEO,
					array('sec' => 5, 'usec' => 0));

				// Reconnect socket
				if (!socket_connect($sd, self::$path_socket_control)) {
					socket_close($sd);
					continue;
				}

				$this->socket_control = $sd;
				$buffer = sprintf('%d:Socket retry', self::CTRL_REPLY_RETRY);
				break;
			}
			if (!is_resource($this->socket_control))
				throw new ControlSocketException(ControlSocketException::CODE_READ);
			break;

		case 'file':
		case 'stream':
			$buffer = fgets($this->socket_control, self::MAX_CMD_LENGTH);
			break;

		default:
			throw new ControlSocketException(ControlSocketException::CODE_INVALID_RESOURCE);
		}

		if (!strlen($buffer))
			throw new ProtocolException(ProtocolException::CODE_HANGUP);
		if (($parts = explode(':', chop($buffer), 2)) === false)
			throw new ProtocolException(ProtocolException::CODE_SYNTAX_ERROR, $buffer);
		if (count($parts) != 2)
			throw new ProtocolException(ProtocolException::CODE_SYNTAX_ERROR, $buffer);
		$code = sprintf('%d', $parts[0]);
		$data = $parts[1];

		switch ($code) {
			case self::CTRL_CMD_PING:
			case self::CTRL_REPLY_OK:
				return;
		}

		$this->LogMessage(sprintf('%s: %s:%s', __FUNCTION__, $code,
			$code == self::CTRL_CMD_MOUNT ? 'XxXxXxXxXxXxXxXxXxXxXxXxXxXxXxXx' : $data),
			LOG_DEBUG);
	}

	// Write a control packet
	public final function ControlSocketWrite($code, $data)
	{
		if (!is_resource($this->socket_control))
			throw new ControlSocketException(ControlSocketException::CODE_INVALID_RESOURCE);

		switch(strtolower(get_resource_type($this->socket_control))) {
		case 'socket':
			for ($i = 0; $i < 3600; $i++) {
				if (socket_write($this->socket_control, "$code:$data\n") !== false) break;

				socket_close($this->socket_control);
				$this->socket_control = null;
				sleep(self::TIMEOUT_SOCKET_RETRY);

				$sd = socket_create(AF_UNIX, SOCK_STREAM, 0);
				if (!is_resource($sd))
					throw new ControlSocketException(ControlSocketException::CODE_CREATE);
				socket_set_option($sd, SOL_SOCKET, SO_SNDTIMEO,
					array('sec' => 5, 'usec' => 0));
				socket_set_option($sd, SOL_SOCKET, SO_RCVTIMEO,
					array('sec' => 5, 'usec' => 0));

				// Reconnect socket
				if (!socket_connect($sd, self::$path_socket_control)) {
					socket_close($sd);
					continue;
				}

				$i = 0;
				$this->socket_control = $sd;
			}
			if (!is_resource($this->socket_control))
				throw new ControlSocketException(ControlSocketException::CODE_WRITE);
			break;

		case 'file':
		case 'stream':
			if ($this->is_server)
				fwrite(STDOUT, "$code:$data\n");
			else fwrite($this->socket_control, "$code:$data\n");
			break;

		default:
			throw new ControlSocketException(ControlSocketException::CODE_INVALID_RESOURCE);
		}

		switch ($code) {
			case self::CTRL_CMD_PING:
			case self::CTRL_REPLY_OK:
				return;
		}

		$this->LogMessage(sprintf('%s: %s:%s', __FUNCTION__, $code,
			$code == self::CTRL_CMD_MOUNT ? 'XxXxXxXxXxXxXxXxXxXxXxXxXxXxXxXx' : $data),
			LOG_DEBUG);
	}

	// Make a control socket connection
	public final function ControlSocketConnect()
	{
		$this->SetStatusCode(self::STATUS_CONNECT_CONTROL);

		// Build MD5 version string from kernel + OS strings
		$ph = popen(self::RELEASE_KERNEL, 'r');
		if (!is_resource($ph))
			throw new WebconfigScriptException(WebconfigScriptException::CODE_MKRELEASE);
		$release = chop(fgets($ph, 4096));
		if (pclose($ph) != 0)
			throw new WebconfigScriptException(WebconfigScriptException::CODE_MKRELEASE);
		$fh = fopen(self::RELEASE_OS, 'r');
		if (!is_resource($fh))
			throw new WebconfigScriptException(WebconfigScriptException::CODE_MKRELEASE);
		$release .= chop(fgets($fh, 4096));
		fclose($fh);

		if (!is_resource($this->socket_control)) $this->socket_control = -1;
		else throw new ControlSocketException(ControlSocketException::CODE_ALREADY_CONNECTED);

		// Check to see if socket exists
		clearstatcache();
		if (!file_exists(self::$path_socket_control))
			throw new ServiceException(ServiceException::CODE_MISSING_CONTROL_SOCKET);

		// Create socket
		$sd = socket_create(AF_UNIX, SOCK_STREAM, 0);
		if (!is_resource($sd))
			throw new ControlSocketException(ControlSocketException::CODE_CREATE);

		// Connect socket
		if (!socket_connect($sd, self::$path_socket_control)) {
			socket_close($sd);
			throw new ControlSocketException(ControlSocketException::CODE_CONNECT);
		}

		// Remember socket descriptor
		$this->socket_control = $sd;

		// Write 'hello' version packet
		$this->ControlSocketWrite(self::CTRL_CMD_HELLO,
			self::PROTOCOL_VERSION . ':' . md5($release));

		// Read response
		$this->ControlSocketRead($code, $data);

		switch ($code) {
		case self::CTRL_REPLY_OK:
			$reply = explode(':', $data);
			if (!is_array($reply) || count($reply) != 2)
				throw new ProtocolException(ProtocolException::CODE_ERROR, "$code:$data");
			$this->session_timestamp = $reply[0];
			return;

		case self::CTRL_REPLY_ERROR:
			throw new ProtocolException(ProtocolException::CODE_ERROR, "$code:$data");

		default:
			$this->LogMessage("Unexpected control reply: $code", LOG_ERR);
		}

		throw new ProtocolException(ProtocolException::CODE_UNEXPECTED, $code);
	}

	// Request backup session from server
	public final function ControlRequestSession()
	{
		$this->SetStatusCode(self::STATUS_REQUEST_SESSION);

		if (!is_resource($this->socket_control))
			throw new ControlSocketException(ControlSocketException::CODE_INVALID_RESOURCE);

		// Send request
		$this->ControlSocketWrite(self::CTRL_CMD_SESSION, $this->state['mode']);

		// Read reply
		$this->ControlSocketRead($code, $data);

		switch ($code) {
		case self::CTRL_REPLY_SESSION:
			return;

		case self::CTRL_REPLY_ERROR:
			throw new ProtocolException(ProtocolException::CODE_ERROR, "$code:$data");
		}

		throw new ProtocolException(ProtocolException::CODE_UNEXPECTED, $code);
	}

	// Request backup data export
	public final function ControlRequestExport()
	{
		if (!is_resource($this->socket_control))
			throw new ControlSocketException(ControlSocketException::CODE_INVALID_RESOURCE);

		// Send request
		$this->ControlSocketWrite(self::CTRL_CMD_EXPORT, 'Export');

		// Read reply, wait for backup data export...
		while (true) {
			$this->ControlSocketRead($code, $data);

			switch ($code) {
			case self::CTRL_REPLY_EXPORT_WAIT:
				// XXX: May want to check for a time-out condition here in the future...
				$this->SetStatusCode(self::STATUS_EXPORT_WAIT);
				continue;

			case self::CTRL_REPLY_EXPORTED:
				$iscsi_credentials = explode('|', $data);
				if (!is_array($iscsi_credentials) || count($iscsi_credentials) != 3)
					throw new ProtocolException(ProtocolException::CODE_ERROR, "$code:$data");
				$this->iscsi_target = $iscsi_credentials[0];
				$this->iscsi_user = $iscsi_credentials[1];
				$this->iscsi_passwd = $iscsi_credentials[2];
				$this->SetStatusCode(self::STATUS_EXPORTED);
				return;

			case self::CTRL_REPLY_ERROR:
				throw new ProtocolException(ProtocolException::CODE_ERROR, "$code:$data");
			}
		}
	}

	// Request backup data remote mount
	public final function ControlRequestMount($mount_key)
	{
		if (!is_resource($this->socket_control))
			throw new ControlSocketException(ControlSocketException::CODE_INVALID_RESOURCE);

		// Send request
		$this->ControlSocketWrite(self::CTRL_CMD_MOUNT, $mount_key);

		// Read reply, wait for backup data export...
		while (true) {
			$this->ControlSocketRead($code, $data);

			switch ($code) {
			case self::CTRL_REPLY_MOUNT_WAIT:
				// XXX: May want to check for a time-out condition here in the future...
				$this->SetStatusCode(self::STATUS_MOUNT_WAIT);
				continue;

			case self::CTRL_REPLY_MOUNTED:
				$this->SetStatusCode(self::STATUS_FS_MOUNTED);
				return;

			case self::CTRL_REPLY_ERROR:
				throw new ProtocolException(ProtocolException::CODE_ERROR, "$code:$data");
			}
		}
	}

	// Request backup data provision
	public final function ControlRequestProvision()
	{
		if (!is_resource($this->socket_control))
			throw new ControlSocketException(ControlSocketException::CODE_INVALID_RESOURCE);

		// Send request
		while (true) {
			$this->ControlSocketWrite(self::CTRL_CMD_PROVISION, 'Provision');

			// Read reply, wait for provisioning to complete...
			while (true) {
				$this->ControlSocketRead($code, $data, true);

				switch ($code) {
				case self::CTRL_REPLY_PROVISION_COPY:
					// XXX: May want to check for a time-out condition here in the future...
					$this->SetStatusCode(self::STATUS_PROVISION_COPY, $data);
					continue;

				case self::CTRL_REPLY_PROVISION_GROW:
					// XXX: May want to check for a time-out condition here in the future...
					$this->SetStatusCode(self::STATUS_PROVISION_GROW, $data);
					continue;

				case self::CTRL_REPLY_PROVISION_OK:
				case self::CTRL_REPLY_PROVISION_MKFS:
					return $code;

				case self::CTRL_REPLY_ERROR:
					throw new ProtocolException(ProtocolException::CODE_ERROR, "$code:$data");
				}
				if ($code == self::CTRL_REPLY_RETRY) break;
			}
		}
	}

	// Request data reset
	public final function ControlRequestReset()
	{
		if (!is_resource($this->socket_control))
			throw new ControlSocketException(ControlSocketException::CODE_INVALID_RESOURCE);

		// Send request
		$this->ControlSocketWrite(self::CTRL_CMD_RESET, 'Reset');

		// Read reply, wait for reset to complete...
		while (true) {
			$this->ControlSocketRead($code, $data);

			switch ($code) {
			case self::CTRL_REPLY_RESET_WAIT:
				// XXX: May want to check for a time-out condition here in the future...
				$this->SetStatusCode(self::STATUS_RESET_WAIT);
				continue;

			case self::CTRL_REPLY_OK:
				return;

			case self::CTRL_REPLY_ERROR:
				throw new ProtocolException(ProtocolException::CODE_ERROR, "$code:$data");
			}
		}
	}

	// Wait for data script to exit
	public final function ControlBusyWait()
	{
		if (!is_resource($this->socket_control))
			throw new ControlSocketException(ControlSocketException::CODE_INVALID_RESOURCE);

		// Send request
		$this->ControlSocketWrite(self::CTRL_CMD_IS_BUSY, 'Busy?');

		// Read reply, wait for backup data export...
		while (true) {
			$this->ControlSocketRead($code, $data);

			switch ($code) {
			case self::CTRL_REPLY_OK:
				return;

			case self::CTRL_REPLY_BUSY:
				continue;

			case self::CTRL_REPLY_ERROR:
				throw new ProtocolException(ProtocolException::CODE_ERROR, "$code:$data");
			}
		}
	}

	// Send session logout to server
	public final function ControlSendSessionLogout($success = true)
	{
		if (!is_resource($this->socket_control))
			throw new ControlSocketException(ControlSocketException::CODE_INVALID_RESOURCE);

		// Send request
		$this->ControlSocketWrite(self::CTRL_CMD_LOGOUT, 'Logout');
		if ($success)
			$this->SetStatusCode(self::STATUS_COMPLETE);
	}

	// Ping control socket
	public final function ControlSendPing($retry = false)
	{
		if (!is_resource($this->socket_control))
			throw new ControlSocketException(ControlSocketException::CODE_INVALID_RESOURCE);

		// Send request
		while (true) {
			$this->ControlSocketWrite(self::CTRL_CMD_PING, 'Ping!');
			$this->ControlSocketRead($code, $data, $retry);

			switch ($code) {
			case self::CTRL_REPLY_OK:
				return;

			case self::CTRL_REPLY_RETRY:
				continue;

			default:
			case self::CTRL_REPLY_ERROR:
				throw new ProtocolException(ProtocolException::CODE_ERROR, "$code:$data");
			}
		}
	}

	// Notify server that a backup is starting
	public final function ControlBackupStart()
	{
		if (!is_resource($this->socket_control))
			throw new ControlSocketException(ControlSocketException::CODE_INVALID_RESOURCE);

		// Send request
		$this->ControlSocketWrite(self::CTRL_CMD_BACKUP_START, 'Backup starting...');
	}

	// Send file-system stats
	public final function ControlSendStats($exitcode)
	{
		if (!$this->state['is_local_fs']) {
			$this->ControlSocketWrite(self::CTRL_CMD_VOL_STATS, "0:0:$exitcode");

			// Read reply, wait for file-system usage stats
			$this->ControlSocketRead($code, $data);

			switch ($code) {
			case self::CTRL_REPLY_VOL_STATS:
				$this->SetFilesystemStats($data);
				break;

			case self::CTRL_REPLY_ERROR:
				throw new ProtocolException(ProtocolException::CODE_ERROR,
					"$code:$data");
			}
			return;
		}
		if (!extension_loaded('statvfs')) dl('statvfs.so');
		if (!extension_loaded('statvfs'))
			throw new ServiceException(ServiceException::CODE_STATVFS);
		$this->SyncFilesystem();
		$stats = statvfs($this->vol_mount);
		$usage = sprintf('%u:%u:%d', $stats['used'], $stats['size'], $exitcode);
		$this->ControlSocketWrite(self::CTRL_CMD_VOL_STATS, $usage);
		$this->SetFilesystemStats($usage);
	}

	// Create snapshot directories, prepare backup plan
	public final function ControlRetentionPrepare($policy)
	{
		$plan = array();
		$this->SetStatusCode(self::STATUS_RETENTION_PREPARE);

		if ($this->state['is_local_fs']) {
			$retention = new Retention($this->session_timestamp);
			if (!$retention->MakeDirectories($this->vol_mount))
				throw new ServiceException(ServiceException::CODE_MKDIR_SNAPSHOT);
			$plan = $retention->PreparePlan($snapshots, $policy, $this->vol_mount);
		} else {
			$this->ControlSocketWrite(self::CTRL_CMD_RETENTION_PREPARE,
				base64_encode(serialize($policy)));

			// Read reply, wait for backup plan
			$this->ControlSocketRead($code, $data);

			switch ($code) {
			case self::CTRL_REPLY_OK:
				$plan = unserialize(base64_decode($data));
				break;

			case self::CTRL_REPLY_ERROR:
			default:
				throw new ProtocolException(ProtocolException::CODE_ERROR,
					"$code:$data");
			}
		}

		return $plan;
	}

	// Return snapshot timestamps from mounted volume
	public final function ControlRequestSnapshots($send_history = false)
	{
		$snapshots = array();
		$this->SetStatusCode(self::STATUS_REQUEST_SNAPSHOT);

		function GetDiskUsage($subdir, $vol_mount, $cmd, $snapshot_size, $delta = true)
		{
			$ph = popen(sprintf($cmd, $delta ? '' : '-l ',
				$vol_mount . "/$subdir"), 'r');
			if (!is_resource($ph)) return array();
			$result = stream_get_contents($ph);
			if (pclose($ph) != 0) return array();
			$snapshot_sizes = explode("\n", $result);
			foreach ($snapshot_sizes as $entry) {
				if (!strlen(trim($entry))) continue;
				list($size_kb, $path) = preg_split('/\s+/', $entry, 2);
				if (!strcmp(basename($path), $subdir)) continue;
				if ($delta)
					$snapshot_size[$subdir][basename($path)]['delta'] = $size_kb;
				else
					$snapshot_size[$subdir][basename($path)]['links'] = $size_kb;
			}
			return $snapshot_size;
		}

		if ($this->state['is_local_fs']) {
			$retention = new Retention($this->session_timestamp);
			$snapshots = $retention->ScanSnapshots($this->vol_mount);
			if (array_key_exists('path', $snapshots)) unset($snapshots['path']);
			$snapshot_size = array();
			foreach ($snapshots as $subdir => $entries) {
				if (!count($entries) || !strcmp('legacy', $subdir)) continue;
				$snapshot_size = 
					GetDiskUsage($subdir, $this->vol_mount,
					self::FORMAT_DU_KILOBYTE, $snapshot_size);
				$snapshot_size = 
					GetDiskUsage($subdir, $this->vol_mount,
					self::FORMAT_DU_KILOBYTE, $snapshot_size, false);
			}
			if (array_key_exists('legacy', $snapshots) &&
				count($snapshots['legacy'])) {
				foreach ($snapshots['legacy'] as $snapshot) {
					// TODO: calculate legacy snapshot sizes
					$snapshot_size['legacy'][$snapshot]['delta'] = 0;
					$snapshot_size['legacy'][$snapshot]['links'] = 0;
				}
			}
			$snapshots = $snapshot_size;

			if ($send_history) {
				$data = base64_encode(gzcompress(serialize($snapshots), 9));
				if (strlen($data) + 5 > self::MAX_CMD_LENGTH) {
					// TODO: Throw exception...  payload too large.
					// If this happens we need to increase MAX_CMD_LENGTH.
					$this->LogMessage(sprintf(
						'Snapshot history payload too large: %d',
							strlen($data)), LOG_WARNING);
				} else {
					$this->ControlSocketWrite(
						self::CTRL_CMD_SAVE_SNAPSHOTS, $data);
				}
			}
		} else {
			$this->ControlSocketWrite(self::CTRL_CMD_LOAD_SNAPSHOTS, 'Request snapshots');

			// Read reply, wait for snapshot list
			$this->ControlSocketRead($code, $data);

			switch ($code) {
			case self::CTRL_REPLY_SNAPSHOTS:
				$snapshots = unserialize(gzuncompress(base64_decode($data)));
				break;

			case self::CTRL_REPLY_ERROR:
				throw new ProtocolException(ProtocolException::CODE_ERROR,
					"$code:$data");
			}
		}

		return $snapshots;
	}

	// Delete specified snapshot
	public final function ControlDeleteSnapshot($snapshot)
	{
		$this->SetStatusCode(self::STATUS_DELETE_SNAPSHOT);

		if ($this->state['is_local_fs']) {
			if ($this->ExecProcess('delete', sprintf(self::FORMAT_DELETE_SNAPSHOT,
				$this->vol_mount, $snapshot), null, true) != 0)
				throw new ServiceException(ServiceException::CODE_DELETE_SNAPSHOT);
		} else {
			$this->ControlSocketWrite(self::CTRL_CMD_DELETE_SNAPSHOT, $snapshot);

			// Read reply, wait for snapshot list
			$this->ControlSocketRead($code, $data);

			switch ($code) {
			case self::CTRL_REPLY_OK:
				break;

			case self::CTRL_REPLY_ERROR:
				throw new ProtocolException(ProtocolException::CODE_ERROR,
					"$code:$data");
			}
		}
	}

	// Load snapshot history
	public final function LoadSnapshotHistory($filename = null)
	{
		$data = array();
		if ($filename == null) {
			$filename = sprintf(
				self::FORMAT_SNAPSHOT_HISTORY,
				self::PATH_RBSDATA);
		}
		if (!file_exists($filename)) return $data;
		$data = unserialize($this->FileReadLocked($filename));
		if ($data === false) return array();
		foreach ($data as $subdir => $entries)
			ksort($data[$subdir], SORT_NUMERIC);
		return $data;
	}

	// Save snapshot history
	public final function SaveSnapshotHistory($filename, $data)
	{
		if (!is_array($data)) return;
		$this->FileWriteLocked($filename, serialize($data));
	}

	// Push snapshot to queue
	public final function PushSnapshotForDelete($snapshot)
	{
		$snapshots = array();
		$fh = $this->FileLock(self::FILE_SNAPSHOT_DELETE_QUEUE);
		if (($contents = stream_get_contents($fh)) !== false)
			$snapshots = unserialize($contents);
		$snapshots[] = $snapshot;
		sort($snapshots, SORT_NUMERIC);
		if (ftruncate($fh, 0) === false) {
			$this->FileUnlock($fh);
			throw new FlockException(FlockException::CODE_TRUNCATE);
		}
		if (fseek($fh, SEEK_SET, 0) == -1) {
			$this->FileUnlock($fh);
			throw new FlockException(FlockException::CODE_SEEK);
		}
		if (fwrite($fh, serialize(array_unique($snapshots))) === false) {
			$this->FileUnlock($fh);
			throw new FlockException(FlockException::CODE_WRITE);
		}
		$this->FileUnlock($fh);
	}

	// Pop snapshot from queue
	public final function PopSnapshotForDelete()
	{
		$snapshot = false;
		$fh = $this->FileLock(self::FILE_SNAPSHOT_DELETE_QUEUE);
		if (($contents = stream_get_contents($fh)) !== false)
			$snapshots = unserialize($contents);
		if (is_array($snapshots))
			$snapshot = array_pop($snapshots);
		if (ftruncate($fh, 0) === false) {
			$this->FileUnlock($fh);
			throw new FlockException(FlockException::CODE_TRUNCATE);
		}
		if (fseek($fh, SEEK_SET, 0) == -1) {
			$this->FileUnlock($fh);
			throw new FlockException(FlockException::CODE_SEEK);
		}
		if (fwrite($fh, serialize($snapshots)) === false) {
			$this->FileUnlock($fh);
			throw new FlockException(FlockException::CODE_WRITE);
		}
		$this->FileUnlock($fh);
		return $snapshot;
	}

	// Return number of snapshots in delete queue
	public final function SnapshotCountForDelete()
	{
		if (!file_exists(self::FILE_SNAPSHOT_DELETE_QUEUE)) return 0;
		$snapshots = unserialize($this->FileReadLocked(self::FILE_SNAPSHOT_DELETE_QUEUE));
		return (!is_array($snapshots) || $snapshots === false ? 0 : count($snapshots));
	}

	// Does a snapshot exists in the delete queue?
	public final function SnapshotExistsForDelete($snapshot)
	{
		if (!file_exists(self::FILE_SNAPSHOT_DELETE_QUEUE)) return false;
		$snapshots = unserialize($this->FileReadLocked(self::FILE_SNAPSHOT_DELETE_QUEUE));
		return (!is_array($snapshots) || $snapshots === false ? false : in_array($snapshot, $snapshots));
	}

	// Load state history
	public final function LoadStateHistory()
	{
		$data = array();
		if (!file_exists(self::FILE_SESSION_HISTORY)) return $data;
		$data = unserialize($this->FileReadLocked(self::FILE_SESSION_HISTORY));
		return ($data === false ? array() : $data);
	}

	// Save state history
	private final function SaveStateHistory()
	{
		$data = $this->LoadStateHistory();
		ksort($this->state);
		array_unshift($data, $this->state);
		if (count($data) > self::MAX_SESSION_HISTORY) array_pop($data);
		$this->FileWriteLocked(self::FILE_SESSION_HISTORY, serialize($data));
	}
}

///////////////////////////////////////////////////////////////////////////////
//
// Global functions used by various client/server scripts
//
///////////////////////////////////////////////////////////////////////////////

// Handy error handler, for logging
function ErrorHandler($code, $error, $filename = null, $line = 0)
{
	global $rbs;

	$prefix = '';
	if ($filename != null) $prefix = sprintf('%s:%d: ', basename($filename), $line);
	if (is_object($rbs)) {
		$level = LOG_ERR;
		switch ($code) {
		case E_NOTICE:
		case E_USER_NOTICE:
			$level = LOG_NOTICE;
			break;
		case E_WARNING:
		case E_USER_WARNING:
			$level = LOG_WARNING;
			break;
		}
		$rbs->LogMessage($prefix . $error, $level);
	}
	else echo "$prefix$error\n";
}

// Handy un-caught exception handler, also for logging.  Terminates further execution.
function ExceptionHandler($exception)
{
	global $rbs;

	$message = sprintf('%s:%d: [%d] %s', basename($exception->getFile()),
		$exception->getLine(), $exception->getCode(), $exception->getMessage());

	if (!is_object($rbs)) echo("$message\n");
	else {
		$rbs->LogMessage($message, LOG_ERR);

		// Handy debug back-trace
		$trace = $exception->getTrace();
		foreach ($trace as $key => $frame) {
			$prefix = '';
			if (array_key_exists('class', $frame)) $prefix .= $frame['class'];
			if (array_key_exists('type', $frame)) $prefix .= $frame['type'];
			if (array_key_exists('function', $frame)) $prefix .= $frame['function'];

			$rbs->LogMessage(sprintf('%s:%d: %s',
				basename($frame['file']), $frame['line'], $prefix), LOG_DEBUG);
		}
	}

	// Un-caught exceptions are fatal
	exit(-1);
}

// Extract Suva/2 environment
function GetSuvaEnvironment(&$device, &$organization)
{
	if (!isset($_ENV['SUVA_DEVICE']) || !isset($_ENV['SUVA_ORGANIZATION'])) {
		fprintf(STDERR, "%s: Suva/2 environment not detected\n", basename($_SERVER['argv'][0]));
		exit(1);
	}

	$device = $_ENV['SUVA_DEVICE'];
	$organization = $_ENV['SUVA_ORGANIZATION'];
}

// vi: ts=4
?>
