<?php

/**
 * PPTP server view.
 *
 * @category   ClearOS
 * @package    PPTPd
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/pptpd/
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

use \clearos\apps\openldap\LDAP_Driver as LDAP;

///////////////////////////////////////////////////////////////////////////////
// Load dependencies
///////////////////////////////////////////////////////////////////////////////

$this->lang->load('base');
$this->lang->load('directory_server');

///////////////////////////////////////////////////////////////////////////////
// Form handler
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// Form handler
///////////////////////////////////////////////////////////////////////////////

if ($form_type === 'edit') {
    $read_only = FALSE;
    $buttons = array(
        form_submit_update('submit'),
        anchor_cancel('/app/directory_server')
    );
} else {
    $read_only = TRUE;
    $buttons = array(
        anchor_edit('/app/directory_server/settings/edit')
    );
}

/*
if ($status == 'unset') {
    if ($mode === LDAP::MODE_SLAVE)
        $button = form_submit_custom('initialize_slave', lang('base_initialize'), 'high');
    else
        $button = form_submit_custom('initialize', lang('base_initialize'), 'high');
} else {
    $button = anchor_javascript('update_directory', lang('base_update'));
}

$domain_read_only = ($mode === LDAP::MODE_SLAVE) ? TRUE : FALSE;
$mode = LDAP::MODE_SLAVE;
*/

///////////////////////////////////////////////////////////////////////////////
// Main form
///////////////////////////////////////////////////////////////////////////////

echo form_open('directory_server');
echo form_header(lang('base_settings'));

echo field_view(lang('directory_server_mode'), $mode_text);

if (($mode === LDAP::MODE_MASTER) || ($mode === LDAP::MODE_STANDALONE)) {
    echo field_input('domain', $domain, lang('directory_server_base_domain'), $read_only);
} else {
    echo field_input('master_hostname', $master, lang('directory_server_master_hostname'), $read_only);

    if ($form_type === 'edit')
        echo field_input('master_password', $master, lang('directory_server_master_password'), $read_only);
}

echo field_button_set($buttons);

echo form_footer();
echo form_close();
