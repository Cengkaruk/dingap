<?php

/**
 * Port forwarding summary view.
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

$this->lang->load('port_forwarding');
$this->lang->load('firewall');

///////////////////////////////////////////////////////////////////////////////
// Headers
///////////////////////////////////////////////////////////////////////////////

$headers = array(
    lang('firewall_nickname'),
    lang('firewall_service'),
    lang('firewall_protocol'),
    lang('firewall_from_port'),
    lang('firewall_to_port'),
    lang('firewall_ip_address'),
);

///////////////////////////////////////////////////////////////////////////////
// Anchors 
///////////////////////////////////////////////////////////////////////////////

$anchors = array(anchor_add('/app/port_forwarding/add'));

///////////////////////////////////////////////////////////////////////////////
// Ports
///////////////////////////////////////////////////////////////////////////////

foreach ($ports as $rule) {
    $key = $rule['protocol_name'] . '/' . $rule['from_port'] . '/' . $rule['to_port'] . '/' . $rule['to_ip'];

    $state = ($rule['enabled']) ? 'disable' : 'enable';
    $state_anchor = 'anchor_' . $state;

    $item['title'] = $rule['name'];
    $item['action'] = '/app/port_forwarding/delete/' . $key;
    $item['anchors'] = button_set(
        array(
            $state_anchor('/app/port_forwarding/' . $state . '/' . $key, 'high'),
            anchor_delete('/app/port_forwarding/delete/' . $key, 'low')
        )
    );
    $item['details'] = array(
        $rule['name'],
        $rule['service'],
        $rule['protocol_name'],
        $rule['from_port'],
        $rule['to_port'],
        $rule['to_ip'],
    );

    $items[] = $item;
}

///////////////////////////////////////////////////////////////////////////////
// Port ranges
///////////////////////////////////////////////////////////////////////////////

foreach ($ranges as $rule) {
    $key = $rule['protocol_name'] . '/' . $rule['low_port'] . '/' . $rule['high_port'] . '/' . $rule['to_ip'];
    $state = ($rule['enabled']) ? 'disable' : 'enable';
    $state_anchor = 'anchor_' . $state;

    $item['title'] = $rule['name'];
    $item['action'] = '/app/port_forwarding/delete_range/' . $key;
    $item['anchors'] = button_set(
        array(
            $state_anchor('/app/port_forwarding/' . $state . '_range/' . $key, 'high'),
            anchor_delete('/app/port_forwarding/delete_range/' . $key, 'low')
        )
    );
    $item['details'] = array(
        $rule['name'],
        $rule['service'],
        $rule['protocol_name'],
        $rule['low_port'] . ':' . $rule['high_port'],
        $rule['low_port'] . ':' . $rule['high_port'],
        $rule['to_ip']
    );

    $items[] = $item;
}

///////////////////////////////////////////////////////////////////////////////
// Summary table
///////////////////////////////////////////////////////////////////////////////

sort($items);

echo summary_table(
    lang('port_forwarding_port_forwarding'),
    $anchors,
    $headers,
    $items
);
