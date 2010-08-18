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

require_once('../../gui/Webconfig.inc.php');
// FIXME: remove unused Os.class reference
require_once('../../api/Os.class.php');
require_once('../../api/StorageDevice.class.php');
require_once('../../api/DmCrypt.class.php');
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-encrypted-filesystem.png", WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$dmcrypt = new DmCrypt();

if (isset($_POST['Cancel'])) {
	// Do nothing
} else if (isset($_POST['ConfirmDelete']) && isset($_POST['confirm_delete'])) {
	try {
		$dmcrypt->DeleteVolume($_POST['volume_name']);
	} catch (Exception $e) {
		WebDialogWarning($e->getMessage());
	}
} else if (isset($_POST['Create'])) {
	try {
		// Check to see if it is a device or mount point
		if (!$_POST['mounted-' . $_POST['vol_device']] && (!isset($_POST['Confirm']) || !isset($_POST['confirm_create']))) {
			DisplayConfirmCreate(
				$_POST['vol_name'], $_POST['vol_mount_point'], $_POST['vol_device'],
                $_POST['vol_size'], $_POST['vol_passwd'], $_POST['vol_verify_passwd']
			);
		} else {
			$dmcrypt->CreateVolume(
				$_POST['vol_name'],
				$_POST['vol_mount_point'],
				$_POST['vol_device'],
				$_POST['vol_size'] * 1024,
				$_POST['vol_passwd'],
				$_POST['vol_verify_passwd']
			);
			$dmcrypt->MountVolume($_POST['vol_name'], $_POST['vol_passwd']);

			WebDialogInfo(sprintf('%s<br><b>%s</b>', WEB_LANG_MOUNT_NOTICE,	$_POST['vol_mount_point']));
			unset($_POST);
		}
	} catch (Exception $e) {
		WebDialogWarning($e->getMessage());
	}
} else if (isset($_POST['Mount'])) {
	try {
		$dmcrypt->MountVolume(key($_POST['mount']), $_POST['mount_password']);
		$volumes = $dmcrypt->GetVolumes();

		foreach ($volumes as $volume) { 
			if ($volume['name'] != key($_POST['mount']))
				continue;
			WebDialogInfo(sprintf('%s&#160;&#160;<b>%s</b>', WEB_LANG_MOUNT_NOTICE,	$volume['mount_point']));
			break;
		}
	} catch (Exception $e) {
		WebDialogWarning($e->getMessage());
	}
} else if (isset($_POST['Unmount'])) {
	try {
		$dmcrypt->UnmountVolume(key($_POST['Unmount']));
	} catch (Exception $e) {
		WebDialogWarning($e->getMessage());
	}
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

if (isset($_POST['DisplayConfirmDelete'])) {
	DisplayConfirmDelete(key($_POST['DisplayConfirmDelete']));
} else if (isset($_POST['DisplayMount'])) {
	DisplayMount(key($_POST['DisplayMount']));
} else {
	DisplaySummary();
	DisplayAdd();
}

WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayAdd
//
///////////////////////////////////////////////////////////////////////////////

function DisplayAdd()
{
	global $dmcrypt;

	$storage_manager = new StorageDevice();

	try {
		$volumes = $dmcrypt->GetVolumes();
		$block_devices = $storage_manager->GetDevices();
	} catch (Exception $e) {
		WebDialogWarning($e->getMessage());
	}

	$devices = array();

	$hidden = '';
	foreach ($block_devices as $device => $info) {
		$inuse = false;

		foreach ($volumes as $volume) {
			if ($volume['device'] != $device) continue;
			$inuse = true; break;
		}

		if ($inuse)
			continue;

		$mount_point = isset($info['mount_point']) ? $info['mount_point'] : $device;

		if (isset($info['mounted']) && $info['mounted'])
			$hidden .= "<input type='hidden' id='mounted-$mount_point' name='mounted-$mount_point' value='1' />";
		else
			$hidden .= "<input type='hidden' id='mounted-$mount_point' name='mounted-$mount_point' value='0' />";

		if ($mount_point == '/') 
			$mount_point = '/ (root)';

		$vendor = empty($info['vendor']) ? '' : $info['vendor'];
		$model = empty($info['model']) ? '' : $info['model'];
		$type = empty($info['type']) ? '' : $info['type'];

		$devices[isset($info['mount_point']) ? $info['mount_point'] : $device] =
			sprintf('%s %s %s [%s]', $mount_point, $vendor, $model, $type);

	}

	if (!count($devices)) {
		$vol_device = WEB_LANG_ERR_NO_DETECTED_DEVICES;
	} else {
		if (isset($_POST['vol_device'])) {
			$selected = $_POST['vol_device'];
		} else {
			$selected = key($devices);
		}
		foreach ($devices as $key => $device) {
			$vol_device .= "<option value='$key' " . ($key == $selected ? ' SELECTED' : '') . ">$device</option>\n";
			$size_disable = $device['size_disable'];
		}
		$vol_device = "<select id='vol_device' name='vol_device' onchange='togglevolsize()'>$vol_device</select>";
	}

	$default_mount_point = dirname(DMCRYPT_MOUNT_POINT);

	$vol_name = isset($_POST['vol_name']) ? $_POST['vol_name'] : "";
	$vol_mount_point = isset($_POST['vol_mount_point']) ? $_POST['vol_mount_point'] : "";
	$vol_size = isset($_POST['vol_size']) ? $_POST['vol_size'] : "";
	$vol_passwd = isset($_POST['vol_passwd']) ? $_POST['vol_passwd'] : "";
	$vol_verify_passwd = isset($_POST['vol_verify_passwd']) ? $_POST['vol_verify_passwd'] : "";

	WebFormOpen('/admin/encrypted-filesystem.php', 'post', 'newvolume');
	WebTableOpen(WEB_LANG_CONFIG_TITLE, '80%');
	echo "
		<tr>
			<td class='mytablesubheader'>" . WEB_LANG_VOLUME_NAME . "</td>
			<td><input id='vol_name' onkeypress='return OnNameChange(event)' onchange='UpdateMountPoint(\"$default_mount_point\")' type='text' name='vol_name' value='$vol_name' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader'>" . WEB_LANG_VOLUME_MOUNT_POINT . "</td>
			<td><input id='vol_mount_point' type='text' name='vol_mount_point' value='$vol_mount_point' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader'>" . WEB_LANG_VOLUME_DEVICE . "</td>
			<td>$vol_device</td>
		</tr>
		<tr>
			<td class='mytablesubheader'>" . WEB_LANG_VOLUME_SIZE . " (" . LOCALE_LANG_MEGABYTES . ")</td>
			<td><input id='vol_size' type='text' name='vol_size' value='$vol_size' style='width:65px' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader'>" . WEB_LANG_VOLUME_PASSWORD . "</td>
			<td><input id='vol_passwd' type='password' name='vol_passwd' value='$vol_passwd' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader'>" . WEB_LANG_VOLUME_VERIFY_PASSWORD . "</td>
			<td><input id='vol_verify_passwd' type='password' name='vol_verify_passwd' value='$vol_verify_passwd' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader'>&#160;</td>
			<td>$hidden " .  WebButtonAdd('Create') . "</td>
		</tr>
	";
	WebTableClose('80%');
	WebFormClose();
	echo "<script type=\"text/javascript\" language=\"JavaScript\">togglevolsize();</script>";
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplaySummary
//
///////////////////////////////////////////////////////////////////////////////

function DisplaySummary()
{
	global $dmcrypt;

	$volumes = array();

	try {
		$volumes = $dmcrypt->GetVolumes();
	} catch (Exception $e) {
		WebDialogWarning($e->getMessage());
	}

	if (empty($volumes)) 
		return;

	try {
		$storage_manager = new StorageDevice();
		$block_devices = $storage_manager->GetDevices();
	} catch (Exception $e) {
		WebDialogWarning($e->getMessage());
	}

	$devices = array('local' => 'Local file');

	foreach ($block_devices as $device => $info) {
		$vendor = empty($info['vendor']) ? '' : $info['vendor'];
		$model = empty($info['model']) ? '' : $info['model'];
		$type = empty($info['type']) ? '' : $info['type'];

		$devices[$device] = sprintf('%s %s %s [%s]', $device, $vendor, $model, $type);
	}

	$vol_device = WebDropDownHash('vol_device', 'local', $devices);

	$output = '';
	$index = 0;

	foreach ($volumes as $volume) {
		if (!extension_loaded('statvfs'))
			dl('statvfs.so');

		$stats = statvfs($volume['mount_point']);
		$statusclass = 'icondisabled';
		$rowclass = 'rowdisabled';

		if ($dmcrypt->IsMounted($volume['name'])) {
			$statusclass = 'iconenabled';
			$rowclass = 'rowenabled';
		}

		$rowclass .= ($index % 2) ? 'alt' : '';
		$percent = LOCALE_LANG_UNKNOWN;
		$output .= "<tr class='$rowclass'>";
		$output .= "<td class='$statusclass'>&#160; </td>";
		$output .= '<td>' . $volume['name'] . '</td><td>';
		$output .= $volume['mount_point'] . '</td>';
		$output .= '<td>' . $volume['device'] . '</td>';
		if ($dmcrypt->IsMounted($volume['name'])) {
			$percent = sprintf('%.02f %s %s %.02f %s (%d%%)',
				($stats['used'] / 1024), LOCALE_LANG_MEGABYTES,
				WEB_LANG_OF,
				($stats['size'] / 1024), LOCALE_LANG_MEGABYTES,
				$stats['used'] * 100 / $stats['size']);
			$output .= "<td>$percent</td><td nowrap>";
			$output .= WebButtonToggle('Unmount[' . $volume['name'] . ']', LOCALE_LANG_DISABLE);
		} else {
			$output .= sprintf('<td>%s</td><td nowrap>', $percent);
			$output .= WebButtonToggle('DisplayMount[' . $volume['name'] . ']', LOCALE_LANG_ENABLE);
		}
		$output .= WebButtonDelete('DisplayConfirmDelete[' . $volume['name'] . ']');
		$output .= "</td></tr>\n";
		$index++;
	}

	WebFormOpen();
	WebTableOpen(WEB_LANG_STATUS_TITLE, "100%");
	WebTableHeader(
		"|" . 
		WEB_LANG_VOLUME_NAME . '|' . 
		WEB_LANG_VOLUME_MOUNT_POINT . '|' .
		WEB_LANG_VOLUME_DEVICE . '|' . 
		LOCALE_LANG_STATUS . '|'
	);
	echo $output;
	WebTableClose('100%');
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayConfirmCreate($name, $mount_point, $device, $size, $passwd, $verify)
//
///////////////////////////////////////////////////////////////////////////////

function DisplayConfirmCreate($name, $mount_point, $device, $size, $passwd, $verify)
{
	WebFormOpen();
	WebTableOpen(WEB_LANG_CONFIRMATION, '60%');
	echo "
		<tr>
			<td align='center'>
				<input type='hidden' name='vol_name' value='$name' />
				<input type='hidden' name='vol_mount_point' value='$mount_point' />
				<input type='hidden' name='vol_device' value='$device' />
				<input type='hidden' name='vol_size' value='$size' />
				<input type='hidden' name='vol_passwd' value='$passwd' />
				<input type='hidden' name='vol_verify_passwd' value='$verify' />
				<input type='hidden' name='Create' value='1' />
				<p>" . WEB_LANG_CREATE_VOLUME_WARNING . "</p>
				<p><input name='confirm_create' type='checkbox' /> " .
				WEB_LANG_CONFIRM_CREATE . " <b><i>$device</i></b></p>" . 
				WebButtonConfirm("Confirm") . " " . WebButtonCancel("Cancel") . "
			</td>
		</tr>
	";
	WebTableClose('60%');
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayConfirmDelete($name)
//
///////////////////////////////////////////////////////////////////////////////

function DisplayConfirmDelete($name)
{
	WebFormOpen();
	WebTableOpen(WEB_LANG_CONFIRMATION, '60%');
	echo "
		<tr>
			<td align='center'>
				<input type='hidden' name='volume_name' value='$name'>
				<p>" . WEB_LANG_DELETE_VOLUME_WARNING . "</p>
				<p><input name='confirm_delete' type='checkbox' /> " .
				WEB_LANG_CONFIRM_DELETE . " <b><i>$name</i></b></p>" . 
				WebButtonConfirm("ConfirmDelete") . " " . WebButtonCancel("Cancel") . "
			</td>
		</tr>
	";
	WebTableClose('60%');
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayMount($name)
//
///////////////////////////////////////////////////////////////////////////////

function DisplayMount($name)
{
	WebFormOpen();
	WebTableOpen(WEB_LANG_PASSWORD_TITLE, '60%');
	echo "
		<tr>
			<td class='mytablesubheader'>" . WEB_LANG_VOLUME_NAME . "</td>
			<td nowrap>$name</td>
		</tr>
		<tr>
			<td class='mytablesubheader'>" . WEB_LANG_VOLUME_PASSWORD . "</td>
			<td nowrap>
				<input type='password' name='mount_password' />&#160;&#160;
				<input type='hidden' name='mount[$name]' value='$name' />
			</td>
		</tr>
		<tr>
			<td class='mytablesubheader'>&#160;</td>
			<td>" . WebButtonConfirm('Mount') . WebButtonCancel('Cancel') . "</td>
		</tr>
	";
	WebTableClose('60%');
	WebFormClose();
}

// vim: syntax=php ts=4
?>
