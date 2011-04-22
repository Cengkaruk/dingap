<?php

/**
 * OpenLDAP directory view.
 *
 * @category   ClearOS
 * @package    OpenLDAP_Accounts
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/openldap_accounts/
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
$this->lang->load('openldap_accounts');

///////////////////////////////////////////////////////////////////////////////
// Form open
///////////////////////////////////////////////////////////////////////////////

echo form_open('directory_manager');
echo form_header(lang('directory_manager_mode'));

echo form_fieldset(lang('base_general_settings'));
echo field_input('domain', $domain, lang('directory_manager_base_domain'));
echo field_dropdown('anonymous', $publish_policies, $publish_policy, 'Anonymous Access');
echo form_fieldset_close();

echo form_fieldset(lang('directory_manager_extensions'));
echo field_view('example', $base_dn, 'Google Apps');
echo field_view('example1', $base_dn, 'Zarafa');
echo field_view('example2', $base_dn, 'Contacts');
echo field_view('example3', $base_dn, 'RADIUS');
echo form_fieldset_close();

echo form_fieldset(lang('directory_manager_ldap_information'));
echo field_dropdown('mode', $modes, $mode, lang('directory_manager_mode'), TRUE);
echo field_view('base_dn', $base_dn, lang('directory_manager_base_dn'));
echo field_view('bind_dn', $bind_dn, lang('directory_manager_bind_dn'));
echo field_view('bind_password', $bind_password, lang('directory_manager_bind_password'));
echo form_fieldset_close();


echo button_set(
    array( form_submit_update('submit', 'high') )
);

///////////////////////////////////////////////////////////////////////////////
// Form close
///////////////////////////////////////////////////////////////////////////////

echo form_footer();
echo form_close();
