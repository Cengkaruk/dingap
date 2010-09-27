<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2003-2007 Point Clark Networks.
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
require_once("../../api/Raid.class.php");
require_once('../../api/StorageDevice.class.php');
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE, "default", "<script type='text/javascript' src='/admin/raidstatus.js'></script>\n", 'getStatus()');
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-raid.png", WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$raid = Raid::Create();

$hidesummary = false;
try {
	if (isset($_POST['Update'])) {
		$raid->SetMonitorStatus($_POST['monitor']);
		$raid->SetNotify($_POST['notify']);
		if ($raid->GetNotify())
			$raid->SetEmail($_POST['email']);
	}
	if (isset($_POST['Remove'])) {
		list($array, $device) = split("\\|", key($_POST['Remove']));
		$raid->RemoveDevice($array, $device);
	}
	if (isset($_POST['RepairSoftwareArray']))
		$raid->RepairArray($_POST['array'], $_POST['device']);
	if (isset($_POST['RepairHardwareArray']))
		$raid->RepairArray($_POST['controller'], $_POST['unit'], $_POST['port']);
	if (isset($_POST['RepairSoftware'])) {
		$hidesummary = true;
		if (isset($_POST['confirm']) && !isset($_POST['action'])) {
			try {
				$raid->CopyPartitionTable($_POST['from'], $_POST['to']);
			} catch (Exception $e) {
				WebDialogWarning($e->GetMessage());
			}
		}
		DisplaySoftwareRepair(key($_POST['RepairSoftware']));
		DisplaySoftwareRaidDetails();
	}
	if (isset($_POST['RepairHardware'])) {
		$hidesummary = true;
		list($controller, $unit) = split("\\|", key($_POST['RepairHardware']));
		DisplayHardwareRepair($controller, $unit);
		DisplayHardwareRaidDetails();
	}
} catch (Exception $e) {
	WebDialogWarning($e->GetMessage());
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////


if (!$hidesummary)
	DisplaySummary();

WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplaySummary()
//
///////////////////////////////////////////////////////////////////////////////

function DisplaySummary()
{
	global $raid;
	try {
		$monitor = $raid->GetMonitorStatus();
		$type = $raid->GetTypeDetails();
		$notify = $raid->GetNotify();
		$email = $raid->GetEmail();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	if ($type['id'] == Raid::TYPE_UNKNOWN) {
		// Disable monitoring if type is unknown
		$raid->SetMonitorStatus(false);
	}

	
	// TODO - Would like to use WebEnabledDisabled widget, but it does not support js yet (id, onchange etc.)
	$monitor_options = "
		  <option value='0'" . (!$monitor ? ' SELECTED' : '') . ">" . LOCALE_LANG_DISABLED . "</option>
		  <option value='1'" . ($monitor ? ' SELECTED' : '') . ">" . LOCALE_LANG_ENABLED . "</option>
	";
	$notify_options = "
		  <option value='0'" . (!$notify ? ' SELECTED' : '') . ">" . LOCALE_LANG_DISABLED . "</option>
		  <option value='1'" . ($notify ? ' SELECTED' : '') . ">" . LOCALE_LANG_ENABLED . "</option>
	";

	WebFormOpen();
	WebTableOpen(WEB_LANG_RAID_SUMMARY, '60%');
	echo "<tr>
			<td width='40%' class='mytablesubheader' nowrap>" . WEB_LANG_TYPE . "</td>
			<td>" . $type['class'] . "</td>
		  </tr>
		  <tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_VENDOR . "</td>
			<td>" . $type['vendor'] . "</td>
		  </tr>
		  <tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_MONITOR . "</td>
			<td><select id='monitor' name='monitor' onchange='togglenotify()'>$monitor_options</select></td>
		  </tr>
		  <tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_NOTIFICATION . "</td>
			<td><select id='notify' name='notify' onchange='toggleemail()'>$notify_options</select></td>
		  </tr>
		  <tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_EMAIL . "</td>
			<td><input id='email' type='text' name='email' value='$email' style='width:200px'></td>
		  </tr>
		  <tr>
			<td class='mytablesubheader'>&#160;</td>
            <td>
			  " . WebButtonUpdate("Update") . "
            </td>
          </tr>
	";
	WebTableClose('60%');
	WebFormClose();
	echo "<script type='text/javascript' language='JavaScript'>" .
		($type['id'] != Raid::TYPE_UNKNOWN ? 'enable' : 'disable') . "('monitor');" .
		($type['id'] != Raid::TYPE_UNKNOWN ? 'enable' : 'disable') . "('notify');" .
		"</script>";
	echo "<script type='text/javascript' language='JavaScript'>" . ($notify ? 'enable' : 'disable') . "('email');</script>";
	echo "<script type='text/javascript' language='JavaScript'>togglenotify();</script>";

	if ($type['id'] == Raid::TYPE_SOFTWARE)
		DisplaySoftwareRaidDetails();
	else if ($type['id'] != Raid::TYPE_UNKNOWN)
		DisplayHardwareRaidDetails();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplaySoftwareRaidDetails()
//
///////////////////////////////////////////////////////////////////////////////

function DisplaySoftwareRaidDetails()
{
	global $raid;
	$help = null;
	try {
		$myarrays = $raid->GetArrays();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

	$index = 0;
	foreach ($myarrays as $dev => $myarray) {
		$rowclass = "rowenabled";
		$iconclass = "iconenabled";
		$status = RAID_LANG_CLEAN;
		$mount = $raid->GetMount($dev);
		$action = '&#160;';
		$rowclass .= ($index % 2) ? "alt" : "";
		if ($myarray['status'] != Raid::STATUS_CLEAN) {
			$iconclass = "icondisabled";
			$status = RAID_LANG_DEGRADED;
			if ($raid->GetInteractive())
				$action = WebButton('RepairSoftware[' . $dev . ']', WEB_LANG_REPAIR, WEBCONFIG_ICON_ADD);
		}
		foreach ($myarray['devices'] as $id => $details) {
			if ($details['status'] == Raid::STATUS_SYNCING) {
				# Provide a more detailed status message
				$status = RAID_LANG_SYNCING . ' (' . $details['dev'] . ') - ' . $details['recovery'] . '%';
			} else if ($details['status'] == Raid::STATUS_SYNC_PENDING) {
				# Provide a more detailed status message
				$status = RAID_LANG_SYNC_PENDING . ' (' . $details['dev'] . ')';
			} else if ($details['status'] == Raid::STATUS_DEGRADED) {
				# Provide a more detailed status message
				$status = RAID_LANG_DEGRADED . ' (' . $details['dev'] . ' ' . RAID_LANG_FAILED . ')';
				# Check what action applies
				if ($myarray['number'] >= count($myarray['devices'])) {
					if ($raid->GetInteractive())
						$action = WebButton('Remove[' . $dev . '|' . $details['dev'] . ']', WEB_LANG_REMOVE . ' ' . $details['dev'], WEBCONFIG_ICON_DELETE);
				}
				$help = $details['dev'];
				
			}
		}
		$data .= "
	  	  <tr class='$rowclass'>
			<td id='icon_" . str_replace('/dev/', '', $dev) . "' class='$iconclass'>&#160;</td>
            <td>$dev</td>
            <td>" . $raid->GetFormattedBytes($myarray['size'], 1) . "</td>
            <td>$mount</td>
            <td>" . $myarray['level'] . "</td>
            <td width='30%' id='status_" . str_replace('/dev/', '', $dev) . "'>$status</td>
            <td><div id='action_" . str_replace('/dev/', '', $dev) . "'>$action</div></td>
		  </tr>
		";
		$index++;
	}

	if ($help != null) {
		try {
			$storage = new StorageDevice();
			$block_devices = $storage->GetDevices();
			$info = $block_devices[$help];
			WebDialogInfo($help . ' = ' . $info['vendor'] . ' ' . $info['model']);
		} catch (Exception $e) {
			// Ignore
		}
	}
	WebFormOpen();
	WebTableOpen(WEB_LANG_RAID_DETAILS, '100%');
	WebTableHeader("|" . RAID_LANG_ARRAY . "|" . RAID_LANG_SIZE . "|" . RAID_LANG_MOUNT . "|" . RAID_LANG_LEVEL . "|" . LOCALE_LANG_STATUS . "|");
	echo $data;
	WebTableClose('100%');
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayHardwareRaidDetails()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayHardwareRaidDetails()
{
	global $raid;
	try {
		$controllers = $raid->GetArrays();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

	$index = 0;
	foreach ($controllers as $controllerid => $controller) {
		# Reset action variable
		$action = '&#160;';
		foreach ($controller['units'] as $unitid => $unit) {
			$rowclass = "rowenabled";
			$iconclass = "iconenabled";
			$status = RAID_LANG_CLEAN;
			$mount = $raid->GetMapping($unitid);
			$rowclass .= ($index % 2) ? "alt" : "";
			if ($unit['status'] != Raid::STATUS_CLEAN) {
				$iconclass = "icondisabled";
				$status = RAID_LANG_DEGRADED;
				if ($raid->GetInteractive())
					$action = WebButton("RepairHardware[$controllerid|$unitid]", WEB_LANG_REPAIR, WEBCONFIG_ICON_ADD);
			}
			foreach ($unit['devices'] as $id => $details) {
				if ($details['status'] == Raid::STATUS_SYNCING) {
					# Provide a more detailed status message
					$status = RAID_LANG_SYNCING . ' (' . RAID_LANG_DISK . ' ' . $id . ') - ' . $details['recovery'] . '%';
				} else if ($details['status'] == Raid::STATUS_SYNC_PENDING) {
					# Provide a more detailed status message
					$status = RAID_LANG_SYNC_PENDING . ' (' . RAID_LANG_DISK . ' ' . $id . ')';
				} else if ($details['status'] == Raid::STATUS_DEGRADED) {
					# Provide a more detailed status message
					$status = RAID_LANG_DEGRADED . ' (' . RAID_LANG_DISK . ' ' . $id . ' ' . RAID_LANG_FAILED . ')';
					if ($raid->GetInteractive())
						$action = WebButton('Remove[' . $controllerid . '|' . $id . ']', WEB_LANG_REMOVE . ' ' . RAID_LANG_DISK . ' ' . $id, WEBCONFIG_ICON_DELETE);
				} else if ($details['status'] == Raid::STATUS_REMOVED) {
					# Provide a more detailed status message
					$status = RAID_LANG_DEGRADED . ' (' . RAID_LANG_DISK . ' ' . $id . ' ' . RAID_LANG_REMOVED . ')';
				}
			}
			$data .= "
			  <tr class='$rowclass'>
				<td id='icon_$controllerid-$unitid' class='$iconclass'>&#160;</td>
				<td>" . $controller['model'] . ", " . RAID_LANG_SLOT . " $controllerid</td>
				<td>" . RAID_LANG_LOGICAL_DISK . " $unitid</td>
				<td>" . $raid->GetFormattedBytes($unit['size'], 1) . "</td>
				<td>$mount</td>
				<td>" . $unit['level'] . "</td>
				<td width='30%' id='status_$controllerid-$unitid'>$status</td>
            	<td><div id='action_$controllerid-$unitid'>$action</div></td>
			  </tr>
			";
			$index++;
		}
	}

	WebFormOpen();
	WebTableOpen(WEB_LANG_RAID_DETAILS, '100%');
	WebTableHeader("|" . RAID_LANG_CONTROLLER . "|" . RAID_LANG_UNIT . "|" . RAID_LANG_SIZE . "|" . RAID_LANG_DEVICE . "|" . RAID_LANG_LEVEL . "|" . LOCALE_LANG_STATUS . "|");
	echo $data;
	WebTableClose('100%');
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplaySoftwareRepair($array)
//
///////////////////////////////////////////////////////////////////////////////

function DisplaySoftwareRepair($array)
{
	global $raid;
	try {
		$myarrays = $raid->GetArrays();
		$devicesinuse = $raid->GetDevicesInUse();
		$storage = new StorageDevice();
		$block_devices = $storage->GetDevices();
		$options = Array();
		$actions = Array(0 => WEB_LANG_COPY_PARTITION_TABLE, 1 => WEB_LANG_MANUAL_OVERRIDE);
		$action_options = '';
		$partition_ok = Array('ok' => false);
		$existing = '';
		foreach ($myarrays as $dev => $myarray) {
			if ($array != $dev)
				continue;
			foreach ($myarray['devices'] as $id => $details) {
				$existing .= $details['dev'] . ', ';
			}
		}
		if ($existing)
			$existing = substr($existing, 0, strlen($existing) -2);
		foreach ($block_devices as $dev => $info) {
			# Skip devices in use
			if (in_array($dev, $devicesinuse))
				continue;
			try {
				if (!$partition_ok['ok'])
					$partition_ok = $raid->SanityCheckPartition($array, $dev);
				$options[$dev] = $dev . ' (' . $info['vendor'] . ' ' . $info['model'] . ')';
			} catch (EngineException $e) {
				// Ignore
			}
		}

		foreach ($actions as $key => $action)
			$action_options .= "<option value='$key'" .
				((isset($_POST['action']) && $_POST['action'] == $key) ? ' SELECTED' : '') . "'>$action</option>\n";

		if ($partition_ok['ok'] || (isset($_POST['confirm']) && isset($_POST['action']) && $_POST['action'] == 1)) {
			WebFormOpen();
			WebTableOpen(WEB_LANG_REPAIR, '60%');
			echo '<tr>
				    <td class=\'mytablesubheader\' width=\'30%\' NOWRAP>' . RAID_LANG_ARRAY . '</td>
				    <td>' . $array . '</td>
				  </tr>
			      <tr>
				    <td class=\'mytablesubheader\' NOWRAP>' . WEB_LANG_EXISTING_DEVICES . '</td>
				    <td>' . $existing . '</td>
				  </tr>
			      <tr>
				    <td class=\'mytablesubheader\' NOWRAP>' . WEB_LANG_ADD_DEVICE . '</td>
				    <td>' . WebDropDownHash('device', key($options), $options) . '</td>
				  </tr>
			      <tr>
				    <td class=\'mytablesubheader\' NOWRAP>&#160;</td>
				    <td><input type=\'hidden\' name=\'array\' value=\'' . $array . '\' />' .
					WebButton('RepairSoftwareArray', WEB_LANG_REPAIR, WEBCONFIG_ICON_ADD) .
					WebButtonCancel("Cancel") . '</td>
				  </tr>
			';
			WebTableClose('60%');
			WebFormClose();
		} else {
			if (!isset($_POST['confirm']))
				WebDialogWarning(WEB_LANG_PARTITION_WARNING);
			else if (isset($_POST['confirm']) && isset($_POST['action']) && $_POST['action'] == 0)
				WebDialogWarning(WEB_LANG_COPY_FAILED);
			else
				WebDialogWarning(WEB_LANG_PARTITION_WARNING);
			WebFormOpen();
	  		echo "<div id='container'>";
			WebTableOpen(WEB_LANG_REPAIR_PARTITION_TABLE, '60%');

// FIXME: createPanel needs to be removed
			foreach ($partition_ok['table'] as $lines)
				$pt .= $lines . "<br>";
			echo "<tr>
				    <td class='mytablesubheader' width='30%' NOWRAP>" . RAID_LANG_ARRAY . "</td>
				    <td>$array</td>
				  </tr>
			      <tr>
				    <td class='mytablesubheader' NOWRAP>" . LOCALE_LANG_ACTION . "</td>
			        <td><select id='action' name='action' onchange='toggleview()'>$action_options</select></td>
				  </tr>
			      <tr id='copyfrom'>
				    <td class='mytablesubheader' NOWRAP>" . WEB_LANG_COPY_FROM . "</td>
				    <td id='position-goodpart'>" . $partition_ok['dev'] .
					  "<a href='#name' onclick=\"createPanel('goodpart','" . $partition_ok['dev'] . "','" .
					  "<span style=text-align:left><pre>" . $pt . "</pre></span>')\">" . WEBCONFIG_ICON_INFO . "</a>
				    </td>
				  </tr>
			      <tr id='copyto'>
				    <td class='mytablesubheader' NOWRAP>" . WEB_LANG_COPY_TO . "</td>
				    <td>" . WebDropDownHash('to', key($options), $options) . "</td>
				  </tr>
			      <tr>
				    <td class='mytablesubheader' NOWRAP>" . LOCALE_LANG_CONFIRM . "</td>
				    <td><input type='checkbox' name='confirm' />" . WEB_LANG_VERIFY . "</td>
				  </tr>
			      <tr>
				    <td class='mytablesubheader' NOWRAP>&#160;</td>
				    <td><input type='hidden' name='array' value='$array' />
				    <input type='hidden' name='from' value='" . $partition_ok['dev'] . "' />" .
					WebButtonContinue('RepairSoftware[' . $array . ']') .
					WebButtonCancel("Cancel") . "</td>
				  </tr>
			";
			WebTableClose('60%');
			echo "</div>";
			WebFormClose();
			echo "<script type=\"text/javascript\" language=\"JavaScript\">toggleview();</script>";
		}
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
}
///////////////////////////////////////////////////////////////////////////////
//
// DisplayHardwareRepair($array)
//
///////////////////////////////////////////////////////////////////////////////

function DisplayHardwareRepair($cid, $uid)
{
	global $raid;
	try {
		$options = Array();
		$spares = Array(); 
		$controllers = $raid->GetArrays();
		foreach ($controllers as $controllerid => $controller) {
			if ($controllerid != $cid)
				continue;
			foreach ($controller['units'] as $unitid => $unit) {
				if ($unitid != $uid)
					continue;
			}
		}
		if (isset($controller['spares']))
			$spares = $controller['spares'];
		foreach ($spares as $id => $spare) {
			$options[$id] = RAID_LANG_PORT . ' ' . $id . ', ' . $raid->GetFormattedBytes($spare['size'], 1); 
		}
		WebFormOpen();
		WebTableOpen(WEB_LANG_REPAIR, '60%');
		echo '<tr>
			    <td class=\'mytablesubheader\' width=\'30%\' NOWRAP>' . RAID_LANG_CONTROLLER . '</td>
			    <td>' . $controller['model'] . ', ' . RAID_LANG_SLOT . ' ' . $controllerid . '</td>
			  </tr>
		      <tr>
			    <td class=\'mytablesubheader\' NOWRAP>' . RAID_LANG_UNIT . '</td>
			    <td>' . RAID_LANG_LOGICAL_DISK . ' ' . $unitid . '</td>
			  </tr>
		      <tr>
			    <td class=\'mytablesubheader\' NOWRAP>' . RAID_LANG_DISK . '</td>
			    <td>' . WebDropDownHash('port', key($options), $options) . '</td>
			  </tr>
		      <tr>
			    <td class=\'mytablesubheader\' NOWRAP>&#160;</td>
			    <td><input type=\'hidden\' name=\'controller\' value=\'' . $cid . '\' />
			    <input type=\'hidden\' name=\'unit\' value=\'' . $uid . '\' />' .
				WebButton('RepairHardwareArray', WEB_LANG_REPAIR, WEBCONFIG_ICON_ADD) .
				WebButtonCancel("Cancel") . '</td>
			  </tr>
		';
		WebTableClose('60%');
		WebFormClose();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
}
// vim: ts=4
?>
