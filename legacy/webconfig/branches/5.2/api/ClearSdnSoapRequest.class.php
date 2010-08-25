<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2010 ClearCenter
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
 * Soap Request class.
 *
 * @package Api
 * @subpackage ClearSdnSoapRequest
 * @author {@link http://www.clearcenter.com/ ClearCenter}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2010, ClearCenter
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
 * Failed to connect to ClearSDN exception.
 *
 * @package Api
 * @subpackage Exception
 * @author {@link http://www.clearcenter.com/ ClearCenter}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2010, ClearCenter
 */

class ClearSdnFailedToConnectException extends EngineException
{
	/**
	 * Failure to connect to ClearSDN Exception constructor.
	 *
	 * @param string $errmsg error message
	 */

	public function __construct($errmsg)
	{
		parent::__construct($errmsg, COMMON_WARNING);
	}
}

/**
 * Authentication exception.
 *
 * @package Api
 * @subpackage Exception
 * @author {@link http://www.clearcenter.com/ ClearCenter}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2010, ClearCenter
 */

class ClearSdnAuthenticationException extends EngineException
{
	/**
	 * Authentication Exception constructor.
	 *
	 * @param string $errmsg error message
	 */

	public function __construct($errmsg)
	{
		parent::__construct($errmsg, COMMON_WARNING);
	}
}

/**
 * Device not registered exception.
 *
 * @package Api
 * @subpackage Exception
 * @author {@link http://www.clearcenter.com/ ClearCenter}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2010, ClearCenter
 */

class ClearSdnDeviceNotRegisteredException extends EngineException
{
	/**
	 * Device Not Registered Exception constructor.
	 *
	 * @param string $errmsg error message
	 * @param int $code error code
	 */

	public function __construct($errmsg)
	{
		parent::__construct($errmsg, COMMON_WARNING);
	}
}

/**
 * Remote soap request exception.
 *
 * @package Api
 * @subpackage Exception
 * @author {@link http://www.clearcenter.com/ ClearCenter}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2010, ClearCenter
 */

class ClearSdnSoapRequestRemoteException extends EngineException
{
	/**
	 * Soap Request Services Exception constructor.
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
 * Soap Request class.
 *
 * The Soap Request class interacts with ClearCenter's backend
 * systems -- the Service Delivery Network.  This network provides dynamic DNS,
 * Dynamic VPN, software updates, content filter updates, and much more.
 *
 * @package Api
 * @subpackage ClearSdnSoapRequest
 * @author {@link http://www.clearcenter.com/ ClearCenter}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2010, ClearCenter
 */

class ClearSdnSoapRequest extends Engine
{
	///////////////////////////////////////////////////////////////////////////////
	// M E M B E R S
	///////////////////////////////////////////////////////////////////////////////

	protected $config = array();
	protected $is_loaded = false;
	protected $sysinfo = Array ("","en_US", "", "", "");

	const FILE_CONFIG = '/usr/share/system/modules/services/environment';
	const FILE_VENDOR = '/usr/share/system/settings/vendor';

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * The ClearSdnSoapRequest constructor.
	 *
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct();

		require_once(GlobalGetLanguageTemplate(__FILE__));

		ini_set("soap.wsdl_cache_enabled", "0");
	}


	/**
	 * A generic way to communicate with the Service Delivery Network (SDN).
	 *
	 * @access private
	 * @param string $jws  Java Web Service page (ie. wsSdnServi es.jws)
	 * @return SoapClient  a soap client
	 * @throws EngineException, ClearSdnFailedToConnectException, ClearSdnSoapRequestRemoteException
	 */

	public function Request($jws)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		// We always send the following information:
		// - hostkey
		// - vendor
		// - OS name
		// - OS version
		// - language (error messages will be translated one of these days)

		if (!$this->sysinfo[0] || !$this->sysinfo[1] || !$this->sysinfo[2])
			$this->_LoadDefaultRequestFields();

		for ($inx = 1; $inx <= $this->config['sdn_jws_servers']; $inx++) {
			$server = $this->config['sdn_jws_prefix'] . "$inx" . "." . $this->config['sdn_domain'];
			$deftimeout = ini_get( 'default_socket_timeout' );
			ini_set('default_socket_timeout', 5);
			try {
				$client = new SoapClient("https://" . $server . "/" . $this->config['sdn_jws_realm'] . "/" . $this->config['sdn_jws_version'] . "/" . $jws . "?wsdl");
				ini_set( 'default_socket_timeout', $deftimeout );

				// TODO Probably won't pass Pete's NO SESSION variables in API test
				foreach ($_SESSION['clearsdn_cookie'] as $cookiename=>$value) {
					$client->__setCookie($cookiename,$value);
				}
				return $client;
			} catch (SoapFault $e) {
				if ($inx < $this->config['sdn_jws_servers'])
					continue;
				ini_set( 'default_socket_timeout', $deftimeout );
				if ($e->faultcode == "WSDL")
					throw new ClearSdnFailedToConnectException($e->getMessage());
				else
					throw new ClearSdnSoapRequestRemoteException($e->getMessage());
			}
		}

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
			$this->sysinfo[0]= $suva->GetHostkey();
			$this->sysinfo[1] = $this->GetVendorCode();

			$os = new Os();
			$this->sysinfo[2] = $os->GetVersion();
			$this->sysinfo[3] = $os->GetName();
		
			$language= new Locale();
			$this->sysinfo[4] = $language->GetLanguageCode();

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
