<?php

/**
 * Mail Archive search stats.
 *
 * @category   Apps
 * @package    Mail_Archive
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearcenter.com/support/documentation/clearos/mail_archive/
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

$this->lang->load('base');
$this->lang->load('mail_archive');

///////////////////////////////////////////////////////////////////////////////
// Headers
///////////////////////////////////////////////////////////////////////////////

$headers = array(
    lang('mail_archive_filename'),
    lang('mail_archive_timestamp'),
    lang('mail_archive_file_size')
);

///////////////////////////////////////////////////////////////////////////////
// Anchors 
///////////////////////////////////////////////////////////////////////////////

$anchors = array();

///////////////////////////////////////////////////////////////////////////////
// Rules
///////////////////////////////////////////////////////////////////////////////

foreach ($rules as $rule) {
    $key = $rule['protocol'] . '/' . $rule['port'];
    $state = ($rule['enabled']) ? 'disable' : 'enable';
    $state_anchor = 'anchor_' . $state;

    $item['title'] = $rule['description'];

    if (empty($priority_buttons))
        $priority = '---';
    else
        $priority = button_set($priority_buttons);

    $item['anchors'] = button_set(
        array(
            $state_anchor('/app/firewall_custom/toggle/' . $rule['line']),
            anchor_delete('/app/firewall_custom/delete/' . $rule['line'])
        )
    );
    $brief = substr($rule['entry'], 0, 20);
    $item['details'] = array(
        $rule['description'],
        "<a href='#' class='view_rule' id='rule_id_" . $rule['line'] . "'>" . $brief . "...</a>",
        $priority,
    );

    $items[] = $item;
}

///////////////////////////////////////////////////////////////////////////////
// Summary table
///////////////////////////////////////////////////////////////////////////////

echo summary_table(
    lang('mail_archive_archives'),
    $anchors,
    $headers,
    $items
);
