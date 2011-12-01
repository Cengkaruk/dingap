<?php

/**
 * Web Access Control time summary.
 *
 * @category   Apps
 * @package    Web_Access_Control
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearcenter.com/support/documentation/clearos/web_access_control/
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

$this->lang->load('base');
$this->lang->load('web_access_control');

///////////////////////////////////////////////////////////////////////////////
// Headers
///////////////////////////////////////////////////////////////////////////////

$headers = array(
    lang('web_access_control_name'),
    lang('web_access_control_day_of_week'),
    lang('web_access_control_start_time'),
    lang('web_access_control_end_time')
);

///////////////////////////////////////////////////////////////////////////////
// Anchors 
///////////////////////////////////////////////////////////////////////////////

$anchors = array(anchor_add('/app/web_access_control/time/add'));

///////////////////////////////////////////////////////////////////////////////
// Rules
///////////////////////////////////////////////////////////////////////////////

foreach ($time_definitions as $time) {
    $item['title'] = $time['name'];
    $item['action'] = '/app/web_access_control/time/edit/' . $time['name'];
    $item['anchors'] = button_set(
        array(
            anchor_edit('/app/web_access_control/time/edit/' . $time['name']),
            anchor_delete('/app/web_access_control/time/delete/' . $time['name'])
        )
    );
    $dow = '';
    foreach ($day_of_week_options as $key => $day) {
        if (in_array($key, $time['dow']))
            $dow .= substr($day, 0, 1);
        else
            $dow .= '-';
    }
    $item['details'] = array(
        $time['name'],
        $dow,
        $time['start'],
        $time['end']
    );

    $items[] = $item;
}

///////////////////////////////////////////////////////////////////////////////
// Summary table
///////////////////////////////////////////////////////////////////////////////

sort($items);

echo summary_table(
    lang('web_access_control_time_definitions'),
    $anchors,
    $headers,
    $items
);
