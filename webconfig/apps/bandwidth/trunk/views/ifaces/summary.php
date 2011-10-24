<?php

/**
 * Bandwidth interfaces view.
 *
 * @category   ClearOS
 * @package    Bandwidth
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/bandwidth/
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
$this->lang->load('bandwidth');

///////////////////////////////////////////////////////////////////////////////
// Headers
///////////////////////////////////////////////////////////////////////////////

$headers = array(
    lang('network_interface'),
    lang('bandwidth_upload_(kilobits_s)'),
    lang('bandwidth_download_(kilobits_s)'),
);

///////////////////////////////////////////////////////////////////////////////
// Anchors 
///////////////////////////////////////////////////////////////////////////////

$anchors = array();

///////////////////////////////////////////////////////////////////////////////
// Items
///////////////////////////////////////////////////////////////////////////////

foreach ($ifaces as $interface => $info) {

    $action = "/app/bandwidth/ifaces/edit/" . $interface;

    if ($info['configured']) {
        $buttons = array(anchor_edit('/app/bandwidth/ifaces/edit/' . $interface));
        $upstream = $info['upstream'];
        $downstream = $info['downstream'];
    } else {
        $buttons = array(anchor_configure('/app/bandwidth/ifaces/edit/' . $interface));
        $upstream = '';
        $downstream = '';
    }

    ///////////////////////////////////////////////////////////////////////////
    // Item details
    ///////////////////////////////////////////////////////////////////////////

    $item['title'] = $interface;
    $item['action'] = $action;
    $item['anchors'] = button_set($buttons);
    $item['details'] = array(
        $interface,
        $upstream,
        $downstream
    );

    $items[] = $item;
}

sort($items);

///////////////////////////////////////////////////////////////////////////////
// Summary table
///////////////////////////////////////////////////////////////////////////////

echo summary_table(
    lang('bandwidth_network_interfaces'),
    $anchors,
    $headers,
    $items
);
