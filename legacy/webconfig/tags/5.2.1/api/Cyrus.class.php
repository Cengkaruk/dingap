<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2006-2007 Point Clark Networks.
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
//
// In order to handle the LDAP/Cyrus account synchronization, access to IMAP 
// on 127.0.0.1 must be running at all times.
//
///////////////////////////////////////////////////////////////////////////////

/**
 * Cyrus mail server class.
 *
 * @package Api
 * @subpackage Daemon
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006-2007, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('Daemon.class.php');
require_once('File.class.php');
require_once('Syslog.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Cyrus mail server class.
 *
 * @package Api
 * @subpackage Daemon
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006-2007, Point Clark Networks
 */

class Cyrus extends Daemon
{
	const FILE_CONFIG_CYRUS = "/etc/cyrus.conf";
	const FILE_CONFIG_IMAPD = "/etc/imapd.conf";
	const CONSTANT_SERVICE_IMAP = "imap";
	const CONSTANT_SERVICE_IMAPS = "imaps";
	const CONSTANT_SERVICE_POP3 = "pop3";
	const CONSTANT_SERVICE_POP3S = "pop3s";
	const CONSTANT_LEVEL_ALL = '*';
	const CONSTANT_LEVEL_DEBUG = 'debug';
	const CONSTANT_LEVEL_INFO = 'info';
	const CONSTANT_LEVEL_UNKNOWN = 'unknown';
	const STATE_NEW = 1;
	const STATE_ENABLED = 2;
	const STATE_DISABLED = 3;

