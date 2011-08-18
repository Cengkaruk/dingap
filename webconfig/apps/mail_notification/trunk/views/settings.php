<?php

/**
 * Mail notification settings.
 *
 * @category   Apps
 * @package    Mail_Notification
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearcenter.com/support/documentation/clearos/mail_notification/
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
$this->lang->load('mail_notification');

///////////////////////////////////////////////////////////////////////////////
// Form open
///////////////////////////////////////////////////////////////////////////////

echo form_open('mail_notification/edit');
echo form_header(lang('mail_notification_settings'));

///////////////////////////////////////////////////////////////////////////////
// Form fields and buttons
///////////////////////////////////////////////////////////////////////////////

if ($mode === 'edit') {
    $read_only = FALSE;
    $buttons = array(
        form_submit_update('submit'),
        anchor_cancel('/app/mail_notification')
    );
} else {
    $read_only = TRUE;
    // FIXME: complete test feature
    //     anchor_custom('/app/mail_notification/test', lang('mail_notification_test'), 'high')
    $buttons = array(
        anchor_edit('/app/mail_notification/edit'),
    );
}

echo field_input('host', $host, lang('mail_notification_host'), $read_only);
echo field_input('port', $port, lang('mail_notification_port'), $read_only);
echo field_dropdown('ssl', $ssl_options, $ssl, lang('mail_notification_ssl'), $read_only);
echo field_input('username', $username, lang('mail_notification_username'), $read_only);
echo field_input('password', $password, lang('mail_notification_password'), $read_only);
echo field_input('sender', $sender, lang('mail_notification_sender'), $read_only);
echo field_button_set($buttons);

///////////////////////////////////////////////////////////////////////////////
// Form close
///////////////////////////////////////////////////////////////////////////////

echo form_footer();
echo form_close();
