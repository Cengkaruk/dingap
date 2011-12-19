<?php

/**
 * Mail Archive live stats.
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

echo form_open('mail_archive/current_archive');
echo form_header(lang('mail_archive_stats'));

///////////////////////////////////////////////////////////////////////////////
// Form fields and buttons
///////////////////////////////////////////////////////////////////////////////

$buttons = array(
    anchor_custom('/app/mail_archive/search', lang('mail_archive_search')),
    anchor_custom('/app/mail_archive/archive', lang('mail_archive_save_to_archive'))
);

echo field_input('estimated_size', $stats['size'], lang('mail_archive_estimated_size'), TRUE);
echo field_input('last_archived', $stats['last'], lang('mail_archive_last_archived'), TRUE);
echo field_input('total_messages', $stats['messages'], lang('mail_archive_total_messages'), TRUE);
echo field_input('total_attachments', $stats['attachments'], lang('mail_archive_total_attachments'), TRUE);
echo field_button_set($buttons);

///////////////////////////////////////////////////////////////////////////////
// Form close
///////////////////////////////////////////////////////////////////////////////

echo form_footer();
echo form_close();
