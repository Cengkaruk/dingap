<?php

/**
 * Network interface settings view.
 *
 * @category   ClearOS
 * @package    Network
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network/
 */

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
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// Load dependencies
///////////////////////////////////////////////////////////////////////////////

$this->lang->load('network');
$this->lang->load('base');

///////////////////////////////////////////////////////////////////////////////
// Headers
///////////////////////////////////////////////////////////////////////////////

$headers = array(
	lang('network_interface'),
	lang('network_role'),
	lang('network_type'),
	lang('network_ip'),
	lang('network_link'),
	lang('network_speed')
);

///////////////////////////////////////////////////////////////////////////////
// Anchors 
///////////////////////////////////////////////////////////////////////////////

$anchors = array();

///////////////////////////////////////////////////////////////////////////////
// Items
///////////////////////////////////////////////////////////////////////////////

$items = array();

foreach ($network_interface as $interface => $detail) {
	// Skip interfaces used 'indirectly' (e.g. PPPoE, bonded interfaces)
	if (isset($detail['master']))
		continue;

	// Skip 1-to-1 NAT interfaces
	if (isset($detail['one-to-one-nat']) && $detail['one-to-one-nat'])
		continue;

	// Skip non-configurable interfaces
	if (! $detail['configurable'])
		continue;

	// Create summary
	$ip = empty($detail['address']) ? '' : $detail['address'];
	$speed = (isset($detail['speed']) && $detail['speed'] > 0) ? $detail['speed'] . " " . lang('base_megabits') : '';
	$role = isset($detail['role']) ? $detail['role'] : '';
	$roletext = isset($detail['roletext']) ? $detail['roletext'] : '';
	$bootproto = isset($detail['ifcfg']['bootprototext']) ? $detail['ifcfg']['bootprototext'] : '';

	if (isset($detail['link'])) {
		if ($detail['link'] == -1)
			$link = '';
		else if ($detail['link'] == 0)
			$link = lang('base_no');
		else
			$link = lang('base_yes');
	} else {
		$link = '';
	}

    if ($detail['configured']) {
        $buttons = array(
            anchor_edit('/app/network/iface/edit/' . $interface),
            anchor_delete('/app/network/iface/delete/' . $interface)
        );
    } else {
        $buttons = array(
            anchor_add('/app/network/iface/add/' . $interface),
        );
    }

    ///////////////////////////////////////////////////////////////////////////
    // Item details
    ///////////////////////////////////////////////////////////////////////////

	$item['title'] = $interface;
	$item['action'] = '';
	$item['anchors'] = button_set($buttons);
	$item['details'] = array(
		$interface,
        $roletext,
        $bootproto,
        $ip,
        $link,
        $speed
	);

	$items[] = $item;
}

///////////////////////////////////////////////////////////////////////////////
// Summary table
///////////////////////////////////////////////////////////////////////////////

echo summary_table(
	lang('network_interface'),
	$anchors,
	$headers,
	$items
);
