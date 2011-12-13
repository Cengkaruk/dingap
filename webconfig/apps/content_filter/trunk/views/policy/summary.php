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

$anchor = anchor_edit('/app/content_filter/general/edit/' . $policy, 'high');
$items['general']['title'] = 'General Settings';
$items['general']['action'] = $anchor;
$items['general']['anchors'] = array($anchor);

$anchor = anchor_edit('/app/content_filter/blacklists/edit/' . $policy, 'high');
$items['blacklists']['title'] = lang('content_filter_blacklists');
$items['blacklists']['action'] = $anchor;
$items['blacklists']['anchors'] = array($anchor);

$anchor = anchor_edit('/app/content_filter/phrase_lists/edit/' . $policy, 'high');
$items['phrase_lists']['title'] = lang('content_filter_phrase_lists');
$items['phrase_lists']['action'] = $anchor;
$items['phrase_lists']['anchors'] = array($anchor);


$anchor = anchor_edit('/app/content_filter/mime_types/edit/' . $policy, 'high');
$items['mime_types']['title'] = lang('content_filter_mime_types');
$items['mime_types']['action'] = $anchor;
$items['mime_types']['anchors'] = array($anchor);

$anchor = anchor_edit('/app/content_filter/file_extensions/edit/' . $policy, 'high');
$items['file_extensions']['title'] = lang('content_filter_file_extensions');
$items['file_extensions']['action'] = $anchor;
$items['file_extensions']['anchors'] = array($anchor);

$anchor = anchor_edit('/app/content_filter/banned_sites/edit/' . $policy, 'high');
$items['banned_sites']['title'] = lang('content_filter_banned_sites');
$items['banned_sites']['action'] = $anchor;
$items['banned_sites']['anchors'] = array($anchor);

$anchor = anchor_edit('/app/content_filter/gray_sites/edit/' . $policy, 'high');
$items['gray_sites']['title'] = lang('content_filter_gray_sites');
$items['gray_sites']['action'] = $anchor;
$items['gray_sites']['anchors'] = array($anchor);

$anchor = anchor_edit('/app/content_filter/exception_sites/edit/' . $policy, 'high');
$items['exception_sites']['title'] = lang('content_filter_exception_sites');
$items['exception_sites']['action'] = $anchor;
$items['exception_sites']['anchors'] = array($anchor);

///////////////////////////////////////////////////////////////////////////////
// Action table
///////////////////////////////////////////////////////////////////////////////

// FIXME
echo infobox_highlight('', 'This back button will be moved below - here it is for now: ' . anchor_custom('/app/content_filter', 'Back'));

echo action_table(
    lang('content_filter_policy') . ' - ' . $name,
    $items
);
