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

$this->lang->load('network');

///////////////////////////////////////////////////////////////////////////////
// Form handler
///////////////////////////////////////////////////////////////////////////////

if ($mode === 'edit') {
	$read_only = FALSE;
	$buttons = array(
		form_submit_update('submit'),
		anchor_cancel('/app/network')
	);
} else {
	$read_only = TRUE;
	$buttons = array(anchor_edit('/app/network/general'));
}

///////////////////////////////////////////////////////////////////////////////
// Form open
///////////////////////////////////////////////////////////////////////////////

echo form_open('network/general'); 
echo form_header(lang('base_general_settings'));

///////////////////////////////////////////////////////////////////////////////
// Form fields and buttons
///////////////////////////////////////////////////////////////////////////////

//$read_only = FALSE;
echo form_fieldset(lang('network_network') . ' - ' . lang('base_general_settings'));
echo field_dropdown('network_mode', $network_modes, $network_mode, lang('network_mode'), $read_only);
echo field_input('hostname', $hostname, lang('network_hostname'), $read_only);
echo field_input('dns1', $dns1, lang('network_dns_server') . ' #1', $read_only);
echo field_input('dns2', $dns2, lang('network_dns_server') . ' #2', $read_only);

echo form_fieldset_close(); 
echo button_set($buttons);

///////////////////////////////////////////////////////////////////////////////
// Form close
///////////////////////////////////////////////////////////////////////////////

echo form_footer();
echo form_close();

// vim: ts=4
?>
