<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2003-2010 ClearFoundation
//
///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Lesser General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

/**
 * NTP time class.
 *
 * @package ClearOS
 * @subpackage API
 * @author {@link http://www.clearfoundation.com/ ClearFoundation}
 * @license http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @copyright Copyright 2003-2010 ClearFoundation
 */

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = isset($_ENV['CLEAROS_BOOTSTRAP']) ? $_ENV['CLEAROS_BOOTSTRAP'] : '/usr/clearos/framework/shared';
require_once($bootstrap . '/bootstrap.php');

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('base');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

clearos_load_library('base/File');
clearos_load_library('base/ShellExec');
clearos_load_library('cron/Cron');
clearos_load_library('date/Time');
clearos_load_library('network/Network');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * NTP time class.
 *
 * @package ClearOS
 * @subpackage API
 * @author {@link http://www.clearfoundation.com/ ClearFoundation}
 * @license http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @copyright Copyright 2003-2010 ClearFoundation
 */

class NtpTime extends Time
{
	const FILE_CROND = "app-ntp";
	const FILE_CONFIG = "/etc/system/ntpdate";
	const DEFAULT_SERVER = "time.clearsdn.com";
	const DEFAULT_CRONTAB_TIME = "2 2 * * *";
	const CMD_NTPDATE = "/usr/sbin/ntpdate";
	const CRON_COMMAND = "/usr/sbin/timesync";

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * NtpTime constructor.
	 */

	public function __construct()
	{
		ClearOsLogger::Profile(__METHOD__, __LINE__);

		parent::__construct();
	}

	/**
	 * NtpTime destructor.
	 *
	 * @access private
	 */

	public function __destruct()
	{
		ClearOsLogger::Profile(__METHOD__, __LINE__);

		parent::__destruct();
	}

	/**
	 * Deletes the cron entry for auto-synchronizing with an NTP server.
	 *
	 * @return void
	 * @throws EngineException
	 */

	public function DeleteAutoSync()
	{
		ClearOsLogger::Profile(__METHOD__, __LINE__);

		try {
			$crontab = new Cron();
			if ($crontab->ExistsCrondConfiglet(self::FILE_CROND))
				$crontab->DeleteCrondConfiglet(self::FILE_CROND);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), ClearOsError::CODE_WARNING);
		}
	}

	/**
	 * Returns the time server to be used on the system.
	 *
	 * This will return the default self::DEFAULT_SERVER if a 
	 * time server has not been specified.
	 *
	 * @return string current auto-sync NTP server
	 * @throws EngineException
	 */

	public function GetAutoSyncServer()
	{
		ClearOsLogger::Profile(__METHOD__, __LINE__);

		$time_server = "";

		try {
			$config = new File(self::FILE_CONFIG);
			$time_server = $config->LookupValue("/^ntp_syncserver\s*=\s*/");
		} catch (FileNoMatchException $e) {
			$time_server = NtpTime::DEFAULT_SERVER;
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), ClearOsError::CODE_WARNING);
		}

		if (! $time_server)
			$time_server = NtpTime::DEFAULT_SERVER;

		$network = new Network();

		if (!($network->IsValidHostname($time_server) || $network->IsValidIp($time_server)))
			throw new EngineException(NTPTIME_LANG_ERRMSG_TIMESERVER_INVALID, ClearOsError::CODE_ERROR);

		return $time_server;
	}

	/**
	 * Returns the status of the auto-sync feature.
	 *
	 * @return boolean true if auto-sync is on
	 * @throws EngineException
	 */

	public function GetAutoSyncStatus()
	{
		ClearOsLogger::Profile(__METHOD__, __LINE__);

		try {
			$cron = new Cron();
			return $cron->ExistsCrondConfiglet(self::FILE_CROND);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), ClearOsError::CODE_ERROR);
		}
	}

	/**
	 * Returns the time configuration in the auto-synchronize cron entry. 
	 *
	 * Returns the default if an entry does not exist.
	 *
	 * @return string current auto-sync cron time
	 * @throws EngineException
	 */

	public function GetAutoSyncTime()
	{
		ClearOsLogger::Profile(__METHOD__, __LINE__);

		try {
			$crontab = new Cron();
			$contents = $crontab->GetCrondConfiglet(self::FILE_CROND);
		} catch (CronConfigletNotFoundException $e) {
			return self::DEFAULT_CRONTAB_TIME;
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), ClearOsError::CODE_WARNING);
		}

		$lines = explode("\n", $contents);

		foreach ($lines as $line) {
			$matches = array();

			if (preg_match("/([\d\*]+\s+[\d\*]+\s+[\d\*]+\s+[\d\*]+\s+[\d\*]+\s+)/", $line, $matches))
				return $matches[0];
		}

		throw new EngineException(NTPTIME_LANG_ERRMSG_CRONTIME_INVALID, ClearOsError::CODE_WARNING);
	}

	/**
	 * Creates a cron file for auto-synchronizng the system clock.
	 *
	 * The cron_time parameter ist optional -- the system will select
	 * a defaults if non is specified.
	 *
	 * @param string $cron_time crontab time
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	public function SetAutoSync($cron_time = self::DEFAULT_CRONTAB_TIME)
	{
		ClearOsLogger::Profile(__METHOD__, __LINE__);

		// Validate
		//---------

		$validtime = false;
		$crontab = new Cron();

		try {
			$validtime = $crontab->IsValidTime($cron_time);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), ClearOsError::CODE_WARNING);
		}

		if (! $validtime)
			throw new ValidationException(NTPTIME_LANG_ERRMSG_CRONTIME_INVALID);

		// Set auto sync
		//--------------

		try {
			$cron = new Cron();

			if ($cron->ExistsCrondConfiglet(self::FILE_CROND))
				$this->DeleteAutoSync();

			$payload  = "# Created by API\n";

			if (file_exists(self::CRON_COMMAND))
				$payload .= "$cron_time root " . self::CRON_COMMAND;
			else
				throw new EngineException(LOCALE_LANG_MISSING . " - " . self::CRON_COMMAND, ClearOsError::CODE_WARNING);

			$crontab->AddCrondConfiglet(self::FILE_CROND, $payload);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), ClearOsError::CODE_WARNING);
		}
	}

	/**
	 * Sets the time server to be used on the system.
	 *
	 * @param string $time_server (optional) auto-sync NTP server, if empty the default is set
	 * @return boolean true on successful update
	 * @throws EngineException
	 */

	public function SetAutoSyncServer($time_server = NULL)
	{
		ClearOsLogger::Profile(__METHOD__, __LINE__);

		if (empty($time_server))
			$time_server = NtpTime::DEFAULT_SERVER;
		
		if ($time_server == $this->GetAutoSyncServer())
			return false;

		$error_message = $this->ValidateTimeServer($time_server);

		if ($error_message)
			throw new EngineException($error_message, ClearOsError::CODE_WARNING);

		try {
			$config = new File(self::FILE_CONFIG);
			$config->ReplaceLines("/^ntp_syncserver\s*=\s*/","ntp_syncserver = {$time_server}\n");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), ClearOsError::CODE_WARNING);
		}

		return true;
	}

	/**
	 * Synchronizes the clock. 
	 *
	 * @param string $time_server time server (optional)
	 * @return string offset time
	 * @throws EngineException, ValidationException
	 */

	public function Synchronize($time_server = NULL)
	{
		ClearOsLogger::Profile(__METHOD__, __LINE__);

		if (is_null($time_server))
			$time_server = $this->GetAutoSyncServer();

		// Validate
		//---------

		$error_message = $this->ValidateTimeServer($time_server);

		if ($error_message)
			throw new ValidationException($error_message);

		// Synchronize
		//------------

		$output = "";

		try {
			$shell = new ShellExec();

			$options['env'] = "LANG=fr_FR";

			if ($shell->Execute(self::CMD_NTPDATE, "-u $time_server", true, $options) != 0)
				throw new EngineException(NTPTIME_LANG_ERRMSG_SYNCHRONIZE_FAILED, ClearOsError::CODE_ERROR);

			$output = $shell->GetFirstOutputLine();
			$output = preg_replace("/.*offset/", "", $output);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), ClearOsError::CODE_WARNING);
		}

		return $output;
	}

	///////////////////////////////////////////////////////////////////////////////
	// V A L I D A T I O N   R O U T I N E S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Validation routine for time server.
	 *
	 * @param string $time_server time server
	 * @return boolean true if time server is valid
	 */

	public function ValidateTimeServer($time_server)
	{
		ClearOsLogger::Profile(__METHOD__, __LINE__);

		if (preg_match("/^([\.\-\w]*)$/", $time_server))
			return '';
		else
			return 'Invalid time server'; // FIXME: localize 
	}
}

// vim: syntax=php ts=4
?>
