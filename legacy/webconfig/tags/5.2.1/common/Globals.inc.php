<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2006 Point Clark Networks.
//
///////////////////////////////////////////////////////////////////////////////
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//
///////////////////////////////////////////////////////////////////////////////

/**
 * Basic environment and error handling for the engine.
 *
 * @package Common
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// E N V I R O N M E N T 
///////////////////////////////////////////////////////////////////////////////

define("COMMON_CORE_DIR", preg_replace("/\/common/", "", dirname(__FILE__)));
define("COMMON_TEMP_DIR", "/usr/webconfig/tmp");

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

if (isset($_ENV['WEBCONFIG_CONFIG']) && file_exists($_ENV['WEBCONFIG_CONFIG']))
	require_once($_ENV['WEBCONFIG_CONFIG']);
else
	require_once('Config.inc.php');

require_once('Logger.class.php');
require_once('Error.class.php');
require_once('ErrorQueue.class.php');

// TODO: strict warning keeps appearing when data functions
// depend on TZ setting.  The line below addresses the warning
// message, but in a horribly kludgy way.  I'm sure something
// about PHP's policy will change in a future release.

if (file_exists("/var/webconfig/common/gettimezone.php")) {
	$tz = `/var/webconfig/common/gettimezone.php`;
	date_default_timezone_set($tz);
}


///////////////////////////////////////////////////////////////////////////////
// E R R O R  A N D  E X C E P T I O N  H A N D L E R S
///////////////////////////////////////////////////////////////////////////////

// Define global error levels
//---------------------------

define("COMMON_FATAL", -1);
define("COMMON_ERROR", -2);
define("COMMON_WARNING", -4);
define("COMMON_INFO", -16);
define("COMMON_VALIDATION", -32);
define("COMMON_NOTICE", -64);
define("COMMON_DEBUG", -128);

// Define global error message mapping
// - Handled errors and exceptions use COMMON_xxx constants
// - Unhandled errors and exceptions use built-in E_xxx constants
//---------------------------------------------------------------

$COMMON_ERROR_TYPE = array(
                      COMMON_FATAL => "fatal",
                      COMMON_ERROR => "error",
                      COMMON_WARNING => "warning",
                      COMMON_INFO => "info",
                      COMMON_VALIDATION => "validation",
                      COMMON_NOTICE => "notice",
                      COMMON_DEBUG => "debug",
                      E_STRICT => "strict",
                      E_ERROR => "error",
                      E_WARNING => "warning",
                      E_PARSE => "parse error",
                      E_NOTICE => "notice",
                      E_CORE_ERROR => "core error",
                      E_CORE_WARNING => "core warning",
                      E_COMPILE_ERROR => "compile error",
                      E_COMPILE_WARNING => "compile warning",
                      E_USER_ERROR => "user error",
                      E_USER_WARNING => "user warning",
                      E_USER_NOTICE => "user notice"
                  );


/** 
 * Global error handler.
 *
 * This should not be called by anything - only for uncaught errors.
 *
 * @access private
 */

function GlobalErrorHandler($errno, $errmsg, $file, $line, $context)
{
	global $COMMON_ERROR_TYPE;

	//  If the @ symbol was used to suppress errors, bail
	if (error_reporting(0) === 0)
		return;
	
	$error = new Error($errno, $errmsg, $file, $line, $context, Error::TYPE_ERROR, false);

	// Log error
	Logger::Log($error);

	// Queue error for those implementations that need it (e.g. web-interface, SOAP)
	ErrorQueue::getInstance()->Push($error);

	// Show error on standard out if running from command line
	if (preg_match('/cli/', php_sapi_name()))
		echo "$COMMON_ERROR_TYPE[$errno]: " . $errmsg . " - $file ($line)\n";

	// TODO: Add "when to die" policy for uncaught errors
}

/**
 * Global exception handler.
 * 
 * This should not be called by anything - only for uncaught exceptions.
 *
 * @access private
 */

function GlobalExceptionHandler(Exception $exception)
{
	Logger::LogException($exception, false);

	// Show error on standard out if running from command line
	if (preg_match('/cli/', php_sapi_name())) {
		echo "Fatal - uncaught exception: " . $exception->getMessage() . "\n";
	} else {
		echo "
			<table cellspacing='10' cellpadding='10' border='0' width='100%' class='dialogwarning'>
			  <tr>
			   <td>" . $exception->getMessage() . "</td>
			  </tr>
			</table>
			<br />
		";
	}
}

// Set error and exception handlers
//---------------------------------

set_error_handler("GlobalErrorHandler");
set_exception_handler("GlobalExceptionHandler");


///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S  H E L P E R
///////////////////////////////////////////////////////////////////////////////

/**
 * Global translation helper.
 *
 * This function loads the appropriate language file.  This will become
 * deprecated in the near future.
 *
 * @param string $basefile /path/filename
 * @return string
 */

function GlobalGetLanguageTemplate($basefile)
{
	$path = pathinfo($basefile);
	$corename = explode(".", $path["basename"]);
	$basepath = $path["dirname"] . "/lang/" . strtolower($corename[0]) . ".";

	// There's a bit of kludge going on here.  If the API is accessed
	// via webconfig, the session (instead of the environment) is used to
	// get the locale.
	//
	// Chicken: the only way to reset the environment is to restart webconfig.  
	// Egg: restarting webconfig via webconfig is not possible

	$code = "";

    if (isset($_SESSION['system_locale'])) {
        $code = $_SESSION['system_locale'];
    } else if (isset($_ENV['LANG'])) {
        $code = preg_replace("/\..*/", "", $_ENV['LANG']);
    }

    if (file_exists($basepath . $code))
        return $basepath . $code;
    else
        return $basepath . "en_US";
}

// vim: syntax=php ts=4
?>
