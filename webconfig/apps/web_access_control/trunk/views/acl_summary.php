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

$anchors = array(anchor_add('/app/web_access_control/add_edit'));

///////////////////////////////////////////////////////////////////////////////
// Rules
///////////////////////////////////////////////////////////////////////////////

$counter = 0;
foreach ($acls as $acl) {
    $item['title'] = $acl['name'];
    $item['action'] = '/app/web_access_control/acl_summary/delete/' . $acl['name'];
    $item['anchors'] = button_set(
        array(
            anchor_edit('/app/web_access_control/add_edit/' . $acl['name']),
            anchor_delete('/app/web_access_control/acl_summary/delete/' . $acl['name'])
        )
    );
    $priority_buttons = array();
    if ($counter > 0)
        $priority_buttons[] = anchor_custom('/app/web_access_control/priority/' . $acl['name'] . '/1', '+');
    if ($counter < count($acls) - 1)
        $priority_buttons[] = anchor_custom('/app/web_access_control/priority/' . $acl['name'] . '/0', '-');

    if (empty($priority_buttons))
        $priority = '---';
    else
        $priority = button_set($priority_buttons);

    if ($acl['logic'])
        $time = lang('web_access_control_within') . ' ' . $acl['time'];
    else
        $time = lang('web_access_control_outside') . ' ' . $acl['time'];
    $item['details'] = array(
        $acl['name'],
        ucfirst($acl['type']),
        $time,
        $priority
    );

    $items[] = $item;
    $counter++;
}

///////////////////////////////////////////////////////////////////////////////
// Summary table
///////////////////////////////////////////////////////////////////////////////

echo summary_table(
    lang('web_access_control_list'),
    $anchors,
    $headers,
    $items,
    array ('sort' => FALSE)
);
