<?php

/**
 * Directory server settings view.
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

use \clearos\apps\openldap\LDAP_Driver as LDAP;

///////////////////////////////////////////////////////////////////////////////
// Load dependencies
///////////////////////////////////////////////////////////////////////////////

$this->lang->load('base');
$this->lang->load('openldap_directory');

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
        anchor_cancel('/app/openldap_directory')
    );
} else {
    $read_only = TRUE;
    $buttons = array(
        anchor_edit('/app/openldap_directory/settings/edit')
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
// Status boxes
///////////////////////////////////////////////////////////////////////////////

echo "<div id='infoboxes' style='display:none;'>";

echo infobox_highlight(lang(
    'openldap_directory_directory_status'),
    "<div id='initializing_status'></div>",
    array('id' => 'initializing_box', 'hidden' => TRUE)
);

echo "</div>";

///////////////////////////////////////////////////////////////////////////////
// Main form
///////////////////////////////////////////////////////////////////////////////

echo "<div id='directory_configuration' style='display:none;'>";

echo "<input type='hidden' id='validated' value='$validated'>";
echo "validated $validated";

echo form_open('openldap_directory/settings/edit');
echo form_header(lang('base_settings'));

echo field_view(lang('openldap_directory_mode'), $mode_text, 'mode_settings');

if (($mode === LDAP::MODE_MASTER) || ($mode === LDAP::MODE_STANDALONE)) {
    echo field_input('domain', $domain, lang('openldap_directory_base_domain'), $read_only);
} else {
    echo field_input('master_hostname', $master, lang('openldap_directory_master_hostname'), $read_only);

    if ($form_type === 'edit')
        echo field_input('master_password', $master, lang('openldap_directory_master_password'), $read_only);
}

echo field_dropdown('policy', $policies, $policy, lang('openldap_directory_publish_policy'), $read_only);

echo field_button_set($buttons);

echo form_footer();
echo form_close();

echo "</div>";

