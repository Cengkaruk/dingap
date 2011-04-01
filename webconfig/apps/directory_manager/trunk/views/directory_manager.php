<?php

/**
 * Directorry manager view.
 *
 * @category   ClearOS
 * @package    Directory_Manager
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/directory_manager/
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
$this->lang->load('directory_manager');

///////////////////////////////////////////////////////////////////////////////
// Common mode field
///////////////////////////////////////////////////////////////////////////////

$mode = 'master';
$read_only = FALSE;

/*
if (empty($mode)) {
    $read_only = FALSE;
} else {
    $read_only = TRUE;
}
*/

$ids['input'] = 'mode';

echo form_open('directory_manager');
echo form_header(lang('directory_mode'));

echo form_fieldset(lang('base_general_settings'));
echo field_dropdown('mode', $modes, $mode, lang('directory_mode'), $read_only, $ids);
echo form_fieldset_close();

///////////////////////////////////////////////////////////////////////////////
// OpenLDAP Master
///////////////////////////////////////////////////////////////////////////////

echo "<div id='master' class='mode_form'>";

echo form_fieldset(lang('directory_master'));
echo field_input('domain', $domain, lang('directory_base_domain'));
echo field_dropdown('publish_policy', $publish_policies, $publish_policy, lang('directory_publish_policy'));
echo form_fieldset_close();

echo form_fieldset(lang('directory_ldap_information'));
echo field_view('base_dn', $base_dn, lang('directory_base_dn'));
echo field_view('bind_dn', $bind_dn, lang('directory_bind_dn'));
echo field_view('bind_password', $bind_password, lang('directory_bind_password'));
echo form_fieldset_close();

echo "</div>";

///////////////////////////////////////////////////////////////////////////////
// OpenLDAP Slave
///////////////////////////////////////////////////////////////////////////////

echo "<div id='slave' class='mode_form'>";

echo form_fieldset(lang('directory_slave'));
echo field_input('master_hostname', $master_hostname, lang('directory_master_directory_hostname'));
echo field_input('master_password', $master_password, lang('directory_master_directory_password'));
echo field_dropdown('publish_policy', $publish_policies, $publish_policy, lang('directory_publish_policy'));
echo form_fieldset_close();

echo form_fieldset(lang('directory_ldap_information'));
echo field_view('base_dn', $base_dn, lang('directory_base_dn'));
echo field_view('bind_dn', $bind_dn, lang('directory_bind_dn'));
echo form_fieldset_close();

echo "</div>";

///////////////////////////////////////////////////////////////////////////////
// Active Directory
///////////////////////////////////////////////////////////////////////////////

echo "<div id='ad' class='mode_form'>";

echo form_fieldset(lang('directory_directory_settings'));
echo field_input('realm', $realm, lang('directory_realm'));
echo form_fieldset_close();

echo "</div>";

///////////////////////////////////////////////////////////////////////////////
// Common submit button
///////////////////////////////////////////////////////////////////////////////

echo button_set(
    array( form_submit_update('submit', 'high') )
);

///////////////////////////////////////////////////////////////////////////////
// Close form
///////////////////////////////////////////////////////////////////////////////

echo form_footer();
echo form_close();
