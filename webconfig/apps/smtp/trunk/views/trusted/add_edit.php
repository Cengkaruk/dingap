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

$this->lang->load('smtp');
$this->lang->load('network');

///////////////////////////////////////////////////////////////////////////////
// Form modes
///////////////////////////////////////////////////////////////////////////////

// FIXME: should we have edit mode for simple values?  Discuss.

if ($mode === 'edit') {
	$form_path = '/smtp/trusted/edit';
	$buttons = array(
		form_submit_update('submit'),
		anchor_cancel('/app/smtp/trusted/'),
		anchor_delete('/app/smtp/trusted/delete/' . $network)
	);
} else {
	$form_path = '/smtp/trusted/add';
	$buttons = array(
		form_submit_add('submit'),
		anchor_cancel('/app/smtp/trusted/')
	);
}

///////////////////////////////////////////////////////////////////////////////
// Form open
///////////////////////////////////////////////////////////////////////////////

echo form_open($form_path . '/' . $network);
echo form_header(lang('smtp_trusted_networks'));

///////////////////////////////////////////////////////////////////////////////
// Form fields and buttons
///////////////////////////////////////////////////////////////////////////////

echo form_fieldset(lang('smtp_trusted_networks'));
echo field_input('network', $network, lang('network_network'));
echo form_fieldset_close();

echo button_set($buttons);

///////////////////////////////////////////////////////////////////////////////
// Form close
///////////////////////////////////////////////////////////////////////////////

echo form_footer();
echo form_close();
