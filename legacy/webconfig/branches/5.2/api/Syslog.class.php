<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2007-2009 Point Clark Networks.
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
 * Syslog class.
 *
 * @package Api
 * @subpackage Daemon
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2007-2009, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('Engine.class.php');
require_once('Daemon.class.php');
require_once('File.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Syslog class.
 *
 * This class is not designed to be a comprehensive syslog configuration tool.
 *
 * @package Api
 * @subpackage Daemon
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2007-2009, Point Clark Networks
 */

class Syslog extends Daemon
{
	///////////////////////////////////////////////////////////////////////////////
	// M E M B E R S
	///////////////////////////////////////////////////////////////////////////////

	const FILE_CONFIG = '/etc/syslog.conf';
	const CONSTANT_LOG_MAIL = '/var/log/maillog';
	const CONSTANT_LOG_AUTHPRIV = '/var/log/secure';
	const CONSTANT_FACILITY_MAIL = 'mail';
	const CONSTANT_FACILITY_AUTHPRIV = 'authpriv';
	const CONSTANT_LEVEL_ALL = '*';
	const CONSTANT_LEVEL_DEBUG = 'debug';
	const CONSTANT_LEVEL_INFO = 'info';
	const CONSTANT_LEVEL_NOTICE = 'notice';
	const CONSTANT_LEVEL_WARNING = 'warning';
	const CONSTANT_LEVEL_ERROR = 'err';
	const CONSTANT_LEVEL_CRITICAL = 'crit';
	const CONSTANT_LEVEL_ALERT = 'alert';
	const CONSTANT_LEVEL_EMERGENCY = 'emerg';
	const CONSTANT_LEVEL_UNKNOWN = 'unknown';

	protected $valid_facilities = array();
	protected $valid_levels = array();
	protected $log_files = array();

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Syslog constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct("syslog");

		$this->log_files[self::CONSTANT_FACILITY_MAIL] = self::CONSTANT_LOG_MAIL;
		$this->log_files[self::CONSTANT_FACILITY_AUTHPRIV] = self::CONSTANT_LOG_AUTHPRIV;

		$this->valid_facilities = array(
			self::CONSTANT_FACILITY_MAIL, 
			self::CONSTANT_FACILITY_AUTHPRIV
		);

		$this->valid_levels = array(
			self::CONSTANT_LEVEL_ALL,
			self::CONSTANT_LEVEL_DEBUG,
			self::CONSTANT_LEVEL_INFO,
			self::CONSTANT_LEVEL_NOTICE,
			self::CONSTANT_LEVEL_WARNING,
			self::CONSTANT_LEVEL_ERROR,
			self::CONSTANT_LEVEL_CRITICAL,
			self::CONSTANT_LEVEL_ALERT,
			self::CONSTANT_LEVEL_EMERGENCY,
			self::CONSTANT_LEVEL_UNKNOWN
		);
	}

	/**
	 * Returns log level.
	 *
	 * @param string $facility syslog facility
	 * @return void
	 * @throws EngineException
	 */

	function GetLogLevel($facility)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! in_array($facility, $this->valid_facilities))
			throw new EngineException(LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - facility", COMMON_WARNING);

		$level = self::CONSTANT_LEVEL_UNKNOWN;

		try {
			$file = new File(self::FILE_CONFIG);
			$rawline = $file->LookupLine("/^$facility\./");
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}

		$matches = array();
		
		if (preg_match("/^$facility\.([^\s]*)/", $rawline, $matches)) {
			if (in_array($matches[1], $this->valid_levels))
				$level = $matches[1];
			else
				$level = self::CONSTANT_LEVEL_UNKNOWN;
		}

		return $level;
	}

	/**
	 * Sets the log level.
	 *
	 * @param string $facility syslog facility
	 * @param string $level log level
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	function SetLogLevel($facility, $level)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! in_array($facility, $this->valid_facilities))
			throw new EngineException(LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - facility", COMMON_WARNING);

		if (! in_array($level, $this->valid_levels))
			throw new EngineException(LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - level", COMMON_WARNING);

		try {
			$file = new File(self::FILE_CONFIG);
			$file->ReplaceLines("/^$facility\./", "$facility.$level                      " . $this->log_files[$facility] . "\n");
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
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__destruct();
	}
}

// vim: syntax=php ts=4
?>
