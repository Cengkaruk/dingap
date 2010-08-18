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
 * System time manager.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('Engine.class.php');
require_once('Folder.class.php');
require_once('File.class.php');


///////////////////////////////////////////////////////////////////////////////
// E X C E P T I O N  C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Timezone not set exception.
 *
 * @package Api
 * @subpackage Exception
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class TimezoneNotSetException extends EngineException
{
	/**
	 * TimezoneNotSetException constructor.
	 *
	 * @param string $message error message
	 * @param int $code error code
	 */

	public function __construct($message, $code)
	{
		parent::__construct($message, $code);
	}
}

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * System time and configuration.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class Time extends Engine
{
	///////////////////////////////////////////////////////////////////////////////
	// M E M B E R S
	///////////////////////////////////////////////////////////////////////////////

	const CMD_HWCLOCK = "/sbin/hwclock";
	const FILE_TIMEZONE = "/etc/localtime";
	const FILE_TIMEZONE_INFO = "/etc/localtime.info";
	const PATH_ZONEINFO = "/usr/share/zoneinfo/posix";
	// TODO: find subdirectories automagically
	// TODO: subdirectories should not be listed as a time zone
	const PATH_ZONEINFO_SUBDIR = "Africa America America/Indiana America/Kentucky America/North_Dakota Antarctica Asia Atlantic Australia Europe Indian Pacific US";

	/**
	 * Time constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct();

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Returns the system time (in seconds since Jan 1, 1970).
	 * 
	 * @return integer system time in seconds since Jan 1, 1970
	 */

	public function GetTime()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		return time();
	}

	/**
	 * Returns the current timzeone.
	 * 
	 * @return string current time zone
	 * @throws EngineException, TimezoneNotSetException
	 */

	public function GetTimeZone()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		// Sanity check existence of real time zone file
		
		$file = new File(self::FILE_TIMEZONE);
		$fileok = false;

		try {
			$fileok = $file->Exists();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		if (! $fileok)
			throw new TimezoneNotSetException(TIME_LANG_ERRMSG_TIMEZONE_NOT_SET, COMMON_ERROR);

		// Use time zone metadata file if it exists

		$infofile = new File(self::FILE_TIMEZONE_INFO);
		$fileok = false;

		try {
			$fileok = $infofile->Exists();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		if ($fileok) {
			try {
				$timezone = $infofile->GetContents();
				$timezone = trim($timezone);
				return $timezone;
			} catch (Exception $e) {
				throw new EngineException($e->GetMessage(), COMMON_ERROR);
			}
		} else {

			$currentmd5 = md5_file(self::FILE_TIMEZONE);

			try {
				foreach (explode(" ", self::PATH_ZONEINFO_SUBDIR) as $subzone) {
					$folder = new Folder(self::PATH_ZONEINFO . "/" . $subzone);
					$subzones = $folder->GetListing();
					foreach ($subzones as $zone) {
						if ($currentmd5 == md5_file(self::PATH_ZONEINFO . "/$subzone/$zone"))
							return "$subzone/$zone";
					}
				}
			} catch (Exception $e) {
				throw new EngineException($e->GetMessage(), COMMON_ERROR);
			}

			// Ugh -- sometimes the timezone files change.
			try {
				$currenttz = date_default_timezone_get();
				$this->SetTimezone($currenttz);
				return $currenttz;
			} catch (Exception $e) {
				throw new EngineException(TIME_LANG_ERRMSG_TIMEZONE_INVALID, COMMON_ERROR);
			}
		}
	}

	/**
	 * Returns a list of available time zones on the system.
	 * 
	 * @return array a list of available time zones
	 * @throws EngineException
	 */

	public function GetTimeZoneList()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$zonelist = array();

		$zoneinfo = array();

		foreach (explode(" ", self::PATH_ZONEINFO_SUBDIR) as $subzone) {
			try {
				$folder = new Folder(self::PATH_ZONEINFO . "/" . $subzone);
				$subzones = $folder->GetListing();
			} catch (Exception $e) {
				throw new EngineException($e->GetMessage(), COMMON_ERROR);
			}

			foreach ($subzones as $zone) {
				$md5 = md5_file(self::PATH_ZONEINFO . "/$subzone/$zone");
				$zoneinfo["fullzone"] = "$subzone/$zone";
				$zoneinfo["subzone"] = $subzone;
				$zoneinfo["zone"] = $zone;
				$zoneinfo["md5"] = $md5;
				$zonelist[] = $zoneinfo;
			}
		}

		return $zonelist;
	}

	/**
	 * Sets the Hardware Clock to the current system time.
	 * 
	 * @return void
	 * @throws EngineException
	 */

	public function SendSystemToHardware()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$shell = new ShellExec();
			if ($shell->Execute(self::CMD_HWCLOCK, "--systohc", true) != 0)
				throw new EngineException($shell->GetFirstOutputLine(), COMMON_ERROR);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Sets the current timzeone.
	 *
	 * The /etc/localtime file is just a copy of the appropriate file in
	 * the time zones directory.  This ends up giving us a one to many
	 * relationship (the localtime file could correspond to many time zone
	 * files).  We keep time zone information in /etc/localtime.info just to
	 * make it a one-to-one relationship.
	 * 
	 * @param string $timezone time zone
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	public function SetTimeZone($timezone)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!$this->IsValidTimeZone($timezone))
			throw new ValidationException(TIME_LANG_ERRMSG_TIMEZONE_INVALID);

		try {
			$info = new File(self::FILE_TIMEZONE_INFO);
      		$file = new File(self::PATH_ZONEINFO . "/" . $timezone);

			if ($info->Exists())
				$info->Delete();

			$info->Create("root", "root", "0644");
			$info->AddLines("$timezone\n");

			$file->CopyTo(self::FILE_TIMEZONE);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
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

	///////////////////////////////////////////////////////////////////////////////
	// V A L I D A T I O N   R O U T I N E S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Validates time zone.
	 *
	 * @param string $timezone time zone
	 * @return boolean true if time zone is valid
	 * @throws EngineException
	 */

	public function IsValidTimeZone($timezone)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!$timezone) {
			$this->AddValidationError(TIME_LANG_ERRMSG_TIMEZONE_INVALID, __METHOD__, __LINE__);
			return false;
		}

		try {
			$file = new File(self::PATH_ZONEINFO . "/" . $timezone);

			if ($file->Exists()) {
				return true;
			} else {
				$this->AddValidationError(TIME_LANG_ERRMSG_TIMEZONE_INVALID, __METHOD__, __LINE__);
				return false;
			}
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

	}
}

// vim: syntax=php ts=4
?>
