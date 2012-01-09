<?php

/**
 * Password policies view.
 *
 * @category   ClearOS
 * @package    Password_Policies
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/password_policies/
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

$this->lang->load('password_policies');
$this->lang->load('base');

///////////////////////////////////////////////////////////////////////////////
// Form handler
///////////////////////////////////////////////////////////////////////////////

if ($form_mode === 'edit') {
    $read_only = FALSE;
    $buttons = array(
        form_submit_update('submit'),
        anchor_cancel('/app/password_policies')
    );
} else {
    $read_only = TRUE;
    $buttons = array(
        anchor_edit('/app/password_policies/edit')
    );
}

///////////////////////////////////////////////////////////////////////////////
// Form
///////////////////////////////////////////////////////////////////////////////

echo form_open('password_policies');
echo form_header(lang('base_settings'));

echo field_simple_dropdown('minimum_length', $minimum_lengths, $minimum_length, lang('password_policies_minimum_password_length'), $read_only);
echo field_dropdown('minimum_age', $minimum_ages, $minimum_age, lang('password_policies_minimum_password_age'), $read_only);
echo field_dropdown('maximum_age', $maximum_ages, $maximum_age, lang('password_policies_maximum_password_age'), $read_only);
echo field_dropdown('history_size', $history_sizes, $history_size, lang('password_policies_history_size'), $read_only);
echo field_toggle_enable_disable('lockout', $lockout, lang('password_policies_account_lockout'), $read_only);

echo field_button_set($buttons);

echo form_footer();
echo form_close();
