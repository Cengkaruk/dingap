#!/usr/webconfig/bin/php -q
<?php
///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2007-2009 Point Clark Networks
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

// Require service class
require_once(dirname($_SERVER['argv'][0]) . '/rbs.inc.php');

// Parse command-line options
$options = getopt('t:r::d:D::l:hTvmuHR');

if (array_key_exists('h', $options)) {
	printf("%s: Remote Backup/Restore Client Usage Help\n",
		basename($_SERVER['argv'][0]));
	printf("Copyright (C) 2007-2008 Point Clark Networks\n");
	printf("-t N      Set session time-limit in second(s), 0 = no limit.\n");
	printf("-r[N]     Enable restore mode, default mode is backup.\n");
	printf("          Optionally, N can be set to the desired snapshot.\n");
	printf("-d PATH   Specify PATH to restore to, default: /\n");
	printf("-T        Traditional high-speed mode.\n");
	printf("-m        Mount file-system.\n");
	printf("-u        Unmount file-system.\n");
	printf("-H        Update backup history.\n");
	printf("-D[N]     Delete snapshot(s).\n");
	printf("-R        Reset backup history (DELETE ALL BACKUP DATA!)\n");
	printf("-l F      Log to a file specified by F.\n");
	printf("-v        Verbose mode (debug output).\n");
	exit(RBS_RESULT_SUCCESS);
}

// Need pcntl_signal: PHP must have been built with --enable-pcntl
if (!function_exists('pcntl_signal')) {
	syslog(LOG_ERR, sprintf('%s: this script requires PHP with --enable-pcntl support!',
		basename($_SERVER['argv'][0])));
	exit(RBS_RESULT_GENERAL_FAILURE);
}

// Signal handler
function SignalHandler($signal)
{
	global $rbs;
	// XXX: This doesn't work...
	if (is_object($rbs)) $rbs->LogMessage("Trapped signal: $signal");
}

pcntl_signal(SIGHUP, 'SignalHandler');
pcntl_signal(SIGINT, 'SignalHandler');
pcntl_signal(SIGTERM, 'SignalHandler');
pcntl_signal(SIGQUIT, 'SignalHandler');

// Set maximum session time-limit in seconds
if (array_key_exists('t', $options)) set_time_limit($options['t']);
else set_time_limit(0);

// Create an RBS client
$rbs = new RemoteBackupService(basename($_SERVER['argv'][0]), false, false, false);

// Enable debug mode?
if (array_key_exists('v', $options)) $rbs->SetDebug();
else $rbs->SetDebug(false);

// Set logging options
$log_options['stderr'] = true;
$log_options['syslog'] = array('facility' => RemoteBackupService::SYSLOG_FACILITY);
if (array_key_exists('l', $options)) $log_options['log_file'] = $options['l'];
$rbs->LogOptions($log_options);

// Register custom error handler
set_error_handler('ErrorHandler');

// Register custom exception handler
set_exception_handler('ExceptionHandler');

// Set state ownership
$rbs->SetStateOwner('root', 'webconfig');

// Open state
$rbs->OpenState();

// Signal running mount process to umount and exit
if (array_key_exists('u', $options)) {
	$rbs->SetMountMode();
	exit(RBS_RESULT_SUCCESS);
}

// Queue snapshot for delete
if (array_key_exists('D', $options)) {
	if ($options['D']) $rbs->PushSnapshotForDelete($options['D']);
}

// Lock instance
$rbs->LockInstance();

// Reset state
$rbs->ResetState();

// Set default mode
$mode = 'Backup';
$rbs->SetRestoreMode(false);
$snapshot = null;

