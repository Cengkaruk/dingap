<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2003-2010 ClearFoundation
//
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
//////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// Load dependencies
///////////////////////////////////////////////////////////////////////////////

$this->load->helper('form');
$this->load->helper('url');
$this->load->library('form_validation');
$this->lang->load('network');
$this->lang->load('dhcp');

///////////////////////////////////////////////////////////////////////////////
// Form handler
///////////////////////////////////////////////////////////////////////////////
 
// Loop through subnet info and display it in HTML table
//------------------------------------------------------

foreach ($subnets as $interface => $subnetinfo) {

	if (! $subnetinfo["isvalid"]) {
		$status = "<span class='alert'>" . lang('base_invalid') . "</span>";
		$short_action = "<a href='/app/dhcp/subnets/edit/" . $interface . "'>$interface - $status</a>";
		$buttons = array(anchor_delete('/app/dhcp/subnets/delete/' . $interface));
	} else if ($subnetinfo["isconfigured"]) {
		$status = "<span class='ok'>" . lang('base_enabled') . "</span>";
		$short_action = "<a href='/app/dhcp/subnets/edit/" . $interface . "'>$interface - $status</a>";
		$buttons = array(
				anchor_edit('/app/dhcp/subnets/edit/' . $interface),
				anchor_delete('/app/dhcp/subnets/delete/' . $interface)
			);
	} else {
		$status = "<span class='alert'>" . lang('base_disabled') . "</span>";
		$short_action = "<a href='/app/dhcp/subnets/add/" . $interface . "'>$interface - $status</a>";
		$buttons = array(anchor_add('/app/dhcp/subnets/add/' . $interface));
	}

	$full_actions = button_set($buttons);

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

sort($items);

///////////////////////////////////////////////////////////////////////////////
// Form fields
///////////////////////////////////////////////////////////////////////////////

$headers = array(
	lang('network_interface'),
	lang('network_network'),
	lang('base_status'),
	''
);

echo summary_table_start(lang('dhcp_subnets'));
echo summary_table_header($headers);
echo summary_table_items($items);
echo summary_table_end();

// vim: ts=4 syntax=php
