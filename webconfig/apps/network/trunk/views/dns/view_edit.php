<?php

/**
 * Network DNS server view.
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
    if ($is_automatic) {
        $read_only = TRUE;
        $is_automatic_warning = TRUE;
        $buttons = array(
            anchor_cancel('/app/network')
        );
    } else {
        $read_only = FALSE;
        $is_automatic_warning = FALSE;
        $buttons = array(
            form_submit_update('submit'),
            anchor_cancel('/app/network')
        );
    }

} else {
	$read_only = TRUE;
    $is_automatic_warning = FALSE;
    if (! $is_automatic)
        $buttons = array(anchor_edit('/app/network/dns/edit'));
}

$dns_count = count($dns);
$dns_fields = $dns_count;

// Append a field for adding a DNS server
if (! $read_only) {
    $dns_fields++;

    // Always show at least 1 DNS server
    if ($dns_fields < 3)
        $dns_fields = 3;
}

///////////////////////////////////////////////////////////////////////////////
// Warnings
///////////////////////////////////////////////////////////////////////////////

if (! $read_only) {
    if ($dns_count === 0)
        echo infobox_warning(lang('network_network_degraded'), lang('network_no_dns_servers_warning'));
    else if ($dns_count === 3)
        echo infobox_highlight(lang('network_best_practices'), lang('network_too_many_dns_servers_warning'));
    else if ($dns_count > 3)
        echo infobox_warning(lang('network_network_degraded'), lang('network_too_many_dns_servers_warning'));

}

if ($is_automatic_warning)
    echo infobox_highlight(lang('network_dns_automatically_configured'), lang('network_dns_automatically_configured_message'));

///////////////////////////////////////////////////////////////////////////////
// Form open
///////////////////////////////////////////////////////////////////////////////

echo form_open('network/dns/edit'); 
echo form_header(lang('network_dns'));

///////////////////////////////////////////////////////////////////////////////
// Form fields and buttons
///////////////////////////////////////////////////////////////////////////////

for ($inx = 1; $inx < $dns_fields + 1; $inx++) {
    $dns_server = isset($dns[$inx-1]) ? $dns[$inx-1] : '';
    echo field_input('dns[' . $inx . ']', $dns_server, lang('network_dns_server') . " #" . $inx, $read_only);
}

if (! empty($buttons))
    echo field_button_set($buttons);

///////////////////////////////////////////////////////////////////////////////
// Form close
///////////////////////////////////////////////////////////////////////////////

echo form_footer();
echo form_close();
