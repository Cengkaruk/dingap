<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2003-2007 Point Clark Networks.
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
 * Network tools.
 *
 * General tools used in dealing with the network.
 *
 * @package Api
 * @subpackage Network
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2007, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('Engine.class.php');
require_once('Locale.class.php');
require_once('Hosts.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Network tools.
 *
 * General tools used in dealing with the network.
 *
 * @package Api
 * @subpackage Network
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class Network extends Engine
{
	///////////////////////////////////////////////////////////////////////////////
	// F I E L D S
	///////////////////////////////////////////////////////////////////////////////

	protected $prefixlist = array();
	
	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Network constructor.
	 *
	 * @return void
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		parent::__construct();

		$this->prefixlist = array(
			0 => "0.0.0.0",
			1 => "128.0.0.0",
			2 => "192.0.0.0",
			3 => "224.0.0.0",
			4 => "240.0.0.0",
			5 => "248.0.0.0",
			6 => "252.0.0.0",
			7 => "254.0.0.0",
			8 => "255.0.0.0",
			9 => "255.128.0.0",
			10 => "255.192.0.0",
			11 => "255.224.0.0",
			12 => "255.240.0.0",
			13 => "255.248.0.0",
			14 => "255.252.0.0",
			15 => "255.254.0.0",
			16 => "255.255.0.0",
			17 => "255.255.128.0",
			18 => "255.255.192.0",
			19 => "255.255.224.0",
			20 => "255.255.240.0",
			21 => "255.255.248.0",
			22 => "255.255.252.0",
			23 => "255.255.254.0",
			24 => "255.255.255.0",
			25 => "255.255.255.128",
			26 => "255.255.255.192",
			27 => "255.255.255.224",
			28 => "255.255.255.240",
			29 => "255.255.255.248",
			30 => "255.255.255.252",
			31 => "255.255.255.254",
			32 => "255.255.255.255"
		);

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Returns netmask for give prefix (bitmask)
	 *
	 * @param string $prefix prefix
	 * @return string netmask
	 */

	function GetNetmask($prefix)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (isset($this->prefixlist[$prefix]))
			return $this->prefixlist[$prefix];
		else 
			throw new ValidationException(NETWORK_LANG_NETMASK . " - " . LOCALE_LANG_INVALID);
	}

	/**
	 * Returns prefix (bitmask) for given netmask
	 *
	 * @param string $netmask netmask
	 * @return int bitmask
	 */

	function GetPrefix($netmask)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$netmasklist = array_flip($this->prefixlist);

		if (isset($netmasklist[$netmask]))
			return $netmasklist[$netmask];
		else 
			throw new ValidationException(NETWORK_LANG_NETMASK . " - " . LOCALE_LANG_INVALID);
	}

	/**
	 * Returns network address for given IP and netmask.
	 *
	 * @param  string  $ip  IP address
	 * @param  string  $netmask  netmask
	 * @return  string  network  address
	 */

	function GetNetworkAddress($ip, $netmask)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$ip_long = ip2long($ip);
		$nm = ip2long($netmask);

		if ($ip_long == -1) {
			$errmsg = NETWORK_LANG_IP . ": ($ip) - " . strtolower(LOCALE_LANG_INVALID);
			throw new EngineException($errmsg, COMMON_ERROR);
		}

		if ($nm == -1) {
			$errmsg = NETWORK_LANG_NETMASK . ": ($netmask) - " . strtolower(LOCALE_LANG_INVALID);
			throw new EngineException($errmsg, COMMON_ERROR);
		}

		$nw = ($ip_long & $nm);
		return long2ip($nw);
	}

	/**
	 * Returns broadcast address for given IP and netmask.
	 *
	 * @param  string  $ip  IP address
	 * @param  string  $netmask  netmask
	 * @return  string  broadcast address
	 */

	function GetBroadcastAddress($ip, $netmask)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$ip_long = ip2long($ip);
		$nm = ip2long($netmask);

		if ($ip_long == -1) {
			$errmsg = NETWORK_LANG_IP . ": ($ip) - " . strtolower(LOCALE_LANG_INVALID);
			throw new EngineException($errmsg, COMMON_ERROR);
		}

		if ($nm == -1) {
			$errmsg = NETWORK_LANG_NETMASK . ": ($netmask) - " . strtolower(LOCALE_LANG_INVALID);
			throw new EngineException($errmsg, COMMON_ERROR);
		}

		$nw = ($ip_long & $nm);
		$bc = $nw | (~$nm);
		return long2ip($bc);
	}

	/**
	 * Checks if IP address is in private range.
	 *
	 * @param  string  $ip  IP address
	 * @return  boolean  true if IP is private
	 */

	function IsPrivateIp($ip)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->IsValidIp($ip)) {
			$errmsg = NETWORK_LANG_IP . ': (' . $ip . ') - ' . strtolower(LOCALE_LANG_INVALID);
			throw new ValidationException($errmsg);
		}

		try {
			$ip_long = ip2long($ip);

			if (
				( ($ip_long >= ip2long("10.0.0.0")) && ($ip_long <= ip2long("10.255.255.255")) ) ||
				( ($ip_long >= ip2long("172.16.0.0")) && ($ip_long <= ip2long("172.31.255.255")) ) ||
				( ($ip_long >= ip2long("192.168.0.0")) && ($ip_long <= ip2long("192.168.255.255")) )
			)
				return true;
			else
				return false;
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Validates a hostname alias.
	 *
	 * @param string $alias alias
	 * @return boolean true if alias is valid
	 */

	function IsValidHostnameAlias($alias)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$state = preg_match('/^([0-9a-zA-Z\.\-_]+)$/', $alias);

		if (! $state) {
			$errmsg = NETWORK_LANG_HOSTNAME . ' (' . $alias . ') - ' . strtolower(LOCALE_LANG_INVALID);
			$this->AddValidationError($errmsg, __METHOD__, __LINE__);
			return false;
		}

		return true;
	}

	/**
	 * Validates a hostname.
	 *
	 * @param  string  $hostname  hostname
	 * @return  boolean  true if hostname is valid
	 */

	function IsValidHostname($hostname)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$state = preg_match('/^([0-9a-zA-Z\.\-_]+)$/', $hostname);

		if (! $state) {
			$errmsg = NETWORK_LANG_HOSTNAME . " - " . strtolower(LOCALE_LANG_INVALID);
			$this->AddValidationError($errmsg, __METHOD__, __LINE__);
			return false;
		}

		if (substr_count($hostname, ".") == 0 && !preg_match("/^localhost$/i", $hostname)) {  
			$errmsg = NETWORK_LANG_ERRMSG_HOSTNAME_MUST_HAVE_A_PERIOD;
			$this->AddValidationError($errmsg, __METHOD__, __LINE__);
			return false;
		}

		return true;
	}

	/**
	 * Validates a domain name.
	 *
	 * @param  string  $domain  domain name
	 * @return  boolean  true if domain is valid
	 */

	function IsValidDomain($domain)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Allow underscores

		if (preg_match('/^([0-9a-zA-Z\.\-_]+)$/', $domain)) {
			if (substr_count($domain, ".") == 0) {
				$errmsg = NETWORK_LANG_ERRMSG_DOMAIN_MUST_HAVE_A_PERIOD;
				$this->AddValidationError($errmsg, __METHOD__, __LINE__);
				return false;
			} else {
				return true;
			}
		} else {
			$errmsg = NETWORK_LANG_DOMAIN . ' - ' . strtolower(LOCALE_LANG_INVALID);
			$this->AddValidationError($errmsg, __METHOD__, __LINE__);
			return false;
		}
	}

	/**
	 * Validates an IP address.
	 *
	 * @param  string  $ip  IP address
	 * @return  boolean  true if IP is valid
	 */

	function IsValidIp($ip)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$ip_long = ip2long($ip);
		if ($ip_long == -1 || $ip_long === FALSE || $ip == $ip_long) {
			$errmsg = NETWORK_LANG_IP . ': (' . $ip . ') - ' . strtolower(LOCALE_LANG_INVALID);
			$this->AddValidationError($errmsg, __METHOD__, __LINE__);
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Checks to see if test IP is on the network given by IP/netmask.
	 *
	 * @param string $ip IP address
	 * @param string $netmask netmask
	 * @param string $testip test IP address
	 * @return boolean true if gateway IP is valid
	 */

	function IsValidIpOnNetwork($ip, $netmask, $testip)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$bin_ip = ip2long($ip);

		if ($bin_ip == false) {
			$errmsg = NETWORK_LANG_IP . ': (' . $ip . ') - ' . strtolower(LOCALE_LANG_INVALID);
			$this->AddValidationError($errmsg, __METHOD__, __LINE__);
			return false;
		}

		$mask = ip2long($netmask);
		$bin_netmask = long2ip($mask) == $netmask ? $mask : 0xffffffff << (32 - $netmask);

		$bin_gateway = ip2long($testip);

		if ($bin_gateway == false) {
			$errmsg = NETWORK_LANG_GATEWAY . ': (' . $testip . ') - ' . strtolower(LOCALE_LANG_INVALID);
			$this->AddValidationError($errmsg, __METHOD__, __LINE__);
			return false;
		}

		$network = $bin_ip & $bin_netmask;
		$broadcast = $network | (~$bin_netmask);

		if ($bin_ip == $bin_gateway || $bin_gateway <= $network || $bin_gateway >= $broadcast) {
			$errmsg = NETWORK_LANG_GATEWAY . ': (' . $testip . ') - ' . strtolower(LOCALE_LANG_INVALID);
			$this->AddValidationError($errmsg, __METHOD__, __LINE__);
			return false;
		}

		return true;
	}

	/**
	 * Validates a MAC address.
	 *
	 * @param  string  $mac  MAC address
	 * @return  boolean  true if MAC address is valid
	 */

	function IsValidMac($mac)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$mac = strtoupper($mac);

		if(eregi("^[0-9A-F]{2}:[0-9A-F]{2}:[0-9A-F]{2}:[0-9A-F]{2}:[0-9A-F]{2}:[0-9A-F]{2}$", $mac)) {
			return true;
		} else {
			$errmsg = NETWORK_LANG_MAC_ADDRESS . ": ($mac) - " . strtolower(LOCALE_LANG_INVALID);
			$this->AddValidationError($errmsg, __METHOD__, __LINE__);
			return false;
		}
	}

	/**
	 * Validates an IP address range.
	 *
	 * @param  string  $from  starting IP address
	 * @param  string  $to  ending IP address
	 * @return  boolean  true if IP address range is valid
	 */

	function IsValidIpRange($from, $to)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if(!$this->IsValidIp($from))
			return false;

		if(!$this->IsValidIp($to))
			return false;

		try {
			// Convert dotted-quad IP addresses to decimal (unsigned integer)
			$parts = explode(".", $from);

			$fromdec = ($parts[0] << 24) + ($parts[1] << 16) + ($parts[2] << 8) + $parts[3];

			$parts = explode(".", $to);

			$todec = ($parts[0] << 24) + ($parts[1] << 16) + ($parts[2] << 8) + $parts[3];

			if($fromdec >= $todec) {
				$errmsg = NETWORK_LANG_IP_RANGE . " - " . strtolower(LOCALE_LANG_INVALID);
				$this->AddValidationError($errmsg, __METHOD__, __LINE__);
				return false;
			}
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		return true;
	}

	/**
	 * Validates a netmask.
	 *
	 * @param string $netmask netmask
	 * @return boolean true if netmask is valid
	 */

	function IsValidNetmask($ip)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$netmasklist = array_flip($this->prefixlist);

		if (isset($netmasklist[$ip]))
			return true;
		else 
			return false;
	}

	/**
	 * Validates a network address.
	 *
	 * @param  string  $network  network address
	 * @return  boolean  true if network address is valid
	 */

	function IsValidNetwork($network)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$matches = array();

		if (preg_match("/^(.*)\/(.*)$/", $network, $matches)) {

			$baseip = $matches[1];
			$netmask_or_prefix = $matches[2];

			if ($this->IsValidPrefix($netmask_or_prefix)) {
				// Convert a prefix (/24) to a netmask (/255.255.255.0)
				$netmask = $this->GetNetmask($netmask_or_prefix);
			} else if ($this->IsValidNetmask($netmask_or_prefix)) {
				$netmask = $netmask_or_prefix;
			} else {
				return false;
			}

			// Make sure the base IP is valid
			$check = $this->GetNetworkAddress($baseip, $netmask);

			if ($check != $baseip)
				return false;
			else
				return true;
			
		} else {
			return false;
		}
	}

	/**
	 * Validates a port number.
	 *
	 * @param  string  $port  port number
	 * @return  boolean  true if port number is valid
	 */

	function IsValidPort($port)
	{
		if (! preg_match("/^\d+$/", $port)) {
			$errmsg = NETWORK_LANG_ERRMSG_PORT_INVALID;
			$this->AddValidationError($errmsg, __METHOD__, __LINE__);
			return false;
		}

		if (($port > 65535) || ($port <= 0)) {
			$errmsg = NETWORK_LANG_ERRMSG_PORT_INVALID;
			$this->AddValidationError($errmsg, __METHOD__, __LINE__);
			return false;
		}

		return true;
	}

	/**
	 * Validates a port number range.
	 *
	 * @param  string  $from  starting port number
	 * @param  string  $to  ending port number
	 * @return  boolean  true if port number range is valid
	 */

	function IsValidPortRange($from, $to)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if ((! preg_match("/^\d+$/", $from)) || (! preg_match("/^\d+$/", $from))) {
			$errmsg = NETWORK_LANG_ERRMSG_PORT_RANGE_INVALID;
			$this->AddValidationError($errmsg, __METHOD__, __LINE__);
			return false;
		}

		if (($from > 65535) || ($from <= 0) || ($to > 65535) || ($to <= 0)) {
			$errmsg = NETWORK_LANG_ERRMSG_PORT_RANGE_INVALID;
			$this->AddValidationError($errmsg, __METHOD__, __LINE__);
			return false;
		}

		if ($from > $to) {
			$errmsg = NETWORK_LANG_ERRMSG_PORT_RANGE_INVALID;
			$this->AddValidationError($errmsg, __METHOD__, __LINE__);
			return false;
		}

		return true;
	}

	/**
	 * Validates a prefix (bitmask).
	 *
	 * @param string $prefix prefix
	 * @return boolean true if prefix is valid
	 */

	function IsValidPrefix($prefix)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (isset($this->prefixlist[$prefix]))
			return true;
		else 
			return false;
	}

	/**
	 * Validates a protocol.
	 *
	 * @param  string  $protocol  protocol
	 * @return  boolean  true if protocol is valid
	 */

	function IsValidProtocol($protocol)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (preg_match("/^(TCP|UDP)$/", $protocol)) {
			return true;
		} else {
			$errmsg = NETWORK_LANG_ERRMSG_PROTOCOL_INVALID;
			$this->AddValidationError($errmsg, __METHOD__, __LINE__);
			return false;
		}
	}

	/**
	 * Checks if IP address/hostname is bound to a local interface.
	 *
	 * @param  string  $add  IP address or hostname
	 * @return  boolean  true if addr is valid
	 */

	function IsLocalIp($addr)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$ip = gethostbyname($addr);

			if(ip2long($ip) == -1)
				return false;

			$ph = popen("/sbin/ip -o addr list | egrep 'inet [0-9]{1,3}' | " .
						"sed -e 's/^.*inet \\([0-9]*\\.[0-9]*\\.[0-9]*\\.[0-9]*\\).*/\\1/g'", "r");

			while($ph && !feof($ph)) {
				if($ip == chop(fgets($ph, 4096)))
					return true;
			}

			pclose($ph);

			return false;
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
	}
}

// vim: syntax=php ts=4
?>
