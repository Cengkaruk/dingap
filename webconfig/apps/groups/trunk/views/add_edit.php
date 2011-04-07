<?php

/**
 * Group manager view.
 *
 * @category   ClearOS
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

$this->lang->load('base');
$this->lang->load('groups');

///////////////////////////////////////////////////////////////////////////////
// Form modes
///////////////////////////////////////////////////////////////////////////////

if ($form_type === 'edit') {
    $read_only = FALSE;
    $group_name_read_only = TRUE;

	$form_path = '/groups/edit';
	$buttons = array(
		form_submit_update('submit'),
		anchor_cancel('/app/groups/'),
		anchor_delete('/app/groups/delete/' . $group_name)
	);
} else if ($form_type === 'view') {
    $read_only = TRUE;
    $group_name_read_only = TRUE;

	$form_path = '/groups/view';
	$buttons = array(
		anchor_cancel('/app/groups/')
	);
} else {
    $read_only = FALSE;
    $group_name_read_only = FALSE;

	$form_path = '/groups/add';
	$buttons = array(
		form_submit_add('submit'),
		anchor_cancel('/app/groups/')
	);
}

///////////////////////////////////////////////////////////////////////////////
// Form open
///////////////////////////////////////////////////////////////////////////////

echo form_open($form_path . '/' . $group_name);
echo form_header(lang('users_user'));

echo "<pre>";
print_r($group_info);
echo "</pre>";

///////////////////////////////////////////////////////////////////////////////
// Form close
///////////////////////////////////////////////////////////////////////////////

echo form_footer();
echo form_close();
