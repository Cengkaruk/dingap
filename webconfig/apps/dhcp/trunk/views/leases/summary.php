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

$this->load->helper('form');
$this->load->helper('url');
$this->load->library('form_validation');
$this->lang->load('network');
$this->lang->load('dhcp');

// Loop through subnet info and display it in HTML table
//------------------------------------------------------

foreach ($leases as $key => $details) {
	// Create a unique ID by merging the MAC and active IP
	$id = $details['active_mac'] . "/" . $details['active_ip'];
	$name = $details['active_mac'] . " - " . $details['active_ip'];
	$short_action = "<a href='/app/dhcp/lease/edit/" . $id . "'>" . $name . "</a>";
	$full_actions = 
			button_set(
				anchor_edit('dhcp/edit/lease/' . $id) . " " .
				anchor_delete('dhcp/delete/lease/' . $id)
			);

	// Short summary table
	$rows['simple_title'] = $name;
	$rows['simple_link'] = $short_action;

	// Long summary table
	$rows['details'] = array(
		$details['active_ip'],
		$details['active_mac'],
		$details['hostname'],
		($details['active_end'] == 0) ? lang('base_unlimited') : strftime('%c', $details['active_end']),
		$full_actions
	);

	$items[] = $rows;
}

// Main view
//------------------------------------------------------

$headers = array(
	lang('network_ip'),
	lang('network_mac_address'),
	lang('network_hostname'),
	lang('dhcp_expires'),
	''
);

sort($items);

echo summary_table_start(lang('dhcp_leases'));
echo summary_table_header($headers);
echo summary_table_items($items);
echo summary_table_end();

// vim: ts=4
