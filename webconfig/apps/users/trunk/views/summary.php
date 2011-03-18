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

$this->lang->load('users');

///////////////////////////////////////////////////////////////////////////////
// Headers
///////////////////////////////////////////////////////////////////////////////

$headers = array(
	lang('users_username'),
	lang('users_fullname'),
	lang('users_apps')
);

///////////////////////////////////////////////////////////////////////////////
// Anchors 
///////////////////////////////////////////////////////////////////////////////

$anchors = array();

///////////////////////////////////////////////////////////////////////////////
// Items
///////////////////////////////////////////////////////////////////////////////

foreach ($subnets as $interface => $subnetinfo) {

	if (! $subnetinfo["isvalid"]) {
		$status = "<span class='alert'>" . lang('base_invalid') . "</span>";
		$action = "/app/dhcp/subnets/edit/" . $interface;
		$buttons = array(anchor_delete('/app/dhcp/subnets/delete/' . $interface));
	} else if ($subnetinfo["isconfigured"]) {
		$status = "<span class='ok'>" . lang('base_enabled') . "</span>";
		$action = "/app/dhcp/subnets/edit/" . $interface;
		$buttons = array(
				anchor_edit('/app/dhcp/subnets/edit/' . $interface),
				anchor_delete('/app/dhcp/subnets/delete/' . $interface)
			);
	} else {
		$status = "<span class='alert'>" . lang('base_disabled') . "</span>";
		$action = "/app/dhcp/subnets/add/" . $interface;
		$buttons = array(anchor_add('/app/dhcp/subnets/add/' . $interface));
	}

    ///////////////////////////////////////////////////////////////////////////
    // Item details
    ///////////////////////////////////////////////////////////////////////////

	$item['title'] = "$interface / " .  $subnetinfo['network'];
	$item['action'] = $action;
	$item['anchors'] = button_set($buttons);
	$item['details'] = array(
		$interface,
		$subnetinfo['network'],
		$status
	);

	$items[] = $item;
}

sort($items);

///////////////////////////////////////////////////////////////////////////////
// Summary table
///////////////////////////////////////////////////////////////////////////////

echo summary_table(
	lang('users_user_manager'),
	$anchors,
	$headers,
	$items
);
