<?php

/**
 * Web proxy warning page.
 *
 * @category   Apps
 * @package    Web_Proxy
 * @subpackage Helpers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/web_proxy/
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
// Proxy server warnings are sent with get variables which are not allowed
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

clearos_load_language('web_proxy');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

use \clearos\apps\web_proxy\Squid as Squid;

clearos_load_library('web_proxy/Squid');

///////////////////////////////////////////////////////////////////////////////
// M A I N
///////////////////////////////////////////////////////////////////////////////

$squid = new Squid();

$code = isset($_REQUEST['code']) ? $_REQUEST['code'] : '';
$url = isset($_REQUEST['url']) ? $_REQUEST['url'] : '';
$ip = isset($_REQUEST['ip']) ? $_REQUEST['ip'] : '';
$ftp_reply = isset($_REQUEST['ftpreply1']) ? $_REQUEST['ftpreply1'] : '';

// Validate
//---------

// TODO

$encoded_ip = strtr(base64_encode($ip),  '+/=', '-_.');
$encoded_url = strtr(base64_encode($url),  '+/=', '-_.');
$encoded_ftp_reply = strtr(base64_encode($ftp_reply),  '+/=', '-_.');

// Redirect back to framework
//---------------------------

header('Location: http://' . $_SERVER['HTTP_HOST'] . '/app/web_proxy/warning/index/' . 
    $code . '/' . $encoded_url . '/' . $encoded_ip . '/' . $encoded_ftp_reply);
