<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2006 Point Clark Networks.
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
require_once(GlobalGetLanguageTemplate('raid.php'));

WebAuthenticate();

try {
	$raidstatus = Raid::Create();
	$type = $raidstatus->GetType();

	if ($type == Raid::TYPE_UNKNOWN)
		$myarrays = array();
	else
		$myarrays = $raidstatus->GetArrays();
} catch (Exception $e) {
	$myarrays = Array();
}

$status = '';
if ($type == Raid::TYPE_UNKNOWN)
	$status = '';
else if ($type == Raid::TYPE_SOFTWARE)
	$status = GetSoftwareStatus($myarrays);
else
	$status = GetHardwareStatus($myarrays);

header('Content-Type: application/xml');

$thedate = strftime("%b %e %Y");
$thetime = strftime("%T %Z");

echo "<?xml version='1.0'?>
<raidstatus>
  <timestamp>$thedate $thetime</timestamp>\n" .
  $status . "
</raidstatus>
";

function GetSoftwareStatus($myarrays)
{
	foreach ($myarrays as $dev => $myarray) {
		$status .= "  <devicearray>\n";
		$status .= "    <name>$dev</name>\n";
		$code = Raid::STATUS_CLEAN;
		$text = RAID_LANG_CLEAN;
		if ($myarray['status'] != Raid::STATUS_CLEAN) {
			$code = Raid::STATUS_DEGRADED;
			$text = RAID_LANG_DEGRADED;
		}
		foreach ($myarray['devices'] as $index => $details) {
			if ($details['status'] == Raid::STATUS_SYNCING) {
				# Provide a more detailed status message
				$code = Raid::STATUS_SYNCING;
				$text = RAID_LANG_SYNCING . ' (' . $details['dev'] . ') - ' . $details['recovery'] . '%';
			} else if ($details['status'] == Raid::STATUS_SYNC_PENDING) {
				# Provide a more detailed status message
				$code = Raid::STATUS_SYNC_PENDING;
				$text = RAID_LANG_SYNC_PENDING . ' (' . $details['dev'] . ')';
			} else if ($details['status'] == Raid::STATUS_DEGRADED) {
				# Provide a more detailed status message
				$code = Raid::STATUS_DEGRADED;
				$text = RAID_LANG_DEGRADED . ' (' . $details['dev'] . ' ' . RAID_LANG_FAILED . ')';
			}
		}
		$status .= "    <code>$code</code>\n"; 
		$status .= "    <msg>" . $text . "</msg>\n"; 
		$status .= "  </devicearray>\n"; 
	}
	return $status;
}

function GetHardwareStatus($controllers)
{
	foreach ($controllers as $controllerid => $controller) {
		foreach ($controller['units'] as $unitid => $unit) {
			$status .= "  <devicearray>\n";
			$status .= "    <name>$controllerid-$unitid</name>\n";
			$code = Raid::STATUS_CLEAN;
			$text = RAID_LANG_CLEAN;
			if ($myarray['status'] != Raid::STATUS_CLEAN) {
				$code = Raid::STATUS_DEGRADED;
				$text = RAID_LANG_DEGRADED;
			}
			foreach ($unit['devices'] as $id => $details) {
				if ($details['status'] == Raid::STATUS_SYNCING) {
					# Provide a more detailed status message
					$code = Raid::STATUS_SYNCING;
					$text = RAID_LANG_SYNCING . ' (' . RAID_LANG_DISK . ' ' . $id . ') - ' . $details['recovery'] . '%';
				} else if ($details['status'] == Raid::STATUS_SYNC_PENDING) {
					# Provide a more detailed status message
					$code = Raid::STATUS_SYNC_PENDING;
					$text = RAID_LANG_SYNC_PENDING . ' (' . RAID_LANG_DISK . ' ' . $id . ')';
				} else if ($details['status'] == Raid::STATUS_DEGRADED) {
					# Provide a more detailed status message
					$code = Raid::STATUS_DEGRADED;
					$text = RAID_LANG_DEGRADED . ' (' . RAID_LANG_DISK . ' ' . $id . ' ' . RAID_LANG_FAILED . ')';
				} else if ($details['status'] == Raid::STATUS_REMOVED) {
					$code = Raid::STATUS_REMOVED;
					$text = RAID_LANG_DEGRADED . ' (' . RAID_LANG_DISK . ' ' . $id . ' ' . RAID_LANG_REMOVED . ')';
				}
			}
		}
		$status .= "    <code>$code</code>\n"; 
		$status .= "    <msg>" . $text . "</msg>\n"; 
		$status .= "  </devicearray>\n"; 
	}
	return $status;
}
// vim: ts=4
?>
