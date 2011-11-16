<?php

/**
 * Content filter policy item controller.
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

$buttons = array(
    form_submit_update('submit'),
    anchor_cancel('/app/content_filter/policy/edit/' . $policy)
);

///////////////////////////////////////////////////////////////////////////////
// Form
///////////////////////////////////////////////////////////////////////////////

echo form_open('/app/content_filter/settings/edit');
echo form_header(lang('base_settings'));

echo field_dropdown('group_mode', $group_modes, $group_mode, lang('content_filter_filter_mode'));
echo field_dropdown('naughtyness_limit', $naughtyness_limits, $naughtyness_limit, lang('content_filter_dynamic_scan_sensitivity'));
echo field_dropdown('reporting_level', $reporting_levels, $reporting_level, lang('content_filter_reporting_level'));
echo field_toggle_enable_disable('disable_content_scan', $disable_content_scan, lang('content_filter_virus_scan'));
echo field_toggle_enable_disable('deep_url_analysis', $deep_url_analysis, lang('content_filter_deep_url_analysis'));
echo field_toggle_enable_disable('block_downloads', $block_downloads, lang('content_filter_block_downloads'));
echo field_toggle_enable_disable('blanket_block', $blanket_block, lang('content_filter_blanket_block'));
echo field_toggle_enable_disable('block_ip_domains', $block_ip_domains, lang('content_filter_block_ip_domains'));
echo field_button_set($buttons);

echo form_footer();
echo form_close();
