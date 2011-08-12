<?php

/**
 * Disk Usage overview.
 *
 * @category   Apps
 * @package    Disk_Usage
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearcenter.com/support/documentation/clearos/disk_usage/
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
$this->lang->load('disk_usage');

// TODO: implement an API call instead of file_exists test. 
if (file_exists(CLEAROS_TEMP_DIR . "/ps.db")) {
    $url = "https://" . $_SERVER['HTTP_HOST'] . "/cgi-bin/philesight.cgi";
    echo "<iframe style='border:none;' src='$url' width='100%' height='550'>";
    echo "<p>" . lang('disk_usage_iframe_not_supported') . "</p>";
    echo "</iframe>";
} else {
    echo infobox_warning(lang('base_warning'), lang('disk_usage_not_available')); 
}
