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

use \clearos\apps\network\Iface as Iface;

$this->load->language('base');
$this->load->language('network');

///////////////////////////////////////////////////////////////////////////////
// Form modes
///////////////////////////////////////////////////////////////////////////////

if ($form_type === 'edit') {
    $read_only = FALSE;
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

$bus = empty($iface_info['bus']) ? '' : $iface_info['bus'];
$vendor = empty($iface_info['vendor']) ? '' : $iface_info['vendor'];
$device = empty($iface_info['device']) ? '' : $iface_info['device'];
$link = (isset($iface_info['link']) && $iface_info['link']) ? lang('base_yes') : lang('base_no');
$speed = (isset($iface_info['speed']) && ($iface_info['speed'] > 0)) ? $iface_info['speed'] . ' ' . lang('base_megabits_per_second') : lang('base_unknown');
$dns = (isset($iface_info['ifcfg']['peerdns'])) ? $iface_info['ifcfg']['peerdns'] : TRUE;
$role_read_only = ($iface_count <= 1) ? TRUE : $read_only;

$bootproto_read_only = (isset($iface_info['type']) && $iface_info['type'] === Iface::TYPE_PPPOE) ? TRUE : $read_only;

///////////////////////////////////////////////////////////////////////////////
// Form open
///////////////////////////////////////////////////////////////////////////////

echo form_open($form_path);
echo form_header(lang('network_interface'));

///////////////////////////////////////////////////////////////////////////////
// General information
///////////////////////////////////////////////////////////////////////////////

echo fieldset_header(lang('base_information'));

if ($vendor)
    echo field_input('vendor', $vendor, lang('network_vendor'), TRUE);

if ($device)
    echo field_input('device', $device, lang('network_device'), TRUE);

if ($bus)
    echo field_input('bus', $bus, lang('network_bus'), TRUE);

echo field_input('link', $link, lang('network_link'), TRUE);
echo field_input('speed', $speed, lang('network_speed'), TRUE);
echo fieldset_footer();

///////////////////////////////////////////////////////////////////////////////
// Common header
///////////////////////////////////////////////////////////////////////////////

echo fieldset_header(lang('base_settings'));
echo field_input('interface', $interface, lang('network_interface'), TRUE);
echo field_dropdown('role', $roles, $iface_info['role'], lang('network_role'), $role_read_only, array('id' => 'role'));
echo field_dropdown('bootproto', $bootprotos, $iface_info['ifcfg']['bootproto'], lang('network_connection_type'), $bootproto_read_only);

///////////////////////////////////////////////////////////////////////////////
// Static
///////////////////////////////////////////////////////////////////////////////

echo field_input('ipaddr', $iface_info['ifcfg']['ipaddr'], lang('network_ip'), $read_only);
echo field_input('netmask', $iface_info['ifcfg']['netmask'], lang('network_netmask'), $read_only);
echo field_input('gateway', $iface_info['ifcfg']['gateway'], lang('network_gateway'), $read_only);

///////////////////////////////////////////////////////////////////////////////
// DHCP
///////////////////////////////////////////////////////////////////////////////

echo field_input('hostname', $iface_info['ifcfg']['dhcp_hostname'], lang('network_hostname'), $read_only);
echo field_checkbox('dhcp_dns', $dns, lang('network_automatic_dns_servers'), $read_only);

///////////////////////////////////////////////////////////////////////////////
// PPPoE
///////////////////////////////////////////////////////////////////////////////

echo field_input('username', $iface_info['ifcfg']['user'], lang('base_username'), $read_only);
echo field_input('password', $password, lang('base_password'), $read_only);
echo field_input('mtu', $iface_info['ifcfg']['mtu'], lang('network_mtu'), $read_only);
echo field_checkbox('pppoe_dns', $dns, lang('network_automatic_dns_servers'), $read_only);

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
