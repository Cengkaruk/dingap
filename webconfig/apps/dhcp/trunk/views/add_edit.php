<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2003-2010 ClearFoundation
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
// NOTES: what to do with read-only form values?

$this->load->helper('form');
$this->load->helper('url');
$this->load->library('form_validation');

$this->lang->load('dhcp');

echo form_open('/dhcp/edit/' . $interface);
echo form_fieldset(lang('dhcp_subnet'));
echo "
	<div>" .
		form_label(lang('dhcp_network_interface'), 'interface') .
		form_input('interface', set_value('interface', $interface)) . " " . form_error('interface') . "
	</div>
	<div>" .
		form_label(lang('dhcp_network'), 'network') .
		form_input('network', set_value('network', $network)) . " " . form_error('network') . "
	</div>
	<div>" .
		form_label(lang('dhcp_lease_time'), 'leasetime') .
		form_dropdown('leasetime', $leasetimes, set_value('leasetime', $leasetime)) . " " . form_error('leasetime') . "
	</div>
	<div>" .
		form_label(lang('dhcp_gateway'), 'gateway') .
		form_input('gateway', set_value('gateway', $gateway)) . " " . form_error('gateway') . "
	</div>
	<div>" .
		form_label(lang('dhcp_ip_range_start'), 'start') .
		form_input('start', set_value('start', $start)) . " " . form_error('start') . "
	</div>
	<div>" .
		form_label(lang('dhcp_ip_range_end'), 'end') .
		form_input('end', set_value('end', $end)) . " " . form_error('end') . "
	</div>
";
for ($i = 0; $i < 3; $i++) {
	$server = isset($dns[$i]) ? $dns[$i] : "";
	$form_id = 'dns' . $i;
	echo "
		<div>" .
			form_label(lang('dhcp_dns') . " #" . sprintf("%d", $i + 1), "$form_id") .
			form_input("dns[]", set_value("dns[]", $dns[$i])) . " " . form_error("dns[]") . "
		</div>
	";
}
echo "	
	<div>" .
		form_label(lang('dhcp_wins'), 'wins') .
		form_input('wins', set_value('wins', $wins)) . " " . form_error('wins') . "
	</div>
	<div>" .
		form_label(lang('dhcp_tftp'), 'tftp') .
		form_input('tftp', set_value('tftp', $tftp)) . " " . form_error('tftp') . "
	</div>
	<div>" . 
		form_label(lang('dhcp_ntp'), 'ntp') .
		form_input('ntp', set_value('ntp', $ntp)) . " " . form_error('ntp') . "
	</div>
";

if ($formtype === 'edit') {
	echo "
		<div>" .
			button_set_open() .
			form_submit_update('submit') .
			anchor_cancel('/app/dhcp') .
			anchor_delete('/app/dhcp/delete/' . $interface) .
			button_set_close() . "
		</div>
	";
} else {
	echo "
		<div>" .
			button_set_open() .
			form_submit_add('submit') .
			anchor_cancel('/app/dhcp') .
			button_set_close() . "
		</div>
	";
}
echo form_fieldset_close();
echo form_close();


// vim: ts=4
