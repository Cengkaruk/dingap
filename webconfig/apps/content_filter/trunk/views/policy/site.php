<?php

/**
 * Content filter site controller.
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
// Form handler
///////////////////////////////////////////////////////////////////////////////

if ($type === 'banned') {
    $basename = 'banned_sites';
    $title = lang('content_filter_banned_site');
} else if ($type === 'gray') {
    $basename = 'gray_sites';
    $title = lang('content_filter_gray_site');
} else if ($type === 'exception') {
    $basename = 'exception_sites';
    $title = lang('content_filter_exception_site');
}

$form = "content_filter/$basename/add/$policy";

$buttons = array(
    form_submit_add('submit'),
    anchor_cancel("/app/content_filter/$basename/edit/$policy")
);

///////////////////////////////////////////////////////////////////////////////
// Form
///////////////////////////////////////////////////////////////////////////////

echo form_open($form);
echo form_header($title);

echo field_input('site', $site, lang('content_filter_site'));
echo field_button_set($buttons);

echo form_footer();
echo form_close();
