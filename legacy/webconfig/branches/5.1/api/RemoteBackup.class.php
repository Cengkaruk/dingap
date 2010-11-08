<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2007 Point Clark Networks.
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

/**
 * Remote back-up client.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2005-2007, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('Engine.class.php');
require_once('File.class.php');
require_once('ShellExec.class.php');
require_once('Software.class.php');
require_once('Daemon.class.php');
require_once('Cron.class.php');
require_once('FileBrowser.class.php');
require_once(COMMON_CORE_DIR . '/scripts/rbs.inc.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * RemoteBackup.
 *
 * Remote back-up client.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2005-2007, Point Clark Networks
 */

class RemoteBackup extends Engine
{
	///////////////////////////////////////////////////////////////////////////////
	// C O N S T A N T S
	///////////////////////////////////////////////////////////////////////////////

	const CLIENT_NAME = 'rbs-client';
	const CLIENT_MODE0 = 0;
	const CLIENT_MODE1 = 1;
	const CMD_SCHEDULE = 'rbs-schedule.php';
	const FILE_CUSTOM_BACKUP_SELECTION = '/tmp/rbs-custom-backup.dat';
	const FILE_CUSTOM_RESTORE_SELECTION = '/tmp/rbs-custom-restore.dat';
	const FILE_CUSTOM = '%s/rbs_custom-%s-%s.ini';
	const FILE_HISTORY = '/var/lib/rbs/session-history.data';

	///////////////////////////////////////////////////////////////////////////////
	// F I E L D S
	///////////////////////////////////////////////////////////////////////////////

	private $rbs_client;

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * RemoteBackup constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		parent::__construct();

		require_once(GlobalGetLanguageTemplate(__FILE__));

