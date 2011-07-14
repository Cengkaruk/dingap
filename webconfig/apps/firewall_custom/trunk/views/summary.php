<?php

/**
 * Incoming firewall summary view.
 *
 * @category   ClearOS
 * @package    Incoming_Firewall
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/firewall_custom/
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

$this->lang->load('firewall_custom');
$this->lang->load('firewall');

///////////////////////////////////////////////////////////////////////////////
// Headers
///////////////////////////////////////////////////////////////////////////////

$headers = array(
    lang('base_description'),
    lang('firewall_custom_rule')
);

///////////////////////////////////////////////////////////////////////////////
// Anchors 
///////////////////////////////////////////////////////////////////////////////

$anchors = array(anchor_add('/app/firewall_custom/add'));

///////////////////////////////////////////////////////////////////////////////
// Rules
///////////////////////////////////////////////////////////////////////////////

foreach ($rules as $rule) {
    $key = $rule['protocol'] . '/' . $rule['port'];
    $state = ($rule['enabled']) ? 'disable' : 'enable';
    $state_anchor = 'anchor_' . $state;

    $item['title'] = $rule['description'];
    $item['action'] = '/app/firewall_custom/delete/' . $rule['line'];
    $item['anchors'] = button_set(
        array(
            $state_anchor('/app/firewall_custom/' . $rule['line'], 'high'),
            anchor_custom('/app/firewall_custom/up/' . $rule['line'], '+', 'low'),
            anchor_custom('/app/firewall_custom/down/' . $rule['line'], '-', 'low'),
            anchor_delete('/app/firewall_custom/delete/' . $rule['line'], 'low')
        )
    );
    $item['details'] = array(
        $rule['description'],
        "<a href='#' class='view_rule' id='rule_id_" . $rule['line'] . "'>" . substr($rule['entry'], 0, 20) . "...</a>"
    );

    $items[] = $item;
    $js[] = "rules['rule_id_" . $rule['line'] . "'] = '" . $rule['entry'] . "';";
}

///////////////////////////////////////////////////////////////////////////////
// Summary table
///////////////////////////////////////////////////////////////////////////////

sort($items);

echo summary_table(
    lang('firewall_custom_rules'),
    $anchors,
    $headers,
    $items
);
echo "<script type='text/javascript'>var rules = new Array();\n";
foreach ($js as $line) {
    echo $line . "\n";
}
echo "</script>";
