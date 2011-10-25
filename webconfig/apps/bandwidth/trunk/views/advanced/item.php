<?php

/**
 * Bandwith advanced rule view.
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

$this->lang->load('bandwidth');
$this->lang->load('network');
$this->lang->load('firewall');

///////////////////////////////////////////////////////////////////////////////
// Form
///////////////////////////////////////////////////////////////////////////////

echo form_open('/bandwidth/advanced/add');
echo form_header(lang('bandwidth_advanced_rule'));

echo fieldset_header(lang('bandwidth_rule'));
echo field_input('name', $name, lang('firewall_nickname'));
echo field_dropdown('direction', $directions, $direction, lang('bandwidth_direction'));

if (count($interfaces) > 1) {
    array_unshift($interfaces, lang('base_all'));
    echo field_simple_dropdown('iface', $interfaces, $iface, lang('bandwidth_network_interface'));
} else {
    echo field_input('iface', $interfaces[0], lang('network_interface'), TRUE);
}

echo fieldset_footer();

echo fieldset_header(lang('network_ip'));
echo field_dropdown('ip_type', $types, $ip_type, lang('bandwidth_type'));
echo field_input('ip', $ip, lang('network_ip'));
echo fieldset_footer();

echo fieldset_header(lang('network_port'));
echo field_dropdown('port_type', $types, $port_type, lang('bandwidth_type'));
echo field_input('port', $port, lang('network_port'));
echo fieldset_footer();


echo fieldset_header(lang('bandwidth_bandwidth'));
echo field_input('rate', $rate, lang('bandwidth_rate'));
echo field_input('ceiling', $ceiling, lang('bandwidth_ceiling'));
echo field_dropdown('priority', $priorities, $priority, lang('bandwidth_greed'));
echo fieldset_footer();

echo field_button_set(
    array(
        form_submit_add('submit'),
        anchor_cancel('/app/bandwidth/advanced')
    )
);

echo form_footer();
echo form_close();
