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
 * Network interface manager class.
 *
 * @package Api
 * @subpackage Network
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('Engine.class.php');
require_once('Firewall.class.php');
require_once('Folder.class.php');
require_once('Iface.class.php');
require_once('Network.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * IfaceManager vendor details wrapper.
 *
 * @package Soap
 * @subpackage Soap
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2007, Point Clark Networks
 */

class IfaceManager_GetVendorDetailsResponse
{
	public $vendor;
	public $device;
	public $sub_device;
	public $bus;
}

$_SOAP["CLASS_MAP"]["GetVendorDetailsResponse"] = "IfaceManager_GetVendorDetailsResponse";

/**
 * Network interface manager class.
 *
 * @package Api
 * @subpackage Network
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class IfaceManager extends Engine
{
	///////////////////////////////////////////////////////////////////////////////
	// F I E L D S
	///////////////////////////////////////////////////////////////////////////////

	const PATH_NET_CONFIG = '/etc/sysconfig/network-scripts';
	const PCI_ID = '/usr/share/hwdata/pci.ids';
	const USB_ID = '/usr/share/hwdata/usb.ids';
	const SYS_CLASS_NET = '/sys/class/net';

	protected $is_loaded = false;
	protected $ethinfo = array();

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * IfaceManager constructor.
	 */

	public function __construct()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		parent::__construct();
	}

	/**
	 * Returns array of interfaces (real and dynamic).
	 *
 	 * @param bool $ignore_ppp ignore PPP interfaces
 	 * @param bool $ignore_lo ignore loopback interfaces
 	 * @return array list of network devices (using ifconfig.so)
	 * @throws EngineException
	 */

	function GetInterfaces($ignore_ppp, $ignore_lo)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! extension_loaded('ifconfig')) {
			if (!@dl('ifconfig.so'))
				throw new EngineException(LOCALE_LANG_ERRMSG_WEIRD, COMMON_WARNING);
		}

		$handle = @ifconfig_init();
		$list = @ifconfig_list($handle);
		$list = array_unique($list);
		sort($list);

		$rawlist = array();

		// Running interfaces
		//-------------------

		foreach ($list as $device) {
			$flags = @ifconfig_flags($handle, $device);
			$rawlist[] = $device;
		}

		// Configured interfaces
		//----------------------

		try {
			$matches = array();
			$folder = new Folder(self::PATH_NET_CONFIG);
			$listing = $folder->GetListing();

			foreach ($listing as $netconfig) {
				if (preg_match("/^ifcfg-(.*)/", $netconfig, $matches))
					$rawlist[] = $matches[1];
			}
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		// Purge unwanted interfaces
		//--------------------------

		$rawlist = array_unique($rawlist);
		$interfaces = array();

		foreach ($rawlist as $iface) {
			// Ignore IPv6-related sit0 interface for now
			if (preg_match("/^sit/", $iface))
				continue;

			if ($ignore_ppp && preg_match("/^pp/", $iface))
				continue;

			if ($ignore_lo && $iface == "lo")
				continue;

			$interfaces[] = $iface;
		}

		return $interfaces;
	}

	/**
	 * Returns interface count (real interfaces only).
	 *
	 * @return int number of real network devices (using ifconfig.so)
	 * @throws EngineException
	 */

	function GetInterfaceCount()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (!extension_loaded('ifconfig')) {
			if (!@dl('ifconfig.so'))
				throw new EngineException(LOCALE_LANG_ERRMSG_WEIRD, COMMON_WARNING);
		}

		$count = 0;
		$handle = @ifconfig_init();
		$list = @ifconfig_list($handle);

		foreach ($list as $device) {
			$flags = @ifconfig_flags($handle, $device);

			if (($flags & IFF_NOARP)) continue;
			if (($flags & IFF_LOOPBACK)) continue;
			if (($flags & IFF_POINTOPOINT)) continue;

			// No virtual interfaces either...
			if (preg_match("/:\d+$/", $device)) continue;

			$count++;
		}

		return $count;
	}

	/**
	 * Returns detailed information on all network interfaces.
	 *
	 * @returns array information on all network interfaces.
	 * @throws EngineException
	 */

	function GetInterfaceDetails()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if ($this->is_loaded)
			return $this->ethinfo;

		$slaveif = array();
		$ethlist = $this->GetInterfaces(false, true);

		foreach ($ethlist as $eth) {

			$interface = new Iface($eth);
			$ifdetails = $interface->GetInterfaceInfo();

			foreach ($ifdetails as $key => $value)
				$ethinfo[$eth][$key] = $value;

			// Flag network interfaces used by PPPoE
			//--------------------------------------

			if (isset($ethinfo[$eth]['ifcfg']['eth'])) {
				$pppoeif = $ethinfo[$eth]['ifcfg']['eth'];
				$ethinfo[$pppoeif]['master'] = $eth;
				$slaveif[$eth] = $pppoeif;
			}

			// Interface role
			//---------------

			try {
				$firewall = new Firewall();
				$role = $firewall->GetInterfaceRole($eth);
				$rolename = $firewall->GetInterfaceRoleText($eth);

				$ethinfo[$eth]['role'] = $role;
				$ethinfo[$eth]['roletext'] = $rolename;
			} catch (Exception $e) {
				// keep going
			}
		}

		foreach ($slaveif as $master => $slave) {
			$ethinfo[$slave]['role'] = $ethinfo[$master]['role'];
			$ethinfo[$slave]['roletext'] = $ethinfo[$master]['roletext'];
		}

		$this->ethinfo = $ethinfo;
		$this->is_loaded = true;

		return $ethinfo;
	}

	/**
	 * Returns list of available LAN networks.
	 *
	 * @return array list of available LAN networks.
	 * @throws EngineException
	 */

	function GetLanNetworks()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$network = new Network();
			$firewall = new Firewall();
			$mode = $firewall->GetMode();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		$ethlist = $this->GetInterfaceDetails();

		$lans = array();

		foreach ($ethlist as $eth => $details) {
			// Only interested in configured interfaces
			if (! $details['configured'])
				continue;

			// Gateway mode
			if (($details['role'] == Firewall::CONSTANT_LAN) && (! empty($details['address'])) && (! empty($details['netmask']))) {
				$basenetwork = $network->GetNetworkAddress($details['address'], $details['netmask']);
				$lans[] = $basenetwork . "/" . $details['netmask'];
			}

			// Standalone mode
			if (($details['role'] == Firewall::CONSTANT_EXTERNAL) && (! empty($details['address'])) && (! empty($details['netmask'])) &&
				($mode == Firewall::CONSTANT_TRUSTEDSTANDALONE) || ($mode == Firewall::CONSTANT_STANDALONE)) {
				$basenetwork = $network->GetNetworkAddress($details['address'], $details['netmask']);
				$lans[] = $basenetwork . "/" . $details['netmask'];
			}
		}

		return $lans;
	}

	/**
	 * Returns list of Wifi interfaces.
	 *
	 * @return array list of Wifi interfaces
	 * @throws EngineException
	 */

	function GetWifiInterfaces()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$ethlist = $this->GetInterfaceDetails();
		$wifilist = array();

		foreach ($ethlist as $eth => $details) {
			if ($details['type'] ==	Iface::TYPE_WIRELESS)
				$wifilist[] = $eth;
		}

		return $wifilist;
	}
	
	/**
	 * Returns the external IP address
	 *
	 * @throws EngineException
	 */

	function GetExternalIpAddress()
	{
		$interface = $this->GetExternalInterface();

		if ($interface != null)
			return $interface["address"];
	}

	/**
	 * Returns the external interface
	 *
	 * @throws EngineException
	 */

	function GetExternalInterface()
	{
		$ethlist = $this->GetInterfaceDetails();

		foreach ($ethlist as $eth => $details) {
			if ($details['role'] ==	"EXTIF")
				return $details;
		}
	}

	/**
	 * Returns a list of interfaces configured with the given role.
	 *
	 * @param boolean $exclude_Virtual exclude virtual interfaces
	 * @throws EngineException
	 */

	function GetExternalInterfaces($exclude_virtual = true)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$ifaces = array();
		$ethlist = $this->GetInterfaceDetails();

		foreach ($ethlist as $eth => $info) {
			// Skip non-external interfaces
			if ($info['role'] != Firewall::CONSTANT_EXTERNAL)
				continue;

			// Skip interfaces used 'indirectly' (e.g. PPPoE, bonded interfaces)
			if (isset($info['master']))
				continue;

			// Skip 1-to-1 NAT interfaces
			if (isset($info['one-to-one-nat']) && $info['one-to-one-nat'])
				continue;

			// Skip non-configurable interfaces
			if (! $info['configurable'])
				continue;

			// Skip virtual interfaces
			if ($exclude_virtual && isset($info['virtual']) && $info['virtual'])
				continue;

			$ifaces[] = $eth;	
		}

		return $ifaces;
	}

	/**
	 * Returns an interface's vendor, model, and bus details
	 * XXX: This method uses fopen/fread/fgets directly rather than the file class
	 * for performance reasons.  We don't need super-user access to gather interface
	 * details.
	 *
 	 * @param bool $iface interface to return details for
	 * @returns array $details details in an array
	 * @throws EngineException
	 */

	function GetVendorDetails($iface)
	{
		global $_SOAP;

		$details = array();
		$details['vendor'] = null;
		$details['device'] = null;
		$details['sub_device'] = null;
		$details['bus'] = null;

		$id_vendor = 0;
		$id_device = 0;
		$id_sub_vendor = 0;
		$id_sub_device = 0;

		$device_link = self::SYS_CLASS_NET . "/$iface/device";

		// TODO: translation
		if (!file_exists($device_link))
			throw new EngineException('Device link not found.', COMMON_WARNING);

		// Determine if this is a USB device
		$is_usb = false;

		if (!($path = readlink($device_link))) {
			throw new EngineException('Interface device inode isn\'t a symlink.',
				COMMON_WARNING);
		}

		if (strstr($path, 'usb')) $is_usb = true;

		// Obtain vendor ID number
		$path = $device_link . (($is_usb) ? '/../idVendor' : '/vendor');

		if (!($fh = fopen($path, 'r'))) {
			throw new EngineException('Error opening vendor information file.',
				COMMON_WARNING);
		}

		fscanf($fh, '%x', $id_vendor);
		fclose($fh);

		if ($id_vendor == 0) {
			throw new EngineException('Error reading vendor information.',
				COMMON_WARNING);
		}

		// Obtain device ID number
		$path = $device_link . (($is_usb) ? '/../idProduct' : '/device');

		if (!($fh = fopen($path, "r"))) {
			throw new EngineException('Error opening device information file.',
				COMMON_WARNING);
		}

		fscanf($fh, '%x', $id_device);
		fclose($fh);

		if ($id_device == 0) {
			throw new EngineException('Error reading device information.',
				COMMON_WARNING);
		}

		if (!$is_usb) {
			// Obtain (optional) sub-vendor ID number (PCI devices only)
			if (($fh = fopen("$device_link/subsystem_vendor", 'r'))) {
				fscanf($fh, '%x', $id_sub_vendor);
				fclose($fh);

				if ($id_sub_vendor == 0) {
					throw new EngineException('Error reading sub-vendor information.',
						COMMON_WARNING);
				}
			}

			// Obtain (optional) sub-device ID number (PCI devices only)
			if( ($fh = fopen("$device_link/subsystem_device", 'r'))) {
				fscanf($fh, '%x', $id_sub_device);
				fclose($fh);

				if($id_sub_device == 0) {
					throw new EngineException('Error reading sub-device information.',
						COMMON_NOTICE);
				}
			}
		}

		// Scan PCI/USB Id database for vendor/device[/sub-vendor/sub-device]
		if (!($fh = fopen((!$is_usb ? self::PCI_ID : self::USB_ID), 'r'))) {
			throw new EngineException('Error opening PCI/USB Id database.',
				COMMON_WARNING);
		}

		$details['bus'] = ($is_usb) ? 'USB' : 'PCI';

		// Find vendor id first
		$search = sprintf('%04x', $id_vendor);

		while (!feof($fh)) {
			$buffer = chop(fgets($fh, 4096));
			if (substr($buffer, 0, 4) != $search) continue;
			$details['vendor'] = substr($buffer, 6);
			break;
		}

		if ($details['vendor'] == null) {
			fclose($fh);
			throw new EngineException('No vendor string found in PCI/USB Id database.',
				COMMON_WARNING);
		}

		// Find device id next
		$search = sprintf('%04x', $id_device);

		while (!feof($fh)) {
			$byte = fread($fh, 1);
			if ($byte == '#') {
				fgets($fh, 4096);
				continue;
			}
			else if($byte != "\t") break;

			$buffer = chop(fgets($fh, 4096));
			if (substr($buffer, 0, 4) != $search) continue;
			$details['device'] = substr($buffer, 6);
			break;
		}

		if ($details['device'] == null) {
			if (!$is_usb) {
				fclose($fh);
				throw new EngineException('No device string found in PCI/USB Id database.',
					COMMON_WARNING);
			}

			// For USB devices, this isn't an error
			// XXX: Probably isn't for PCI devices either?
			return $details;
		}

		if ($id_sub_vendor == 0) {
			fclose($fh);
			return $details;
		}

		// Find (optional) sub-vendor id next
		$search = sprintf('%04x %04x', $id_sub_vendor, $id_sub_device);

		while (!feof($fh)) {
			$byte = fread($fh, 1);
			if ($byte == '#') {
				fgets($fh, 4096);
				continue;
			}
			else if($byte != "\t") break;

			if(fread($fh, 1) != "\t") break;

			$buffer = chop(fgets($fh, 4096));
			if (substr($buffer, 0, 9) != $search) continue;
			$details['sub_device'] = substr($buffer, 11);
			break;
		}

		fclose($fh);

		if (isset($_SOAP['REQUEST'])) {
			$result = new IfaceManager_GetVendorDetailsResponse;

			$result->vendor = $details['vendor'];
			$result->sub_device = $details['sub_device'];
			$result->device = $details['device'];
			$result->bus = $details['bus'];

			return $result;
		}

		return $details;
	}

	///////////////////////////////////////////////////////////////////////////////
	// P R I V A T E   M E T H O D S
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
}

// vim: syntax=php ts=4
?>
