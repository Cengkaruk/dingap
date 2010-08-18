<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2003-2006 Point Clark Networks.
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
 * Daemon manager class.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once("Engine.class.php");
require_once("File.class.php");


///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Daemon manager class.
 *
 * DaemonManager offers a method for discovering installed daemons on 
 * your system.  Use the Daemon class to find information on specific
 * daemons.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 * @see Daemon
 */

class DaemonManager extends Engine
{
	const FILE_CONFIG = "/api/Daemon.inc.php";
	const PATH_INITD = "/etc/rc.d/rc3.d";
	const CMD_PIDOF = "/sbin/pidof";

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * DaemonManager constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct();

		require_once(GlobalGetLanguageTemplate(preg_replace("/DaemonManager/", "Daemon", __FILE__)));
	}


	/**
	 * Returns list of known daemons.
	 *
	 * Grabs the official list of daemons known by this class.
	 * Not all daemons are listed here since determining what is and is not
	 * a daemon is non-standard. 
	 *
	 * @return array list of daemons
	 * @throws EngineException
	 */

	public function GetList()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		global $DAEMONS;
		
		$daemonlist = array();

		if (! file_exists(COMMON_CORE_DIR . "/" . self::FILE_CONFIG))
			throw new EngineException(LOCALE_LANG_MISSING . " - " . COMMON_CORE_DIR . "/" . self::FILE_CONFIG, COMMON_ERROR);

		require(COMMON_CORE_DIR . "/" . self::FILE_CONFIG);

		while (list($initd, $info) = each($DAEMONS))
			$daemonlist[] = $initd;

		return $daemonlist;
	}

	/**
	 * Returns information on known daemons.
	 *
	 * The returned array contains the following information:
	 * - initd
	 * - package
	 * - processname
	 * - description
	 * - reloadable
	 *
	 * example: $daemon["httpd"]["description"]
	 *
	 * @return array information on known daemons
	 * @throws EngineException
	 */

	public function GetMetaData()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		global $DAEMONS;
		
		$daemonlist = array();

		if (! file_exists(COMMON_CORE_DIR . "/" . self::FILE_CONFIG))
			throw new EngineException(LOCALE_LANG_MISSING . " - " . COMMON_CORE_DIR . "/" . self::FILE_CONFIG, COMMON_ERROR);

		require(COMMON_CORE_DIR . "/" . self::FILE_CONFIG);

		while (list($initd, $details) = each($DAEMONS)) {
			$daemonlist[$initd]['package'] = $details[0];
			$daemonlist[$initd]['processname'] = $details[1];
			$daemonlist[$initd]['description'] = $details[3];
			$daemonlist[$initd]['url'] = $details[5];

			if (preg_match("/yes/i", $details[2]))
				$daemonlist[$initd]['reloadable'] = true;
			else
				$daemonlist[$initd]['reloadable'] = false;

			if (preg_match("/yes/i", $details[4]))
				$daemonlist[$initd]['core'] = true;
			else
				$daemonlist[$initd]['core'] = false;
		}

		return $daemonlist;
	}

	/**
	 * Returns status information and metadata on known daemons.
	 *
	 * The returned array contains the following information:
	 * - initd
	 * - package
	 * - processname
	 * - description
	 * - reloadable
	 * - installed
	 *
	 * example: $daemon["httpd"]["description"]
	 *
	 * This method tries to return information in a relatively short
	 * period.  It overlaps with some of the methods in Daemon.
	 *
	 * @return array daemon information
	 * @throws EngineException
	 */

	public function GetStatusData()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		global $DAEMONS;
		
		$daemonlist = array();

		// Grab list of daemons that start on boot

		if ($handle = opendir(self::PATH_INITD)) {
			while (false !== ($file = readdir($handle))) {
				$basename = preg_replace("/[S]\d+/", "", $file);
				$onboot[$basename] = true;
			}

			closedir($handle);
		} else {
			throw new EngineException(LOCALE_LANG_MISSING . " - " . self::PATH_INITD, COMMON_ERROR);
		}

		// Load our metadata
		if (! file_exists(COMMON_CORE_DIR . "/" . self::FILE_CONFIG))
			throw new EngineException(LOCALE_LANG_MISSING . " - " . COMMON_CORE_DIR . "/" . self::FILE_CONFIG, COMMON_ERROR);

		require(COMMON_CORE_DIR . "/" . self::FILE_CONFIG);

		// Set metadata and status data

		$shell = new ShellExec();
		
		foreach ($DAEMONS as $initd => $details) {
			$daemonlist[$initd]['package'] = $details[0];
			$daemonlist[$initd]['processname'] = $details[1];
			$daemonlist[$initd]['description'] = $details[3];

			if (preg_match("/yes/i", $details[2]))
				$daemonlist[$initd]['reloadable'] = true;
			else
				$daemonlist[$initd]['reloadable'] = false;

			if (file_exists("/etc/rc.d/init.d/$initd")) {
				$daemonlist[$initd]['installed'] = true;
				$daemonlist[$initd]['running'] = false;
				$daemonlist[$initd]['onboot'] = false;

				if (isset($onboot[$initd]))
					$daemonlist[$initd]['onboot'] = true;

				// Determine if Daemon is running
				if ($daemonlist[$initd]['processname'] == "kernel") {
					// "kernel" process like the firewall are always running
					$daemonlist[$initd]['running'] = true;
				} else if (file_exists("/var/run/" .  $daemonlist[$initd]['processname'] . ".pid")) {
					$daemonlist[$initd]['running'] = true;
				} else {
					$exitcode = 1;

					try {
						$exitcode = $shell->Execute(self::CMD_PIDOF, "-x -s " . $daemonlist[$initd]['processname']);
					} catch (Exception $e) {
						throw new DaemonManagerException($e->GetMessage(), COMMON_ERROR);
					}

					if ($exitcode == 0)
						$daemonlist[$initd]['running'] = true;
				}

			} else {
				$daemonlist[$initd]['installed'] = false;
				$daemonlist[$initd]['onboot'] = false;
				$daemonlist[$initd]['running'] = false;
			}
		}

		return $daemonlist;
	}
}

// vim: syntax=php ts=4
?>
