<?php

/**
 * Content filter warning page.
 *
 * @category   Apps
 * @package    Content_Filter
 * @subpackage Helpers
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
//
// Content filter warnings are sent with get variables which are not allowed
// in the framework.  This is a simple wrapper script to convert these to a
// framework-friendly format.
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('content_filter');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

use \clearos\apps\content_filter\DansGuardian as DansGuardian;

clearos_load_library('content_filter/DansGuardian');

///////////////////////////////////////////////////////////////////////////////
// M A I N
///////////////////////////////////////////////////////////////////////////////

$dansguardian = new DansGuardian();

$url = isset($_REQUEST['DENIEDURL']) ? $_REQUEST['DENIEDURL'] : '';
$reason = isset($_REQUEST['REASON']) ? $_REQUEST['REASON'] : '';

// TODO: Validate

$encoded_url = strtr(base64_encode($url),  '+/=', '-_.');
$encoded_reason = strtr(base64_encode($reason),  '+/=', '-_.');

// Redirect back to framework
//---------------------------

header('Location: http://' . $_SERVER['HTTP_HOST'] . '/app/content_filter/warning/index/' . 
    $encoded_url . '/' . $encoded_reason);