		try {
			$this->rbs_client = new RemoteBackupService(self::CLIENT_NAME);
			$this->rbs_client->OpenState(null, true);
		} catch (WebconfigScriptException $e) {
			throw new EngineException($this->TranslateExceptionId($e->GetExceptionId()), COMMON_ERROR);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}
	}

	/**
	 * @access private
	 */

	function __destruct()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (is_object($this->rbs_client))
			$this->rbs_client->ResetState();

		parent::__destruct();
	}

	final public function TranslateExceptionId($id)
	{
		$tag = $id;
		if (strpos($id, '::') !== false) {
			list($name, $code) = explode('::', $id);
			$tag = 'REMOTEBACKUP_LANG_ERR_' . strtoupper(str_ireplace('exception', '', $name));
			$tag .= '_' . str_replace('CODE_', '', $code);
		}
		else if (defined($id)) $tag = $id;
		else if (strpos($id, ':') !== false) {
			$parts = explode(':', $tag);
			$tag = 'REMOTEBACKUP_LANG_ERR_' . $parts[0];
		}
		else $tag = "REMOTEBACKUP_LANG_ERR_$id";
		$string = $tag;
		eval("\$string = $tag;");

		// FIXME: Add translation tags.  No idea what they are, so
		// only the common ones are hacked in
		if ($tag == 'REMOTEBACKUP_LANG_ERR_SERVICE_ISCSI_DEVICE_NOT_FOUND')
			$string = "Unable to connect to remote disk";
		else if ($tag == 'REMOTEBACKUP_LANG_ERR_SERVICE_READ')
			$string = "Unable to connect";

		// Not translated, so make it a little prettier 
		if (preg_match("/_/", $string)) {
			$string = preg_replace("/REMOTEBACKUP_LANG_ERR/", " ", $string);
			$string = preg_replace("/_/", " ", $string);
			$string = strtolower($string);
			$string = ucwords($string);
		}

		return $string;
	}

	final public function TranslateStatusCode($id)
	{
		if (!strcmp($id, '0')) return REMOTEBACKUP_LANG_STATUS_UNKNOWN;
		$tag = 'REMOTEBACKUP_LANG_STATUS_' . $id;
		$string = $tag;
		eval("\$string = $tag;");

		// Not translated, so make it a little prettier 
		if (preg_match("/_/", $string)) {
			$string = preg_replace("/REMOTEBACKUP_LANG_STATUS/", " ", $string);
			$string = preg_replace("/_/", " ", $string);
			$string = strtolower($string);
			$string = ucwords($string);
		}

		// TODO: Add translation tags
		return $string;
	}

	final public function IsRunning()
	{
		return $this->rbs_client->IsRunning();
	}

	final public function StartBackup()
	{
		if ($this->IsRunning())
			throw new EngineException(REMOTEBACKUP_LANG_ERR_INSTANCE_RUNNING, COMMON_ERROR);
		$traditional = $this->GetClientMode();
		$options = array('background' => true);
		$shell = new ShellExec();
		$shell->Execute(sprintf('%s/scripts/%s.php',
			COMMON_CORE_DIR, self::CLIENT_NAME),
			$traditional ? '-T' : '', true, $options);
	}

	final public function StartRestore($snapshot, $restore_to)
	{
		if ($this->IsRunning())
			throw new EngineException(REMOTEBACKUP_LANG_ERR_INSTANCE_RUNNING, COMMON_ERROR);
		try {
			$folder = new Folder($restore_to, true);
			if (!$folder->Exists())
				throw new EngineException(REMOTEBACKUP_LANG_ERR_SERVICE_RESTORE_TO_INVALID, COMMON_ERROR);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage() . ": <b>$restore_to</b>", COMMON_ERROR);
		}
		$traditional = $this->GetClientMode();
		$options = array('background' => true);
		$shell = new ShellExec();
		$shell->Execute(sprintf('%s/scripts/%s.php',
			COMMON_CORE_DIR, self::CLIENT_NAME),
			(strlen($snapshot) > 0 ? "-r$snapshot " : '-r ') .
			($traditional ? '-T ' : ' ') . "-d \"$restore_to\"",
			true, $options);
	}

	final public function ResetVolume()
	{
		$traditional = $this->GetClientMode();
		$options = array('background' => true);
		$shell = new ShellExec();
		$shell->Execute(sprintf('%s/scripts/%s.php',
			COMMON_CORE_DIR, self::CLIENT_NAME),
			'-R ' .
			($traditional ? ' -T' : ''), true, $options);
	}

	final public function ResetHistory()
	{
		$file = new File(self::FILE_HISTORY, true);
		$file->Delete();
		$file->Create('root', 'root', '0644');
	}

	final public function DeleteSnapshot($snapshot)
	{
		$traditional = $this->GetClientMode();
		$options = array('background' => true);
		$shell = new ShellExec();
		$shell->Execute(sprintf('%s/scripts/%s.php',
			COMMON_CORE_DIR, self::CLIENT_NAME),
			"-D$snapshot " .
			($traditional ? ' -T' : ''), true, $options);
	}

	final public function DeleteSnapshotExists($snapshot)
	{
		return $this->rbs_client->SnapshotExistsForDelete($snapshot);
	}

	final public function GetMountPoint()
	{
		return $this->rbs_client->GetVolumeMountPoint();
	}

	final public function MountFilesystem()
	{
		if ($this->IsRunning()) {
			if ($this->rbs_client->IsVolumeMounted()) {
				$state = $this->rbs_client->GetState();
				if (array_key_exists('is_mount_mode', $state) &&
					$state['is_mount_mode']) {
					$this->UnmountFilesystem();
					while ($this->IsRunning()) sleep(1);
				}
			}
			else throw new EngineException(REMOTEBACKUP_LANG_ERR_INSTANCE_RUNNING, COMMON_ERROR);
		}

		$options = array('background' => true);
		$shell = new ShellExec();
		$shell->Execute(sprintf('%s/scripts/%s.php',
			COMMON_CORE_DIR, self::CLIENT_NAME),
			'-m', true, $options);
		return $this->rbs_client->GetVolumeMountPoint();
	}

	final public function UnmountFilesystem()
	{
		$shell = new ShellExec();
		$shell->Execute(sprintf('%s/scripts/%s.php',
			COMMON_CORE_DIR, self::CLIENT_NAME), '-u', true);
	}

	final public function Stop()
	{
		if (!$this->IsRunning()) return false;
		return true;
	}

	final public function GetStatus()
	{
		try {
			$this->rbs_client->UnserializeState();
			$state = $this->rbs_client->GetState();
			if (!array_key_exists('status_code', $state) ||
				is_integer($state['status_code']))
				return 'UNKNOWN';
			return $state['status_code'];
		} catch (WebconfigScriptException $e) {
			throw new EngineException($this->TranslateExceptionId($e->GetExceptionId()), COMMON_ERROR);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}
	}

	final public function GetStatusData()
	{
		try {
			$this->rbs_client->UnserializeState();
			$state = $this->rbs_client->GetState();
			if (!array_key_exists('status_data', $state)) return null;
			return $state['status_data'];
		} catch (WebconfigScriptException $e) {
			throw new EngineException($this->TranslateExceptionId($e->GetExceptionId()), COMMON_ERROR);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}
	}

	final public function GetError()
	{
		try {
			$this->rbs_client->UnserializeState();
			$state = $this->rbs_client->GetState();
			if (!array_key_exists('error_code', $state) ||
				is_integer($state['error_code']))
				return 'REMOTEBACKUP_LANG_ERR_NONE';
			return $state['error_code'];
		} catch (WebconfigScriptException $e) {
			throw new EngineException($this->TranslateExceptionId($e->GetExceptionId()), COMMON_ERROR);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}
	}

	final public function GetSessionMode()
	{
		try {
			$this->rbs_client->UnserializeState();
			$state = $this->rbs_client->GetState();
			if (array_key_exists('mode', $state)) return $state['mode'];
			return RBS_MODE_INVALID;
		} catch (WebconfigScriptException $e) {
			throw new EngineException($this->TranslateExceptionId($e->GetExceptionId()), COMMON_ERROR);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}
	}

	final public function SetKey($key)
	{
		if (!$this->IsKeyValid($key))
			throw new EngineException(REMOTEBACKUP_LANG_ERR_KEY_INVALID, COMMON_ERROR);

		$file = new File(RemoteBackupService::FILE_CONF, true);
		try {
			if (!$file->Exists())
				$file->Create('root', 'root', '0600');
			$this->ReplaceValue($file,
				'/^[[:space:]]*key[[:space:]]*=.*$/',
				sprintf("key = %s\n", md5($key)));
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}
	}

	final public function ResetKey($key)
	{
		$this->SetKey($key);
		$options = array('background' => true);
		$shell = new ShellExec();
		$shell->Execute(sprintf('%s/scripts/%s.php',
			COMMON_CORE_DIR, self::CLIENT_NAME), '-R', true, $options);
	}

	final public function GetKeyHash()
	{
		$file = new File(RemoteBackupService::FILE_CONF, true);
		try {
			return $file->LookupValue('/^[[:space:]]*key[[:space:]]*=[[:space:]]*/');
		} catch (FileNoMatchException $e) {
			return null;
		}
		catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}
	}

	final public function SetClientMode($mode)
	{
//		if (!$this->IsClientModeValid($mode))
//			throw new EngineException(REMOTEBACKUP_LANG_ERR_CMODE_INVALID, COMMON_ERROR);

		$file = new File(RemoteBackupService::FILE_CONF, true);
		try {
			if (!$file->Exists())
				$file->Create('root', 'root', '0600');
			$this->ReplaceValue($file,
				'/^[[:space:]]*mode[[:space:]]*=.*$/',
				sprintf("mode = %d\n", $mode));
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}
	}

	final public function GetClientMode()
	{
		$file = new File(RemoteBackupService::FILE_CONF, true);
		try {
			return $file->LookupValue('/^[[:space:]]*mode[[:space:]]*=[[:space:]]*/');
		} catch (FileNoMatchException $e) {
			return self::CLIENT_MODE0;
		}
		catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}
	}

	final public function LocaleTime($timestamp)
	{
		return strftime('%b %d %H:%M', $timestamp);
	}

	final public function GetSessionHistory()
	{
		return $this->rbs_client->LoadStateHistory();
	}

	final public function GetBackupHistory($raw_output = false)
	{
		$history = $this->rbs_client->LoadSnapshotHistory();
		if ($raw_output) return $history;
		$snapshots = array();
		foreach ($history as $subdir => $snapshot) {
			switch ($subdir) {
			case 'daily':
				$name = LOCALE_LANG_DAILY;
				break;
			case 'weekly':
				$name = LOCALE_LANG_WEEKLY;
				break;
			case 'monthly':
				$name = LOCALE_LANG_MONTHLY;
				break;
			case 'yearly':
				$name = LOCALE_LANG_YEARLY;
				break;
			case 'legacy':
				$name = WEB_LANG_LEGACY;
				break;
			default:
				$name = null;
				break;
			}
			if ($name == null) continue;

			foreach ($snapshot as $timestamp => $sizes) {
				$path = strcmp($subdir, 'legacy') ? "$subdir/$timestamp" : $timestamp;
				$snapshots[$path] = sprintf(
					'%s: %s (%.02f MB)', $name,
					$this->LocaleTime($timestamp),
					(float)$sizes['links'] / 1024.0);
			}
		}
		return $snapshots;
	}

	final public function RefreshBackupHistory()
	{
		if ($this->IsRunning()) return $this->GetBackupHistory();
		$shell = new ShellExec();
		$shell->Execute(sprintf('%s/scripts/%s.php',
			COMMON_CORE_DIR, self::CLIENT_NAME), '-H', true);
		return $this->GetBackupHistory();
	}

	final public function SetBackupSchedule($window, $daily, $weekly, $monthly, $yearly)
	{
		if (!$this->IsWindowScheduleValid($window))
			throw new EngineException(REMOTEBACKUP_LANG_ERR_WINDOW_SCHEDULE_INVALID, COMMON_ERROR);

		$file = new File(RemoteBackupService::FILE_CONF, true);
		try {
			if (!$file->Exists())
				$file->Create('root', 'root', '0600');
			$schedule = array('daily' => $daily, 'weekly' => $weekly,
				'monthly' => $monthly, 'yearly' => $yearly,
				'window' => $window);
			$this->ReplaceValue($file,
				'/^[[:space:]]*auto-backup-schedule[[:space:]]*=.*$/',
				sprintf("auto-backup-schedule = \"%s\"\n", base64_encode(serialize($schedule))));
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}

		$cron = new Cron();

		try {
			if ($cron->ExistsCrondConfiglet('app-remote-backup'))
				$cron->DeleteCrondConfiglet('app-remote-backup');
			$cron->AddCrondConfigletByParts('app-remote-backup',
				0, $window * 2, '*', '*', '*', 'root',
				sprintf('%s/scripts/%s >/dev/null 2>&1',
					COMMON_CORE_DIR, self::CMD_SCHEDULE));
		} catch(Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
	}

	final public function GetBackupSchedule()
	{
		$file = new File(RemoteBackupService::FILE_CONF, true);
		try {
			$value = $file->LookupValue('/^[[:space:]]*auto-backup-schedule[[:space:]]*=[[:space:]]*/');
			$schedule = unserialize(trim(base64_decode($value), '"'));
			if (array_key_exists('days', $schedule)) {
				unset($schedule['days']);
				$schedule['daily'] = 7;
				$schedule['weekly'] = 0;
				$schedule['monthly'] = 0;
				$schedule['yearly'] = 0;
			}
			return $schedule;
		} catch (FileNoMatchException $e) {
			return array('daily' => 0, 'weekly' => 0, 'monthly' => 0, 'yearly' => 0, 'window' => 0);
		}
		catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}
	}

	final public function EnableBackupSchedule($enable)
	{
		$file = new File(RemoteBackupService::FILE_CONF, true);
		try {
			if (!$file->Exists())
				$file->Create('root', 'root', '0600');
			$this->ReplaceValue($file,
				'/^[[:space:]]*auto-backup[[:space:]]*=.*$/',
				 sprintf("auto-backup = %d\n", $enable));
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}

		if ($enable) return;

		$cron = new Cron();

		try {
			if (!$cron->ExistsCrondConfiglet('app-remote-backup')) return;
			$cron->DeleteCrondConfiglet('app-remote-backup');
		} catch(Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
	}

	final public function IsBackupScheduleEnabled()
	{
		$file = new File(RemoteBackupService::FILE_CONF, true);
		try {
			return $file->LookupValue('/^[[:space:]]*auto-backup[[:space:]]*=[[:space:]]*/');
		} catch (FileNoMatchException $e) {
			return 0;
		}
		catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}
	}

	final public function SetNotifyEmail($email)
	{
		$file = new File(RemoteBackupService::FILE_CONF, true);
		try {
			if (!$file->Exists())
				$file->Create('root', 'root', '0600');
			$this->ReplaceValue($file,
				'/^[[:space:]]*email-notify-address[[:space:]]*=.*$/',
				 sprintf("email-notify-address = '%s'\n", $email));
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}
	}

	final public function GetNotifyEmail()
	{
		$file = new File(RemoteBackupService::FILE_CONF, true);
		try {
			$value = $file->LookupValue(
				'/^[[:space:]]*email-notify-address[[:space:]]*=[[:space:]]*/');
			return trim($value, '\'"');
		} catch (FileNoMatchException $e) {
			return null;
		}
		catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}
		return null;
	}

	final public function EnableNotifyOnError($enable)
	{
		$file = new File(RemoteBackupService::FILE_CONF, true);
		try {
			if (!$file->Exists())
				$file->Create('root', 'root', '0600');
			$this->ReplaceValue($file,
				'/^[[:space:]]*email-notify-error[[:space:]]*=.*$/',
				 sprintf("email-notify-error = %d\n", $enable));
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}
	}

	final public function IsNotifyOnErrorEnabled()
	{
		$file = new File(RemoteBackupService::FILE_CONF, true);
		try {
			return $file->LookupValue(
				'/^[[:space:]]*email-notify-error[[:space:]]*=[[:space:]]*/');
		} catch (FileNoMatchException $e) {
			return 0;
		}
		catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}
	}

	final public function EnableNotifySummary($enable)
	{
		$file = new File(RemoteBackupService::FILE_CONF, true);
		try {
			if (!$file->Exists())
				$file->Create('root', 'root', '0600');
			$this->ReplaceValue($file,
				'/^[[:space:]]*email-notify-summary[[:space:]]*=.*$/',
				 sprintf("email-notify-summary = %d\n", $enable));
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}
	}

	final public function IsNotifySummaryEnabled()
	{
		$file = new File(RemoteBackupService::FILE_CONF, true);
		try {
			return $file->LookupValue(
				'/^[[:space:]]*email-notify-summary[[:space:]]*=[[:space:]]*/');
		} catch (FileNoMatchException $e) {
			return 0;
		}
		catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}
	}

	final public function GetFilesystemQuickPicks($mode = RBS_MODE_BACKUP)
	{
		// XXX: Included here to keep global bloat to a minimum
		require('RemoteBackup.list.php');

		$quick_picks = $RBS_QUICK_PICKS;
		try {
			$folder = new Folder(RemoteBackupService::DIR_CONFIG_NODES, true);
			$files = $folder->GetListing();
			foreach ($quick_picks as $name => $desc) {
				$filename = sprintf('%s-%s.ini', $name,
					$mode == RBS_MODE_BACKUP ? 'backup' : 'restore');
				if (!in_array($filename, $files)) continue;
				$desc['enabled'] = true;
				$path = sprintf('%s/%s',
					RemoteBackupService::DIR_CONFIG_NODES, $filename);
				$file = new File($path, true);
				try {
					$desc['enabled'] = $file->LookupValue('/^[[:space:]]*enabled[[:space:]]*=[[:space:]]*/');
				} catch (FileNoMatchException $e) { }
				if (preg_match('/^[\'"]*false/i', $desc['enabled'])) $desc['enabled'] = false;
				else if (preg_match('/^[\'"]*no/i', $desc['enabled'])) $desc['enabled'] = false;
				else if (preg_match('/^[\'"]0/', $desc['enabled'])) $desc['enabled'] = false;
				if ($desc['type'] == RBS_TYPE_DATABASE) {
					try {
						$desc['username'] = trim($file->LookupValue(
							'/^[[:space:]]*username[[:space:]]*=[[:space:]]*/'), '\'"');
					} catch (FileNoMatchException $e) { }
					try {
						$desc['password'] = trim($file->LookupValue(
							'/^[[:space:]]*password[[:space:]]*=[[:space:]]*/'), '\'"');
					} catch (FileNoMatchException $e) { }
					try {
						$desc['db-name'] = trim($file->LookupValue(
							'/^[[:space:]]*db-name[[:space:]]*=[[:space:]]*/'), '\'"');
					} catch (FileNoMatchException $e) { }
				}
				$quick_picks[$name] = $desc;
			}
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}
		return $quick_picks;
	}

	final public function SetFilesystemQuickPicks($selection, $mode = RBS_MODE_BACKUP)
	{
		// XXX: Included here to keep global bloat to a minimum
		require('RemoteBackup.list.php');

		$quick_picks = $RBS_QUICK_PICKS;
		try {
			$folder = new Folder(RemoteBackupService::DIR_CONFIG_NODES, true);
			$files = $folder->GetListing();
			foreach ($quick_picks as $name => $desc) {
				$file = new File(sprintf('%s/%s-%s.ini',
					RemoteBackupService::DIR_CONFIG_NODES, $name,
					$mode == RBS_MODE_BACKUP ? 'backup' : 'restore'), true);
				if (!array_key_exists($name, $selection)) {
					if ($file->Exists()) {
						$this->ReplaceValue($file,
							'/^[[:space:]]*enabled[[:space:]]*=.*$/',
							"enabled = false\n");
					}
					continue;
				}
				if (!$file->Exists())
					$file->Create('root', 'root', '0600');
				switch ($desc['type']) {
				case RBS_TYPE_FILEDIR:
					$this->ReplaceValue($file,
						'/^[[:space:]]*type[[:space:]]*=.*$/',
						"type = RBS_TYPE_FILEDIR\n");
					$this->ReplaceValue($file,
						'/^[[:space:]]*path[[:space:]]*=.*$/',
						sprintf("path = \"%s\"\n", $desc['path']));
					break;
				case RBS_TYPE_DATABASE:
					$this->ReplaceValue($file,
						'/^[[:space:]]*type[[:space:]]*=.*$/',
						"type = RBS_TYPE_DATABASE\n");
					switch ($desc['sub-type']) {
					case RBS_SUBTYPE_DATABASE_MYSQL:
						$this->ReplaceValue($file,
							'/^[[:space:]]*sub-type[[:space:]]*=.*$/',
							"sub-type = RBS_SUBTYPE_DATABASE_MYSQL\n");
						break;
					}
					break;
				case RBS_TYPE_MAIL:
					$this->ReplaceValue($file,
						'/^[[:space:]]*type[[:space:]]*=.*$/',
						"type = RBS_TYPE_MAIL\n");
					switch ($desc['sub-type']) {
					case RBS_SUBTYPE_MAIL_CYRUSIMAP:
						$this->ReplaceValue($file,
							'/^[[:space:]]*sub-type[[:space:]]*=.*$/',
							"sub-type = RBS_SUBTYPE_MAIL_CYRUSIMAP\n");
						break;
					}
					break;
				}
				$this->ReplaceValue($file,
					'/^[[:space:]]*enabled[[:space:]]*=.*$/',
					"enabled = true\n");
				$this->ReplaceValue($file,
					'/^[[:space:]]*mode[[:space:]]*=.*$/',
					sprintf("mode = %s\n", $mode == RBS_MODE_BACKUP ?
					'RBS_MODE_BACKUP' : 'RBS_MODE_RESTORE'));
			}
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}
	}

	final public function SetDatabaseQuickPick($type, $user, $pass, $db_list, $mode = RBS_MODE_BACKUP)
	{
		require('RemoteBackup.list.php');

		$name = null;
		switch ($type) {
		case RBS_SUBTYPE_DATABASE_MYSQL:
			foreach ($RBS_QUICK_PICKS as $key => $qp) {
				if ($qp['type'] != RBS_TYPE_DATABASE || $qp['sub-type'] != $type) continue;
				$name = $key;
				break;
			}
			break;
		}
		if ($name == null)
			throw new EngineException(REMOTEBACKUP_LANG_ERR_DB_INVALID . ' ' . $type, COMMON_ERROR);

		$file = new File(sprintf('%s/%s-%s.ini',
			RemoteBackupService::DIR_CONFIG_NODES, $name,
			$mode == RBS_MODE_BACKUP ? 'backup' : 'restore'), true);
		if (!$file->Exists())
			$file->Create('root', 'root', '0600');
		$this->ReplaceValue($file,
			'/^[[:space:]]*type[[:space:]]*=.*$/',
			"type = RBS_TYPE_DATABASE\n");
		$this->ReplaceValue($file,
			'/^[[:space:]]*sub-type[[:space:]]*=.*$/',
			"sub-type = RBS_SUBTYPE_DATABASE_MYSQL\n");
		$this->ReplaceValue($file,
			'/^[[:space:]]*username[[:space:]]*=.*$/',
			"username = \"$user\"\n");
		$this->ReplaceValue($file,
			'/^[[:space:]]*password[[:space:]]*=.*$/',
			"password = \"$pass\"\n");
		$db_name = '';
		foreach ($db_list as $db) $db_name .= "$db:";
		$this->ReplaceValue($file,
			'/^[[:space:]]*db-name[[:space:]]*=.*$/',
			sprintf("db-name = \"%s\"\n", trim($db_name, ':')));
		$this->ReplaceValue($file,
			'/^[[:space:]]*mode[[:space:]]*=.*$/',
			sprintf("mode = %s\n", $mode == RBS_MODE_BACKUP ?
			'RBS_MODE_BACKUP' : 'RBS_MODE_RESTORE'));
	}

	final public function SetDatabaseRestoreConfig($type, $user, $pass, $db_list)
	{
		switch ($type) {
		case RBS_SUBTYPE_DATABASE_MYSQL:
			break;
		default:
			throw new EngineException(REMOTEBACKUP_LANG_ERR_DB_INVALID . ' ' . $type, COMMON_ERROR);
		}

		$file = new File(sprintf('%s/db-restore-%d.ini',
			RemoteBackupService::DIR_CONFIG_NODES, $type), true);
		if ($file->Exists()) $file->Delete();
		$file->Create('root', 'root', '0600');
		$this->ReplaceValue($file,
			'/^[[:space:]]*type[[:space:]]*=.*$/',
			"type = RBS_TYPE_DATABASE\n");
		$this->ReplaceValue($file,
			'/^[[:space:]]*sub-type[[:space:]]*=.*$/',
			"sub-type = RBS_SUBTYPE_DATABASE_MYSQL\n");
		$this->ReplaceValue($file,
			'/^[[:space:]]*username[[:space:]]*=.*$/',
			"username = \"$user\"\n");
		$this->ReplaceValue($file,
			'/^[[:space:]]*password[[:space:]]*=.*$/',
			"password = \"$pass\"\n");
		$db_name = '';
		foreach ($db_list as $db) $db_name .= "$db:";
		$this->ReplaceValue($file,
			'/^[[:space:]]*db-name[[:space:]]*=.*$/',
			sprintf("db-name = \"%s\"\n", trim($db_name, ':')));
	}

	final public function SetDebug($debug = true)
	{
		$this->rbs_client->SetDebug($debug);
	}

	final public function IsDebug()
	{
		return $this->rbs_client->IsDebug();
	}

	final public function WebDialogDump($dump)
	{
		if (!$this->IsDebug()) return;

		ob_start();
		var_dump($dump);
		$contents = explode("\n", ob_get_clean());
		$step = 15;
		$indent = 0;
		$css = 'padding: 0px; margin: 0px; left: %dpx;';
		$output = '';
		$types = array('/^(array)/', '/^(bool)/', '/^(float)/', '/^(int)/', '/^(string)/');
		$types_style = '<b>\1</b>';
		$quoted_style = "\\1\"<font style='color: #00aa00; font-weight: bold;'>\\2</font>\"";
		foreach ($contents as $line) {
			$line = trim($line);
			if (!strlen($line)) continue;
			if (preg_match('/^}/', $line)) $indent -= $step;
			$line = preg_replace($types, $types_style, $line);
			$line = preg_replace('/([\[ ]+)"(.*)"/', $quoted_style, $line);
			$output .= sprintf("<div style='%s'>%s</div>\n",
				sprintf($css, $indent), $line);
			if (preg_match('/{$/', $line)) $indent += $step;
		}
		WebDialogWarning($output);
	}

	final public function SaveCustomSelection($mode = RBS_MODE_BACKUP)
	{
		$file_browser = new FileBrowser();
		try {
			$config = $file_browser->LoadConfiguration($mode == RBS_MODE_BACKUP ?
				self::FILE_CUSTOM_BACKUP_SELECTION : self::FILE_CUSTOM_RESTORE_SELECTION);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}

		$this->ResetCustomSelection($mode);

		foreach ($config as $hash => $entry) {
			if (!is_array($entry)) continue;
			if (!array_key_exists('selected', $entry) || !$entry['selected']) continue;
			$filename = sprintf(self::FILE_CUSTOM, RemoteBackupService::DIR_CONFIG_NODES,
				$hash, $mode == RBS_MODE_BACKUP ? 'backup' : 'restore');
			$file = new File($filename, true);
			try {
				$file->Create('root', 'root', '0600');
				$file->AddLines(sprintf("type = RBS_TYPE_FILEDIR\npath = \"%s\"\nmode = %s\nenabled = true\n",
					trim(sprintf("%s/%s", $entry['path'], $entry['filename']), '/'),
					$mode == RBS_MODE_BACKUP ? 'RBS_MODE_BACKUP' : 'RBS_MODE_RESTORE'));
			} catch (Exception $e) {
				throw new EngineException($e->getMessage(), COMMON_ERROR);
			}
		}
	}

	final public function IsCustomSelectionEnabled($mode = RBS_MODE_BACKUP)
	{
		try {
			$folder = new Folder(RemoteBackupService::DIR_CONFIG_NODES, true);
			$files = $folder->GetListing();
			foreach ($files as $filename) {
				if (!preg_match(sprintf('/^rbs_custom-[0-9a-f]{32}-%s.ini$/',
					$mode == RBS_MODE_BACKUP ? 'backup' : 'restore'), $filename)) continue;
				$file = new File(sprintf('%s/%s', RemoteBackupService::DIR_CONFIG_NODES, $filename), true);
				try {
					$value = trim($file->LookupValue('/^[[:space:]]*enabled[[:space:]]*=[[:space:]]*/'));
					if (!strcmp($value, '1') || !strcasecmp($value, 'true') || !strcasecmp($value, 'yes'))
						return true;
				} catch (FileNoMatchException $e) { }
			}
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}

		return false;
	}

	final public function EnableCustomSelection($mode, $enable)
	{
		try {
			$folder = new Folder(RemoteBackupService::DIR_CONFIG_NODES, true);
			$files = $folder->GetListing();
			foreach ($files as $filename) {
				if (!preg_match(sprintf('/^rbs_custom-[0-9a-f]{32}-%s.ini$/',
					$mode == RBS_MODE_BACKUP ? 'backup' : 'restore'), $filename)) continue;
				$file = new File(sprintf('%s/%s', RemoteBackupService::DIR_CONFIG_NODES, $filename), true);
				try {
					$this->ReplaceValue($file, '/^[[:space:]]*enabled[[:space:]]*=.*$/',
						sprintf("enabled = %s\n", $enable ? 'true' : 'false'));
				} catch (Exception $e) {
					throw new EngineException($e->getMessage(), COMMON_ERROR);
				}
			}
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}
	}

	final public function ResetCustomSelection($mode)
	{
		$shell = new ShellExec();
		try {
			$shell->Execute('rm', sprintf(self::FILE_CUSTOM,
				RemoteBackupService::DIR_CONFIG_NODES, '*',
				$mode == RBS_MODE_BACKUP ? 'backup' : 'restore'), true);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}
	}

///////////////////////////////////////////////////////////////////////////////
// P R I V A T E
///////////////////////////////////////////////////////////////////////////////

	final private function ReplaceValue($file, $search, $replace)
	{
		try {
			$file->LookupValue($search);
			$file->ReplaceLines($search, $replace);
		} catch (FileNoMatchException $e) {
			$file->AddLines($replace);
		}
	}

///////////////////////////////////////////////////////////////////////////////
// V A L I D A T I O N
///////////////////////////////////////////////////////////////////////////////

	final public function IsKeyValid($key)
	{
		if (!strlen($key)) return false;
		return true;
	}

	final public function IsWindowScheduleValid($window)
	{
		if ($window < 0 || $window > 11) return false;
		return true;
	}

	final public function IsMysqlInstalled()
	{
		try {
			$software = new Software('mysql-server');
			if (!$software->IsInstalled()) return false;
			$daemon = new Daemon('mysqld');
			if (!$daemon->GetRunningState()) $daemon->SetRunningState(true);
		} catch (Exception $e) {
			throw new EngineException(REMOTEBACKUP_LANG_ERR_MYSQL_UNAVAILABLE, COMMON_ERROR);
		}
		return true;
	}
}

// vim: syntax=php ts=4
?>
