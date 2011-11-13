<?php

/**
 * Disk usage image.
 *
 * @category   Apps
 * @package    Disk_Usage
 * @subpackage Helper
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
//
// Image maps send get variables, e.g. &?123,456 which are not allowed in
// the framework.  This is a simple wrapper script to convert these to a
// framework-friendly format.
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\disk_usage\Philesight as Philesight;

clearos_load_library('disk_usage/Philesight');

///////////////////////////////////////////////////////////////////////////////
// M A I N
///////////////////////////////////////////////////////////////////////////////

$philesight = new Philesight();

$real_path = isset($_GET['path']) ? trim($_GET['path']) : '/';

$matches = array();
$xcoord = 0;
$ycoord = 0;

if (preg_match('/\?(\d+),(\d+)/', $_SERVER['QUERY_STRING'], $matches)) {
    $xcoord = $matches[1];
    $ycoord = $matches[2];
    $real_path = trim($philesight->get_path($real_path, $xcoord, $ycoord));
}

// Validate
//---------

try {
    if ($philesight->validate_path($real_path))
        throw new \Exception(lang('disk_usage_path_invalid'));

    if ($philesight->validate_coordinate($xcoord))
        throw new \Exception(lang('disk_usage_coordinate_invalid'));

    if ($philesight->validate_coordinate($ycoord))
        throw new \Exception(lang('disk_usage_coordinate_invalid'));
} catch (Engine_Exception $e) {
    return;
}

// Redirect back to framework
//---------------------------

$encoded_path = strtr(base64_encode($real_path),  '+/=', '-_.');

header('Location: https://' . $_SERVER['HTTP_HOST'] . '/app/disk_usage/index/' . $encoded_path . '/' . $xcoord . '/' . $ycoord);
