<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2002-2006 Point Clark Networks.
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

require_once("../../gui/Webconfig.inc.php");
require_once("../../api/Bacula.class.php");
require_once("../../api/File.class.php");
require_once("../../api/Locale.class.php");
require_once("../../api/Daemon.class.php");
require_once("../../api/Hostname.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Icons
//
///////////////////////////////////////////////////////////////////////////////

// TODO: too many custom icons
define(WEB_ICON_GENERAL_CONFIG, ReplacePngTags("/images/deprecated/icon-bacula-config.png", ""));
define(WEB_ICON_BACKUP, ReplacePngTags("/images/deprecated/icon-bacula-backup.png", ""));
define(WEB_ICON_RESTORE, ReplacePngTags("/images/deprecated/icon-bacula-restore.png", ""));
define(WEB_ICON_SCHEDULE, ReplacePngTags("/images/deprecated/icon-bacula-schedule.png", ""));
define(WEB_ICON_STORAGE, ReplacePngTags("/images/deprecated/icon-bacula-storage.png", ""));
define(WEB_ICON_FILESET, ReplacePngTags("/images/deprecated/icon-bacula-fileset.png", ""));
define(WEB_ICON_CLIENT, ReplacePngTags("/images/deprecated/icon-bacula-client.png", ""));
define(WEB_ICON_CURRENT_STATUS, ReplacePngTags("/images/deprecated/icon-bacula-status.png", ""));
define(WEB_ICON_CONTROL, ReplacePngTags("/images/deprecated/icon-bacula-control.png", ""));
define(WEB_ICON_JOB, ReplacePngTags("/images/deprecated/icon-bacula-job.png", ""));
define(WEB_ICON_POOL, ReplacePngTags("/images/deprecated/icon-bacula-pool.png", ""));
define(WEB_ICON_REPORT, ReplacePngTags("/images/deprecated/icon-bacula-report.png", ""));
define(WEB_ICON_LOG, ReplacePngTags("/images/deprecated/icon-bacula-log.png", ""));
define(WEB_ICON_OK, ReplacePngTags("/images/deprecated/icon-bacula-log.png", ""));
define(WEB_ICON_WARNING, ReplacePngTags("/images/deprecated/icon-bacula-log.png", ""));
define(WEB_ICON_ADVANCED, ReplacePngTags("/images/deprecated/icon-bacula-advanced.png", ""));
define(WEB_ICON_RESTORE_CATALOG, ReplacePngTags("/images/deprecated/icon-bacula-catalog.png", ""));
define(WEB_ICON_COMMAND, ReplacePngTags("/images/deprecated/icon-bacula-command.png", ""));
define(WEB_ICON_SIGNATURE, ReplacePngTags("/images/deprecated/icon-bacula-signature.png", ""));
define(WEB_ICON_CASE, ReplacePngTags("/images/deprecated/icon-bacula-case.png", ""));
define(WEB_ICON_OPTIONS, ReplacePngTags("/images/deprecated/icon-bacula-options.png", ""));
define(WEB_ICON_WILD, ReplacePngTags("/images/deprecated/icon-bacula-wild.png", ""));
define(WEB_ICON_WILD_FILE, ReplacePngTags("/images/deprecated/icon-bacula-wild-file.png", ""));
define(WEB_ICON_WILD_DIR, ReplacePngTags("/images/deprecated/icon-bacula-wild-dir.png", ""));
define(WEB_ICON_REGEX, ReplacePngTags("/images/deprecated/icon-bacula-regex.png", ""));
define(WEB_ICON_REGEX_FILE, ReplacePngTags("/images/deprecated/icon-bacula-regex-file.png", ""));
define(WEB_ICON_REGEX_DIR, ReplacePngTags("/images/deprecated/icon-bacula-regex-dir.png", ""));
define(WEB_ICON_COMPRESSION, ReplacePngTags("/images/deprecated/icon-bacula-compression.png", ""));
define(WEB_ICON_LOCKED, ReplacePngTags("/images/deprecated/icon-bacula-locked.png", ""));
define(WEB_ICON_EMAIL, ReplacePngTags("/images/deprecated/icon-bacula-email.png", ""));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

$bacula = new Bacula();

WebAuthenticate();
	
try {
	if (isset($_POST['Download'])) {
		WebDownload(key($_POST['Download']));
	}
} catch (Exception $e) {
    WebDialogWarning($e->GetMessage());
}
// AJAX
if ($_POST['command']) {
	try {
		$reply = $bacula->IssueCommand($_POST['command']);
		echo $reply;
		exit;
	} catch (Exception $e) {
		echo $e->GetMessage();
	}
}

WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-backup-network.png", WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

if ($_POST['EnableBoot']) {
	try {
		$daemon = new Daemon(key($_POST['EnableBoot']));
		$daemon->SetBootState(true);
		sleep(4);
	} catch (Exception $e) {
        WebDialogWarning($e->GetMessage());
	}
} else if ($_POST['DisableBoot']) {
	try {
		$daemon = new Daemon(key($_POST['DisableBoot']));
		$daemon->SetBootState(false);
		sleep(4);
	} catch (Exception $e) {
        WebDialogWarning($e->GetMessage());
	}
} else if ($_POST['RestartDaemons']) {
	try {
		$bacula->RestartRequiredDaemons($_SESSION['bacula_restart']);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
} else if ($_POST['StartDaemon']) {
	try {
		$daemon = new Daemon(key($_POST['StartDaemon']));
		$daemon->SetRunningState(true);
		sleep(2);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
} else if ($_POST['StopDaemon']) {
	try {
		$daemon = new Daemon(key($_POST['StopDaemon']));
		$daemon->SetRunningState(false);
		sleep(2);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
} else if ($_POST['StartAllDaemons']) {
	try {
		$bacula->StartAllDaemons();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
} else if ($_POST['RestoreCatalog']) {
	if ($_POST['filesource']) {
		try {
			$bacula->RestoreCatalog($_POST['filesource']);
			WebDialogInfo(WEB_LANG_CATALOG_RESTORE_STARTED);
			$_POST['Action'] = "menu";
		} catch (Exception $e) {
			WebDialogWarning($e->GetMessage());
		}
	} else if ($_POST['catalog_method'] == "bsr") {
		if (!$_FILES["bsr"]) {
			WebDialogWarning(WEB_LANG_ERRMSG_NO_FILE);
		} else {
			try {
				if (isset($_FILES["bsr"]) && !$_FILES["bsr"]["error"]) {
					move_uploaded_file($_FILES["bsr"]["tmp_name"], "/tmp/" . $_FILES["bsr"]["name"]);
					$restored = $bacula->CreateCatalogFromBootstrap("/tmp/" . $_FILES["bsr"]["name"]);
					DisplayConfirmCatalogRestore($_POST['catalog_method'], $restored);
				}
			} catch (Exception $e) {
				WebDialogWarning($e->GetMessage());
			}
		}
	} else if ($_POST['catalog_method'] == "upload") {
		if (!$_FILES['upload']) {
			WebDialogWarning(WEB_LANG_ERRMSG_NO_FILE);
		} else {
			if (isset($_FILES["upload"]) && !$_FILES["upload"]["error"]) {
				DisplayConfirmCatalogRestore($_POST['catalog_method'], $_FILES["upload"]["name"]);
				move_uploaded_file($_FILES["upload"]["tmp_name"], "/tmp/" . $_FILES["upload"]["name"]);
			}
		}
	} else if ($_POST['catalog_method'] == "local") {
		if (!$_POST['local']) {
			WebDialogWarning(WEB_LANG_ERRMSG_NO_FILE);
		} else {
			DisplayConfirmCatalogRestore($_POST['catalog_method'], $_POST['local']);
		}
	}
} else if ($_POST['DeviceAction']) {
	if (!$_POST['Confirm']) {
		if (!$_POST['Cancel'])
			DisplayConfirmDeviceAction($_POST['device_name'], $_POST['device_action']);
	} else if ($_POST['device_action'] == "mount") {
		try {
			$bacula->DeviceMount($_POST['device_name']);
			WebDialogInfo(WEB_LANG_DEVICE_MOUNTED);
		} catch (Exception $e) {
			$Cancel = true;
			WebDialogWarning($e->GetMessage());
		}
	} else if ($_POST['device_action'] == "umount" || $_POST['device_action'] == "umount_eject") {
		try {
			$bacula->DeviceUmount($_POST['device_name']);
			if ($_POST['device_action'] == "umount_eject") {
				$bacula->DeviceEject($_POST['device_name']);
				WebDialogInfo(WEB_LANG_DEVICE_EJECTED);
			} else {
				WebDialogInfo(WEB_LANG_DEVICE_UMOUNTED);
			}
		} catch (Exception $e) {
			if ($_POST['device_action'] == "umount_eject") {
				try {
					$bacula->DeviceEject($_POST['device_name']);
					WebDialogInfo(WEB_LANG_DEVICE_EJECTED);
				} catch (Exception $e) {
					$Cancel = true;
					WebDialogWarning($e->GetMessage());
				}
			} else {
				$Cancel = true;
				WebDialogWarning($e->GetMessage());
			}
		}
	} else if ($_POST['device_action'] == "eject") {
		try {
			$bacula->DeviceEject($_POST['device_name']);
			WebDialogInfo(WEB_LANG_DEVICE_EJECTED);
		} catch (Exception $e) {
			$Cancel = true;
			WebDialogWarning($e->GetMessage());
		}
	} else if ($_POST['device_action'] == "label") {
		try {
			$bacula->DeviceLabel($_POST['device_name'], $_POST['pool'], $_POST['label']);
			WebDialogInfo(WEB_LANG_DEVICE_LABELED);
		} catch (Exception $e) {
			$Cancel = true;
			WebDialogWarning($e->GetMessage());
		}
	}
} else if ($_POST['UpdateBasicConfig']) {
	try {
		$bacula->SetDirectorOperatorEmail($_POST['email']);
		$bacula->SetDirectorAdminEmail($_POST['email']);
		$bacula->Commit();
		unset($_SESSION['basic']['step']);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
} else if ($_POST['SendConfig']) {
	try {
		$bacula->SendAdminConfig(1, 1, 1);
		WebDialogInfo(WEB_LANG_CONFIG_SENT . " " . $bacula->GetDirectorAdminEmail() . ".");
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
} else if ($_POST['SendConfigDir']) {
	try {
		$bacula->SendAdminConfig(1, 0, 0);
		WebDialogInfo(WEB_LANG_CONFIG_SENT . " " . $bacula->GetDirectorAdminEmail() . ".");
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
} else if ($SendConfigFd) {
	try {
		$bacula->SendAdminConfig(0, 1, 0);
		WebDialogInfo(WEB_LANG_CONFIG_SENT . " " . $bacula->GetDirectorAdminEmail() . ".");
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
} else if ($SendConfigSd) {
	try {
		$bacula->SendAdminConfig(0, 0, 1);
		WebDialogInfo(WEB_LANG_CONFIG_SENT . " " . $bacula->GetDirectorAdminEmail() . ".");
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
} else if ($_POST['UpdateConfig']) {
	try {
		$bacula->SetDirectorEmailOnEdit($_POST['dir_email_on_edit']);
		$bacula->Commit();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
} else if ($_POST['UploadConfigDir']) {
	DisplayUploadConfig(Bacula::DIR_FILE_CONFIG);
} else if ($_POST['UploadConfigFd']) {
	DisplayUploadConfig(Bacula::FD_FILE_CONFIG);
} else if ($_POST['UploadConfigSd']) {
	DisplayUploadConfig(Bacula::SD_FILE_CONFIG);
} else if ($_POST['UploadConfig']) {
	try {
		if (isset($_FILES["file1"]) && !$_FILES["file1"]["error"]) {
			if ($_FILES["file1"]["name"] == basename(Bacula::DIR_FILE_CONFIG)) {
				if (isset($_FILES["file2"]) && !$_FILES["file2"]["error"]) {
					if ($_FILES["file2"]["name"] == basename(Bacula::CONSOLE_FILE_CONFIG)) {
						move_uploaded_file($_FILES["file1"]["tmp_name"], "/tmp/" . $_FILES["file1"]["name"]);
						move_uploaded_file($_FILES["file2"]["tmp_name"], "/tmp/" . $_FILES["file2"]["name"]);
						$bacula->ReplaceConfig($_FILES["file1"]["name"]);
						$bacula->ReplaceConfig($_FILES["file2"]["name"]);
						$_SESSION['bacula_restart'] = 'true';
						WebDialogInfo(WEB_LANG_UPLOAD_COMPLETE);
					} else {
						WebDialogWarning(WEB_LANG_ERRMSG_INVALID_CONF);
					}
				} else {
					WebDialogWarning(WEB_LANG_ERRMSG_INVALID_CONF);
				}
			} else if ($_FILES["file1"]["name"] == basename(Bacula::FD_FILE_CONFIG)) {
				move_uploaded_file($_FILES["file1"]["tmp_name"], "/tmp/" . $_FILES["file1"]["name"]);
				$bacula->ReplaceConfig($_FILES["file1"]["name"]);
				$_SESSION['bacula_restart'] = 'true';
				WebDialogInfo(WEB_LANG_UPLOAD_COMPLETE);
			} else if ($_FILES["file1"]["name"] == basename(Bacula::SD_FILE_CONFIG)) {
				move_uploaded_file($_FILES["file1"]["tmp_name"], "/tmp/" . $_FILES["file1"]["name"]);
				$bacula->ReplaceConfig($_FILES["file1"]["name"]);
				$_SESSION['bacula_restart'] = 'true';
				WebDialogInfo(WEB_LANG_UPLOAD_COMPLETE);
			} else {
				WebDialogWarning(WEB_LANG_ERRMSG_INVALID_CONF);
			}
		} else {
			WebDialogWarning(WEB_LANG_ERRMSG_INVALID_CONF);
		}
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
} else if ($_POST['UpdateConfigDir']) {
	try {
		$bacula->SetDirectorName($_POST['dir_name']);
		$bacula->SetDirectorAddress($_POST['dir_address']);
		$bacula->SetDirectorPort($_POST['dir_port']);
		$bacula->SetDirectorPassword($_POST['dir_password']);
		$bacula->SetDirectorOperatorEmail($_POST['dir_operator_email']);
		$bacula->SetDirectorAdminEmail($_POST['dir_admin_email']);
		$bacula->SetDirectorMailserver($_POST['dir_mailserver']);
		$bacula->SetDirectorDatabasePassword($_POST['dir_db_password']);
		$bacula->Commit();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
} else if ($_POST['UpdateConfigFd']) {
	try {
		$bacula->SetFileName($_POST['fd_name']);
		$bacula->SetFilePort($_POST['fd_port']);
		$bacula->Commit();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
} else if ($_POST['UpdateConfigSd']) {
	try {
		$bacula->SetStorageName($_POST['sd_name']);
		$bacula->SetStoragePort($_POST['sd_port']);
		$bacula->Commit();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
} else if ($_POST['Action'] == "backup") {
	try {
		if ($_POST['StartBackup'])
			$_POST['job_id'] = $bacula->StartBackup($_POST['job']);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
} else if ($_POST['Action'] == "restore") {
	try {
		if ($_POST['StartRestore']) {
			$_POST['job_id'] = $bacula->StartRestore(
				$_POST['client'], $_POST['pool'], $_POST['device'], $_POST['fileset'], $_POST['where'], $_POST['replace']
			);
		} elseif ($_POST['StartBsrRestore']) {
			move_uploaded_file($_FILES["bsr"]["tmp_name"], "/tmp/" . $_FILES["bsr"]["name"]);
			$_POST['job_id'] = $bacula->StartBsrRestore(
				$_POST['job'], $_POST['client'], $_POST['device'],
				"/tmp/" . $_FILES["bsr"]["name"], $_POST['where'], $_POST['replace']
			);
		}
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
} else if ($_POST['Action'] == "job") {
	if ($_POST['Add']) {
		try {
			$name = str_replace(" ", "", $_POST['newjob']);
			$bacula->AddJob($name);
			$bacula->Commit();
			DisplayEditJob($name);
		} catch (Exception $e) {
			WebDialogWarning($e->GetMessage());
		}
	} else if ($_POST['DoDelete']) {
		try {
			$bacula->DeleteJob(key($_POST['DoDelete']));
			$bacula->Commit();
		} catch (Exception $e) {
			WebDialogWarning($e->GetMessage());
		}
	} else if ($_POST['DoEdit']) {
		try {
			$bacula->SetJobType($_POST['name'], $_POST['job_type']);
			$bacula->SetJobLevel($_POST['name'], $_POST['job_level']);
			$bacula->SetJobClient($_POST['name'], $_POST['job_client']);
			$bacula->SetJobFileset($_POST['name'], $_POST['job_fileset']);
			$bacula->SetJobSchedule($_POST['name'], $_POST['job_schedule']);
			$bacula->SetJobStorageDevice($_POST['name'], $_POST['job_storage_device']);
			$bacula->SetJobPool($_POST['name'], $_POST['job_pool']);
			$bacula->SetJobPriority($_POST['name'], $_POST['job_priority']);
			$bacula->SetJobWriteBsr($_POST['name'], $_POST['job_write_bsr']);
			$bacula->SetJobSendBsr($_POST['name'], $_POST['job_send_bsr']);
			# Always set name last
			$bacula->SetJobName($_POST['name'], $_POST['job_name']);
			$bacula->Commit();
		} catch (Exception $e) {
			WebDialogWarning($e->GetMessage());
		}
	} else if ($_POST['Edit']) {
		DisplayEditJob(key($_POST['Edit']));
	} else if ($_POST['Delete']) {
		DisplayDeleteJob(key($_POST['Delete']));
	}
} else if ($_POST['Action'] == "pool") {
	if ($_POST['Add']) {
		try {
			$name = str_replace(" ", "", $_POST['newpool']);
			$bacula->AddPool($name);
			$bacula->Commit();
			DisplayEditPool($name);
		} catch (Exception $e) {
			WebDialogWarning($e->GetMessage());
		}
	} else if ($_POST['DoDelete']) {
		try {
			$bacula->DeletePool(key($_POST['DoDelete']));
			$bacula->Commit();
		} catch (Exception $e) {
			WebDialogWarning($e->GetMessage());
		}
	} else if ($_POST['DoEdit']) {
		try {
			$bacula->SetPoolType($_POST['name'], $_POST['pool_type']);
			$bacula->SetPoolRecycle($_POST['name'], $_POST['pool_recycle']);
			$bacula->SetPoolAutoPrune($_POST['name'], $_POST['pool_auto_prune']);
			$bacula->SetPoolVolumeRetention(
				$_POST['name'], $_POST['pool_volume_retention'] . " " . $_POST['pool_volume_retention_unit']
			);
			$bacula->SetPoolLabelFormat($_POST['name'], $_POST['pool_label_format']);
			$bacula->SetPoolMaxVolumes($_POST['name'], $_POST['pool_max_volumes']);
			$bacula->SetPoolMaxVolumeJobs($_POST['name'], $_POST['pool_max_volume_jobs']);
			# Always set name last
			$bacula->SetPoolName($_POST['name'], $_POST['pool_name']);
			$bacula->Commit();
		} catch (Exception $e) {
			WebDialogWarning($e->GetMessage());
			DisplayEditPool($_POST['name']);
		}
	} else if ($_POST['Edit']) {
		DisplayEditPool(key($_POST['Edit']));
	} else if ($_POST['Delete']) {
		DisplayDeletePool(key($_POST['Delete']));
	}
} else if ($_POST['Action'] == "storage") {
	if ($_POST['Add']) {
		try {
			$name = preg_replace("/\s+|\\.|\\,/", "_", $_POST['newsd']);
			$bacula->AddSd($name);
			$bacula->Commit();
			if ($_POST['sd_mount'])
				$bacula->SetSdMount($name, $_POST['sd_mount']);
			DisplayEditSd($name);
		} catch (Exception $e) {
			WebDialogWarning($e->GetMessage());
		}
	} else if ($_POST['DoDelete']) {
		try {
			$bacula->DeleteSd(key($_POST['DoDelete']));
			$bacula->Commit();
		} catch (Exception $e) {
			WebDialogWarning($e->GetMessage());
		}
	} else if ($_POST['DoEdit']) {
		try {
			$bacula->SetSdAddress($_POST['name'], $_POST['sd_address']);
			$bacula->SetSdPort($_POST['name'], $_POST['sd_port']);
			$bacula->SetSdPassword($_POST['name'], $_POST['sd_password']);
			$bacula->SetSdMediaType($_POST['name'], $_POST['sd_media_type']);
			# Make sure media type comes before mount...it effects the override
			$bacula->SetSdMount($_POST['name'], $_POST['sd_mount']);
			$bacula->SetSdLabelMedia($_POST['name'], $_POST['sd_label_media']);
			$bacula->SetSdRandomAccess($_POST['name'], $_POST['sd_random_access']);
			$bacula->SetSdAutomaticMount($_POST['name'], $_POST['sd_automatic_mount']);
			$bacula->SetSdRemovableMedia($_POST['name'], $_POST['sd_removable_media']);
			$bacula->SetSdAlwaysOpen($_POST['name'], $_POST['sd_always_open']);
			$bacula->SetSdMaximumVolumeSize($_POST['name'], $_POST['sd_max_volume_size'], $_POST['sd_max_volume_size_unit']);
			if (eregi("^" . Bacula::MEDIA_SMB, $_POST['sd_media_type'])) {
				$info = Array(
					"address" => $_POST['address'], "username" => $_POST['username'],
					"password" => $_POST['password'], "sharedir" => $_POST['sharedir']
				);
				$bacula->CheckSmbMount($info, $_POST['sd_mount']);
				$share = base64_encode("username=" . $_POST['username'] . "|" .
									   "password=" . $_POST['password'] . "|" .
									   "address=" . $_POST['address'] . "|" .
									   "sharedir=" . $_POST['sharedir']);
				$bacula->SetSdComment($_POST['name'], "ShareInfo", $share);
			}
			# Always set name last
			$bacula->SetSdName($_POST['name'], $_POST['sd_name']);
			$bacula->Commit();
			DisplayEditSd($_POST['name']);
		} catch (Exception $e) {
			WebDialogWarning($e->GetMessage());
			DisplayEditSd($_POST['name']);
		}
	} else if ($_POST['Edit']) {
		DisplayEditSd(key($_POST['Edit']));
	} else if ($_POST['Delete']) {
		DisplayDeleteSd(key($_POST['Delete']));
	} else if ($_POST['name']) {
        DisplayEditSd($_POST['name']);
	}
} else if ($_POST['Action'] == "client") {
	if ($_POST['Add']) {
		try {
			$name = str_replace(" ", "", $_POST['newclient']);
			$bacula->AddClient($name);
			$bacula->Commit();
			DisplayEditClient($name);
		} catch (Exception $e) {
			WebDialogWarning($e->GetMessage());
		}
	} else if ($_POST['DoDelete']) {
		try {
			$bacula->DeleteClient(key($_POST['DoDelete']));
			$bacula->Commit();
		} catch (Exception $e) {
			WebDialogWarning($e->GetMessage());
		}
	} else if ($_POST['DoEdit']) {
		try {
			$bacula->SetClientAddress($_POST['name'], $_POST['client_address']);
			$bacula->SetClientPort($_POST['name'], $_POST['client_port']);
			$bacula->SetClientPassword($_POST['name'], $_POST['client_password']);
			$bacula->SetClientFileRetention(
				$_POST['name'], $_POST['client_file_retention'] . " " . $_POST['client_file_retention_unit']
			);
			$bacula->SetClientJobRetention(
				$_POST['name'], $_POST['client_job_retention'] . " " . $_POST['client_job_retention_unit']
			);
			$bacula->SetClientAutoPrune($_POST['name'], $_POST['client_auto_prune']);
			# Always set name last
			$bacula->SetClientName($_POST['name'], $_POST['client_name']);
			$bacula->Commit();
		} catch (Exception $e) {
			WebDialogWarning($e->GetMessage());
		}
	} else if ($_POST['Edit']) {
		DisplayEditClient(key($_POST['Edit']));
	} else if ($_POST['Delete']) {
		DisplayDeleteClient(key($_POST['Delete']));
	}
} else if ($_REQUEST['Action'] == "fileset") {
	if ($_POST['DoNothing']) {
		DisplayEditFileset(key($_POST['DoNothing']), $_POST['show']);
	} else if ($_POST['Add']) {
		try {
			$name = str_replace(" ", "", $_POST['newfileset']);
			$bacula->AddFileset($name, $_POST['database']);
			$bacula->Commit();
			DisplayEditFileset($name, $_POST['show']);
		} catch (Exception $e) {
			WebDialogWarning($e->GetMessage());
		}
	} else if ($_POST['AddInclude']) {
		try {
			$bacula->AddFilesetInclude(key($_POST['AddInclude']));
			$bacula->Commit();
			DisplayEditFileset(key($_POST['AddInclude']), $_POST['show']);
		} catch (Exception $e) {
			WebDialogWarning($e->GetMessage());
		}
	} else if ($_POST['DoDelete']) {
		try {
			$bacula->DeleteFileset(key($_POST['DoDelete']));
			$bacula->Commit();
		} catch (Exception $e) {
			WebDialogWarning($e->GetMessage());
		}
	} else if ($_POST['DoEdit'] && !$_POST['Cancel']) {
		# Fileset does not fit 'standard'...Include/Exclude and File parameters are not unique.  So,
		# we just re-write entire block.
		$delete_in_list = Array();
		$delete_block_in_list = Array();
		$delete_ex_list = Array();
		$delete_block_ex_list = Array();
		$exclude_list = Array();
		$include_list = Array();
		$new_inc_list = Array();
		$new_exc_list = Array();
		foreach ($_POST['DoEdit'] as $key => $value) {
			$index_container = split("\|", $key);
			if ($index_container[0] == "delete_include" && $index_container[2] == -1)
				$delete_block_in_list[$index_container[1]] = true;
			else if ($index_container[0] == "delete_include")
				$delete_in_list[$index_container[1]][$index_container[2]] = true;
			else if ($index_container[0] == "delete_exclude" && $index_container[2] == -1)
				$delete_block_ex_list[$index_container[1]] = true;
			else if ($index_container[0] == "delete_exclude")
				$delete_ex_list[$index_container[1]][$index_container[2]] = true;
			else if ($index_container[0] == "include" && $value)
				$include_list[$index_container[1]][$index_container[2]] = $value;
			else if ($index_container[0] == "exclude" && $value)
				$exclude_list[$index_container[1]][$index_container[2]] = $value;
		}

		$newblock = 0;
		foreach ($include_list as $block) {
			$newindex = 0;
			foreach ($block as $index => $line) {
				if ($delete_block_in_list[$newblock])
					continue;
				if ($delete_in_list[$newblock][$index])
					continue;
				if ($_POST['db_properties'])
					$new_inc_list[$newblock][$newindex] = "    File = \"" . Bacula::BACULA_VAR . $_POST['fileset_name'] . ".sql\"";
				else
					$new_inc_list[$newblock][$newindex] = "    File = \"" . $include_list[$newblock][$index] . "\"";
				$newindex++;
			}
			$newblock++;
		}
		$newblock = 0;
		foreach ($exclude_list as $block) {
			$newindex = 0;
			foreach ($block as $index => $line) {
				if ($delete_block_ex_list[$newblock])
					continue;
				if ($delete_ex_list[$newblock][$index])
					continue;
				$new_exc_list[$newblock][$newindex] = "    File = \"" . $exclude_list[$newblock][$index] . "\"";
				$newindex++;
			}
			$newblock++;
		}
		foreach ($Delete as $key => $value) {
			$part = explode("|", $key);
			unset($_POST['fileset_options'][$part[0]][$part[1]][$part[2]]);
		}
		try {
			$bacula->SetFilesetList($_POST['name'], $new_inc_list, $new_exc_list, $_POST['fileset_options']);
			if ($_POST['db_properties'])
				$bacula->UpdateDbScripts($_POST['fileset_name'], $_POST['db_properties']);
			# Always set name last
			$bacula->SetFilesetName($_POST['name'], $_POST['fileset_name']);
			$bacula->Commit();
			if (!$db_properties)
				DisplayEditFileset($_POST['fileset_name'], $_POST['show']);
		} catch (Exception $e) {
			WebDialogWarning($e->GetMessage());
		}
	} else if ($_REQUEST['Edit']) {
		DisplayEditFileset(key($_REQUEST['Edit']), $_REQUEST['show']);
	} else if ($_POST['Delete']) {
		DisplayDeleteFileset(key($_POST['Delete']));
	}
} else if ($_POST['Action'] == "schedule") {
	if ($_POST['Add']) {
		try {
			$name = str_replace(" ", "", $_POST['newschedule']);
			$bacula->AddSchedule($name);
			$bacula->Commit();
			DisplayEditSchedule($name);
		} catch (Exception $e) {
			WebDialogWarning($e->GetMessage());
		}
	} else if ($_POST['DoDelete']) {
		$bacula->DeleteSchedule(key($_POST['DoDelete']));
		$bacula->Commit();
	} else if ($_POST['DoEdit'] && !$_POST['Cancel']) {
		# Schedule does not fit 'standard'...Run = <pattern> is not unique.  So,
		# we just re-write entire block.
		$run_list = Array();
		$delete_list = Array();
		$level_list = Array();
		$date_list = Array();
		$hour_list = Array();
		$minute_list = Array();
		foreach ($_POST['DoEdit'] as $key => $value) {
			$index_container = split("\|", $key);
			if ($index_container[0] == "delete" || $value == -1)
				$delete_list[$index_container[1]] = true;
			else if ($index_container[0] == "level")
				$level_list[$index_container[1]] = $value;
			else if ($index_container[0] == "date")
				$date_list[$index_container[1]] = $value;
			else if ($index_container[0] == "hour")
				$hour_list[$index_container[1]] = $value;
			else if ($index_container[0] == "minute")
				$minute_list[$index_container[1]] = $value;
		}

		$newindex = 0;
		foreach ($level_list as $index => $line) {
			if ($delete_list[$index])
				continue;
			$newline = "  Run = " . $level_list[$index] . " " . $date_list[$index] . " at " . $hour_list[$index] .
						":" . $minute_list[$index];
			$run_list[$newindex] = $newline;
			$newindex++;
		}
		try {
			$bacula->SetScheduleRunList($_POST['name'], $run_list);
			$bacula->SetSchedulePools($_POST['name'], $_POST['pools']);
			$bacula->Commit();
			DisplayEditSchedule($_POST['name']);
		} catch (Exception $e) {
			WebDialogWarning($e->GetMessage());
		}
	} else if ($_POST['Edit']) {
		DisplayEditSchedule(key($_POST['Edit']));
	} else if ($_POST['Delete']) {
		DisplayDeleteSchedule(key($_POST['Delete']));
	}
} else if (isset($_POST['GotoConfig'])) {
	$_REQUEST['Action'] = "config";
} else if (isset($_POST['Controls'])) {
	$_REQUEST['Action'] = "control";
} else if (isset($_POST['AutoDetect'])) {
	$bacula->AutoConfigureDevices();
	DisplayAutoDetectedDevices();
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

if ($_REQUEST['Action'] == null || $_REQUEST['Action'] == "menu" || $_REQUEST['Return']) {
	DisplayMenu();
} else if ($_REQUEST['Action'] == "backup" && isset($_SESSION['basic']['step'])) {
	DisplayMenu();
} else if ($_REQUEST['Action'] == "config") {
	DisplayConfig();
} else if ($_REQUEST['Action'] == "report") {
	DisplayReport($_REQUEST['job']);
} else if ($_REQUEST['Action'] == "control") {
	DisplayControl();
} else if ($_REQUEST['Action'] == "scheduled_jobs") {
	DisplayScheduledJobs();
} else if ($_REQUEST['Action'] == "status") {
	DisplayStatus();
} else if ($_REQUEST['Action'] == "virtual") {
	DisplayVirtualConsole($reply);
} else if ($_REQUEST['Action'] == "storage") {
	DisplaySd();
} else if ($_REQUEST['Action'] == "client") {
	DisplayClient();
} else if ($_REQUEST['Action'] == "schedule") {
	DisplaySchedule();
} else if ($_REQUEST['Action'] == "fileset") {
	DisplayFileset();
} else if ($_REQUEST['Action'] == "pool") {
	DisplayPool();
} else if ($_REQUEST['Action'] == "job") {
	DisplayJob();
} else if ($_REQUEST['Action'] == "backup") {
	DisplayBackup($_POST['job_id'], $_REQUEST['refresh']);
} else if ($_REQUEST['Action'] == "restore") {
	DisplayRestore($_POST['job_id'], $_REQUEST['refresh']);
} else if ($_REQUEST['Action'] == "catalog") {
	DisplayRestoreCatalog();
} else if ($_REQUEST['Action'] == "daemon") {
	DisplayDaemon();
} else {
	WebDialogWarning(WEB_LANG_ERRMSG_UNKNOWN_ACTION);
}
WebFooter();


///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////


///////////////////////////////////////////////////////////////////////////////
//
// DisplayMenu()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayMenu()
{
	global $bacula;

	if (isset($_SESSION['bacula_restart'])) {
		WebFormOpen($_SERVER['PHP_SELF'], 'post');
		WebDialogWarning("Configuration changes have been made to your system that requires restarting the Bacula daemons." .
		"<p align='center'>" .
		   WebButton("RestartDaemons", "Restart Bacula daemons now", WEBCONFIG_ICON_GO) . "
		   </p>
		");
		WebFormClose();
	}
	$bacula_daemons = Array("bacula-dir", "bacula-fd", "bacula-sd", "bacula-mysqld");
	$index = 0;
	$daemon_data = "";
	$all_disabled = true;
	foreach($bacula_daemons as $individual) {
		$daemon = new Daemon($individual);
		if ($daemon->GetRunningState()) {
			$status = "<span class='ok'><b>" . DAEMON_LANG_RUNNING . "</b></span>";
			$all_disabled = false;
		} else {
			$status = "<span class='alert'><b>" . DAEMON_LANG_STOPPED . "</b></span>";
		}

		if ($daemon->GetBootState()) {
			$onboot = "<span class='ok'><b>" . DAEMON_LANG_AUTOMATIC . "</b></span>";
		} else {
			$onboot = "<span class='alert'><b>" . DAEMON_LANG_MANUAL . "</b></span>";
		}

		$rowclass = 'rowenabled';
		$rowclass .= ($index % 2) ? 'alt' : '';
		$daemon_data .= "
			<tr class='$rowclass'>
			  <td>" . $individual . "</td>
			  <td>" . $status . "</td>
			  <td>" . $onboot . "</td>
			</tr>
		";
		$index++;
	}


	# Get 24 hour summary
	try {
		$sql_data = $bacula->GetLast24HoursActivity();
		if (!$sql_data) {
			$table_data = "<tr><td colspan='4' align='center'>" . WEB_LANG_NO_DATA . "</td></tr>";
		} else {
			$index = 0;
			foreach ($sql_data as $line) {
				$rowclass = 'rowenabled';
				$rowclass .= ($index % 2) ? 'alt' : '';

				if ($line["JobStatus"] == "T")
					$state = WEBCONFIG_ICON_OK;
				else
					$state = WEBCONFIG_ICON_WARNING;
				$table_data .= "
					<tr class='$rowclass'>
					  <td><a href='backup-network.php?Action=report&job=" . $line["JobName"] . "'>" . $line["JobName"] . "</a></td>
					  <td NOWRAP>" . strftime("%A, %H:%M", strtotime($line["StartTime"])) . "</td>
					  <td align='center'>" . $line["Level"] . "</td>
					  <td align='center'>" . $state . "</td>
					</tr>
				";
				$index++;
			}
		}
	} catch (Exception $e) {
		$table_data = "<tr><td colspan='4'>" . $e->GetMessage() . "</td></tr>";
	}

	if (isset($_REQUEST['blevel']) && $_REQUEST['blevel'] == 'advanced') {
		$_SESSION['basic']['level'] = 'advanced';
	} else if (isset($_REQUEST['blevel']) && $_REQUEST['blevel'] == 'basic') {
		$_SESSION['basic']['level'] = 'basic';
	} else if (isset($_SESSION['basic']['level']) && $_SESSION['basic']['level'] == 'advanced') {
		$_SESSION['basic']['level'] = 'advanced';
	} else {
		$_SESSION['basic']['level'] = 'basic';
	}

	if ($all_disabled) {
		WebFormOpen($_SERVER['PHP_SELF'], 'post');
		WebDialogWarning(WEB_LANG_NO_DAEMONS_RUNNING .
           "<p align='left'>" .
           WebButton("StartAllDaemons", WEB_LANG_START_ALL_DAEMONS, WEBCONFIG_ICON_GO) . "
           </p>"
		);
		WebFormClose();
	} else if ($_SESSION['basic']['level'] == 'basic') {
		# Make sure we're always moving forward
		if (!isset($_SESSION['basic']['step']) || (int)$_SESSION['basic']['step'] < (int)$_POST['bstep'])
			$_SESSION['basic']['step'] = $_POST['bstep'];
		# Unset step session variable if Cancel is pressed at any time
		if (isset($_POST['Cancel'])) {
			unset($_SESSION['basic']);
		}

		# Backup Client
		if (isset($_POST['BasicBackupClient'])) {
			$_SESSION['basic']['type'] = 'BackupClient';
			$_SESSION['basic']['title'] = WEB_LANG_BACKUP_CLIENT;
			$_SESSION['basic']['step'] = 1;
		} else if (isset($_POST['BasicBackupServer'])) {
			$_SESSION['basic']['type'] = 'BackupServer';
			$_SESSION['basic']['title'] = WEB_LANG_BACKUP_SERVER;
			$_SESSION['basic']['step'] = 4;
		} else if (isset($_POST['BasicRestoreClient'])) {
			$_SESSION['basic']['type'] = 'RestoreClient';
			$_SESSION['basic']['title'] = WEB_LANG_RESTORE_CLIENT;
			$_SESSION['basic']['step'] = 1;
		} else if (isset($_POST['BasicRestoreServer'])) {
			$_SESSION['basic']['type'] = 'RestoreServer';
			$_SESSION['basic']['title'] = WEB_LANG_RESTORE_SERVER;
			$_SESSION['basic']['step'] = 4;
		}

		# Some of the functions below take some time...especially on crappy hardware
		set_time_limit (0);

		WebFormOpen($_SERVER['PHP_SELF'], 'post');
		if (!isset($_SESSION['basic']['step'])) {
			DisplayBasicOptions();
		} else if (isset($_SESSION['basic']['type']) && $_SESSION['basic']['type'] == 'RestoreClient') {
			WebDialogInfo(WEB_LANG_USE_WX_CONSOLE);
			echo "<p align='center'>" .	WebButton("Cancel", LOCALE_LANG_RETURN_TO_SUMMARY, WEBCONFIG_ICON_BACK) . "</p>";
		} else if (isset($_SESSION['basic']['type']) && $_SESSION['basic']['type'] == 'BackupClient') {
			$bacula->AutoConfigureDevices();
			if ($_SESSION['basic']['step'] == '1') {
				DisplayBasicSelectClient();
			}
			if ($_SESSION['basic']['step'] == '2') {
				$_SESSION['basic']['client'] = $_POST['client'];
				if (isset($_POST['client']) && $_POST['client'] == '-1') {
					unset($_POST['client']);
					DisplayBasicAddClient();
				} else if (isset($_POST['client']) && $_POST['client'] == '-2') {
					WebDialogWarning("Select client");
					DisplayBasicSelectClient();
				} else if (isset($_POST['Add'])) {
					try {
						$bacula->AddClient(str_replace(" ", "", $_POST['client']));
						$bacula->Commit();
						$_SESSION['basic']['client'] = $_POST['client'];
						$_SESSION['basic']['step'] = '3';
					} catch (Exception $e) {
						WebDialogWarning($e->GetMessage());
						DisplayBasicAddClient();
					}
				} else {
					$_SESSION['basic']['step'] = '4';
				}
			}
			if ($_SESSION['basic']['step'] == '3') {
				if (isset($_POST['ContinueFileset'])) {
					try {
						$bacula->SetClientAddress($_SESSION['basic']['client'], $_POST['address']);
						$bacula->Commit();
						if (isset($_POST['os']) && $_POST['os'] == '-1') {
							throw new Exception(WEB_LANG_SELECT_OS);
						} else {
							try {
								$bacula->AddBasicFileset($_SESSION['basic']['client'], $_POST['os']);
							} catch (Exception $ignore) {
								# TODO - should be checking for duplicate key exception
							}
							$_SESSION['basic']['fileset'] = $_SESSION['basic']['client'] . "-" . $_POST['os'];
							$_SESSION['basic']['step'] = '4';
						}
					} catch (Exception $e) {
						WebDialogWarning($e->GetMessage());
						DisplayBasicSetupClient();
					}
				} else {
					DisplayBasicSetupClient();
				}
			}
			if ($_SESSION['basic']['step'] == '4') {
				if (isset($_POST['ContinueFileset'])) {
					if (isset($_POST['os']) && $_POST['os'] == '-1') {
						throw new Exception(WEB_LANG_SELECT_OS);
					} else {
						try {
							$bacula->AddBasicFileset($_SESSION['basic']['client'], $_POST['os']);
						} catch (Exception $ignore) {
							# TODO - should be checking for duplicate key exception
						}
						$_SESSION['basic']['fileset'] = $_SESSION['basic']['client'] . "-" . $_POST['os'];
					}
				}
				if (isset($_POST['ContinueStorage'])) {
					if (isset($_POST['storage']))
						$_SESSION['basic']['storage'] = $_POST['storage'];
					if (isset($_POST['storage']) && $_POST['storage'] == '-1') {
						WebDialogWarning(WEB_LANG_SELECT_SD);
						DisplayBasicSelectDevice(false, true);
					} else {
						$jobname = str_replace(" ", "", $_SESSION['basic']['client'] . $_SESSION['basic']['storage']);
						$_SESSION['basic']['job'] = $jobname;
						try {
							$attributes = $bacula->GetJobAttributes($jobname);
							if (empty($attributes)) {
								# Check that we know the fileset
								if (!isset($_SESSION['basic']['fileset'])) {
									DisplayBasicSelectFileset();
								} else {
									# Need to create a job
									try {
										$bacula->AddJob($jobname);
										$bacula->Commit();
										$bacula->SetJobLevel($jobname, "Full");
										$bacula->SetJobClient($jobname, $_SESSION['basic']['client']);
										$bacula->SetJobFileset($jobname, $_SESSION['basic']['fileset']);
										$bacula->SetJobSchedule($jobname, "");  # Manual
										$bacula->SetJobStorageDevice($jobname, $_SESSION['basic']['storage']);
										$bacula->SetJobPool($jobname, Bacula::BASIC_POOL);
										$bacula->SetJobPriority($jobname, "10");
										$bacula->SetJobWriteBsr($jobname, "no");
										$bacula->SetJobSendBsr($jobname, "no");
										if (eregi("DVD", $_SESSION['basic']['storage']))
											$bacula->SetJobWritePartAfterJob($jobname, "yes");
										$bacula->Commit();
										$_SESSION['basic']['step'] = '5';
									} catch (Exception $e) {
										WebDialogWarning($e->GetMessage());
									}
								}
							} else {
								if (!isset($_SESSION['basic']['fileset'])) {
									$regex = "^[[:space:]]*(FileSet)[[:space:]]*=[[:space:]]*(.*$)";
									foreach ($attributes as $line) {
										if (eregi($regex, preg_replace("/ /", "", trim($line)), $match)) {
											$_SESSION['basic']['fileset'] = trim($match[2]);
											$_SESSION['basic']['step'] = '5';
											break;
										}
									}
								}
							}
						} catch (Exception $e) {
							if (!isset($_SESSION['basic']['storage'])) {
								WebDialogWarning($e->GetMessage());
								DisplayBasicSelectDevice(false, true);
							}
						}
					}
				} else {
					DisplayBasicSelectDevice(false, true);
				}
			}
			if ($_SESSION['basic']['step'] == '5') {
				$start_job = true; 
				if (isset($_POST['Confirm'])) {
					try {
						$bacula->IsBasicJobReady($_SESSION['basic']['job']);
						$job_id = $bacula->StartBackup($_SESSION['basic']['job']);
						$_SESSION['basic']['job_id'] = $job_id;
						DisplayBackup($job_id, true);
					} catch (Exception $e) {
						WebDialogWarning($e->GetMessage());
						DisplayBasicConfirmBackup();
					}
				} else if (isset($_SESSION['basic']['job_id']) && (int)$_SESSION['basic']['job_id'] > 0) {
					DisplayBackup($_SESSION['basic']['job_id'], true);
					WebFormOpen($_SERVER['PHP_SELF'], "post");
					echo "<p align='center'>" .	
						WebButton("Cancel", LOCALE_LANG_RETURN_TO_SUMMARY, WEBCONFIG_ICON_BACK) . "</p>";
					WebFormClose();
				} else {
					try {
						$bacula->IsBasicJobReady($_SESSION['basic']['job']);
						DisplayBasicConfirmBackup();
					} catch (Exception $e) {
						WebDialogWarning($e->GetMessage());
						DisplayBasicConfirmBackup();
					}
				}
			}
		} else if (isset($_SESSION['basic']['type']) && $_SESSION['basic']['type'] == 'BackupServer') {
			$bacula->AutoConfigureDevices();
			if ($_SESSION['basic']['step'] == '4') {
				if (isset($_POST['AddBackupToClient'])) {
					try {
						$name = str_replace(" ", "", $_POST['name']);
						$bacula->AddSd($name, Bacula::MEDIA_SMB);
						$bacula->Commit();
						$mnt = Bacula::DEFAULT_MOUNT . "/" . str_replace(" ", "", $name);
						$sharedir = $_POST['sharedir'];
						if (isset($_POST['sharedir']) && ereg("^" . 
							Bacula::SHARED_DOCS . "(.*)$", $_POST['sharedir'], $match)
						) {
							$mnt .= $match[1];
							$sharedir = Bacula::SHARED_DOCS; 
						}
						$bacula->SetSdMount($name, $mnt);
						$bacula->SetSdLabelMedia($name, "yes");
						$share = base64_encode("username=" . $_POST['username'] . "|" .
												   "password=" . $_POST['password'] . "|" .
											       "address=" . $_POST['address'] . "|" .
												   "sharedir=" . $sharedir);
						$bacula->SetSdComment($name, "ShareInfo", $share);
						$bacula->Commit();
						# Continue on
						$_POST['ContinueStorage'] = true;
						$_SESSION['basic']['storage'] = $name;
					} catch (Exception $e) {
						WebDialogWarning($e->GetMessage());
						DisplayBasicAddBackupToClient();
					}
				}
				if (isset($_POST['ContinueStorage'])) {
					if (isset($_POST['storage']))
						$_SESSION['basic']['storage'] = $_POST['storage'];
					if (isset($_POST['storage']) && $_POST['storage'] == '-1') {
						WebDialogWarning(WEB_LANG_SELECT_SD);
						DisplayBasicSelectDevice(true, true);
					} else if (isset($_POST['storage']) && $_POST['storage'] == 'toclient') {
						DisplayBasicAddBackupToClient();
					} else {
						$hostname = new Hostname();
						$host = $hostname->Get();
						$client = $host;
						$_SESSION['basic']['client'] = $client;
						$jobname = $client . "_" . $_SESSION['basic']['storage'];
						$_SESSION['basic']['job'] = $jobname;
						try {
							$attributes = $bacula->GetJobAttributes($jobname);
							if (empty($attributes)) {
								# Need to create a job
								try {
									# If fileset for server does not exist, create it.
									$fileset = $host;
									if (!$bacula->DirectiveExists(Bacula::DIR_FILE_CONFIG, "FileSet", $fileset)) {
										$file = new File(Bacula::BACULA_USR . Bacula::DEFAULT_SERVER_FILESET);
										$mytablesubheaders = $file->GetContentsAsArray();
	
										# TODO - this breaks if changes made to /usr/bacula/server.fileset
										$mytablesubheaders[2] = "  Name = \"" .  $host . "\"";
										$bacula->AddDirectiveToDir($mytablesubheaders);
									}
									# If client for server does not exist, create it.
									if (!$bacula->DirectiveExists(Bacula::DIR_FILE_CONFIG, "Client", $client)) {
										$bacula->AddClient($client);
									}
									$bacula->AddJob($jobname);
									$bacula->Commit();
									$bacula->SetJobLevel($jobname, "Full");
									$bacula->SetJobClient($jobname, $client);
									$bacula->SetJobFileset($jobname, $fileset);
									$bacula->SetJobSchedule($jobname, "");  # Manual
									$bacula->SetJobStorageDevice($jobname, $_SESSION['basic']['storage']);
									$bacula->SetJobPool($jobname, Bacula::BASIC_POOL);
									$bacula->SetJobPriority($jobname, "10");
									$bacula->SetJobWriteBsr($jobname, "yes");
									$bacula->SetJobSendBsr($jobname, "yes");
									if (eregi("DVD", $_SESSION['basic']['storage']))
										$bacula->SetJobWritePartAfterJob($jobname, "yes");
									$bacula->Commit();
									$_SESSION['basic']['step'] = '5';
								} catch (Exception $e) {
									WebDialogWarning($e->GetMessage());
								}
							} else {
								$_SESSION['basic']['step'] = '5';
							}
						} catch (Exception $e) {
							if (!isset($_SESSION['basic']['storage'])) {
								WebDialogWarning($e->GetMessage());
								DisplayBasicSelectDevice(true, true);
							}
						}
					}
				} else {
					DisplayBasicSelectDevice(true, true);
				}
			}
			if ($_SESSION['basic']['step'] == '5') {
				$start_job = true; 
				if (isset($_POST['Confirm'])) {
					try {
						$bacula->IsBasicJobReady($_SESSION['basic']['job']);
						$job_id = $bacula->StartBackup($_SESSION['basic']['job']);
						$_SESSION['basic']['job_id'] = $job_id;
						DisplayBackup($job_id, true);
					} catch (Exception $e) {
						WebDialogWarning($e->GetMessage());
						DisplayBasicConfirmBackup(false);
					}
				} else if (isset($_SESSION['basic']['job_id']) && (int)$_SESSION['basic']['job_id'] > 0) {
					DisplayBackup($_SESSION['basic']['job_id'], true);
					WebFormOpen($_SERVER['PHP_SELF'], "post");
					echo "<p align='center'>" .	
						WebButton("Cancel", LOCALE_LANG_RETURN_TO_SUMMARY, WEBCONFIG_ICON_BACK) . "</p>";
					WebFormClose();
				} else {
					try {
						$bacula->IsBasicJobReady($_SESSION['basic']['job']);
						DisplayBasicConfirmBackup(false);
					} catch (Exception $e) {
						WebDialogWarning($e->GetMessage());
						DisplayBasicSelectDevice(true, true);
					}
				}
			}
		} else if (isset($_SESSION['basic']['type']) && $_SESSION['basic']['type'] == 'RestoreServer') {
			if ($_SESSION['basic']['step'] == '4') {
				if (isset($_POST['storage']))
					$_SESSION['basic']['storage'] = $_POST['storage'];
				if (isset($_POST['storage']) && $_POST['storage'] == '-1') {
					WebDialogWarning(WEB_LANG_SELECT_SD);
					DisplayBasicSelectDevice(true, false);
				} else if (isset($_POST['storage'])) {
					$_SESSION['basic']['step'] = '5';
				} else {
					DisplayBasicSelectDevice(true, false);
				}
			}
			if ($_SESSION['basic']['step'] == '5') {
				if (isset($_SESSION['basic']['job_id']) && (int)$_SESSION['basic']['job_id'] > 0) {
					DisplayBasicServerRestoreStatus();
					WebFormOpen($_SERVER['PHP_SELF'], "post");
					echo "<p align='center'>" .	
						WebButton("Cancel", LOCALE_LANG_RETURN_TO_SUMMARY, WEBCONFIG_ICON_BACK) . "</p>";
					WebFormClose();
				} else {
					try {
						if (isset($_POST['StartServerRestore'])) {
							if (!$_FILES["bsr"]["tmp_name"])
								throw new Exception(WEB_LANG_ERRMSG_INVALID_BSR);
							move_uploaded_file($_FILES["bsr"]["tmp_name"], "/tmp/" . $_FILES["bsr"]["name"]);
							$file = new File("/tmp/" . $_FILES["bsr"]["name"]);
							if (!$file->Exists())
								throw new Exception(WEB_LANG_ERRMSG_INVALID_BSR);
	
							$mytablesubheaders = $file->GetContentsAsArray();
							if (empty($mytablesubheaders))
								throw new Exception(WEB_LANG_ERRMSG_INVALID_BSR);

							$hostname = new Hostname();
							$host = $hostname->Get();
							$client = $host;
							$_SESSION['basic']['job'] = Bacula::RESTORE_JOB;
							$bacula->SetJobClient(Bacula::RESTORE_JOB, $client);
							$bacula->SetJobStorageDevice(Bacula::RESTORE_JOB, $_SESSION['basic']['storage']);
							$bacula->SetJobPool(Bacula::RESTORE_JOB, Bacula::BASIC_POOL);
							$bacula->Commit();
							$bacula->IsBasicJobReady($_SESSION['basic']['job']);
							$dir = Bacula::RESTORE_DEFAULT;
							if (isset($_POST['overwrite_original']))
								$dir = "";
   			         		$job_id = $bacula->StartBsrRestore(
								Bacula::RESTORE_JOB, $client, $_SESSION['basic']['storage'],
								"/tmp/" . $_FILES["bsr"]["name"], $dir, "always"
   			         		);
							# Start with 'clean slate' next time round
							$bacula->ResetRestoreToDefault();
							$_SESSION['basic']['job_id'] = $job_id;
							DisplayBasicServerRestoreStatus();
						} else {
							DisplayBasicServerRestore();
						}
					} catch (Exception $e) {
						WebDialogWarning($e->GetMessage());
						DisplayBasicServerRestore();
					}
				}
			}
		} else {
			# Reset variables
			WebFormOpen($_SERVER['PHP_SELF'], "post");
			echo "<p align='center'>" .	WebButton("Cancel", LOCALE_LANG_RETURN_TO_SUMMARY, WEBCONFIG_ICON_BACK) . "</p>";
			WebFormClose();
		}
		
		WebFormClose();
	} else {
		unset($_SESSION['basic']);
		$_SESSION['basic']['level'] = 'advanced';
		echo "
          <table width='100%' border='0' cellpadding='3' cellspacing='0'>
            <tr>
              <td colspan='2'>
                <h1>" . WEB_LANG_ADVANCED . " <span class='small'>(<a href='" .
				$_SERVER['PHP_SELF'] . "?blevel=basic'>" . WEB_LANG_SHOW_BASIC . "</a>)</span></h1>
              </td>
            </tr>
            <tr>
              <td width='50%'>
                <a href='backup-network.php?Action=backup'>" . WEB_ICON_BACKUP . "</a>
                <a href='backup-network.php?Action=backup'>" . WEB_LANG_BACKUP . "</a>
              </td>
              <td width='50%'>
                <a href='backup-network.php?Action=restore'>" . WEB_ICON_RESTORE . "</a>
                <a href='backup-network.php?Action=restore'>" . WEB_LANG_RESTORE . "</a>
              </td>
            </tr>
            <tr>
              <td>
                <a href='backup-network.php?Action=config'>" . WEB_ICON_GENERAL_CONFIG. "</a>
                <a href='backup-network.php?Action=config'>" . WEB_LANG_GENERAL_CONFIG. "</a>
              </td>
              <td>
                <a href='backup-network.php?Action=control'>" . WEB_ICON_CONTROL . "</a>
                <a href='backup-network.php?Action=control'>" . WEB_LANG_CONTROL . "</a>
              </td>
            </tr>
            <tr>
              <td>
                <a href='backup-network.php?Action=scheduled_jobs'>" . WEB_ICON_SCHEDULE . "</a>
                <a href='backup-network.php?Action=scheduled_jobs'>" . WEB_LANG_SCHEDULED_JOBS . "</a>
              </td>
              <td>
                <a href='backup-network.php?Action=status'>" . WEB_ICON_CURRENT_STATUS . "</a>
                <a href='backup-network.php?Action=status'>" . WEB_LANG_CURRENT_STATUS . "</a>
              </td>
            </tr>
            <tr>
              <td>
                <a href='backup-network.php?Action=report'>" . WEB_ICON_REPORT . "</a>
                <a href='backup-network.php?Action=report'>" . WEB_LANG_REPORT . "</a>
              </td>
              <td>
                <a href='logs.php?logfile=bacula'>" . WEB_ICON_LOG . "</a>
                <a href='logs.php?logfile=bacula'>" . WEB_LANG_LOG . "</a>
              </td>
            </tr>
            <tr>
              <td width='50%'>
                <a href='backup-network.php?Action=client'>" . WEB_ICON_CLIENT . "</a>
                <a href='backup-network.php?Action=client'>" . WEB_LANG_CLIENT . "</a>
              </td>
              <td width='50%'>
                <a href='backup-network.php?Action=schedule'>" . WEB_ICON_SCHEDULE . "</a>
                <a href='backup-network.php?Action=schedule'>" . WEB_LANG_SCHEDULE . "</a>
              </td>
            </tr>
            <tr>
              <td>
                <a href='backup-network.php?Action=fileset'>" . WEB_ICON_FILESET . "</a>
                <a href='backup-network.php?Action=fileset'>" . WEB_LANG_FILESET . "</a>
              </td>
              <td>
                <a href='backup-network.php?Action=job'>" . WEB_ICON_JOB . "</a>
                <a href='backup-network.php?Action=job'>" . WEB_LANG_JOB . "</a>
              </td>
            </tr>
            <tr>
              <td>
                <a href='backup-network.php?Action=pool'>" . WEB_ICON_POOL . "</a>
                <a href='backup-network.php?Action=pool'>" . WEB_LANG_POOL . "</a>
              </td>
              <td>
                <a href='backup-network.php?Action=storage'>" . WEB_ICON_STORAGE . "</a>
                <a href='backup-network.php?Action=storage'>" . WEB_LANG_STORAGE . "</a>
              </td>
            </tr>
            <tr>
              <td>
                <a href='backup-network.php?Action=virtual'>" . WEB_ICON_COMMAND . "</a>
                <a href='backup-network.php?Action=virtual'>" . WEB_LANG_COMMAND . "</a>
              </td>
              <td>
                <a href='backup-network.php?Action=catalog'>" . WEB_ICON_RESTORE_CATALOG . "</a>
                <a href='backup-network.php?Action=catalog'>" . WEB_LANG_RESTORE_CATALOG . "</a>
              </td>
            </tr>
          </table>
		";
	}

	WebFormOpen($_SERVER['PHP_SELF'], "post");
	echo "<input type='hidden' name='Action' value='daemon' />";
    WebTableOpen(WEBCONFIG_LANG_SERVER_STATUS, "100%");
	echo
		"<tr>
          <td class='mytableheader'>" . DAEMON_LANG_SERVICE . "</td>
          <td class='mytableheader'>" . DAEMON_LANG_STATUS . "</td>
		  <td class='mytableheader'>" . DAEMON_LANG_ONBOOT . "</td>
		</tr>" . $daemon_data . "
		<tr>
          <td colspan='2'>&#160;</td>
          <td>" . WebButton("UpdateDaemonsStatus", WEB_LANG_CONFIGURE, WEBCONFIG_ICON_GO) . "</td>
		</tr>
	";
    WebTableClose("100%");
    WebFormClose();
    WebTableOpen(WEB_LANG_24_HR, "100%");
	echo
		"<tr>
          <td class='mytableheader'>" . WEB_LANG_NAME . "</td>
		  <td class='mytableheader'>" . WEB_LANG_START_TIME . "</td>
		  <td class='mytableheader' align='center'>" . WEB_LANG_POOL_TYPE . "</td>
		  <td class='mytableheader' align='center'>" . WEB_LANG_STATUS . "</td>
		</tr>" . $table_data;
    WebTableClose("100%");

	if ($_SESSION['basic']['level'] == 'basic')
		return;
	# Display messages, if any.
	try {
		$messages = $bacula->GetMessages();
		echo "
		<table width='100%' border='0' cellpadding='0' cellspacing='0' align='center'>
		  <tr>
		    <td>
			  <TEXTAREA NAME='messages' ROWS='4' COLS='10' DISABLED style='color: #333333'>$messages</TEXTAREA>
			</td>
		  </tr>
		</table>
		";
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
}


///////////////////////////////////////////////////////////////////////////////
//
// DisplayDaemon()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayDaemon()
{
	$bacula_daemons = Array("bacula-dir", "bacula-fd", "bacula-sd", "bacula-mysqld");
	$dialog_mytablesubheader = Array("bacula-dir" => "", "bacula-fd" => "", "bacula-sd" => "", "bacula-mysqld" => "");
	$index = 0;
	$all_disabled = true;
	foreach($bacula_daemons as $individual) {
		$daemon = new Daemon($individual);
		if ($daemon->GetRunningState()) {
			$all_disabled = false;
			$status_button = WebButtonToggle("StopDaemon[$individual]", DAEMON_LANG_STOP);
			$status = "<span class='ok'><b>" . DAEMON_LANG_RUNNING . "</b></span>";
		} else {
			$status_button = WebButtonToggle("StartDaemon[$individual]", DAEMON_LANG_START);
			$status = "<span class='alert'><b>" . DAEMON_LANG_STOPPED . "</b></span>";
		}

		if ($daemon->GetBootState()) {
			$onboot_button = WebButtonToggle("DisableBoot[$individual]", DAEMON_LANG_TO_MANUAL);
			$onboot = "<span class='ok'><b>" . DAEMON_LANG_AUTOMATIC . "</b></span>";
		} else {
			$onboot_button = WebButtonToggle("EnableBoot[$individual]", DAEMON_LANG_TO_AUTO);
			$onboot = "<span class='alert'><b>" . DAEMON_LANG_MANUAL . "</b></span>";
		}

		// Build sub-table
		//----------------

		$dialog_mytablesubheader[$individual] .= "
		 <form action='backup-network.php' method='post'>
		   <table width='100%' border='0' cellspacing='0' cellpadding='0' align='center'>
		     <tr>
               <td colspan='6'><h2>" . $daemon->GetTitle() . "</h2></td>
		     </tr>
		     <tr>
			   <td nowrap align='right'><b>" . DAEMON_LANG_STATUS . " -</b>&#160; </td>
			   <td nowrap><b>$status</b></td>
			   <td width='10'>&#160; </td>
			   <td width='100'>$status_button</td>
			   <td width='10'>&#160; </td>
			   <td rowspan='2'>" . DAEMON_LANG_WARNING_START . "</td>
		     </tr>
		     <tr>
			   <td nowrap align='right'><b>" . DAEMON_LANG_ONBOOT . " -</b>&#160; </td>
			   <td nowrap><b>$onboot</b></td>
			   <td width='10'>&#160; </td>
			   <td nowrap>$onboot_button</td>
			   <td width='10'>&#160; </td>
		     </tr>
		   </table>
		   <input type='hidden' name='Action' value='daemon' />
		 </form>
		";

	}

	if ($all_disabled) {
		$mytablesubheader = "
           <p>" . WEB_LANG_NO_DAEMONS_RUNNING . "</p>
           <p align='center'>" .
           WebButton("StartAllDaemons", WEB_LANG_START_ALL_DAEMONS, WEBCONFIG_ICON_GO) . "
           </p>
		";
		echo "
          <form action='backup-network.php' method='post'>
		    <input type='hidden' name='Action' value='daemon' />
		    <table width='70%' border='0' cellspacing='0' cellpadding='0' align='center'>
		      <tr>
                <td>
		";
        WebDialogInfo($mytablesubheader);
		echo "
              </td>
            </tr>
          </table>
		</form>
		";
	} else {
		// Use the standard dialog-box
		//----------------------------
		foreach($dialog_mytablesubheader as $key => $status)
			WebDialogBox("dialogdaemon", '', WEBCONFIG_DIALOG_ICON_DAEMON, $status);
	}
	WebFormOpen($_SERVER['PHP_SELF'], "post");
    echo "<p align='center'>" .	WebButton("Return", LOCALE_LANG_RETURN_TO_SUMMARY, WEBCONFIG_ICON_BACK) . "</p>";
	WebFormClose();
}


///////////////////////////////////////////////////////////////////////////////
//
// DisplayConfig()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayConfig()
{
	// HTML output
	//------------
	global $bacula;

	# Director/Console
	$dir_address = $bacula->GetDirectorAddress();
	$dir_name = $bacula->GetDirectorName();
	$dir_port = $bacula->GetDirectorPort();
	$dir_password = $bacula->GetDirectorPassword();
	$dir_operator_email = $bacula->GetDirectorOperatorEmail();
	$dir_admin_email = $bacula->GetDirectorAdminEmail();
	$dir_mailserver = $bacula->GetDirectorMailserver();
	$dir_db_password = $bacula->GetDirectorDatabasePassword();
	if ($bacula->GetDirectorEmailOnEdit())
		$dir_email_on_edit = "CHECKED";

	# File
	$fd_name = $bacula->GetFileName();
	$fd_port = $bacula->GetFilePort();

	# Storage
	$sd_name = $bacula->GetStorageName();
	$sd_port = $bacula->GetStoragePort();

	WebFormOpen($_SERVER['PHP_SELF'], "post");
	echo "<input type='hidden' name='Action' value='config' />";
	WebTableOpen(WEB_LANG_CONFIG_TITLE, "100%");
	echo "
	  <tr>
		<td colspan='2' class='mytableheader'>" . WEB_LANG_GLOBAL_SETTINGS . "</td>
		<td width='40%' valign='top' class='help' rowspan='4'>
		  <p>" . WEB_LANG_BACULA_GLOBAL_HELP . "</p>
		</td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' align='right' nowrap>" . WEB_LANG_EMAIL_ON_EDIT . "</td>
		<td width='45%'><input type='checkbox' name='dir_email_on_edit' $dir_email_on_edit /></td>
	  </tr>
	  <tr>
		<td class='mytablesubheader'>&#160;</td>
		<td nowrap>" .
          WebButtonUpdate("UpdateConfig") . "&#160;&#160;" .
          WebButton("SendConfig", WEB_LANG_SEND_ALL_CONFIG_FILE, WEB_ICON_EMAIL) . "
        </td>
	  </tr>
      <tr>
		<td class='mytablesubheader'>&#160;</td>
		<td nowrap>" . WebButton("Return", LOCALE_LANG_RETURN_TO_SUMMARY, WEBCONFIG_ICON_BACK) . "</td>
      </tr>
	  <tr>
		<td colspan='2' class='mytableheader'>" . WEB_LANG_DIR_SETTINGS . "</td>
		<td valign='top' class='help' rowspan='10'>
		  <p>" . WEB_LANG_BACULA_DIR_HELP . "</p>
		</td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' align='right' nowrap width='15%'>" . WEB_LANG_NAME . "</td>
		<td><input type='text' name='dir_name' value='$dir_name' style='width:180px' /></td>
	  </tr>
	  <tr>
		<td align='right' nowrap class='mytablesubheader'>" . WEB_LANG_MACHINE_ADDRESS . "</td>
		<td><input type='text' name='dir_address' value='$dir_address' style='width:180px' /></td>
	  </tr>
	  <tr>
		<td align='right' nowrap class='mytablesubheader'>" . WEB_LANG_MACHINE_PORT . "</td>
		<td><input type='text' name='dir_port' value='$dir_port' style='width:50px' /></td>
	  </tr>
	  <tr>
		<td align='right' nowrap class='mytablesubheader'>" . WEB_LANG_MACHINE_PASSWORD . "</td>
		<td><input type='text' name='dir_password' value='$dir_password' style='width:280px' /></td>
	  </tr>
	  <tr>
		<td align='right' nowrap class='mytablesubheader'>" . WEB_LANG_OPERATOR_EMAIL . "</td>
		<td><input type='text' name='dir_operator_email' value='$dir_operator_email' style='width:180px' /></td>
	  </tr>
	  <tr>
		<td align='right' nowrap class='mytablesubheader'>" . WEB_LANG_ADMIN_EMAIL . "</td>
		<td><input type='text' name='dir_admin_email' value='$dir_admin_email' style='width:180px' /></td>
	  </tr>
	  <tr>
		<td align='right' nowrap class='mytablesubheader'>" . WEB_LANG_MAILSERVER . "</td>
		<td><input type='text' name='dir_mailserver' value='$dir_mailserver' style='width:180px' /></td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' align='right' nowrap>" . WEB_LANG_DATABASE_PASSWORD . "</td>
		<td><input type='password' name='dir_db_password' value='$dir_db_password' style='width:180px' /></td>
	  </tr>
	  <tr>
		<td class='mytablesubheader'>&#160;</td>
		<td nowrap>" .
          WebButtonUpdate("UpdateConfigDir") . "&#160;&#160;" .
          WebButton("UploadConfigDir", WEB_LANG_UPLOAD_CONFIG_FILE, WEB_ICON_RESTORE) . "&#160;&#160;" .
          WebButton("SendConfigDir", WEB_LANG_SEND_CONFIG_FILE, WEB_ICON_EMAIL) . "
        </td>
	  </tr>
	  <tr>
		<td colspan='2' class='mytableheader'>" . WEB_LANG_FD_SETTINGS . "</td>
		<td valign='top' class='help' rowspan='4'>
		  <p>" . WEB_LANG_BACULA_FD_HELP . "</p>
		</td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' align='right' nowrap>" . WEB_LANG_NAME . "</td>
		<td><input type='text' name='fd_name' value='$fd_name' style='width: 180px' /></td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' align='right' nowrap>" . WEB_LANG_MACHINE_PORT . "</td>
		<td><input type='text' name='fd_port' value='$fd_port'style='width: 50px' /></td>
	  </tr>
	  <tr>
		<td class='mytablesubheader'>&#160;</td>
		<td nowrap>" .
          WebButtonUpdate("UpdateConfigFd") . "&#160;&#160;" .
          WebButton("UploadConfigFd", WEB_LANG_UPLOAD_CONFIG_FILE, WEB_ICON_RESTORE) . "&#160;&#160;" .
          WebButton("SendConfigFd", WEB_LANG_SEND_CONFIG_FILE, WEB_ICON_EMAIL) . "
        </td>
	  </tr>
	  <tr>
		<td colspan='2' class='mytableheader'>" . WEB_LANG_SD_SETTINGS . "</td>
		<td valign='top' class='help' rowspan='5'>
		  <p>" . WEB_LANG_BACULA_SD_HELP . "</p>
		</td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' align='right' nowrap>" . WEB_LANG_NAME . "</td>
		<td><input type='text' name='sd_name' value='$sd_name' style='width: 180px' /></td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' align='right' nowrap>" . WEB_LANG_MACHINE_PORT . "</td>
		<td><input type='text' name='sd_port' value='$sd_port' style='width: 50px' /></td>
	  </tr>
	  <tr>
		<td class='mytablesubheader'><img src='../images/transparent.gif' width='1' height='80' alt='' /></td>
		<td valign='top' nowrap>" .
          WebButtonUpdate("UpdateConfigSd") . "&#160;&#160;" .
          WebButton("UploadConfigSd", WEB_LANG_UPLOAD_CONFIG_FILE, WEB_ICON_RESTORE) . "&#160;&#160;" .
          WebButton("SendConfigSd", WEB_LANG_SEND_CONFIG_FILE, WEB_ICON_EMAIL) . "
        </td>
	  </tr>
	";
	WebTableClose("100%");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayScheduledJobs()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayScheduledJobs()
{
	// HTML output
	//------------
	global $bacula;
	$regex_level = "^[[:space:]]*Run[[:space:]]*=[[:space:]]*(Full|Differential|Incremental)(.*)at(.*$)";
	$job_list = $bacula->GetJobList();
	asort($job_list);
	$date_options = $bacula->GetDateOptions();
	$index = 0;
	foreach ($job_list as $job) {
		# Don't show non-backup jobs
		if ($bacula->GetJobType($job) == "Restore")
			continue;

		if (is_integer($index/2))
			$tr_class = "";
		else
			$tr_class = " class='mytablesubheader'";
		$attributes = $bacula->GetScheduleAttributes($bacula->GetJobSchedule($job));
		$subindex = 0;
		foreach ($attributes as $line) {
			if (eregi($regex_level, $line, $match)) {
				# Level
				$level = trim($match[1]);
				# Date
				foreach ($date_options as $key => $value) {
					if (trim(strtolower($match[2])) == trim(strtolower($key))) {
						$date = $value;
						break;
					} else {
						$date = $value;
					}
				}
				# Time
				$time = trim($match[3]);
			} else {
				# Not intrested in other parameters
				continue;
			}

			if ($subindex > 0)
				$job = "&#160;";
			else
				$job = "<a href='backup-network.php?Action=job&Edit[$job]'=Edit>$job</a>";
			$table_data .= "
			  <tr" . $tr_class . ">
				<td width='30%'>$job</td>
				<td width='15%'>" . $level . "</td>
				<td width='55%'>" . $date . "&#160;@&#160;" . $time . "</td>
			  </tr>
			";
			$subindex++;
		}
		$index++;
	}
	WebTableOpen(WEB_LANG_SCHEDULED_JOBS, "70%");
	echo "
	  <tr>
		<td class='mytableheader'>" . WEB_LANG_JOB_NAME . "</td>
		<td class='mytableheader'>" . WEB_LANG_TYPE . "</td>
		<td class='mytableheader'>" . WEB_LANG_SCHEDULE_NAME . "</td>
	  </tr>
	";
	echo $table_data;
	WebTableClose("70%");
	WebFormOpen($_SERVER['PHP_SELF'], "post");
    echo "<p align='center'>" .	WebButton("Return", LOCALE_LANG_RETURN_TO_SUMMARY, WEBCONFIG_ICON_BACK) . "</p>";
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayUploadConfig()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayUploadConfig($file)
{
	global $bacula;
	$daemon = new Daemon("bacula-dir");
	if ($file == Bacula::DIR_FILE_CONFIG) {
		$console = "
		  <tr>
			<td width='40%' align='right' class='mytablesubheader'>" . basename(Bacula::CONSOLE_FILE_CONFIG) . "</td>
			<td width='60%'><input type='file' name='file2' style='width: 160px' /></td>
		  </tr>
		";
	}
	WebFormOpen($_SERVER['PHP_SELF'], "post");
	echo "<input type='hidden' name='Action' value='config' />";
	WebTableOpen(WEB_LANG_UPLOAD_AND_REPLACE, "60%");
	echo "
	  <tr>
		<td width='40%' align='right' class='mytablesubheader'>" . basename($file) . "</td>
        <td width='60%'><input type='file' name='file1' style='width: 160px' /></td>
	  </tr>" . $console . "
	  <tr>
		<td class='mytablesubheader'>&#160;</td>
        <td>" . WebButton("UploadConfig", WEB_LANG_UPLOAD_CONFIG_AND_RESTART, WEB_ICON_RESTORE) . "</td>
	  </tr>"
	;
	WebTableClose("60%");
	WebFormClose();
	WebDialogInfo(DAEMON_LANG_WARNING_START);
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayReport()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayReport($job)
{
	global $bacula;
	$job_list = $bacula->GetJobList();
	foreach ($job_list as $this_job) {
		# Don't show non-backup jobs
		if ($bacula->GetJobType($this_job) != "Backup")
			continue;
		if ($job == $this_job || !$job) {
			$job = $this_job;
			$job_options .= "<option value='$this_job' SELECTED>$this_job</option>";
		} else {
			$job_options .= "<option value='$this_job'>$this_job</option>";
		}
	}
	$chartheader = array('');
	$chartdata = array('');
    $start_date = strftime("%Y-%m-%d %H:%M:%S", time()-2678400);
    $end_date = strftime("%Y-%m-%d %H:%M:%S", time());

	$raw_data = $bacula->GetJobStats($end_date, $start_date, $job);
	$unit_info = $bacula->GetUnitAndMultiplier("JobBytes", $raw_data);
	foreach($raw_data as $row) {
		$chartdata[] = $row["JobBytes"]/$unit_info[0];
		$chartheader[] = strftime("%y-%m-%d %H:%M", strtotime($row["EndTime"]));
		if (is_integer($index/2))
			$tr_class = " class='mytablesubheader'";
		else
			$tr_class = "";

		# Job status
		if ($row["JobStatus"] == "T")
			$state = WEBCONFIG_ICON_OK;
		else
			$state = WEBCONFIG_ICON_WARNING;

		# Level
		if ($row["Level"] == "F")
			$level = BACULA_LANG_FULL;
		elseif ($row["Level"] == "I")
			$level = BACULA_LANG_INCREMENTAL;
		elseif ($row["Level"] == "D")
			$level = BACULA_LANG_DIFFERENTIAL;

		$table_data = "
            <tr$tr_class>
			  <td>" . strftime("%Y-%m-%d %H:%M", strtotime($row["StartTime"])) . "</td>
              <td>" . $row["Elapsed"] . "</td>
              <td>" . $level . "</td>
              <td align='right'>" . $row["JobFiles"] . "</td>
              <td align='right'>" . $bacula->GetFormattedBytes($row["JobBytes"], 1) . "</td>
              <td align='center'>" . $state . "</td>
			</tr>" . $table_data
		;
		$index++;
	}

	WebFormOpen($_SERVER['PHP_SELF'], "post");
	WebTableOpen($job, "100%");
	echo "
          <tr>
            <td align='right' colspan='6'>
              <select name='job'>$job_options</select>
              <input type='hidden' name='Action' value='report' />
		      " . WebButtonUpdate("Update") . "&#160;&#160;&#160;&#160;
            </td>
          </tr>
          <tr>
            <td align='center' colspan='6'>
	";
	WebChart(
		$job,
		"column",
		"550",
		"150",
		array($chartheader, $chartdata),
		array(CHART_COLOR_ALERT, CHART_COLOR_OK1, CHART_COLOR_OK2),
		0,
		1,
		$unit_info[1]
	);
	echo "
            </td>
          </tr>
	";
	echo "<tr class='mytableheader'>
            <td>" . WEB_LANG_START_TIME . "</td>
            <td>" . WEB_LANG_ELAPSED_TIME . "</td>
            <td>" . WEB_LANG_TYPE . "</td>
            <td align='right' width='15%'>" . WEB_LANG_FILES . "</td>
            <td align='right' width='15%'>" . WEB_LANG_SIZE . "</td>
            <td align='center' width='10%'>" . WEB_LANG_STATUS . "</td>
          </tr>" . $table_data;
	WebTableClose("100%");
    echo "<p align='center'>" .	WebButton("Return", LOCALE_LANG_RETURN_TO_SUMMARY, WEBCONFIG_ICON_BACK) . "</p>";
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayBackup()
//
///////////////////////////////////////////////////////////////////////////////


function DisplayBackup($job_id, $refresh)
{
	global $bacula;
	if (!$job_id) {
		$job_list = $bacula->GetJobList();
		asort($job_list);
		$job_options = "<option value=''>" . WEB_LANG_SELECT . "</option>";
		foreach ($job_list as $job) {
			# Don't show non-backup jobs
			if ($bacula->GetJobType($job) != "Backup")
				continue;
			$job_options .= "<option value='$job'>$job</option>";
		}

		WebFormOpen($_SERVER['PHP_SELF'], "post");
		WebTableOpen(WEB_LANG_BACKUP, "80%");
		echo "
		  <tr>
			<td class='mytablesubheader' NOWRAP>" . WEB_LANG_JOB_NAME . "</td>
			<td NOWRAP>
			  <select name='job'>$job_options</select>
			</td>
			<td width='35%' valign='top' class='help' rowspan='6'>
			  <p>" . WEB_LANG_BACULA_BACKUP_HELP . "</p>
			</td>
		  </tr>
		  <tr>
			<td class='mytablesubheader' NOWRAP>&#160;</td>
			<td NOWRAP>" .
			  WebButton("StartBackup", WEB_LANG_START, WEBCONFIG_ICON_GO) . "&#160;" .
			  WebButton("Return", LOCALE_LANG_RETURN_TO_SUMMARY, WEBCONFIG_ICON_BACK) . "
			  <input type='hidden' name='Action' value='backup' />
			</td>
		  </tr>
		";
		WebTableClose("80%");
		WebFormClose();
	} else if ($job_id > 0) {
		$show_refresh = true;
		# Only display job started once
		if (!$refresh)
			WebDialogInfo(WEB_LANG_JOB_STARTED . " " . $job_id);

		$status = $bacula->GetJobStatus($job_id);
		if ($status['JobStatus'] == "R" || $status['JobStatus'] == "C") {
			WebDialogInfo(WEB_LANG_BACKUP_RUNNING);
		} else if ($status['JobStatus'] == "T") {
			WebDialogInfo(WEB_LANG_BACKUP_COMPLETE);
			$show_refresh = false;
			unset($_SESSION['basic']);
			WebTableOpen(WEB_LANG_BACKUP, "100%");
			echo "
              <tr>
				<td class='mytableheader' colspan='2'>" . WEB_LANG_STATS . "</td>
              </tr>
              <tr>
				<td class='mytablesubheader' NOWRAP width='30%' align='right'>" . WEB_LANG_JOB_NAME . "</td>
				<td>" . $status['Name']. "</td>
              </tr>
              <tr>
				<td align='right' class='mytablesubheader'>" . WEB_LANG_NUMBER_OF_FILES . "</td>
				<td>" . $status['JobFiles']. "</td>
              </tr>
              <tr>
				<td align='right' class='mytablesubheader'>" . WEB_LANG_SIZE . "</td>
				<td>" . $bacula->GetFormattedBytes($status['JobBytes'], 2) . "</td>
              </tr>
			";
			
			WebTableClose("100%");
		} else {
			WebDialogWarning(WEB_LANG_BACKUP_FAILED);
			$show_refresh = false;
			unset($_SESSION['basic']);
		}
		if ($show_refresh) {
			WebFormOpen($_SERVER['PHP_SELF'], "post");
			echo "
				<p align='center'>" .
				WebButton("refresh", WEB_LANG_REFRESH, WEBCONFIG_ICON_GO) . "
				<input type='hidden' name='Action' value='backup' />
				<input type='hidden' name='job_id' value='$job_id' />
    		    <input type='hidden' name='bstep' value='5' />
				</p>
			";
			WebFormClose();
		}
		if ($_SESSION['basic']['level'] == 'advanced') {
			$messages = $bacula->GetMessages();
			echo "
			<table width='100%' border='0' cellpadding='0' cellspacing='0' align='center'>
		 	  <tr>
				<td>
				  <TEXTAREA NAME='messages' ROWS='10' COLS='10' DISABLED style='color: #333333'>$messages</TEXTAREA>
				</td>
		  	  </tr>
			</table>
			";
			WebFormOpen($_SERVER['PHP_SELF'], "post");
			echo WebButton("Return", LOCALE_LANG_RETURN_TO_SUMMARY, WEBCONFIG_ICON_BACK);
			WebFormClose();
		}
	}
}


///////////////////////////////////////////////////////////////////////////////
//
// DisplayRestore()
//
///////////////////////////////////////////////////////////////////////////////


function DisplayRestore($job_id, $refresh)
{
	global $bacula;
	if (!$job_id) {
		# Client
		$client_list = $bacula->GetClientList();
		asort($client_list);
		$client_options = "<option value=''>" . WEB_LANG_SELECT . "</option>";
		foreach ($client_list as $my_client)
			$client_options .= "<option value='$my_client'>$my_client</option>";

		# Pools 
		$pool_list = $bacula->GetPoolList();
		asort($pool_list);
		$pool_options = "<option value=''>" . WEB_LANG_SELECT . "</option>";
		foreach ($pool_list as $my_pool)
			$pool_options .= "<option value='$my_pool'>$my_pool</option>";

		# Job
		$job_list = $bacula->GetJobList();
		asort($job_list);
		$job_options = "<option value=''>" . WEB_LANG_SELECT . "</option>";
		foreach ($job_list as $this_job) {
			# Don't show non-restore jobs
			if ($bacula->GetJobType($this_job) != "Restore")
				continue;
			$job_options .= "<option value='$this_job'>$this_job</option>";
		}

		# Storage device
		$device_list = $bacula->GetSdList();
		asort($device_list);
		$device_options = "<option value=''>" . WEB_LANG_SELECT . "</option>";
		foreach ($device_list as $device)
			$device_options .= "<option value='$device'>$device</option>";

		$fileset_list = $bacula->GetFilesetList();
		asort($fileset_list);
		$fileset_options = "<option value=''>" . WEB_LANG_SELECT . "</option>";
		foreach ($fileset_list as $fileset)
			$fileset_options .= "<option value='$fileset'>$fileset</option>";

		# Replace options
		$replace_list = $bacula->GetReplaceOptions();
		foreach ($replace_list as $replace => $display)
			$replace_options .= "<option value='$replace'>$display</option>";

		WebFormOpen($_SERVER['PHP_SELF'], "post");
		WebTableOpen(WEB_LANG_STANDARD_RESTORE, "100%");
		echo "
          <tr>
			<td class='mytablesubheader' NOWRAP valign='top' width='20%'>" . WEB_LANG_CLIENT_NAME . "</td>
			<td NOWRAP valign='top'>
			  <select name='client' style='width:150px'>$client_options</select>
			</td>
			<td width='40%' valign='top' class='help' rowspan='7'>
			  <p>" . WEB_LANG_BACULA_RESTORE_HELP . "</p>
			</td>
		  </tr>
		  <tr>
			<td class='mytablesubheader' NOWRAP valign='top'>" . WEB_LANG_DEVICE_NAME . "</td>
			<td valign='top'>
              <select name='device' style='width:150px'>$device_options</select>
            </td>
		  </tr>
		  <tr>
			<td class='mytablesubheader' NOWRAP valign='top'>" . WEB_LANG_POOL_NAME . "</td>
			<td NOWRAP valign='top'>
			  <select name='pool' style='width:150px'>$pool_options</select>
			</td>
		  </tr>
		  <tr>
			<td class='mytablesubheader' NOWRAP valign='top'>" . WEB_LANG_FILESET_NAME . "</td>
			<td NOWRAP valign='top'>
			  <select name='fileset' style='width:150px'>$fileset_options</select>
			</td>
		  </tr>
		  <tr>
			<td class='mytablesubheader' NOWRAP valign='top'>" . WEB_LANG_REPLACE . "</td>
			<td NOWRAP valign='top'>
			  <select name='replace' style='width:150px'>$replace_options</select>
			</td>
		  </tr>
		  <tr>
			<td class='mytablesubheader' NOWRAP valign='top'>" . WEB_LANG_LOCATION . "</td>
			<td valign='top'>
              <input type='text' name='where' value='" . Bacula::RESTORE_DEFAULT . "' style='width:180px' />
            </td>
		  </tr>
		  <tr>
		    <td class='mytablesubheader'>&#160;</td>
			<td NOWRAP valign='top'>" .
			  WebButton("StartRestore", WEB_LANG_START, WEBCONFIG_ICON_GO) . "&#160;" .
			  WebButton("Return", LOCALE_LANG_RETURN_TO_SUMMARY, WEBCONFIG_ICON_BACK) . "
			  <input type='hidden' name='Action' value='restore' />
			</td>
		  </tr>
		";
		WebTableClose("100%");
		WebFormClose();

		WebFormOpen($_SERVER['PHP_SELF'], "post");
		WebTableOpen(WEB_LANG_BSR_RESTORE, "100%");
		echo "
          <tr>
			<td class='mytablesubheader' NOWRAP valign='top'>" . WEB_LANG_JOB_NAME . "</td>
			<td valign='top'>
              <select name='job' style='width:150px'>$job_options</select>
            </td>
			<td width='40%' valign='top' class='help' rowspan='7'>
			  <p>" . WEB_LANG_BACULA_RESTORE_BSR_HELP . "</p>
			</td>
          </tr>
          <tr>
			<td class='mytablesubheader' NOWRAP valign='top' width='20%'>" . WEB_LANG_CLIENT_NAME . "</td>
			<td NOWRAP valign='top'>
			  <select name='client' style='width:150px'>$client_options</select>
			</td>
		  </tr>
		  <tr>
			<td class='mytablesubheader' NOWRAP valign='top'>" . WEB_LANG_DEVICE_NAME . "</td>
			<td valign='top'>
              <select name='device' style='width:150px'>$device_options</select>
            </td>
		  </tr>
		  <tr>
			<td class='mytablesubheader' NOWRAP valign='top'>" . WEB_LANG_REPLACE . "</td>
			<td NOWRAP valign='top'>
			  <select name='replace' style='width:150px'>$replace_options</select>
			</td>
		  </tr>
		  <tr>
			<td class='mytablesubheader' NOWRAP valign='top'>" . WEB_LANG_LOCATION . "</td>
			<td valign='top'>
              <input type='text' name='where' value='" . Bacula::RESTORE_DEFAULT . "' style='width:180px' />
            </td>
		  </tr>
		  <tr>
			<td class='mytablesubheader' NOWRAP>" . WEB_LANG_UPLOAD . "</td>
			<td NOWRAP>
	          <input type='file' name='bsr' style='width: 160px' />
            </td>
		  </tr>
		  <tr>
		    <td class='mytablesubheader'>&#160;</td>
			<td NOWRAP valign='top'>" .
			  WebButton("StartBsrRestore", WEB_LANG_START, WEBCONFIG_ICON_GO) . "&#160;" .
			  WebButton("Return", LOCALE_LANG_RETURN_TO_SUMMARY, WEBCONFIG_ICON_BACK) . "
			  <input type='hidden' name='Action' value='restore' />
			</td>
		  </tr>
		";
		WebTableClose("100%");
		WebFormClose();
	} else if ($job_id > 0) {
		# Only display job started once
		if (!$refresh)
			WebDialogInfo(WEB_LANG_JOB_STARTED . " " . $job_id);

		$messages = "";
		try {
			$status = $bacula->GetJobStatus($job_id);
			if ($status['JobStatus'] == "T") {
				WebDialogInfo(WEB_LANG_RESTORE_COMPLETE);
				WebFormOpen($_SERVER['PHP_SELF'], "post");
				echo WebButton("Return", LOCALE_LANG_RETURN_TO_SUMMARY, WEBCONFIG_ICON_BACK);
				WebFormClose();
				return;
			} else {
				$messages = $bacula->GetMessages();
				// If restoring catalog, we won't have job in database...just parse for restore OK.
				if (eregi(".*Restore OK.*", $messages)) {
					WebDialogInfo(WEB_LANG_RESTORE_COMPLETE);
					WebFormOpen($_SERVER['PHP_SELF'], "post");
					echo WebButton("Return", LOCALE_LANG_RETURN_TO_SUMMARY, WEBCONFIG_ICON_BACK);
					WebFormClose();
					return;
				}
			}
			WebFormOpen($_SERVER['PHP_SELF'], "post");
			echo "
				<p align='center'>" .
				WebButton("refresh", WEB_LANG_REFRESH, WEBCONFIG_ICON_GO) .
				WebButton("Return", LOCALE_LANG_RETURN_TO_SUMMARY, WEBCONFIG_ICON_BACK) . "
			    <input type='hidden' name='Action' value='restore' />
				<input type='hidden' name='job_id' value='$job_id' />
				</p>
			";
			WebFormClose();
			echo "
			<table width='100%' border='0' cellpadding='0' cellspacing='0' align='center'>
			  <tr>
				<td>
				  <TEXTAREA NAME='messages' ROWS='10' COLS='10' DISABLED style='color: #333333'>$messages</TEXTAREA>
				</td>
			  </tr>
			</table>
			";
		} catch (Exception $e) {
			WebDialogWarning($e->GetMessage());
		}
	}
}


///////////////////////////////////////////////////////////////////////////////
//
// DisplayStatus()
//
///////////////////////////////////////////////////////////////////////////////


function DisplayStatus()
{
	global $bacula;

	try {
		$status = $bacula->GetStatus();
		echo "
		<table width='100%' border='0' cellpadding='0' cellspacing='0' align='center'>
		  <tr>
			<td>
			  <TEXTAREA NAME='messages' ROWS='30' COLS='10' DISABLED style='color: #333333; font-size: 11px; font-family: monospace'>$status</TEXTAREA>
			</td>
		  </tr>
		</table>
		";
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	WebFormOpen($_SERVER['PHP_SELF'], "post");
    echo "<p align='center'>" .	WebButton("Return", LOCALE_LANG_RETURN_TO_SUMMARY, WEBCONFIG_ICON_BACK) . "</p>";
	WebFormClose();
}


///////////////////////////////////////////////////////////////////////////////
//
// DisplayControl()
//
///////////////////////////////////////////////////////////////////////////////


function DisplayControl()
{
	global $bacula;

	$device_list = $bacula->GetSdList();
	$device_action_list = $bacula->GetDeviceActionOptions();

	foreach ($device_list as $device)
		$device_options .= "<option value='$device'>$device</option>";

	foreach ($device_action_list as $action => $display_action)
		$device_action_options .= "<option value='$action'>$display_action</option>";

    WebFormOpen($_SERVER['PHP_SELF'], "post");
    WebTableOpen(WEB_LANG_CONTROL, "80%");
	echo "
	  <tr>
		<td class='mytablesubheader' align='right' nowrap width='40%'>" . WEB_LANG_DEVICE_NAME . "</td>
		<td>
          <select name='device_name'>$device_options</select>
        </td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' align='right' nowrap width='40%'>" . WEB_LANG_ACTION . "</td>
		<td>
          <select name='device_action'>$device_action_options</select>
	      <input type='hidden' name='Action' value='control' />
        </td>
	  </tr>
	  <tr>
		<td class='mytablesubheader'>&#160;</td>
		<td>
		   " . WebButtonContinue("DeviceAction") . "&#160;" .
           WebButton("Return", LOCALE_LANG_RETURN_TO_SUMMARY, WEBCONFIG_ICON_BACK) . " 
        </td>
	  </tr>
	";
	WebTableClose("80%");
	WebFormClose();
}


///////////////////////////////////////////////////////////////////////////////
//
// DisplayVirtualConsole()
//
///////////////////////////////////////////////////////////////////////////////


function DisplayVirtualConsole($reply)
{

	echo "<script type='text/javascript' src='bacula.js'></script>";
	WebFormOpen($_SERVER['PHP_SELF'], "post");
	echo "
		<p align='center'>" . WEB_LANG_SEND_COMMAND . ":&#160;
		<input type='text' id='command' name='command' value='' style='width:220px' onkeypress='return CommandKeypress(event);' />
		</p>
		<input type='hidden' id='command_submit' name='command' value='Submit' />
	    <input type='hidden' name='Action' value='virtual' />
	";
	WebFormClose();

	echo "
		<table width='100%' border='0' cellpadding='0' cellspacing='0' align='center'>
		  <tr>
			<td>
			  <TEXTAREA id='console_output' ROWS='20' COLS='10' DISABLED style='color: #333333; font-size: 10px; font-family: monospace'></TEXTAREA>
			</td>
		  </tr>
		</table>
	";
	WebFormOpen($_SERVER['PHP_SELF'], "post");
    echo "<p align='center'>" .	WebButton("Return", LOCALE_LANG_RETURN_TO_SUMMARY, WEBCONFIG_ICON_BACK) . "</p>";
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayRestoreCatalog()
//
///////////////////////////////////////////////////////////////////////////////


function DisplayRestoreCatalog()
{
	global $bacula;
	WebFormOpen($_SERVER['PHP_SELF'], "post");
	WebTableOpen(WEB_LANG_RESTORE_CATALOG, "100%");
	echo "
	  <tr>
	    <td NOWRAP class='mytablesubheader' colspan='2'>
          <input type='radio' name='catalog_method' value='bsr' CHECKED />" . WEB_LANG_CATALOG_BSR . "
        </td>
		<td width='35%' valign='top' class='help' rowspan='7'>
		  <p>" . WEB_LANG_BACULA_RESTORE_CATALOG_HELP . "</p>
	    </td>
	  </tr>
	  <tr>
        <td width='4%'>&#160;</td>
        <td>
	      <input type='file' name='bsr' style='width: 160px' />
	    </td>
	  </tr>
	  <tr>
	    <td NOWRAP class='mytablesubheader' colspan='2'>
          <input type='radio' name='catalog_method' value='local' />" . WEB_LANG_CATALOG_LOCAL . "
	    </td>
	  </tr>
	  <tr>
        <td>&#160;</td>
	    <td NOWRAP>
	      <input type='text' name='local' style='width: 195px' /><br />
	    </td>
	  </tr>
	  <tr>
	    <td NOWRAP class='mytablesubheader' colspan='2'>
          <input type='radio' name='catalog_method' value='upload' />" . WEB_LANG_CATALOG_UPLOAD . "
        </td>
	  </tr>
	  <tr>
        <td>&#160;</td>
        <td>
	      <input type='file' name='upload' style='width: 160px' />
	    </td>
	  </tr>
	  <tr>
        <td>&#160;</td>
	    <td valign='top'>
	       <input type='hidden' name='Action' value='catalog' />
		   " . WebButtonContinue("RestoreCatalog") . "&#160;" .
		   WebButton("Return", LOCALE_LANG_RETURN_TO_SUMMARY, WEBCONFIG_ICON_BACK) . "
	    </td>
      </tr>
	";
	WebTableClose("100%");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayConfirmCatalogRestore()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayConfirmCatalogRestore($method, $restore)
{
	if ($method == "upload") {
		$filesource = "/tmp/" . $restore;
	} else if ($method == "local" || $method == "bsr") {
		$filesource = $restore;
	}
	WebFormOpen($_SERVER['PHP_SELF'], "post");
	WebTableOpen(LOCALE_LANG_CONFIRM, "450");
	echo "
      <tr>
        <td align='center'>
          <p>" . WEBCONFIG_ICON_WARNING . " " . WEB_LANG_RESTORE_CATALOG_CONFIRM . "?</p>
		  <pre>$restore</pre>
          <p>" . WebButtonContinue("RestoreCatalog") . " " . WebButtonCancel("Cancel") . "<br />
          <input type='hidden' name='filesource' value='$filesource' />
          <input type='hidden' name='Action' value='catalog' /></p>
        </td>
      </tr>
    ";
	WebTableClose("450");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayJob()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayJob()
{
	global $bacula;

	list($edit_index, $edit_job) = split("\\|", $edit);
	$spacer = 135;

	# Fetch job list
	$job_list = $bacula->GetJobList();
	asort($job_list);

	$rowspan = (sizeof($job_list) + 4);

	// HTML output
	//------------
	WebFormOpen($_SERVER['PHP_SELF'], "post");
	echo "<input type='hidden' name='Action' value='job' />";
	WebTableOpen(WEB_LANG_JOB_TITLE, "100%");
	echo "
	  <tr>
		<td class='mytableheader' colspan='3'>" . WEB_LANG_EXISTING_JOBS . "</td>
		<td width='40%' valign='top' class='help' rowspan='" . $rowspan . "'>
		  <p>" . WEB_LANG_BACULA_JOB_HELP . "</p>
		</td>
	  </tr>
	";
	foreach ($job_list as $index => $job) {
		echo "
			<tr>
			  <td>$job</td>
		";
		$restriction_code = $bacula->IsJobRestricted($job);
		if ($restriction_code == Bacula::RESTRICT_ALL) {
			echo "<td>" . WEB_ICON_LOCKED . "</td><td>" . WEB_ICON_LOCKED . "</td>";
		} else {
			if ($restriction_code == Bacula::RESTRICT_EDIT) {
				echo "<td>" . WEB_ICON_LOCKED . "</td>";
			} else {
				echo "
				  <td width='10%' NOWRAP>
				  " . WebButtonEdit("Edit[$job]") . "
				  </td>
				";
			}
			if ($restriction_code == Bacula::RESTRICT_DELETE) {
				echo "<td>" . WEB_ICON_LOCKED . "</td>";
			} else {
				echo "
				  <td width='10%' NOWRAP>
				  " . WebButtonDelete("Delete[$index|$job]") . "
				  </td>
				";
			}
		}
		echo "</tr>";
	}

	if (sizeof($job_list) == 0)
		echo "<td colspan='3'>" . WEB_LANG_NO_JOBS . "</td>";

	echo "
		<tr>
		  <td class='mytableheader' colspan='3'>" . WEB_LANG_ADD_JOB . "</td>
		</tr>
		<tr>
		  <td>
	        <input type='text' name='newjob' value='$newjob' style='width: 180px' />
          </td>
		  <td>&#160;</td>
		  <td width='10%' NOWRAP>" . WebButtonAdd("Add") . "</td>
		</tr>
	";

	WebTableClose("100%");
    echo "<p align='center'>" .	WebButton("Return", LOCALE_LANG_RETURN_TO_SUMMARY, WEBCONFIG_ICON_BACK) . "</p>";
	WebFormClose();

}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayEditJob()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayEditJob($job)
{
	global $bacula;

	$attributes = $bacula->GetJobAttributes($job);
	$lineindex = 0;
	$restore = false;
	# Getting job types requires specifying a job in order to 'hide'
	# "Restore" type.  This makes it impossible to add multiple restores.
	$job_types = $bacula->GetJobTypes($job);
	$level_types = $bacula->GetLevelOptions();
	$client_list = $bacula->GetClientList();
	$fileset_list = $bacula->GetFilesetList();
	$schedule_list = $bacula->GetScheduleList();
	$device_list = $bacula->GetSdList();
	$pool_list = $bacula->GetPoolList();
	$priority_list = $bacula->GetPriorityOptions();

	# Add "Run Manually" to schedule list
	$schedule_list[0] = WEB_LANG_RUN_MANUAL;
	# Sort lists
	asort($job_types);
	asort($level_types);
	asort($fileset_list);
	asort($client_list);
	asort($schedule_list);
	asort($device_list);
	asort($pool_list);
	# Defaults - in case parameter does not exist
	foreach ($level_types as $value => $display)
		$job_level_options .= "<option value='" . $value . "'>" . $display . "</option>\n";
	foreach ($schedule_list as $schedule) {
		if ($schedule == WEB_LANG_RUN_MANUAL)
			$job_schedule_options .= "<option value='' SELECTED>" . $schedule . "</option>\n";
		else
			$job_schedule_options .= "<option value='" . $schedule . "'>" . $schedule . "</option>\n";
	}
	foreach ($priority_list as $priority)
		$job_priority_options .= "<option value='" . $priority . "'>" . $priority . "</option>\n";
	$job_write_bsr_options =
		"<option value='yes'>" . LOCALE_LANG_YES . "</option>\n
		<option value='no' SELECTED>" . LOCALE_LANG_NO . "</option>";
	$job_send_bsr_options =
		"<option value='yes'>" . LOCALE_LANG_YES . "</option>\n
		<option value='no' SELECTED>" . LOCALE_LANG_NO . "</option>";
	foreach ($attributes as $line) {
		$pair = split("=", $line);
		if (eregi("^[[:space:]]*(Name)[[:space:]]*=[[:space:]]*(.*$)", trim($line), $match)) {
			$job_name = trim($match[2]);
		} else if (eregi("^[[:space:]]*(Type)[[:space:]]*=[[:space:]]*(.*$)", trim($line), $match)) {
			if (eregi("Restore", trim($match[2])))
				$restore = true;
			$job_type_options = "";
			foreach ($job_types as $value => $display) {
				if (eregi($value, trim($match[2])))
					$job_type_options .= "<option value='" . $value . "' SELECTED>" . $display . "</option>\n";
				else
					$job_type_options .= "<option value='" . $value . "'>" . $display . "</option>\n";
			}
		} else if (eregi("^[[:space:]]*(Level)[[:space:]]*=[[:space:]]*(.*$)", trim($line), $match)) {
			$job_level_options = "";
			foreach ($level_types as $value => $display) {
				if (eregi($value, trim($match[2])))
					$job_level_options .= "<option value='" . $value . "' SELECTED>" . $display . "</option>\n";
				else
					$job_level_options .= "<option value='" . $value . "'>" . $display . "</option>\n";
			}
		} else if (eregi("^[[:space:]]*(Client)[[:space:]]*=[[:space:]]*(.*$)", trim($line), $match)) {
			foreach ($client_list as $client) {
				if (eregi($client, trim($match[2])))
					$job_client_options .= "<option value='" . $client . "' SELECTED>" . $client . "</option>\n";
				else
					$job_client_options .= "<option value='" . $client . "'>" . $client . "</option>\n";
			}
		} else if (eregi("^[[:space:]]*(FileSet)[[:space:]]*=[[:space:]]*(.*$)", preg_replace("/ /", "", trim($line)), $match)) {
			foreach ($fileset_list as $fileset) {
				if (eregi($fileset, trim($match[2])))
					$job_fileset_options .= "<option value='" . $fileset . "' SELECTED>" . $fileset . "</option>\n";
				else
					$job_fileset_options .= "<option value='" . $fileset . "'>" . $fileset . "</option>\n";
			}
		} else if (eregi("^[[:space:]]*(Schedule)[[:space:]]*=[[:space:]]*(.*$)", trim($line), $match)) {
			$job_schedule_options = "";
			foreach ($schedule_list as $schedule) {
				if ($schedule == WEB_LANG_RUN_MANUAL)
					$job_schedule_options .= "<option value=''>" . $schedule . "</option>\n";
				else if (eregi($schedule, trim($match[2])))
					$job_schedule_options .= "<option value='" . $schedule . "' SELECTED>" . $schedule . "</option>\n";
				else
					$job_schedule_options .= "<option value='" . $schedule . "'>" . $schedule . "</option>\n";
			}
		} else if (eregi("^[[:space:]]*(Storage)[[:space:]]*=[[:space:]]*(.*$)", trim($line), $match)) {
			foreach ($device_list as $device) {
				if (eregi($device, trim($match[2])))
					$job_device_options .= "<option value='" . $device . "' SELECTED>" . $device . "</option>\n";
				else
					$job_device_options .= "<option value='" . $device . "'>" . $device . "</option>\n";
			}
		} else if (eregi("^[[:space:]]*(Pool)[[:space:]]*=[[:space:]]*(.*$)", trim($line), $match)) {
			foreach ($pool_list as $pool) {
				if (eregi($pool, trim($match[2])))
					$job_pool_options .= "<option value='" . $pool . "' SELECTED>" . $pool . "</option>\n";
				else
					$job_pool_options .= "<option value='" . $pool . "'>" . $pool . "</option>\n";
			}
		} else if (eregi("^[[:space:]]*(Priority)[[:space:]]*=[[:space:]]*(.*$)", trim($line), $match)) {
			$job_priority_options = "";
			foreach ($priority_list as $priority) {
				if (eregi($priority, trim($match[2])))
					$job_priority_options .= "<option value='" . $priority . "' SELECTED>" . $priority . "</option>\n";
				else
					$job_priority_options .= "<option value='" . $priority . "'>" . $priority . "</option>\n";
			}
		} else if (eregi("^[[:space:]]*(WriteBootstrap)[[:space:]]*=[[:space:]]*(.*$)", preg_replace("/ /", "", trim($line)), $match)) {
			$job_write_bsr_options =
				"<option value='yes' SELECTED>" . LOCALE_LANG_YES . "</option>\n
				<option value=''>" . LOCALE_LANG_NO . "</option>";
		} else if (eregi("^[[:space:]]*(RunAfterJob)[[:space:]]*=[[:space:]]*(.*$)", preg_replace("/ /", "", trim($line)), $match)) {
			if (eregi("pcnl_send_bsr", trim($match[2]))) {
				$job_send_bsr_options =
					"<option value='yes' SELECTED>" . LOCALE_LANG_YES . "</option>\n
					<option value='no'>" . LOCALE_LANG_NO . "</option>";
			}
		}
	}

	// HTML output
	//------------
    WebFormOpen($_SERVER['PHP_SELF'], "post");
    WebTableOpen(WEB_LANG_EDIT_JOB, "80%");
	echo "
	  <tr>
		<td class='mytablesubheader' align='right' nowrap>" . WEB_LANG_NAME . "</td>
		<td><input type='text' name='job_name' style='width: 200px' value='$job_name' READONLY /></td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' align='right' nowrap>" . WEB_LANG_JOB_TYPE . "</td>
		<td>
          <select name='job_type'>$job_type_options</select>
        </td>
	  </tr>
	";
	if (!$restore) {
	echo "
	  <tr>
		<td class='mytablesubheader' align='right' nowrap>" . WEB_LANG_LEVEL . "</td>
		<td>
          <select name='job_level'>$job_level_options</select>
        </td>
	  </tr>
	";
	}
	echo "
	  <tr>
		<td class='mytablesubheader' align='right' nowrap>" . WEB_LANG_CLIENT_NAME . "</td>
		<td>
          <select name='job_client'>$job_client_options</select>
        </td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' align='right' nowrap>" . WEB_LANG_FILESET_NAME . "</td>
		<td>
          <select name='job_fileset'>$job_fileset_options</select>
        </td>
	  </tr>
	";
	if (!$restore) {
	echo "
	  <tr>
		<td class='mytablesubheader' align='right' nowrap>" . WEB_LANG_SCHEDULE_NAME . "</td>
		<td>
          <select name='job_schedule'>$job_schedule_options</select>
        </td>
	  </tr>
	";
	}
	echo "
	  <tr>
		<td class='mytablesubheader' align='right' nowrap>" . WEB_LANG_DEVICE_NAME . "</td>
		<td>
          <select name='job_storage_device'>$job_device_options</select>
        </td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' align='right' nowrap>" . WEB_LANG_POOL_NAME . "</td>
		<td>
          <select name='job_pool'>$job_pool_options</select>
        </td>
	  </tr>
	";
	if (!$restore) {
	echo "
	  <tr>
		<td class='mytablesubheader' align='right' nowrap>" . WEB_LANG_PRIORITY . "</td>
		<td>
          <select name='job_priority'>$job_priority_options</select>
        </td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' align='right' nowrap>" . WEB_LANG_CREATE_BSR . "</td>
		<td>
          <select name='job_write_bsr'>$job_write_bsr_options</select>
        </td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' align='right' nowrap>" . WEB_LANG_SEND_BSR . "</td>
		<td>
          <select name='job_send_bsr'>$job_send_bsr_options</select>
        </td>
	  </tr>
	";
	}
	echo "
      <tr>
	    <td class='mytablesubheader'>&#160;</td>
		<td NOWRAP>
		" . WebButtonUpdate("DoEdit[$job]") . "
	    <input type='hidden' name='Action' value='job' />
	    <input type='hidden' name='name' value='$job' />
		</td>
	  </tr>
	";
    WebTableClose("80%");
    WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayDeleteJob()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayDeleteJob($job)
{
    WebFormOpen($_SERVER['PHP_SELF'], "post");
    WebTableOpen(LOCALE_LANG_CONFIRM, "450");
	list($index, $job_name) = split("\\|", $job);
	echo "<input type='hidden' name='Action' value='job' />";
    echo "
      <tr>
        <td align='center'>
          <p>" . WEBCONFIG_ICON_WARNING . " " . WEB_LANG_ARE_YOU_SURE . " " . $job_name . "?</p>
			". WebButtonDelete("DoDelete[$index]") . " " . WebButtonCancel("Cancel") . "
        </td>
      </tr>
    ";
    WebTableClose("450");
    WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayPool()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayPool()
{
	global $bacula;

	list($edit_index, $edit_pool) = split("\\|", $edit);
	$spacer = 135;

	# Fetch pool list
	$pool_list = $bacula->GetPoolList();
	asort($pool_list);

	$rowspan = (sizeof($pool_list) + 3);

	# No pools

	if (sizeof($pool_list) == 0)
		$rowspan = 5;

	// HTML output
	//------------
	WebFormOpen($_SERVER['PHP_SELF'], "post");
	echo "<input type='hidden' name='Action' value='pool' />";
	WebTableOpen(WEB_LANG_POOL_TITLE, "100%");
	echo "
	  <tr>
		<td class='mytableheader' colspan='3'>" . WEB_LANG_EXISTING_POOLS . "</td>
		<td width='40%' valign='top' class='help' rowspan='" . $rowspan . "'>
		  <p>" . WEB_LANG_BACULA_POOL_HELP . "</p>
		</td>
	  </tr>
	";
	foreach ($pool_list as $index => $pool) {
		echo "
	    <tr>
		  <td>$pool</td>
		";
		$restriction_code = $bacula->IsPoolRestricted($pool);
		if ($restriction_code == Bacula::RESTRICT_ALL) {
			echo "<td width='10%' NOWRAP>" . WEB_ICON_LOCKED . "</td><td width='10%' NOWRAP>" . WEB_ICON_LOCKED . "</td>";
		} else {
			if ($restriction_code == Bacula::RESTRICT_EDIT) {
				echo "<td width='10%' NOWRAP>" . WEB_ICON_LOCKED . "</td>";
			} else {
				echo "
				  <td width='10%' NOWRAP>
				  " . WebButtonEdit("Edit[$pool]") . "
				  </td>
				";
			}
			if ($restriction_code == Bacula::RESTRICT_DELETE) {
				echo "<td width='10%' NOWRAP>" . WEB_ICON_LOCKED . "</td>";
			} else {
				echo "
				  <td width='10%' NOWRAP>
				  " . WebButtonDelete("Delete[$index|$pool]") . "
				  </td>
				";
			}
		}
		echo "</tr>";
	}

	if (sizeof($pool_list) == 0)
		echo "<td colspan='3'>" . WEB_LANG_NO_POOLS . "</td>";

	echo "
		<tr>
		  <td class='mytableheader' colspan='3'>" . WEB_LANG_ADD_POOL . "</td>
		</tr>
		<tr>
		  <td>
	        <input type='text' name='newpool' value='$newpool' style='width: 180px' />
          </td>
		  <td>&#160;</td>
		  <td width='10%' NOWRAP>" . WebButtonAdd("Add") . "</td>
		</tr>
	";

	WebTableClose("100%");
    echo "<p align='center'>" .	WebButton("Return", LOCALE_LANG_RETURN_TO_SUMMARY, WEBCONFIG_ICON_BACK) . "</p>";
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayEditPool()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayEditPool($pool)
{
	global $bacula;

	$attributes = $bacula->GetPoolAttributes($pool);
	$lineindex = 0;
	$pool_types = $bacula->GetPoolTypeOptions();
	$time_units = $bacula->GetTimeUnits();
	$accept_any_volume_options =
		"\n<option value='yes'>" . LOCALE_LANG_YES . "</option>\n
		<option value='no' SELECTED>" . LOCALE_LANG_NO . "</option>\n";
	foreach ($attributes as $line) {
		$pair = split("=", $line);
		if (eregi(trim($pair[0]), "Name")) {
			$pool_name = trim($pair[1]);
		} else if (eregi(preg_replace("/ /", "", trim($pair[0])), "PoolType")) {
			foreach ($pool_types as $pool_value => $pool_display) {
				if (eregi($pool_value, trim($pair[1])))
					$pool_type_options .= "<option value='" . $pool_value . "' SELECTED>" .
						$pool_display . "</option>\n";
				else
					$pool_type_options .= "<option value='" . $pool_value . "'>" .
						$pool_display. "</option>\n";
			}
		} else if (eregi(preg_replace("/ /", "", trim($pair[0])), "LabelFormat")) {
			$pool_label_format = trim($pair[1]);
		} else if (eregi(trim($pair[0]), "Recycle")) {
			if (eregi(trim($pair[1]), "yes")) {
				$recycle_options =
					"<option value='yes' SELECTED>" . LOCALE_LANG_YES . "</option>\n
					<option value='no'>" . LOCALE_LANG_NO . "</option>";
			} else {
				$recycle_options =
					"<option value='yes'>" . LOCALE_LANG_YES . "</option>\n
					<option value='no' SELECTED>" . LOCALE_LANG_NO . "</option>";
			}
		} else if (eregi(preg_replace("/ /", "", trim($pair[0])), "AutoPrune")) {
			if (eregi(trim($pair[1]), "yes")) {
				$auto_prune_options =
					"<option value='yes' SELECTED>" . LOCALE_LANG_YES . "</option>\n
					<option value='no'>" . LOCALE_LANG_NO . "</option>";
			} else {
				$auto_prune_options =
					"<option value='yes'>" . LOCALE_LANG_YES . "</option>\n
					<option value='no' SELECTED>" . LOCALE_LANG_NO . "</option>";
			}
		} else if (eregi(preg_replace("/ /", "", trim($pair[0])), "VolumeRetention")) {
			$pool_volume_retention = trim($pair[1]);
			if (eregi("^(.*)[[:space:]](.*$)", $pair[1], $match))
				$pool_volume_retention = trim($match[1]);
			foreach ($time_units as $unit_value => $unit_display) {
				if (eregi($unit_value, trim($match[2])))
					$volume_retention_options .= "<option value='" . $unit_value . "' SELECTED>" .
						$unit_display . "</option>\n";
				else
					$volume_retention_options .= "<option value='" . $unit_value . "'>" .
						$unit_display. "</option>\n";
			}
		} else if (eregi(preg_replace("/ /", "", trim($pair[0])), "MaximumVolumes")) {
			$max_volumes = trim($pair[1]);
		} else if (eregi(preg_replace("/ /", "", trim($pair[0])), "MaximumVolumeJobs")) {
			$max_volume_jobs = trim($pair[1]);
		}
	}

	// HTML output
	//------------
    WebFormOpen($_SERVER['PHP_SELF'], "post");
    WebTableOpen(WEB_LANG_EDIT_POOL, "90%");
	echo "
	  <tr>
		<td class='mytablesubheader' align='right' nowrap>" . WEB_LANG_NAME . "</td>
		<td><input type='text' name='pool_name' value='$pool_name' style='width: 150px' READONLY /></td>
		<td width='30%' class='help' rowspan='11'>" . WEB_LANG_EDIT_CLIENT_HELP . "</td>
	  </tr>
	  <tr>
		<td align='right' nowrap class='mytablesubheader'>" . WEB_LANG_POOL_TYPE . "</td>
		<td><select name='pool_type'>$pool_type_options</select></td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' align='right' nowrap>" . WEB_LANG_POOL_RECYCLE . "</td>
		<td><select name='pool_recycle'>$recycle_options</select></td>
	  </tr>
	  <tr>
		<td align='right' nowrap class='mytablesubheader'>" . WEB_LANG_AUTO_PRUNE . "</td>
		<td><select name='pool_auto_prune'>$auto_prune_options</select></td>
	  </tr>
	  <tr>
		<td align='right' nowrap class='mytablesubheader'>" . WEB_LANG_POOL_VOLUME_RETENTION. "</td>
		<td>
          <input type='text' name='pool_volume_retention' style='width: 40px' value='$pool_volume_retention' />
          <select name='pool_volume_retention_unit'>$volume_retention_options</select>
        </td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' align='right' nowrap>" . WEB_LANG_POOL_MAX_VOLUMES . "</td>
		<td><input type='text' name='pool_max_volumes' value='$max_volumes' style='width: 40px'></td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' align='right' nowrap>" . WEB_LANG_POOL_MAX_VOLUME_JOBS . "</td>
		<td><input type='text' name='pool_max_volume_jobs' value='$max_volume_jobs' style='width: 40px'></td>
	  </tr>
	  <tr>
		<td align='right' nowrap class='mytablesubheader'>" . WEB_LANG_POOL_LABEL_FORMAT . "</td>
		<td><input type='text' name='pool_label_format' value='$pool_label_format' style='width: 100px' /></td>
	  </tr>
      <tr>
	    <td class='mytablesubheader'>&#160;</td>
		<td NOWRAP>
		" . WebButtonUpdate("DoEdit[$pool]") . "
	    <input type='hidden' name='Action' value='pool' />
	    <input type='hidden' name='name' value='$pool' />
		</td>
	  </tr>
	";
    WebTableClose("90%");
    WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayDeletePool()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayDeletePool($pool)
{
    WebFormOpen($_SERVER['PHP_SELF'], "post");
    WebTableOpen(LOCALE_LANG_CONFIRM, "450");
	list($index, $pool_name) = split("\\|", $pool);
	echo "<input type='hidden' name='Action' value='pool' />";
    echo "
      <tr>
        <td align='center'>
          <p>" . WEBCONFIG_ICON_WARNING . " " . WEB_LANG_ARE_YOU_SURE . " " . $pool_name . "?</p>
          <p>" . WEB_LANG_DEPENDENT_RESOURCES . "</p>
			". WebButtonDelete("DoDelete[$index]") . " " . WebButtonCancel("Cancel") . "
        </td>
      </tr>
    ";
    WebTableClose("450");
    WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplaySd()
//
///////////////////////////////////////////////////////////////////////////////

function DisplaySd()
{
	global $bacula;

	list($edit_index, $edit_sd) = split("\\|", $edit);
	$spacer = 135;

	# Fetch storage device list
	$sd_list = $bacula->GetSdList();
	asort($sd_list);

	$rowspan = (sizeof($sd_list) + 4);

	# No Storage Devices

	if (sizeof($sd_list) == 0)
		$rowspan = 5;
	else if (sizeof($sd_list) == 1)
		$spacer = 20;
	else if (sizeof($sd_list) >= 2)
		$spacer = 1;

	// HTML output
	//------------
	WebFormOpen($_SERVER['PHP_SELF'], "post");
	echo "<input type='hidden' name='Action' value='storage' />";
	WebTableOpen(WEB_LANG_SD_TITLE, "100%");
	echo "
	  <tr>
		<td class='mytableheader' colspan='3'>" . WEB_LANG_EXISTING_SD . "</td>
		<td width='40%' valign='top' class='help' rowspan='" . $rowspan . "'>
		  <p>" . WEB_LANG_BACULA_SD_HELP . "</p>
		</td>
	  </tr>
	";
	foreach ($sd_list as $index => $sd) {
		echo "
	    <tr>
		  <td>$sd</td>
		";
		$restriction_code = $bacula->IsSdRestricted($sd);
		if ($restriction_code == Bacula::RESTRICT_ALL) {
			echo "<td>" . WEB_ICON_LOCKED . "</td><td>" . WEB_ICON_LOCKED . "</td>";
		} else {
			if ($restriction_code == Bacula::RESTRICT_EDIT) {
				echo "<td>" . WEB_ICON_LOCKED . "</td>";
			} else {
				echo "
				  <td width='10%' NOWRAP>
				  " . WebButtonEdit("Edit[$sd]") . "
				  </td>
				";
			}
			if ($restriction_code == Bacula::RESTRICT_DELETE) {
				echo "<td>" . WEB_ICON_LOCKED . "</td>";
			} else {
				echo "
				  <td width='10%' NOWRAP>
				  " . WebButtonDelete("Delete[$index|$sd]") . "
				  </td>
				";
			}
		}
		echo "</tr>";
	}

	if (sizeof($sd_list) == 0)
		echo "<td colspan='3'>" . WEB_LANG_NO_SD . "</td>";

	echo "
		<tr>
		  <td class='mytableheader' colspan='3'>" . WEB_LANG_ADD_SD . "</td>
		</tr>
		<tr>
		  <td>
	        <input type='text' name='newsd' value='$newsd' style='width: 180px' />
          </td>
		  <td>&#160;</td>
		  <td width='10%' NOWRAP>" . WebButtonAdd("Add") . "</td>
		</tr>
		<tr>
		  <td colspan='3'><img src='../images/transparent.gif' width='1' height='$spacer' alt='' /></td>
		</tr>
	";

	WebTableClose("100%");
    echo "<p align='center'>" .	WebButton("Return", LOCALE_LANG_RETURN_TO_SUMMARY, WEBCONFIG_ICON_BACK) . "</p>";
	WebFormClose();

}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayEditSd()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayEditSd($storage)
{
	global $bacula;

	$attributes = $bacula->GetSdAttributes($storage);
	$storage_units = $bacula->GetStorageUnits();
	$media_type = $bacula->GetDeviceMediaTypeOptions();
	$lineindex = 0;
	$rowspan = 14;
	# Defaults - in case parameter does not exist
	$label_media_options =
		"<option value='yes'>" . LOCALE_LANG_YES . "</option>\n
		<option value='no' SELECTED>" . LOCALE_LANG_NO . "</option>";
	$random_access_options =
		"<option value='yes'>" . LOCALE_LANG_YES . "</option>\n
		<option value='no' SELECTED>" . LOCALE_LANG_NO . "</option>";
	$automatic_mount_options =
		"<option value='yes'>" . LOCALE_LANG_YES . "</option>\n
		<option value='no' SELECTED>" . LOCALE_LANG_NO . "</option>";
	$removable_media_options =
		"<option value='yes'>" . LOCALE_LANG_YES . "</option>\n
		<option value='no' SELECTED>" . LOCALE_LANG_NO . "</option>";
	$always_open_options =
		"<option value='yes'>" . LOCALE_LANG_YES . "</option>\n
		<option value='no' SELECTED>" . LOCALE_LANG_NO . "</option>";
	$use_volume_once_options =
        "<option value='yes'>" . LOCALE_LANG_YES . "</option>\n
        <option value='no' SELECTED>" . LOCALE_LANG_NO . "</option>";
	$max_vol_size = 0;
	foreach ($media_type as $type)
		$media_type_options .= "<option value='" . $type . "'>\n" .	$type . "</option>";
	foreach ($storage_units as $unit_value => $unit_display)
		$max_vol_size_options .= "<option value='" . $unit_value . "'>\n" .	$unit_display . "</option>";
	# End defaults

	$show_fields = true;
    # Start with default mount point

    $mount_point = "<input type='text' name='sd_mount' value='" . $bacula->GetSdArchiveDevice($storage) . "' style='width: 240px' />";
	foreach ($attributes as $line) {
		$pair = split("=", $line);
		if (eregi(trim($pair[0]), "Name")) {
			$sd_name = trim($pair[1]);
		} else if (eregi(trim($pair[0]), "Address")) {
			$sd_address = trim($pair[1]);
		} else if (eregi(preg_replace("/ /", "", trim($pair[0])), "SDport")) {
			$sd_port = trim($pair[1]);
		} else if (eregi(trim($pair[0]), "Password")) {
			$sd_password = trim($pair[1]);
		} else if (eregi(preg_replace("/ /", "", trim($pair[0])), "ArchiveDevice")) {
			$mount = trim($pair[1]);
		} else if (eregi(preg_replace("/ /", "", trim($pair[0])), "MediaType")) {
			$media_type_options = "";
			$mymedia = trim($pair[1]);
			if (isset($_POST['sd_media_type']))
				$mymedia = trim($_POST['sd_media_type']);
			else
				$_POST['sd_media_type'] = $mymedia;

			foreach ($media_type as $type => $description) {
				if (isset($_POST['sd_media_type']) && eregi("^" . $type, $mymedia)) {
                    $media_type_options .= "<option value='" . $type . "' SELECTED>\n" . $description . "</option>";
                    if (eregi("^" . Bacula::MEDIA_DVD, $mymedia) ||
						eregi("^" . Bacula::MEDIA_IOMEGA, $mymedia) ||
						eregi("^" . Bacula::MEDIA_USB, $mymedia)) {
                        $show_fields = false;
                        $mount_point_options = "";
						foreach ($bacula->GetDevices() as $dev => $info) {
							$name = preg_replace("/\s+|\\.|\\,/", "_", $info['vendor'] . $info['model']);
                            if (isset($_POST['sd_mount']) && $_POST['sd_mount'] == $dev) {
                                $mount_point_options .= "<option value='" . $dev . "' SELECTED>" . $name . "</option>\n";
                            } else if ($storage == str_replace(" ", "", $name)) {
                                $mount_point_options .= "<option value='" . $dev . "' SELECTED>" . $name . "</option>\n";
                            } else if (!isset($_POST['sd_mount']) && $dev == $bacula->GetSdArchiveDevice($storage)) {
                                $mount_point_options .= "<option value='" . $dev . "' SELECTED>" . $name . "</option>\n";
                            } else {
                                $mount_point_options .= "<option value='" . $dev . "'>" . $name . "</option>\n";
                            }
                        }
                        # Tack on some additional hidden fields
                    	if (eregi("^" . Bacula::MEDIA_DVD, $type)) {
                        	$mount_point = "<select name='sd_mount'>\n$mount_point_options</select>" .
                                           "<input type='hidden' name='sd_label_media' value='yes' />" .
                                           "<input type='hidden' name='sd_random_access' value='yes' />" .
                                           "<input type='hidden' name='sd_automatic_mount' value='yes' />" .
                                           "<input type='hidden' name='sd_removable_media' value='yes' />" .
                                           "<input type='hidden' name='sd_always_open' value='yes' />" .
                                           "<input type='hidden' name='sd_use_volume_once' value='yes' />";
						} else if (eregi("^" . Bacula::MEDIA_IOMEGA, $type) || eregi("^" . Bacula::MEDIA_USB, $type)) {
                        	$mount_point = "<select name='sd_mount'>\n$mount_point_options</select>" .
                                           "<input type='hidden' name='sd_label_media' value='yes' />" .
                                           "<input type='hidden' name='sd_random_access' value='yes' />" .
                                           "<input type='hidden' name='sd_automatic_mount' value='yes' />" .
                                           "<input type='hidden' name='sd_removable_media' value='yes' />" .
                                           "<input type='hidden' name='sd_always_open' value='yes' />" .
                                           "<input type='hidden' name='sd_use_volume_once' value='no' />";
						}
                    }
				} else if (!isset($_POST['sd_media_type']) && eregi("^" . $type, $mymedia)) {
                    $media_type_options .= "<option value='" . $type . "' SELECTED>\n" . $description . "</option>";
                } else {
                    $media_type_options .= "<option value='" . $type . "'>\n" . $description . "</option>";
                }
			}
		} else if (eregi(preg_replace("/ /", "", trim($pair[0])), "LabelMedia")) {
			if (eregi(trim($pair[1]), "yes")) {
				$label_media_options =
					"<option value='yes' SELECTED>" . LOCALE_LANG_YES . "</option>\n
					<option value='no'>" . LOCALE_LANG_NO . "</option>";
			} else {
				$label_media_options =
					"<option value='yes'>" . LOCALE_LANG_YES . "</option>\n
					<option value='no' SELECTED>" . LOCALE_LANG_NO . "</option>";
			}
		} else if (eregi(preg_replace("/ /", "", trim($pair[0])), "RandomAccess")) {
			if (eregi(trim($pair[1]), "yes")) {
				$random_access_options =
					"<option value='yes' SELECTED>" . LOCALE_LANG_YES . "</option>\n
					<option value='no'>" . LOCALE_LANG_NO . "</option>";
			} else {
				$random_access_options =
					"<option value='yes'>" . LOCALE_LANG_YES . "</option>\n
					<option value='no' SELECTED>" . LOCALE_LANG_NO . "</option>";
			}
		} else if (eregi(preg_replace("/ /", "", trim($pair[0])), "AutomaticMount")) {
			if (eregi(trim($pair[1]), "yes")) {
				$automatic_mount_options =
					"<option value='yes' SELECTED>" . LOCALE_LANG_YES . "</option>\n
					<option value='no'>" . LOCALE_LANG_NO . "</option>";
			} else {
				$automatic_mount_options =
					"<option value='yes'>" . LOCALE_LANG_YES . "</option>\n
					<option value='no' SELECTED>" . LOCALE_LANG_NO . "</option>";
			}
		} else if (eregi(preg_replace("/ /", "", trim($pair[0])), "RemovableMedia")) {
			if (eregi(trim($pair[1]), "yes")) {
				$removable_media_options =
					"<option value='yes' SELECTED>" . LOCALE_LANG_YES . "</option>\n
					<option value='no'>" . LOCALE_LANG_NO . "</option>";
			} else {
				$removable_media_options =
					"<option value='yes'>" . LOCALE_LANG_YES . "</option>\n
					<option value='no' SELECTED>" . LOCALE_LANG_NO . "</option>";
			}
		} else if (eregi(preg_replace("/ /", "", trim($pair[0])), "AlwaysOpen")) {
			if (eregi(trim($pair[1]), "yes")) {
				$always_open_options =
					"<option value='yes' SELECTED>" . LOCALE_LANG_YES . "</option>\n
					<option value='no'>" . LOCALE_LANG_NO . "</option>";
			} else {
				$always_open_options =
					"<option value='yes'>" . LOCALE_LANG_YES . "</option>\n
					<option value='no' SELECTED>" . LOCALE_LANG_NO . "</option>";
			}
		} else if (eregi(preg_replace("/ /", "", trim($pair[0])), "MaximumVolumeSize")) {
			$max_vol_size_options = "";
			if (eregi("^([[:digit:]]*.*[[:digit:]]+)([[:alpha:]]+$)", trim($pair[1]), $match))
				$max_vol_size = trim($match[1]);
			foreach ($storage_units as $unit_value => $unit_display) {
				if (eregi($unit_value, trim($match[2])))
					$max_vol_size_options .= "<option value='" . $unit_value . "' SELECTED>\n" .
						$unit_display . "</option>";
				else
					$max_vol_size_options .= "<option value='" . $unit_value . "'>\n" .
						$unit_display . "</option>";
			}
		} else if (eregi(preg_replace("/ /", "", trim($pair[0])), "UseVolumeOnce")) {
            if (eregi(trim($pair[1]), "yes")) {
                $use_volume_once_options =
                    "<option value='yes' SELECTED>" . LOCALE_LANG_YES . "</option>\n
                    <option value='no'>" . LOCALE_LANG_NO . "</option>";
            } else {
                $use_volume_once_options =
                    "<option value='yes'>" . LOCALE_LANG_YES . "</option>\n
                    <option value='no' SELECTED>" . LOCALE_LANG_NO . "</option>";
            }
		}
	}

	if (eregi($sd_address, "localhost"))
		WebDialogWarning(WEB_LANG_ERRMSG_LOCALHOST);

	if (eregi("^" . Bacula::MEDIA_SMB, $mymedia))
		$rowspan = 19;

	// HTML output
	//------------
    WebFormOpen($_SERVER['PHP_SELF'], "post");
    WebTableOpen(WEB_LANG_EDIT_SD, "90%");
	echo "
	  <tr>
		<td class='mytablesubheader' align='right' nowrap>" . WEB_LANG_NAME . "</td>
		<td><input type='text' name='sd_name' value='$sd_name' style='width: 180px' READONLY /></td>
		<td width='30%' class='help' rowspan='$rowspan'>" . WEB_LANG_EDIT_CLIENT_HELP . "</td>
	  </tr>
	  <tr>
		<td align='right' nowrap class='mytablesubheader'>" . WEB_LANG_MACHINE_ADDRESS . "</td>
		<td><input type='text' name='sd_address' value='$sd_address' style='width: 180px' /></td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' align='right' nowrap>" . WEB_LANG_MACHINE_PORT . "</td>
		<td><input type='text' name='sd_port' value='$sd_port' style='width: 40px' /></td>
	  </tr>
	  <tr>
		<td align='right' nowrap class='mytablesubheader'>" . WEB_LANG_MACHINE_PASSWORD . "</td>
		<td><input type='text' name='sd_password' value='$sd_password' style='width: 240px' /></td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' align='right' nowrap>" . WEB_LANG_DEVICE_OR_MOUNT . "</td>
		<td>$mount_point</td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' align='right' nowrap>" . WEB_LANG_MEDIA_TYPE . "</td>
        <td>
          <select name='sd_media_type' onChange='form.submit()'>$media_type_options</select>
        </td>
	  </tr>
	";
	if (eregi("^" . Bacula::MEDIA_SMB, $mymedia)) {
		$share = $bacula->DecodeShareInfo($sd_name);
		echo "
		  <tr>
        	<td align='right' nowrap>" . WEB_LANG_MACHINE_ADDRESS . "</td>
        	<td><input type='text' name='address' value='" . $share['address'] . "' style='width: 180px'></td>
          </tr>
          <tr>
            <td align='right' nowrap>" . WEB_LANG_USERNAME . "</td>
            <td><input type='text' name='username' value='" . $share['username'] . "' style='width: 180px'></td>
          </tr>
          <tr>
            <td align='right' nowrap>" . WEB_LANG_MACHINE_PASSWORD . "</td>
            <td><input type='password' name='password' value='" . $share['password'] . "' style='width: 180px'></td>
          </tr>
          <tr>
            <td align='right' nowrap>" . WEB_LANG_SHARE_NAME . "</td>
            <td><input type='text' name='sharedir' value='" . $share['sharedir'] . "' style='width: 180px'></td>
          </tr>
		";
	}

    if ($show_fields)
    echo "
	  <tr>
		<td class='mytablesubheader' align='right' nowrap>" . WEB_LANG_LABEL_MEDIA . "</td>
        <td><select name='sd_label_media'>$label_media_options</select></td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' align='right' nowrap>" . WEB_LANG_RANDOM_ACCESS . "</td>
		<td>
          <select name='sd_random_access'>$random_access_options</select>
        </td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' align='right' nowrap>" . WEB_LANG_AUTOMATIC_MOUNT . "</td>
		<td>
          <select name='sd_automatic_mount'>$automatic_mount_options</select>
        </td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' align='right' nowrap>" . WEB_LANG_REMOVABLE_MEDIA . "</td>
		<td>
          <select name='sd_removable_media'>$removable_media_options</select>
        </td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' align='right' nowrap>" . WEB_LANG_ALWAYS_OPEN . "</td>
		<td>
          <select name='sd_always_open'>$always_open_options</select>
        </td>
	  </tr>
      <tr>
        <td class='mytablesubheader' align='right' nowrap>" . WEB_LANG_USE_VOLUME_ONCE . "</td>
        <td>
          <select name='sd_use_volume_once'>$use_volume_once_options</select>
        </td>
      </tr>
	";
    echo "
	  <tr>
		<td class='mytablesubheader' align='right' nowrap>" . WEB_LANG_MAX_VOL_SIZE . "</td>
		<td>
          <input type='text' name='sd_max_volume_size' style='width: 40px' value='$max_vol_size' />
          <select name='sd_max_volume_size_unit'>$max_vol_size_options</select>&#160;
          <span class='small'>" . WEB_LANG_NO_MAX_VOL_SIZE . "</span>
        </td>
	  </tr>
      <tr>
	    <td class='mytablesubheader'>&#160;</td>
		<td NOWRAP>
		" . WebButtonUpdate("DoEdit[$storage]") . "
	    <input type='hidden' name='Action' value='storage' />
	    <input type='hidden' name='name' value='$storage' />
		</td>
	  </tr>
	";
    WebTableClose("90%");
    WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayDeleteSd()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayDeleteSd($sd)
{
    WebFormOpen($_SERVER['PHP_SELF'], "post");
    WebTableOpen(LOCALE_LANG_CONFIRM, "450");
	list($index, $sd_name) = split("\\|", $sd);
	echo "<input type='hidden' name='Action' value='storage' />";
    echo "
      <tr>
        <td align='center'>
          <p>" . WEBCONFIG_ICON_WARNING . " " . WEB_LANG_ARE_YOU_SURE . " " . $sd_name . "?</p>
          <p>" . WEB_LANG_DEPENDENT_RESOURCES . "</p>
			". WebButtonDelete("DoDelete[$index]") . " " . WebButtonCancel("Cancel") . "
        </td>
      </tr>
    ";
    WebTableClose("450");
    WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplaySchedule()
//
///////////////////////////////////////////////////////////////////////////////

function DisplaySchedule()
{
	global $bacula;

	list($edit_index, $edit_schedule) = split("\\|", $edit);
	$spacer = 135;

	# Fetch schedule list
	$schedule_list = $bacula->GetScheduleList();

	$rowspan = (sizeof($schedule_list) + 4);

	# No schedules

	if (sizeof($schedule_list) == 0)
		$rowspan = 5;
	else if (sizeof($schedule_list) == 1)
		$spacer = 20;
	else if (sizeof($schedule_list) >= 2)
		$spacer = 1;

	// HTML output
	//------------
	WebFormOpen($_SERVER['PHP_SELF'], "post");
	echo "<input type='hidden' name='Action' value='schedule' />";
	WebTableOpen(WEB_LANG_SCHEDULE_TITLE, "100%");
	echo "
	  <tr>
		<td class='mytableheader' colspan='3'>" . WEB_LANG_EXISTING_SCHEDULES . "</td>
		<td width='40%' valign='top' class='help' rowspan='" . $rowspan . "'>
		  <p>" . WEB_LANG_BACULA_SCHEDULE_HELP . "</p>
		</td>
	  </tr>
	";
	foreach ($schedule_list as $index => $schedule) {
		echo "
	    <tr>
		  <td>$schedule</td>
		";
		$restriction_code = $bacula->IsScheduleRestricted($schedule);
		if ($restriction_code == Bacula::RESTRICT_ALL) {
			echo "<td>" . WEB_ICON_LOCKED . "</td><td>" . WEB_ICON_LOCKED . "</td>";
		} else {
			if ($restriction_code == Bacula::RESTRICT_EDIT) {
				echo "<td>" . WEB_ICON_LOCKED . "</td>";
			} else {
				echo "
				  <td width='10%' NOWRAP>
				  " . WebButtonEdit("Edit[$schedule]") . "
				  </td>
				";
			}
			if ($restriction_code == Bacula::RESTRICT_DELETE) {
				echo "<td>" . WEB_ICON_LOCKED . "</td>";
			} else {
				echo "
				  <td width='10%' NOWRAP>
				  " . WebButtonDelete("Delete[$index|$schedule]") . "
				  </td>
				";
			}
		}
		echo "</tr>";
	}

	if (sizeof($schedule_list) == 0)
		echo "<td colspan='3'>" . WEB_LANG_NO_SCHEDULES . "</td>";

	echo "
		<tr>
		  <td class='mytableheader' colspan='3'>" . WEB_LANG_ADD_SCHEDULE . "</td>
		</tr>
		<tr>
		  <td>
	        <input type='text' name='newschedule' value='$newschedule' style='width: 180px' />
          </td>
		  <td>&#160;</td>
		  <td width='10%' NOWRAP>
		    " . WebButtonAdd("Add") . "
		  </td>
		</tr>
		<tr>
		  <td colspan='3'><img src='../images/transparent.gif' width='1' height='$spacer' alt='' /></td>
		</tr>
	";

	WebTableClose("100%");
    echo "<p align='center'>" .	WebButton("Return", LOCALE_LANG_RETURN_TO_SUMMARY, WEBCONFIG_ICON_BACK) . "</p>";
	WebFormClose();

}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayEditSchedule()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayEditSchedule($schedule)
{
	global $bacula;

	$attributes = $bacula->GetScheduleAttributes($schedule);
	$regex_level = "^[[:space:]]*Run[[:space:]]*=[[:space:]]*(Full|Differential|Incremental)(.*)at(.*$)";
	$lineindex = 0;
	$level_options = $bacula->GetLevelOptions();
	$date_options = $bacula->GetDateOptions();
	$hour_options = $bacula->GetHourOptions();
	$minute_options = $bacula->GetMinuteOptions();
	foreach ($attributes as $line) {
		if (eregi("^[[:space:]]*(Name)[[:space:]]*=[[:space:]]*(.*$)", $line, $match)) {
			$schedule_name = $match[2];
		} else if (eregi($regex_level, $line, $match)) {
			# Levels
			foreach ($level_options as $key => $value) {
				if (trim(strtolower($match[1])) == trim(strtolower($key)))
					$level_options_html .= "<option value='" . $key . "' SELECTED>" . $value . "</option>\n";
				else
					$level_options_html .= "<option value='" . $key . "'>" . $value . "</option>\n";
			}
			# Date
			foreach ($date_options as $key => $value) {
				if (trim(strtolower($match[2])) == trim(strtolower($key)))
					$date_options_html .= "<option value='" . $key . "' SELECTED>" . $value . "</option>\n";
				else
					$date_options_html .= "<option value='" . $key . "'>" . $value . "</option>\n";
			}
			$time = split("\:", $match[3]);
			$hour = $time[0];
			$minute = $time[1];
			# Time (hour)
			foreach ($hour_options as $key => $value) {
				if ($hour == $key) {
					$hour_options_html .= "<option value='" . $key . "' SELECTED>" . $value . "</option>\n";
				} else {
					$hour_options_html .= "<option value='" . $key . "'>" . $value . "</option>\n";
				}
			}
			# Time (minute)
			foreach ($minute_options as $key => $value) {
				if ($minute == $key)
					$minute_options_html .= "<option value='" . $key . "' SELECTED>" . $value . "</option>\n";
				else
					$minute_options_html .= "<option value='" . $key . "'>" . $value . "</option>\n";
			}
			if ($lineindex > 0)
				$data .= "<tr>";
			$data .= "
				<td>
                  <select name='DoEdit[level|$lineindex]'>\n" . $level_options_html . "\n</select>
                </td>
                <td>
                  <select name='DoEdit[date|$lineindex]'>\n" . $date_options_html . "\n</select>
                </td>
                <td NOWRAP>
                  <select name='DoEdit[hour|$lineindex]'>\n" . $hour_options_html . "\n</select>
                  :
                  <select name='DoEdit[minute|$lineindex]'>\n" . $minute_options_html . "\n</select>
                </td>
                <td align='center'>";
			$data .= (($lineindex == 0) ? "" : "<input type='checkbox' name='DoEdit[delete|$lineindex]' />");
			$data .= "
                </td>
              </tr>
			";

			$lineindex++;
			$level_options_html = "";
			$date_options_html = "";
			$hour_options_html = "";
			$minute_options_html = "";
		} else {
			$lineindex++;
			$data .= "<tr>\n<td>\n<input type='input' name='' value='$line' />\n</td>\n</tr>\n";
		}
	}

	# Add run level options
	# ---------------------
	# Levels
	$level_options_html .= "<option value='-1' SELECTED>" . WEB_LANG_SELECT . "</option>\n";
	foreach ($level_options as $key => $value)
		$level_options_html .= "<option value='" . $key . "'>" . $value . "</option>\n";
	# Date
	foreach ($date_options as $key => $value)
		$date_options_html .= "<option value='" . $key . "'>" . $value . "</option>\n";
	$time = split("\:", $match[3]);
	$hour = $time[0];
	$minute = $time[1];
	# Time (hour)
	foreach ($hour_options as $key => $value)
		$hour_options_html .= "<option value='" . $key . "'>" . $value . "</option>\n";
	# Time (minute)
	foreach ($minute_options as $key => $value)
		$minute_options_html .= "<option value='" . $key . "'>" . $value . "</option>\n";

	// HTML output
	//------------
    WebFormOpen($_SERVER['PHP_SELF'], "post");
    WebTableOpen(WEB_LANG_EDIT_SCHEDULE . " " . $schedule_name, "100%");
	echo "
	  <tr>
 	    <td class='mytableheader'>" . WEB_LANG_LEVEL  . "</td>
 	    <td class='mytableheader'>" . WEB_LANG_DATE_RUN  . "</td>
 	    <td class='mytableheader'>" . WEB_LANG_TIME_RUN  . "</td>
 	    <td class='mytableheader'>" . WEB_LANG_DELETE  . "</td>
	  </tr>
      " . $data. "
	  <tr>
		<td>
          <select name='DoEdit[level|$lineindex]'>\n" . $level_options_html . "\n</select>
        </td>
        <td>
          <select name='DoEdit[date|$lineindex]'>\n" . $date_options_html . "\n</select>
        </td>
        <td>
          <select name='DoEdit[hour|$lineindex]'>\n" . $hour_options_html . "\n</select>
          :
          <select name='DoEdit[minute|$lineindex]'>\n" . $minute_options_html . "\n</select>
        </td>
	  </tr>
      <tr>
		<td colspan='2'>&#160;</td>
		<td colspan='2'>
		" . WebButtonUpdate("DoEdit[$schedule]") . "
		" . WebButtonCancel("Cancel") . "
	    <input type='hidden' name='Action' value='schedule' />
	    <input type='hidden' name='name' value='$schedule' />
		</td>
	  </tr>
	";
    WebTableClose("100%");
    WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayDeleteSchedule()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayDeleteSchedule($schedule)
{
    WebFormOpen($_SERVER['PHP_SELF'], "post");
    WebTableOpen(LOCALE_LANG_CONFIRM, "450");
	list($index, $schedule_name) = split("\\|", $schedule);
	echo "<input type='hidden' name='Action' value='schedule' />";
    echo "
      <tr>
        <td align='center'>
          <p>" . WEBCONFIG_ICON_WARNING . " " . WEB_LANG_ARE_YOU_SURE . " " . $schedule_name . "?</p>
			". WebButtonDelete("DoDelete[$index]") . " " . WebButtonCancel("Cancel") . "
        </td>
      </tr>
    ";
    WebTableClose("450");
    WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayFileset()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayFileset()
{
	global $bacula;

	list($edit_index, $edit_fileset) = split("\\|", $edit);
	$spacer = 50;

	# Fetch fileset list
	$fileset_list = $bacula->GetFilesetList();
	asort($fileset_list);

	$rowspan = (sizeof($fileset_list) + 4);

	# No schedules
	if (sizeof($fileset_list) == 0)
		$rowspan = 5;
	else if (sizeof($fileset_list) == 1)
		$spacer = 20;
	else if (sizeof($fileset_list) == 2)
		$spacer = 10;
	else if (sizeof($fileset_list) >= 3)
		$spacer = 1;

	// HTML output
	//------------
	WebFormOpen($_SERVER['PHP_SELF'], "post");
	echo "<input type='hidden' name='Action' value='fileset' />";
	WebTableOpen(WEB_LANG_FILESET_TITLE, "100%");
	echo "
	  <tr>
		<td class='mytableheader'>" . WEB_LANG_EXISTING_FILESETS . "</td>
		<td class='mytableheader' align='center'>" . WEB_LANG_DATABASE . "</td>
		<td colspan='2' class='mytableheader'>&#160;</td>
		<td width='35%' valign='top' class='help' rowspan='" . $rowspan . "'>
		  <p>" . WEB_LANG_BACULA_FILESET_HELP . "</p>
		</td>
	  </tr>
	";
	foreach ($fileset_list as $index => $fileset) {
		if ($bacula->IsFilesetDatabase($fileset))
			$db_flag = WEBCONFIG_ICON_OK;
		else
			$db_flag = "&#160;";

		echo "
	    <tr>
		  <td>$fileset</td>
		  <td align='center'>$db_flag</td>
		";
		$restriction_code = $bacula->IsFilesetRestricted($fileset);
		if ($restriction_code == Bacula::RESTRICT_ALL) {
			echo "<td>" . WEB_ICON_LOCKED . "</td><td>" . WEB_ICON_LOCKED . "</td>";
		} else {
			if ($restriction_code == Bacula::RESTRICT_EDIT) {
				echo "<td>" . WEB_ICON_LOCKED . "</td>";
			} else {
				echo "
				  <td width='10%' NOWRAP>
				  " . WebButtonEdit("Edit[$fileset]") . "
				  </td>
				";
			}
			if ($restriction_code == Bacula::RESTRICT_DELETE) {
				echo "<td>" . WEB_ICON_LOCKED . "</td>";
			} else {
				echo "
				  <td width='10%' NOWRAP>
				  " . WebButtonDelete("Delete[$index|$fileset]") . "
				  </td>
				";
			}
		}
		echo "</tr>";
	}

	if (sizeof($fileset_list) == 0)
		echo "<tr><td colspan='4'>" . WEB_LANG_NO_FILESETS . "</td></tr>";

	echo "
		<tr>
		  <td class='mytableheader' colspan='4'>" . WEB_LANG_ADD_FILESET . "</td>
		</tr>
		<tr>
		  <td>
	        <input type='text' name='newfileset' value='$newfileset' style='width: 180px' />
          </td>
		  <td align='center'><input type='checkbox' name='database' /></td>
		  <td width='10%' NOWRAP>
		    " . WebButtonAdd("Add") . "
		  </td>
		</tr>
		<tr>
		  <td colspan='4'><img src='../images/transparent.gif' width='1' height='$spacer' alt='' /></td>
		</tr>
	";

	WebTableClose("100%");
    echo "<p align='center'>" .	WebButton("Return", LOCALE_LANG_RETURN_TO_SUMMARY, WEBCONFIG_ICON_BACK) . "</p>";
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayEditFileset()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayEditFileset($fileset, $show)
{
	global $bacula;

	$include = $bacula->GetFilesetInclude($fileset);
	$exclude = $bacula->GetFilesetExclude($fileset);
	$signatures = $bacula->GetSignatures();
	$is_database = $bacula->IsFilesetDatabase($fileset);
	$inc_index = 0;
	foreach ($include as $include_block) {
		$file_index = 0;
		foreach ($include_block as $key => $value) {
			if ($file_index == 0) {
				$fileset_options = $bacula->GetFilesetOptions($fileset, $inc_index);
				# Reset options
				$fileset_sig_options = "";
				$wild = "";
				$wildfile = "";
				$wilddir = "";
				$regex = "";
				$regexfile = "";
				$regexdir = "";

				# Compression
				if ($fileset_options["compression"]) {
					$fileset_compression_options =
						"<option value='yes' SELECTED>" . LOCALE_LANG_YES . "</option>\n
						<option value='no'>" . LOCALE_LANG_NO . "</option>\n";
				} else {
					$fileset_compression_options =
						"<option value='yes'>" . LOCALE_LANG_YES . "</option>\n
						<option value='no' SELECTED>" . LOCALE_LANG_NO . "</option>\n";
				}

				# Signature
				foreach ($signatures as $key => $display) {
					if (eregi($key, $fileset_options["signature"]))
						$fileset_sig_options .= "<option value='" . $key . "' SELECTED>" . $display . "</option>\n";
					else
						$fileset_sig_options .= "<option value='" . $key . "'>" . $display . "</option>\n";
				}

				# Ignore case
				if (!$fileset_options["case"]) {
					$fileset_case_options =
						"<option value='yes'>" . WEB_LANG_IGNORE . "</option>\n
						<option value='no' SELECTED>" . WEB_LANG_RESPECT . "</option>\n";
				} else {
					$fileset_case_options =
						"<option value='yes' SELECTED>" . WEB_LANG_IGNORE . "</option>\n
						<option value='no'>" . WEB_LANG_RESPECT . "</option>\n";
				}

				# Option rules (include/exclude)
				if ($fileset_options["exclude"]) {
					$fileset_exclude_options =
						"<option value='no'>" . WEB_LANG_FILESET_INCLUDE . "</option>\n
						<option value='yes' SELECTED>" . WEB_LANG_FILESET_EXCLUDE . "</option>\n";
				} else {
					$fileset_exclude_options =
						"<option value='no' SELECTED>" . WEB_LANG_FILESET_INCLUDE . "</option>\n
						<option value='yes'>" . WEB_LANG_FILESET_EXCLUDE . "</option>\n";
				}
				# Wild cards
				$index = 0;
				$maxindex = 0;
				foreach ($fileset_options["wild"] as $line) {
					$wild .= "
				    	<tr>
					      <td colspan='2' align='center'>" . WEB_ICON_WILD . "</td>
					      <td>
					        <input type='text' name='fileset_options[$inc_index][wild][$index]' value='$line' style='width: 420px' />
					      </td>
					      <td align='center'><input type='checkbox' name='Delete[$inc_index|wild|$index]' /></td>
				        </tr>
					";
					$index++;
					if ($index > $maxindex)
						$maxindex = $index;
				}
				# Wild Files
				$index = 0;
				foreach ($fileset_options["wildfile"] as $line) {
					$wildfile .= "
				    	<tr>
					      <td colspan='2' align='center'>" . WEB_ICON_WILD_FILE . "</td>
					      <td>
					        <input type='text' name='fileset_options[$inc_index][wildfile][$index]' value='$line' style='width: 420px' />
					      </td>
					      <td align='center'><input type='checkbox' name='Delete[$inc_index|wildfile|$index]' /></td>
				        </tr>
					";
					$index++;
					if ($index > $maxindex)
						$maxindex = $index;
				}
				# Wild Folders
				$index = 0;
				foreach ($fileset_options["wilddir"] as $line) {
					$wilddir .= "
				    	<tr>
					      <td colspan='2' align='center'>" . WEB_ICON_WILD_DIR . "</td>
					      <td>
					        <input type='text' name='fileset_options[$inc_index][wilddir][$index]' value='$line' style='width: 420px' />
					      </td>
					      <td align='center'><input type='checkbox' name='Delete[$inc_index|wilddir|$index]' /></td>
				        </tr>
					";
					$index++;
					if ($index > $maxindex)
						$maxindex = $index;
				}
				# Regular expressions
				$index = 0;
				foreach ($fileset_options["regex"] as $line) {
					$regex .= "
				    	<tr>
					      <td colspan='2' align='center'>" . WEB_ICON_REGEX . "</td>
					      <td>
					        <input type='text' name='fileset_options[$inc_index][regex][$index]' value='$line' style='width: 420px' />
					      </td>
					      <td align='center'><input type='checkbox' name='Delete[$inc_index|regex|$index]' /></td>
				        </tr>
					";
					$index++;
					if ($index > $maxindex)
						$maxindex = $index;
				}
				# Regex Files
				$index = 0;
				foreach ($fileset_options["regexfile"] as $line) {
					$regexfile .= "
				    	<tr>
					      <td colspan='2' align='center'>" . WEB_ICON_REGEX_FILE . "</td>
					      <td>
					        <input type='text' name='fileset_options[$inc_index][regexfile][$index]' value='$line' style='width: 420px' />
					      </td>
					      <td align='center'><input type='checkbox' name='Delete[$inc_index|regexfile|$index]' /></td>
				        </tr>
					";
					$index++;
					if ($index > $maxindex)
						$maxindex = $index;
				}
				# Regex Directories
				$index = 0;
				foreach ($fileset_options["regexdir"] as $line) {
					$regexdir .= "
				    	<tr>
					      <td colspan='2' align='center'>" . WEB_ICON_REGEX_DIR . "</td>
					      <td>
					        <input type='text' name='fileset_options[$inc_index][regexdir][$index]' value='$line' style='width: 420px' />
					      </td>
					      <td align='center'><input type='checkbox' name='Delete[$inc_index|regexdir|$index]' /></td>
				        </tr>
					";
					$index++;
					if ($index > $maxindex)
						$maxindex = $index;
				}
				if ($maxindex > 1)
					$show[$inc_index] = 1;
				if ($is_database) {
					try {
						$supported_db = $bacula->GetSupportedDatabases();
						$db_properties = $bacula->GetDatabaseProperties($fileset);
					} catch (Exception $e) {
						break;
					}
					
					foreach ($supported_db as $key => $display) {
						if (eregi($key, $db_properties["TYPE"]))
							$supported_db_options .= "<option value='" . $key . "' SELECTED>" .
								$display . "</option>\n";
						else
							$supported_db_options .= "<option value='" . $key . "'>" .
								$display. "</option>\n";
					}
					$database_options = "
					  <tr>
						<td class='mytablesubheader'>" . WEB_LANG_COMPRESSION . "</td>
						<td>
						  <select name='fileset_options[$inc_index][compression]'>$fileset_compression_options</select>
						</td>
					  </tr>
					  <tr>
						<td class='mytablesubheader'>" . WEB_LANG_SIGNATURE . "</td>
						<td>
						  <select name='fileset_options[$inc_index][signature]'>$fileset_sig_options</select>
						</td>
					  </tr>
					";
					$include_data .= "
					  <tr>
						<td class='mytableheader' colspan='2'>" . WEB_LANG_DB_CONFIG . "</td>
					  </tr>
					  <tr>
						<td class='mytablesubheader'>" . WEB_LANG_TYPE . "</td>
						<td>
						  <select name='db_properties[TYPE]'>$supported_db_options</select>
						</td>
					  </tr>
					  <tr>
						<td class='mytablesubheader'>" . WEB_LANG_HOST . "</td>
						<td>
					      <input type='text' name='db_properties[HOST]' value='" .
							$db_properties["HOST"] . "' style='width: 180px' />
						</td>
					  </tr>
					  <tr>
						<td class='mytablesubheader'>" . WEB_LANG_DATABASE . " " . WEB_LANG_NAME . "</td>
						<td>
					      <input type='text' name='db_properties[NAME]' value='" .
 							$db_properties["NAME"] . "' style='width: 180px' />
						</td>
					  </tr>
					  <tr>
						<td class='mytablesubheader'>" . WEB_LANG_USERNAME . "</td>
						<td>
					      <input type='text' name='db_properties[USER]' value='" .
							$db_properties["USER"] . "' style='width: 180px' />
						</td>
					  </tr>
					  <tr>
						<td class='mytablesubheader'>" . WEB_LANG_PASSWORD . "</td>
						<td>
					      <input type='password' name='db_properties[PASS]' value='" .
							$db_properties["PASS"] . "' style='width: 180px' />
						</td>
					  </tr>
					  <tr>
						<td class='mytablesubheader'>" . WEB_LANG_PORT . "</td>
						<td>
					      <input type='text' name='db_properties[PORT]' value='" .
						    $db_properties["PORT"] . "' style='width: 50px' />
				          <input type='hidden' name='DoEdit[include|$inc_index|$file_index]' value='$value' />
						</td>
					  </tr>
					";

					break;
				}
				$include_data .= "
				  <tr>
					<td class='mytableheader' colspan='3'>" . WEB_LANG_FILESET_INCLUDE . "</td>
					<td class='mytableheader' align='center' width='15%'>
					  <input type='checkbox' name='DoEdit[delete_include|$inc_index|-1]' />
					</td>
				  </tr>
				  <tr>
					<td colspan='4' class='mytablesubheader'>" . WEB_LANG_OPTIONS . "</td>
				  </tr>
				  <tr>
					<td colspan='2' align='center'>" . WEB_ICON_COMPRESSION . "</td>
					<td>
					  <select name='fileset_options[$inc_index][compression]'>$fileset_compression_options</select>
					</td>
					<td>&#160;</td>
				  </tr>
				  <tr>
					<td colspan='2' align='center'>" . WEB_ICON_SIGNATURE . "</td>
					<td>
					  <select name='fileset_options[$inc_index][signature]'>$fileset_sig_options</select>
					</td>
					<td>&#160;</td>
				  </tr>
				  <tr>
					<td colspan='2' align='center'>" . WEB_ICON_CASE . "</td>
					<td>
					  <select name='fileset_options[$inc_index][case]'>$fileset_case_options</select>
					</td>
					<td>&#160;</td>
				  </tr>
				  <tr>
					<td colspan='4' class='mytablesubheader'>" . WEB_LANG_WILD_AND_REGEX . "</td>
				  </tr>
				";
				if ($show[$inc_index] == 1) {
					$include_data .= "
				      <tr>
    					<td colspan='2' align='center'>" . WEB_ICON_OPTIONS . "</td>
    					<td>
    					  <select name='fileset_options[$inc_index][exclude]'>$fileset_exclude_options</select>
    					</td>
    					<td>&#160;</td>
    				  </tr>" .
					$wild . $wildfile . $wilddir . $regex . $regexfile . $regexdir . "
				      <tr>
    					<td colspan='4' align='center'>" .
						WEB_ICON_WILD . "&#160;=&#160;" . WEB_LANG_WILD . "&#160;" .
						WEB_ICON_WILD_FILE . "&#160;=&#160;" . WEB_LANG_WILD_FILE . "&#160;" .
						WEB_ICON_WILD_DIR . "&#160;=&#160;" . WEB_LANG_WILD_DIR . "<br />" .
						WEB_ICON_REGEX . "&#160;=&#160;" . WEB_LANG_REGEX . "&#160;" .
						WEB_ICON_REGEX_FILE . "&#160;=&#160;" . WEB_LANG_REGEX_FILE . "&#160;" .
						WEB_ICON_REGEX_DIR . "&#160;=&#160;" . WEB_LANG_REGEX_DIR . "
                        </td>
    				  </tr>"
					;
				} else {
					$include_data .= "
					  <tr>
						<td colspan='4'>" . WEB_LANG_FILESET_ADVANCED . "&#160;-&#160;
						  <a href='backup-network.php?Action=fileset&Edit[$fileset]=Edit&show[$inc_index]=1'>" . WEB_LANG_SHOW . "</a>.
						</td>
					  </tr>
					";
				}
				$include_data .= "
				  <tr>
					<td colspan='4' class='mytablesubheader'>" . WEB_LANG_DIR_AND_FILES . "</td>
				  </tr>
				";
			}

			if ($value != "") {
				$include_data .= "
				  <tr>
					<td align='center'>" . WEB_ICON_FILESET . "</td>
					<td colspan='2'>
					  <input type='text' name='DoEdit[include|$inc_index|$file_index]' value='$value' style='width: 420px' />
					</td>
					<td align='center' width='15%'>
					  <input type='checkbox' name='DoEdit[delete_include|$inc_index|$file_index]' />
					</td>
				  </tr>\n
				";
			}
			$file_index++;
		}
		if (!$is_database) {
			$include_data .= "
			  <tr>
				<td align='center'>" . WEBCONFIG_ICON_ADD . "</td>
				<td colspan='3'>
				  <input type='text' name='DoEdit[include|$inc_index]' value='' style='width: 420px' />
				</td>
			  </tr>
			";
		}
		$inc_index++;
	}

	$exc_index = 0;
	foreach ($exclude as $exclude_block) {
		$file_index = 0;
		foreach ($exclude_block as $key => $value) {
			if ($file_index == 0) {
				$exclude_data .= "
				  <tr>
					<td class='mytableheader' colspan='3'>" . WEB_LANG_FILESET_EXCLUDE . "</td>
					<td class='mytableheader' align='center' width='15%'>
					  <input type='checkbox' name='DoEdit[delete_exclude|$exc_index|-1]' />
					</td>
				  </tr>
				";
			}
			$exclude_data .= "
			  <tr>
				<td align='center'>" . WEB_ICON_FILESET . "</td>
				<td colspan='2'>
				  <input type='text' name='DoEdit[exclude|$exc_index|$file_index]' value='$value' style='width: 420px' />
				</td>
				<td align='center' width='15%'>
				  <input type='checkbox' name='DoEdit[delete_exclude|$exc_index|$file_index]' />
				</td>
			  </tr>\n
			";
			$file_index++;
		}
		$exclude_data .= "
	      <tr>
            <td align='center'>" . WEBCONFIG_ICON_ADD . "</td>
            <td colspan='3'>
              <input type='text' name='DoEdit[exclude|$exc_index]' value='' style='width: 420px' />
            </td>
	      </tr>
		";
		$exc_index++;
	}

	if (!$include_data && !$is_database) {
	    $include_data = "
		  <tr>
			<td class='mytableheader' colspan='4'>" . WEB_LANG_FILESET_INCLUDE . "</td>
		  </tr>
	      <tr>
            <td align='center'>" . WEBCONFIG_ICON_ADD . "</td>
            <td colspan='3'>
              <input type='text' name='DoEdit[include|$inc_index]' value='' style='width: 420px' />
            </td>
	      </tr>
		";
	}

	if (!$exclude_data && !$is_database) {
	    $exclude_data = "
		  <tr>
			<td class='mytableheader' colspan='4'>" . WEB_LANG_FILESET_EXCLUDE . "</td>
		  </tr>
	      <tr>
            <td align='center'>" . WEBCONFIG_ICON_ADD . "</td>
            <td colspan='3'>
              <input type='text' name='DoEdit[exclude|$exc_index]' value='' style='width: 420px' />
            </td>
	      </tr>
		";
	}

	// HTML output
	//------------
    WebFormOpen($_SERVER['PHP_SELF'], "post");
    WebTableOpen(WEB_LANG_EDIT_FILESET, "100%");
	if ($is_database)
		echo "
		  <tr>
			<td class='mytableheader' colspan='2'>" . WEB_LANG_GENERAL_CONFIG . "</td>
		  </tr>
          <tr>
		    <td class='mytablesubheader' NOWRAP>" . WEB_LANG_NAME . "</td>
            <td>
              <input type='text' name='fileset_name' value='$fileset' style='width: 200px' READONLY />
            </td>
          </tr>" .
		  $database_options .
	      $include_data .
	      $exclude_data . "
          <tr>
            <td class='mytablesubheader'>&#160;</td>
		    <td>
		      " . WebButtonUpdate("DoEdit[$fileset]") . "
		      " . WebButtonCancel("Cancel") . "
	          <input type='hidden' name='Action' value='fileset' />
	          <input type='hidden' name='name' value='$fileset' />
		    </td>
	      </tr>
		";
	else
		echo "
	      <tr>
		    <td class='mytableheader' colspan='3'>" . WEB_LANG_GENERAL_CONFIG . "</td>
 	        <td class='mytableheader' NOWRAP align='center'>" . WEB_LANG_DELETE  . "</td>
    	  </tr>
          <tr>
	    	<td colspan='2' class='mytablesubheader' NOWRAP>" . WEB_LANG_NAME . "</td>
            <td>
              <input type='text' name='fileset_name' value='$fileset' style='width: 200px' READONLY />
            </td>
            <td>&#160;</td>
          </tr>
	      " . $include_data . "
	      " . $exclude_data . "
          <tr>
	    	<td colspan='4' align='right'>
		      " . WebButton("AddInclude[$fileset]", WEB_LANG_ADD_INCLUDE,  WEBCONFIG_ICON_ADD) . "
	    	  " . WebButtonUpdate("DoEdit[$fileset]") . "
    		  " . WebButtonCancel("Cancel") . "
    	      <input type='hidden' name='Action' value='fileset' />
    	      <input type='hidden' name='name' value='$fileset' />
    		</td>
    	  </tr>
    	";
    WebTableClose("100%");
    WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayDeleteFileset()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayDeleteFileset($fileset)
{
    WebFormOpen($_SERVER['PHP_SELF'], "post");
    WebTableOpen(LOCALE_LANG_CONFIRM, "450");
	list($index, $fileset_name) = split("\\|", $fileset);
	echo "<input type='hidden' name='Action' value='fileset' />";
    echo "
      <tr>
        <td align='center'>
          <p>" . WEBCONFIG_ICON_WARNING . " " . WEB_LANG_ARE_YOU_SURE . " " . $fileset_name . "?</p>
          <p>" . WEB_LANG_DEPENDENT_RESOURCES . "</p>
			". WebButtonDelete("DoDelete[$index]") . " " . WebButtonCancel("Cancel") . "
        </td>
      </tr>
    ";
    WebTableClose("450");
    WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayClient()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayClient()
{
	global $bacula;

	list($edit_index, $edit_client) = split("\\|", $edit);
	$spacer = 20;

	# Fetch client list
	$client_list = $bacula->GetClientList();
	asort($client_list);

	$rowspan = (sizeof($client_list) + 4);

	# No clients
	if (sizeof($client_list) == 0)
		$rowspan = 5;
	else if (sizeof($client_list) > 3)
		$spacer = 1;

	// HTML output
	//------------
	WebFormOpen($_SERVER['PHP_SELF'], "post");
	echo "<input type='hidden' name='Action' value='client' />";
	WebTableOpen(WEB_LANG_CLIENT_TITLE, "100%");
	echo "
	  <tr>
		<td class='mytableheader' colspan='3'>" . WEB_LANG_EXISTING_CLIENTS . "</td>
		<td width='40%' valign='top' class='help' rowspan='" . $rowspan . "'>
		  <p>" . WEB_LANG_BACULA_CLIENT_HELP . "
          <a href='http://sourceforge.net/project/showfiles.php?group_id=50727' target='_blank'>
          http://sourceforge.net</a>.
          </p>
		</td>
	  </tr>
	";
	foreach ($client_list as $index => $client) {
		echo "
	    <tr>
		  <td>$client</td>
		";
		$restriction_code = $bacula->IsClientRestricted($client);
		if ($restriction_code == Bacula::RESTRICT_ALL) {
			echo "<td>" . WEB_ICON_LOCKED . "</td><td>" . WEB_ICON_LOCKED . "</td>";
		} else {
			if ($restriction_code == Bacula::RESTRICT_EDIT) {
				echo "<td>" . WEB_ICON_LOCKED . "</td>";
			} else {
				echo "
				  <td width='10%' NOWRAP>
				  " . WebButtonEdit("Edit[$client]") . "
				  </td>
				";
			}
			if ($restriction_code == Bacula::RESTRICT_DELETE) {
				echo "<td>" . WEB_ICON_LOCKED . "</td>";
			} else {
				echo "
				  <td width='10%' NOWRAP>
				  " . WebButtonDelete("Delete[$index|$client]") . "
				  </td>
				";
			}
		}
		echo "</tr>";
	}

	if (sizeof($client_list) == 0)
		echo "<td colspan='3'>" . WEB_LANG_NO_CLIENTS . "</td>";

	echo "
		<tr>
		  <td class='mytableheader' colspan='3'>" . WEB_LANG_ADD_CLIENT . "</td>
		</tr>
		<tr>
		  <td>
	        <input type='text' name='newclient' value='$newclient' style='width: 180px'>
          </td>
		  <td>&#160;</td>
		  <td width='10%' NOWRAP>
		    " . WebButtonAdd("Add") . "
		  </td>
		</tr>
		<tr>
		  <td colspan='3'><img src='../images/transparent.gif' width='1' height='$spacer' alt='' /></td>
		</tr>
	";

	WebTableClose("100%");
    echo "<p align='center'>" .	WebButton("Return", LOCALE_LANG_RETURN_TO_SUMMARY, WEBCONFIG_ICON_BACK) . "</p>";
	WebFormClose();

}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayEditClient()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayEditClient($client)
{
	global $bacula;

	$attributes = $bacula->GetClientAttributes($client);
	$lineindex = 0;
	$time_units = $bacula->GetTimeUnits();
	foreach ($attributes as $line) {
		$pair = split("=", $line);
		if (eregi(trim($pair[0]), "Name")) {
			$client_name = trim($pair[1]);
		} else if (eregi(trim($pair[0]), "Address")) {
			$client_address = trim($pair[1]);
		} else if (eregi(preg_replace("/ /", "", trim($pair[0])), "FDport")) {
			$client_port = trim($pair[1]);
		} else if (eregi(trim($pair[0]), "Password")) {
			$client_password = trim($pair[1]);
		} else if (eregi(preg_replace("/ /", "", trim($pair[0])), "FileRetention")) {
			if (eregi("^(.*)[[:space:]](.*$)", $pair[1], $match))
				$client_file_retention = trim($match[1]);
			foreach ($time_units as $unit_value => $unit_display) {
				if (eregi($unit_value, trim($match[2])))
					$file_retention_options .= "<option value='" . $unit_value . "' SELECTED>\n" .
						$unit_display . "</option>";
				else
					$file_retention_options .= "<option value='" . $unit_value . "'>\n" .
						$unit_display. "</option>";
			}
		} else if (eregi(preg_replace("/ /", "", trim($pair[0])), "JobRetention")) {
			if (eregi("^(.*)[[:space:]](.*$)", $pair[1], $match))
				$client_job_retention = trim($match[1]);
			foreach ($time_units as $unit_value => $unit_display) {
				if (eregi($unit_value, trim($match[2])))
					$job_retention_options .= "<option value='" . $unit_value . "' SELECTED>\n" .
						$unit_display . "</option>";
				else
					$job_retention_options .= "<option value='" . $unit_value . "'>\n" .
						$unit_display . "</option>";
			}
		} else if (eregi(trim($pair[0]), "AutoPrune")) {
			if (eregi(trim($pair[1]), "yes")) {
				$auto_prune_options =
					"<option value='yes' SELECTED>" . LOCALE_LANG_YES . "</option>\n
					<option value='no'>" . LOCALE_LANG_NO . "</option>";
			} else {
				$auto_prune_options =
					"<option value='yes'>" . LOCALE_LANG_YES . "</option>\n
					<option value='no' SELECTED>" . LOCALE_LANG_NO . "</option>";
			}
		}
	}

	// HTML output
	//------------
    WebFormOpen($_SERVER['PHP_SELF'], "post");
    WebTableOpen(WEB_LANG_EDIT_CLIENT, "90%");
	echo "
	  <tr>
		<td class='mytablesubheader' align='right' nowrap>" . WEB_LANG_NAME . "</td>
		<td><input type='text' name='client_name' value='$client_name' style='width: 160px' READONLY /></td>
		<td width='30%' class='help' rowspan='8'>" . WEB_LANG_EDIT_CLIENT_HELP . "</td>
	  </tr>
	  <tr>
		<td align='right' nowrap class='mytablesubheader'>" . WEB_LANG_MACHINE_ADDRESS . "</td>
		<td><input type='text' name='client_address' value='$client_address' style='width: 160px' /></td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' align='right' nowrap>" . WEB_LANG_MACHINE_PORT . "</td>
		<td><input type='text' name='client_port' value='$client_port' style='width: 40px' /></td>
	  </tr>
	  <tr>
		<td align='right' nowrap class='mytablesubheader'>" . WEB_LANG_MACHINE_PASSWORD . "</td>
		<td><input type='text' name='client_password' value='$client_password' style='width: 240px' /></td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' align='right' nowrap>" . WEB_LANG_FILE_RETENTION . "</td>
		<td>
          <input type='text' name='client_file_retention' style='width: 40px' value='$client_file_retention' />
          <select name='client_file_retention_unit'>$file_retention_options</select>
        </td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' align='right' nowrap>" . WEB_LANG_JOB_RETENTION . "</td>
		<td>
          <input type='text' name='client_job_retention' style='width: 40px' value='$client_job_retention' />
          <select name='client_job_retention_unit'>$job_retention_options</select>
        </td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' align='right' nowrap>" . WEB_LANG_AUTO_PRUNE . "</td>
		<td>
          <select name='client_auto_prune'>$auto_prune_options</select>
        </td>
	  </tr>
      <tr>
	    <td class='mytablesubheader'>&#160;</td>
		<td NOWRAP>
		" . WebButtonUpdate("DoEdit[$client]") . "
	    <input type='hidden' name='Action' value='client' />
	    <input type='hidden' name='name' value='$client' />
		</td>
	  </tr>
	";
    WebTableClose("90%");
    WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayDeleteClient()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayDeleteClient($client)
{
    WebFormOpen($_SERVER['PHP_SELF'], "post");
    WebTableOpen(LOCALE_LANG_CONFIRM, "450");
	list($index, $client_name) = split("\\|", $client);
	echo "<input type='hidden' name='Action' value='client' />";
    echo "
      <tr>
        <td align='center'>
          <p>" . WEBCONFIG_ICON_WARNING . " " . WEB_LANG_ARE_YOU_SURE . " " . $client_name . "?</p>
          <p>" . WEB_LANG_DEPENDENT_RESOURCES . "</p>
			". WebButtonDelete("DoDelete[$index]") . " " . WebButtonCancel("Cancel") . "
        </td>
      </tr>
    ";
    WebTableClose("450");
    WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayConfirmDeviceAction()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayConfirmDeviceAction($device, $action)
{
	global $bacula;
    WebFormOpen($_SERVER['PHP_SELF'], "post");
    WebTableOpen(LOCALE_LANG_CONFIRM, "80%");
	echo "<input type='hidden' name='Action' value='control' />";
	echo "<input type='hidden' name='DeviceAction' value='update' />";
	echo "<input type='hidden' name='device_action' value='$action' />";
	echo "<input type='hidden' name='device_name' value='$device' />";
    echo "
      <tr>
        <td align='center'>
	";
	if ($action == "mount") {
		$mountpoint = $bacula->GetSdArchiveDevice($device);
		echo "<p>" . WEB_LANG_CONFIRM_MOUNT . " " . $device . "?</p>" .
			 WebButtonContinue("Confirm") . " " . WebButtonCancel("Cancel")
		;
	} else if ($action == "umount" || $action == "umount_eject") {
		echo "<p>" . WEB_LANG_CONFIRM_UNMOUNT . " " . $device . "?</p>" .
			 WebButtonContinue("Confirm") . " " . WebButtonCancel("Cancel")
		;
	} else if ($action == "eject") {
		echo "<p>" . WEB_LANG_CONFIRM_EJECT . " " . $device . "?</p>" .
			 WebButtonContinue("Confirm") . " " . WebButtonCancel("Cancel")
		;
	} else if ($action == "label") {
		$pool_list = $bacula->GetPoolList();
		foreach ($pool_list as $pool) {
			$pool_options .= "<option value='" . $pool . "'>" . $pool . "</option>\n";
		}
		echo "<table width='50%' border='0' cellpadding='3' cellspacing='0'>
                <tr>
                  <td width='50%' align='right'>" . WEB_LANG_NAME . "</td>
                  <td width='50%'>" . $device . "</td>
                </tr>
                <tr>
                  <td align='right'>" . WEB_LANG_POOL_NAME . "</td>
                  <td>
                    <select name='pool'>$pool_options</select>
                  </td>
                </tr>
                <tr>
                  <td align='right'>" . WEB_LANG_LABEL . "</td>
                  <td>
		            <input type='text' name='label' value='$mountpoint' style='width:180px' />
                  </td>
                </tr>
                <tr>
                  <td>&#160;</td>
                  <td>" .
                    WebButtonContinue("Confirm") . " " . WebButtonCancel("Cancel") . "
                  </td>
                </tr>
              </table>
		";
	}
	echo "
        </td>
      </tr>
    ";
    WebTableClose("80%");
    WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayBasicOptions()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayBasicOptions()
{
	global $bacula;
	echo "
	  <table width='100%' border='0' cellpadding='3' cellspacing='0'>
		<tr>
		  <td>
			<h1>" . WEB_LANG_BASIC . " <span class='small'>(<a href='" .
			$_SERVER['PHP_SELF'] . "?blevel=advanced'>" .WEB_LANG_SHOW_ADVANCED . "</a>)</span></h1>
		  </td>
		</tr>
		<tr>
		  <td>
			" . WebButton("BasicBackupServer", WEB_LANG_BACKUP_SERVER, WEB_ICON_BACKUP) . "
          </td>
        </tr>
		<tr>
		  <td>
			" . WebButton("BasicBackupClient", WEB_LANG_BACKUP_CLIENT, WEB_ICON_CLIENT) . "
		  </td>
		</tr>
		<tr>
		  <td>
			" . WebButton("BasicRestoreServer", WEB_LANG_RESTORE_SERVER, WEB_ICON_BACKUP) . "
		  </td>
		</tr>
		<tr>
		  <td>
			" . WebButton("BasicRestoreClient", WEB_LANG_RESTORE_CLIENT, WEB_ICON_CLIENT) . "
		  </td>
		</tr>
		<tr>
		  <td>
			" . WebButton("GotoConfig", WEB_LANG_RESTORE_CONFIG_FILES, WEB_ICON_GENERAL_CONFIG) . "
		  </td>
		</tr>
		<tr>
		  <td>
			" . WebButton("Controls", WEB_LANG_CONTROL, WEB_ICON_CONTROL) . "
		  </td>
		</tr>
		<tr>
		  <td>
			" . WebButton("AutoDetect", WEB_LANG_AUTO_DETECT, WEB_ICON_STORAGE) . "
		  </td>
		</tr>
	  </table>
      <img src='../images/transparent.gif' width='1' height='1' alt='' />
	";
	$email = $bacula->GetDirectorAdminEmail();
	if ($email == 'root@localhost')
		WebDialogWarning(WEB_LANG_WARN_EMAIL_NOTIFICATION);
	WebTableOpen(WEB_LANG_ALERT_AND_CONFIG_TITLE, "100%");
	echo "
	  <tr>
		<td align='right' nowrap class='mytablesubheader' width='30%'>" . WEB_LANG_ADMIN_EMAIL . "</td>
		<td><input type='text' name='email' value='$email' style='width:180px' /></td>
	  </tr>
	  <tr>
		<td class='mytablesubheader'>&#160;</td>
		<td nowrap>" .
          WebButtonUpdate("UpdateBasicConfig") . "&#160;" .
          WebButton("SendConfig", WEB_LANG_SEND_ALL_CONFIG_FILE, WEB_ICON_EMAIL) . "
        </td>
	  </tr>
    ";
	WebTableClose("100%");
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayBasicSelectClient()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayBasicSelectClient()
{
	global $bacula;
	$network = new Network();
	$client_list = $bacula->GetClientList();
	asort($client_list);
	$client_options = "<option value='-2'>" . WEB_LANG_SELECT . "</option>\n";
	foreach ($client_list as $my_client) {
		$attributes = $bacula->GetClientAttributes($my_client);
		foreach ($attributes as $line) {
			$pair = split("=", $line);
			if (eregi(trim($pair[0]), "Address")) {
				$client_address = trim($pair[1]);
				if ($network->IsLocalIp($client_address))
					continue;
				$client_options .= "<option value='$my_client'>\n$my_client\n</option>\n";
			}
		}
	}
	$client_options .= "<option value='-1'>" . WEB_LANG_CREATE_NEW_CLIENT . "</option>\n";
	WebTableOpen($_SESSION['basic']['title'], "100%");
	echo "<tr>
			<td class='mytableheader'>" . WEB_LANG_CLIENT_TO_BACKUP . "</td>
		  </tr>
			<td>
			  <select name='client'>$client_options</select>
			</td>
		  </tr>
	      <tr>
		    <td>" . WebButtonContinue("") . WebButtonCancel("Cancel") . "</td>
	      </tr>
	";
	WebTableClose('100%');
    echo "<input type='hidden' name='bstep' value='2' />";
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayBasicAddClient()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayBasicAddClient()
{
	global $bacula;
	WebTableOpen($_SESSION['basic']['title'], "100%");
	echo "<tr>
	    	<td class='mytableheader' colspan='2'>" . WEB_LANG_ADD_CLIENT. "</td>
          </tr>
          <tr>
	    	<td align='right' nowrap>" . WEB_LANG_CLIENT_NAME . "</td>
		    <td>
	          <input type='text' name='client' value='" . $_POST['client'] . "' style='width: 180px'>
            </td>
		  </tr>
	      <tr>
		    <td>&nbsp;</td>
		    <td>" . WebButtonContinue("Add") . WebButtonCancel("Cancel") . "</td>
	      </tr>
	";
	WebTableClose('100%');
    echo "<input type='hidden' name='bstep' value='2' />";
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayBasicSetupClient()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayBasicSetupClient()
{
	global $bacula;
	$os_list = $bacula->GetBasicFilesetOsOptions();
	$os_options = "<option value='-1'>" . WEB_LANG_SELECT . "</option>\n";
	foreach ($os_list as $key => $os)
		$os_options .= "<option value='$key'>$os</option>\n";
	WebTableOpen($_SESSION['basic']['title'], "100%");
	echo "<tr>
			<td class='mytableheader' colspan='2'>" . WEB_LANG_WHAT_ADDRESS_AND_OS . "</td>
		  </tr>
	      <tr>
	    	<td align='right' nowrap>" . WEB_LANG_MACHINE_ADDRESS . "</td>
    		<td><input type='text' name='address' value='" . $_POST['address'] . "' style='width: 180px'></td>
    	  </tr>
          <tr>
	    	<td align='right' nowrap>" . WEB_LANG_MACHINE_OS . "</td>
            <td>
              <select name='os'>$os_options</select>
            </td>
		  </tr>
	      <tr>
            <td>&#160;</td>
		    <td>" . WebButtonContinue("ContinueFileset") . WebButtonCancel("Cancel") . "</td>
	      </tr>
	";
	WebTableClose('100%');
    echo "<input type='hidden' name='bstep' value='3' />";
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayBasicSelectFileset()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayBasicSelectFileset()
{
	global $bacula;
	$os_list = $bacula->GetBasicFilesetOsOptions();
	$os_options = "<option value='-1'>" . WEB_LANG_SELECT . "</option>\n";
	foreach ($os_list as $key => $os)
		$os_options .= "<option value='$key'>$os</option>\n";
	WebTableOpen($_SESSION['basic']['title'], "100%");
	echo "<tr>
			<td class='mytableheader' colspan='2'>" . WEB_LANG_MACHINE_OS . "</td>
		  </tr>
          <tr>
	    	<td align='right' nowrap>" . WEB_LANG_MACHINE_OS . "</td>
            <td>
              <select name='os'>$os_options</select>
            </td>
		  </tr>
	      <tr>
            <td>&#160;</td>
		    <td>" . WebButtonContinue("ContinueFileset") . WebButtonCancel("Cancel") . "</td>
	      </tr>
	";
	WebTableClose('100%');
    echo "<input type='hidden' name='bstep' value='4' />";
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayBasicSelectDevice()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayBasicSelectDevice($self, $backup = true)
{
	global $bacula;
	$storage_list = $bacula->GetSdList();
	$storage_options = "<option value='-1'>" . WEB_LANG_SELECT . "</option>\n";
	foreach ($storage_list as $storage) {
		$storage_options .= "<option value='$storage'>\n$storage\n</option>\n";
	}
	# IF this is the server, allow option to backup to client share
	if ($self && $backup)
		$storage_options .= "<option value='toclient'>\n" . WEB_LANG_BACKUP_TO_CLIENT . "\n</option>\n";
	if ($backup)
		$header = WEB_LANG_WHERE_TO_BACKUP_TO;
	else
		$header = WEB_LANG_WHERE_TO_RESTORE_FROM;
	WebTableOpen($_SESSION['basic']['title'], "100%");
	echo "<tr>
			<td class='mytableheader'>$header</td>
		  </tr>
			<td>
			  <select name='storage'>$storage_options</select>
			</td>
		  </tr>
	      <tr>
		    <td>" . WebButtonContinue("ContinueStorage") . WebButtonCancel("Cancel") . "</td>
	      </tr>
	";
	WebTableClose('100%');
    echo "<input type='hidden' name='bstep' value='4' />";
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayBasicConfirmBackup()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayBasicConfirmBackup($show_config = true)
{
	global $bacula;

	$version = $bacula->GetVersion();

    $os = ereg_replace("^" . $_SESSION['basic']['client'] . "-", "", $_SESSION['basic']['fileset']);
	$bacula->CreateClientConfig($_SESSION['basic']['client'], $os);
	$fd = new File(COMMON_TEMP_DIR . "/bacula-fd.conf");
	if ($fd->Exists())
	    $client_config = WebButton(
			"Download[" . $fd->GetFilename() . "]", basename($fd->GetFilename()), WEBCONFIG_ICON_UPDATE
		);
	$console = new File(COMMON_TEMP_DIR . "/wx-console.conf");
	if ($console->Exists())
	    $client_config .= "<br>" . WebButton(
			"Download[" . $console->GetFilename() . "]", basename($console->GetFilename()), WEBCONFIG_ICON_UPDATE
		);
	$client = $_SESSION['basic']['client'];
	$storage = $_SESSION['basic']['storage'];
	WebTableOpen($_SESSION['basic']['title'], "100%");
	echo "<tr>
			<td class='mytableheader' colspan='2'>" . WEB_LANG_CONFIRM_BACKUP . "</td>
		  </tr>
          <tr>
	    	<td align='right' nowrap>" . WEB_LANG_CLIENT_NAME . "</td>
            <td>$client</td>
		  </tr>
	";
	if ($show_config)
	echo "
          <tr>
	    	<td align='right' nowrap>" . WEB_LANG_MACHINE_OS . "</td>
            <td>$os</td>
		  </tr>
	";
    echo "<tr>
	    	<td align='right' nowrap>" . WEB_LANG_DEVICE_NAME . "</td>
            <td>$storage</td>
		  </tr>
	";
	if ($show_config)
	echo "
          <tr>
	    	<td align='right' nowrap>" . WEB_LANG_CLIENT_SOFTWARE . "</td>
            <td>" .
              WEB_ICON_STORAGE . "&#160;
              <a href='" . $_SESSION['system_redirect'] . "/bacula/client={$os}_{$version}' target='_blank'>" . 
              $version . " ($os)</a>
            </td>
		  </tr>
          <tr>
	    	<td align='right' nowrap valign='top'>" . WEB_LANG_CLIENT_CONFIG . "</td>
            <td>" . $client_config . "</td>
		  </tr>
	";
	echo "<tr>
            <td>&#160;</td>
		    <td>" . WebButtonConfirm("Confirm") . WebButtonCancel("Cancel") . "</td>
	      </tr>
	";
	WebTableClose('100%');
    echo "<input type='hidden' name='bstep' value='5' />";
	if (eregi("DVD", $storage))
		WebDialogWarning(WEB_LANG_WARNING_DVD_RW_FORMAT);
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayBasicAddBackupToClient()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayBasicAddBackupToClient()
{
    global $bacula;
	if (!isset($_POST['sharedir']))
		$_POST['sharedir'] = Bacula::SHARED_DOCS;
	WebTableOpen($_SESSION['basic']['title'], "100%");
	echo "<tr>
			<td class='mytableheader' colspan='2'>" . WEB_LANG_ADD_CLIENT_SHARE . "</td>
		  </tr>
          <tr>
	    	<td align='right' nowrap>" . WEB_LANG_CLIENT_NAME . "</td>
    		<td><input type='text' name='name' value='" . $_POST['name'] . "' style='width: 180px'></td>
		  </tr>
	      <tr>
	    	<td align='right' nowrap>" . WEB_LANG_MACHINE_ADDRESS . "</td>
    		<td><input type='text' name='address' value='" . $_POST['address'] . "' style='width: 180px'></td>
    	  </tr>
	      <tr>
	    	<td align='right' nowrap>" . WEB_LANG_USERNAME . "</td>
    		<td><input type='text' name='username' value='" . $_POST['username'] . "' style='width: 180px'></td>
    	  </tr>
	      <tr>
	    	<td align='right' nowrap>" . WEB_LANG_MACHINE_PASSWORD . "</td>
    		<td><input type='password' name='password' value='" . $_POST['password'] . "' style='width: 180px'></td>
    	  </tr>
	      <tr>
	    	<td align='right' nowrap>" . WEB_LANG_SHARE_NAME . "</td>
    		<td><input type='text' name='sharedir' value='" . $_POST['sharedir'] . "' style='width: 180px'></td>
    	  </tr>
	      <tr>
            <td>&#160;</td>
		    <td>" . WebButtonContinue("AddBackupToClient") . WebButtonCancel("Cancel") . "</td>
	      </tr>
	";
    echo "<input type='hidden' name='bstep' value='4' />";
	WebTableClose('100%');
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayBasicServerRestore()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayBasicServerRestore()
{
	global $bacula;
	$client = $_SESSION['basic']['client'];
	$storage = $_SESSION['basic']['storage'];
	WebTableOpen(WEB_LANG_RESTORE_SERVER, "100%");
	echo "<tr>
			<td class='mytableheader' colspan='2'>" . WEB_LANG_CONFIRM_RESTORE . "</td>
		  </tr>
          <tr>
	    	<td align='right' NOWRAP>" . WEB_LANG_DEVICE_NAME . "</td>
            <td>$storage</td>
		  </tr>
		  <tr>
			<td align='right' NOWRAP>" . WEB_LANG_BSR_FILE . "</td>
			<td NOWRAP>
	          <input type='file' name='bsr' style='width: 160px' />
            </td>
		  </tr>
		  <tr>
			<td align='right' NOWRAP>" . WEB_LANG_OVERWRITE_ORIGINAL . "</td>
			<td NOWRAP>
	          <input type='checkbox' name='overwrite_original' />
            </td>
		  </tr>
		  <tr>
		    <td>&#160;</td>
			<td NOWRAP>" .
			  WebButtonConfirm("StartServerRestore") . "&#160;" .
			  WebButtonCancel("Cancel") . "
			</td>
		  </tr>
	";
	WebTableClose("100%");
    echo "<input type='hidden' name='bstep' value='5' />";
}

function DisplayBasicServerRestoreStatus()
{
	global $bacula;
	$status = $bacula->GetJobStatus($_SESSION['basic']['job_id']);
    if ($status['JobStatus'] == "R" || $status['JobStatus'] == "C") {
        WebDialogInfo(WEB_LANG_RESTORE_RUNNING);
	echo "
		<p align='center'>" .
		WebButton("refresh", WEB_LANG_REFRESH, WEBCONFIG_ICON_GO) . "
		</p>
	";
    } else if ($status['JobStatus'] == "T") {
        WebDialogInfo(WEB_LANG_RESTORE_COMPLETE);
        WebTableOpen(WEB_LANG_RESTORE, "100%");
        echo "
        <tr>
          <td class='mytableheader' colspan='2'>" . WEB_LANG_STATS . "</td>
        </tr>
        <tr>
          <td NOWRAP width='30%' align='right'>" . WEB_LANG_JOB_NAME . "</td>
          <td>" . $status['Name']. "</td>
        </tr>
        <tr>
          <td align='right'>" . WEB_LANG_NUMBER_OF_FILES . "</td>
          <td>" . $status['JobFiles']. "</td>
        </tr>
        <tr>
          <td align='right'>" . WEB_LANG_SIZE . "</td>
		  <td>" . $bacula->GetFormattedBytes($status['JobBytes'], 2) . "</td>
        </tr>
    	";
		WebTableClose("100%");
    } else {
    	WebDialogWarning(BACULA_LANG_ERRMSG_RESTORE_FAILED);
    	unset($_SESSION['basic']);
    }
}

function DisplayAutoDetectedDevices()
{
	global $bacula;
	$existing =  $bacula->GetSdList();
	foreach ($bacula->GetDevices() as $dev => $info) {
		$name = preg_replace("/\s+|\\.|\\,/", "_", $info['vendor'] . $info['model']);
		if (array_search($name, $existing)) {
			$action = "<form action='" . $_SERVER['PHP_SELF'] . "' method='post' enctype='multipart/form-data'>" .
				WebButtonUpdate("Edit[" . $name . "]") .
				"<input type='hidden' name='Action' value='storage'></form>";
		} else {
			$action = "<form action='" . $_SERVER['PHP_SELF'] . "' method='post' enctype='multipart/form-data'>" .
				WebButtonAdd("Add") . "<input type='hidden' name='newsd' value='$name'>" .
				"<input type='hidden' name='Action' value='storage'>" .
				"<input type='hidden' name='sd_mount' value='$dev'></form>";
		}
		$data .= "<tr>\n<td>$dev</td>\n<td>" . $info['vendor'] . " " . $info['model'] . "</td>\n" .
				 "<td>" . $info['type'] . "</td>\n<td>$action</td>\n</tr>\n";
	}
	WebTableOpen(WEB_LANG_AUTO_DETECT, "80%");
	WebTableHeader(WEB_LANG_DEVICE . "|" . WEB_LANG_DEVICE_NAME . "|" . WEB_LANG_TYPE . "|");
	echo $data;
	WebTableClose("80%");
}

// vi: syntax=php ts=4 
?>
