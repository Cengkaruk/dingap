<?php

/**
 * Warning view.
 *
 * @category   ClearOS
 * @package    Content_Filter
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/content_filter/
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
$this->lang->load('content_filter');

///////////////////////////////////////////////////////////////////////////////
// Form 
///////////////////////////////////////////////////////////////////////////////

echo form_open('content_filter/warning');
echo form_header(lang('content_filter_content_filter'));

echo field_view(lang('content_filter_web_address'), $url, 'web_address');
echo field_view(lang('base_description'), $description, 'description');

if ($details)
    echo field_view(lang('content_filter_full_report'), $details, 'details');

echo form_footer();
echo form_close();
