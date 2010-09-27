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
 * Base class for the GUI.
 *
 * @package Gui
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once(dirname(__FILE__) . '/../common/Globals.inc.php');
require_once(dirname(__FILE__) . '/../common/Logger.class.php');
require_once(dirname(__FILE__) . '/../common/Error.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Base class for the GUI.
 *
 * @package Gui
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

class Gui extends Engine
{
	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Gui constructor.
	 */

	function __construct()
	{
		// A bit noisy
		// if (COMMON_DEBUG_MODE)
		//  $this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);
	}

	/**
	 * Prints a message to the log.
	 *
	 * The following log levels are used:
	 *
	 * - COMMON_DEBUG - debug messages
	 * - COMMON_VALIDATION - validation error message
	 * - COMMON_INFO - informational messages (e.g. dynamic DNS updated with IP w.x.y.z)
	 * - COMMON_NOTICE - pedantic warnings (e.g. dynamic DNS updated with IP w.x.y.z)
	 * - COMMON_WARNING - normal but significant errors (e.g. dynamic DNS could not detect WAN IP)
	 * - COMMON_ERROR - errors that should not happen under normal circumstances
	 * - COMMON_FATAL - really nasty errors
	 *
	 * @param int $code error code
	 * @param string $message short and informative message
	 * @param string $tag identifier (usually the method)
	 * @param int $line line number
	 * @return  void
	 * @static
	 */

	static function Log($code, $message, $tag, $line)
	{
		$error = new Error($code, $message, $tag, $line, null, true);
		Logger::Log($error);
	}

	/**
	 * @access private
	 */

	public function __destruct()
	{
		// A bit noisy
		// if (COMMON_DEBUG_MODE)
		//  $this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);
	}
}

// vim: syntax=php ts=4
?>
