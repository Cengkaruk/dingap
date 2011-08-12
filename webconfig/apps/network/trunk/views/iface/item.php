<?php

/**
 * Network interface settings view.
 *
 * @category   ClearOS
 * @package    Network
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network/
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

$this->load->language('base');
$this->load->language('network');

///////////////////////////////////////////////////////////////////////////////
// Form modes
///////////////////////////////////////////////////////////////////////////////

if ($form_type === 'edit') {
    $read_only = TRUE;
    $form_path = '/network/iface/edit/' . $interface;
    $buttons = array(
        form_submit_update('submit'),
        anchor_cancel('/app/network/iface/'),
        anchor_delete('/app/network/iface/delete/' . $interface)
    );
} else if ($form_type === 'add') {
    $read_only = FALSE;
    $form_path = '/network/iface/add/' . $interface;
    $buttons = array(
        form_submit_add('submit'),
        anchor_cancel('/app/network/iface/'),
    );
} else  {
    $read_only = TRUE;
    $form_path = '';
    $buttons = array(
        anchor_cancel('/app/network/iface/'),
    );
}

//print_r($iface_info);
$read_only = FALSE;

$vendor = empty($iface_info['vendor']) ? '' : $iface_info['vendor'];
$bus = empty($iface_info['bus']) ? '' : $iface_info['bus'];
$device = empty($iface_info['device']) ? '' : $iface_info['device'];
$link = (isset($iface_info['link']) && $iface_info['link']) ? lang('network_detected') : lang('network_not_detected');
$speed = (isset($iface_info['speed']) && $iface_info['speed'] > 0) ? $iface_info['speed'] . ' ' . lang('base_megabits_per_second') : '';

///////////////////////////////////////////////////////////////////////////////
// Form open
///////////////////////////////////////////////////////////////////////////////

echo form_open($form_path);
echo form_header(lang('network_interface'));

///////////////////////////////////////////////////////////////////////////////
// General information
///////////////////////////////////////////////////////////////////////////////

echo fieldset_header(lang('base_information'));
echo field_input('vendor', $vendor, lang('network_vendor'), TRUE);
echo field_input('device', $device, lang('network_device'), TRUE);
echo field_input('bus', $bus, lang('network_bus'), TRUE);
echo field_input('link', $link, lang('network_link'), TRUE);
echo field_input('speed', $speed, lang('network_speed'), TRUE);
echo fieldset_footer();

///////////////////////////////////////////////////////////////////////////////
// Common header
///////////////////////////////////////////////////////////////////////////////

echo fieldset_header(lang('base_settings'));
echo field_input('interface', $interface, lang('network_interface'), TRUE);
echo field_dropdown('role', $roles, $iface_info['role'], lang('network_role'), FALSE, array('id' => 'role'));
echo field_dropdown('bootproto', $bootprotos, $iface_info['ifcfg']['bootproto'], lang('network_connection_type'), FALSE, array('id' => 'bootproto'));

///////////////////////////////////////////////////////////////////////////////
// Static
///////////////////////////////////////////////////////////////////////////////

echo field_input('ipaddr', $iface_info['ifcfg']['ipaddr'], lang('network_ip'));
echo field_input('netmask', $iface_info['ifcfg']['netmask'], lang('network_netmask'));
echo field_input('gateway', $iface_info['ifcfg']['gateway'], lang('network_gateway'));

///////////////////////////////////////////////////////////////////////////////
// DHCP
///////////////////////////////////////////////////////////////////////////////

echo field_input('hostname', $hostname, lang('network_hostname'));
echo field_checkbox('dhcp_dns', $dhcp_dns, lang('network_automatic_dns_servers'));

///////////////////////////////////////////////////////////////////////////////
// PPPoE
///////////////////////////////////////////////////////////////////////////////

echo field_input('username', $username, lang('base_username'));
echo field_input('password', $password, lang('base_password'));
echo field_input('mtu', $mtu, lang('network_mtu'));
echo field_checkbox('pppoe_dns', $pppoe_dns, lang('network_automatic_dns_servers'));

///////////////////////////////////////////////////////////////////////////////
// Common footer
///////////////////////////////////////////////////////////////////////////////

echo fieldset_footer();
echo field_button_set($buttons);

///////////////////////////////////////////////////////////////////////////////
// Form close
///////////////////////////////////////////////////////////////////////////////

echo form_footer();
echo form_close();
