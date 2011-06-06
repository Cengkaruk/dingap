<?php

/**
 * Organization view.
 *
 * @category   ClearOS
 * @package    Organization
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/organization/
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
$this->lang->load('firewall');

///////////////////////////////////////////////////////////////////////////////
// Standard service
///////////////////////////////////////////////////////////////////////////////

echo form_open('/app/incoming_firewall/allow/add_service');
echo form_header(lang('firewall_standard_service'));

echo field_dropdown('service', $services, $service, lang('firewall_service'));

echo button_set(
    array(
        form_submit_add('standard', 'high'),
        anchor_cancel('/app/incoming_firewall/allow')
    )
);

echo form_footer();
echo form_close();

///////////////////////////////////////////////////////////////////////////////
// Port
///////////////////////////////////////////////////////////////////////////////

echo form_open('/app/incoming_firewall/allow/add_port');
echo form_header(lang('firewall_port'));

echo field_input('port_nickname', $port_nickname, lang('firewall_nickname'));
echo field_dropdown('port_protocol', $protocols, $port_protocol, lang('firewall_protocol'));
echo field_input('port', $port, lang('firewall_port'));

echo button_set(
    array(
        form_submit_add('port', 'high'),
        anchor_cancel('/app/incoming_firewall/allow')
    )
);

echo form_footer();
echo form_close();

///////////////////////////////////////////////////////////////////////////////
// Port range
///////////////////////////////////////////////////////////////////////////////

echo form_open('/app/incoming_firewall/allow/add_range');
echo form_header(lang('firewall_port_range'));

echo field_input('range_nickname', $range_nickname, lang('firewall_nickname'));
echo field_dropdown('range_protocol', $protocols, $range_protocol, lang('firewall_protocol'));
echo field_input('range_from', $range_from, lang('base_from'));
echo field_input('range_to', $range_To, lang('base_to'));

echo button_set(
    array(
        form_submit_add('port', 'high'),
        anchor_cancel('/app/incoming_firewall/allow')
    )
);

echo form_footer();
echo form_close();

