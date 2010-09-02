<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2006-2008 Point Clark Networks.
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

require_once('../../gui/Webconfig.inc.php');
require_once('../../api/Locale.class.php');
require_once('../../api/RemoteBackup.class.php');
require_once('../../api/FileBrowser.class.php');
require_once('../../api/StorageDevice.class.php');
require_once('../../api/Folder.class.php');
require_once('../../api/Mailer.class.php');
require_once('../../api/Hostname.class.php');
require_once("../../api/Product.class.php");
require_once("../../api/ClearSdnService.class.php");
require_once("../../api/ClearSdnStore.class.php");
require_once("../../api/ClearSdnShoppingCart.class.php");
require_once("../../api/ClearSdnCartItem.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// D E F I N E S
//
///////////////////////////////////////////////////////////////////////////////

define('RBS_SORT_ALL', 0);
define('RBS_SORT_SNAPSHOTS', 1);
define('RBS_SORT_FAILURES', 2);

///////////////////////////////////////////////////////////////////////////////
//
// H E A D E R
//
// The authentication happens here but the page header is written after the
// AJAX processing section below...
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();

// Construct global service object
try {
	$rbs = new RemoteBackup();
} catch (Exception $e) {
	exit;
}

///////////////////////////////////////////////////////////////////////////////
//
// A J A X  P O S T S
//
///////////////////////////////////////////////////////////////////////////////

$file_browser = new FileBrowser();
$file_browser->ProcessPost($_POST);

function TimeDuration($tm_begin, $tm_end)
{
	$seconds = $tm_end - $tm_begin;
	if ($seconds <= 0) return '(00:00)';
	$hours = floor($seconds / (60 * 60));
	if ($hours > 0) $seconds -= ($hours * (60 * 60));
	$minutes = floor($seconds / 60);
	return sprintf('(%02u:%02u)', $hours, $minutes);
}

// GetStatus() request
if (array_key_exists('request', $_REQUEST) &&
	$_REQUEST['request'] == 'status') {
	try {
		$status_code = '';
		$status_text = '';
		$status_data = '';
		$running = 0;
		$running_text = WEB_LANG_STATUS_IDLE;
		if ($rbs->IsRunning()) $running = $rbs->GetSessionMode();
		if ($running == RBS_MODE_BACKUP)
			$running_text = WEB_LANG_STATUS_BACKUP;
		else if ($running == RBS_MODE_RESTORE)
			$running_text = WEB_LANG_STATUS_RESTORE;
		else if ($running == RBS_MODE_MOUNT)
			$running_text = WEB_LANG_STATUS_MOUNT;
		else if ($running == RBS_MODE_RESET)
			$running_text = WEB_LANG_STATUS_RESET;
		else if ($running == RBS_MODE_DELETE)
			$running_text = WEB_LANG_STATUS_DELETE;
		if ($running > 0) {
			$status_code = $rbs->GetStatus();
			$status_text = $rbs->TranslateStatusCode($status_code);
			$status_data = $rbs->GetStatusData();
		}
		$error_code = $rbs->GetError();
		$error_text = $rbs->TranslateExceptionId($error_code);
		$history = $rbs->GetSessionHistory();
		$last_backup = WEB_LANG_NO_HISTORY;
		$last_backup_result = REMOTEBACKUP_LANG_STATUS_UNKNOWN;
		$storage_used = 0;
		$storage_capacity = 0;

		if (count($history)) {
			$last_backup_entry = null;
			foreach ($history as $state) {
				if ($state['mode'] != RBS_MODE_BACKUP) continue;
				if ($last_backup_entry == null)
					$last_backup_entry = $state;
				if ($storage_capacity == 0 &&
					strpos($state['usage_stats'], ':') !== false)
					list($storage_used, $storage_capacity) = explode(':',
						$state['usage_stats']);
				if ($last_backup_entry != null && $storage_capacity != 0)
					break;
			}
			if ($last_backup_entry != null) {
				$last_backup = sprintf('%s %s',
					$rbs->LocaleTime($last_backup_entry['tm_started']),
					!strcmp($last_backup_entry['error_code'], '0') ?
						TimeDuration($last_backup_entry['tm_started'],
						$last_backup_entry['tm_completed']) : '; ' .  WEB_LANG_FAILED . '!');
				$last_backup_result = $rbs->TranslateStatusCode($last_backup_entry['status_code']);
				if (strcmp($last_backup_entry['error_code'], '0'))
					$last_backup_result .= '; ' . $rbs->TranslateExceptionId($last_backup_entry['error_code']);
			}
		}
	
		if ($storage_used == 0)
			$storage_used_text = '0 ' . LOCALE_LANG_MEGABYTES;
		else if($storage_capacity != 0)
			$storage_used_text = sprintf('%u %s (%.02f%%)',
				$storage_used / 1024, LOCALE_LANG_MEGABYTES,
				$storage_used * 100 / $storage_capacity);
		if ($storage_capacity == 0) $storage_remaining = WEB_LANG_NONE;
		else $storage_remaining = sprintf('%u %s (%u %s %s)',
			($storage_capacity - $storage_used) / 1024, LOCALE_LANG_MEGABYTES,
			$storage_capacity / 1024, LOCALE_LANG_MEGABYTES, WEB_LANG_TOTAL);
	} catch (Exception $e) {
		// TODO: Could handle this in some generic way...
		exit;
	}

	header('Content-Type: application/xml');
	echo "<?xml version='1.0'?>\n";
	echo "<status>\n";
	echo "\t<running>$running</running>\n";
	echo "\t<running_text>$running_text</running_text>\n";
	echo "\t<code>$status_code</code>\n";
	echo "\t<code_text>$status_text</code_text>\n";
	echo "\t<data>$status_data</data>\n";
	echo "\t<error>$error_code</error>\n";
	echo "\t<error_text>$error_text</error_text>\n";
	echo "\t<last_backup>$last_backup</last_backup>\n";
	echo "\t<last_backup_result>$last_backup_result</last_backup_result>\n";
	echo "\t<storage_used>$storage_used_text</storage_used>\n";
	echo "\t<storage_remaining>$storage_remaining</storage_remaining>\n";
	echo "</status>\n";
	exit;
}
else if (array_key_exists('request', $_REQUEST) &&
	$_REQUEST['request'] == 'backup_history') {
	$error_text = null;
	$backup_history = array();
	try {
		$backup_history = $rbs->RefreshBackupHistory();
		krsort($backup_history, SORT_NUMERIC);
	} catch (Exception $e) {
		// TODO: Untranslated error text
		$error_text = sprintf('[%s] %s', $e->getCode(),
			(strlen($e->getMessage()) ? $e->getMessage() : 'No error message'));
	}

	header('Content-Type: application/xml');
	echo "<?xml version='1.0'?>\n";
	echo "<backup_history>\n";
	if ($error_text != null) printf("\t<error_text>%s</error_text>\n", $error_text);
	else {
		foreach ($backup_history as $key => $value) {
			printf("\t<entry>\n\t\t<mtime>%d</mtime>\n\t\t<text>%s</text>\n\t</entry>\n",
				$key, $value);
		}
	}
	echo "</backup_history>\n";
	exit;
}
if (array_key_exists('request', $_REQUEST) &&
	$_REQUEST['request'] == 'validate_key') {
	$result = false;
	$result_text = WEB_LANG_FSKEY_NOMATCH;
	$key = null;
	try {
		$key = $rbs->GetKeyHash();
	} catch (Exception $e) { }

	if (array_key_exists('rbs_fskey', $_POST)) {
		$hash = md5(base64_decode(urldecode($_POST['rbs_fskey'])));
		if (!strcasecmp($hash, $key)) {
			$result = true;
			$result_text = $hash;
		}
	}

	header('Content-Type: application/xml');
	echo "<?xml version='1.0'?>\n";
	echo "<validate>\n";
	printf("\t<result>%d</result>\n", $result);
	printf("\t<result_text>%s</result_text>\n", $result_text);
	echo "</validate>\n";
	exit;
}

// Write the page header now, after we've tested for an AJAX post
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE,
	'/images/icon-remotebackup.png', WEB_LANG_PAGE_INTRO);
