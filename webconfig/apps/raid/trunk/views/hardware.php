<?php

/**
 * Raid manager view.
 *
 * @category   ClearOS
 * @package    Raid
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/raid/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.  
//  
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// Load dependencies
///////////////////////////////////////////////////////////////////////////////

use \clearos\apps\base\Storage_Device as Storage_Device;

clearos_load_library('base/Storage_Device');

$this->lang->load('base');
$this->lang->load('raid');

///////////////////////////////////////////////////////////////////////////////
// Headers
///////////////////////////////////////////////////////////////////////////////

$headers = array(
    lang('raid_controller'),
    lang('raid_unit'),
    lang('raid_size'),
    lang('raid_device'),
    lang('raid_mount'),
    lang('raid_level'),
    lang('raid_status')
);

///////////////////////////////////////////////////////////////////////////////
// Row Data
///////////////////////////////////////////////////////////////////////////////

$help = NULL;
foreach ($controllers as $controllerid => $controller) {
    $status = lang('raid_clean');
    $action = '&#160;';
    $detail_buttons = '';
	foreach ($controller['units'] as $unitid => $unit) {
        $status = lang('raid_clean');
        $mount = $raid_hardware->get_mapping($unitid);
        if ($unit['status'] != Raid::STATUS_CLEAN) {
            $status = lang('raid_degraded');
            if ($hardware_raid->get_interactive())
                anchor_custom(lang('raid_repair'), '/app/raid/hardware/repair/' . $unitid);
        }

        foreach ($unit['devices'] as $id => $details) {
            if ($details['status'] == $raid_hardware::STATUS_SYNCING) {
                // Provide a more detailed status message
                $status = lang('raid_syncing') . ' (' . lang('raid_disk') . ' ' . $id . ') - ' . $details['recovery'] . '%';
            } else if ($details['status'] == $raid_hardware::STATUS_SYNC_PENDING) {
                // Provide a more detailed status message
                $status = lang('raid_sync_pending') . ' (' . lang('raid_disk') . ' ' . $id . ')';
            } else if ($details['status'] == $raid_hardware::STATUS_DEGRADED) {
                // Provide a more detailed status message
                $status = lang('raid_degraded') . ' (' . lang('raid_disk') . ' ' . $id . ' ' . lang('raid_failed') . ')';
                // Check what action applies
                if ($raid_hardware->get_interactive())
                    $detail_buttons = button_set(
                        array(
                            anchor_delete('/app/raid/hardware/remove/' . $dev)
                        )
                    );
            } else if ($details['status'] == $raid_hardware::STATUS_REMOVED) {
                $status = lang('raid_degraded') . ' (' . lang('raid_disk') . ' ' . $id . ' ' . lang('raid_removed') . ')';
            }
        }

        $row['title'] = $dev;
        $row['action'] = '/app/raid/FIXME/';
        $row['anchors'] = $detail_buttons;
        $row['details'] = array (
            $controller['model'] . ', ' . lang('raid_slot') . ' ' . $controllerid,
            lang('raid_logical_disk') . ' ' . $unitid,
            $raid_hardware->get_formatted_bytes($unit['size'], 1),
            $mount,
            $unit['level'],
            $status
        );
        $rows[] = $row;
    }
}

///////////////////////////////////////////////////////////////////////////////
// Sumary table
///////////////////////////////////////////////////////////////////////////////

echo summary_table(
    lang('raid_hardware'),
    NULL,
    $headers,
    $rows
);
