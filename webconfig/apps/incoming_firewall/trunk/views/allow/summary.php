<?php

/**
 * Incoming firewall summary view.
 *
 * @category   ClearOS
 * @package    SMTP
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/incoming_firewall/
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

$this->lang->load('incoming_firewall');
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

$anchors = array(anchor_add('/app/incoming_firewall/allow/add'));

///////////////////////////////////////////////////////////////////////////////
// Ports
///////////////////////////////////////////////////////////////////////////////

foreach ($ports as $rule) {
    $key = $rule['protocol'] . '|' . $rule['port'];
    $state = ($rule['enabled']) ? 'disable' : 'enable';
    $state_anchor = 'anchor_' . $state;

	$item['title'] = $rule['name'];
	$item['action'] = '/app/incoming_firewall/allow/delete/' . $key;
	$item['anchors'] = button_set(array(
        $state_anchor('/app/incoming_firewall/allow/' . $state . '/' . $key, 'high'),
        anchor_delete('/app/incoming_firewall/allow/delete/' . $key, 'low')
    ));
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
    $key = $rule['protocol'] . '|' . $rule['from'] . '|' . $rule['to'];
    $state = ($rule['enabled']) ? 'disable' : 'enable';
    $state_anchor = 'anchor_' . $state;

	$item['title'] = $rule['name'];
	$item['action'] = '/app/incoming_firewall/allow/delete_range/' . $key;
	$item['anchors'] = button_set(array(
        $state_anchor('/app/incoming_firewall/allow/' . $state . '_range/' . $key, 'high'),
        anchor_delete('/app/incoming_firewall/allow/delete_range/' . $key, 'low')
    ));
	$item['details'] = array(
        $rule['name'],
        $rule['service'],
        $rule['protocol'],
        $rule['from'] . ':' . $rule['to'],
    );

	$items[] = $item;
}

///////////////////////////////////////////////////////////////////////////////
// Special IPsec and PPTP rules
///////////////////////////////////////////////////////////////////////////////

if ($ipsec) {
	$item['title'] = 'IPsec';
	$item['action'] = '/app/incoming_firewall/allow/delete_ipsec';
	$item['anchors'] = button_set(array(
        anchor_delete('/app/incoming_firewall/allow/delete_ipsec')
    ));
	$item['details'] = array(
        'IPsec',
        'IPsec',
        'ESP/AH + UDP',
        '500',
    );

	$items[] = $item;
}

if ($pptp) {
	$item['title'] = 'PPTP';
	$item['action'] = '/app/incoming_firewall/allow/delete_pptp';
	$item['anchors'] = button_set(array(
        anchor_delete('/app/incoming_firewall/allow/delete_pptp')
    ));
	$item['details'] = array(
        'PPTP',
        'PPTP',
        'GRE + TCP',
        '1723',
    );

	$items[] = $item;
}

///////////////////////////////////////////////////////////////////////////////
// Summary table
///////////////////////////////////////////////////////////////////////////////

sort($items);

echo summary_table(
	lang('incoming_firewall_allowed_incoming_connections'),
	$anchors,
	$headers,
	$items
);
