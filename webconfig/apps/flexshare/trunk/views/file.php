<?php

/**
 * Flexshare File edit view.
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

echo field_view(lang('flexshare_share_name'), $share['Name']);
echo field_toggle_enable_disable('enabled', $share['FileEnabled'], lang('base_status'), $read_only);
echo field_view(lang('flexshare_file_server_url'), $server_url);
echo field_input('comment', $share['FileComment'], lang('flexshare_file_comment'), $read_only);
echo field_dropdown('file_permissions', $permission_options, $share['FilePermission'], lang('flexshare_file_permissions'), $read_only);
echo field_toggle_enable_disable('recycle_bin', $share['FileRecycleBin'], lang('flexshare_file_recyle_bin'), $read_only);
echo field_toggle_enable_disable('audit_log', $share['FileAuditLog'], lang('flexshare_file_audit_log'), $read_only);

echo field_button_set($buttons);

///////////////////////////////////////////////////////////////////////////////
// Form close
///////////////////////////////////////////////////////////////////////////////

echo form_footer();
echo form_close();
