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

use \clearos\apps\network\Iface as Iface;

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
);

///////////////////////////////////////////////////////////////////////////////
// Anchors 
///////////////////////////////////////////////////////////////////////////////

$anchors = array(
    // TODO: anchor_custom('/app/network/iface/add_virtual', lang('network_add_virtual_interface'))
);

///////////////////////////////////////////////////////////////////////////////
// Items
///////////////////////////////////////////////////////////////////////////////

$items = array();

foreach ($network_interface as $interface => $detail) {
	// Create summary
	$ip = empty($detail['address']) ? '' : $detail['address'];
	$speed = (isset($detail['speed']) && $detail['speed'] > 0) ? $detail['speed'] . " " . lang('base_megabits') : '';
	$role = isset($detail['roletext']) ? $detail['roletext'] : '';
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


    // Network types
/*
    const TYPE_BONDED = 'Bonded';
    const TYPE_BONDED_SLAVE = 'BondedChild';
    const TYPE_BRIDGED = 'Bridge';
    const TYPE_BRIDGED_SLAVE = 'BridgeChild';
    const TYPE_ETHERNET = 'Ethernet';
    const TYPE_PPPOE = 'xDSL';
    const TYPE_PPPOE_SLAVE = 'PPPoEChild';
    const TYPE_UNKNOWN = 'Unknown';
    const TYPE_VIRTUAL = 'Virtual';
    const TYPE_VLAN = 'VLAN';
    const TYPE_WIRELESS = 'Wireless';
*/

    // Behavior when interface is configured
    //--------------------------------------

    if ($detail['configured']) {

        // Show edit/delete for supported Ethernet and PPPoE types
        //--------------------------------------------------------

        if (($detail['type'] === Iface::TYPE_ETHERNET) || ($detail['type'] === Iface::TYPE_PPPOE)) {
            $buttons = array(
                anchor_edit('/app/network/iface/edit/' . $interface),
                anchor_delete('/app/network/iface/delete/' . $interface)
            );

        // Show view for bridged, bonded, and wireless types
        //--------------------------------------------------

        } else if (($detail['type'] === Iface::TYPE_BONDED)
            || ($detail['type'] === Iface::TYPE_BRIDGED)
            || ($detail['type'] === Iface::TYPE_WIRELESS)) 
        {
            $buttons = array(
                anchor_view('/app/network/iface/view/' . $interface),
            );

        // Skip all other unsupported types
        //---------------------------------

        } else {
            continue;
        }

    // Behavior when interface is not configured
    //------------------------------------------

    } else {
        // Show only Ethernet interfaces
        //------------------------------

        if ($detail['type'] === Iface::TYPE_ETHERNET) {
            $buttons = array(
                anchor_add('/app/network/iface/add/' . $interface),
            );

        // Skip all other unsupported types
        //---------------------------------

        } else {
            continue;
        }
    }

    ///////////////////////////////////////////////////////////////////////////
    // Item details
    ///////////////////////////////////////////////////////////////////////////

	$item['title'] = $interface;
	$item['action'] = '';
	$item['anchors'] = button_set($buttons);
	$item['details'] = array(
		$interface,
        "<span id='role_" . $interface . "'>$role</span>",
        "<span id='bootproto_" . $interface . "'>$bootproto</span>",
        "<span id='ip_" . $interface . "'>$ip</span>",
        "<span id='link_" . $interface . "'>$link</span>",
	);

	$items[] = $item;
}

///////////////////////////////////////////////////////////////////////////////
// Summary table
///////////////////////////////////////////////////////////////////////////////

echo summary_table(
	lang('network_interfaces'),
	$anchors,
	$headers,
	$items,
    array('id' => 'network_summary')
);
