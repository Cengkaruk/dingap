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
$this->lang->load('dhcp');

echo form_fieldset(lang('dhcp_dhcp') . ' - ' . lang('base_general_settings'));
echo "
	<div>" .
		form_label(lang('dhcp_authoritative'), 'autoritative') .
		$authoritative . "
	</div>
	<div>" .
		form_label(lang('dhcp_domain'), 'domain') .
		$domain . "
	</div>
	<div>" . 
		anchor_edit('/app/dhcp/general/edit') . "
	</div>
";
echo form_fieldset_close(); 

// vim: ts=4
?>
