
<?php

/**
 * Mail archive settings.
 *
 * @category   Apps
 * @package    Mail_Archive
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearcenter.com/support/documentation/clearos/mail_archive/
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
$this->lang->load('mail_archive');

///////////////////////////////////////////////////////////////////////////////
// Form open
///////////////////////////////////////////////////////////////////////////////

echo form_open('mail_archive/edit', array('autocomplete' => 'off'));
echo form_header(lang('mail_archive_settings'));

///////////////////////////////////////////////////////////////////////////////
// Form fields and buttons
///////////////////////////////////////////////////////////////////////////////

if ($mode === 'edit') {
    $read_only = FALSE;
    $buttons = array(
        form_submit_update('submit'),
        anchor_cancel('/app/mail_archive')
    );
} else {
    $read_only = TRUE;
    $buttons = array(
        anchor_edit('/app/mail_archive/edit'),
        anchor_custom('/app/mail_archive/current_archive', lang('mail_archive_current_stats'), 'high'),
        anchor_custom('/app/mail_archive/search_archive', lang('mail_archive_search_stats'), 'high')
    );
}

echo field_toggle_enable_disable('archive_status', $archive_status, lang('mail_archive_mail_archive'), $read_only);
echo field_dropdown('discard_attachments', $discard_attachments_options, $discard_attachments, lang('mail_archive_discard_attachments'), $read_only);
echo field_dropdown('auto_archive', $auto_archive_options, $auto_archive, lang('mail_archive_auto_archive'), $read_only);
echo field_toggle_enable_disable('encrypt', $encrypt, lang('mail_archive_encrypt'), $read_only);
echo field_password('encrypt_password', $encrypt_password, lang('mail_archive_encrypt_password'), $read_only);
echo field_button_set($buttons);

///////////////////////////////////////////////////////////////////////////////
// Form close
///////////////////////////////////////////////////////////////////////////////

echo form_footer();
echo form_close();
