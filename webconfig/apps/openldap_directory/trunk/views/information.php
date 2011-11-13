<?php

/**
 * Direcotry server information view.
 *
 * @category   ClearOS
 * @package    OpenLDAP_Directory
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/openldap_directory/
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
$this->lang->load('openldap_directory');

///////////////////////////////////////////////////////////////////////////////
// Main form
///////////////////////////////////////////////////////////////////////////////

echo "<div id='directory_information'>";

echo form_open('openldap_directory');
echo form_header(lang('openldap_directory_directory_information'));

echo fieldset_header(lang('openldap_directory_capabilities'));
echo field_view(lang('openldap_directory_mode'), '', 'mode_text');
echo fieldset_footer();

echo fieldset_header(lang('openldap_directory_connection_information'));
echo field_view(lang('openldap_directory_base_dn'), '', 'base_dn');
echo field_view(lang('openldap_directory_bind_dn'), '', 'bind_dn');
echo field_view(lang('openldap_directory_bind_password'), '', 'bind_password');
echo fieldset_footer();

echo fieldset_header(lang('openldap_directory_containers'));
echo field_view(lang('openldap_directory_users'), '', 'users_container');
echo field_view(lang('openldap_directory_groups'), '', 'groups_container');
echo field_view(lang('openldap_directory_computers'), '', 'computers_container');
echo fieldset_footer();

echo form_footer();
echo form_close();

echo "</div>";