// Set client mode; backup mode is the default
if (array_key_exists('r', $options)) {
	// Set restore mode
	$mode = 'Restore';
	$rbs->SetRestoreMode();
	if ($options['r']) $snapshot = $options['r'];
	else {
		$snapshot = null;
		$snapshots = $rbs->LoadSnapshotHistory();
		$subdirs = array('daily', 'weekly', 'monthly', 'yearly', 'legacy');
		foreach ($subdirs as $subdir) {
			if (!array_key_exists($subdir, $snapshots) ||
				!count($snapshots[$subdir])) continue;
			end($snapshots[$subdir]);
			if (strcmp('legacy', $subdir))
				$snapshot = "$subdir/" . key($snapshots[$subdir]);
			else
				$snapshot = key($snapshots[$subdir]);
			break;
		}
		if ($snapshot == null) {
			// TODO: throw exception
			$rbs->LogMessage('Unable to restore; no previous backups.', LOG_ERR);
			exit(RBS_RESULT_GENERAL_FAILURE);
		}
	}

	// Set 'restore-to' path if we're doing a restore
	try {
		if (array_key_exists('d', $options)) {
			$restore_to = $options['d'];
			if (!is_dir($restore_to))
				throw new ServiceException(ServiceException::CODE_RESTORE_TO_INVALID);
		} else $restore_to = '/';
	} catch (ServiceException $e) {
		if ($e->getCode() == ServiceException::CODE_RESTORE_TO_INVALID)
			$rbs->LogMessage('Restoration path is invalid: "' . $restore_to . '"', LOG_ERR);
		$rbs->SetErrorCode($e->GetExceptionId());
		exit(RBS_RESULT_SERVICE_ERROR);
	}
} else if (array_key_exists('m', $options)) {
	// Set mount mode
	$mode = 'Mount';
	$rbs->SetMountMode(true);
} else if (array_key_exists('H', $options)) {
	// Set refresh history mode
	$mode = 'Refresh History';
	$rbs->SetHistoryMode();
} else if (array_key_exists('R', $options)) {
	// Set reset mode
	$mode = 'Reset Data';
	$rbs->SetResetMode();
} else if (array_key_exists('D', $options)) {
	// Set delete snapshot mode
	$mode = 'Delete Snapshot';
	$rbs->SetDeleteMode();
	if (!$rbs->SnapshotCountForDelete()) {
		$rbs->LogMessage('No snapshots in queue for delete.', LOG_DEBUG);
		exit(RBS_RESULT_SUCCESS);
	}
}

// Say hello
$rbs->LogMessage("Initializing RBS Client: $mode Mode");

// Set file-system mode, local or remote mount:
if (array_key_exists('T', $options)) $rbs->SetFilesystemLocal(false);
else $rbs->SetFilesystemLocal();

