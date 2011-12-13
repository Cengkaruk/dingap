<?php

/**
 * Content filter file extensions view.
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

$this->lang->load('base');
$this->lang->load('content_filter');

///////////////////////////////////////////////////////////////////////////////
// Buttons
///////////////////////////////////////////////////////////////////////////////

$buttons = array(
    anchor_cancel('/app/content_filter/policy/configure/' . $policy),
    form_submit_update('submit', 'high')
);

///////////////////////////////////////////////////////////////////////////////
// Headers
///////////////////////////////////////////////////////////////////////////////

$headers = array(
    lang('base_category'),
    lang('content_filter_file_extension'),
    lang('base_description'),
);

///////////////////////////////////////////////////////////////////////////////
// Items
///////////////////////////////////////////////////////////////////////////////

foreach ($all_file_extensions as $extension) {
    $item['title'] = $extension['name'];
    $item['name'] = 'extensions[' . $extension['name'] . ']';
    $item['state'] = in_array($extension['name'], $banned_file_extensions) ? TRUE : FALSE;
    $item['details'] = array(
        $extension['category_text'],
        $extension['name'],
        $extension['description'],
    );

    $items[] = $item;
}

///////////////////////////////////////////////////////////////////////////////
// List table
///////////////////////////////////////////////////////////////////////////////

$options['grouping'] = TRUE;

echo form_open('content_filter/file_extensions/edit/' . $policy);

echo list_table(
    lang('content_filter_file_extensions'),
    $buttons,
    $headers,
    $items,
    $options
);

echo form_close();
