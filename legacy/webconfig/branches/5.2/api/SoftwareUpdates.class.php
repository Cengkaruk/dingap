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
 * Software web services class.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('Cron.class.php');
require_once('File.class.php');
require_once('Firewall.class.php');
require_once('Os.class.php');
require_once('ShellExec.class.php');
require_once('WebServices.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Software web services class.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class SoftwareUpdates extends WebServices
{
	///////////////////////////////////////////////////////////////////////////////
	// M E M B E R S
	///////////////////////////////////////////////////////////////////////////////

	protected $rpmdb = array();

	const CONSTANT_NAME =  "Software";
	const COMMAND_RPM = "/bin/rpm";
	const COMMAND_AUTOUPDATE = "/usr/sbin/software-update";
	const CODE_OK = 1;
	const CODE_NOTINSTALLED = 2;
	const CODE_REQUIRED = 3;
	const CODE_OBSOLETE = 4;
	const FILE_CRONFILE = "app-software-update";

	const PATH_CONFIG = "/usr/share/system/modules/services";
	const TYPE_CRITICAL = "crit";
	const TYPE_RECOMMENDED = "recommended";
	const TYPE_MODULE = "fun";
	const TYPE_CONTRIB = "contrib";

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Software webservice constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct(self::CONSTANT_NAME);

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Send OS version information to backend systems.
	 *
	 * Setting the OS version ensures that the list of software updates
	 * returned is appropriate.
	 *
	 * @return void
	 * @throws EngineException, WebServicesRemoteException
	 */

	public function SetOsInformation()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$os = new Os();
			$osversion = $os->GetVersion();
			$osversion = preg_replace("/ /", "%20", $osversion);
			$osname = $os->GetName();
			$osname = preg_replace("/ /", "%20", $osname);
			$this->Request("SetOsInformation", "&osversion=$osversion&osname=$osname");
		} catch (WebServicesRemoteException $e) {
			throw new WebServicesRemoteException($e->GetMessage(), COMMON_INFO);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Returns the complete list of available software updates.
	 *
	 * The hash array contains the following details:
	 *  - name
	 *  - version
	 *  - release
	 *  - type
	 *  - filename
	 *  - size
	 *  - packager
	 *  - url
	 *  - summary
	 *  - fullname
	 *  - rpmcheck
	 *  - alert_url
	 * 
	 * And the following optional fields
	 *  - vendor_name
	 *  - vendor_code
	 *  - repo_vendor
	 *  - repo_protocol
	 *  - repo_username
	 *  - repo_password
	 *  - repo_url
	 *  - repo_path
	 *  - repo_check
	 *
	 * @return array hash array of available software updates
	 * @throws EngineException, WebServicesRemoteException
	 */

	public function GetSoftwareUpdates()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (count($this->rpmdb) == 0)
			$this->_LoadRpmData();

		$payload = "";

		try {
			$os = new Os();
			$osversion = $os->GetVersion();
			$osversion = preg_replace("/ /", "%20", $osversion);
			$osname = $os->GetName();
			$osname = preg_replace("/ /", "%20", $osname);

			// Send mode (gateway or standalone).  Some modules are only available on gateways
			$mode = "";

			try {
				$firewall = new Firewall();
				$mode = $firewall->GetMode();
				$mode = preg_replace("/ /", "%20", $mode);
			} catch (Exception $e) {
				// Not fatal
			}

			$payload = $this->Request("GetInfo", "&os=$osname&version=$osversion&mode=$mode");

		} catch (WebServicesNotRegisteredException $e) {
			throw $e;
		} catch (WebServicesRemoteException $e) {
			throw new EngineException($e->GetMessage(), COMMON_INFO);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		$recordlist = array();
		$swlist = array();
		$swinfo = array();

		// Each record is separated by a new line
		// Each parameter is separated by a colon (in the form param=value)
		//-----------------------------------------------------------------

		$recordlist = explode("\n", $payload);

		for ($i = 0; $i < sizeof($recordlist); $i++) {
			$matches = array();

			if (preg_match("/name=([^|]*)/", $recordlist[$i], $matches))
				$swinfo["name"] = $matches[1];

			if (preg_match("/type=([^|]*)/", $recordlist[$i], $matches))
				$swinfo["type"] = $matches[1];

			if (preg_match("/version=([^|]*)/", $recordlist[$i], $matches))
				$swinfo["version"] = $matches[1];

			if (preg_match("/release=([^|]*)/", $recordlist[$i], $matches))
				$swinfo["release"] = $matches[1];

			if (preg_match("/filename=([^|]*)/", $recordlist[$i], $matches))
				$swinfo["filename"] = $matches[1];

			if (preg_match("/size=([^|]*)/", $recordlist[$i], $matches))
				$swinfo["size"] = $matches[1];

			if (preg_match("/packager=([^|]*)/", $recordlist[$i], $matches))
				$swinfo["packager"] = $matches[1];

			if (preg_match("/summary=([^|]*)/", $recordlist[$i], $matches))
				$swinfo["summary"] = $matches[1];

			if (preg_match("/date=([^|]*)/", $recordlist[$i], $matches))
				$swinfo["date"] = $matches[1];

			if (preg_match("/url=([^|]*)/", $recordlist[$i], $matches))
				$swinfo["url"] = $matches[1];

			if (preg_match("/alert_date=([^|]*)/", $recordlist[$i], $matches))
				$swinfo["alert_date"] = $matches[1];

			if (preg_match("/alert_url=([^|]*)/", $recordlist[$i], $matches))
				$swinfo["alert_url"] = $matches[1];

			if (preg_match("/vendor_name=([^|]*)/", $recordlist[$i], $matches))
				$swinfo["vendor_name"] = $matches[1];

			if (preg_match("/vendor_code=([^|]*)/", $recordlist[$i], $matches))
				$swinfo["vendor_code"] = $matches[1];

			if (preg_match("/repo_vendor=([^|]*)/", $recordlist[$i], $matches))
				$swinfo["repo_vendor"] = $matches[1];

			if (preg_match("/repo_protocol=([^|]*)/", $recordlist[$i], $matches))
				$swinfo["repo_protocol"] = $matches[1];

			if (preg_match("/repo_username=([^|]*)/", $recordlist[$i], $matches))
				$swinfo["repo_username"] = $matches[1];

			if (preg_match("/repo_password=([^|]*)/", $recordlist[$i], $matches))
				$swinfo["repo_password"] = $matches[1];

			if (preg_match("/repo_url=([^|]*)/", $recordlist[$i], $matches))
				$swinfo["repo_url"] = $matches[1];

			if (preg_match("/repo_path=([^|]*)/", $recordlist[$i], $matches))
				$swinfo["repo_path"] = $matches[1];

			if (preg_match("/repo_check=([^|]*)/", $recordlist[$i], $matches))
				$swinfo["repo_check"] = $matches[1];

			$swinfo["fullname"] = $swinfo["name"] . "-" . $swinfo["version"] . "-" . $swinfo["release"];

			if (isset($this->rpmdb[$swinfo["name"]]['version'])) {
				$rpmversion = $this->rpmdb[$swinfo["name"]]['version'];
				$rpmrelease = $this->rpmdb[$swinfo["name"]]['release'];

				if ($swinfo["version"] > $rpmversion) {
					$swinfo["rpmcheck"] = self::CODE_REQUIRED;
				} else if ($swinfo["version"] == $rpmversion) {
					if ($swinfo["release"] > $rpmrelease) {
						$swinfo["rpmcheck"] = self::CODE_REQUIRED;
					} else if ($swinfo["release"] == $rpmrelease) {
						$swinfo["rpmcheck"] = self::CODE_OK;
					} else {
						$swinfo["rpmcheck"] = self::CODE_OBSOLETE;
					}
				} else {
					$swinfo["rpmcheck"] = self::CODE_OBSOLETE;
				}
			} else {
				$swinfo["rpmcheck"] = self::CODE_NOTINSTALLED;
			}

			$swlist[] = $swinfo;
		}

		return $swlist;
	}

	/**
	 * Loads the rpm database with version and release information.
	 *
	 * Loading the rpm information for all RPMs improves performance.
	 *
	 * @access private
	 * @return void
	 * @throws EngineException
	 */

	public function _LoadRpmData()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$rawoutput = array();

		try {
			$shell = new ShellExec();
			$exitcode = $shell->Execute(self::COMMAND_RPM, "-qa --queryformat \"%{NAME} %{VERSION} %{RELEASE}\\n\"", false);

			if ($exitcode != 0)
				throw new EngineException($shell->GetLastOutputLine(), COMMON_WARNING);

			$rawoutput = $shell->GetOutput();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		foreach ($rawoutput as $line) {
			$items = explode(" ", $line);
			$this->rpmdb[$items[0]]['version'] = $items[1];
			$this->rpmdb[$items[0]]['release'] = $items[2];
		}
	}

	/**
	 * Returns auto-update time.
	 *
	 * @return array array with time info
	 * @throws EngineException
	 */

	public function GetAutoUpdateTime()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$cronlines = "";

		try {
			$cron = new Cron();
			$cronrawdata = $cron->GetCrondConfiglet(self::FILE_CRONFILE);
			$cronlines = explode("\n", $cronrawdata);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		foreach ($cronlines as $line) {
			if ( (! preg_match("/^#/", $line)) && (! preg_match("/^\s*$/", $line))) {
				$rawline = $line;
				break;
			}
		}

		// Parse the cron info
		//--------------------

		$cronentries = explode(" ", $rawline, 7);
		$croninfo = array();
		$croninfo["minute"] = $cronentries[0];
		$croninfo["hour"] = $cronentries[1];

		if (strlen($croninfo["minute"]) == 1)
			$croninfo["minute"] = "0" . $croninfo["minute"];

		return $croninfo;
	}

	/**
	 * Return auto-update state.
	 *
	 * Type can be:
	 * - TYPE_CRITICAL
	 * - TYPE_RECOMMENDED
	 *
	 * @param string $type type of software update
	 * @return boolean true if auto-update is enabled
	 * @throws EngineException, ValidationException
	 */

	public function GetAutoUpdateState($type)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!(($type == self::TYPE_CRITICAL) || ($type == self::TYPE_RECOMMENDED)))
			throw new ValidationException("$type - " . LOCALE_LANG_ERRMSG_INVALID_TYPE);

		$ison = false;

		try {
			$update = new File(self::PATH_CONFIG . "/$type");

			if ($update->Exists())
				$ison = true;
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		return $ison;
	}

	/**
	 * Set auto-update state.
	 *
	 * Type can be:
	 * - TYPE_CRITICAL
	 * - TYPE_RECOMMENDED
	 *
	 * @param string $type type of software update
	 * @param boolean $state state of software update
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	public function SetAutoUpdateState($type, $state)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! is_bool($state))
			throw new ValidationException("$state - " . LOCALE_LANG_ERRMSG_INVALID_TYPE);

		if (!(($type == self::TYPE_CRITICAL) || ($type == self::TYPE_RECOMMENDED)))
			throw new ValidationException("$type - " . LOCALE_LANG_ERRMSG_INVALID_TYPE);

		try {
			$update = new File(self::PATH_CONFIG . "/$type");

			if ($state) {
				if (!$update->Exists())
					$update->Create("root", "root", "0644");
			} else {
				if ($update->Exists())
					$update->Delete();
			}
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Set auto-update batch job.
	 *
	 * Auto-update will set a cron job roughly 24 hours from the time this
	 * method is called.  The time is randomized a bit to spread the load
	 * on the download servers.
	 *
	 * @return void
	 * @throws EngineException
	 */

	public function SetAutoUpdateTime()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$cron = new Cron();

			if ($cron->ExistsCrondConfiglet(self::FILE_CRONFILE))
				$cron->DeleteCrondConfiglet(self::FILE_CRONFILE);

			$nextday = date("w") + 1;

			$cron->AddCrondConfigletByParts(self::FILE_CRONFILE, rand(0,59), rand(3,8), "*", "*", $nextday, "root", self::COMMAND_AUTOUPDATE . " >/dev/null 2>&1");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		// Clean out cruft files created by rpm upgrades
		try {
			$cruftfiles = array('rpmsave', 'rpmnew', 'rpmorig');
			foreach ($cruftfiles as $cruft) {
				$file = new File(self::FILE_CRONFILE . "." . $cruft);

				if ($file->Exists())
					$file->Delete();
			}
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
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
