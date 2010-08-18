<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2007 Point Clark Networks.
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
 * Freshclam class.
 *
 * @package Api
 * @subpackage Daemon
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2007, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once("Daemon.class.php");
require_once("ConfigurationFile.class.php");
require_once("File.class.php");
require_once("ShellExec.class.php");

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Freshclam class.
 *
 * @package Api
 * @subpackage Daemon
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2007, Point Clark Networks
 */

class Freshclam extends Daemon
{
	///////////////////////////////////////////////////////////////////////////////
	// M E M B E R S
	///////////////////////////////////////////////////////////////////////////////

	protected $is_loaded = false;
	protected $config = array();

	const FILE_CONFIG = "/etc/freshclam.conf";
	const FILE_MIRRORS = "/var/lib/clamav/mirrors.dat";
	const CMD_FRESHCLAM = "/usr/bin/freshclam";
	const DEFAULT_CHECKS = 12;

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Freshclam constructor.
	 */

	function __construct() 
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct("freshclam");

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Returns information on the mirror that provided the last update.
	 *
	 * @return array mirror information
	 * @throws EngineException
	 */

	function GetLastChangeInfo()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$details = $this->GetMirrorDetails();

		$lastupdate = 0;
		$updatemirror = array();

		foreach ($details as $mirror => $mirrorinfo) {
			foreach ($mirrorinfo as $key => $value) {
				if (($key == "accessed") && ($value >= $lastupdate)) {
					$lastupdate = $value;
					$updatemirror = $mirrorinfo;
				}
			}
		}

		return $updatemirror;
	}

	/**
	 * Returns time of last attempted update.
	 *
	 * @return int time since last attempted update
	 * @throws EngineException
	 */

	function GetLastCheck()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$file = new File(self::FILE_MIRRORS);
			if (! $file->Exists())
				return 0;

			$modified = $file->LastModified();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		return $modified;
	}

	/**
	 * Returns details on the update mirrors.
	 *
	 * @return array details on the update mirrors
	 * @throws EngineException
	 */

	function GetMirrorDetails()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			// If no update has occurred, return an empty array
			$file = new File(self::FILE_MIRRORS);
			if (! $file->Exists())
				return array();

			$shell = new ShellExec();
			$options['env'] = "LANG=en_US";
			$retval = $shell->Execute(self::CMD_FRESHCLAM, "--list-mirrors", true, $options);
			if ($retval != 0) {
				$line = $shell->GetLastOutputLine();
				throw new EngineException($line);
			} else {
				$rawdata = $shell->GetOutput();
			}
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		$current = 1;

		foreach ($rawdata as $item) {
			$matches = array();

			if (preg_match('/^Mirror #(\d+)/i', $item, $matches)) {
				$current = $matches[1];
			} else if (preg_match('/^IP: ([\d\.]+)/i', $item, $matches)) {
				$details[$current]['ip'] = $matches[1];
			} else if (preg_match('/^Successes: ([\d]+)/i', $item, $matches)) {
				$details[$current]['successes'] = $matches[1];
			} else if (preg_match('/^Failures: ([\d]+)/i', $item, $matches)) {
				$details[$current]['failures'] = $matches[1];
			} else if (preg_match('/^Ignore: (\w+)/i', $item, $matches)) {
				$details[$current]['ignore'] = $matches[1];
			} else if (preg_match('/^Last access: (.*)/i', $item, $matches)) {
				$details[$current]['accessed'] = strtotime($matches[1]);
			}
		}

		return $details;
	}

	/**
	 * Returns the number of antivirus updates per day.
	 *
	 * @return int number of updates per day
	 * @throws EngineException
	 */

	function GetChecksPerDay()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!$this->is_loaded)
			$this->_LoadConfig();

		if (isset($this->config['Checks']))
			return $this->config['Checks'];
		else
			return self::DEFAULT_CHECKS;
	}

	/**
	 * Sets the number of antivirus updates per day.
	 *
	 * @param int $updates number of updates per day
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	function SetChecksPerDay($updates)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$intupdates = (int)$updates;

		if (($intupdates < 1) || ($intupdates > 24))
			 throw new ValidationException(FRESHCLAM_LANG_UPDATE_INTERVAL . " - " . LOCALE_LANG_INVALID);
	
		$this->_SetParameter("Checks", $updates);
	}

	///////////////////////////////////////////////////////////////////////////////
	// P R I V A T E  M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Sets a parameter.
	 *
	 * @access private
	 * @param string $key key
	 * @param string $value value for preference
	 * @return void
	 * @throws EngineException
	 */

	function _SetParameter($key, $value)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$file = new File(self::FILE_CONFIG);
			$match = $file->ReplaceLines("/^$key\s+/", "$key $value\n");
			if (!$match)
				$file->AddLines("$key $value\n");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		$this->is_loaded = false;
	}

	/**
	 * Loads configuration file.
	 *
	 * @access private
	 * @return void
	 * @throws EngineException
	 */

	function _LoadConfig()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$file = new ConfigurationFile(self::FILE_CONFIG, "split", "\s+");
			$this->config = $file->Load();
		} catch (FileNotFoundException $e) {
			// Empty configuration
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		$this->is_loaded = true;
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
