<?php

/**
 * Samba initialize view.
 *
 * @category   ClearOS
 * @package    Samba
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/samba/
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
$this->lang->load('samba');

///////////////////////////////////////////////////////////////////////////////
// Form handler
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// Form
///////////////////////////////////////////////////////////////////////////////

echo form_open('samba/status');
echo form_header(lang('base_initialize'));

echo fieldset_header(lang('samba_windows_network'));
echo field_input('netbios', $netbios, lang('samba_server_name'));
echo field_input('domain', $domain, lang('samba_windows_domain'));
echo fieldset_footer();

echo fieldset_header(lang('samba_set_administrator_password') . ' - winadmin');
echo field_password('password', '', lang('base_password'));
echo field_password('verify', '', lang('base_verify'));
echo fieldset_footer();

echo field_button_set(
    array(form_submit_custom('submit', lang('base_initialize')))
);

echo form_footer();
echo form_close();
