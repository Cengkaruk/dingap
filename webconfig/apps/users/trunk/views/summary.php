<?php

/**
 * User manager view.
 *
 * @category   ClearOS
 * @package    Users
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/users/
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

$this->lang->load('users');

///////////////////////////////////////////////////////////////////////////////
// View modes
///////////////////////////////////////////////////////////////////////////////

if ($mode === 'view') {
    $read_only = TRUE;
    $anchors = array();
} else {
    $read_only = FALSE;
    $anchors = array(anchor_add('/app/users/add'));
}

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

foreach ($users as $username => $info) {

    if ($read_only) {
        $buttons = array(
            anchor_view('/app/users/view/' . $username),
        );
    } else {
        $buttons = array(
            anchor_edit('/app/users/edit/' . $username),
            anchor_delete('/app/users/delete/' . $username)
        );
    }

    ///////////////////////////////////////////////////////////////////////////////
    //
    // Some directory drivers separate first and last names into separate fields,
    // while others only support the full name (common name).  If the separate 
    // fields don't exist, fall back to the full name.
    //
    ///////////////////////////////////////////////////////////////////////////////

    if (! empty($info['core']['full_name']))
        $full_name = $info['core']['full_name'];
    else
        $full_name = $info['core']['first_name'] . ' ' . $info['core']['last_name'];

    $item['title'] = $username;
    $item['action'] = '/app/users/edit/' . $username;
    $item['anchors'] = button_set($buttons);
    $item['details'] = array(
        $username,
        $full_name,
    );

    $items[] = $item;
}

sort($items);

///////////////////////////////////////////////////////////////////////////////
// Summary table
///////////////////////////////////////////////////////////////////////////////

echo summary_table(
    lang('users_user_manager'),
    $anchors,
    $headers,
    $items
);
