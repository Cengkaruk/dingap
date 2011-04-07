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
// Headers
///////////////////////////////////////////////////////////////////////////////

$headers = array(
	lang('users_username'),
	lang('users_fullname'),
	lang('users_apps')
);

///////////////////////////////////////////////////////////////////////////////
// Anchors 
///////////////////////////////////////////////////////////////////////////////

$anchors = array();

$view_only = TRUE;

///////////////////////////////////////////////////////////////////////////////
// Items
///////////////////////////////////////////////////////////////////////////////

foreach ($users as $username => $info) {

    if ($view_only) {
        $buttons = array(
            anchor_view('/app/users/view/' . $username),
        );
    } else {
        $buttons = array(
            anchor_edit('/app/users/edit/' . $username),
            anchor_delete('/app/users/delete/' . $username)
        );
    }

	$item['title'] = $username;
	$item['action'] = '/app/users/edit/' . $username;
	$item['anchors'] = button_set($buttons);
	$item['details'] = array(
		$username,
		$info['display_name'],
		$username,
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
