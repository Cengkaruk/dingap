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
 * Software update class.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2009, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('Engine.class.php');
require_once('File.class.php');
require_once('ShellExec.class.php');

///////////////////////////////////////////////////////////////////////////////
// E X C E P T I O N  C L A S S E S
///////////////////////////////////////////////////////////////////////////////

/**
 * SoftwareUpdateBusyException exception.
 *
 * @package Api
 * @subpackage Exception
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2009, Point Clark Networks
 */

class SoftwareUpdateBusyException extends EngineException
{
	/**
	 * SoftwareUpdateBusyException constructor.
	 */

	public function __construct()
	{
		parent::__construct(SOFTWAREUPDATE_LANG_ERRMSG_IN_PROGRESS, COMMON_NOTICE);
	}
}

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Software update class.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2009, Point Clark Networks
 */

class SoftwareUpdate extends Engine
{
	///////////////////////////////////////////////////////////////////////////////
	// M E M B E R S
	///////////////////////////////////////////////////////////////////////////////

	const COMMAND_RPM = "/bin/rpm";
	const COMMAND_PID = "/sbin/pidof";
	const COMMAND_YUM = "/usr/bin/yum";
	const COMMAND_YUMINSTALL = "/usr/sbin/yuminstall";
	const ENV_GNUPG = "GNUPGHOME=/root/.gnupg";
	const FILE_INSTALL_LOG = "install.log";

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Software update constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct();

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Installs a package (or list of packages) from the update server.
	 *
	 * @param array $package package list
	 * @param boolean $background flag indicating whether or not to background the process
	 * @return integer exit code
	 * @throws SoftwareUpdateBusyException, ValidationException
	 */

	function Install($packages, $background = true)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		// Validate
		//---------

		if (! $packages)
			throw new ValidationException(SOFTWAREUPDATE_LANG_PACKAGE . " - " . LOCALE_LANG_MISSING);

		if (! is_bool($background))
			throw new ValidationException(LOCALE_LANG_ERRMSG_INVALID_TYPE . " - background");
			
		if (! is_array($packages))
			$packages = array($packages);

		foreach ($packages as $package) {
			if (preg_match("/;/", $package))
				throw new ValidationException(SOFTWAREUPDATE_LANG_PACKAGE . "($package) - " . LOCALE_LANG_INVALID);
		}

		if ($this->IsBusy())
			throw new SoftwareUpdateBusyException();

		// Reset log file
		//----------------
		
		$file = new File(COMMON_TEMP_DIR . "/" . SoftwareUpdate::FILE_INSTALL_LOG);

		try {
			if ($file->Exists())
				$file->Delete();
			$file->Create("webconfig", "webconfig", "0644");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		// Create rpm list
		//----------------

		$rpmlist = "";

		foreach ($packages as $package)
			$rpmlist .= escapeshellarg($package) . " ";

		// Run install script
		//-------------------

		try {
			$options = array();

			if ($background)
				$options['background'] = true;

			$shell = new ShellExec();
			$exitcode = $shell->Execute(self::COMMAND_YUMINSTALL, $rpmlist, false, $options);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		return $exitcode;
	}

	/**
	 * Checks to see if the update system is already running. 
	 *
	 * @return boolean  true if update system is busy
	 */

	function IsBusy()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$shell = new ShellExec();
			$exitcode = $shell->Execute(self::COMMAND_PID, "-s -x " . self::COMMAND_YUM, false);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		if ($exitcode == 0)
			return true;
		else
			return false;
	}

	/**
	 * Returns install log.
	 *
	 * @return array  log file in an array
	 */

	function GetLog()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$log = new File(COMMON_TEMP_DIR . "/" . self::FILE_INSTALL_LOG);
			$lines = $log->GetContentsAsArray();
		} catch (FileNotFoundException $e) {
			$lines = array();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
		
		return $lines;
	}

	/**
	 * @access private
	 */

	public function __destruct()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__destruct();
	}
}

// vim: syntax=php ts=4
?>
