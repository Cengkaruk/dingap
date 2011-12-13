<?php

/**
 * Content filter policy controller.
 *
 * @category   Apps
 * @package    Content_Filter
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/content_filter/
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
$this->lang->load('content_filter');

///////////////////////////////////////////////////////////////////////////////
// Form handler
///////////////////////////////////////////////////////////////////////////////

if ($form_type === 'edit') {
    $form = 'content_filter/policy/edit/' . $policy;
    $buttons = array(
        form_submit_update('submit'),
        anchor_cancel('/app/content_filter')
    );
} else {
    $form = 'content_filter/policy/add';
    $buttons = array(
        form_submit_add('submit'),
        anchor_cancel('/app/content_filter')
    );
}

if (count($groups) == 0) {
    // TODO: review widget 
    echo infobox_warning(lang('base_warning'), 
        lang('content_filter_no_system_groups_warning') . '<br><br>' . 
        anchor_custom('/app/content_filter', lang('base_back')) . ' ' .
        anchor_custom('/app/groups', lang('content_filter_add_system_group'))
    );
    return;
}

///////////////////////////////////////////////////////////////////////////////
// Form
///////////////////////////////////////////////////////////////////////////////

echo form_open($form);
echo form_header(lang('content_filter_policy'));

echo field_input('policy_name', $policy_name, lang('content_filter_policy_name'));
echo field_simple_dropdown('group', $groups, $group, lang('base_group'), $read_only);
echo field_button_set($buttons);

echo form_footer();
echo form_close();