try {
	// Load and validate configuration
	$config = $rbs->LoadConfiguration();

	if (!array_key_exists('key', $config) || !isset($config['key']))
		throw new ServiceException(ServiceException::CODE_CONFIG_NOFSKEY);

	// Generate rsync include/exclude files
	if (!$rbs->IsMountMode() && !array_key_exists('R', $options)) {
		$rbs->GenerateRsyncFiles();
		// Perform a system configuration backup if enabled
		if ($rbs->IsBackupMode()) {
			$node = $rbs->GetConfigurationNodes(RBS_TYPE_FILEDIR, 0, 'rbs_qp_sysconfig-backup');
			if (count($node) == 1 && $node[0]['enabled']) {
				require(dirname($_SERVER['argv'][0]) . '/../api/BackupRestore.class.php');
				$archive = null;
				$br = new BackupRestore();
				if (($archive = $br->Backup())) $br->Purge();
				unset($br);
			}
		}
	}

	// Establish control connection
	$rbs->ControlSocketConnect();

	// Request backup session
	$rbs->ControlRequestSession();

	if ($rbs->IsResetMode()) {
		// Reset (delete) all backup history!
		$rbs->ControlRequestReset();
		$rbs->ControlSendSessionLogout();
		exit(RBS_RESULT_SUCCESS);
	}

	// Request provision update
	$mkfs = false;
	if (!$rbs->IsMountMode()) {
		$mkfs = ($rbs->ControlRequestProvision() ==
			RemoteBackupService::CTRL_REPLY_PROVISION_MKFS) ? true : false;
	}

	// In restore mode, create a restore directory for non-root paths
	if ($rbs->IsRestoreMode() && strcmp($restore_to, '/')) {
		// If the destination looks like a home directory,
		// attempt to extract user in order to set ownership below
		$user = 'root';
		if (preg_match('|^/home/(.*)$|', $restore_to, $match))
			$user = $match[1];
		if (($timestamp = strstr($snapshot, '/')) === false)
			$timestamp = $snapshot;
		$restore_to = sprintf('%s/restore-%s', $restore_to,
			strftime('%m%d%y-%H%M%S', trim($timestamp, '/')));
		// TODO: Check the result of the following operations and
		// throw an exception if any of them fail.
		@mkdir($restore_to);
		@chown($restore_to, $user);
		@chgrp($restore_to, $user);
		@chmod($restore_to, 0700);
	}

	if ($rbs->IsFilesystemLocal()) {
		// Request backup data export notification
		$rbs->ControlRequestExport();

		// Perform iSCSI login
		$rbs->iScsiLogin();

		// Initialize encrypted volume
		try {
			$fifo = '/tmp/rbs-keyfifo';
			$fifo_pid = 0;
			$fifo_pid = $rbs->FifoWrite($fifo, $config['key']);
			$rbs->CreateEncryptedVolume($fifo);
			$rbs->FifoWait($fifo_pid);
		} catch (Exception $e) {
			unlink($fifo);
			if ($fifo_pid > 0) $rbs->FifoWait($fifo_pid);
			throw $e;
		}

		// Make (format) file-system?
		if ($mkfs)
			$rbs->FormatFilesystem();
		else if ($rbs->IsBackupMode()) {
			// Validate keyfile and verify filesystem integrity
			$rbs->VerifyFilesystem();

			// Possibly extend volume
			$rbs->ExtendFilesystem();
		}

		// Mount filesystem
		$rbs->MountFilesystem();
	}
	else $rbs->ControlRequestMount($config['key']);

	$exitcode = 0;

	if ($rbs->IsMountMode()) {
		$tick = 2;
		$seconds = 0;
		// Mount file-system and sleep until requested to exit
		while ($rbs->IsMountMode()) {
			sleep($tick);
			try {
				$rbs->ControlSendPing();
			} catch (ProtocolException $e) {
				$rbs->LogMessage('Ping failed, exting mount mode.', LOG_ERR);
				break;
			}
			$rbs->UnserializeState();
			$seconds += $tick;
			if ($seconds >= RemoteBackupService::TIMEOUT_MOUNT) {
				$rbs->LogMessage(sprintf('Mount time-out after %d seconds.',
					RemoteBackupService::TIMEOUT_MOUNT), LOG_WARNING);
				break;
			}
		}
		// Re-enable this flag for the state history record
		$rbs->SetMountMode(true);
	} else if ($rbs->IsDeleteMode()) {
		$rbs->LogMessage(sprintf('Deleting %d snapshot(s).',
			$rbs->SnapshotCountForDelete()), LOG_DEBUG);
		while (($snapshot = $rbs->PopSnapshotForDelete()) !== false &&
			strlen(trim($snapshot))) {
			$rbs->LogMessage('Deleting snapshot: ' . $snapshot, LOG_DEBUG);
			$rbs->ControlDeleteSnapshot($snapshot);
		}
	} else if ($rbs->IsBackupMode() || $rbs->IsRestoreMode()) {
		try {
			if ($rbs->IsBackupMode()) {
				// First, purge any snapshots pending deletion...
				while (($snapshot = $rbs->PopSnapshotForDelete()) !== false && $snapshot != 0)
					$rbs->ControlDeleteSnapshot($snapshot);

				// Backup databases
				// XXX: Database dumps must be done before files/mail
				$rbs->BackupDatabases();

				// Purge previous failed backup snapshots
				$rbs->ControlPurgeFailedSnapshots();

				// Notify SDN that a backup is starting now...
				$rbs->ControlBackupStart();

				// Load data retention policy and execute plan
				$policy = unserialize(trim(base64_decode($config['auto-backup-schedule']), '"'));
				$backup_plan = $rbs->ControlRetentionPrepare($policy);
				foreach ($backup_plan as $snapshot) {
					if (!count($snapshot)) continue;
					// Backup files/directories
					if ($rbs->IsFilesystemLocal())
						$dst = sprintf('%s/%s',
							RemoteBackupService::VOLUME_MOUNT_POINT, $snapshot['dst']);
					else
						$dst = sprintf('%s/%s',
							RemoteBackupService::RSYNC_URI, $snapshot['dst']);
					$rbs->RsyncData('/', $dst, $snapshot['src']);
					// Backup mail servers
					$rbs->BackupMailServers($dst, $snapshot['src']);
				}
			} else {
				// Restore files/directories
				if ($rbs->IsFilesystemLocal())
					$rbs->RsyncData(RemoteBackupService::VOLUME_MOUNT_POINT . "/$snapshot", $restore_to);
				else
					$rbs->RsyncData(RemoteBackupService::RSYNC_URI . "/$snapshot", $restore_to);
				// Restore databases
				$rbs->RestoreDatabases($restore_to);
				// Restore mail servers
				$rbs->RestoreMailServers($restore_to);
			}
		} catch (ServiceException $e) {
			$rbs->SetErrorCode($e->GetExceptionId());
			$rbs->LogMessage(sprintf('[%s] %s', $e->GetExceptionId(),
				$e->getMessage()), LOG_ERR);
			if ($e->getCode() != ServiceException::CODE_RSYNC &&
				$e->getCode() != ServiceException::CODE_VOLUME_FULL)
				exit(RBS_RESULT_SERVICE_ERROR);
			$exitcode = $e->GetExitCode();
			if ($e->getCode() != ServiceException::CODE_VOLUME_FULL)
				$rbs->SetErrorCode($e->GetExceptionId() . '_EXIT' . $exitcode);
		}
	}

	// Send/update file-system stats
	if ($rbs->IsBackupMode() || $rbs->IsHistoryMode() || $rbs->IsDeleteMode()) {
		$rbs->ControlSendStats($exitcode);
		if ($exitcode == 12) {
			$rbs->ControlSendSessionLogout(false);
			exit(RBS_RESULT_VOLUME_FULL);
		}
		else if ($exitcode != 0) exit(RBS_RESULT_SERVICE_ERROR);
		$snapshots = $rbs->ControlRequestSnapshots(true);
		$rbs->SaveSnapshotHistory(sprintf(
			RemoteBackupService::FORMAT_SNAPSHOT_HISTORY,
			RemoteBackupService::PATH_RBSDATA), $snapshots);
	}

	// Success
	$rbs->ControlSendSessionLogout();
	exit(RBS_RESULT_SUCCESS);

} catch (ControlSocketException $e) {
	$rbs->SetErrorCode($e->GetExceptionId());
	$rbs->LogMessage(sprintf('[%s] %s', $e->GetExceptionId(), $e->getMessage()), LOG_ERR);
	exit(RBS_RESULT_SOCKET_ERROR);
} catch (ProtocolException $e) {
	if ($e->getCode() == ProtocolException::CODE_ERROR) {
		// Try to extract remote exception ID
		if (($pos = strpos($e->getMessage(), ':')) !== false) {
			$code = substr($e->getMessage(), 0, $pos);
			if ($code == RemoteBackupService::CTRL_REPLY_ERROR) {
				$data = substr($e->getMessage(), $pos + 1);
				$rbs->SetErrorCode($data);
				$rbs->LogMessage(sprintf('[%s] %s',
					$e->GetExceptionId(), $e->getMessage()), LOG_ERR);
				exit(RBS_RESULT_PROTOCOL_ERROR);
			}
		}
	}

	$rbs->LogMessage(sprintf('[%s] %s', $e->GetExceptionId(), $e->getMessage()), LOG_ERR);
	$rbs->SetErrorCode($e->GetExceptionId());
	$rbs->ControlSocketWrite(RemoteBackupService::CTRL_REPLY_ERROR, $e->GetExceptionId());
	exit(RBS_RESULT_PROTOCOL_ERROR);
} catch (FifoExecption $e) {
	$rbs->LogMessage(sprintf('[%s] %s', $e->GetExceptionId(), $e->getMessage()), LOG_ERR);
	$rbs->SetErrorCode($e->GetExceptionId());
	$rbs->ControlSocketWrite(RemoteBackupService::CTRL_REPLY_ERROR, $e->GetExceptionId());
	exit(RBS_RESULT_FIFO_ERROR);
} catch (ProcessException $e) {
	$rbs->SetErrorCode($e->GetExceptionId());
	$rbs->LogMessage(sprintf('[%s] %s', $e->GetExceptionId(), $e->getMessage()), LOG_ERR);
	$rbs->ControlSocketWrite(RemoteBackupService::CTRL_REPLY_ERROR, $e->GetExceptionId());
	exit(RBS_RESULT_PROCESS_ERROR);
} catch (ServiceException $e) {
	$rbs->SetErrorCode($e->GetExceptionId());
	$rbs->LogMessage(sprintf('[%s] %s', $e->GetExceptionId(), $e->getMessage()), LOG_ERR);
	$rbs->ControlSocketWrite(RemoteBackupService::CTRL_REPLY_ERROR, $e->GetExceptionId());
	exit(RBS_RESULT_SERVICE_ERROR);
} catch (Exception $e) {
	$rbs->LogMessage(sprintf('[%s] %s', $e->getCode(), $e->getMessage()), LOG_ERR);
	$rbs->ControlSocketWrite(RemoteBackupService::CTRL_REPLY_ERROR, $e->GetExceptionId());
	exit(RBS_RESULT_UNKNOWN_ERROR);
}

// vi: ts=4
?>
