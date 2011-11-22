<?php

/**
 * Intrusion prevention blocked list.
 *
 * @category   ClearOS
 * @package    Intrusion_Prevention
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/intrusion_prevention/
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

$this->lang->load('intrusion_prevention');
$this->lang->load('network');

///////////////////////////////////////////////////////////////////////////////
// Headers
///////////////////////////////////////////////////////////////////////////////

$headers = array(
    lang('network_ip'),
    lang('intrusion_prevention_security_id'),
    lang('intrusion_prevention_block_time'),
);

///////////////////////////////////////////////////////////////////////////////
// Anchors
///////////////////////////////////////////////////////////////////////////////

// TODO: add delete all button / action
$anchors = array();

///////////////////////////////////////////////////////////////////////////////
// Items
///////////////////////////////////////////////////////////////////////////////

$items = array();

foreach ($blocked as $id => $details) {

    $ip = $details['blocked_ip'];
    $order_ip = "<span style='display: none'>" . sprintf("%032b", ip2long($ip)) . "</span>$ip";
    $sid = $details['sid'];
    $timestamp = strftime("%c", $details['timestamp']);

    ///////////////////////////////////////////////////////////////////////////
    // Item buttons
    ///////////////////////////////////////////////////////////////////////////

    $detail_buttons = button_set(
        array(
            anchor_custom('/app/intrusion_prevention/blocked_list/exempt/' . $ip, lang('intrusion_prevention_white_list'), 'high'),
            anchor_delete('/app/intrusion_prevention/blocked_list/delete/' . $ip)
        )
    );

    ///////////////////////////////////////////////////////////////////////////
    // Item details
    ///////////////////////////////////////////////////////////////////////////

    $item['title'] = $ip;
    $item['action'] = '/app/intrusion_prevention/blocked_list/delete/' . $ip;
    $item['anchors'] = $detail_buttons;
    $item['details'] = array(
        $order_ip,
        $sid,
        $timestamp,
    );

    $items[] = $item;
}

///////////////////////////////////////////////////////////////////////////////
// Summary table
///////////////////////////////////////////////////////////////////////////////

echo summary_table(
    lang('intrusion_prevention_blocked_list'),
    $anchors,
    $headers,
    $items
);
