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
// Form handler
///////////////////////////////////////////////////////////////////////////////

if ($form_type === 'add') {
    $read_only = FALSE;
    $action = 'web_access_control/acl/add';
    $buttons = array(
        form_submit_add('update'),
        anchor_cancel('/app/web_access_control')
    );
} else {
    $read_only = TRUE;
    $action = 'web_access_control/acl/edit/' . $name;
    $buttons = array(
        form_submit_update('update'),
        anchor_cancel('/app/web_access_control')
    );
}

///////////////////////////////////////////////////////////////////////////////
// Form
///////////////////////////////////////////////////////////////////////////////

echo form_open($action);
echo form_header(lang('web_access_control_time_rule'));

echo field_input('name', $name, lang('web_access_control_name'), $read_only);
echo field_dropdown('type', $type_options, $type, lang('web_access_control_type'));
echo field_dropdown('time', $time_options, $time, lang('web_access_control_time_of_day'));
echo field_dropdown('restrict', $restrict_options, $restrict, lang('web_access_control_time_restriction'));
echo field_dropdown('ident', $ident_options, $ident, lang('web_access_control_id_method'));
echo field_simple_dropdown('ident_group', $groups, $ident_group, lang('web_access_control_apply_group'));
echo field_textarea('ident_ip', $ident_ip, lang('web_access_control_apply_ip'), FALSE);
echo field_textarea('ident_mac', $ident_mac, lang('web_access_control_apply_mac'), FALSE);

echo field_button_set($buttons);

echo form_footer();
echo form_close();