WebCheckRegistration();

///////////////////////////////////////////////////////////////////////////////
//
// F O R M  P O S T S
//
///////////////////////////////////////////////////////////////////////////////

$vol_mount_point = null;
//$rbs->WebDialogDump($rbs->RefreshBackupHistory());

try {
	if (array_key_exists('rbs_do_update_fskey', $_POST) &&
		array_key_exists('rbs_fskey', $_POST) &&
		array_key_exists('rbs_verify_fskey', $_POST)) {
		// Set the filesystem encryption key.  The plain-text key is MD5
		// hashed and stored in the RBS configuration file.
		if (!strcmp($_POST['rbs_fskey'], $_POST['rbs_verify_fskey'])) {
			$key = $rbs->GetKeyHash();
			if ($key == null) $rbs->SetKey($_POST['rbs_fskey']);
			else $rbs->ResetKey($_POST['rbs_fskey']);
		} else {
			$_POST['rbs_do_set_fskey'] = true;
			WebDialogWarning(WEB_LANG_FSKEY_VERIFY_MISMATCH);
		}
	}
	else if (array_key_exists('rbs_update_config', $_POST)) {
		// Save configuration
		UpdateConfiguration(RBS_MODE_BACKUP);
	}
	else if (array_key_exists('rbs_history_refresh', $_POST)) {
		// Refresh backup history data
		$rbs->RefreshBackupHistory();
	}
	else if (array_key_exists('rbs_do_backup', $_POST)) {
		// Start a backup immediately
		$rbs->StartBackup();
	}
	else if (array_key_exists('rbs_do_restore', $_POST)) {
		// Start a restore
		UpdateConfiguration(RBS_MODE_RESTORE);
		$rbs->StartRestore($_POST['rbs_backup_history'], $_POST['rbs_restore_path']);
	}
	else if (array_key_exists('rbs_custom_save_backup', $_POST)) {
		// Save custom backup file list
		$rbs->SaveCustomSelection(RBS_MODE_BACKUP);
	}
	else if (array_key_exists('rbs_custom_save_restore', $_POST)) {
		// Save custom restore file list
		$rbs->UnmountFilesystem();
		$rbs->SaveCustomSelection(RBS_MODE_RESTORE);
	}
	else if (array_key_exists('rbs_custom_reset_backup', $_POST)) {
		// Reset custom backup file list
		$rbs->ResetCustomSelection(RBS_MODE_BACKUP);
		$file_browser->ResetConfiguration(RemoteBackup::FILE_CUSTOM_BACKUP_SELECTION);
	}
	else if (array_key_exists('rbs_custom_reset_restore', $_POST)) {
		// Reset custom restore file list
		$rbs->ResetCustomSelection(RBS_MODE_RESTORE);
		$file_browser->ResetConfiguration(RemoteBackup::FILE_CUSTOM_RESTORE_SELECTION);
	}
	else if (array_key_exists('rbs_browse_restore', $_POST)) {
		// Browse a back-up file
		$rbs->UnmountFilesystem();
		$vol_mount_point = $rbs->MountFilesystem();
	}
	else if (array_key_exists('rbs_change_snapshot', $_POST)) {
		// Browse a different back-up snapshot
		$vol_mount_point = $rbs->GetMountPoint();
	}
	else if (array_key_exists('rbs_unmount', $_POST)) {
		// Unmount remote filesystem
		$rbs->UnmountFilesystem();
	}
	else if (array_key_exists('rbs_delete_snapshot', $_POST)) {
		$rbs->DeleteSnapshot(key($_POST['rbs_delete_snapshot']));
		for ($i = 0; $i < 10; $i++) {
			if ($rbs->DeleteSnapshotExists(key($_POST['rbs_delete_snapshot']))) break;
			sleep(1);
		}
	}
	else if (array_key_exists('rbs_db_update', $_POST)) {
		// Save MySQL database quick-pick configuration
		$db_list = '';
		if (array_key_exists('rbs_db_list', $_POST)) {
			foreach ($_POST['rbs_db_list'] as $db_name) $db_list[] = $db_name;
		}
		if (array_key_exists('rbs_db_avail', $_POST)) {
			foreach ($_POST['rbs_db_avail'] as $db_name) $db_list[] = $db_name;
		}
		$rbs->SetDatabaseQuickPick(RBS_SUBTYPE_DATABASE_MYSQL,
			$_POST['rbs_db_user'], $_POST['rbs_db_pass'], $db_list);
	}
	else if (array_key_exists('rbs_db_update_restore', $_POST)) {
		// Save MySQL database restore configuration
		$db_list = '';
		if (array_key_exists('rbs_db_list', $_POST)) {
			foreach ($_POST['rbs_db_list'] as $db_name) $db_list[] = $db_name;
		}
		if (array_key_exists('rbs_db_avail', $_POST)) {
			foreach ($_POST['rbs_db_avail'] as $db_name) $db_list[] = $db_name;
		}
		$rbs->SetDatabaseQuickPick(RBS_SUBTYPE_DATABASE_MYSQL,
			$_POST['rbs_db_user'], $_POST['rbs_db_pass'],
			$db_list, RBS_MODE_RESTORE);
	}
	else if (array_key_exists('rbs_db_restore', $_POST)) {
		//if (array_key_exists('rbs_backup_history', $_POST))
		$vol_mount_point = $rbs->MountFilesystem();
	}
	else if (array_key_exists('rbs_do_refresh', $_POST)) {
		$_SESSION['rbs_backup_sort'] = $_POST['rbs_backup_sort'];
	}
	else if (array_key_exists('rbs_notify_test', $_POST)) {
		UpdateConfiguration(RBS_MODE_BACKUP);
		$mailer = new Mailer();
		$hostname = new Hostname();
		$mailer->SetSubject(WEB_LANG_EMAIL_SUBJECT_TEST . ' - ' . $hostname->Get());
		$mailer->SetBody(WEB_LANG_EMAIL_BODY_TEST . '.');
		$mailer->AddRecipient($_POST['rbs_notify_email']);
		$sender = $mailer->GetSender();
		if (!strlen($sender)) $mailer->SetSender('root@' . $hostname->Get());
		$mailer->Send();
	}
	else if (array_key_exists('rbs_reset', $_POST)) {
		$rbs->ResetVolume();
	}
	else if (array_key_exists('rbs_reset_history', $_POST)) {
		$rbs->ResetHistory();
	}

} catch (Exception $e) {
	WebDialogWarning($e->getMessage());
}

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

