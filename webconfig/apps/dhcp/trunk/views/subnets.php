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

// Loop through subnet info and display it in HTML table
//------------------------------------------------------

foreach ($subnets as $interface => $subnetinfo) {

	if (! $subnetinfo["isvalid"]) {
		$status = "<span class='alert'>" . lang('base_invalid') . "</span>";
		$short_action = "<a href='/app/dhcp/edit/" . $interface . "'>$interface - $status</a>";
		$full_actions = anchor_delete('dhcp/delete/' . $interface);
	} else if ($subnetinfo["isconfigured"]) {
		$status = "<span class='ok'>" . lang('base_enabled') . "</span>";
		$short_action = "<a href='/app/dhcp/edit/" . $interface . "'>$interface - $status</a>";
		$full_actions = 
			button_set_open() .
			anchor_edit('dhcp/edit/' . $interface) . " " .
			anchor_delete('dhcp/delete/' . $interface) . " " .
			button_set_close();
	} else {
		$status = "<span class='alert'>" . lang('base_disabled') . "</span>";
		$short_action = "<a href='/app/dhcp/add/" . $interface . "'>$interface - $status</a>";
		$full_actions = 
			button_set_open() .
			anchor_add('dhcp/add/' . $interface) .
			button_set_close();
	}

	// Short summary table
	$details['simple_title'] = "$interface / " .  $subnetinfo['network'];
	$details['simple_link'] = $short_action;

	// Long summary table
	$details['details'] = array(
		$interface,
		$subnetinfo['network'],
		$status,
		$full_actions
	);

	$items[] = $details;
}

// Main view
//------------------------------------------------------

$headers = array(
	lang('network_interface'),
	lang('network_network'),
	lang('base_status'),
	''
);

sort($items);

echo summary_table_start(lang('dhcp_subnets'));
echo summary_table_header($headers);
echo summary_table_items($items);
echo summary_table_end();

// vim: ts=4
