<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2010 ClearFoundation
//
///////////////////////////////////////////////////////////////////////////////
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// Load dependencies
///////////////////////////////////////////////////////////////////////////////

$this->lang->load('filescan');

///////////////////////////////////////////////////////////////////////////////
// Form open
///////////////////////////////////////////////////////////////////////////////

echo form_open('filescan/config'); 

///////////////////////////////////////////////////////////////////////////////
// Form fields
///////////////////////////////////////////////////////////////////////////////

// FIXME
$read_only = TRUE;

echo form_fieldset(lang('base_status'));

echo field_input('status', $status, lang('base_status'), $read_only);
echo field_input('progress', $status, lang('filescan_progress'), $read_only, array('input' => 'progress'));
echo "<div id='progress_bar'> </div>";
echo field_input('errors', $errors, lang('filescan_errors'), $read_only);
echo field_input('items_found', $items_found, lang('filescan_malware_items_found'), $read_only);
echo field_input('last_result', $last_result, lang('filescan_last_scan_result'), $read_only);
// echo field_input('last_run', $last_run, lang('filescan_last_run'), $read_only, array('input' => 'last_run'));

echo form_fieldset_close(); 

///////////////////////////////////////////////////////////////////////////////
// Buttons
///////////////////////////////////////////////////////////////////////////////

echo button_set(
	array(
		anchor_javascript('start', lang('base_start'), 'high'),
		anchor_javascript('stop', lang('base_stop'), 'high')
	)
);

///////////////////////////////////////////////////////////////////////////////
// Form close
///////////////////////////////////////////////////////////////////////////////

echo form_close();

// vim: ts=4
?>
