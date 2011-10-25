<?php

/**
 * OpenSSH server settings view.
 *
 * @category   Apps
 * @package    OpenSSH
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/ssh_server/
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
$this->lang->load('network');
$this->lang->load('ssh_server');

///////////////////////////////////////////////////////////////////////////////
// Form
///////////////////////////////////////////////////////////////////////////////

echo form_open('ssh_server/settings');
echo form_header(lang('base_settings'));

echo field_input('port', $port, lang('network_port'));
echo field_toggle_enable_disable('password_authentication', $password_authentication, lang('ssh_server_allow_passwords'));
echo field_dropdown('permit_root_login', $permit_root_logins, $permit_root_login, lang('ssh_server_allow_root_login'));

echo field_button_set(
    array(form_submit_update('submit', 'high'))
);

echo form_footer();
echo form_close();
