<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2003-2009 Point Clark Networks.
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
 * Suva server.
 *
 * @package Api
 * @subpackage Daemon
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2009, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once("Daemon.class.php");
require_once("ShellExec.class.php");
require_once("SuvaWebService.class.php");

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Suva utility class.
 *
 * The Suva class can be used to return the current host key.
 *
 * @package Api
 * @subpackage Daemon
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2009, Point Clark Networks
 */

class Suva extends Daemon
{
	///////////////////////////////////////////////////////////////////////////////
	// C O N S T A N T S
	///////////////////////////////////////////////////////////////////////////////

	const PARAM_GET_HOSTKEY = "--get-hostkey";
	const PARAM_NEW_HOSTKEY = "--new-hostkey";
	const CMD_NEW_KEY = "/usr/bin/mkhost.sh";
	const FILE_CONFIG = "/etc/suvad.conf";
	const FILE_SUVA_CONFIGURED = '/etc/system/initialized/suva';

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Suva constructor.
	 */

	function __construct() 
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct("suvad");

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Returns hostkey.
	 *
	 * @return string hostkey
	 * @throws EngineException
	 */

	function GetHostkey()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$try_version =  false;

		try {
			$file = new File(self::FILE_CONFIG, true);
			$hostkey = $file->LookupValue("/^\s*device-hostkey\s*=\s*/");
			$hostkey = preg_replace('/[";]/', '', $hostkey);
		} catch (FileNoMatchException $e) {
			// Try xml format below
			$try_version = true;
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}

		// TODO: verify with Darryl S.
		if ($try_version) {
			try {
				$file = new File(self::FILE_CONFIG, true);
				$hostkey = $file->LookupValue("/^\s*<hostkey>/");
				$hostkey = preg_replace('/<\/hostkey>/', '', $hostkey);
			} catch (FileNoMatchException $e) {
				// Try xml format below
			} catch (Exception $e) {
				throw new EngineException($e->getMessage(), COMMON_WARNING);
			}	
		}

		if (empty($hostkey))
			throw new EngineException(SUVA_LANG_ERRMSG_HOSTKEY_NOT_FOUND, COMMON_WARNING);

		return $hostkey;
	}

	/**
	 * Automatically configures the suva configuration file.
	 *
	 * Two key pieces of information are required for the suvad.conf file:
	 * - the device name
	 * - the hostkey
	 *
	 * The hostkey is automatically generated on boot.  The device name
	 * must match the name provided by the backend services.  The accompanying
	 * web service will automatically fetch the device name from the backend
	 * and set it in suvad.conf.
	 *
	 * @return void
	 * @throws EngineException
	 */

	function AutoConfigure()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$file = new File(self::FILE_SUVA_CONFIGURED);

		try {
			if (!$file->Exists()) {
				$device = new SuvaWebService();
				$id = $device->GetId();

				$this->SetDeviceName($id);

				$file->Create("root", "root", "0644");
			}
		} catch (WebServicesNotRegisteredException $e) {
			return; // Not fatal
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Resets a hostkey.
	 *
	 * @throws EngineException
	 */

	function ResetHostkey()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			// Reset for AutoConfigure()
			$file = new File(self::FILE_SUVA_CONFIGURED);
			if ($file->Exists())
				$file->Delete();

			$shell = new ShellExec();
			$exitcode = $shell->Execute(Suva::CMD_NEW_KEY, Suva::FILE_CONFIG, true);
			if ($exitcode != 0)
				throw new EngineException($shell->GetLastOutputLine(), COMMON_WARNING);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Sets device name.
	 *
	 * @param string $name device name
	 * @return void
	 * @throws EngineException
	 */

	function SetDeviceName($name)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!preg_match("/^[0-9]+$/", $name))
			throw new ValidationException(LOCALE_LANG_INVALID . " - " . $name);

		try {
			$file = new File(Suva::FILE_CONFIG);
			$file->ReplaceLines("/<device>.*<\/device>/", "\t<device>$name</device>\n");
			$this->Restart();
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * @access private
	 */

	function __destruct()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__destruct();
	}
}

// vim: syntax=php ts=4
?>
