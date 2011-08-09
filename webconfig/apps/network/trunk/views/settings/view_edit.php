<?php

/**
 * Network general settings view.
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
// Form handler
///////////////////////////////////////////////////////////////////////////////

if ($form_type === 'edit') {
	$read_only = FALSE;
	$buttons = array(
		form_submit_update('submit'),
		anchor_cancel('/app/network')
	);
} else {
	$read_only = TRUE;
	$buttons = array(anchor_edit('/app/network/settings/edit'));
}

$dns_count = count($dns);

// Always show at least 1 DNS server
if ($dns_count === 0)
    $dns_count = 1;

// Append a field for adding a DNS server
if (! $read_only)
    $dns_count++;

///////////////////////////////////////////////////////////////////////////////
// Form open
///////////////////////////////////////////////////////////////////////////////

echo form_open('network/settings/edit'); 
echo form_header(lang('base_settings'));

///////////////////////////////////////////////////////////////////////////////
// Form fields and buttons
///////////////////////////////////////////////////////////////////////////////

echo field_dropdown('network_mode', $network_modes, $network_mode, lang('network_mode'), $read_only);
echo field_input('hostname', $hostname, lang('network_hostname'), $read_only);

for ($inx = 1; $inx < $dns_count + 1; $inx++) {
    $dns_server = isset($dns[$inx-1]) ? $dns[$inx-1] : '';
    echo field_input('dns[' . $inx . ']', $dns_server, lang('network_dns_server') . " #" . $inx, $read_only);
}

echo field_button_set($buttons);

///////////////////////////////////////////////////////////////////////////////
// Form close
///////////////////////////////////////////////////////////////////////////////

echo form_footer();
echo form_close();
