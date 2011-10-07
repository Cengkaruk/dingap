<?php

/**
 * Flexshare FTP view/edit view.
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
$form_path = '/flexshare/ftp/configure/' . $share['Name'];
$buttons = array(
    form_submit_update('submit'),
    anchor_cancel('/app/flexshare/'),
);

///////////////////////////////////////////////////////////////////////////////
// Form open
///////////////////////////////////////////////////////////////////////////////

echo form_open($form_path);
echo form_header(lang('flexshare_ftp'));

///////////////////////////////////////////////////////////////////////////////
// Form fields
///////////////////////////////////////////////////////////////////////////////

echo field_input('name', $share['Name'], lang('flexshare_share_name'), TRUE);
echo field_toggle_enable_disable('ftp_enabled', $share['FtpEnabled'], lang('base_status'), $read_only);
echo field_input('ftp_server_url', $share['FtpServerUrl'], lang('flexshare_hostname'), $read_only);
echo field_toggle_enable_disable('ftp_req_ssl', $share['FtpReqSsl'], lang('flexshare_ftp_require_ssl'), $read_only);
echo field_toggle_enable_disable('ftp_override_port', $share['FtpOverridePort'], lang('flexshare_ftp_override_port'), $read_only);
echo field_input('ftp_port', $share['FtpPort'], lang('flexshare_ftp_port'), $read_only);
echo field_toggle_enable_disable('ftp_allow_passive', $share['FtpAllowPassive'], lang('flexshare_ftp_allow_passive'), $read_only);
echo field_input('passive_min_port', $share['FtpPassivePortMin'], lang('flexshare_ftp_min_port'), $read_only);
echo field_input('passive_max_port', $share['FtpPassivePortMax'], lang('flexshare_ftp_max_port'), $read_only);
echo field_dropdown('group_permission', $group_permission_options, $share['FtpGroupPermission'], lang('flexshare_ftp_group_permissions'), $read_only);
echo field_textarea('group_greeting', $share['FtpGroupGreeting'], lang('flexshare_ftp_group_greeting'), $read_only);
echo field_toggle_enable_disable('allow_anonymous', $share['FtpAllowAnonymous'], lang('flexshare_ftp_allow_anonymous'), $read_only);
echo field_dropdown('anonymous_permission', $anonymous_permission_options, $share['FtpAnonymousPermission'], lang('flexshare_ftp_anonymous_permissions'), $read_only);
echo field_textarea('anonymous_greeting', $share['FtpAnonymousGreeting'], lang('flexshare_ftp_anonymous_greeting'), $read_only);

echo field_button_set($buttons);

///////////////////////////////////////////////////////////////////////////////
// Form close
///////////////////////////////////////////////////////////////////////////////

echo form_footer();
echo form_close();
