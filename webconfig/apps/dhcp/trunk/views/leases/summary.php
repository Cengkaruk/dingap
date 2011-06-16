<?php

/**
 * DHCP leases view.
 *
 * @category   ClearOS
 * @package    DHCP
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/dhcp/
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

$this->lang->load('network');
$this->lang->load('dhcp');

///////////////////////////////////////////////////////////////////////////////
// Headers
///////////////////////////////////////////////////////////////////////////////

$headers = array(
   lang('network_ip'),
   lang('network_mac_address'),
   lang('network_hostname'),
   lang('dhcp_vendor'),
   lang('dhcp_expires')
);

///////////////////////////////////////////////////////////////////////////////
// Anchors 
///////////////////////////////////////////////////////////////////////////////

$anchors = array(anchor_add('/app/dhcp/leases/add'));

///////////////////////////////////////////////////////////////////////////////
// Items
///////////////////////////////////////////////////////////////////////////////

foreach ($leases as $key => $details) {
    $key = $details['mac'] . "/" . $details['ip'];
    $order_ip = "<span style='display: none'>" . sprintf("%032b", ip2long($details['ip'])) . "</span>" . $details['ip'];

    if ($details['end'] == 0)
        $order_date = lang('dhcp_never');
    else
        $order_date = "<span style='display: none'>" . $details['end'] . "</span>" . strftime('%c', $details['end']);

    if (strlen($details['vendor']) > 20)
        $vendor = substr($details['vendor'], 0, 20) . "...";
    else
        $vendor = $details['vendor'];

    $item['title'] = $order_ip;
    $item['action'] = anchor_edit('/app/dhcp/leases/edit/' . $key, 'high');
    $item['anchors'] = button_set(
        array(
            anchor_edit('/app/dhcp/leases/edit/' . $key, 'high'),
            anchor_delete('/app/dhcp/leases/delete/' . $key, 'low')
        )
    );
    $item['details'] = array(
        $order_ip,
        $details['mac'],
        $details['hostname'],
        $vendor,
        $order_date,
        $full_actions
    );

    $items[] = $item;
}

sort($items);

///////////////////////////////////////////////////////////////////////////////
// Summary table
///////////////////////////////////////////////////////////////////////////////

echo summary_table(
    lang('dhcp_leases'),
    $anchors,
    $headers,
    $items
);
