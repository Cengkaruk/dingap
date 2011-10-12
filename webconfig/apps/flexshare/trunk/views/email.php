<?php

/**
 * Flexshare Email edit view.
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
$form_path = '/flexshare/email/configure/' . $share['Name'];
$buttons = array(
    form_submit_update('submit'),
    anchor_cancel('/app/flexshare/edit/' . $share['Name']),
);

///////////////////////////////////////////////////////////////////////////////
// Form open
///////////////////////////////////////////////////////////////////////////////

echo form_open($form_path);
echo form_header(lang('flexshare_email'));

///////////////////////////////////////////////////////////////////////////////
// Form fields
///////////////////////////////////////////////////////////////////////////////

echo field_view(lang('flexshare_share_name'), $share['Name']);
echo field_toggle_enable_disable('enabled', $share['EmailEnabled'], lang('base_status'), $read_only);
echo field_view(lang('flexshare_email_address'), $email_address);
echo field_dropdown('dir', $dir_options, $share['EmailDir'], lang('flexshare_email_dir'), $read_only);
echo field_dropdown('policy', $policy_options, $share['EmailPolicy'], lang('flexshare_email_policy'), $read_only);
echo field_dropdown('save', $save_options, $share['EmailSave'], lang('flexshare_email_save'), $read_only);
echo field_input('notify', $share['EmailNotify'], lang('flexshare_notify_on_receive'), $read_only);
echo field_toggle_enable_disable('restrict_access', $share['EmailRestrictAccess'], lang('flexshare_email_restrict_access'), $read_only);
echo field_textarea('acl', preg_replace("/ /", "\n", $share['EmailAcl']), lang('flexshare_email_acl'), $read_only);
echo field_toggle_enable_disable('req_signature', $share['EmailReqSignature'], lang('flexshare_email_require_sig'), $read_only);

echo field_button_set($buttons);

///////////////////////////////////////////////////////////////////////////////
// Form close
///////////////////////////////////////////////////////////////////////////////

echo form_footer();
echo form_close();
