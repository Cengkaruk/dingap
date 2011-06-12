<?php

/**
 * DHCP edit lease view.
 *
 * @category   ClearOS
 * @package    DHCP
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/dhcp/
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
$this->lang->load('dhcp');

///////////////////////////////////////////////////////////////////////////////
// Form open
///////////////////////////////////////////////////////////////////////////////

echo form_open('dhcp/leases/edit/' . $lease['mac'] . '/' . $lease['ip']); 
echo form_header(lang('dhcp_lease'));

///////////////////////////////////////////////////////////////////////////////
// Form fields and buttons
///////////////////////////////////////////////////////////////////////////////

$expires = ($lease['end'] === 0) ? lang('dhcp_never') : strftime('%c', $lease['end']);

echo field_input('end', $expires, lang('dhcp_expires'), TRUE);
echo field_input('mac', $lease['mac'], lang('network_mac_address'), TRUE);
echo field_input('vendor', $lease['vendor'], lang('dhcp_vendor'), TRUE);
echo field_input('hostname', $lease['hostname'], lang('network_hostname'), TRUE);
echo field_input('ip', $lease['ip'], lang('network_ip'));
echo field_dropdown('type', $types, $lease['type'], lang('dhcp_lease'));

echo button_set(
    array(
        form_submit_update('submit', 'high'),
        anchor_cancel('/app/dhcp/leases')
    )
);

///////////////////////////////////////////////////////////////////////////////
// Form close
///////////////////////////////////////////////////////////////////////////////

echo form_footer(); 
echo form_close();
