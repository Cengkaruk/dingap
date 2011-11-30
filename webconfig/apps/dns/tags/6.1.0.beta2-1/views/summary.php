<?php

/**
 * Local DNS Server summary view.
 *
 * @category   ClearOS
 * @package    DNS
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/dns/
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

$this->lang->load('dns');
$this->lang->load('network');

///////////////////////////////////////////////////////////////////////////////
// Headers
///////////////////////////////////////////////////////////////////////////////

$headers = array(
    lang('network_ip'),
    lang('network_hostname'),
);

///////////////////////////////////////////////////////////////////////////////
// Anchors
///////////////////////////////////////////////////////////////////////////////

$anchors = array(anchor_add('/app/dns/add/'));

///////////////////////////////////////////////////////////////////////////////
// Items
///////////////////////////////////////////////////////////////////////////////

foreach ($hosts as $real_ip => $entry) {

    $ip = $entry['ip'];
    $hostname = $entry['hostname'];
    $alias = (count($entry['aliases']) > 0) ? $entry['aliases'][0] : '';
    
    // Add '...' to indicate more aliases exist
    if (count($entry['aliases']) > 1)
        $alias .= " ..."; 

    ///////////////////////////////////////////////////////////////////////////
    // Item buttons
    ///////////////////////////////////////////////////////////////////////////

    // Hide 127.0.0.1 entry

    if (($ip === '127.0.0.1') || ($ip === '::1'))
        continue;

    $detail_buttons = button_set(
        array(
            anchor_edit('/app/dns/edit/' . $ip, 'high'),
            anchor_delete('/app/dns/delete/' . $ip, 'high')
        )
    );

    ///////////////////////////////////////////////////////////////////////////
    // Item details
    ///////////////////////////////////////////////////////////////////////////

    // TODO: not IPv6 friendly
    // Order IPs in human-readable way
    $order_ip = "<span style='display: none'>" . sprintf("%032b", ip2long($ip)) . "</span>$ip";

    $item['title'] = $ip . " - " . $hostname;
    $item['action'] = '/app/dns/edit/' . $ip;
    $item['anchors'] = $detail_buttons;
    $item['details'] = array(
        $order_ip,
        $hostname,
    );

    $items[] = $item;
}

///////////////////////////////////////////////////////////////////////////////
// Summary table
///////////////////////////////////////////////////////////////////////////////

echo summary_table(
    lang('dns_dns_server'),
    $anchors,
    $headers,
    $items
);
