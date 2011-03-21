<?php

/**
 * SMTP general settings view.
 *
 * @category   ClearOS
 * @package    SMTP
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/smtp/
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

$this->lang->load('smtp');
$this->lang->load('base');

///////////////////////////////////////////////////////////////////////////////
// Form handler
///////////////////////////////////////////////////////////////////////////////

if ($mode === 'edit') {
    $read_only = FALSE;
    $buttons = array(
        form_submit_update('submit'),
        anchor_cancel('/app/smtp')
    );
} else {
    $read_only = TRUE;
    $buttons = array(anchor_edit('/app/smtp/general'));
}

$max_message_sizes = array();
$max_message_sizes['1024000'] = '1 ' . lang('base_megabytes');
$max_message_sizes['2048000'] = '2 ' . lang('base_megabytes');
$max_message_sizes['5120000'] = '5 ' . lang('base_megabytes');
$max_message_sizes['10240000'] = '10 ' . lang('base_megabytes');
$max_message_sizes['20480000'] = '20 ' . lang('base_megabytes');
$max_message_sizes['30720000'] = '30 ' . lang('base_megabytes');
$max_message_sizes['40960000'] = '40 ' . lang('base_megabytes');
$max_message_sizes['51200000'] = '50 ' . lang('base_megabytes');
$max_message_sizes['102400000'] = '100 ' . lang('base_megabytes');

///////////////////////////////////////////////////////////////////////////////
// Form open
///////////////////////////////////////////////////////////////////////////////

echo form_open('smtp');
echo form_header(lang('base_general_settings'));

///////////////////////////////////////////////////////////////////////////////
// Form fields and buttons
///////////////////////////////////////////////////////////////////////////////

echo form_fieldset(lang('base_general_settings'));
echo field_input('domain', $domain, lang('smtp_domain'), TRUE);
echo field_input('hostname', $domain, lang('smtp_hostname'), $read_only);
echo field_toggle_enable_disable('smtp_authentication', $smtp_authentication, lang('smtp_smtp_authentication'), $read_only);
echo field_dropdown('max_message_size', $max_message_sizes, $max_message_size, lang('smtp_maximum_message_size'), $read_only);
// FIXME: implement user list
echo field_simple_dropdown('catch_all', $catch_alls, $catch_all, lang('smtp_catch_all'), $read_only);
echo form_fieldset_close();

echo button_set($buttons);

///////////////////////////////////////////////////////////////////////////////
// Form close
///////////////////////////////////////////////////////////////////////////////

echo form_footer();
echo form_close();
