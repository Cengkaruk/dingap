<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2009 ClearCenter.
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
 * IPsec Dynamic VPN class.
 *
 * @package Api
 * @subpackage ClearSDN
 * @author {@link http://www.clearcenter.com/ ClearCenter}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2009, ClearCenter
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('File.class.php');
require_once('Firewall.class.php');
require_once('Iface.class.php');
require_once('IpSec.class.php');
require_once('Network.class.php');
require_once('Routes.class.php');
require_once('Syswatch.class.php');
require_once('WebServices.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * IPsec Dynamic VPN class.
 *
 * @package Api
 * @subpackage ClearSDN
 * @author {@link http://www.clearcenter.com/ ClearCenter}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2009, ClearCenter
 */

class DynamicVpn extends WebServices
{
	///////////////////////////////////////////////////////////////////////////////
	// F I E L D S
	///////////////////////////////////////////////////////////////////////////////

	const FILE_CONFIG = '/etc/ipsec.d/ipsec.managed';
	const PATH_STATUS = '/var/lib/ipsec';
	const CMD_IPCALC = '/bin/ipcalc';
	const STATUS_UP = 0;
	const STATUS_WARNING = 1;
	const STATUS_DOWN = 2;
	const STATUS_INVALID = 3;
	const STATUS_THROTTLED = 4;
	const STATUS_INIT = 5;
	const LOG_TAG = 'dynamicvpn';
	const CONSTANT_NAME = 'dynamic-vpn';

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Dynamic VPN constructor.
	 *
	 * @return void
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		parent::__construct(self::CONSTANT_NAME);
	}

	/**
	 * Delete a dynamic VPN conection.
	 *
	 * @param  integer  $remoteid  remote VPN ID
	 * @return  void
	 * @throws  EngineException
	 */

	function DeleteConnection($remoteid)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$file = new File(self::FILE_CONFIG, true);

			if (!$file->Exists())
				return;

			$file->ReplaceLines("/->\s*$remoteid\s*/", "");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Returns configuration settings for a dynamic VPN connection.
	 *
	 * @param  integer  $remoteid  remote VPN ID
	 * @return  array  information on a VPN connection
	 * @throws  EngineException
	 */

	function GetConnection($remoteid)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$connectioninfo = array();

		try {
			$file = new File(self::FILE_CONFIG, true);

			if (! $file->Exists())
				return;
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		// Get the connection settings for this machine
		//---------------------------------------------

		try {
			$info = $this->GetInfo();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		$connectioninfo["home_ip"] = $info["ip"];
		$connectioninfo["home_gateway"] = $info["gateway"];
		$connectioninfo["home_lanip"] = $info["lanip"];
		$connectioninfo["home_lannetwork"] = $info["lannetwork"];
		$connectioninfo["home_name"] = $info["name"];
		$connectioninfo["home_id"] = $info["id"];

		// Get the connection settings for remote machine
		//-----------------------------------------------

		try {
			$info = $this->GetInfoById($remoteid);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		$connectioninfo["remote_ip"] = $info["ip"];
		$connectioninfo["remote_gateway"] = $info["gateway"];
		$connectioninfo["remote_lanip"] = $info["lanip"];
		$connectioninfo["remote_lannetwork"] = $info["lannetwork"];
		$connectioninfo["remote_name"] = $info["name"];
		$connectioninfo["remote_id"] = $remoteid;

		// Get the shared secret
		//----------------------

		try {
			$file = new File(self::FILE_CONFIG, true);
			$lines = $file->GetContentsAsArray();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		foreach ($lines as $line) {
			if (preg_match("/>\s*$remoteid\s*,/", $line)) {
				$params = preg_split("/,/", $line);
				$secret = trim($params[1]);
				$connectioninfo["secret"] = preg_replace("/\"/", "", $secret);
				break;
			}
		}

		return $connectioninfo;
	}

	/**
	 * Returns the settings on all currently configured connections.
	 *
	 * @return array information on a remote systems
	 * @throws EngineException
	 */

	function GetConnectionData()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$connectionlist = array();
		$connectioninfo = array();

		try {
			$file = new File(self::FILE_CONFIG, true);

			if (! $file->Exists())
				return;
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		// Get the connection settings for this machine
		//---------------------------------------------

		try {
			$info = $this->GetInfo();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		$connectioninfo["home_ip"] = $info["ip"];
		$connectioninfo["home_gateway"] = $info["gateway"];
		$connectioninfo["home_lanip"] = $info["lanip"];
		$connectioninfo["home_lannetwork"] = $info["lannetwork"];
		$connectioninfo["home_name"] = $info["name"];
		$connectioninfo["home_id"] = $info["id"];

		// Get the connection settings for remote machines
		//------------------------------------------------

		try {
			$lines = $file->GetContentsAsArray();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		foreach ($lines as $line) {
			if (preg_match("/^#/", $line))
				continue;

			$params = preg_split("/,/", $line);
			$hosts = preg_split("/->/", preg_replace("/\s+/", "", $params[0]));

			if (!$hosts[1])  // skip unparsable lines
				continue;

			$id = $hosts[1];

			try {
				$info = $this->GetInfoById($id);
			} catch (Exception $e) {
				throw new EngineException($e->GetMessage(), COMMON_WARNING);
			}

			// Connection settings
			//--------------------

			$connectioninfo["remote_ip"] = $info["ip"];
			$connectioninfo["remote_gateway"] = $info["gateway"];
			$connectioninfo["remote_lanip"] = $info["lanip"];
			$connectioninfo["remote_lannetwork"] = $info["lannetwork"];
			$connectioninfo["remote_name"] = $info["name"];
			$connectioninfo["remote_id"] = $id;

			try {
				$connectioninfo["status"] = $this->GetStatus($id);
			} catch (Exception $e) {
				throw new EngineException($e->GetMessage(), COMMON_WARNING);
			}

			$connectioninfo["secret"]  = preg_replace("/\"/", "", $params[1]);

			// Add settings to connection list array
			//--------------------------------------

			$connectionlist[] = $connectioninfo;
		}

		return $connectionlist;
	}

	/**
	 * Returns the status code for connection.
	 *
	 * @param integer $id VPN ID
	 * @return string status code
	 * @throws EngineException
	 */

	function GetStatus($id)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$file = new File(self::PATH_STATUS . "/$id");

			if (! $file->Exists())
				return DynamicVpn::STATUS_INIT;

			$lines = $file->GetContentsAsArray();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		foreach ($lines as $line) {
			if (preg_match("/^status code\s*=/", $line)) {
				$code = preg_replace("/.*=\s*/", "", $line);
				return $code;
			}
		}
	}

	/**
	 * Returns information required to create an IPsec connection.
	 *
	 * Return data array:
	 *  - IP
	 *  - gateway
	 *  - LAN IP
	 *  - LAN network
	 *
	 * @param  integer  $id  VPN ID
	 * @return  array  hash array with VPN information
	 * @throws  EngineException
	 */

	function GetInfoById($id)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Validate
		//---------

		// Let the SDN handle the validation

		try {
			$payload = $this->Request("getinfobyid", "&id=$id");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		$result = explode("|", $payload);
		$info = array();
		$info["id"] = $id;
		$info["ip"] = $result[0];
		$info["gateway"] = $result[1];
		$info["lanip"] = $result[2];
		$info["lannetwork"] = $result[3];
		$info["name"] = $result[4];

		return $info;
	}

	/**
	 * Returns ID number for a given hostname.
	 *
	 * @deprecated
	 * @param  string  $hostname  dynamic DNS hostname
	 * @return  integer  VPN ID
	 * @throws  EngineException
	 */

	function GetIdByHostname($hostname)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Validate
		//---------

		// Let the SDN handle the validation

		try {
			$payload = $this->Request("getidbyhostname", "&hostname=$hostname");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		return trim($payload);
	}


	/**
	 * Returns information required to create an IPsec connection.
	 *
	 * Return data array:
	 *  - IP
	 *  - gateway
	 *  - LAN IP
	 *  - LAN network
	 *
	 * @return  array  hash array with VPN information
	 * @throws  EngineException
	 */

	function GetInfo()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$payload = $this->Request("getinfo");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		$result = explode("|", $payload);
		$info = array();
		$info["ip"] = $result[0];
		$info["gateway"] = $result[1];
		$info["lanip"] = $result[2];
		$info["lannetwork"] = $result[3];
		$info["id"] = $result[4];
		$info["name"] = $result[5];

		return $info;
	}

	/**
	 * Returns configuration settings on all remote systems.
	 *
	 * Return data array:
	 *  - IP
	 *  - gateway
	 *  - LAN IP
	 *  - LAN network
	 *
	 * @return  array  hash array with VPN information
	 * @throws  EngineException
	 */

	function GetRemoteList()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Validate
		//---------

		// Let the SDN handle the validation

		try {
			$payload = $this->Request("getremotelist");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		$ipsecinfo = array();
		$ipseclist = array();

		$lines = explode("\n", $payload);
		foreach ($lines as $line) {
			$result = explode("|", $line);
			$ipsecinfo["ip"] = $result[0];
			$ipsecinfo["gateway"] = $result[1];
			$ipsecinfo["lanip"] = $result[2];
			$ipsecinfo["lannetwork"] = $result[3];
			$ipsecinfo["id"] = $result[4];
			$ipsecinfo["name"] = $result[5];
			$ipseclist[] = $ipsecinfo;
		}

		return $ipseclist;
	}

	/**
	 * Sets a dynamic connection.
	 *
	 * @param  integer  $remoteid  VPN ID of remote system
	 * @param  string  $secret  shared secret
	 * @return  void
	 * @throws  EngineException
	 */

	function SetConnection($remoteid, $secret)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$ipseclocale = new Ipsec(); // Locale

		// Validate
		//---------

		if (!$remoteid || !$secret) {
			throw new EngineException(IPSEC_LANG_ERRMSG_FIELD_MISSING, COMMON_ERROR);
		}

		if (preg_match("/[\'\",]/", $secret)) {
			throw new EngineException(IPSEC_LANG_ERRMSG_SECRET_INVALID, COMMON_ERROR);
		}

		// Sanity check the LAN networks
		// i) they must exist
		// ii) they must not be the same
		//------------------------------

		try {
			$remoteinfo = $this->GetInfoById($remoteid);
			$homeinfo = $this->GetInfo();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		if (!$remoteinfo[lannetwork]) {
			$errmsg = IPSEC_LANG_ERRMSG_SATELLITE_NETWORK_INVALID . strtolower(LOCALE_LANG_MISSING);
			throw new EngineException($errmsg, COMMON_ERROR);
		}

		if (!$homeinfo[lannetwork]) {
			$errmsg = IPSEC_LANG_ERRMSG_HQ_NETWORK_INVALID . strtolower(LOCALE_LANG_MISSING);
			throw new EngineException($errmsg, COMMON_ERROR);
		}

		if ($homeinfo[lannetwork] == $remoteinfo[lannetwork]) {
			$network = new Network(); // For language tags
			$ipsec = new Ipsec(); // For language tags
			$errmsg = IPSEC_LANG_ERRMSG_SAME_NETWORK . " " . NETWORK_LANG_NETWORK . ": " . $homeinfo[lannetwork];
			throw new EngineException($errmsg, COMMON_ERROR);
		}

		try {
			$this->DeleteConnection($remoteid);
			$this->AddConnection($homeinfo, $remoteinfo, $secret);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Adds a dynamic connection.
	 *
	 * @param  array  $homeinfo  VPN info of this system
	 * @param  array  $remoteinfo  VPN info of remote system
	 * @param  string  $secret  shared secret
	 * @return  void
	 * @throws  EngineException
	 */

	function AddConnection($homeinfo, $remoteinfo, $secret)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$file = new File(self::FILE_CONFIG, true);

			if (! $file->Exists())
				$file->Create("root", "root", "0600");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		// TODO - check for duplicate

		// Add new entry
		//--------------

		try {
			$file->AddLines("$homeinfo[id] -> $remoteinfo[id], \"$secret\"\n");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}


	/**
	 * Sends IP/gateway update to the Service Delivery Network.
	 *
	 * @param  string  $ip  IP address
	 * @param  string  $gateway  gateway IP address
	 * @param  string  $lannetwork  LAN network
	 * @param  string  $lanip  LAN IP address
	 * @return  void
	 * @throws  EngineException
	 */

	function SetInfo($ip, $gateway, $lannetwork, $lanip)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$network = new Network();
		$ipsec = new IpSec(); // locale

		if (! $network->IsValidIp($ip)) {
			$errmsg = IPSEC_LANG_ERRMSG_IP_INVALID;
			throw new EngineException($errmsg, COMMON_ERROR);
		}

		if (! $network->IsValidIp($gateway)) {
			$errmsg = IPSEC_LANG_ERRMSG_GATEWAY_INVALID;
			throw new EngineException($errmsg, COMMON_ERROR);
		}

		if (! $network->IsValidIp($lanip)) {
			$errmsg = IPSEC_LANG_ERRMSG_LANIP_INVALID;
			throw new EngineException($errmsg, COMMON_ERROR);
		}

		if (! $network->IsValidNetwork($lannetwork)) {
			$errmsg = IPSEC_LANG_ERRMSG_LANNETWORK_INVALID;
			throw new EngineException($errmsg, COMMON_ERROR);
		}

		$this->Request("setinfo", "&ip=$ip&gateway=$gateway&lannetwork=$lannetwork&lanip=$lanip");
	}

	/**
	 * Sends IP/gateway update to the Service Delivery Network.
	 *
	 * The required IP, gateway, LAN IP and LAN network are auto-detected.
	 *
	 * On a multi-WAN box, the preferred interface for VPN can specified
	 * in /etc/firewall using the VPNIF parameter.  Alternatively, the
	 * primary interface returned from GetInterfaceDefinition is used.
	 *
	 * @param boolean $checkcache flag if local cache should be used
	 * @return  void
	 * @throws  EngineException
	 */

	function SetInfoAuto($checkcache)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$firewall = new Firewall();

		// Grab LAN information
		//---------------------

		try {
			$lanif = $firewall->GetInterfaceDefinition(Firewall::CONSTANT_LAN);
			$interface = new Iface($lanif);
			$lanisconfigured = $interface->IsConfigured();

			if (!$lanisconfigured) // bail if no LAN
				return;

			$lanip = $interface->GetLiveIp();

			$lannetmask = $interface->GetLiveNetmask();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		// Grab preferred WAN interface
		//-----------------------------

		// TODO: move this to firewall class, or move config value
		$vpnif = "";

		try {
			$file = new File(Firewall::FILE_CONFIG);
			$vpnif = $file->LookupValue("/^VPNIF=/");
		} catch (FileNoMatchException $e) {
			$vpnif = "";
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}

		$vpnif = preg_replace("/\"/", "", $vpnif);
		$vpnif = trim($vpnif);

		// Grab available WAN interfaces
		//------------------------------

		$workingextifsknown = true;

		$syswatch = new Syswatch();

		try {
			$workingextiflist = $syswatch->GetWorkingExternalInterfaces();
		} catch (SyswatchUnknownStateException $e) {
			$workingextifsknown = false;
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		// Determine the WAN interface to use
		//-----------------------------------

		// Use syswatch to see if preferred WAN is working 
		// otherwise fall back to:
		// - the preferred VPN interface, or if all else fails,
		// - first WAN interface defined by EXTIF in /etc/firewall

		if ($workingextifsknown) {
			// Use preferred WAN interface if available

			if ($vpnif && $workingextiflist && (in_array($vpnif, $workingextiflist))) {
				$extif = $vpnif;

			// Otherwise, use the first working WAN interface
			} else if (isset($workingextiflist[0])) {
				$extif = $workingextiflist[0];

			// No working WAN interfaces?  Bail.
			} else {
				return;
			}
		} else {
			if ($vpnif) {
				$extif = $vpnif;
			} else {
				try {
					$extif = $firewall->GetInterfaceDefinition(Firewall::CONSTANT_EXTERNAL);
				} catch (Exception $e) {
					throw new EngineException($e->GetMessage(), COMMON_WARNING);
				}
			}
		}

		// Grab IP on WAN interface
		//-------------------------

		$interface = new Iface($extif);

		try {
			$ip = $interface->GetLiveIp();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		// Grab route for given WAN interface
		//-----------------------------------

		$routes = new Routes();
		$gatewayinfo = array();

		try {
			$gatewayinfo = $routes->GetDefaultInfo();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		// If no route, just bail
		if (isset($gatewayinfo[$extif]))
			$gateway = $gatewayinfo[$extif];
		else
			return;

		// Compute LAN network and prefixes
		//---------------------------------

		// TODO: move this to firewall class, or move config value
		$lannetwork = "";

		try {
			$file = new File(Firewall::FILE_CONFIG);
			$lannetwork = $file->LookupValue("/^LANNET=/");
		} catch (FileNoMatchException $e) {
			$lannetwork = "";
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}

		$lannetwork = preg_replace("/\"/", "", $lannetwork);
		$lannetwork = trim($lannetwork);

		if (empty($lannetwork)) {
			if (!$lanip || !$lannetmask)
				return;

			try {
				$network = new Network();
				$prefix = $network->GetPrefix($lannetmask);
				$lannetwork = $network->GetNetworkAddress($lanip, $lannetmask) . "/" . $prefix;
			} catch (Exception $e) {
				throw new EngineException($e->GetMessage(), COMMON_WARNING);
			}
		}

		$lannetwork = trim($lannetwork);

		// Don't send update if details haven't changed and caching is allowed
		//--------------------------------------------------------------------
		
		$cache = new File(self::PATH_STATUS . "/cache");

		if ($checkcache) {
			try {
				if ($cache->Exists()) {
					$line = $cache->GetContents();
					if ($line == "$ip $gateway $lannetwork $lanip")
						return; 
				}
			} catch (Exception $e) {
				throw new EngineException($e->GetMessage(), COMMON_WARNING);
			}
		}

		try {
			$this->Request("setinfo", "&ip=$ip&gateway=$gateway&lannetwork=$lannetwork&lanip=$lanip");
			Logger::Syslog(self::LOG_TAG, "Dynamic VPN updated with IP $ip and gateway $gateway");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		// Reset cache file

		try {
			if ($cache->Exists())
				$cache->Delete();

			$cache->Create("root", "root", "0644");
			$cache->AddLines("$ip $gateway $lannetwork $lanip\n");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}
}

// vim: syntax=php ts=4
?>
