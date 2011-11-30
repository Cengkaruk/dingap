<?php

/**
 * Port forwarding add rule view.
 *
 * @category   ClearOS
 * @package    Port_Forwarding
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/port_forwarding/
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
// Standard service
///////////////////////////////////////////////////////////////////////////////

echo form_open('port_forwarding/add');
echo form_header(lang('firewall_standard_service'));

echo field_simple_dropdown('service', $services, $service, lang('firewall_service'));
echo field_input('service_ip', $service_ip, lang('firewall_ip_address'));

echo field_button_set(
    array(
        form_submit_add('submit_standard', 'high'),
        anchor_cancel('/app/port_forwarding')
    )
);

echo form_footer();
echo form_close();

///////////////////////////////////////////////////////////////////////////////
// Port
///////////////////////////////////////////////////////////////////////////////

echo form_open('port_forwarding/add');
echo form_header(lang('firewall_port'));

echo field_input('port_nickname', $port_nickname, lang('firewall_nickname'));
echo field_simple_dropdown('port_protocol', $protocols, $port_protocol, lang('firewall_protocol'));
echo field_input('port_from', $port, lang('firewall_from_port'));
echo field_input('port_to', $port, lang('firewall_to_port'));
echo field_input('port_ip', $port_ip, lang('firewall_ip_address'));

echo field_button_set(
    array(
        form_submit_add('submit_port', 'high'),
        anchor_cancel('/app/port_forwarding')
    )
);

echo form_footer();
echo form_close();

///////////////////////////////////////////////////////////////////////////////
// Port range
///////////////////////////////////////////////////////////////////////////////

echo form_open('port_forwarding/add');
echo form_header(lang('firewall_port_range'));

echo field_input('range_nickname', $range_nickname, lang('firewall_nickname'));
echo field_simple_dropdown('range_protocol', $protocols, $range_protocol, lang('firewall_protocol'));
echo field_input('range_start', $range_start, lang('firewall_start_port'));
echo field_input('range_end', $range_end, lang('firewall_end_port'));
echo field_input('range_ip', $range_ip, lang('firewall_ip_address'));

echo field_button_set(
    array(
        form_submit_add('submit_range', 'high'),
        anchor_cancel('/app/port_forwarding')
    )
);

echo form_footer();
echo form_close();
