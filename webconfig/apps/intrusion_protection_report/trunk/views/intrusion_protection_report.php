<?php

/**
 * Intrusion protection report view.
 *
 * @category   Apps
 * @package    Intrusion_Protection_Report
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/intrusion_protection_report/
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

///////////////////////////////////////////////////////////////////////////////
// Form open
///////////////////////////////////////////////////////////////////////////////

echo form_open('intrusion_protection_report');
echo form_header('Report Overview');

///////////////////////////////////////////////////////////////////////////////
// Form Fields and Buttons
///////////////////////////////////////////////////////////////////////////////

echo form_fieldset('Section 1');
echo field_input('key1', 'Value' , 'Key', TRUE);
echo field_input('key2', 'Value' , 'Key', TRUE);
echo form_fieldset_close();

echo form_fieldset('Section2');
echo field_input('key3', 'Value' , 'Key', TRUE);
echo field_input('key4', 'Value' , 'Key', TRUE);
echo form_fieldset_close();

///////////////////////////////////////////////////////////////////////////////
// Form close
///////////////////////////////////////////////////////////////////////////////

echo form_footer();
echo form_close();
