
<?php

/**
 * Mail archive search.
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

echo form_open('mail_archive/search', array('autocomplete' => 'off'));
echo form_header(lang('mail_archive_settings'));

///////////////////////////////////////////////////////////////////////////////
// Form fields and buttons
///////////////////////////////////////////////////////////////////////////////

$buttons = array(
    form_submit_custom('search', lang('mail_archive_search')),
    anchor_cancel('/app/mail_archive')
);

echo field_dropdown('match', $match_options, $match, lang('mail_archive_match_options'), FALSE);
for ($index = 1; $index <=5; $index++) {
    echo fieldset_header(lang('mail_archive_filter') . ' #' . $index, array('id' => 'filter_' . $index));
    echo field_dropdown('field[' . $index . ']', ${'field_options_' . $index}, ${'field_' . $index}, lang('mail_archive_field'), FALSE);
    echo field_dropdown('pattern[' . $index . ']', ${'pattern_options_' . $index}, ${'pattern_' . $index}, lang('mail_archive_pattern'), FALSE);
    echo field_input('search[' . $index . ']', ${'search_' . $index}, lang('mail_archive_value'), FALSE);
}
echo field_button_set($buttons);

///////////////////////////////////////////////////////////////////////////////
// Form close
///////////////////////////////////////////////////////////////////////////////

echo form_footer();
echo form_close();

///////////////////////////////////////////////////////////////////////////////
// Headers
///////////////////////////////////////////////////////////////////////////////

$headers = array(
    lang('mail_archive_subject'),
    lang('mail_archive_from'),
    lang('mail_archive_date')
);

///////////////////////////////////////////////////////////////////////////////
// List table
///////////////////////////////////////////////////////////////////////////////

echo summary_table(
    lang('mail_archive_search_results'),
    NULL,
    $headers,
    NULL
);
