<?php

/**
 * Account Import/Export overview.
 *
 * @category   Apps
 * @package    Account Import
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearcenter.com/support/documentation/clearos/account_import/
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
$this->lang->load('account_import');

///////////////////////////////////////////////////////////////////////////////
// Form open
///////////////////////////////////////////////////////////////////////////////

echo form_open_multipart('account_import/upload');
echo form_header(lang('account_import_users'));

///////////////////////////////////////////////////////////////////////////////
// Form fields and buttons
///////////////////////////////////////////////////////////////////////////////


if ($import_ready)
    $buttons = array(
        form_submit_custom('start', lang('account_import_start_import'), 'high'),
        form_submit_custom('reset', lang('base_reset'), 'low')
    );
else
    $buttons = array(
        form_submit_custom('upload', lang('account_import_upload'), 'high'),
        anchor_custom('account_import/template', lang('account_import_download_template'), 'low')
    );

echo field_file('csv_file', $filename, lang('account_import_csv_file'), $import_ready);

if ($import_ready) {
    echo field_file('size', $size, lang('account_import_size'), $import_ready);
    echo field_file('number', $number_of_records, lang('account_import_number_of_records'), $import_ready);
}

echo button_set($buttons);

///////////////////////////////////////////////////////////////////////////////
// Form close
///////////////////////////////////////////////////////////////////////////////

echo form_footer();
echo form_close();
