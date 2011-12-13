<?php

/**
 * Content filter grey sites view.
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
// Buttons
///////////////////////////////////////////////////////////////////////////////

$buttons = array(
    anchor_cancel('/app/content_filter/policy/configure/' . $policy),
    anchor_add('/app/content_filter/grey_sites/add/' . $policy),
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

foreach ($sites as $banned_site) {
    $banned_site_route = preg_replace('/\//', 'X', $banned_site);

    $item['title'] = $banned_site;
    $item['action'] = '/app/content_filter/banned_sites/delete/' . $policy . '/' . $banned_site_route;
    $item['anchors'] = button_set(
        array(
            anchor_delete('/app/content_filter/banned_sites/delete/' . $policy . '/' . $banned_site_route, 'high')
        )
    );
    $item['details'] = array(
        $banned_site
    );

    $items[] = $item;
}

///////////////////////////////////////////////////////////////////////////////
// List table
///////////////////////////////////////////////////////////////////////////////

echo form_open('content_filter/banned_sites/edit/' . $policy);

echo summary_table(
    lang('content_filter_banned_sites'),
    $buttons,
    $headers,
    $items
);

echo form_close();
