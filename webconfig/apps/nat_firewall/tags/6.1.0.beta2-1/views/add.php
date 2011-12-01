<?php

/**
 * NAT firewall forwarding add view.
 *
 * @category   ClearOS
 * @package    NAT_Firewall
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/nat_firewall/
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
$this->lang->load('firewall');

///////////////////////////////////////////////////////////////////////////////
// Handling
///////////////////////////////////////////////////////////////////////////////

$iface_count = count($interfaces);

if ($iface_count == 1) {
    $iface_read_only = TRUE;
    $interface = $interfaces[0];
} else if ($iface_count > 1) {
    $iface_read_only = FALSE;
} else {
    echo infobox_warning(lang('base_warning'), lang('nat_firewall_no_external_nic'));
    return;
}

///////////////////////////////////////////////////////////////////////////////
// Form
///////////////////////////////////////////////////////////////////////////////

echo form_open('nat_firewall/add');
echo form_header(lang('nat_firewall_add_nat_rule'));

echo field_input('nickname', $nickname, lang('firewall_nickname'));
echo field_simple_dropdown('interface', $interfaces, $interface, lang('nat_firewall_interface'), $iface_read_only);
echo field_input('public_ip', $public_ip, lang('nat_firewall_public_ip'));
echo field_input('private_ip', $private_ip_address, lang('nat_firewall_private_ip'));
echo field_checkbox('all', $all, lang('firewall_all_protocols_and_ports'));
echo field_simple_dropdown('protocol', $protocols, $protocol, lang('firewall_protocol'));
echo field_input('port', $port, lang('nat_firewall_port_or_port_range'));

echo field_button_set(
    array(
        form_submit_add('submit', 'high'),
        anchor_cancel('/app/nat_firewall')
    )
);

echo form_footer();
echo form_close();
