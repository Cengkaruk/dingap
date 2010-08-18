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
//
// Eventually, this will get merged with the WebServices class. For now, this
// class will handle its own request to the Service Delivery Network.
//
///////////////////////////////////////////////////////////////////////////////

/**
 * System registration class.
 *
 * @package Api
 * @subpackage WebServices
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('ConfigurationFile.class.php');
require_once('Engine.class.php');
require_once('File.class.php');
require_once('Os.class.php');
require_once('Product.class.php');
require_once('Resolver.class.php');
require_once('Suva.class.php');
require_once('Syswatch.class.php');
require_once('WebServices.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * System registration class.
 *
 * Registers your system to the Service Delivery Network (SDN).  Instead of
 * going over the network to check the registration status *every* time, we
 * simply set a local file to indicate that the system has been registered.
 *
 * @package Api
 * @subpackage WebServices
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class Register extends Engine
{
	const URL_SERVICES = "webservice_services.jsp";
	const URL_DEVICE = "webservice_device.jsp";
	const URL_USER = "webservice_user.jsp";
	const FILE_DIAGNOSTICS = "/usr/share/system/modules/services/diagnostics.state";
	const FILE_VENDOR = '/usr/share/system/settings/vendor';
	const TYPE_ADD = "Add";
	const TYPE_UPGRADE = "Upgrade";
	const TYPE_REINSTALL = "Reinstall";

	protected $hostkey = "";
	protected $langcode = "en_US";
	protected $vendor = "";
	protected $config = array();
	protected $is_loaded = false;

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Register constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct();

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Returns state of sending up diagnostic data.
	 *
	 * @return boolean true if diagnostic data is enabled
	 */

	public function GetDiagnosticsState()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$file = new File(Register::FILE_DIAGNOSTICS);
			$exists = $file->Exists();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		return $exists;
	}

	/**
	 * Returns secure portal URL.
	 *
	 * @return String  URL
	 */

	public function GetSdnURL()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		// FIXME: a little messy now with URL coming from old environment file.  Fix in 5.2.

		try {
			$product = new Product();
			$portalurl = $product->GetPortalUrl();
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}

		if (! empty($portalurl)) {
			return $portalurl;
		}

		if (! $this->isloaded)
			$this->_LoadConfig();

		return $this->config['sdn_url'];
	}

	/**
	 * Returns boolean flag determining requiring an OS license or not.
	 *
	 * @return boolean  require OS license
	 */

	public function GetSdnOsLicenseRequired()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->isloaded)
			$this->_LoadConfig();
		if ($this->config['sdn_os_license'])
			return true;
		else
			return false;
	}

	/**
	 * Returns boolean flag determining display of free trial or not.
	 *
	 * @return boolean  display free trial
	 */

	public function GetFreeTrialState()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$product = new Product();
			$state = $product->GetFreeTrialState();
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}

		return $state;
	}

	/**
	 * Returns registration status.
	 *
	 * @return boolean registration status
	 */

	public function GetStatus()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (file_exists(WebServices::FILE_REGISTERED))
			return true;
		else
			return false;
	}

	/**
	 * Returns the vendor code for the operating system/distribution.
	 *
	 * @return string vendor code
	 * @throws EngineException
	 */

	public function GetVendorCode()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		// TODO: overlaps with WebServices.GetVendorCode.

		$vendor = "";

		try {
			$file = new File(self::FILE_VENDOR);
			$vendor = $file->LookupValue("/^vendor\s*=\s*/");
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}

		return $vendor;
	}

	/**
	 * Sets state of sending up diagnostic data.
	 *
	 * @param boolean $state true if diagnostic data should be enabled
	 * @return void
	 */

	public function SetDiagnosticsState($state)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$file = new File(Register::FILE_DIAGNOSTICS, true);
			$exists = $file->Exists();

			if ($state && !$exists)
				$file->Create("root", "root", "644");
			else if (!$state && $exists)
				$file->Delete();

		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Sets registration to true in local cache.
	 *
	 * @returns void
	 * @throws EngineException
	 */

	public function SetStatus()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$file = new File(WebServices::FILE_REGISTERED);

		try {
			if ($file->Exists())
				$file->Delete();

			$file->Create("root", "root", "0644");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Resets the registration status.
	 *
	 * @param boolean $newkey flag to indicate host key regeneration
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	public function Reset($newkey)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! is_bool($newkey))
			throw new ValidationException(LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - newkey");

		if ($newkey) {
			$suva = new Suva();
			$this->hostkey = $suva->ResetHostkey();
		}

		$file = new File(WebServices::FILE_REGISTERED);

		try {
			if ($file->Exists())
				$file->Delete();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Simple username/password check for an online account.
	 *
	 * @param string $username username
	 * @param string $password password
	 * @return array request response
	 * @throws
	 */

	public function Authenticate($username, $password)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$response = $this->Request(
						self::URL_USER,
						"Authenticate",
						"&username=" . urlencode($username) . "&password=" . urlencode($password)
					);

		if ($response['exitcode'] != 0)
			throw new WebServicesRemoteException($response['errormessage'], COMMON_WARNING);

		return $response;
	}

	/**
	 * Returns a list of valid service levels for the given account.
	 *
	 * @param string $username username
	 * @param string $password password
	 * @return array service level list
	 */

	public function GetServiceLevel($username, $password)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$os = new Os();
			$osname = $os->GetName();
			$osversion = $os->GetVersion();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		$response = $this->Request(
						self::URL_DEVICE,
						"GetServiceLevels",
						"&username=" . urlencode($username) . "&password=" . urlencode($password) .
						"&osname=" . urlencode($osname) . "&osversion=" . urlencode($osversion)
					);

		if ($response['exitcode'] == 20)
			throw new WebServicesNotRegisteredException($response['errormessage'], COMMON_WARNING);
		if ($response['exitcode'] != 0)
			throw new WebServicesRemoteException($response['errormessage'], COMMON_WARNING);

		// Parse the payload
		//------------------

		$payload = $response['payload'];
		$rawlicenses = preg_replace("/.*<servicelevels>/si", "", $payload);
		$rawlicenses = preg_replace("/<\/servicelevels>.*/si", "", $rawlicenses);

		$licenseinfo = Array();
		if ($rawlicenses) {
			$licenses = explode("|", $rawlicenses);
			foreach ($licenses as $rawdetails) {
				unset($licenseinfo);
				// TODO - Awful hack (date has comma)...retool using proper XML structure...soon.
				$details = explode(" , ", $rawdetails);
				if (count($details) >= 4) {
					$licenseinfo["serial"] = $details[0];
					$licenseinfo["description"] = $details[1];
					$licenseinfo["expire"] = $details[2];
					$licenseinfo["status"] = $details[3];
					for ($index = 4; $index < count($details); $index++)
						$licenseinfo["child"][] = $details[$index];
					$licenselist[] = $licenseinfo;
				}
			}
		}

		return $licenselist;
	}

	/**
	 * Returns a list of valid licenses for the given account.
	 *
	 * @param string $username username
	 * @param string $password password
	 * @return array license list
	 */

	public function GetLicenseList($username, $password)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$os = new Os();
			$osname = $os->GetName();
			$osversion = $os->GetVersion();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		$response = $this->Request(
						self::URL_DEVICE,
						"GetLicenses",
						"&username=" . urlencode($username) . "&password=" . urlencode($password) .
						"&osname=" . urlencode($osname) . "&osversion=" . urlencode($osversion)
					);

		if ($response['exitcode'] == 20)
			throw new WebServicesNotRegisteredException($response['errormessage'], COMMON_WARNING);
		if ($response['exitcode'] != 0)
			throw new WebServicesRemoteException($response['errormessage'], COMMON_WARNING);

		// Parse the payload
		//------------------

		$payload = $response['payload'];
		$rawlicenses = preg_replace("/.*<licenses>/si", "", $payload);
		$rawlicenses = preg_replace("/<\/licenses>.*/si", "", $rawlicenses);

		if ($rawlicenses) {
			$licenses = explode("|", $rawlicenses);
			foreach ($licenses as $rawdetails) {
				$details = explode(",", $rawdetails);
				if (count($details) >= 4) {
					$licenseinfo["serial"] = $details[0];
					$licenseinfo["description"] = $details[1];
					$licenseinfo["name"] = $details[2];
					$licenseinfo["status"] = $details[3];
					$licenselist[] = $licenseinfo;
				}
			}
		}

		return $licenselist;
	}

	/**
	 * Returns a list of devices in the given account.
	 *
	 * @param string $username username
	 * @param string $password password
	 * @return array device list
	 */

	public function GetDeviceList($username, $password)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$response = $this->Request(
						self::URL_DEVICE,
						"GetDeviceList",
						"&username=" . urlencode($username) . "&password=" . urlencode($password)
					);

		if ($response['exitcode'] == 20)
			throw new WebServicesNotRegisteredException($response['errormessage'], COMMON_WARNING);
		if ($response['exitcode'] != 0)
			throw new WebServicesRemoteException($response['errormessage'], COMMON_WARNING);

		// Parse the payload
		//------------------

		$deviceinfo = array();
		$payload = $response['payload'];
		$rawdevices = preg_replace("/.*<devices>/si", "", $payload);
		$rawdevices = preg_replace("/<\/devices>.*/si", "", $rawdevices);

		if ($rawdevices) {
			$devices = explode("|", $rawdevices);
			foreach ($devices as $rawdetails) {
				$details = explode(",", $rawdetails);
				$deviceinfo['id'] = $details[0];
				$deviceinfo['name'] = $details[1];
				$deviceinfo['osname'] = $details[2];
				$deviceinfo['osversion'] = $details[3];
				$devicelist[] = $deviceinfo;
			}
		}

		return $devicelist;
	}

	/**
	 * Returns details of a subscription.
	 * @param string $username username
	 * @param string $password password
	 * @param string $serial serial number of license
	 *
	 * @return array  details
	 */

	public function GetLicenseDetails($username, $password, $serial)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$response = $this->Request(
					self::URL_DEVICE,
					"GetLicenseDetails",
					"&username=" . urlencode($username) . "&password=" . urlencode($password) .
					"&serial=" . urlencode($serial)
		);

		if ($response['exitcode'] == 20)
			throw new WebServicesNotRegisteredException($response['errormessage'], COMMON_WARNING);
		if ($response['exitcode'] != 0)
			throw new WebServicesRemoteException($response['errormessage'], COMMON_WARNING);

		$rawdata = split("\|", $response['payload']);

		return $rawdata;
	}

	/**
	 * Returns subscriptions for a device.
	 * @param string $username username
	 * @param string $password password
	 * @param string $device  a device nickname
	 *
	 * @return array  details
	 */

	public function GetDeviceDetails($username, $password, $device)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$response = $this->Request(
					self::URL_DEVICE,
					"GetDeviceDetails",
					"&username=" . urlencode($username) . "&password=" . urlencode($password) .
					"&device=" . urlencode($device)
		);

		if ($response['exitcode'] == 20)
			throw new WebServicesNotRegisteredException($response['errormessage'], COMMON_WARNING);
		if ($response['exitcode'] != 0)
			throw new WebServicesRemoteException($response['errormessage'], COMMON_WARNING);

		// Parse the payload
		//------------------

		$sublist = array();
		$payload = $response['payload'];

		if ($payload) {
			$services = explode("|", $payload);
			foreach($services as $rawdetails) {
				$details = explode(",", $rawdetails);
				$info['type'] = trim($details[0]);
				$info['serial'] = trim($details[1]);
				$info['description'] = trim($details[2]);
				$sublist[] = $info;
			}
		}
		return $sublist;
	}

	/**
	 * Returns product end of license.
	 *
	 * @return integer date
	 */

	public function GetEndOfLicense()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$response = $this->Request(
						self::URL_DEVICE,
						"GetEndOfLicense",
						""
					);

		if ($response['exitcode'] == 20)
			throw new WebServicesNotRegisteredException($response['errormessage'], COMMON_WARNING);
		if ($response['exitcode'] != 0)
			throw new WebServicesRemoteException($response['errormessage'], COMMON_WARNING);

		// TODO: date should be localized 
		$rawdata = split("\|", $response['payload']);

		return $rawdata[1];
	}

	/**
	 * Returns product end of life.
	 *
	 * @return integer date
	 */

	public function GetEndOfLife()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$response = $this->Request(
						self::URL_DEVICE,
						"GetEndOfLife",
						""
					);

		if ($response['exitcode'] == 20)
			throw new WebServicesNotRegisteredException($response['errormessage'], COMMON_WARNING);
		if ($response['exitcode'] != 0)
			throw new WebServicesRemoteException($response['errormessage'], COMMON_WARNING);

		// TODO: date should be localized 
		$rawdata = split("\|", $response['payload']);

		return $rawdata[1];
	}

	/**
	 * Returns services and subscription status.
	 *
	 * @return array service and subscription status
	 */

	function GetServiceList()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$response = $this->Request(
						self::URL_SERVICES,
						"GetList",
						"username=unused"
					);

		if ($response['exitcode'] == 20)
			throw new WebServicesNotRegisteredException($response['errormessage'], COMMON_WARNING);
		if ($response['exitcode'] != 0)
			throw new WebServicesRemoteException($response['errormessage'], COMMON_WARNING);

		// Parse the payload
		//------------------

		$servicelist = array();
		$payload = $response['payload'];

		// TODO: add available/not available
		// TODO: help link should only be a URL (not a full href)

		if ($payload) {
			$services = explode("|", $payload);
			foreach($services as $rawdetails) {
				$details = explode(",", $rawdetails);
				$serviceinfo['name'] = $details[0];
				$serviceinfo['help'] = $details[1];

				if ($details[2] == "true")
					$serviceinfo['state'] = true;
				else
					$serviceinfo['state'] = false;

				$servicelist[] = $serviceinfo;
			}
		}
		return $servicelist;
	}

	/**
	 * Returns optional account information.
	 *
	 * @return array account and device
	 */

	function GetOptionalInfo()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$response = $this->Request(
						self::URL_DEVICE,
						"GetOptionalInfo",
						""
					);

		if ($response['exitcode'] == 20)
			throw new WebServicesNotRegisteredException($response['errormessage'], COMMON_WARNING);
		if ($response['exitcode'] != 0)
			throw new WebServicesRemoteException($response['errormessage'], COMMON_WARNING);

		// Parse the payload
		//------------------

		$info = array();
		$payload = $response['payload'];

		if ($payload) {
			$data = explode("|", trim($payload));
			if ($data[0] != "")
				$info['account'] = $data[0];
			if ($data[1] != "")
				$info['var'] = $data[1];
			if ($data[2] != "")
				$info['devicename'] = $data[2];
			if ($data[3] != "")
				$info['address'] = $data[3];
			if ($data[4] != "")
				$info['license'] = $data[4];
		}

		return $info;
	}

	/**
	 * Returns the terms of service.
	 *
	 * @param string $username username
	 * @param string $password password
	 * @return string terms of service
	 */

	public function GetTermsOfService($username, $password)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$response = $this->Request(
						self::URL_DEVICE,
						"GetTermsOfService",
						"&username=" . urlencode($username) . "&password=" . urlencode($password)
					);

		if ($response['exitcode'] == 20)
			throw new WebServicesNotRegisteredException($response['errormessage'], COMMON_WARNING);
		if ($response['exitcode'] != 0)
			throw new WebServicesRemoteException($response['errormessage'], COMMON_WARNING);

		$tos = preg_replace("/.*<tos>/si", "", $response['payload']);
		$tos = preg_replace("/<\/tos>.*/si", "", $tos);

		return $tos;
	}

	/**
	 * Submits request to service delivery network.
	 *
	 * @param string $username username
	 * @param string $password password
	 * @param string $name system name
	 * @param string $serial serial number
	 * @param string $action type of registration
	 * @param string $service serial number of service level
	 * @param string $terms agree to tos
	 * @return void
	 */

	public function SubmitRegistration($username, $password, $name, $serial, $action, $service, $terms)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$os = new Os();
			$osname = $os->GetName();
			$osversion = $os->GetVersion();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		$response = $this->Request(
			self::URL_DEVICE,
			$action,
			"&username=" . urlencode($username) . "&password=" . urlencode($password) .
			"&name=" . urlencode($name) . "&serial=" . urlencode($serial) . "&service=" . urlencode($service) .
			"&terms=" . urlencode($terms) . "&osversion=" . urlencode($osversion)
		);

		if ($response['exitcode'] == 20)
			throw new WebServicesNotRegisteredException($response['errormessage'], COMMON_WARNING);
		if ($response['exitcode'] != 0)
			throw new WebServicesRemoteException($response['errormessage'], COMMON_WARNING);
	}

	/**
	 * @access private
	 */

	private function Request($resource, $action, $postfields)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->isloaded)
			$this->_LoadConfig();

		// We use the WebServices locale just for consistency.
		$ws_locale = new WebServices("Register");

		// We always send the following information:
		// - hostkey
		// - vendor (reseller)
		// - language (error messages will be translated one of these days)

		if (!$this->hostkey || !$this->vendor || !$this->langcode)
			$this->LoadDefaultRequestFields();

		$postfields .= "&hostkey=" . $this->hostkey . "&vendor=" . $this->vendor . "&lang=" . $this->langcode;

		// Registration can take some time
		set_time_limit(60);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->GetSdnUrl() . "/$resource");
		curl_setopt($ch, CURLOPT_POSTFIELDS, "action=" . $action . $postfields);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		curl_setopt($ch, CURLOPT_FAILONERROR, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

		$rawmessage = chop(curl_exec($ch));
		$error = curl_error($ch);
		$errno = curl_errno($ch);

		curl_close($ch);

		// Return useful errno messages for the most common errnos
		//--------------------------------------------------------

		if ($error || $errno) {
			$data["exitcode"] = 4;

			if ($errno == CURLE_COULDNT_RESOLVE_HOST) {
				// KLUDGE: PHP has an annoying caching issue when DNS servers in /etc/resolv.conf change.
				// TODO: Push this issue upstream?  Handle this better?
				$resolver = new Resolver();
				if ($resolver->TestLookup()) {
					$syswatch = new Syswatch();
					$syswatch->ReconfigureSystem();
					//TODO: translation
					throw new EngineException("Restarting webconfig due to DNS server changes... please try again in 30 seconds", COMMON_WARNING);
				}
				throw new EngineException(WEBSERVICES_LANG_ERRMSG_COULDNT_RESOLVE_HOST, COMMON_WARNING);
			} else if ($errno == CURLE_OPERATION_TIMEOUTED) {
				throw new EngineException(WEBSERVICES_LANG_ERRMSG_TIMEOUT, COMMON_WARNING);
			} else {
				throw new EngineException(WEBSERVICES_LANG_ERRMSG_CONNECTION_FAIL . " " . $error, COMMON_WARNING);
			}
		} else {

			// Data is ok... return the payload and error message (if any)
			//------------------------------------------------------------

			if (!preg_match("/exit_code|\d|/", $rawmessage)) {
				throw new EngineException("invalid page or data", COMMON_WARNING);
			} else {
				$rawmessage = trim($rawmessage);

				$exitcode = preg_replace("/.*<exitcode>/si", "", $rawmessage);
				$exitcode = preg_replace("/<\/exitcode>.*/si", "", $exitcode);
				$errormessage = preg_replace("/.*<errormessage>/si", "", $rawmessage);
				$errormessage = preg_replace("/<\/errormessage>.*/si", "", $errormessage);
				$payload = preg_replace("/.*<payload>/si", "", $rawmessage);
				$payload = preg_replace("/<\/payload>.*/si", "", $payload);

				$data["exitcode"] = $exitcode;
				$data["errormessage"] = $errormessage;
				$data["payload"] = $payload;
			}
		}

		return $data;
	}

	/**
	 * Loads default remote request fields.
	 *
	 * @access private
	 * @return void
	 * @throws EngineException
	 */

	protected function LoadDefaultRequestFields()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$suva = new Suva();
			$this->hostkey = $suva->GetHostkey();

			$vendor = $this->GetVendorCode();

			$language= new Locale();
			$this->langcode = $language->GetLanguageCode();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Loads configuration.
	 *
	 * @access private
	 * @return void
	 * @throws EngineException
	 */

	protected function _LoadConfig()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$configfile = new ConfigurationFile(WebServices::FILE_CONFIG);

		try {
			$this->config = $configfile->Load();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		$this->is_loaded = true;
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
