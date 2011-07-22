<?php

/**
 * System time manager view.
 *
 * @category   ClearOS
 * @package    Greylisting
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/greylisting/
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

$this->lang->load('greylisting');

$delay_options = array(
    '120' => '2 ' . lang('base_minutes'),
    '180' => '3 ' . lang('base_minutes'),
    '240' => '4 ' . lang('base_minutes'),
    '300' => '5 ' . lang('base_minutes') . ' - ' . lang('base_default'),
    '360' => '6 ' . lang('base_minutes'),
    '420' => '7 ' . lang('base_minutes'),
    '480' => '8 ' . lang('base_minutes'),
    '540' => '9 ' . lang('base_minutes'),
    '600' => '10 ' . lang('base_minutes'),
    '1200' => '20 ' . lang('base_minutes'),
    '1800' => '30 ' . lang('base_minutes'),
    '2400' => '40 ' . lang('base_minutes'),
    '3000' => '50 ' . lang('base_minutes'),
    '3600' => '60 ' . lang('base_minutes'),
);

$retention_time_options = array(
    '5' => '5 ' . lang('base_days'),
    '10' => '10 ' . lang('base_days'),
    '15' => '15 ' . lang('base_days'),
    '20' => '20 ' . lang('base_days'),
    '25' => '25 ' . lang('base_days'),
    '30' => '30 ' . lang('base_days'),
    '35' => '35 ' . lang('base_days') . ' - ' . lang('base_default'),
    '40' => '40 ' . lang('base_days'),
    '45' => '45 ' . lang('base_days'),
    '50' => '50 ' . lang('base_days'),
    '55' => '55 ' . lang('base_days'),
    '60' => '60 ' . lang('base_days'),
);

///////////////////////////////////////////////////////////////////////////////
// Form open
///////////////////////////////////////////////////////////////////////////////

echo form_open('greylisting');
echo form_header(lang('greylisting_greylisting'));

///////////////////////////////////////////////////////////////////////////////
// Form fields
///////////////////////////////////////////////////////////////////////////////

echo field_dropdown('delay', $delay_options, $delay, lang('greylisting_delay'));
echo field_dropdown('retention_time', $retention_time_options, $retention_time, lang('greylisting_retention_time'));

echo field_button_set(
    array(form_submit_update('submit', 'high'))
);

///////////////////////////////////////////////////////////////////////////////
// Form close
///////////////////////////////////////////////////////////////////////////////

echo form_footer();
echo form_close();
