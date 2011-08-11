<?php

/**
 * System time manager view.
 *
 * @category   ClearOS
 * @package    Date
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/date/
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
$this->lang->load('date');

///////////////////////////////////////////////////////////////////////////////
// Form open
///////////////////////////////////////////////////////////////////////////////

echo form_open('date');
echo form_header(lang('base_settings'));

///////////////////////////////////////////////////////////////////////////////
// Form fields and buttons
///////////////////////////////////////////////////////////////////////////////

echo field_input('date', $date, lang('date_date'), TRUE, array('id' => 'date'));
echo field_input('time', $time, lang('date_time'), TRUE, array('id' => 'time'));
echo field_simple_dropdown('time_zone', $time_zones, $time_zone, lang('date_time_zone'));

echo field_button_set(
    array( 
        form_submit_update('submit', 'high'),
        anchor_javascript('sync', lang('date_synchronize_now'), 'high')
    )
);

///////////////////////////////////////////////////////////////////////////////
// Form close
///////////////////////////////////////////////////////////////////////////////

echo form_footer();
echo form_close();

// FIXME: Aaron
echo "<div id='result_box'>";
echo infobox_highlight(lang('base_status'), lang('date_synchronization_changed_time_by_x_seconds:') . ' ' .  "<span id='result'></span>");
echo "</div>";
