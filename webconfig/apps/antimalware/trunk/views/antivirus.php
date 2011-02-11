<?php

/**
 * Antivirus view.
 *
 * @category   ClearOS
 * @package    Antimalware
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/antimalware/
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

$this->lang->load('antimalware');
$this->lang->load('base');

///////////////////////////////////////////////////////////////////////////////
// Form open
///////////////////////////////////////////////////////////////////////////////

echo form_open('antimalware/antivirus');

///////////////////////////////////////////////////////////////////////////////
// Form fields
///////////////////////////////////////////////////////////////////////////////

echo form_fieldset(lang('antimalware_antivirus'));

$max_files_options[100] = 100;
$max_files_options[200] = 200;
$max_files_options[300] = 300;
$max_files_options[400] = 400;
$max_files_options[500] = 500;
$max_files_options[600] = 600;
$max_files_options[700] = 700;
$max_files_options[800] = 800;
$max_files_options[900] = 900;
$max_files_options[1000] = 1000;
$max_files_options[2000] = 2000;
$max_files_options[3000] = 3000;
$max_files_options[4000] = 4000;
$max_files_options[5000] = 5000;
$max_files_options[10000] = 10000;

$max_file_size_options[2] = 2 . ' ' . lang('base_megabytes');
$max_file_size_options[5] = 5 . ' ' . lang('base_megabytes');
$max_file_size_options[10] = 10 . ' ' . lang('base_megabytes');
$max_file_size_options[20] = 20 . ' ' . lang('base_megabytes');
$max_file_size_options[30] = 30 . ' ' . lang('base_megabytes');
$max_file_size_options[40] = 40 . ' ' . lang('base_megabytes');
$max_file_size_options[50] = 50 . ' ' . lang('base_megabytes');
$max_file_size_options[100] = 100 . ' ' . lang('base_megabytes');
$max_file_size_options[200] = 200 . ' ' . lang('base_megabytes');

$max_recursion_options[1] = 1;
$max_recursion_options[2] = 2;
$max_recursion_options[3] = 3;
$max_recursion_options[4] = 4;
$max_recursion_options[5] = 5;
$max_recursion_options[6] = 6;
$max_recursion_options[7] = 7;
$max_recursion_options[8] = 8;
$max_recursion_options[9] = 9;
$max_recursion_options[10] = 10;
$max_recursion_options[15] = 15;
$max_recursion_options[20] = 20;
$max_recursion_options[25] = 25;

$checks_options = array(
    '1' => lang('base_daily'),
    '2' => lang('antimalware_twice_a_day'),
    '12' => lang('antimalware_every_two_hours'),
    '24' => lang('base_hourly'),
);

echo field_toggle_enable_disable('block_encrypted', $block_encrypted, lang('antimalware_block_encrypted_files'));
echo field_dropdown('max_files', $max_files_options, $max_files, lang('antimalware_maximum_files'));
echo field_dropdown('max_file_size', $max_file_size_options, $max_file_size, lang('antimalware_maximum_file_size'));
echo field_dropdown('max_recursion', $max_recursion_options, $max_recursion, lang('antimalware_maximum_recursion'));
echo field_dropdown('checks', $checks_options, $checks, lang('antimalware_update_interval'));

echo form_fieldset_close();

///////////////////////////////////////////////////////////////////////////////
// Buttons
///////////////////////////////////////////////////////////////////////////////

echo button_set( array( form_submit_update('submit', 'high') ));

///////////////////////////////////////////////////////////////////////////////
// Form close
///////////////////////////////////////////////////////////////////////////////

echo form_close();
