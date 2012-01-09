<?php

/**
 * Content filter banned/exception IPs view.
 *
 * @category   Apps
 * @package    Content_Filter
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/content_filter/
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
$this->lang->load('content_filter');

///////////////////////////////////////////////////////////////////////////////
// Form handler
///////////////////////////////////////////////////////////////////////////////

if ($type === 'banned') {
    $title = lang('content_filter_banned_ips');
    $basename = 'banned_ips';
} else if ($type === 'exception') {
    $title = lang('content_filter_exception_ips');
    $basename = 'exception_ips';
}

///////////////////////////////////////////////////////////////////////////////
// Buttons
///////////////////////////////////////////////////////////////////////////////

$buttons = array(
    anchor_cancel("/app/content_filter/"),
    anchor_add("/app/content_filter/$basename/add"),
);

///////////////////////////////////////////////////////////////////////////////
// Headers
///////////////////////////////////////////////////////////////////////////////

$headers = array(
    lang('network_ip'),
);

///////////////////////////////////////////////////////////////////////////////
// Items
///////////////////////////////////////////////////////////////////////////////

foreach ($ips as $ip) {
    $item['title'] = $ip;
    $item['action'] = "/app/content_filter/$basename/delete/$ip";
    $item['anchors'] = button_set(
        array( anchor_delete($item['action'], 'high'))
    );
    $item['details'] = array(
        $ip
    );

    $items[] = $item;
}

///////////////////////////////////////////////////////////////////////////////
// List table
///////////////////////////////////////////////////////////////////////////////

echo form_open("content_filter/$basename/edit");

echo summary_table(
    $title,
    $buttons,
    $headers,
    $items
);

echo form_close();
