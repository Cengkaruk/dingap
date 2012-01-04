<?php

/**
 * Samba mode view.
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

if ($form_type === 'edit') {
    $read_only = FALSE;
    $buttons = array(
        form_submit_update('submit', 'high'),
        anchor_cancel('/app/samba/mode')
    );
} else {
    $read_only = TRUE;
    $buttons = array(
        anchor_edit('/app/samba/mode/edit')
    );
}

if ($read_only) {
    $mode_read_only = TRUE;
    $domain_read_only = TRUE;
}

///////////////////////////////////////////////////////////////////////////////
// Form
///////////////////////////////////////////////////////////////////////////////

if ($should_be_pdc_warning) {
    echo infobox_warning(lang('base_warning'), lang('samba_master_node_should_be_pdc'));
    return;
} else if ($should_be_bdc_warning) {
    echo infobox_warning(lang('base_warning'), lang('samba_slave_node_should_be_bdc'));
    return;
} else if ($unsupported_bdc_warning) {
    echo infobox_warning(lang('base_warning'), lang('samba_bdc_only_supported_on_slave_systems'));
    return;
}

echo form_open('samba/mode/edit');
echo form_header(lang('samba_mode'));

echo field_dropdown('mode', $modes, $mode, lang('samba_mode'), $mode_read_only);
echo field_input('domain', $domain, lang('samba_windows_domain'), $domain_read_only);

echo field_toggle_enable_disable('profiles', $profiles, lang('samba_roaming_profiles'), $read_only);
echo field_simple_dropdown('logon_drive', $logon_drives, $logon_drive, lang('samba_logon_drive'), $read_only);
echo field_input('logon_script', $logon_script, lang('samba_logon_script'), $read_only);

echo field_button_set($buttons);

echo form_footer();
echo form_close();
