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

use \clearos\apps\groups\Group_Engine as Group;

$this->lang->load('groups');

///////////////////////////////////////////////////////////////////////////////
// Headers
///////////////////////////////////////////////////////////////////////////////

$description_available = FALSE;

if ($description_available) {
    $headers = array(
        lang('groups_group'),
        lang('groups_description'),
    );
} else {
    $headers = array(
        lang('groups_group')
    );
}

///////////////////////////////////////////////////////////////////////////////
// Anchors 
///////////////////////////////////////////////////////////////////////////////

$normal_anchors = array(anchor_add('/app/groups/add'));
$plugin_anchors = array();
$windows_anchors = array();

///////////////////////////////////////////////////////////////////////////////
// Normal groups
///////////////////////////////////////////////////////////////////////////////

foreach ($groups as $group_name => $info) {

    if ($mode === 'view') {
        $normal_anchors = array();

        if ($description_available) {
            $buttons = array(
                anchor_custom('/app/groups/view_members/' . $group_name, lang('groups_view_members'), 'high'),
                anchor_view('/app/groups/view/' . $group_name, 'low'),
            );
        } else {
            $buttons = array(
                anchor_custom('/app/groups/view_members/' . $group_name, lang('groups_view_members'), 'high'),
            );
        }
    } else {
         if ($info['type'] === Group::TYPE_NORMAL) {
            $buttons = array(
                anchor_custom('/app/groups/edit_members/' . $group_name, lang('groups_edit_members'), 'high'),
                anchor_edit('/app/groups/edit/' . $group_name, 'low'),
                anchor_delete('/app/groups/delete/' . $group_name, 'low')
            );
        } else {
            $buttons = array(
                anchor_custom('/app/groups/edit_members/' . $group_name, lang('groups_edit_members'), 'high'),
                anchor_edit('/app/groups/edit/' . $group_name, 'low'),
            );
        }
    }

    $item['title'] = $group_name;
    $item['action'] = '/app/groups/edit/' . $group_name;
    $item['anchors'] = button_set($buttons);

    if ($description_available) {
        $item['details'] = array(
            $group_name,
            $info['description'],
        );
    } else {
        $item['details'] = array(
            $group_name,
            $info['description'],
        );
    }

    if ($info['type'] === Group::TYPE_NORMAL)
        $normal_items[] = $item;
    else if ($info['type'] === Group::TYPE_PLUGIN)
        $plugin_items[] = $item;
    else if ($info['type'] === Group::TYPE_WINDOWS)
        $windows_items[] = $item;
}

sort($normal_items);
sort($plugin_items);
sort($windows_items);

///////////////////////////////////////////////////////////////////////////////
// Windows groups
///////////////////////////////////////////////////////////////////////////////

echo summary_table(
    lang('groups_user_defined_groups'),
    $normal_anchors,
    $headers,
    $normal_items
);

///////////////////////////////////////////////////////////////////////////////
// Windows groups
///////////////////////////////////////////////////////////////////////////////

echo summary_table(
    lang('groups_windows_groups'),
    $windows_anchors,
    $headers,
    $windows_items
);
