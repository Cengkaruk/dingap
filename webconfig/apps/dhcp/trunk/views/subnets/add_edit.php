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
// FIXME: what to do with validating IP ranges and its ilk

$this->load->helper('form');
$this->load->helper('url');
$this->load->library('form_validation');

$this->lang->load('dhcp');

echo form_open('/dhcp/subnets/edit/' . $interface);
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
    <div> " .
        form_label(lang('dhcp_dns') . "#1", 'dns1') .
        form_input('dns1', set_value("dns1", $dns[0])) . " " . form_error("dns1") . "
    </div>
    <div> " .
        form_label(lang('dhcp_dns') . "#2", 'dns2') .
        form_input('dns2', set_value("dns2", $dns[1])) . " " . form_error("dns2") . "
    </div>
    <div> " .
        form_label(lang('dhcp_dns') . "#3", 'dns3') .
        form_input('dns3', set_value("dns3", $dns[2])) . " " . form_error("dns3") . "
    </div>
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
			anchor_cancel('/app/dhcp/subnets/') .
			anchor_delete('/app/dhcp/subnets/delete/' . $interface) .
			button_set_close() . "
		</div>
	";
} else {
	echo "
		<div>" .
			button_set_open() .
			form_submit_add('submit') .
			anchor_cancel('/app/dhcp/subnets/') .
			button_set_close() . "
		</div>
	";
}
echo form_fieldset_close();
echo form_close();


// vim: ts=4
