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
// Form modes
///////////////////////////////////////////////////////////////////////////////

if ($mode === 'add') {
    $buttons = array(
        form_submit_add('update'),
        anchor_cancel('/app/web_access_control')
    );
} else {
    $buttons = array(
        form_submit_update('update'),
        anchor_cancel('/app/web_access_control')
    );
}

///////////////////////////////////////////////////////////////////////////////
// Form open
///////////////////////////////////////////////////////////////////////////////

if ($mode === 'add')
    echo form_open('web_access_control/add_edit_time');
else
    echo form_open('web_access_control/add_edit_time/' . $name);
echo form_header(lang('web_access_control_add_time'));

///////////////////////////////////////////////////////////////////////////////
// Form fields and buttons
///////////////////////////////////////////////////////////////////////////////

echo field_input('name', $name, lang('web_access_control_name'), ($mode === 'add' ? FALSE : TRUE));
echo field_simple_dropdown('start_time', $time_options, $start_time, lang('web_access_control_start_time'));
echo field_simple_dropdown('end_time', $time_options, $end_time, lang('web_access_control_end_time'));
echo field_multiselect_dropdown('dow[]', $day_of_week_options, $days, lang('web_access_control_day_of_week') . ' ' . lang('web_access_control_ctrl_click'), FALSE);

echo field_button_set($buttons);

///////////////////////////////////////////////////////////////////////////////
// Form close
///////////////////////////////////////////////////////////////////////////////

echo form_footer();
echo form_close();
