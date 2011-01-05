<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2003-2011 ClearFoundation
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
 * Cron manager.
 *
 * @package ClearOS
 * @subpackage API
 * @author {@link http://www.clearfoundation.com/ ClearFoundation}
 * @license http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @copyright Copyright 2003-2011 ClearFoundation
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

clearos_load_library('base/Daemon');
clearos_load_library('base/File');

///////////////////////////////////////////////////////////////////////////////
// E X C E P T I O N S
///////////////////////////////////////////////////////////////////////////////

/**
 * Cron.d configlet not found exception.
 *
 * @package ClearOS
 * @subpackage API
 * @author {@link http://www.clearfoundation.com/ ClearFoundation}
 * @license http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @copyright Copyright 2003-2011 ClearFoundation
 */

class CronConfigletNotFoundException extends EngineException
{
	/**
	 * CronConfigletNotFoundException constructor.
	 *
	 * @param string $errmsg error message
	 * @param int $code error code
	 */

	public function __construct($errmsg, $code)
	{
		parent::__construct($errmsg, $code);
	}
}

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Cron server and crontab configuration.
 *
 * @package ClearOS
 * @author {@link http://www.clearfoundation.com/ ClearFoundation}
 * @license http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @copyright Copyright 2003-2011 ClearFoundation
 */

class Cron extends Daemon
{
	///////////////////////////////////////////////////////////////////////////////
	// C O N S T A N T S
	///////////////////////////////////////////////////////////////////////////////

	const FILE_CRONTAB = "/etc/crontab";
	const PATH_CROND = "/etc/cron.d";

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Cron constructor.
	 */

	public function __construct()
	{
		ClearOsLogger::Profile(__METHOD__, __LINE__);

		parent::__construct("crond");
	}

	/**
	 * Add a configlet to cron.d.
	 *
	 * @param string $name configlet name
	 * @param string $payload valid crond payload
	 * @returns void
	 * @throws EngineException, ValidationException
	 */

	public function AddCrondConfiglet($name, $payload)
	{
		ClearOsLogger::Profile(__METHOD__, __LINE__);

		// TODO -- validate payload

		try {
			$file = new File(self::PATH_CROND . "/" . $name, true);

			if ($file->Exists())
				throw new ValidationException(FILE_LANG_ERRMSG_EXISTS . " - " . $name);

			$file->Create("root", "root", "0644");

			$file->AddLines("$payload\n");

		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Add a configlet to cron.d.
	 * 
	 * @param string $name configlet name
	 * @param integer $minute minute of the day
	 * @param integer $hour hour of the day
	 * @param integer $dayofmonth day of the month
	 * @param integer $month month
	 * @param integer $dayofweek day of week
	 * @param string $user user that will run cron command
	 * @param string $command command
	 * @returns void
	 * @throws EngineException, ValidationException
	 */

	public function AddCrondConfigletByParts($name, $minute, $hour, $dayofmonth, $month, $dayofweek, $user, $command)
	{
		ClearOsLogger::Profile(__METHOD__, __LINE__);

		// TODO: validate variables

		try {
			$file = new File(self::PATH_CROND . "/" . $name, true);

			if ($file->Exists())
				throw new ValidationException(FILE_LANG_ERRMSG_EXISTS . " - " . $name);

			$file->Create("root", "root", "0644");

			$file->AddLines("$minute $hour $dayofmonth $month $dayofweek $user $command\n");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Get contents of a cron.d configlet.
	 *
	 * @param string $name configlet
	 * @return string contents of a cron.d file
	 * @throws CronConfigletNotFoundException, EngineException, ValidationException
	 */

	public function GetCrondConfiglet($name)
	{
		ClearOsLogger::Profile(__METHOD__, __LINE__);

		// TODO: validate filename, do not allow .. or leading /

		$contents = "";

		try {
			$file = new File(self::PATH_CROND . "/" . $name, true);
			$contents = $file->GetContents();
		} catch (FileNotFoundException $e) {
			throw new CronConfigletNotFoundException($e->GetMessage(), COMMON_INFO);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		return $contents;
	}

	/**
	 * Deletes cron.d configlet.
	 *
	 * @param string $name cron.d configlet
	 * @returns void
	 * @throws EngineException, ValidationException
	 */

	public function DeleteCrondConfiglet($name)
	{
		ClearOsLogger::Profile(__METHOD__, __LINE__);

		// TODO: validate filename, do not allow .. or leading /

		try {
			$file = new File(self::PATH_CROND . "/" . $name, true);

			if (! $file->Exists())
				throw new ValidationException(FILE_LANG_ERRMSG_NOTEXIST . " - " . $name);

			$file->Delete();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Checks to see if cron.d configlet exists.
	 *
	 * @param string $name configlet
	 * @return boolean true if file exists
	 * @throws EngineException, ValidationException
	 */

	public function ExistsCrondConfiglet($name)
	{
		ClearOsLogger::Profile(__METHOD__, __LINE__);

		try {
			$file = new File(self::PATH_CROND . "/" . $name, true);

			if ($file->Exists())
				return true;
			else
				return false;
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
	}

	///////////////////////////////////////////////////////////////////////////////
	// V A L I D A T I O N   R O U T I N E S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Validation routine for crontab time.
	 *
	 * @param string $time crontab time
	 * @return boolean true if time entry is valid
	 */

	public function IsValidTime($time)
	{
		ClearOsLogger::Profile(__METHOD__, __LINE__);

		// Could do more validation here...

		$time = preg_replace("/\s+/", " ", $time);

		$parts = explode(" ", $time);

		if (sizeof($parts) != 5)
			return false;

		return true;
	}
}

// vim: syntax=php ts=4
?>
