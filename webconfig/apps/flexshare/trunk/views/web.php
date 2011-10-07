<?php

/**
 * Flexshare Web edit view.
 *
 * @category   Apps
 * @package    Flexshare
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/flexshare/
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

$this->load->language('flexshare');

///////////////////////////////////////////////////////////////////////////////
// Form modes
///////////////////////////////////////////////////////////////////////////////

$read_only = FALSE;
$form_path = '/flexshare/web/configure/' . $share['Name'];
$buttons = array(
    form_submit_update('submit'),
    anchor_cancel('/app/flexshare/'),
);

///////////////////////////////////////////////////////////////////////////////
// Form open
///////////////////////////////////////////////////////////////////////////////

echo form_open($form_path);
echo form_header(lang('flexshare_web'));

///////////////////////////////////////////////////////////////////////////////
// Form fields
///////////////////////////////////////////////////////////////////////////////

echo field_input('name', $share['Name'], lang('flexshare_share_name'), TRUE);
echo field_input('server_name', $share['WebServerName'], lang('flexshare_server_name'), TRUE);
echo field_input('server_url', $share['WebServerUrl'], lang('flexshare_hostname'), TRUE);
echo field_dropdown('accessibility', $share['WebEnabled'], lang('base_status'), $read_only);
echo field_toggle_enable_disable('show_index', $share['WebReqSsl'], lang('flexshare_web_require_ssl'), $read_only);
echo field_toggle_enable_disable('follow_sym_links', $share['WebOverridePort'], lang('flexshare_web_override_port'), $read_only);
echo field_toggle_enable_disable('ssi', $share['WebAllowPassive'], lang('flexshare_web_allow_passive'), $read_only);
echo field_toggle_enable_disable('htaccess', $share['WebAccess'], lang('flexshare_web_allow_passive'), $read_only);
echo field_toggle_enable_disable('req_ssl', $share['WebReqSsl'], lang('flexshare_web_allow_passive'), $read_only);
echo field_toggle_enable_disable('default_port', $share['WebOverridePort'], lang('flexshare_web_allow_passive'), $read_only);
echo field_input('port', $share['WebPort'], lang('flexshare_web_port'), $read_only);
echo field_toggle_enable_disable('required_auth', $share['WebRequireAuth'], lang('flexshare_web_allow_passive'), $read_only);
echo field_input('realm', $share['WebRealm'], lang('flexshare_web_max_port'), $read_only);
echo field_toggle_enable_disable('php', $share['WebPhp'], lang('flexshare_web_allow_passive'), $read_only);
echo field_toggle_enable_disable('cgi', $share['WebCgi'], lang('flexshare_web_allow_passive'), $read_only);

echo field_button_set($buttons);

///////////////////////////////////////////////////////////////////////////////
// Form close
///////////////////////////////////////////////////////////////////////////////

echo form_footer();
echo form_close();
