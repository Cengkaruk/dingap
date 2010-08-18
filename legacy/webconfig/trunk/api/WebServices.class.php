<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2003-2005 Point Clark Networks.
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
 * Web services class.
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
require_once('Folder.class.php');
require_once('Locale.class.php');
require_once('Os.class.php');

///////////////////////////////////////////////////////////////////////////////
// E X C E P T I O N  C L A S S E S
///////////////////////////////////////////////////////////////////////////////

/**
 * Remote web services exception.
 *
 * @package Api
 * @subpackage Exception
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2007, Point Clark Networks
 */

class WebServicesNoCacheException extends EngineException
{
	/**
	 * Web Services no cache exception constructor.
	 *
	 * @param string $errmsg error message
	 * @param int $code error code
	 */

	public function __construct()
	{
		parent::__construct(WEBSERVICES_LANG_SUBSCRIPTION_INFORMATION_UNAVAILABLE, COMMON_INFO);
	}
}

/**
 * Not registered exception.
 *
 * @package Api
 * @subpackage Exception
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

class WebServicesNotRegisteredException extends EngineException
{
	/**
	 * Web Services not registered constructor.
	 */

	public function __construct()
	{
		parent::__construct(WEBSERVICES_LANG_SYSTEM_NOT_REGISTERED, COMMON_INFO);
	}
}

