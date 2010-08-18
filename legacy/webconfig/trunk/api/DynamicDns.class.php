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
 * Dynamic DNS class.
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

require_once('Firewall.class.php');
require_once('Iface.class.php');
require_once('IpReferrer.class.php');
require_once('Network.class.php');
require_once('Syswatch.class.php');
require_once('WebServices.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Dynamic DNS class.
 *
 * @package Api
 * @subpackage WebServices
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class DynamicDns extends WebServices
{
	///////////////////////////////////////////////////////////////////////////////
	// V A R I A B L E S
	///////////////////////////////////////////////////////////////////////////////

	const FILE_STATE = '/var/lib/dynamicdns';
	const LOG_TAG = 'dynamicdns';

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Dynamic Dns constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		parent::__construct('DynamicDNS');

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Returns general information on the Dynamic DNS record.
	 *
	 * The information returned in a hash array:
	 * - ip
	 * - lastupdate
	 * - domain
	 *
	 * @return array hash array with DNS information
	 * @throws WebServicesRemoteException
	 */

	function GetInfo()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$info = array();
		$payload = $this->Request("getinfo", null);
		$result = explode("|", $payload);

		// payload format -- dns_ip|dns_lastmod|domain
		$info['ip'] = $result[0];
		$info['lastupdate'] = $result[1];
		$info['domain'] = $result[2];

		return $info;
	}

	/**
	 * Sends IP update to the Service Delivery Network.
	 *
	 * By default, this method will send the external IP address on your
	 * system.  If you want to override this behavior, specify the IP address.
	 * The dynamic DNS system will ignore an IP address in a private network 
	 * (eg 192.168.x.x) and use the referrer IP instead.
	 *
	 * @param boolean $checkcache flag if local cache should be used
	 * @return void
	 * @throws EngineException, WebServicesRemoteException
	 */

	function SetInfo($checkcache = true)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Grab preferred WAN interface
		//-----------------------------

		// TODO: move this to firewall class, or move config value
		$dnsif = "";

		try {
			$file = new File(Firewall::FILE_CONFIG);
			$dnsif = $file->LookupValue("/^DNSIF=/");
		} catch (FileNoMatchException $e) {
			$dnsif = "";
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}

		$dnsif = preg_replace("/\"/", "", $dnsif);
		$dnsif = trim($dnsif);

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

		$firewall = new Firewall();

		if ($workingextifsknown) {
			// Use preferred WAN interface if available

			if ($dnsif && $workingextiflist && (in_array($dnsif, $workingextiflist))) {
				$extif = $dnsif;

			// Otherwise, use the first working WAN interface
			} else if (isset($workingextiflist[0])) {
				$extif = $workingextiflist[0];

			// No working WAN interfaces?  Bail.
			} else {
				return;
			}
		} else {
			if ($dnsif) {
				$extif = $dnsif;
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

		try {
			$interface = new Iface($extif);
			$ip = $interface->GetLiveIp();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		// Validate
		//---------

		$network = new Network();

		if (! $network->IsValidIp($ip))
			throw new ValidationException(NETWORK_LANG_IP . ': (' . $ip . ') - ' . strtolower(LOCALE_LANG_INVALID));

		// Get referrer IP if we're on a LAN
		//----------------------------------

		if ($network->IsPrivateIp($ip)) {
			$ipreferrer = new IpReferrer();
			$ip = $ipreferrer->Get();
			Logger::Syslog(self::LOG_TAG, "Dynamic DNS detected Internet IP $ip");
		}

		// Don't send update if IP hasn't changed and caching is allowed
		//--------------------------------------------------------------

		$cachefile = new File(self::FILE_STATE);

		if ($checkcache) {
			if ($cachefile->Exists()) {
				try {
					$cacheip = $cachefile->GetContents();
					$cacheip = trim($cacheip);
					if ($ip == $cacheip) {
						Logger::Syslog(self::LOG_TAG, "Dynamic DNS update not required on $ip");
						return;
					}
				} catch (Exception $e) {
					// Non-fatal
				}
			}
		}

		// Send update
		//------------

		try {
			$payload = $this->Request("SetInfo", "&ip=$ip");
			Logger::Syslog(self::LOG_TAG, "Dynamic DNS updated with $ip");
		} catch (WebServicesNotRegisteredException $e) {
			throw new WebServicesNotRegisteredException();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		// Save it in our local cache file
		//--------------------------------

		try {
			if ($cachefile->Exists())
				$cachefile->Delete();

			$cachefile->Create("root", "root", "0644");
			$cachefile->AddLines("$ip\n");
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
