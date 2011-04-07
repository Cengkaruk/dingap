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

$this->lang->load('groups');

///////////////////////////////////////////////////////////////////////////////
// Headers
///////////////////////////////////////////////////////////////////////////////

$headers = array(
	lang('groups_group'),
	lang('groups_description'),
);

///////////////////////////////////////////////////////////////////////////////
// Anchors 
///////////////////////////////////////////////////////////////////////////////

$anchors = array();

$view_only = TRUE;

///////////////////////////////////////////////////////////////////////////////
// Normal groups
///////////////////////////////////////////////////////////////////////////////

foreach ($normal_groups as $group_name => $info) {

    if ($view_only) {
        $buttons = array(
            anchor_view('/app/groups/view/' . $group_name),
        );
    } else {
        $buttons = array(
            anchor_edit('/app/groups/edit/' . $group_name),
            anchor_delete('/app/groups/delete/' . $group_name)
        );
    }

	$item['title'] = $group_name;
	$item['action'] = '/app/groups/edit/' . $group_name;
	$item['anchors'] = button_set($buttons);
	$item['details'] = array(
		$group_name,
		$info['description'],
	);

	$items[] = $item;
}

sort($items);

///////////////////////////////////////////////////////////////////////////////
// Windows groups
///////////////////////////////////////////////////////////////////////////////

// FIXME: translate

echo summary_table(
	'User-defined Groups',
	$anchors,
	$headers,
	$items
);

///////////////////////////////////////////////////////////////////////////////
// Plugin groups
///////////////////////////////////////////////////////////////////////////////

// FIXME: translate

echo summary_table(
    'Plugin Groups',
	$anchors,
	$headers,
	$items
);

///////////////////////////////////////////////////////////////////////////////
// Windows groups
///////////////////////////////////////////////////////////////////////////////

// FIXME: translate

echo summary_table(
    'Windows Groups',
	$anchors,
	$headers,
	$items
);
