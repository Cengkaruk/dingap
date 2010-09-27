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
 * Dnsmasq caching nameserver.
 *
 * @package Api
 * @subpackage Daemon
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('Daemon.class.php');
require_once('Ethers.class.php');
require_once('File.class.php');
require_once('Firewall.class.php');
require_once('Iface.class.php');
require_once('IfaceManager.class.php');
require_once('Network.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Active lease wrapper.
 *
 * @package Soap
 * @subpackage Soap
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

class DnsMasq_GetActiveLeasesResponse
{
	public $mac;
	public $end;
	public $ip;
	public $hostname;
}

/**
 * Static lease wrapper.
 *
 * @package Soap
 * @subpackage Soap
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

class DnsMasq_GetStaticLeasesResponse
{
	public $mac;
	public $ip;
	public $hostname;
}

$_SOAP['CLASS_MAP']['GetActiveLeasesResponse'] = 'DnsMasq_GetActiveLeasesResponse';
$_SOAP['CLASS_MAP']['GetStaticLeasesResponse'] = 'DnsMasq_GetStaticLeasesResponse';

/**
 * Dnsmasq caching nameserver.
 *
 * @package Api
 * @subpackage Daemon
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

class DnsMasq extends Daemon
{
	///////////////////////////////////////////////////////////////////////////////
	// F I E L D S
	///////////////////////////////////////////////////////////////////////////////

	protected $is_loaded = false;
	protected $config = array();
	protected $subnets = array();

	const FILE_DHCP = '/etc/dnsmasq/dhcp.conf';
	const FILE_CONFIG = '/etc/dnsmasq.conf';
	const FILE_LEASES = '/var/lib/misc/dnsmasq.leases';
	const DEFAULT_LEASETIME = "12"; // in hours
	const CONSTANT_UNLIMITED_LEASE = "infinite";

	const OPTION_SUBNET_MASK = 1;
	const OPTION_GATEWAY = 3;
	const OPTION_NAME_SERVERS = 6;
	const OPTION_BROADCAST = 28;
	const OPTION_WINS = 44;
	const OPTION_NETBIOS_NODE_TYPE = 46;
	const OPTION_TFTP = 66;
	const OPTION_NTP = 42;

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * DnsMasq constructor.
	 */

	public function __construct()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		parent::__construct('dnsmasq');

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Adds a static lease to DHCP server.
	 *
	 * @param string $mac MAC address
	 * @param string $ip IP address
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	function AddStaticLease($mac, $ip)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$isvalid = true;
		$network = new Network();

		if (! $network->IsValidMac($mac)) {
			$this->AddValidationError(implode($network->GetValidationErrors(true)), __METHOD__, __LINE__);
			$isvalid = false;
		}

		if (! $network->IsValidIp($ip)) {
			$this->AddValidationError(implode($network->GetValidationErrors(true)), __METHOD__, __LINE__);
			$isvalid = false;
		}

		if (! $isvalid)
			throw new ValidationException(LOCALE_LANG_INVALID);

		try {
			$ethers = new Ethers();
			$exists = $ethers->GetHostnameByMac($mac);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		if (!empty($exists))
			throw new EngineException(ETHERS_LANG_MAC_ALREADY_EXISTS, COMMON_ERROR);

		try {
			$ethers->AddEther($mac, $ip);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Adds info for specific network subnet.
	 *
	 * @param string $interface network interface
	 * @param string $gateway gateway IP address
	 * @param array $dns DNS server list
	 * @param string $start starting IP for DHCP range
	 * @param string $start ending IP for DHCP range
	 * @param int $leasetime lease time in hours
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	function AddSubnet($interface, $gateway, $start, $end, $dns, $wins, $leasetime = DnsMasq::DEFAULT_LEASETIME, $tftp = "", $ntp = "")
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->IsValidSubnet($interface, $gateway, $start, $end, $dns, $wins, $leasetime, $tftp, $ntp))
			throw new ValidationException(LOCALE_LANG_INVALID);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		$dnsarray = array();
		$dnslist = "";

		if (count($dns) > 0) {
			foreach ($dns as $server) {
				if (!empty($server))
					$dnsarray[] = $server;
			}

			$dnslist = implode(",", $dnsarray);
		}

		try {
			$network = new Network();
			$ethinfo = new Iface($interface);
			$ip = $ethinfo->GetLiveIp();
			$netmask = $ethinfo->GetLiveNetmask();
			$broadcast = $network->GetBroadcastAddress($ip, $netmask);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		if (isset($this->config['dhcp-range']['count']))
			$range_count = $this->config['dhcp-range']['count'];
		else
			$range_count = 1;

		if (isset($this->config['dhcp-option']['count']))
			$option_count = $this->config['dhcp-option']['count'];
		else
			$option_count = 1;

		if ($leasetime != self::CONSTANT_UNLIMITED_LEASE)
			$leasetime = $leasetime . "h";

		$this->config['dhcp-range']['line'][++$range_count] = "$interface,$start,$end,$leasetime";

		if ($netmask)
			$this->config['dhcp-option']['line'][++$option_count] = "$interface," . self::OPTION_SUBNET_MASK . ",$netmask";

		if ($gateway)
			$this->config['dhcp-option']['line'][++$option_count] = "$interface," . self::OPTION_GATEWAY . ",$gateway";

		if (! empty($dnslist))
			$this->config['dhcp-option']['line'][++$option_count] = "$interface," . self::OPTION_NAME_SERVERS . ",$dnslist";

		if ($broadcast)
			$this->config['dhcp-option']['line'][++$option_count] = "$interface," . self::OPTION_BROADCAST . ",$broadcast";

		if ($wins) {
			$this->config['dhcp-option']['line'][++$option_count] = "$interface," . self::OPTION_WINS . ",$wins";
			$this->config['dhcp-option']['line'][++$option_count] = "$interface," . self::OPTION_NETBIOS_NODE_TYPE . ",8";
		}

		if ($tftp)
			$this->config['dhcp-option']['line'][++$option_count] = "$interface," . self::OPTION_TFTP . ",\"$tftp\"";

		if ($ntp)
			$this->config['dhcp-option']['line'][++$option_count] = "$interface," . self::OPTION_NTP . ",$ntp";

		$this->_SaveConfig();
	}

	/**
	 * Deletes a static lease from DHCP server.
	 *
	 * @param string $mac MAC address
	 * @return void
	 * @throws EngineException
	 */

	function DeleteStaticLease($mac)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$ethers = new Ethers();
			$ethers->DeleteEther($mac);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Removes DHCP subnet from configuration file.
	 *
	 * @param string $interface network interface
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	function DeleteSubnet($interface)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		foreach ($this->config as $key => $details) {
			foreach ($details['line'] as $lineno => $value) {
				if (preg_match("/^$interface,/", $value))
					unset($this->config[$key]['line'][$lineno]);
			}
		}

		$this->_SaveConfig();
	}

	/**
	 * Enables a default DHCP range on all LAN interfaces.
	 *
	 * @return void
	 * @throws EngineException
	 */

	function EnableDhcpAutomagically()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		try {
			$firewall = new Firewall();
			$mode = $firewall->GetMode();
			$interfaces = $this->GetDhcpInterfaces();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		// This logic should really go into the firewall class.
		// Enable DHCP if:
		// - role is LAN
		// - role is External and mode is standalone

		$dhcpifs = array();

		foreach ($interfaces as $interface) {
			if (
				($firewall->GetInterfaceRole($interface) == Firewall::CONSTANT_LAN) ||
				($mode == Firewall::CONSTANT_STANDALONE) || 
				($mode == Firewall::CONSTANT_TRUSTEDSTANDALONE)
				) { 
				$dhcpifs[] = $interface;
			}
		}

		$netcheck = new Network();

		foreach ($dhcpifs as $interface) {
			try {
				$ethinfo = new Iface($interface);
				$ip = $ethinfo->GetLiveIp();
				$netmask = $ethinfo->GetLiveNetmask();
				$network = $netcheck->GetNetworkAddress($ip, $netmask);
				$broadcast = $netcheck->GetBroadcastAddress($ip, $netmask);

				// Add some intelligent defaults
				$long_nw = ip2long($network);
				$long_bc = ip2long($broadcast);
				$start = long2ip($long_bc - round(($long_bc - $long_nw )* 3 / 5,0) - 2);
				$end = long2ip($long_bc - 1);
				$dns = array($ip);
				$dns[] = $ip;

				$this->AddSubnet($interface, $ip, $start, $end, $dns, "");
			} catch (ValidationException $e) {
				// Not fatal, keep going
			} catch (Exception $e) {
				throw new EngineException($e->GetMessage(), COMMON_ERROR);
			}
		}

		try {
			$this->SetDhcpState(true);
			$this->SetBootState(true);
			$this->Restart();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Determines if subnet already exists.
	 *
	 * @param string $interface network interface
	 * @return boolean true if subnet exists
	 * @throws EngineException
	 */

	function SubnetExists($interface)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		if (isset($this->subnets[$interface]["start"]))
			return true;
		else
			return false;
	}

	/**
	 * Returns authoritative status.
	 *
	 * @return boolean true if authoritative
	 * @throws EngineException
	 */

	function GetAuthoritativeState()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		if (isset($this->config['dhcp-authoritative']['line'][1]))
			return true;
		else
			return false;
	}

	/**
	 * Returns a list of network interfaces that can use DHCP.
	 *
	 * PPTP and other interfaces are not included in the list.
	 *
	 * @return array list of valid DHCP interfaces
	 * @throws EngineException
	 */

	function GetDhcpInterfaces()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$interfaces = new IfaceManager();

		$ethlist = $interfaces->GetInterfaces(true, true);
		$validlist = array();

		foreach ($ethlist as $eth) {
			$ethinfo = new Iface($eth);

			// Skip non-configurable interfaces
			if (! $ethinfo->IsConfigurable())
				continue;

			// Skip virtual interfaces... for now
			if (preg_match("/:/", $eth))
				continue;

			$validlist[] = $eth;
		}

		return $validlist;
	}

	/** 
	 * Returns status of DHCP server.
	 *
	 * @return boolean status of DHCP server
	 * @throws EngineException
	 */

	function GetDhcpState()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		if (isset($this->config['conf-file']['line'][1]))
			return true;
		else
			return false;
	}

	/**
	 * Returns default domain name.
	 *
	 * @return string default domain name
	 * @throws EngineException
	 */

	function GetDomainName()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		if (isset($this->config['domain']['line'][1]))
			return $this->config['domain']['line'][1];
		else
			return "";
	}

	/**
	 * Returns merged list of active and static leases.
	 *
	 * Lease data is keyed by MAC address, but sorted by IP.
	 *
	 * @return array array containing lease data
	 * @throws EngineException
	 */

	function GetLeases()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		/* The MAC/IP pair in the static leases (/etc/ethers) and active
		 * leases (/var/lib/misc/dnsmasq.leases) could be different.  For
		 * example, a machine may grab a dynamic lease at first but an
		 * administrator could later add a static entry for future use.
		 *
		 * For this reason, the list of leases is keyed on the MAC/IP pairing.
		 * There is a little trickery going on to handle the key.  First, the
		 * ip2long function is used so that 192.168.1.20 comes before
		 * 192.168.1.100.  In addition, the MAC address becomes a decimal,
		 * e.g. 11:22:33:44:55:66 becomes 0.112233445566.  The unique keys
		 * would look similar to 3232236157.112233445566.
		 */

		$active = $this->GetActiveLeases();
		$static = $this->GetStaticLeases();
		$leases = array();
		$ip_ndx = array();

		foreach ($static as $mac => $details) {
			$key = sprintf("%u.%s", ip2long($details['ip']), hexdec(preg_replace("/\:/", "", $mac)) );

			$leases[$key]['static_mac'] = $mac;
			$leases[$key]['static_ip'] = $details['ip'];
			$leases[$key]['is_static'] = true;
			$leases[$key]['hostname'] = $details['hostname'];
		}

		foreach ($active as $mac => $details) {
			$key = sprintf("%u.%s", ip2long($details['ip']), hexdec(preg_replace("/\:/", "", $mac)) );

			$leases[$key]['active_mac'] = $mac;
			$leases[$key]['active_ip'] = $details['ip'];
			$leases[$key]['active_end'] = $details['end'];
			$leases[$key]['is_active'] = true;
			$leases[$key]['hostname'] = $details['hostname'];
		}

		ksort($leases);

		// Go through array and set missing indexes
		foreach ($leases as $key => $details) {
			$leases[$key]['static_mac'] = isset($leases[$key]['static_mac']) ? $leases[$key]['static_mac'] : "";
			$leases[$key]['static_ip'] = isset($leases[$key]['static_ip']) ? $leases[$key]['static_ip'] : "";
			$leases[$key]['is_static'] = isset($leases[$key]['is_static']) ? $leases[$key]['is_static'] : false;
			$leases[$key]['active_mac'] = isset($leases[$key]['active_mac']) ? $leases[$key]['active_mac'] : "";
			$leases[$key]['active_ip'] = isset($leases[$key]['active_ip']) ? $leases[$key]['active_ip'] : "";
			$leases[$key]['active_end'] = isset($leases[$key]['active_end']) ? $leases[$key]['active_end'] : "";
			$leases[$key]['is_active'] = isset($leases[$key]['is_active']) ? $leases[$key]['is_active'] : false;
		}

		return $leases;
	}

	/**
	 * Returns active leases.
	 *
	 * @return array array containing lease data
	 * @throws EngineException
	 */

	function GetActiveLeases()
	{
		global $_SOAP;

		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$leases = array();
		$leasefile = new File(self::FILE_LEASES);

		try {
			if (! $leasefile->Exists())
				return array();

			$leasedata = $leasefile->GetContentsAsArray();

			foreach ($leasedata as $line) {
				$parts = preg_split('/[\s]+/', $line);

				$key = $parts[1];

				$leases[$key]['end'] = isset($parts[0]) ? $parts[0] : "";
				$leases[$key]['ip'] = isset($parts[2]) ? $parts[2] : "";
				$leases[$key]['hostname'] = isset($parts[3]) ? $parts[3] : "";
			}

		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		if (isset($_SOAP['REQUEST'])) {
			$this->Log(COMMON_DEBUG, 'soap request/response', __METHOD__, __LINE__);
			$response = array();
			foreach($leases as $mac => $details) {
				$result = new DnsMasq_GetActiveLeasesResponse;

				$result->mac = $mac;
				$result->end = $details['end'];
				$result->ip= $details['ip'];
				$result->hostname = $details['hostname'];

				$response[] = $result;
			}

			return $response;
		}

		return $leases;
	}

	/**
	 * Returns static leases from /etc/ethers.
	 *
	 * @return array lease data keyed by MAC and sorted by IP
	 * @throws EngineException
	 */

	function GetStaticLeases()
	{
		global $_SOAP;

		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$leases = array();
		$ip_ndx = array();

		// Bail if read-ethers feature is disabled
		if (! isset($this->config['read-ethers']))
			return array();

		$network = new Network();
		$ethers = new Ethers();
		$mac_ip_pairs = $ethers->GetEthers();

		foreach($mac_ip_pairs as $mac => $host_or_ip) {

			// Find a hostname for IP address entries
			// Find an IP for hostname entries

			if ($network->IsValidIp($host_or_ip)) {
				$ip = $host_or_ip;
				$hostname = gethostbyaddr($host_or_ip);
				if ($hostname == $host_or_ip)	
					$hostname = "";
			} else {
				$hostname = $host_or_ip;
				$ip = gethostbyname($host_or_ip);
				if ($ip == $host_or_ip)	
					$ip = "";
			}

			// Keep an index to sort by IP

			$ip_long = sprintf("%u", ip2long($ip));
			$ip_ndx[$ip_long] = $mac;

			$leases[$mac]['ip'] = $ip;
			$leases[$mac]['hostname'] = $hostname;
		}

		ksort($ip_ndx);

		$sortedleases = array();

		foreach($ip_ndx as $ip => $mac) {
			$sortedleases[$mac]['ip'] = $leases[$mac]['ip'];
			$sortedleases[$mac]['hostname'] = $leases[$mac]['hostname'];
		}

		if (isset($_SOAP['REQUEST'])) {
			$response = array();
			foreach($sortedleases as $mac => $details) {
				$result = new DnsMasq_GetStaticLeasesResponse;

				$result->mac = $mac;
				$result->ip= $details['ip'];
				$result->hostname = $details['hostname'];

				$response[] = $result;
			}

			return $response;
		}

		return $sortedleases;
	}

	/**
	 * Returns list of declared subnets.
	 *
	 * @return array list of declared subnets 
	 * @throws EngineException
	 */

	function GetSubnets()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		return $this->subnets;
	}

	/**
	 * Sets state of authoritative flag.
	 *
	 * @param boolean $state authoritative state
	 * @return void
	 * @throws EngineException
	 */

	function SetAuthoritativeState($state)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! is_bool)
			throw new ValidationException(LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		// Cleans out invalid duplicates
		if ($this->config['dhcp-authoritative'])
			unset($this->config['dhcp-authoritative']);

		if ($state)
			$this->config['dhcp-authoritative']['line'][1] = "";

		$this->_SaveConfig();
	}

	/**
	 * Sets state of DHCP server.
	 *
	 * @param boolean $state DHCP server state
	 * @return void
	 * @throws EngineException
	 */

	function SetDhcpState($state)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! is_bool($state))
			throw new ValidationException(LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		// Cleans out invalid duplicates
		if (isset($this->config['conf-file']))
			unset($this->config['conf-file']);

		if ($state)
			$this->config['conf-file']['line'][1] = self::FILE_DHCP;

		$this->_SaveConfig();
	}

	/**
	 * Sets global domain name.
	 *
	 * @param string $domain domain name
	 * @return void
	 * @throws EngineException
	 */

	function SetDomainName($domain)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		// Cleans out invalid duplicates
		if ($this->config['domain'])
			unset($this->config['domain']);

		$this->config['domain']['line'][1] = $domain;

		$this->_SaveConfig();
	}

	/**
	 * Updates subnet.
	 *
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	function UpdateSubnet($interface, $gateway, $start, $end, $dns, $wins, $leasetime = DnsMasq::DEFAULT_LEASETIME, $tftp="", $ntp="")
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->IsValidSubnet($interface, $gateway, $start, $end, $dns, $wins, $leasetime, $tftp, $ntp))
			throw new ValidationException(LOCALE_LANG_INVALID);
			
		if (! $this->is_loaded)
			$this->_LoadConfig();

		if ($this->SubnetExists($interface))
			$this->DeleteSubnet($interface);

		$this->AddSubnet($interface, $gateway, $start, $end, $dns, $wins, $leasetime, $tftp, $ntp);
	}

	///////////////////////////////////////////////////////////////////////////////
	// V A L I D A T I O N
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Validates subnet information.
	 *
	 * @return boolean true if subnet is valid
	 */

	function IsValidSubnet($interface, $gateway, $start, $end, $dns, $wins, $leasetime = DnsMasq::DEFAULT_LEASETIME, $tftp="", $ntp="")
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$isvalid = true;
		$network = new Network();

		if (isset($this->subnets[$interface]['network'])) {
			$isvalid = false;
			$errmsg = DNSMASQ_LANG_ERRMSG_SUBNETEXISTS;
			$this->AddValidationError($errmsg, __METHOD__, __LINE__);
		}

		if (! $network->IsValidIp($gateway)) {
			$isvalid = false;
			$errmsg = DNSMASQ_LANG_ROUTER . " ($gateway) - " . LOCALE_LANG_INVALID;
			$this->AddValidationError($errmsg, __METHOD__, __LINE__);
		}

		if ($wins && (! $network->IsValidIp($wins))) {
			$isvalid = false;
			$errmsg = DNSMASQ_LANG_NETBIOS . " ($wins) - " . LOCALE_LANG_INVALID;
			$this->AddValidationError($errmsg, __METHOD__, __LINE__);
		}

		if (! $network->IsValidIp($start)) {
			$isvalid = false;
			$errmsg = DNSMASQ_LANG_LOW_IP . " - " . LOCALE_LANG_INVALID;
			$this->AddValidationError($errmsg, __METHOD__, __LINE__);
		}
		
		if (! $network->IsValidIp($end)) {
			$isvalid = false;
			$errmsg = DNSMASQ_LANG_HIGH_IP . " - " . LOCALE_LANG_INVALID;
			$this->AddValidationError($errmsg, __METHOD__, __LINE__);
		}

		if (! (preg_match("/^\d+$/", $leasetime) || ($leasetime == self::CONSTANT_UNLIMITED_LEASE))) {
			$isvalid = false;
			$errmsg = DNSMASQ_LANG_LEASE_TIME . " ($leasetime) - " . LOCALE_LANG_INVALID;
			$this->AddValidationError($errmsg, __METHOD__, __LINE__);
		}
		
		if (! is_array($dns)) {
			$isvalid = false;
			$errmsg = DNSMASQ_LANG_DNS . " - " . LOCALE_LANG_ERRMSG_INVALID_TYPE;
			$this->AddValidationError($errmsg, __METHOD__, __LINE__);
		}

		if ($tftp && (! $network->IsValidIp($tftp))) {
			$isvalid = false;
			$errmsg = DNSMASQ_LANG_TFTP . " ($tftp) - " . LOCALE_LANG_INVALID;
			$this->AddValidationError($errmsg, __METHOD__, __LINE__);
		}

		if ($ntp && (! $network->IsValidIp($ntp))) {
			$isvalid = false;
			$errmsg = DNSMASQ_LANG_NTP . " ($ntp) - " . LOCALE_LANG_INVALID;
			$this->AddValidationError($errmsg, __METHOD__, __LINE__);
		}
		
		if (count($dns) > 0) {
			foreach ($dns as $server) {
				if (empty($server))
					continue;

				if (! $network->IsValidIp($server)) {
					$isvalid = false;
					$errmsg = DNSMASQ_LANG_DNS . " ($server) - " . LOCALE_LANG_INVALID;
					$this->AddValidationError($errmsg, __METHOD__, __LINE__);
				}
			}
		}

		return $isvalid;
	}

	///////////////////////////////////////////////////////////////////////////////
	// P R I V A T E  M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * @access private
	 */

	function __destruct()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		parent::__destruct();
	}

	/**
	 * Loads configuration file.
	 *
	 * @access private
	 * @return void
	 * @throws EngineException
	 */

	function _LoadConfig()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$lines = array();

		try {
			$dhcpfile = new File(self::FILE_DHCP);
			$dnsmasqfile = new File(self::FILE_CONFIG);

			$lines = $dnsmasqfile->GetContentsAsArray();

			if ($dhcpfile->Exists())
				$lines = array_merge($lines, $dhcpfile->GetContentsAsArray());

		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		/* The Dnsmasq configuration file can have multiple keys with the same
		 * value.  For instance:
		 *
		 * - dhcp-option=eth1,44,192.168.2.16
		 * - dhcp-option=eth1,1,255.255.255.0
		 * - dhcp-option=eth1,28,192.168.2.255
		 *
		 * The config() array is in the following format to deal with this:
		 * 
		 * config[key][count]
		 * config[key][line][line_index]
		 *
		 * In our example, this would look like:
		 *
		 * config['dhcp-option']['count'] = 3
		 * config['dhcp-option']['line'][1] = eth1,44,192.168.2.16
		 * config['dhcp-option']['line'][2] = eth1,1,255.255.255.0
		 * config['dhcp-option']['line'][3] = eth1,28,192.168.2.255
		 */

		$matches = array();

		foreach ($lines as $line) {
			if (preg_match("/^#/", $line) || preg_match("/^\s*$/", $line)) {
				continue;
			} else if (preg_match("/(.*)=(.*)/", $line, $matches)) {
				$key = $matches[1];
				$value = $matches[2];
			} else {
				$key = trim($line);
				$value = "";
			}

			if (isset($this->config[$key]['count']))
				$this->config[$key]['count']++;
			else
				$this->config[$key]['count'] = 1;

			// $count is just to make code more readable
			$count = $this->config[$key]['count'];
			$this->config[$key]['line'][$count] = $value;
		}

		/** 
		 * The $subnet array contains the following data structure:
		 *
		 * $subnet[interface][netmask]
		 * $subnet[interface][start]
		 * $subnet[interface][end]
		 * $subnet[interface][leasetime]
		 * $subnet[interface][wins]
		 * $subnet[interface][broadcast]
		 * $subnet[interface][gateway]
		 * $subnet[interface][nameservers] (array)
		 * $subnet[interface][isconfigured]
		 * $subnet[interface][option][rfc_id]
		 */

		if (isset($this->config['dhcp-range'])) {
			foreach ($this->config["dhcp-range"]["line"] as $line) {
				$items = preg_split("/,/", $line);
				$this->subnets[$items[0]]["start"] = $items[1];
				$this->subnets[$items[0]]["end"] = $items[2];
				$this->subnets[$items[0]]["leasetime"] = preg_replace("/[hsm]\s*$/", "", $items[3]);
				$this->subnets[$items[0]]["isconfigured"] = true;
			}
		}

		if (isset($this->config["dhcp-option"])) {
			foreach ($this->config["dhcp-option"]["line"] as $line) {
				$items = preg_split("/,/", $line, 3);
				if ($items[1] == self::OPTION_SUBNET_MASK) {
					$key = "netmask";
					$value = $items[2];
				} else if ($items[1] == self::OPTION_GATEWAY) {
					$key = "gateway";
					$value = $items[2];
				} else if ($items[1] == self::OPTION_NAME_SERVERS) {
					$key = "nameservers";
					$value = explode(",", $items[2]);
				} else if ($items[1] == self::OPTION_BROADCAST) {
					$key = "broadcast";
					$value = $items[2];
				} else if ($items[1] == self::OPTION_WINS) {
					$key = "wins";
					$value = $items[2];
				} else if ($items[1] == self::OPTION_TFTP) {
					$key = "tftp";
					$value = preg_replace("/\"/", "", $items[2]);
				} else if ($items[1] == self::OPTION_NTP) {
					$key = "ntp";
					$value = $items[2];
				} else if (count($items) == 2) {
					// Skip over dhcp-option tags without subnet definition
					continue;
				} else {
					$key = $items[1];
					$value = $items[2];
				}

				$this->subnets[$items[0]][$key] = $value;
			}
		}

		/* Calculate network setting
		 * Check to see if configuration is valid for given interface
		 *
		 * $subnet[interface][network]
		 * $subnet[interface][isvalid]
		 */

		$netcheck = new Network();

		foreach ($this->subnets as $eth => $subnetinfo) {
			if (isset($this->subnets[$eth]['start']) && isset($this->subnets[$eth]['netmask'])) {
				$configured_network = $netcheck->GetNetworkAddress($this->subnets[$eth]['start'], $this->subnets[$eth]['netmask']);
				$this->subnets[$eth]['network'] = $configured_network;
			} else {
				$configured_network = "";
			}

			$this->subnets[$eth]["isvalid"] = false;

			try {
				$ethinfo = new Iface($eth);
				$ethip = $ethinfo->GetLiveIp();
				$ethnetmask = $ethinfo->GetLiveNetmask();
				$ethnetwork = $netcheck->GetNetworkAddress($ethip, $ethnetmask);

				if ($ethnetwork == $configured_network)
					$this->subnets[$eth]["isvalid"] = true;
			} catch (Exception $e) {
				// Not fatal
			}
		}

		$this->is_loaded = true;
	}

	/**
	 * Save configuration changes.
	 *
	 * @access private
	 * @return void
	 * @throws EngineException
	 */

	function _SaveConfig()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$dhcp_file = new File(self::FILE_DHCP);
		$dnsmasq_file = new File(self::FILE_CONFIG);

		// Go through the existing configuration files and save
		// any user-created comments.

		$dhcp_comments = array();
		$dnsmasq_comments = array();

		try {
			if ($dhcp_file->Exists()) {
				$lines = $dhcp_file->GetContentsAsArray();
				foreach ($lines as $line) {
					if (preg_match("/^#/", $line))
						$dhcp_comments[] = $line;
				}
			}

			if ($dnsmasq_file->Exists()) {
				$lines = $dnsmasq_file->GetContentsAsArray();
				foreach ($lines as $line) {
					if (preg_match("/^#/", $line))
						$dnsmasq_comments[] = $line;
				}
			}
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		// The DHCP options in Dnsmasq go into a separate dhcp.conf file.  The
		// relevant parameters are as follows:
		//
		// - read-ethers
		// - dhcp-range
		// - dhcp-option
		//
		// All other options go into dnsmasq.conf
		//
		// Some keys in the configuration files do not have values, while others do:
		// - log-queries  (no value)
		// - domain=pointclark.net (value)

		$dhcp_lines = array();
		$dnsmasq_lines = array();

		// Always enable read-ethers for now
		$this->config['read-ethers']['line'][1] = "";

		foreach ($this->config as $key => $details) {
			foreach ($details['line'] as $lineno => $value) {
				if ($value)
					$line = "$key=$value";
				else
					$line = "$key";

				if (in_array($key, array('read-ethers', 'dhcp-range'))) {
					$dhcp_lines[] = $line;
				} else if ($key == "dhcp-option") {
					// The dhcp-option does not require a subnet definition.
					// These items should go in the main dnsmasq_lines array.

					$items = preg_split("/,/", $value, 3);

					if (count($items) == 2)
						$dnsmasq_lines[] = $line;
					else
						$dhcp_lines[] = $line;
				} else {
					$dnsmasq_lines[] = $line;
				}
			}
		}

		// Append any user-created comments to the file.

		$dnsmasq_lines = array_merge($dnsmasq_lines, $dnsmasq_comments);
		$dhcp_lines = array_merge($dhcp_lines, $dhcp_comments);

		// Write out the files

		try {
			if (! $dhcp_file->Exists())
				$dhcp_file->Create("root", "root", "0644");

			if (! $dnsmasq_file->Exists())
				$dnsmasq_file->Create("root", "root", "0644");

			sort($dnsmasq_lines);
			sort($dhcp_lines);

			$dhcp_file->DumpContentsFromArray($dhcp_lines);
			$dnsmasq_file->DumpContentsFromArray($dnsmasq_lines);

		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		// Reset our internal data structures
		$this->is_loaded = false;
		$this->config = array();
		$this->subnets = array();
	}
}

// vim: syntax=php ts=4
?>
