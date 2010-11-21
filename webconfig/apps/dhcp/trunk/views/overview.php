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

$this->load->helper('form');
$this->load->library('form_validation');
$this->lang->load('dhcp');

// form_label(lang('dhcp_authoritative'), 'authoritative') .
// form_checkbox('authoritative', 'TRUE', set_value('authoritative', $authoritative)) . "

echo form_open('dhcp'); 
echo form_fieldset(lang('dhcp_global_configuration'));
echo cos_form_toggle_enable('authoritative', set_value('authoritative', $authoritative), lang('dhcp_authoritative'));
echo "
	<div>" .
		form_label(lang('dhcp_domain'), 'domain') .
		form_input('domain', set_value('domain', $domain)) . " " . form_error('domain') . "
	</div>
	<div>" . form_submit_update('submit') . "</div>
";
echo form_fieldset_close(); 
echo form_close();

// vim: ts=4
?>
