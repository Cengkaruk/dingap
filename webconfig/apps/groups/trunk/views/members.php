<?php

/**
 * Group item view.
 *
 * @category   Apps
 * @package    Groups
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/groups/
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

$this->lang->load('groups');
$this->lang->load('users');

///////////////////////////////////////////////////////////////////////////////
// Buttons
///////////////////////////////////////////////////////////////////////////////

if ($mode === 'view')
    $buttons = array(anchor_cancel('/app/groups'));
else
    $buttons = array(anchor_cancel('/app/groups'), form_submit_update('submit', 'high'));

///////////////////////////////////////////////////////////////////////////////
// Headers
///////////////////////////////////////////////////////////////////////////////

$headers = array(
    lang('users_username'),
    lang('users_full_name'),
);

///////////////////////////////////////////////////////////////////////////////
// Items
///////////////////////////////////////////////////////////////////////////////

foreach ($users as $username => $details) {
    $item['title'] = $username;
    $item['name'] = 'users[' . $username . ']';
    $item['state'] = (in_array($username, $group_info['members'])) ? TRUE : FALSE;
    $item['details'] = array(
        $username,
        $details['core']['full_name']
    );

    $items[] = $item;
}

///////////////////////////////////////////////////////////////////////////////
// List table
///////////////////////////////////////////////////////////////////////////////

echo form_open('/groups/edit_members/' . $group_info['group_name']);

// FIXME: implement read_only in theme
echo list_table(
    lang('groups_group_members') . ' - ' .  $group_info['group_name'],
    $buttons,
    $headers,
    $items,
    array('read_only' => TRUE)
);

echo form_close();
