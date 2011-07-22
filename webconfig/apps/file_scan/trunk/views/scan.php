<?php

/**
 * File scan scanner view.
 *
 * @category   ClearOS
 * @package    Date
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/date/
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
$this->lang->load('file_scan');

///////////////////////////////////////////////////////////////////////////////
// Form open
///////////////////////////////////////////////////////////////////////////////

echo form_open('file_scan/config'); 
echo form_header(lang('file_scan_scanner'));

///////////////////////////////////////////////////////////////////////////////
// Form fields and buttons
///////////////////////////////////////////////////////////////////////////////

// FIXME
$read_only = TRUE;

echo field_input('state', $state, lang('base_state'), $read_only);
echo field_input('status', $status, lang('base_status'), $read_only);
// echo field_progress_bar('progress', $progress, lang('file_scan_progress'), array('input' => 'progress'));
echo field_progress_bar(lang('file_scan_progress'), 'progress');
echo field_input('error_count', $errors, lang('file_scan_errors'), $read_only);
echo field_input('malware_count', $items_found, lang('file_scan_malware_items_found'), $read_only);
echo field_input('last_result', $last_result, lang('file_scan_last_scan_result'), $read_only);

echo field_button_set(
	array(
		anchor_javascript('start', lang('base_start'), 'high'),
		anchor_javascript('stop', lang('base_stop'), 'high')
	)
);

///////////////////////////////////////////////////////////////////////////////
// Form close
///////////////////////////////////////////////////////////////////////////////

echo form_footer();
echo form_close();
