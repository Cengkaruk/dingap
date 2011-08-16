<?php

/**
 * Web Access Control overview.
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
    lang('web_access_control_type'),
    lang('web_access_control_time_of_day'),
    lang('web_access_control_priority')
);

///////////////////////////////////////////////////////////////////////////////
// Anchors 
///////////////////////////////////////////////////////////////////////////////

$anchors = array(anchor_add('/app/web_access_control/add'));

///////////////////////////////////////////////////////////////////////////////
// Rules
///////////////////////////////////////////////////////////////////////////////

foreach ($acls as $acl) {
    $item['title'] = $acl['name'];
    $item['action'] = '/app/web_access_control/delete/' . $acl['name'];
    $item['anchors'] = button_set(
        array(
            $state_anchor('/app/web_access_control/' . $acl['line'], 'high'),
            anchor_custom('/app/web_access_control/up/' . $acl['line'], '+', 'low'),
            anchor_custom('/app/web_access_control/down/' . $acl['line'], '-', 'low'),
            anchor_delete('/app/web_access_control/delete/' . $acl['line'], 'low')
        )
    );
    if ($acl['logic'])
        $time = lang('web_access_control_within') . ' ' . $acl['time'];
    else
        $time = lang('web_access_control_outside') . ' ' . $acl['time'];
    $item['details'] = array(
        $acl['name'],
        $acl['type'],
        $time,
        $acl['priority']
    );

    $items[] = $item;
}

///////////////////////////////////////////////////////////////////////////////
// Summary table
///////////////////////////////////////////////////////////////////////////////

sort($items);

echo summary_table(
    lang('web_access_control_list'),
    $anchors,
    $headers,
    $items
);
