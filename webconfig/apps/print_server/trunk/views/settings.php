<?php

/**
 * MySQL view.
 *
 * @category   ClearOS
 * @package    MySQL
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/mysql/
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
$this->lang->load('mysql');

///////////////////////////////////////////////////////////////////////////////
// Show warning if password is not set
///////////////////////////////////////////////////////////////////////////////

echo "<div id='mysql_not_running' style='display:none;'>";
echo infobox_warning(lang('base_warning'), lang('mysql_management_tool_not_accessible'));
echo "</div>";

///////////////////////////////////////////////////////////////////////////////
// Password not set
///////////////////////////////////////////////////////////////////////////////

echo "<div id='mysql_no_password' style='display:none;'>";
echo infobox_warning(lang('base_warning'), lang('mysql_lang_please_set_a_database_password'));

echo form_open('mysql');
echo form_header(lang('base_password'));

echo field_password('new_password', '', lang('base_password'));
echo field_password('new_verify', '', lang('base_verify'));

echo field_button_set(
    array(form_submit_custom('submit_new', lang('mysql_set_password'), 'high'))
);

echo form_footer();
echo form_close();

echo "</div>";

///////////////////////////////////////////////////////////////////////////////
// Password set
///////////////////////////////////////////////////////////////////////////////

echo "<div id='mysql_password_ok' style='display:none;'>";

echo infobox_highlight(
    lang('mysql_management_tool'),
    lang('mysql_management_tool_help') . '<br><br>' .
    "<p align='center'>" .  
    anchor_custom('/mysql', lang('mysql_go_to_management_tool'), 'high', array('target' => '_blank')) . 
    "</p>"
);

echo form_open('mysql');
echo form_header(lang('base_password'));

echo field_password('current_password', '', lang('base_current_password'));
echo field_password('password', '', lang('base_password'));
echo field_password('verify', '', lang('base_verify'));

echo field_button_set(
    array(form_submit_update('submit', 'high'))
);

echo form_footer();
echo form_close();

echo "</div>";
