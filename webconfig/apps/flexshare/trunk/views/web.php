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
    anchor_cancel('/app/flexshare/edit/' . $share['Name']),
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
echo field_toggle_enable_disable('enabled', $share['WebEnabled'], lang('base_status'), $read_only);
echo field_input('server_name', $server_name, lang('flexshare_web_server_name'), TRUE);
echo field_view(lang('flexshare_web_server_url'), $server_url[0]);
echo field_view(lang('flexshare_web_server_url_alt'), $server_url[1]);
echo field_dropdown('web_access', $accessibility_options, $share['WebAccess'], lang('flexshare_web_accessibility'), $read_only);
echo field_toggle_enable_disable('show_index', $share['WebShowIndex'], lang('flexshare_web_show_index'), $read_only);
echo field_toggle_enable_disable('follow_sym_links', $share['WebFollowSymLinks'], lang('flexshare_web_follow_sym_links'), $read_only);
echo field_toggle_enable_disable('ssi', $share['WebAllowSSI'], lang('flexshare_web_allow_ssi'), $read_only);
echo field_toggle_enable_disable('htaccess', $share['WebHtaccessOverride'], lang('flexshare_web_allow_htaccess'), $read_only);
echo field_toggle_enable_disable('req_ssl', $share['WebReqSsl'], lang('flexshare_web_require_ssl'), $read_only);
echo field_toggle_enable_disable('override_port', $share['WebOverridePort'], lang('flexshare_web_override_default_port'), $read_only);
echo field_input('port', $share['WebPort'], lang('flexshare_port'), $read_only);
echo field_toggle_enable_disable('req_auth', $share['WebReqAuth'], lang('flexshare_web_require_auth'), $read_only);
echo field_input('realm', $share['WebRealm'], lang('flexshare_web_realm'), $read_only);
echo field_toggle_enable_disable('php', $share['WebPhp'], lang('flexshare_web_enable_php'), $read_only);
echo field_toggle_enable_disable('cgi', $share['WebCgi'], lang('flexshare_web_enable_cgi'), $read_only);

echo field_button_set($buttons);

///////////////////////////////////////////////////////////////////////////////
// Form close
///////////////////////////////////////////////////////////////////////////////

echo form_footer();
echo form_close();
