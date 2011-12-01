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
    $action = 'web_access_control/time/add';
    $read_only = FALSE;
    $buttons = array(
        form_submit_add('update'),
        anchor_cancel('/app/web_access_control')
    );
} else {
    $action = 'web_access_control/time/edit/' . $name;
    $read_only = TRUE;
    $buttons = array(
        form_submit_update('update'),
        anchor_cancel('/app/web_access_control')
    );
}

///////////////////////////////////////////////////////////////////////////////
// Form
///////////////////////////////////////////////////////////////////////////////

echo form_open($action);
echo form_header(lang('web_access_control_add_time'));

echo field_input('name', $name, lang('web_access_control_name'), $read_only);
echo field_simple_dropdown('start_time', $time_options, $start_time, lang('web_access_control_start_time'));
echo field_simple_dropdown('end_time', $time_options, $end_time, lang('web_access_control_end_time'));
echo field_multiselect_dropdown(
    'dow[]',
    $day_of_week_options,
    $days,
    lang('web_access_control_day_of_week') . ' ' . lang('web_access_control_ctrl_click'),
    FALSE
);

echo field_button_set($buttons);

echo form_footer();
echo form_close();
