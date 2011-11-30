<?php

/**
 * Local DNS Server add/edit view.
 *
 * @category   ClearOS
 * @package    DNS
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/dns/
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

$this->load->language('dns');
$this->load->language('network');

///////////////////////////////////////////////////////////////////////////////
// Form modes
///////////////////////////////////////////////////////////////////////////////

if ($form_type === 'edit') {
    $read_only = TRUE;
    $form_path = '/dns/edit';
    $buttons = array(
        form_submit_update('submit'),
        anchor_cancel('/app/dns/'),
        anchor_delete('/app/dns/delete/' . $ip)
    );
} else {
    $read_only = FALSE;
    $form_path = '/dns/add';
    $buttons = array(
        form_submit_add('submit'),
        anchor_cancel('/app/dns/')
    );
}

///////////////////////////////////////////////////////////////////////////////
// Form open
///////////////////////////////////////////////////////////////////////////////

echo form_open($form_path . '/' . $ip);
echo form_header(lang('dns_dns_entry'));

///////////////////////////////////////////////////////////////////////////////
// Form fields
///////////////////////////////////////////////////////////////////////////////

echo field_input('ip', $ip, lang('network_ip'), $read_only);
echo field_input('hostname', $hostname, lang('network_hostname'));

$alias_count = count($aliases);

for ($inx = 1; $inx < $alias_count + 5; $inx++)
    echo field_input('alias' . $inx, $aliases[$inx-1], lang('dns_alias') . " #" . $inx);

echo field_button_set($buttons);

///////////////////////////////////////////////////////////////////////////////
// Form close
///////////////////////////////////////////////////////////////////////////////

echo form_footer();
echo form_close();
