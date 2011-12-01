<?php

/**
 * Bandwith interface settings view.
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

///////////////////////////////////////////////////////////////////////////////
// Form
///////////////////////////////////////////////////////////////////////////////

echo form_open('/bandwidth/ifaces/edit/' . $iface);
echo form_header(lang('bandwidth_network_interface_settings'));

echo field_input('interface', $iface, lang('bandwidth_network_interface'), TRUE);
echo field_input('ip', $ip, lang('network_ip'), TRUE);
echo field_input('upstream', $upstream, lang('bandwidth_upload_(kilobits_s)'));
echo field_input('downstream', $downstream, lang('bandwidth_download_(kilobits_s)'));

echo field_button_set(
    array(
        form_submit_update('submit'),
        anchor_cancel('/app/bandwidth/ifaces')
    )
);

echo form_footer();
echo form_close();
