<?php

/**
 * Content filter grey/banned/exempt sites view.
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

$this->lang->load('content_filter');

///////////////////////////////////////////////////////////////////////////////
// Form handler
///////////////////////////////////////////////////////////////////////////////

if ($type === 'banned') {
    $title = lang('content_filter_banned_sites');
    $basename = 'banned_sites';
} else if ($type === 'gray') {
    $title = lang('content_filter_gray_sites');
    $basename = 'gray_sites';
} else if ($type === 'exception') {
    $title = lang('content_filter_exception_sites');
    $basename = 'exception_sites';
}

///////////////////////////////////////////////////////////////////////////////
// Buttons
///////////////////////////////////////////////////////////////////////////////

$buttons = array(
    anchor_cancel("/app/content_filter/policy/configure/$policy"),
    anchor_add("/app/content_filter/$basename/add/$policy"),
);

///////////////////////////////////////////////////////////////////////////////
// Headers
///////////////////////////////////////////////////////////////////////////////

$headers = array(
    lang('content_filter_site'),
);

///////////////////////////////////////////////////////////////////////////////
// Items
///////////////////////////////////////////////////////////////////////////////

foreach ($sites as $site) {
    $site_route = preg_replace('/\//', 'X', $site);

    $item['title'] = $site;
    $item['action'] = "/app/content_filter/$basename/delete/$policy/$site_route";
    $item['anchors'] = button_set(
        array( anchor_delete($item['action'], 'high'))
    );
    $item['details'] = array(
        $site
    );

    $items[] = $item;
}

///////////////////////////////////////////////////////////////////////////////
// List table
///////////////////////////////////////////////////////////////////////////////

echo form_open("content_filter/$basename/edit/$policy");

echo summary_table(
    $title,
    $buttons,
    $headers,
    $items
);

echo form_close();