	protected $is_loaded = false;
	protected $config = array();
	protected $validservices = array();

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Cyrus constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct("cyrus-imapd");

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Disables idled.
	 *
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	function DisableIdled()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!$this->is_loaded)
			$this->_LoadConfig();

		$this->config['start']['idled']['state'] = self::STATE_DISABLED;

		$this->_SaveConfig();
	}

	/**
	 * Disables service.
	 *
	 * @param string $service service name
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	function DisableService($service)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->IsValidService($service))
			throw new ValidationException(CYRUS_LANG_ERRMSG_INVALID_SERVICE);

		if (!$this->is_loaded)
			$this->_LoadConfig();

		// See note at the start of this file.
		if ($service == self::CONSTANT_SERVICE_IMAP) {
			$this->config['services'][$service]['state'] = self::STATE_NEW;
			$this->config['services'][$service]['listen'] = "127.0.0.1:143";
			$this->config['services'][self::CONSTANT_SERVICE_IMAP]['cmd'] = "imapd";
			$this->config['services'][self::CONSTANT_SERVICE_IMAP]['prefork'] = 3;
		} else {
			$this->config['services'][$service]['state'] = self::STATE_DISABLED;
		}

		$this->_SaveConfig();
	}

	/**
	 * Enables idled service.
	 *
	 * @return void
	 * @throws EngineException
	 */

	function EnableIdled()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!$this->is_loaded)
			$this->_LoadConfig();

		$this->config['start']['idled']['state'] = self::STATE_NEW;
		$this->config['start']['idled']['cmd'] = "idled";

		$this->_SaveConfig();
	}

	/**
	 * Enables service.
	 *
	 * @param string $service service name
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	function EnableService($service)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!$this->is_loaded)
			$this->_LoadConfig();

		if ($service == self::CONSTANT_SERVICE_IMAPS) {
			$this->config['services'][self::CONSTANT_SERVICE_IMAPS]['state'] = self::STATE_NEW;
			$this->config['services'][self::CONSTANT_SERVICE_IMAPS]['cmd'] = "imapd -s";
			$this->config['services'][self::CONSTANT_SERVICE_IMAPS]['listen'] = 993;
			$this->config['services'][self::CONSTANT_SERVICE_IMAPS]['prefork'] = 3;
		} else if ($service == self::CONSTANT_SERVICE_IMAP) {
			$this->config['services'][self::CONSTANT_SERVICE_IMAP]['state'] = self::STATE_NEW;
			$this->config['services'][self::CONSTANT_SERVICE_IMAP]['cmd'] = "imapd";
			$this->config['services'][self::CONSTANT_SERVICE_IMAP]['listen'] = 143;
			$this->config['services'][self::CONSTANT_SERVICE_IMAP]['prefork'] = 3;
		} else if ($service == self::CONSTANT_SERVICE_POP3S) {
			$this->config['services'][self::CONSTANT_SERVICE_POP3S]['state'] = self::STATE_NEW;
			$this->config['services'][self::CONSTANT_SERVICE_POP3S]['cmd'] = "pop3d -s";
			$this->config['services'][self::CONSTANT_SERVICE_POP3S]['listen'] = 995;
			$this->config['services'][self::CONSTANT_SERVICE_POP3S]['prefork'] = 0;
		} else if ($service == self::CONSTANT_SERVICE_POP3) {
			$this->config['services'][self::CONSTANT_SERVICE_POP3]['state'] = self::STATE_NEW;
			$this->config['services'][self::CONSTANT_SERVICE_POP3]['cmd'] = "pop3d";
			$this->config['services'][self::CONSTANT_SERVICE_POP3]['listen'] = 110;
			$this->config['services'][self::CONSTANT_SERVICE_POP3]['prefork'] = 0;
		} else {
			throw new ValidationException(CYRUS_LANG_ERRMSG_INVALID_SERVICE);
		}

		$this->_SaveConfig();
	}

	/**
	 * Returns the state of idled (push mail).
	 *
	 * @return boolean true if service is enabled
	 * @throws EngineException
	 */

	function GetIdledState()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!$this->is_loaded)
			$this->_LoadConfig();

		if (isset($this->config['start']['idled']['state']) && 
				 ($this->config['start']['idled']['state'] == self::STATE_ENABLED)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Gets log level.
	 *
	 * @return void
	 * @throws EngineException
	 */

	function GetLogLevel()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$syslog = new Syslog();
			$level = $syslog->GetLogLevel(Syslog::CONSTANT_FACILITY_MAIL);
			$syslog->Reset();
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}

		return $level;
	}

	/**
	 * Returns list of available services
	 *
	 * @return array list of services
	 * @throws EngineException, ValidationException
	 */

	function GetServiceList()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		return array(
				Cyrus::CONSTANT_SERVICE_IMAP,
				Cyrus::CONSTANT_SERVICE_IMAPS,
				Cyrus::CONSTANT_SERVICE_POP3,
				Cyrus::CONSTANT_SERVICE_POP3S,
				);
	}

	/**
	 * Returns the state of the service.
	 *
	 * @return boolean true if service is enabled
	 * @throws EngineException, ValidationException
	 */

	function GetServiceState($service)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->IsValidService($service))
			throw new ValidationException(CYRUS_LANG_ERRMSG_INVALID_SERVICE);

		if (!$this->is_loaded)
			$this->_LoadConfig();

		if (isset($this->config['services'][$service]['state']) && 
				 ($this->config['services'][$service]['state'] == self::STATE_ENABLED)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Sets the log level.
	 *
	 * @param string $level log level
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	function SetLogLevel($level)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$syslog = new Syslog();
			$syslog->SetLogLevel(Syslog::CONSTANT_FACILITY_MAIL, $level);
			$syslog->Reset();
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Upgrades Cyrus system.
	 *
	 * @return void
	 * @throws EngineException
	 */

	function Upgrade()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		// The IMAP server is now always running on localhost.  The following
		// non-intuitive code will update old boxes to this policy.

		$isenabled = $this->GetServiceState(Cyrus::CONSTANT_SERVICE_IMAP);

		if (! $isenabled)
			$this->DisableService(Cyrus::CONSTANT_SERVICE_IMAP);
	}

	/**
	 * Loads configuration.
	 *
	 * @access private
	 * @throws EngineException
	 */

	function _LoadConfig()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$this->config['services'] = array();

		try {
			$file = new File(self::FILE_CONFIG_CYRUS);
			$lines = $file->GetContentsAsArray();
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}

		$section = "";
		$matches = array();

		foreach ($lines as $line) {
			if (preg_match('/^\s*START/', $line))
				$section = "start";

			if (preg_match('/^\s*SERVICES/', $line))
				$section = "services";

			if (preg_match('/^\s*EVENTS/', $line))
				$section = "events";

			if (preg_match('/^\s*}/', $line))
				$section = "";

			if (preg_match('/^\s*[a-z][A-Z]*/', $line)) {

				// process
				if (preg_match('/^\s*([^\s]*)/', $line, $matches))
					$process = $matches[1];
				else
					$process = "unknown";

				$this->config[$section][$process]['state'] = self::STATE_ENABLED;

				// cmd
				if (preg_match('/cmd="([^\"]*)"/', $line, $matches))
					$this->config[$section][$process]['cmd'] = $matches[1];

				// prefork
				if (preg_match('/prefork=([^\s]*)/', $line, $matches))
					$this->config[$section][$process]['prefork'] = preg_replace("/[\"\']/", "", $matches[1]);

				// listen
				if (preg_match('/listen=([^\s]*)/', $line, $matches))
					$this->config[$section][$process]['listen'] = preg_replace("/[\"\']/", "", $matches[1]);

				// See note about LDAP/Cyrus above.  If IMAP is only listening on localhost
				// then we consider IMAP to be disabled.
				if (($process == self::CONSTANT_SERVICE_IMAP) && (preg_match("/127.0.0.1/", $this->config[$section][$process]['listen'])))
					$this->config[$section][$process]['state'] = self::STATE_DISABLED;
			}
		}

		$this->is_loaded = true;
	}

	/**
	 * Saves configuration
	 *
	 * @access private
	 * @throws EngineException
	 */

	function _SaveConfig()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$file = new File(self::FILE_CONFIG_CYRUS);

		try {
			$lines = $file->GetContentsAsArray();
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}

		$section = "";
		$newlines = array();
		$matches = array();

		foreach ($lines as $line) {

			// Find new sections
			//------------------

			if (preg_match('/^\s*START/', $line)) {
				$section = "start";
			} else if (preg_match('/^\s*SERVICES/', $line)) {
				$section = "services";
			} else if (preg_match('/^\s*EVENTS/', $line)) {
				$section = "events";
			}

			// Add new lines when we have reached the end of a section
			//--------------------------------------------------------
	
			if (preg_match('/^\s*}/', $line) && $section) {
				foreach ($this->config[$section] as $process => $details) {
					if ($details['state'] == self::STATE_NEW) {

						$newline = "  $process";

						if (isset($details['cmd']))
							$newline .= ' cmd="' . $details['cmd'] . '"';

						if (isset($details['listen']))
							$newline .= ' listen="' . $details['listen'] . '"';

						if (isset($details['prefork']))
							$newline .= ' prefork=' . $details['prefork'];

						$newlines[] = rtrim($newline);
					}
				}

				$section = "";
			}

			// Parse existing item entries (imaps cmd=...)
			//--------------------------------------------

			if (preg_match('/^\s*[a-z][A-Z]*/', $line)) {
				if (preg_match('/^\s*([^\s]*)/', $line, $matches))
					$process = $matches[1];
				else
					$process = "unknown";

				// STATE_DISABLED: delete the line
				// STATE_ENABLED: keep existing line
				// STATE_NEW: rewrite line (not fully implemented)

				if (isset($this->config[$section][$process]['state'])) {
					if ($this->config[$section][$process]['state'] == self::STATE_DISABLED)
						continue;
					else if ($this->config[$section][$process]['state'] == self::STATE_NEW)
						continue;
				}
			}

			$newlines[] = $line;
		}

		try {
			$file->DumpContentsFromArray($newlines);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}

		$this->is_loaded = false;
	}

	/**
	 * @access private
	 */

	public function __destruct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__destruct();
	}


	///////////////////////////////////////////////////////////////////////////////
	// V A L I D A T I O N   R O U T I N E S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Validates the service name.
	 *
	 * @param string $service service name
	 * @return boolean true if service is valid
	 */

	function IsValidService($service)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$allowedservices = array(
				self::CONSTANT_SERVICE_IMAP,
				self::CONSTANT_SERVICE_IMAPS,
				self::CONSTANT_SERVICE_POP3,
				self::CONSTANT_SERVICE_POP3S
		);

		if (in_array($service, $allowedservices))
			return true;
		else
			return false;
	}
}

// vim: syntax=php ts=4
?>