/**
 * Remote web services exception.
 *
 * @package Api
 * @subpackage Exception
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

class WebServicesRemoteException extends EngineException
{
	/**
	 * Web Services Exception constructor.
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
 * Web services class.
 *
 * The Web Service class interacts with Point Clark Networks' backend
 * systems -- the Service Delivery Network.  This network provides dynamic DNS,
 * Dynamic VPN, software updates, content filter updates, and much more.
 * This class will be replaced by direct SOAP calls in the future.
 *
 * @package Api
 * @subpackage WebServices
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class WebServices extends Engine
{
	///////////////////////////////////////////////////////////////////////////////
	// M E M B E R S
	///////////////////////////////////////////////////////////////////////////////

	protected $service;
	protected $cachefile;
	protected $config = array();
	protected $is_loaded = false;
	protected $hostkey = "";
	protected $langcode = "en_US";
	protected $vendor = "";
	protected $os_version = "";
	protected $os_name = "";

	const CONSTANT_UNKNOWN = 'unknown';
	const CONSTANT_UNLIMITED = 'unlimited';
	const CONSTANT_INCLUDED = 'included';
	const CONSTANT_EXTRA = 'extra';
	const CONSTANT_ASP = 'asp';
	const CONSTANT_NOT_REGISTERED = 20;
	const PATH_SUBSCRIPTIONS = '/var/lib/suva/services';
	const FILE_REGISTERED = '/usr/share/system/modules/services/registered';
	const FILE_CONFIG = '/usr/share/system/modules/services/environment';
	const FILE_VENDOR = '/usr/share/system/settings/vendor';

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * The WebServices constructor.
	 *
	 * The constructor requires the name of the remote service, for example:
	 *
	 *  - DynamicDNS - Dynamic DNS service
	 *  - DynamicVPN - Dynamic VPN service
	 *  - IntrusionDetection - Intrusion detection rules updates service
	 *  - etc.
	 *
	 * @param string $service service name
	 */

	function __construct($service)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct();

		require_once(GlobalGetLanguageTemplate(__FILE__));

		$this->service = $service;
		$this->cachefile = self::PATH_SUBSCRIPTIONS . "/$service/subscription";
	}


	/**
	 * A generic way to communicate with the Service Delivery Network (SDN).
	 *
	 * You can send IP updates, retrieve software update information, check
	 * dynamic VPN status, etc.  The method will immediately attempt a
	 * connection with alternate servers in the SDN if a previous connection
	 * attempt fails.
	 *
	 * @access private
	 * @param string $action action
	 * @param string $postfields post fields (eg ?ip=1.2.3.4)
	 * @return string payload
	 * @throws EngineException, ValidationException, WebServicesRemoteException
	 */

	public function Request($action, $postfields = "")
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		// The FILE_REGISTERED file is created when a box is successfully
		// registered.  We check to see if this file exists to save the
		// backend from handling endless invalid requests.

		if (! file_exists(self::FILE_REGISTERED))
			throw new WebServicesNotRegisteredException();

		$sdnerror = "";
		$resource = strtolower($this->service) . ".php?action=" . strtolower($action) . $postfields;

		// We always send the following information:
		// - hostkey
		// - vendor
		// - OS name
		// - OS version
		// - language (error messages will be translated one of these days)

		if (!$this->hostkey || !$this->vendor || !$this->langcode)
			$this->_LoadDefaultRequestFields();

		$resource .= "&hostkey=" . $this->hostkey . 
			"&vendor=" . $this->vendor . 
			"&osversion=" . $this->os_version . 
			"&osname=" . urlencode($this->os_name) . 
			"&lang=" . $this->langcode;

		for ($inx = 1; $inx <= $this->config['sdn_servers']; $inx++) {
			$server = $this->config['sdn_prefix'] . "$inx" . "." . $this->config['sdn_domain'];
			// Logger::Syslog("webservices", "Sending request to SDN $server", "WebServices." . $action);

			if (isset($ch))
				unset($ch);
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "https://" . $server . "/" . $this->config['sdn_version'] . "/" . $resource);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_TIMEOUT, 20);
			curl_setopt($ch, CURLOPT_FAILONERROR, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			$rawmessage = chop(curl_exec($ch));
			$error = curl_error($ch);
			$errno = curl_errno($ch);
			curl_close($ch);

			// Return useful errno messages for the most common errnos
			//--------------------------------------------------------

			if ($errno == 0) {

				//------------------------------------------------------------------
				//
				// Data is ok... return the payload and error message (if any)
				//
				// payload format:
				// exit_code|<code>|<error message>
				// <payload>
				//
				// e.g., a request for dynamic DNS information looks like:
				// exit_code|0|ok
				// 123.12.12.32|1052023323|test.system.net
				//
				//------------------------------------------------------------------

				// Make sure the return data is valid

				if (!preg_match("/exit_code|\d|/", $rawmessage)) {
					$sdnerror = "$server: invalid page or data";
				} else {
					$message = explode("\n", $rawmessage, 2);
					$returned = explode("|", $message[0], 3);

					if (!isset($returned[1])) {
						$sdnerror = "$server: invalid return code";
						$this->Log(COMMON_DEBUG, $sdnerror, "WebServices." . $action, __LINE__);
					} else if ($returned[1] == self::CONSTANT_NOT_REGISTERED) {
						throw new WebServicesNotRegisteredException();
					} else if ($returned[1] != 0) {
						throw new WebServicesRemoteException($returned[2], COMMON_INFO);
					} else {
						// Not all replies have a payload (just true/false)
						if (isset($message[1]))
							return $message[1];
						else
							return "";
					}
				}
			}
		}

		// None of the SDN servers responded -- send the last error code.
		if ($sdnerror)
			throw new EngineException($sdnerror, COMMON_WARNING);
		else if ($errno == CURLE_COULDNT_RESOLVE_HOST)
			throw new EngineException(WEBSERVICES_LANG_ERRMSG_COULDNT_RESOLVE_HOST, COMMON_WARNING);
		else if ($errno == CURLE_OPERATION_TIMEOUTED)
			throw new EngineException(WEBSERVICES_LANG_ERRMSG_TIMEOUT, COMMON_WARNING);
		else
			throw new EngineException(WEBSERVICES_LANG_ERRMSG_CONNECTION_FAIL . " " . $error, COMMON_WARNING);
	}

	/**
	 * Returns subscription status.
	 *
	 * Information in hash array includes:
	 *  - policy
	 *  - expiry
	 *  - license
	 *  - title
	 *  - subscribed
	 *  - status
	 *
	 * @param boolean $realtime set realtime to true to fetch real-time data
	 * @return array information about subscription
	 * @throws EngineException, ValidationException, WebServicesRemoteException
	 */

	public function GetSubscriptionStatus($realtime)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$file = new File($this->cachefile);
		$fileexists = false;

		try {
			$fileexists = $file->Exists();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		if (!$realtime && $fileexists) {
			try {
				$subscribed = $file->LookupValue("/^subscribed\s*=\s*/");
				$state = $file->LookupValue("/^state\s*=\s*/");

				$info["subscribed"] = ($subscribed == "t") ? true : false;
				$info["state"] = ($state == "t") ? true : false;

				$info["policy"] = $file->LookupValue("/^policy\s*=\s*/");
				$info["expiry"] = $file->LookupValue("/^expiry\s*=\s*/");
				$info["license"] = $file->LookupValue("/^license\s*=\s*/");
				$info["title"] = $file->LookupValue("/^title\s*=\s*/");
				$info["message"] = $file->LookupValue("/^message\s*=\s*/");
				$info["updated"] = $file->LookupValue("/^updated\s*=\s*/");
				$info["cached"] = $file->LastModified();

				return $info;
			} catch (Exception $e) {
				throw new EngineException($e->GetMessage(), COMMON_ERROR);
			}
		} else if (!$realtime && !$fileexists) {
			throw new WebServicesNoCacheException();
		}

		// Catch the harmless exceptions -- we do not want them to
		// throw a COMMON_ERROR exception.

		try {
			$payload = $this->Request("GetSubscriptionInfo");
		} catch (WebServicesNotRegisteredException $e) {
			throw new WebServicesNotRegisteredException();
		} catch (WebServicesRemoteException $e) {
			throw new WebServicesRemoteException($e->GetMessage(), COMMON_INFO);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		// payload format -- subscribed|policy|expiry|license_type|title|message|state
		$result = explode("|", $payload);

		if ($result[0] == "t")
			$info["subscribed"] = true;
		else
			$info["subscribed"] = false;

		$info["policy"] = $result[1];
		$info["expiry"] = $result[2];
		$info["license"] = $result[3];
		$info["title"] = $result[4];
		$info["message"] = $result[5];

		if ($result[6] == "t")
			$info["state"] = true;
		else
			$info["state"] = false;

		$info["updated"] = $result[7];

		// Cache info
		//-----------

		$folder = new Folder(self::PATH_SUBSCRIPTIONS . "/$this->service");

		try {
			if (! $folder->Exists())
				$folder->Create("suva", "suva", "0755");

			if ($fileexists)
				$file->Delete();

			$file->Create("suva", "suva", "0644");
			$file->AddLines(
				"subscribed = $result[0]\n" .
				"policy = $result[1]\n" .
				"expiry = $result[2]\n" .
				"license = $result[3]\n" .
				"title = $result[4]\n" .
				"message = $result[5]\n" .
				"state = $result[6]\n" .
				"updated = $result[7]\n"
			);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		$info['cached'] = time();

		return $info;
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
	 * Loads default remote request fields.
	 * 
	 * @access private
	 * @return void
	 * @throws EngineException
	 */

	protected function _LoadDefaultRequestFields()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			// TODO: circular reference in SuvaWebService
			require_once('Suva.class.php');
			$suva = new Suva();
			$this->hostkey = $suva->GetHostkey();

			$os = new Os();
			$this->os_name = $os->GetName();
			$this->os_version = $os->GetVersion();
		
			$language= new Locale();
			$this->langcode = $language->GetLanguageCode();

			$this->vendor = $this->GetVendorCode();
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

		$configfile = new ConfigurationFile(self::FILE_CONFIG);

		try {
			$this->config = $configfile->Load();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		$this->is_loaded = true;
	}

	/**
	 * @ignore
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
