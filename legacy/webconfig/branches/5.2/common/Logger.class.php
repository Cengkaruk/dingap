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
 * Logger class.
 *
 * @package Common
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('Error.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * A general purpose logger.
 *
 * @package Common
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

class Logger
{
	/**
	 * Logger constructor.
	 */
	private function __construct()
	{}

	/**
	 * Logs a message to the log.
	 *
	 * @static
	 * @global array global error message mapping
	 * @return void
	 */

	public static function Log(Error $error)
	{
		global $COMMON_ERROR_TYPE;

		static $basetime = 0;

		// Set the log variables
		$errno = $error->GetCode();
		$errmsg = $error->GetMessage();
		$file = $error->GetTag();
		$line = $error->GetLine();
		$context = $error->GetContext();
		$caught = $error->IsCaught();
		$type = $error->GetType();

		// In debug mode, all errors are logged. In production mode, only important
		// messages are logged.

		if (! COMMON_DEBUG_MODE) {
			if ($type == Error::TYPE_EXCEPTION) {
 				if ($errno <= COMMON_WARNING)
					return;
			} else if ($type == Error::TYPE_ERROR) {
				// if (($errno === E_NOTICE) || ($errno === E_STRICT))
				//	return;
				// TODO: things like ldap_read generate errors... but that's
				// an expected error.  Unfortunately, it still gets logged
				return;
			}
		}

		// Set prefix for log line
		if ($type == Error::TYPE_ERROR)
			$prefix = "error";

		elseif ($type == Error::TYPE_EXCEPTION)
		$prefix = "exception";
		else
			$prefix = "unknown";

		if (!$caught)
			$prefix .= " uncaught";

		// Specify log line format
		$logline = sprintf("$prefix: %s: %s (%d): %s", $COMMON_ERROR_TYPE[$errno], preg_replace("/.*\//", "", $file), $line, $errmsg);

		// Perform extra goodness in debug mode
		if (COMMON_DEBUG_MODE) {
			// Append timestamp to log line
			if ($basetime == 0) {
				$basetime = microtime(true);
				$timestamp = 0;
			} else {
				$currenttime = microtime(true);
				$timestamp = microtime(true) - $basetime;
			}

			$logline = sprintf("%.4f: %s", round($timestamp, 4),  $logline);

			// Log messages to standard out when in command-line mode
			if (ini_get('display_errors') && preg_match('/cli/', php_sapi_name())) {
				echo "$logline\n";
			}

			// Log messages to custom log file (if set) and standard out on
			if (ini_get('error_log')) {
				date_default_timezone_set("EST");
				$timestamp = date("M j G:i:s T Y");
				error_log("{$timestamp}: $logline\n", 3, ini_get('error_log'));

				foreach ($error->getTrace() as $traceinfo) {
					// Backtrace log format
					$logline = sprintf("$prefix: debug backtrace: %s (%d): %s",
									   preg_replace("/.*\//", "", $traceinfo["file"]),
									   $traceinfo["line"],
									   $traceinfo["function"]);
					error_log("{$timestamp}: $logline\n", 3, ini_get('error_log'));
				}
			}
		} else {
			// Log errors to syslog
			openlog("engine", LOG_NDELAY, LOG_LOCAL6);
			syslog(LOG_INFO, $logline);

			// Log backtrace
			foreach ($error->getTrace() as $traceinfo) {
				// Backtrace log format
				$logline = sprintf("$prefix: debug backtrace: %s (%d): %s",
								   preg_replace("/.*\//", "", $traceinfo["file"]),
								   $traceinfo["line"],
								   $traceinfo["function"]);
				syslog(LOG_INFO, $logline);
			}

			closelog();
		}
	}

	/**
	 * Logs an exception to the log.
	 *
	 * @static
	 * @global array global error message mapping
	 * @return void
	 */

	public static function LogException(Exception $exception, $iscaught)
	{
		self::Log(
			new Error(
				$exception->getCode(),
				$exception->getMessage(),
				$exception->getFile(),
				$exception->getLine(),
				"",
				Error::TYPE_EXCEPTION,
				$iscaught,
				$exception->getTrace()
			)
		);
	}

	/**
	 * Logs to syslog
	 *
	 * @static
	 * @param string $tag prefix for log message
	 * @param string $message short and informative message
	 * @return void
	 */

	public static function Syslog($tag, $message)
	{
		openlog($tag, LOG_NDELAY, LOG_LOCAL6);
		syslog(LOG_INFO, $message);
		closelog();
	}
}

// vim: syntax=php ts=4
?>
