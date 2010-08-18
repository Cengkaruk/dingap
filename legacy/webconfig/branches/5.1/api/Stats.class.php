<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2006 Point Clark Networks.
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
 * Stats server.
 *
 * @package Soap
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once("File.class.php");
require_once("ShellExec.class.php");
require_once("Iface.class.php");
require_once("Firewall.class.php");

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Stats load average wrapper.
 *
 * @package Soap
 * @subpackage Soap
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

class Stats_GetLoadAveragesResponse
{
	public $one;
	public $five;
	public $fifteen;
}

/**
 * Stats uptime wrapper.
 *
 * @package Soap
 * @subpackage Soap
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

class Stats_GetUptimeResponse
{
	public $uptime;
	public $idle;
}

/**
 * Stats network interface wrapper.
 *
 * @package Soap
 * @subpackage Soap
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

class Stats_GetInterfaceInfoResponse
{
	public $type;
	public $type_name;
	public $role;
	public $role_name;
	public $boot_proto;
	public $boot_proto_name;
	public $address;
	public $link;
	public $speed;
}

/**
 * Stats network interface details wrapper.
 *
 * @package Soap
 * @subpackage Soap
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

class Stats_InterfaceStats
{
	public $bytes;
	public $packets;
	public $errors;
	public $dropped;
	public $fifo;
	public $frame;
	public $compressed;
	public $multicast;
}

/**
 * Stats network interface configuration wrapper.
 *
 * @package Soap
 * @subpackage Soap
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

class Stats_InterfaceConfig
{
	public $address;
	public $netmask;
	public $broadcast;
	public $hwaddress;
	public $mtu;
	public $metric;
	public $flags;
	public $link;
	public $speed;
	public $type;
	public $role;
}

/**
 * Stats network interface wrapper.
 *
 * @package Soap
 * @subpackage Soap
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

class Stats_GetInterfaceStatsResponse
{
	public $device;
	public $config;
	public $receive;
	public $transmit;
	public $timestamp;
}

/**
 * Stats hard disk wrapper.
 *
 * @package Soap
 * @subpackage Soap
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

class Stats_GetDiskStatsResponse
{
	public $device;
	public $mount;
	public $filesystem;
	public $blocks;
	public $used;
	public $timestamp;
}

/**
 * Stats processes wrapper.
 *
 * @package Soap
 * @subpackage Soap
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

class Stats_GetProcessCountResponse
{
	public $running;
	public $total;
	public $timestamp;
}

/**
 * Stats disk wrapper.
 *
 * @package Soap
 * @subpackage Soap
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

class Stats_GetMemStatsResponse
{
	public $mem_total;
	public $mem_free;
	public $swap_total;
	public $swap_free;
	public $timestamp;
}

/**
 * Stats utility class.
 *
 * The Stats class can be used to return various system stats like load
 * averages, memory usage, disk usage, etc.
 *
 * @package Soap
 * @author Point Clark Networks
 * @license GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

class Stats extends Engine
{
	///////////////////////////////////////////////////////////////////////////////
	// C O N S T A N T S
	///////////////////////////////////////////////////////////////////////////////

	const BIN_DF = "/bin/df";
	const FILE_UPTIME = "/proc/uptime";
	const FILE_RELEASE = "/etc/release";
	const FILE_PROC_MOUNTS = "/proc/mounts";
	const FILE_PROC_LOADAVG = "/proc/loadavg";
	const FILE_PROC_NET_DEV = "/proc/net/dev";
	const FILE_PROC_MEMINFO = "/proc/meminfo";

	///////////////////////////////////////////////////////////////////////////////
	// F I E L D S
	///////////////////////////////////////////////////////////////////////////////

	protected $dev_exclude = array("none");	
	protected $fs_exclude = array("rootfs");	
	protected $ifn_exclude = array("lo");	

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Stats constructor.
	 */

	public function Stats() 
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct();
	}

	/**
	 * Returns uptime.
	 *
	 * @return  array uptime
	 */

	public function GetUptime()
	{
		try {
			$file = new File(self::FILE_UPTIME);
			$contents = $file->GetContents();
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}

		list($uptime, $idle) = explode(" ", chop($contents));

		$result = array();
		$result["uptime"] = sprintf("%d", $uptime);
		$result["idle"] = sprintf("%d", $idle);

		return $result;
	}

	/**
	 * Returns load averages and processes running/total.
	 *
	 * @return  array loadavg
	 */

	public function GetLoadAverages()
	{
		try {
			$file = new File(self::FILE_PROC_LOADAVG);
			$contents = $file->GetContents();
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}

		$fields = explode(" ", chop($contents));

		$result = array();
		$result["one"] = $fields[0];
		$result["five"] = $fields[1];
		$result["fifteen"] = $fields[2];

		return $result;
	}

	/**
	 * Returns operating system release name.
	 *
	 * @return  string release
	 */

	public function GetRelease()
	{
		// FIXME: quick workaround for 5.1.  Fix in 5.2.
		if (file_exists(COMMON_CORE_DIR . "/api/Product.class.php")) {
			require_once(COMMON_CORE_DIR . "/api/Product.class.php");
			$product = new Product();
			$name = $product->GetName();
			if (! empty($name))
				return $name;
		}

		try {
			$file = new File(self::FILE_RELEASE);
			$contents = $file->GetContents();
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}

		$result = "Unknown";
		$match = array();
		
		if(ereg("(.*) release (.*)", chop($contents), $match)) {
			$result = sprintf("%s %s", $match[1], $match[2]);
		}

		return chop($result);
	}

	/**
	 * Returns an array of configurable interfaces
	 *
	 * @return array string Configurable interfaces
	 */

	public function GetInterfaces()
	{
		$iface_manager = new IfaceManager();
		$all_devices = $iface_manager->GetInterfaces(true, true);
		$cfg_devices = array();

		foreach($all_devices as $device) {
			$iface = new Iface($device);
			if(!$iface->IsConfigurable()) continue;
			$cfg_devices[] = $device;
		}

		return $cfg_devices;
	}

	/**
	 * Returns interface information
	 *
	 * @return object Stats_GetInterfaceInfoResponse
	 */

	public function GetInterfaceInfo($device)
	{
		$iface = new Iface($device);
		$config = $iface->ReadConfig();
		$fw = new Firewall();

		$info["type"] = $iface->GetType();
		$info["type_name"] = $iface->GetTypeName();
		$info["role"] = $fw->GetInterfaceRole($device);
		$info["role_name"] = $fw->GetInterfaceRoleText($device);
		$info["boot_proto"] = $iface->GetBootProtocol();
		$info["boot_proto_name"] = $iface->GetBootProtocolText();
		$info["address"] = $iface->GetLiveIp();
		$info["link"] = $iface->GetLinkStatus();
		$info["speed"] = $iface->GetSpeed();

		return $info;
	}

	/**
	 * Returns disk statistics
	 *
	 * @return object Stats_GetDiskStatsResponse
	 */

	public function GetDiskStats()
	{
		if (!($fh = @fopen(self::FILE_PROC_MOUNTS, "r"))) {
			throw new EngineException("Unable to open: " . self::FILE_PROC_MOUNTS, COMMON_ERROR);
		}

		$mounts = array();

		while (!@feof($fh)) $mounts[] = explode(" ", chop(@fgets($fh)));
		@fclose($fh);

		if (!($ph = @popen(self::BIN_DF, "r"))) {
			throw new EngineException("Unable to execute: " . self::BIN_DF, COMMON_ERROR);
		}

		$result = array();

		$expr = "^([A-z0-9/\._@:-]+)[[:space:]]+([0-9]+)[[:space:]]+([0-9]+)";
		$expr .= "[[:space:]]+[0-9]+[[:space:]]+[0-9%]+[[:space:]]+(.+)";

		while (!@feof($ph)) {
			$parts = array();
			if (!ereg($expr, chop(@fgets($ph)), $parts)) continue;
			if (array_search($parts[1], $this->dev_exclude) !== false) continue;

			$fs = "unknown";
	
			foreach ($mounts as $value) {
				if ($value[1] != $parts[4]) continue;
				if (array_search($value[2], $this->fs_exclude) !== false) continue;

				$fs = $value[2];
				break;
			}

			$stats = new Stats_GetDiskStatsResponse;
			$stats->device = $parts[1];
			$stats->mount = $parts[4];
			$stats->filesystem = $fs;
			$stats->blocks = (float)$parts[2];
			$stats->used = (float)$parts[3];
			$stats->timestamp = (float)time();

			$result[] = $stats;
		}

		@pclose($ph);
		return $result;
	}

	/**
	 * Returns statistics for all network interfaces
	 *
	 * @return object Stats_GetInterfaceStatsResponse
	 */

	public function GetInterfaceStats()
	{
		$expr = "^[[:space:]]*([[:alnum:]]+):[[:space:]]*([0-9]+)[[:space:]]+([0-9]+)";
		$expr .= "[[:space:]]+([0-9]+)[[:space:]]+([0-9]+)[[:space:]]+([0-9]+)";
		$expr .= "[[:space:]]+([0-9]+)[[:space:]]+([0-9]+)[[:space:]]+([0-9]+)";
		$expr .= "[[:space:]]+([0-9]+)[[:space:]]+([0-9]+)[[:space:]]+([0-9]+)";
		$expr .= "[[:space:]]+([0-9]+)[[:space:]]+([0-9]+)[[:space:]]+([0-9]+)";
		$expr .= "[[:space:]]+([0-9]+)[[:space:]]+([0-9]+)";

		if (!($fh = @fopen(self::FILE_PROC_NET_DEV, "r"))) {
			throw new EngineException("Unable to open: " . self::FILE_PROC_NET_DEV, COMMON_ERROR);
		}

		$result = array();
		$firewall = new Firewall;

		while (!@feof($fh)) {
			$parts = array();
			if (!ereg($expr, chop(@fgets($fh)), $parts)) continue;
			if (array_search($parts[1], $this->ifn_exclude) !== false) continue;

			$iface = new Iface($parts[1]);

			try {
				$info = $iface->GetInterfaceInfo();
			} catch (Exception $e) {
				continue;
			}

			$config = new Stats_InterfaceConfig;
			$config->address = $info["address"];
			$config->netmask = $info["netmask"];
			$config->broadcast = $info["broadcast"];
			$config->hwaddress = $info["hwaddress"];
			$config->mtu = $info["mtu"];
			$config->metric = $info["metric"];
			$config->flags = $info["flags"];
			$config->link = $info["link"];
			$config->speed = $info["speed"];
			$config->type = $info["type"];

			$config->role = $firewall->GetInterfaceRole($parts[1]);

			$recv = new Stats_InterfaceStats;
			$recv->bytes = (float)$parts[2];
			$recv->packets = (float)$parts[3];
			$recv->errors = (float)$parts[4];
			$recv->dropped = (float)$parts[5];
			$recv->fifo = (float)$parts[6];
			$recv->frame = (float)$parts[7];
			$recv->compressed = (float)$parts[8];
			$recv->multicast = (float)$parts[9];

			$xmit = new Stats_InterfaceStats;
			$xmit->bytes = (float)$parts[10];
			$xmit->packets = (float)$parts[11];
			$xmit->errors = (float)$parts[12];
			$xmit->dropped = (float)$parts[13];
			$xmit->fifo = (float)$parts[14];
			$xmit->frame = (float)$parts[15];
			$xmit->compressed = (float)$parts[16];
			$xmit->multicast = (float)$parts[17];

			$response = new Stats_GetInterfaceStatsResponse;

			$response->device = $parts[1];
			$response->config = $config;
			$response->receive = $recv;
			$response->transmit = $xmit;
			$response->timestamp = (float)time();

			$result[] = $response;
		}

		@fclose($fh);
		return $result;
	}

	public function GetMemStats()
	{
		$expr = "^([A-z_]+):[[:space:]]+([0-9]+).*$";

		if( !($fh = @fopen(self::FILE_PROC_MEMINFO, "r"))) {
			throw new EngineException("Unable to open: " . self::FILE_PROC_MEMINFO, COMMON_ERROR);
		}

		$info = new Stats_GetMemStatsResponse;

		while (!@feof($fh)) {
			$parts = array();
			if (!ereg($expr, chop(@fgets($fh)), $parts)) continue;

			if ($parts[1] == "MemTotal")
				$info->mem_total = (float)$parts[2];
			else if ($parts[1] == "MemFree")
				$info->mem_free = (float)$parts[2];
			else if ($parts[1] == "SwapTotal")
				$info->swap_total = (float)$parts[2];
			else if ($parts[1] == "SwapFree")
				$info->swap_free = (float)$parts[2];
		}

		@fclose($fh);

		$info->timestamp = (float)time();

		return $info;
	}

	public function GetProcessCount()
	{
		if (!($fh = @fopen(self::FILE_PROC_LOADAVG, "r"))) {
			throw new EngineException("Unable to open: " . self::FILE_PROC_LOADAVG, COMMON_ERROR);
		}

		$info = new Stats_GetProcessCountResponse;

		$fields = explode(" ", chop(@fgets($fh)));
		@fclose($fh);

		list($info->running, $info->total) = explode("/", $fields[3]);
		$info->timestamp = (float)time();

		return $info;
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
