<?php

/**
 * Egress firewall summary view.
 *
 * @category   ClearOS
 * @package    Egress_Firewall
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/egress_firewall/
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

use \clearos\apps\firewall\Firewall as Firewall;
use \clearos\apps\network\Network as Network;

$this->lang->load('egress_firewall');
$this->lang->load('firewall');

///////////////////////////////////////////////////////////////////////////////
// Headers
///////////////////////////////////////////////////////////////////////////////

$headers = array(
    lang('firewall_nickname'),
    lang('firewall_service'),
    lang('firewall_protocol'),
    lang('firewall_port')
);

///////////////////////////////////////////////////////////////////////////////
// Anchors 
///////////////////////////////////////////////////////////////////////////////

$anchors = array(anchor_add('/app/egress_firewall/port/add'));

///////////////////////////////////////////////////////////////////////////////
// Ports
///////////////////////////////////////////////////////////////////////////////

foreach ($ports as $rule) {
    $key = $rule['protocol_name'] . '/' . $rule['port'];
    $state = ($rule['enabled']) ? 'disable' : 'enable';
    $state_anchor = 'anchor_' . $state;

    $item['title'] = $rule['name'];
    $item['action'] = '/app/egress_firewall/port/delete/' . $key;
    $item['anchors'] = button_set(
        array(
            $state_anchor('/app/egress_firewall/port/' . $state . '/' . $key, 'high'),
            anchor_delete('/app/egress_firewall/port/delete/' . $key, 'low')
        )
    );
    $item['details'] = array(
        $rule['name'],
        $rule['service'],
        $rule['protocol'],
        $rule['port'],
    );

    $items[] = $item;
}

///////////////////////////////////////////////////////////////////////////////
// Port ranges
///////////////////////////////////////////////////////////////////////////////

foreach ($ranges as $rule) {
    $key = $rule['protocol'] . '/' . $rule['from'] . '/' . $rule['to'];
    $state = ($rule['enabled']) ? 'disable' : 'enable';
    $state_anchor = 'anchor_' . $state;

    $item['title'] = $rule['name'];
    $item['action'] = '/app/egress_firewall/port/delete_range/' . $key;
    $item['anchors'] = button_set(
        array(
            $state_anchor('/app/egress_firewall/port/' . $state . '_range/' . $key, 'high'),
            anchor_delete('/app/egress_firewall/port/delete_range/' . $key, 'low')
        )
    );
    $item['details'] = array(
        $rule['name'],
        $rule['service'],
        $rule['protocol'],
        $rule['from'] . ':' . $rule['to'],
    );

    $items[] = $item;
}

///////////////////////////////////////////////////////////////////////////////
// Summary table
///////////////////////////////////////////////////////////////////////////////

sort($items);

echo summary_table(
    lang('egress_firewall_destination_ports'),
    $anchors,
    $headers,
    $items
);
