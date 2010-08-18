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
 * Web services for applications.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2009, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('Software.class.php');
require_once('SoftwareUpdate.class.php');
require_once('WebServices.class.php');
require_once('File.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Web services for applications.
 *
 * Core class for applications that include a web service (eg DansGuardian)
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2009, Point Clark Networks
 */

class ApplicationWebServices extends WebServices
{
	///////////////////////////////////////////////////////////////////////////////
	// M E M B E R S
	///////////////////////////////////////////////////////////////////////////////

	protected $package = "";

	const CONSTANT_DOUPDATE = "do_update = ";
	const CONSTANT_DELAY = "update_delay = ";
	const PATH_YUM_REPOS = "/etc/yum.repos.d";

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Application web services constructor.
	 *
	 * The constructor requires the name of the remote service.
	 *
	 * @param string $service service name
	 * @param string $package RPM package name of update
	 */

	function __construct($service, $package)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct($service);

		$this->package = $package;
	}

	/**
	 * Checks for an available update.
	 *
	 * @return boolean true if update is required
	 * @throws EngineException, WebServicesRemoteException
	 */

	public function CheckForUpdate()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$version = "";
		$release = "";
		$software = new Software($this->package);

		try {
			if ($software->IsInstalled()) {
				$version = $software->GetVersion();
				$release = $software->GetRelease();
			} else {
				$version = 0;
				$release = 0;
			}
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		$payload = $this->Request("CheckForUpdate", "&version=" . $version . "&release=" . $release);

		if (preg_match("/" . self::CONSTANT_DOUPDATE . "true/", $payload)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Installs the latest update if one is available.
	 *
	 * @return void
	 * @throws EngineException, WebServicesRemoteException
	 */

	public function InstallUpdate()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$this->_SetYumRepo();
			$update = new SoftwareUpdate();
			$update->Install($this->package, false);
			$this->_DeleteYumRepo();

			// Sanity check to see if RPM was installed
			// Since yum might come back "nothing to do / exit 0"
			// we cannot use the exit code to determine if the
			// update was actually installed
			$rpm = new Software($this->package);
			$installtime = $rpm->GetInstallTime();
			$delta = time() - $installtime;

			if (abs($delta) < '300')
				$this->_SetUpdateComplete();
		} catch (Exception $e) {
			$this->_DeleteYumRepo();
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Deletes yum repo file
	 *
	 * @return void
	 * @throws EngineException, WebServicesRemoteException
	 */

	public function _DeleteYumRepo()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$file = new File(self::PATH_YUM_REPOS . "/" . $this->package . ".repo", true);

			if ($file->Exists())
				$file->Delete();
		} catch (Exception $e) {
			// Not fatal
		}
	}

	/**
	 * Set yum repo information.
	 *
	 * @return void
	 * @throws EngineException, WebServicesRemoteException
	 */

	public function _SetYumRepo()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$payload = $this->Request("GetYumRepoInformation");

		$details = explode("|", $payload);

		try {
			$file = new File(self::PATH_YUM_REPOS . "/" . $this->package . ".repo", true);

			if ($file->Exists())
				$file->Delete();

			$file->Create("root", "root", "0644");

			$lines = "[" . $details[0] . "]\n";
			$lines .= "name=" . $details[1] . "\n";
			$lines .= "baseurl=" . $details[2] . "\n";
			$lines .= "gpgcheck=" . $details[3] . "\n";

			$file->AddLines($lines);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Sends ok message to Service Delivery Network.
	 *
	 * @returns void
	 */

	private function _SetUpdateComplete()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$payload = $this->Request("SetUpdateComplete");
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
}

// vim: syntax=php ts=4
?>