if (isset($_POST['addtocart'])) {
	try {
		$item = new ClearSdnCartItem(ClearSdnService::SDN_BACKUP);
		$item->SetPid($_POST['pid']);
		$item->SetDescription($_POST['description-' . $_POST['pid']]);
		$item->SetUnitPrice($_POST['unitprice-' . $_POST['pid']]);
		$item->SetUnit($_POST['unit-' . $_POST['pid']]);
		$item->SetDiscount($_POST['discount-' . $_POST['pid']]);
		$item->SetCurrency($_POST['currency-' . $_POST['pid']]);
		$item->SetClass(ClearSdnCartItem::CLASS_SERVICE);
		$item->SetGroup("notused");
		$cart = new ClearSdnShoppingCart();
		$cart->AddItem($item);
		WebFormOpen("cart.php");
		WebDialogInfo(CLEARSDN_SHOPPING_CART_LANG_ITEM_ADDED_TO_CART . "&nbsp;&nbsp;" . WebButtonViewCart());
		WebFormClose();
	} catch (ValidationException $e) {
		WebDialogWarning(WEB_LANG_ALREADY_SUBSCRIBED);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
}


///////////////////////////////////////////////////////////////////////////////
//
// M A I N
//
///////////////////////////////////////////////////////////////////////////////

// Custom CSS
/*
echo "
<style>
</style>
";
*/

try {
	// Display service status
	DisplayStatus();
	if (array_key_exists('rbs_browse_restore', $_POST) &&
		// Dispay file browser for custom restore selection
		$vol_mount_point != null) {
		if (array_key_exists('rbs_backup_history', $_POST))
		DisplayBrowseList(RBS_MODE_RESTORE,
			$vol_mount_point, $_POST['rbs_backup_history']);
	} else if (array_key_exists('rbs_change_snapshot', $_POST) &&
		// Dispay file browser for snapshot selection
		$vol_mount_point != null) {
		if (array_key_exists('rbs_backup_history', $_POST))
		DisplayBrowseList(RBS_MODE_RESTORE,
			$vol_mount_point, $_POST['rbs_backup_history'], true);
	} else if (array_key_exists('rbs_browse_backup', $_POST)) {
		// Dispay file browser for custom backup selection
		DisplayBrowseList(RBS_MODE_BACKUP);
	} else if (array_key_exists('rbs_db_backup', $_POST) ||
		array_key_exists('rbs_db_update', $_POST)) {
		// Dispay database configuration
		DisplayDatabaseConfiguration();
	} else if (array_key_exists('rbs_db_restore', $_POST) ||
		array_key_exists('rbs_db_update_restore', $_POST)) {
		// Dispay database configuration
		DisplayDatabaseConfiguration(RBS_MODE_RESTORE);
	} else if (array_key_exists('rbs_do_set_fskey', $_POST)) {
		// Display set FS key dialog
		DisplayResetFilesystemKey(true);
	} else if (array_key_exists('rbs_do_reset_fskey', $_POST)) {
		// Display reset FS key dialog
		DisplayResetFilesystemKey();
	} else if (array_key_exists('rbs_confirm_delete_snapshot', $_POST)) {
		// Display delete snapshot confirmation dialog
		DisplayConfirmDeleteSnapshot(key($_POST['rbs_confirm_delete_snapshot']));
	} else if (array_key_exists('rbs_confirm_reset', $_POST)) {
		// Display reset confirmation dialog
		DisplayConfirmReset(key($_POST['rbs_confirm_reset']));
	} else if (array_key_exists('rbs_confirm_reset_history', $_POST)) {
		// Display reset history confirmation dialog
		DisplayConfirmResetHistory(key($_POST['rbs_confirm_reset_history']));
	} else {
		// Display service configuration
		DisplayTabView();
	}
} catch (Exception $e) {
	WebDialogWarning($e->getMessage());
}

WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S 
/////////////////////////////////////////////////////////////////////////////// 

///////////////////////////////////////////////////////////////////////////////
//
// MyDropDownHash
// Duplicated from Webconfig.inc.php because I need to set the 'id' parameter
//
///////////////////////////////////////////////////////////////////////////////

function MyDropDownHash($variable, $value, $hash, $id = '', $onchange = null)
{
	$found = false;
	$options = "";

	foreach ($hash as $actual => $show) {
		if (strcasecmp($value, $actual) == 0) {
			$options .= "<option value='$actual' selected>$show</option>\n";
			$found = true;
		} else {
			$options .= "<option value='$actual'>$show</option>\n";
		}
	}

	if (!$found)
		$options = "<option value='$value' selected>$value</option>\n" . $options;

	if ($onchange != null)
		$onchange = " onchange=\"$onchange\"";
	else $onchange = '';

	return "<select id='$id' name='$variable'$onchange>$options</select>";
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayStatus()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayStatus()
{
	WebTableOpen(WEB_LANG_STATUS_TITLE, '100%');

	// Current client status
	printf("<tr><td width='20%%' class='mytablesubheader' nowrap>%s</td><td id='rbs_status' width='30%%'>%s</td>\n",
		WEB_LANG_STATUS, REMOTEBACKUP_LANG_STATUS_UNKNOWN);
	printf("<td width='20%%' class='mytablesubheader' nowrap>%s</td><td id='rbs_error' width='30%%'>%s</td></tr>\n",
		WEB_LANG_LAST_ERROR, REMOTEBACKUP_LANG_ERR_UNKNOWN);

	// Last backup/status and next auto-backup
	printf("<tr><td width='20%%' class='mytablesubheader' nowrap>%s</td><td id='rbs_last_backup' width='30%%'>%s</td>\n",
		WEB_LANG_LAST_BACKUP, WEB_LANG_NO_HISTORY);
	printf("<td width='20%%' class='mytablesubheader' nowrap>%s</td><td id='rbs_last_backup_result' width='30%%'>%s</td></tr>\n",
		WEB_LANG_LAST_BACKUP_RESULT, WEB_LANG_NO_HISTORY);

	// Volume storage statistics
	printf("<tr><td width='20%%' class='mytablesubheader' nowrap>%s</td><td id='rbs_storage_used' width='30%%'>%s</td>\n",
		WEB_LANG_STORAGE_USED, '0 ' . LOCALE_LANG_MEGABYTES);
	printf("<td width='20%%' class='mytablesubheader' nowrap>%s</td><td id='rbs_storage_remaining' width='30%%'>%s</td></tr>\n",
		WEB_LANG_STORAGE_CAPACITY, WEB_LANG_NONE);

	WebTableClose('100%');

	echo("\n<span id='rbs_status_ready'></span>\n");
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayTabView()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayTabView()
{
	global $rbs;
	$sdn = new ClearSdnService();

	$key = null;
	try {
		$key = $rbs->GetKeyHash();
	} catch (Exception $e) { }

	if ($key == null)
		$rbs_active_tab = 'config';
	else {
		$rbs_active_tab = isset($_REQUEST['rbs_active_tab']) ?
			$_REQUEST['rbs_active_tab'] : 'config';
	}

	$tabinfo['config']['title'] = WEB_LANG_CONFIG_TITLE;
	$tabinfo['config']['contents'] = GetConfigurationTab();
	$tabinfo['backup']['title'] = WEB_LANG_BACKUP_TITLE;
	$tabinfo['backup']['contents'] = GetBackupTab();
	$tabinfo['restore']['title'] = WEB_LANG_RESTORE_TITLE;
	$tabinfo['restore']['contents'] = GetRestoreTab();
	$tabinfo['history']['title'] = WEB_LANG_HISTORY_TITLE;
	$tabinfo['history']['contents'] = GetHistoryTab();
	$tabinfo['subscription']['title'] = CLEARSDN_SERVICE_LANG_SUBSCRIPTION;
	$tabinfo['subscription']['contents'] = GetSubscriptionTab();

	echo "<div style='width: 100%'>";
	WebTab(WEB_LANG_PAGE_TITLE, $tabinfo, $rbs_active_tab);
	echo "</div>";
}

///////////////////////////////////////////////////////////////////////////////
//
// GetBackupTab()
//
///////////////////////////////////////////////////////////////////////////////

function GetBackupTab()
{
	global $rbs;

	ob_start();

	try {
		$snapshots = $rbs->GetBackupHistory(true);
	} catch (Exception $e) {
		WebDialogWarning($e->getMessage());
		return ob_get_clean();
	}

	WebFormOpen();

	echo "<table cellspacing='0' cellpadding='5' width='100%' border='0' class='tablebody'>\n";
	printf("<tr><td colspan='3'>%s %s</td></tr>\n",
		WebButton('rbs_do_backup', WEB_LANG_BACKUP_NOW, WEBCONFIG_ICON_SAVE),
		WebButton('rbs_refresh', LOCALE_LANG_REFRESH, WEBCONFIG_ICON_TOGGLE));
	echo "<input type='hidden' name='rbs_active_tab' value='backup'>";

	foreach ($snapshots as $subdir => $entries) {
		if (!count($entries)) continue;
		$index = 0;
		$name = WEB_LANG_LEGACY;
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
		}
		WebTableHeader($name . '|' . WEB_LANG_DELTA . ' / ' . WEB_LANG_LINKS . '|');
		foreach ($entries as $snapshot => $size_kb) {
			if (strcmp($subdir, 'legacy'))
				$path = "$subdir/$snapshot";
			else
				$path = $snapshot;
			if ($rbs->DeleteSnapshotExists($path))
				$delete = WEB_LANG_DELETE_PENDING;
			else
				$delete = WebButtonDelete("rbs_confirm_delete_snapshot[$path]");

			$delta = 0;
			if ($size_kb['delta'] > 0) $delta = $size_kb['delta'] / 1024;
			$links = 0;
			if ($size_kb['links'] > 0) $links = $size_kb['links'] / 1024;

			$class = ($index % 2) ? 'mytablealt' : '';
			$index++;

			printf("<tr class='%s'><td nowrap>%s</td>" .
				"<td>%.02f %s / %.02f %s</td><td nowrap width='1%%'>%s</td></tr>",
				$class, $rbs->LocaleTime($snapshot),
				$delta, LOCALE_LANG_MEGABYTES,
				$links, LOCALE_LANG_MEGABYTES, $delete);
		}
	}

	echo "</table>\n";
	WebFormClose();

	return ob_get_clean();
}

///////////////////////////////////////////////////////////////////////////////
//
// GetSubscriptionTab()
//
///////////////////////////////////////////////////////////////////////////////

function GetSubscriptionTab()
{
	ob_start();
	$sdn = new ClearSdnService();
	$store = new ClearSdnStore();
	echo "<div id='sdn-confirm-purchase' title='" . CLEARSDN_STORE_LANG_PURCHASE_CONFIRMATION . "'>";
	echo "<div id='sdn-confirm-purchase-content'></div>";
	echo "</div>";
	WebFormOpen($_SERVER['PHP_SELF'], "post", "backup", "id='clearsdnform' target='_blank'");
	echo "<table cellspacing='0' cellpadding='5' width='100%' border='0' class='tablebody' id='clearsdn-overview'>\n";
	echo "
		<tr id='clearsdn-splash'>
		<td align='center'><img src='/images/icon-os-to-sdn.png' alt=''>
		<div id='whirly' style='padding: 10 0 10 0'>" . WEBCONFIG_ICON_LOADING . "</div>
		</td>
		</tr>
	";
	echo "</table>";
	WebFormClose();

	echo "
        <script language='javascript'>
          $(document).ready(function() {
            $.ajax({
              type: 'POST',
              url: 'clearsdn-ajax.php',
              data: 'action=getServiceDetails&service=" . ClearSdnService::SDN_BACKUP . (isset($_POST['usecache']) ? "&usecache=1" : "") . "',
              success: function(html) {
                $('#clearsdn-splash').remove();
                $('#clearsdn-overview').append(html);
              },
              error: function(xhr, text, err) {
                $('#whirly').html(xhr.responseText.toString());
                // TODO...should need below hack...edit templates.css someday
                $('.ui-state-error').css('max-width', '700px'); 
              }
            });
          });
        </script>
	";
	return ob_get_clean();
}

///////////////////////////////////////////////////////////////////////////////
//
// GetHistoryTab()
//
///////////////////////////////////////////////////////////////////////////////

function GetHistoryTab()
{
	global $rbs;

	ob_start();

	try {
		$history = $rbs->GetSessionHistory();
	} catch (Exception $e) {
		WebDialogWarning($e->getMessage());
		return ob_get_clean();
	}

	$sort_mode = RBS_SORT_ALL;
	if (array_key_exists('rbs_backup_sort', $_SESSION))
		$sort_mode = $_SESSION['rbs_backup_sort'];
	$sort_modes = array(
		WEB_LANG_SORT_ALL,
		WEB_LANG_SORT_FAILURES
	);

	WebFormOpen();

	echo "<table cellspacing='0' cellpadding='5' width='100%' border='0' class='tablebody'>\n";
	printf("<tr><td colspan='4'>%s %s %s</td></tr>\n",
		WebDropDownHash('rbs_backup_sort', $sort_mode, $sort_modes),
		WebButtonRefresh('rbs_do_refresh'),
		WebButtonReset('rbs_confirm_reset_history'));
	echo "<input type='hidden' name='rbs_active_tab' value='history'>";
	WebTableHeader('|' .  LOCALE_LANG_DATE . '|' . WEB_LANG_STATUS_TITLE . '|' .  WEB_LANG_SIZE);

	$index = 0;
	foreach ($history as $entry) {
		if ($entry['mode'] != RBS_MODE_BACKUP) continue;
		if ($sort_mode == RBS_SORT_FAILURES &&
			!strcmp($entry['error_code'], '0')) continue;

		$duration = sprintf('%s %s',
			$rbs->LocaleTime($entry['tm_started']),
			!strcmp($entry['error_code'], '0') ?
				TimeDuration($entry['tm_started'],
				$entry['tm_completed']) : '; ' .  WEB_LANG_FAILED . '!');

		$result = $rbs->TranslateStatusCode($entry['status_code']);
		if (strcmp($entry['error_code'], '0'))
			$result .= '; ' . $rbs->TranslateExceptionId($entry['error_code']);

		//$delete = null;
		if (strcmp($entry['error_code'], '0')) {
			$icon = 'icondisabled';
			$class = 'rowdisabled';
		} else {
			$icon = 'iconenabled';
			$class = 'rowenabled';
			/*
			if (array_key_exists($entry['snapshot'], $snapshots)) {
				if ($rbs->DeleteSnapshotExists($entry['snapshot']))
					$result = WEB_LANG_DELETE_PENDING;
				else
					$delete = WebButtonDelete('rbs_confirm_delete_snapshot[' . $entry['snapshot'] . ']');
			}
			else $result = WEB_LANG_DELETED;
			*/
		}

		$storage_used = 0;
		if (strpos($entry['usage_stats'], ':') !== false)
			list($storage_used, $storage_capacity) = explode(':',
				$entry['usage_stats']);
		if ($storage_used == 0)
			$storage_used_text = '0 ' . LOCALE_LANG_MEGABYTES;
		else if($storage_capacity != 0)
			$storage_used_text = sprintf('%u %s (%.02f%%)',
				$storage_used / 1024, LOCALE_LANG_MEGABYTES,
				$storage_used * 100 / $storage_capacity);
		if ($storage_capacity == 0) $storage_remaining = WEB_LANG_NONE;
		else $storage_remaining = sprintf('%u %s (%u %s %s)',
			($storage_capacity - $storage_used) / 1024, LOCALE_LANG_MEGABYTES,
			$storage_capacity / 1024, LOCALE_LANG_MEGABYTES, WEB_LANG_TOTAL);

		$class .= ($index % 2) ? 'alt' : '';
		$index++;

		printf("<tr class='%s'><td class='%s'>&nbsp;</td><td nowrap width='1%%'>%s</td>" .
			"<td>%s</td><td nowrap width='1%%'>%s / %s</td></tr>", $class, $icon,
			$duration, $result, $storage_used_text, $storage_remaining);

		if ($index == 100) break;
	}

	echo "</table>\n";
	WebFormClose();

	return ob_get_clean();
}

///////////////////////////////////////////////////////////////////////////////
//
// GetRestoreTab()
//
///////////////////////////////////////////////////////////////////////////////

function GetRestoreTab()
{
	global $rbs;

	ob_start();

	// Select / refresh back-up history
	$backup_history = $rbs->GetBackupHistory();

	WebFormOpen();
	echo "<table cellspacing='0' cellpadding='5' width='100%' border='0' class='tablebody'>\n";

	if (!count($backup_history)) {
		echo "<tr><td width='100%'>";
		WebDialogWarning(WEB_LANG_NO_BACKUPS . '<br>' .
			WebButton('rbs_history_refresh',
				LOCALE_LANG_REFRESH, WEBCONFIG_ICON_TOGGLE));
		echo "</td></tr>\n";
	} else {
		// Restore from quick-pick selection
		echo GetQuickPickHtml(RBS_MODE_RESTORE);

		// Backup history selection
		printf("<tr><td width='20%%' class='mytablesubheader'>%s</td>",
			WEB_LANG_CONFIG_BACKUP_HISTORY);
		printf("<td>%s%s</td></tr>\n",
			MyDropDownHash('rbs_backup_history', key($backup_history),
				$backup_history, 'rbs_backup_history'),
			WebButton('rbs_history_refresh',
				LOCALE_LANG_REFRESH, WEBCONFIG_ICON_TOGGLE,
				array('type' => 'button', 'onclick' => 'RefreshBackupHistory()')));

		// Configure restore path
		$mounts = array();
		$storage_manager = new StorageDevice();

		try {
			$block_devices = $storage_manager->GetDevices();
		} catch (Exception $e) {
			printf("<tr><td width='20%%' class='mytableheader'>&nbsp;</td><td>%s</td></tr>",
				WebDialogWarning($e->getMessage()));
		}
	
		foreach ($block_devices as $device => $info) {
			if (!array_key_exists('mounted', $info) || !$info['mounted'] ||
				!array_key_exists('mount_point', $info)) continue;
			if (!strcmp($info['mount_point'], '/boot')) continue;

			if ($info['mount_point'] == '/')
				$mounts[$info['mount_point']] = WEB_LANG_DEFAULT;
			else {
				$mounts[$info['mount_point']] = sprintf('%s %s %s [%s]',
					$info['mount_point'], $info['vendor'],
					$info['model'], $info['type']);
			}
		}

		$mounts['/tmp'] = '/tmp';
		$home_directory = new Folder('/home');

		try {
			$homes = $home_directory->GetListing();
		} catch (Exception $e) {
			printf("<tr><td width='20%%' class='mytableheader'>&nbsp;</td><td>%s</td></tr>",
				WebDialogWarning($e->getMessage()));
		}

		foreach ($homes as $home)
			$mounts['/home/' . $home] = '/home/' . $home;

		printf("<tr><td width='20%%' class='mytablesubheader'>%s</td>",
			WEB_LANG_CONFIG_RESTORE_PATH);
		printf("<td>%s</td></tr>\n",
			MyDropDownHash('rbs_restore_path', '/', $mounts));

		// Restore now...
		printf("<tr><td width='20%%' class='mytablesubheader'>%s</td>",
			'&nbsp;');
		printf("<td>%s</td></tr>\n", WebButton('rbs_do_restore',
			WEB_LANG_RESTORE_NOW, WEBCONFIG_ICON_SAVE));
	}

	echo "<input type='hidden' name='rbs_active_tab' value='restore'>";
	echo "</table>\n";
	WebFormClose();

	return ob_get_clean();
}

///////////////////////////////////////////////////////////////////////////////
//
// GetConfigurationTab()
//
///////////////////////////////////////////////////////////////////////////////

function GetConfigurationTab()
{
	global $rbs;

	ob_start();

	$ab_schedule = array('days' => array_fill(0, 7, false), 'window' => 0);
	try {
		$ab_schedule = $rbs->GetBackupSchedule();
	} catch (Exception $e) {
		WebDialogWarning($e);
	}

	WebFormOpen();
	echo "<table cellspacing='0' cellpadding='5' width='100%' border='0' class='tablebody'>\n";

	// File-system key
	$key = null;
	try {
		$key = $rbs->GetKeyHash();
	} catch (Exception $e) { }

	if ($key != null) {
		// Configure back-up files from quick-pick selection
		echo GetQuickPickHtml();

/* XXX: I think we decided to not expose this option, users that need to switch
 *      from the default (High-security), can be instructed how to edit rbs.conf

		// Client mode
		printf("<tr><td width='20%%' class='mytablesubheader'>%s</td>",
			WEB_LANG_SET_CMODE);
		$mode = null;
		try {
			$mode = $rbs->GetClientMode();
		} catch (Exception $e) { }
		$modes = array(WEB_LANG_CMODE0, WEB_LANG_CMODE1);
		printf("<td>%s</td></tr>\n",
			MyDropDownHash('rbs_client_mode', $mode == null ? 0 : $mode, $modes));
*/

		// Automated back-up enable/disable
		printf("<tr><td width='20%%' class='mytablesubheader'>%s</td>",
			WEB_LANG_AUTOBACKUP);
		$ab_enable = 0;
		try {
			$ab_enabled = $rbs->IsBackupScheduleEnabled();
		} catch (Exception $e) { }
		$ab_options[] = LOCALE_LANG_DISABLED;
		$ab_options[] = LOCALE_LANG_ENABLED;

		printf("<td>%s</td></tr>\n",
			MyDropDownHash('rbs_ab',
				$ab_enabled == null ? 0 : $ab_enabled, $ab_options, 'rbs_ab',
				'EnableAutomatedBackup(this)'));

		// Start time window
		$ab_window = $ab_schedule['window'];
		$ab_windows = array();
		for ($i = 0; $i < 24; $i += 2) {
			$ab_windows[] = sprintf('%02d:00 - %02d:00',
				$i, ($i + 2 == 24 ? 0 : $i + 2));
		}

		printf("<tr><td width='20%%' class='mytablesubheader'>%s</td>",
			WEB_LANG_AUTOBACKUP_WINDOW);
		printf("<td>%s</td></tr>\n",
			MyDropDownHash('rbs_ab_window',
				$ab_window == null ? 0 : $ab_window, $ab_windows, 'rbs_ab_window'));

		// Data retention: daily
		$retain_daily = sprintf("<input id='rbs_retain_daily' type='text' size='4' name='rbs_retain_daily' value='%d'> %s",
			$ab_schedule['daily'], LOCALE_LANG_DAYS);

		printf("<tr><td width='20%%' class='mytablesubheader'>%s</td>",
			WEB_LANG_RETAIN_DAILY);
		printf("<td>%s</td></tr>\n", $retain_daily);

		// Data retention: weekly
		$retain_weekly = sprintf("<input id='rbs_retain_weekly' type='text' size='4' name='rbs_retain_weekly' value='%d'> %s",
			$ab_schedule['weekly'], LOCALE_LANG_WEEKS);

		printf("<tr><td width='20%%' class='mytablesubheader'>%s</td>",
			WEB_LANG_RETAIN_WEEKLY);
		printf("<td>%s</td></tr>\n", $retain_weekly);

		// Data retention: monthly
		$retain_monthly = sprintf("<input id='rbs_retain_monthly' type='text' size='4' name='rbs_retain_monthly' value='%d'> %s",
			$ab_schedule['monthly'], LOCALE_LANG_MONTHS);

		printf("<tr><td width='20%%' class='mytablesubheader'>%s</td>",
			WEB_LANG_RETAIN_MONTHLY);
		printf("<td>%s</td></tr>\n", $retain_monthly);

		// Data retention: yearly
		$retain_yearly = sprintf("<input id='rbs_retain_yearly' type='text' size='4' name='rbs_retain_yearly' value='%d'> %s",
			$ab_schedule['yearly'], LOCALE_LANG_YEARS);

		printf("<tr><td width='20%%' class='mytablesubheader'>%s</td>",
			WEB_LANG_RETAIN_YEARLY);
		printf("<td>%s</td></tr>\n", $retain_yearly);

		// Email notification
		$notify_email = 'root@localhost';
		try {
			$notify_email = $rbs->GetNotifyEmail();
		} catch (Exception $e) { }
		$notify_on_error = true;
		try {
			$notify_on_error = $rbs->IsNotifyOnErrorEnabled();
		} catch (Exception $e) { }
		$notify_summary = false;
		try {
			$notify_summary = $rbs->IsNotifySummaryEnabled();
		} catch (Exception $e) { }
		$email_notify = sprintf("%s: <input id='rbs_notify_email' type='text' name='rbs_notify_email' value='%s'>%s<br>" .
			"<input type='checkbox' name='rbs_notify_error' style='vertical-align: middle;'%s> %s<br>" .
			"<input type='checkbox' name='rbs_notify_summary' style='vertical-align: middle;'%s> %s",
			WEB_LANG_EMAIL_RECIPIENT, $notify_email,
			WebButton('rbs_notify_test',
				WEB_LANG_EMAIL_TEST, WEBCONFIG_ICON_UPDATE),
			$notify_on_error ? ' checked' : '',
			WEB_LANG_EMAIL_SUMMARIES,
			$notify_summary ? ' checked' : '',
			WEB_LANG_EMAIL_ON_ERROR);

		printf("<tr><td width='20%%' class='mytablesubheader' style='vertical-align: top;'>%s</td>",
			WEB_LANG_EMAIL_NOTIFY);
		printf("<td>%s</td></tr>\n", $email_notify);

		// Reset filesystem key
		printf("<tr><td width='20%%' class='mytablesubheader'>%s</td>",
			WEB_LANG_SET_FSKEY);
		printf("<td>%s</td></tr>\n", WebButtonReset('rbs_do_reset_fskey'));

		// Reset remote volume
		printf("<tr><td width='20%%' class='mytablesubheader'>%s</td>",
			WEB_LANG_RESET_TITLE);
		printf("<td>%s</td></tr>\n", WebButtonReset('rbs_confirm_reset'));

		// Enable/disable automated back-up controls 
		echo "<script language='javascript'>\n";
		echo "var control = document.getElementById('rbs_ab');\n";
		echo "if (control) EnableAutomatedBackup(control);\n</script>\n";

		// Update button
		printf("<tr><td width='20%%' class='mytablesubheader'>%s</td>",
			'&nbsp;');
		printf("<td>%s</td></tr>\n", WebButtonUpdate('rbs_update_config'));
	} else {
		printf("<tr><td width='20%%' class='mytablesubheader'>%s</td>",
			WEB_LANG_NO_FSKEY_SET);
		printf("<td>%s</td></tr>\n", WebButtonUpdate('rbs_do_set_fskey'));
	}

	echo "<input type='hidden' name='rbs_active_tab' value='config'>";
	echo "</table>\n";
	WebFormClose();

	return ob_get_clean();
}

///////////////////////////////////////////////////////////////////////////////
//
// GetQuickPickTable()
//
///////////////////////////////////////////////////////////////////////////////

function GetQuickPickHtml($mode = RBS_MODE_BACKUP)
{
	global $rbs;

	$quick_picks = array();
	try { $quick_picks = $rbs->GetFilesystemQuickPicks($mode);
	} catch (Exception $e) {
		ob_start();
		WebDialogWarning($e->getMessage());
		$warning = ob_get_clean();
		return sprintf("<tr><td class='mytablesubheader' width='20%'>&nbsp;</td><td>%s</td></tr>\n",
			$warning);
	}

	$quick_pick_html = '';
	$quick_pick_types = array(RBS_TYPE_FILEDIR, RBS_TYPE_MAIL);

	if (function_exists('mysql_connect'))
		$quick_pick_types[] = RBS_TYPE_DATABASE;

	foreach ($quick_pick_types as $type) {
		switch ($type) {
		case RBS_TYPE_FILEDIR:
			$heading = WEB_LANG_DEFAULTS;
			break;
		case RBS_TYPE_DATABASE:
			$heading = WEB_LANG_DATABASE;
			break;
		default:
			$heading = null;
		}

		foreach ($quick_picks as $name => $desc) {
			if ($desc['type'] != $type) continue;
			$quick_pick_html .= sprintf('<tr><td class="mytablesubheader" width="20%%">%s</td>',
				$heading != null ? $heading : '&nbsp;');
			$heading = null;
			$quick_pick_html .= sprintf('<td><input type="checkbox" name="%s" style="vertical-align: middle;"%s>%s</td></tr>',
				$name, ($desc['enabled'] ? ' checked' : ''),
				$desc['type'] == RBS_TYPE_DATABASE ?
					WebButton($mode == RBS_MODE_BACKUP ? 'rbs_db_backup' : 'rbs_db_restore',
					WEB_LANG_CONFIGURE, WEBCONFIG_ICON_EDIT) : $desc['text']);
		}
	}

	// Configure back-up files from custom browse list
	$quick_pick_html .= sprintf("<tr><td width='20%%' class='mytablesubheader'>%s</td>",
		WEB_LANG_CUSTOM);
	$quick_pick_html .=
		sprintf('<td><input type="checkbox" name="%s" style="vertical-align: middle;"%s>%s %s</td></tr>',
		$mode == RBS_MODE_BACKUP ? 'rbs_custom_backup' : 'rbs_custom_restore',
			$rbs->IsCustomSelectionEnabled($mode) ? ' checked' : '',
		WebButton($mode == RBS_MODE_BACKUP ? 'rbs_browse_backup' : 'rbs_browse_restore',
			WEB_LANG_BROWSE, WEBCONFIG_ICON_SEARCH),
		WebButtonReset($mode == RBS_MODE_BACKUP ? 'rbs_custom_reset_backup' : 'rbs_custom_reset_restore'));

	return $quick_pick_html;
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayBrowseList
//
///////////////////////////////////////////////////////////////////////////////

function DisplayBrowseList($mode, $root = '/', $backup_date = null, $change_snapshot = false)
{
	global $rbs;
	global $file_browser;

	if ($mode == RBS_MODE_RESTORE) {
		$title = WEB_LANG_STATUS_RESTORE . ': ' . WEB_LANG_FB_TITLE;
		if ($backup_date != null) $title .= ': ' . $rbs->LocaleTime($backup_date);
		$root .= '/' . $backup_date;
		$save_location = RemoteBackup::FILE_CUSTOM_RESTORE_SELECTION;
	} else {
		$title = WEB_LANG_STATUS_BACKUP . ': ' . WEB_LANG_FB_TITLE;
		$save_location = RemoteBackup::FILE_CUSTOM_BACKUP_SELECTION;
	}

	$file_browser->Configure($save_location, $root, '/admin/remote-server-backup.php');

	WebFormOpen();
	WebTableOpen($title, '100%');

	$snapshots = '';
	if ($mode == RBS_MODE_RESTORE) {
		$backup_history = $rbs->GetBackupHistory();
		krsort($backup_history, SORT_NUMERIC);
		$snapshots .= ' ' . MyDropDownHash('rbs_backup_history', $backup_date,
			$backup_history, 'rbs_backup_history');
		$snapshots .= ' ' . WebButtonRefresh('rbs_change_snapshot');
	}

	echo "<tr><td class='mytableheader'>Location: <span id='fb_path'>...</span></td></tr>\n";
	printf("<tr><td%s %s %s %s%s</td></tr>",
		WebButtonSave($mode == RBS_MODE_RESTORE ? 'rbs_custom_save_restore' : 'rbs_custom_save_backup'),
		WebButtonCancel($mode == RBS_MODE_RESTORE ? 'rbs_unmount' : 'rbs_cancel'),
		WebButton('fb_select_all', WEB_LANG_SELECT_ALL, WEBCONFIG_ICON_CHECKMARK,
			array('type' => 'button', 'onclick' => 'FileBrowser.selectAll()')),
		WebButton('fb_select_none', WEB_LANG_SELECT_NONE, WEBCONFIG_ICON_XMARK,
			array('type' => 'button', 'onclick' => 'FileBrowser.selectNone()')),
		$snapshots
	);

	echo "<tr><td>";
	echo("<table width='100%' id='fb_table' cellspacing='0'><tr class='mytableheader'><td width='16px'>&#160;</td>\n");
	printf("<td>%s</td><td align='right' style='%s'>%s</td><td>%s</td>",
		WEB_LANG_FB_COLUMN_NAME, 'padding-right: 20px;',
		WEB_LANG_FB_COLUMN_SIZE, WEB_LANG_FB_COLUMN_MODIFIED);
	printf("<td width='40px' align='right'>&nbsp;</td>\n");
	printf("</tr>\n</table></td></tr>");

	printf("<input type='hidden' name='rbs_active_tab' value='%s'>",
		$mode == RBS_MODE_RESTORE ? 'restore' : 'config');

	WebTableClose('100%');
	WebFormClose();

	if ($mode == RBS_MODE_BACKUP)
		echo "<script>window.browseLocal = true;\nFileBrowser.changeDir(null);</script>\n";
	else echo "<script>window.browseLocal = false;</script>\n";
	if ($change_snapshot)
		echo "<script>FileBrowser.changeDir(null);</script>\n";
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayDatabaseConfiguration
// XXX: Currently only MySQL is supported.
//
///////////////////////////////////////////////////////////////////////////////

function DisplayDatabaseConfiguration($mode = RBS_MODE_BACKUP)
{
	global $rbs;

	WebFormOpen();
	WebTableOpen(WEB_LANG_DB_CONFIG_TITLE, '100%');

	$user = 'root';
	$pass = '';
	$db_list = array();

	try {
		$rbs->IsMysqlInstalled();
		$quick_picks = $rbs->GetFilesystemQuickPicks($mode);
		foreach ($quick_picks as $node) {
			if ($node['type'] != RBS_TYPE_DATABASE ||
				$node['sub-type'] != RBS_SUBTYPE_DATABASE_MYSQL) continue;
			if (array_key_exists('username', $node))
				$user = $node['username'];
			if (array_key_exists('password', $node))
				$pass = $node['password'];
			if (array_key_exists('db-name', $node)) {
				$names = explode(':', trim($node['db-name'], ':'));
				foreach ($names as $name) {
					if (!strlen(trim($name))) continue;
					$db_list[] = trim($name);
				}
			}
			break;
		}
	} catch (Exception $e) {
		echo "<tr><td>\n";
		WebDialogWarning($e->getMessage() . ' ' . WebButtonBack('rbs_cancel'));
		echo "</td></tr>\n";
		WebTableClose('100%');
		WebFormClose();
		return;
	}

	// Username
	printf("<tr><td class='mytablesubheader' width='15%%'>%s</td>", WEB_LANG_DB_USERNAME);
	printf("<td><input type='text' name='rbs_db_user' value='%s'></td></tr>\n", $user);

	// Password
	printf("<tr><td class='mytablesubheader' width='15%%'>%s</td>", WEB_LANG_DB_PASSWORD);
	printf("<td><input type='password' name='rbs_db_pass' value='%s'></td></tr>\n", $pass);

	// Try to connect to local MySQL server
	$db_all = array();
	$link = mysql_connect('localhost', $user, $pass);
	if (!is_resource($link)) {
		echo "<tr><td class='mytablesubheader'>&nbsp;</td><td>\n";
		WebDialogWarning(mysql_error());
		echo "</td></tr>\n";
	} else {
		// Databases
		$db_list_result = mysql_list_dbs($link);
		while ($row = mysql_fetch_array($db_list_result, MYSQL_NUM))
			$db_all[] = $row[0];
		$db_avail = array_diff($db_all, $db_list);
		printf("<tr><td class='mytablesubheader' width='15%%'>&nbsp;</td>");
		$style = 'width: 200px;';
		printf("<td><table><tr><td>%s:</td><td>%s:</td></tr>\n<tr><td>",
			WEB_LANG_DB_SELECTED, WEB_LANG_DB_AVAILABLE);
		printf("<select %sstyle='%s' multiple size='12' name='rbs_db_list[]'>\n",
			count($db_list) ? '' : 'disabled ', $style);
		foreach ($db_list as $db_name)
			printf("<option selected>%s</option>\n", $db_name);
		printf("</select>\n</td>");
		printf("<td><select %sstyle='%s' multiple size='12' name='rbs_db_avail[]'>\n",
			count($db_avail) ? '' : 'disabled ', $style);
		foreach ($db_avail as $db_name)
			printf("<option>%s</option>\n", $db_name);
		printf("</select>\n</td></tr></table></td></tr>\n");
	}
	
	printf("<tr><td class='mytablesubheader'>&nbsp;</td><td>%s %s</td></tr>\n",
		$mode == RBS_MODE_BACKUP ? WebButtonUpdate('rbs_db_update') :
			WebButtonUpdate('rbs_db_update_restore'),
		$mode == RBS_MODE_BACKUP ? WebButtonBack('rbs_cancel') :
			WebButtonBack('rbs_unmount'));
	printf("<input type='hidden' name='rbs_active_tab' value='%s'>",
		$mode == RBS_MODE_BACKUP ? 'config' : 'restore');

	WebTableClose('100%');
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayConfirmDeleteSnapshot
//
///////////////////////////////////////////////////////////////////////////////

function DisplayConfirmDeleteSnapshot($snapshot)
{
	global $rbs;

	WebFormOpen();
	echo "<input type='hidden' name='rbs_active_tab' value='backup'>";
	WebTableOpen(WEB_LANG_DELETE_SNAPSHOT_TITLE, '30%');

	// Warning
	if (($timestamp = strstr($snapshot, '/')) === false)
		$timestamp = $snapshot;
	printf("<tr><td>%s</td></tr><tr><td align='center'><b>%s</b></td></tr>\n",
		WEB_LANG_DELETE_SNAPSHOT,
		$rbs->LocaleTime(trim($timestamp, '/')));

	// Confirm or Cancel...
	printf("<tr><td align='center'>%s %s</tr>\n",
		WebButtonDelete('rbs_delete_snapshot[' . $snapshot . ']'),
		WebButtonCancel('rbs_cancel'));

	WebTableClose('30%');
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayConfirmReset
//
///////////////////////////////////////////////////////////////////////////////

function DisplayConfirmReset()
{
	global $rbs;

	WebFormOpen();
	echo "<input type='hidden' name='rbs_active_tab' value='config'>";
	WebTableOpen(WEB_LANG_RESET_TITLE, '30%');

	// Warning
	printf("<tr><td>%s</td></tr>\n", WEB_LANG_RESET_WARNING);

	// Delete or Cancel...
	printf("<tr><td align='center'>%s %s</tr>\n",
		WebButtonDelete('rbs_reset'),
		WebButtonCancel('rbs_cancel'));

	WebTableClose('30%');
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayConfirmResetHistory
//
///////////////////////////////////////////////////////////////////////////////

function DisplayConfirmResetHistory()
{
	global $rbs;

	WebFormOpen();
	echo "<input type='hidden' name='rbs_active_tab' value='history'>";
	WebTableOpen(WEB_LANG_RESET_HISTORY_TITLE, '30%');

	// Warning
	printf("<tr><td>%s</td></tr>\n", WEB_LANG_RESET_HISTORY_WARNING);

	// Delete or Cancel...
	printf("<tr><td align='center'>%s %s</tr>\n",
		WebButtonDelete('rbs_reset_history'),
		WebButtonCancel('rbs_cancel'));

	WebTableClose('30%');
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayResetFilesystemKey
//
///////////////////////////////////////////////////////////////////////////////

function DisplayResetFilesystemKey($verify = false)
{
	global $rbs;

	WebFormOpen();
	echo "<input type='hidden' name='rbs_active_tab' value='config'>";

	if (!$verify) {
		$key = null;
		try {
			$key = $rbs->GetKeyHash();
		} catch (Exception $e) {}

		if ($key == null) {
			WebDialogWarning(WEB_LANG_FSKEY_INVALID . ' ' . WebButtonBack('rbs_cancel'));
			WebFormClose();
			return;
		}

		WebDialogWarning(WEB_LANG_RESET_FSKEY_WARNING);
	}

	WebTableOpen($verify ? WEB_LANG_SET_FSKEY_TITLE : WEB_LANG_RESET_FSKEY_TITLE, '60%');

	// Old FS key
	if (!$verify) {
		printf("<tr><td nowrap width='15%%' class='mytablesubheader'>%s</td>",
			WEB_LANG_OLD_FSKEY);
		printf("<td><input id='rbs_old_fskey' type='password' name='rbs_old_fskey' %s></td></tr>\n",
			'onkeyup="ValidateOldKey(this, \'rbs_fskey\', \'rbs_verify_fskey\', \'rbs_old_fskey_result\')"');

		printf("<tr><td nowrap width='15%%' class='mytablesubheader'>&nbsp;");
		printf("</td><td id='rbs_old_fskey_result'>&nbsp;</td></tr>\n");
	}

	// New FS key
	printf("<tr><td nowrap width='15%%' class='mytablesubheader'>%s</td>",
		WEB_LANG_SET_FSKEY);
	printf("<td><input %sid='rbs_fskey' type='password' name='rbs_fskey'></td></tr>\n",
		$verify ? '' : 'disabled ');

	// Verify FS key
	printf("<tr><td nowrap width='15%%' class='mytablesubheader'>%s</td>",
		WEB_LANG_VERIFY_FSKEY);
	printf("<td><input %sid='rbs_verify_fskey' type='password' name='rbs_verify_fskey'></td></tr>\n",
		$verify ? '' : 'disabled ');

	printf("<tr><td nowrap width='15%%' class='mytablesubheader'>&nbsp;</td><td>&nbsp;</td></tr>\n");

	// Update or Cancel...
	printf("<tr><td class='mytablesubheader'>&nbsp;</td><td>%s %s</tr>\n",
		WebButtonUpdate('rbs_do_update_fskey'), WebButtonCancel('rbs_cancel'));

	WebTableClose('60%');
	WebFormClose();
}

function UpdateConfiguration($mode)
{
	global $rbs;

	// Save quick-picks
	$rbs->SetFilesystemQuickPicks($_POST, $mode);

	if ($mode == RBS_MODE_BACKUP) {
		// Set client mode (high-speed vs. high-security)
		//$rbs->SetClientMode($_POST['rbs_client_mode']);

		// Enable/disable auto-backup
		$rbs->EnableBackupSchedule($_POST['rbs_ab']);

		// Set the auto-backup schedule
		$rbs->SetBackupSchedule($_POST['rbs_ab_window'],
			$_POST['rbs_retain_daily'], $_POST['rbs_retain_weekly'],
			$_POST['rbs_retain_monthly'], $_POST['rbs_retain_yearly']);

		// Set email notification address and options
		$rbs->SetNotifyEmail($_POST['rbs_notify_email']);
		$rbs->EnableNotifyOnError(array_key_exists(
			'rbs_notify_error', $_POST) ? true : false);
		$rbs->EnableNotifySummary(array_key_exists(
			'rbs_notify_summary', $_POST) ? true : false);
	}

	// Enable/disable custom backup/restore selection
	if (array_key_exists($mode == RBS_MODE_BACKUP ?
		'rbs_custom_backup' : 'rbs_custom_restore', $_POST))
		$rbs->EnableCustomSelection($mode, true);
	else
		$rbs->EnableCustomSelection($mode, false);
}

// vi: syntax=php ts=4
?>
