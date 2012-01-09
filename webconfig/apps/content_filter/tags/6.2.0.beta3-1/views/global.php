<?php

/**
 * Content filter policy summary view.
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
// Items
///////////////////////////////////////////////////////////////////////////////

$anchor = anchor_edit('/app/content_filter/exception_ips/edit', 'high');
$items['exception_ips']['title'] = lang('content_filter_exception_ips');
$items['exception_ips']['action'] = $anchor;
$items['exception_ips']['anchors'] = array($anchor);

$anchor = anchor_edit('/app/content_filter/banned_ips/edit', 'high');
$items['banned_ips']['title'] = lang('content_filter_banned_ips');
$items['banned_ips']['action'] = $anchor;
$items['banned_ips']['anchors'] = array($anchor);

///////////////////////////////////////////////////////////////////////////////
// Action table
///////////////////////////////////////////////////////////////////////////////

echo action_table(
    lang('content_filter_global_settings'),
    $items
);
