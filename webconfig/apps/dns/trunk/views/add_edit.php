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

///////////////////////////////////////////////////////////////////////////////
// Form fields
///////////////////////////////////////////////////////////////////////////////

echo form_fieldset(lang('dns_dns_entry'));

echo field_input('ip', $ip, lang('network_ip'), $read_only);
echo field_input('hostname', $hostname, lang('network_hostname'));
echo field_input('alias1', $aliases[0], lang('dns_alias') . " #1");
echo field_input('alias2', $aliases[1], lang('dns_alias') . " #2");
echo field_input('alias3', $aliases[2], lang('dns_alias') . " #3");

echo form_fieldset_close();

///////////////////////////////////////////////////////////////////////////////
// Form buttons
///////////////////////////////////////////////////////////////////////////////

echo button_set($buttons);

///////////////////////////////////////////////////////////////////////////////
// Form close
///////////////////////////////////////////////////////////////////////////////

echo form_close();

// vim: ts=4 syntax=php
