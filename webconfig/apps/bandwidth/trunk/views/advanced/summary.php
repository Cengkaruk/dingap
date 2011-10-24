<?php

/**
 * Bandwidth advanced rules view.
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
    lang('bandwidth_mode'),
    lang('bandwidth_service'),
    lang('bandwidth_direction'),
    lang('bandwidth_rate'),
    lang('bandwidth_greed'),
);

///////////////////////////////////////////////////////////////////////////////
// Anchors 
///////////////////////////////////////////////////////////////////////////////

$anchors = array(anchor_add('/app/bandwidth/advanced/add'));

///////////////////////////////////////////////////////////////////////////////
// Items
///////////////////////////////////////////////////////////////////////////////

foreach ($rules as $id => $details) {
    $key = $details['name'];
    $state = ($details['enabled']) ? 'disable' : 'enable';
    $state_anchor = 'anchor_' . $state;

    $item['title'] = $details['name'];
    $item['action'] = '/app/bandwidth/advanced/delete/' . $key;
    $item['anchors'] = button_set(
        array(
            $state_anchor('/app/bandwidth/advanced/' . $state . '/' . $key, 'high'),
            anchor_delete('/app/bandwidth/advanced/delete/' . $key . '/' . $details['service'] . '/' . $details['upstream'], 'low')
        )
    );
    $item['details'] = array(
        $details['mode_text'],
        $details['service'],
        $details['direction_text'],
        $details['upstream'],
        $details['priority_text'],
    );

    $items[] = $item;
}

sort($items);

///////////////////////////////////////////////////////////////////////////////
// Summary table
///////////////////////////////////////////////////////////////////////////////

echo summary_table(
    lang('bandwidth_advanced_rules'),
    $anchors,
    $headers,
    $items
);
