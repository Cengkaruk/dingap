<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2007 Point Clark Networks.
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
 * Provides interface for discovering mass storage devices.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('Engine.class.php');
require_once('File.class.php');
require_once('Folder.class.php');

///////////////////////////////////////////////////////////////////////////////
// E X C E P T I O N  C L A S S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Storage Device Utility.
 *
 * Class to assist in the discovery of mass storage devices on the server.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class StorageDevice extends Engine
{
	///////////////////////////////////////////////////////////////////////////////
	// V A R I A B L E S
	///////////////////////////////////////////////////////////////////////////////

	const PROC_IDE = "/proc/ide";
	const PROC_MDSTAT = "/proc/mdstat";
	const ETC_MTAB = "/etc/mtab";
	const BIN_SWAPON = "/sbin/swapon -s %s";
	const USB_DEVICES = "/sys/bus/usb/devices";
	const IDE_DEVICES = "/sys/bus/ide/devices";
	const SCSI_DEVICES = "/sys/bus/scsi/devices";

	protected $devices = Array();
	protected $is_scanned = false;
	protected $mount_point = null;

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * StorageDevice constructor.
	 *
	 * @return void
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		parent::__construct();
	}

	/** Retrieve a list of all storage devices.
	 *
	 * @return array
     * @throws EngineException
	 */

	final public function GetDevices($mounted = true, $swap = false)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_scanned) $this->Scan($mounted, $swap);

		return $this->devices;
	}

	/** Retrieve mount point location set by last IsMounted() call.
	 *
	 * @return string
     * @throws EngineException
	 */

	final public function GetMountPoint()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		return $this->mount_point;
	}

	/** Is the device mounted?
	 *
	 * @return boolean
     * @throws EngineException
	 */

	final public function IsMounted($device)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (!($fh = fopen(self::ETC_MTAB, 'r')))
			return false;

		while (!feof($fh)) {
			$buffer = chop(fgets($fh, 4096));
			if (!strlen($buffer)) break;
			list($name, $this->mount_point) = explode(' ', $buffer);
			if ($name == $device) { fclose($fh); return true; }
		}

		$this->mount_point = null;

		fclose($fh);
		return false;
	}

	/** Is this a swap device?
	 *
	 * @return boolean
	 * @throws EngineException
	 */

	final public function IsSwap($device)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (!($ph = popen(sprintf(self::BIN_SWAPON, $device), 'r')))
			return false;

		while (!feof($ph)) {
			list($name) = explode(' ', fgets($ph, 4096));
			if ($name == $device) { pclose($ph); return true; }
		}

		pclose($ph);
		return false;
	}

	/** Get software RAID devices
	 *
	 * @return array
	 * @throws EngineException
	 */
	final public function GetSoftwareRaidDevices()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (!($fh = fopen(self::PROC_MDSTAT, 'r')))
			return false;

		$devices = array();
		while (!feof($fh)) {
			if (!preg_match(
				'/^(md[0-9]+)\s+:\s+(\w+)\s+(\w+)\s+(.*$)/',
				chop(fgets($fh, 8192)),
				$matches)) continue;
			$device = array();
			$device['status'] = $matches[2];
			$device['type'] = strtoupper($matches[3]);
			$nodes = explode(' ', $matches[4]);
			foreach ($nodes as $node) {
				$device['node'][] = '/dev/' . preg_replace(
					'/\[[0-9]+\]/', '', $node);
			}
			$devices['/dev/' . $matches[1]] = $device;
		}

		fclose($fh);
		return $devices;
	}

	/** Is this a software RAID device?
	 *
	 * @return boolean
	 * @throws EngineException
	 */

	final public function IsSoftwareRaidDevice($device)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$raid = $this->GetSoftwareRaidDevices();
		if (array_key_exists($device, $raid)) return true;
		return false;
	}

	/** Is this a software RAID node?
	 *
	 * @return boolean
	 * @throws EngineException
	 */

	final public function IsSoftwareRaidNode($device)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$raid = $this->GetSoftwareRaidDevices();
		foreach ($raid as $dev) {
			if (in_array($device, $dev['node']))
				return true;
		}
		return false;
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

	/**
	 * @access private
	 */

	final private function Scan($mounted, $swap)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$atapi = $this->ScanAtapi();

		foreach ($atapi as $parent => $device) {
			if (!isset($device['partition'])) continue;
			foreach ($device['partition'] as $partition) {
				$atapi[$partition]['vendor'] = $device['vendor'];
				$atapi[$partition]['model'] = $device['model'];
				$atapi[$partition]['type'] = $device['type'];
				$atapi[$partition]['parent'] = $parent;
			}
			unset($atapi[$parent]);
		}

		$devices = $this->ScanScsi();
		$scsi = array();
		foreach ($devices as $device) {
			if (!isset($device['partition'])) {
				$scsi[$device['device']]['vendor'] = $device['vendor'];
				$scsi[$device['device']]['model'] = $device['model'];
				if ($device['bus'] == 'usb')
					$scsi[$device['device']]['type'] = 'USB';
				else
					$scsi[$device['device']]['type'] = 'SCSI/SATA';
				continue;
			}

			foreach ($device['partition'] as $partition) {
				$scsi[$partition]['vendor'] = $device['vendor'];
				$scsi[$partition]['model'] = $device['model'];
				$scsi[$device['device']]['parent'] = $device['device'];
				if ($device['bus'] == 'usb')
					$scsi[$partition]['type'] = 'USB';
				else
					$scsi[$partition]['type'] = 'SCSI/SATA';
			}
			unset($scsi[$device['device']]);
		}

		$this->devices = array_merge($atapi, $scsi);

		$raid_devices = $this->GetSoftwareRaidDevices();
		$purge = array();
		foreach ($this->devices as $device => $details) {
			foreach ($raid_devices as $raid) {
				if (!in_array($device, $raid['node']))
					continue;
				$purge[] = $device;
			}
		}
		foreach ($purge as $device) unset($this->devices[$device]);
		$purge = array();

		foreach ($raid_devices as $device => $details) {
			$this->devices[$device]['vendor'] = 'Software';
			$this->devices[$device]['model'] = 'RAID';
			$this->devices[$device]['type'] = $details['type'];
		}

		foreach ($this->devices as $device => $details) {
			$this->devices[$device]['mounted'] = $this->IsMounted($device);
			if ($this->devices[$device]['mounted'])
				$this->devices[$device]['mount_point'] = $this->mount_point;
		}

		$purge = array();
		if (!$mounted) {
			foreach ($this->devices as $device => $details) {
				if (!$details['mounted']) continue;
				$purge[] = $device;
			}
		}
		if (!$swap) {
			foreach ($this->devices as $device => $details) {
				if (!$this->IsSwap($device)) continue;
				$purge[] = $device;
			}
		}

		foreach ($purge as $device) unset($this->devices[$device]);

		ksort($this->devices);
		$this->is_scanned = true;
	}

	/**
	 * @access private
	 */

	final private function ScanAtapi()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$scan = Array();
		// Find IDE devices that match: %d.%d
		$entries = $this->ScanDir(self::IDE_DEVICES, '/^\d.\d$/');

		// Scan all ATAPI/IDE devices.
		foreach ($entries as $entry) {
			$path = self::IDE_DEVICES . "/$entry";
			if (($block_devices = $this->ScanDir("$path/block", '/^dev$/')) === false) {
				if (($block_devices = $this->ScanDir($path, '/^block:.*$/')) === false) continue;
				if (!count($block_devices)) continue;
				$path .= '/' . $block_devices[0];
			} else $path .= '/block';
			if (($block = basename(readlink("$path"))) === false) continue;

			$info = array();
			$info['type'] = 'IDE/ATAPI';

			try {
				$file = new File(self::PROC_IDE . "/$block/model", true);
				if ($file->Exists())
					list($info['vendor'], $info['model']) = split(' ', $file->GetContents(), 2);
			} catch (Exception $e) {
				self::Log(COMMON_WARNING, $e->GetMessage(), __METHOD__, __LINE__);
			}

			// Here we are looking for detected partitions
			if (($partitions = $this->ScanDir($path,
				"/^$block\d$/")) !== false && count($partitions) > 0) {
				foreach($partitions as $partition)
					$info['partition'][] = "/dev/$partition";
			}

			$scan["/dev/$block"] = $info;
		}
		return $scan;
	}
 
	/**
	 * @access private
	 */

	final private function ScanScsi()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$devices = Array();

		try {
			// Find USB devices that match: %d-%d
			$entries = $this->ScanDir(self::USB_DEVICES, '/^\d-\d$/');

			// Walk through the expected USB -> SCSI /sys paths.
			foreach ($entries as $entry) {
				$path = self::USB_DEVICES . "/$entry";
				if (($devid = $this->ScanDir($path, "/^$entry:\d\.\d$/")) === false) continue;
				if (count($devid) != 1) continue;

				// Might need this product
				//if (!($fh = fopen("$path/product", 'r'))) continue;
				//$device['product'] = chop(fgets($fh, 4096));
				//fclose($fh);

				$path .= '/' . $devid[0];
				if (($host = $this->ScanDir($path, '/^host\d+$/')) === false) continue;
				if (count($host) != 1) continue;
				$path .= '/' . $host[0];
				if (($target = $this->ScanDir($path, '/^target\d+:\d:\d$/')) === false) continue;
				if (count($target) != 1) continue;
				$path .= '/' . $target[0];
				if (($lun = $this->ScanDir($path, '/^\d+:\d:\d:\d$/')) === false) continue;
				if (count($lun) != 1) continue;
				$path .= '/' . $lun[0];
				if (($dev = $this->ScanDir("$path/block", '/^dev$/')) === false) continue;
				if (count($dev) != 1) continue;

				// Validate USB mass-storage device
				if (!($fh = fopen("$path/vendor", 'r'))) continue;
				$device['vendor'] = chop(fgets($fh, 4096));
				fclose($fh);
				if (!($fh = fopen("$path/model", 'r'))) continue;
				$device['model'] = chop(fgets($fh, 4096));
				fclose($fh);
				if (!($fh = fopen("$path/block/dev", 'r'))) continue;
				$device['nodes'] = chop(fgets($fh, 4096));
				fclose($fh);
				$device['path'] = $path;
				$device['bus'] = 'usb';

				// Valid device found (almost, continues below)...
				$devices[] = $device;
			}

			// Find SCSI devices that match: %d:%d:%d:%d
			$entries = $this->ScanDir(self::SCSI_DEVICES, '/^\d:\d:\d:\d$/');

			// Scan all SCSI devices.
			if ($entries !== false) {
				foreach ($entries as $entry) {
					$block = 'block';
					$path = self::SCSI_DEVICES . "/$entry";
					if (($dev = $this->ScanDir("$path/block", '/^dev$/')) === false) {
						if (($block_devices = $this->ScanDir("$path", '/^block:.*$/')) === false) continue;
						$block = $block_devices[0];
						if (($dev = $this->ScanDir("$path/$block", '/^dev$/')) === false) continue;
					}
					if (count($dev) != 1) continue;

					// Validate SCSI storage device
					if (!($fh = fopen("$path/vendor", 'r'))) continue;
					$device['vendor'] = chop(fgets($fh, 4096));
					fclose($fh);
					if (!($fh = fopen("$path/model", 'r'))) continue;
					$device['model'] = chop(fgets($fh, 4096));
					//$device['product'] = $device['model'];
					fclose($fh);
					if (!($fh = fopen("$path/$block/dev", 'r'))) continue;
					$device['nodes'] = chop(fgets($fh, 4096));
					fclose($fh);
					$device['path'] = "$path/$block";
					$device['bus'] = 'scsi';

					// Valid device found (almost, continues below)...
					$unique = true;
					foreach ($devices as $usb) {
						if ($usb['nodes'] != $device['nodes']) continue;
						$unique = false;
						break;
					}

					if ($unique) $devices[] = $device;
				}
			}

			if (count($devices)) {
				// Create a hashed array of all device nodes that match: /dev/s*
				// XXX: This can be fairly expensive, takes a few seconds to run.
				if (!($ph = popen('stat -c 0x%t:0x%T:%n /dev/s*', 'r')))
					throw new Exception("Error running stat command", COMMON_WARNING);

				$nodes = array();
				$major = '';
				$minor = '';
				
				while (!feof($ph)) {
					$buffer = chop(fgets($ph, 4096));
					if (sscanf($buffer, '%x:%x:', $major, $minor) != 2) continue;
					if ($major == 0) continue;
					$nodes["$major:$minor"] = substr($buffer, strrpos($buffer, ':') + 1);
				}

				// Clean exit?
				if (pclose($ph) != 0)
					throw new Exception("Error running stat command", COMMON_WARNING);

				// Hopefully we can now find the true device name for each
				// storage device found above.  Validation continues...
				foreach ($devices as $key => $device) {
					if (!isset($nodes[$device['nodes']])) {
						unset($devices[$key]);
						continue;
					}

					// Set the block device
					$devices[$key]['device'] = $nodes[$device['nodes']];

					// Here we are looking for detected partitions
					if (($partitions = $this->ScanDir($device['path'],
						'/^' . basename($nodes[$device['nodes']]) . '\d$/')) !== false && count($partitions) > 0) {
						foreach($partitions as $partition)
							$devices[$key]['partition'][] = dirname($nodes[$device['nodes']]) . '/' . $partition;
					}

					unset($devices[$key]['path']);
					unset($devices[$key]['nodes']);
				}
			}
		} catch (Exception $e) {
			self::Log(COMMON_WARNING, $e->GetMessage(), __METHOD__, __LINE__);
		}

		return $devices;
	}

	// This function scans a directory returning files that match the pattern.
	final private function ScanDir($dir, $pattern)
	{
		if (!($dh = opendir($dir))) return false;

		$matches = array();
		while (($file = readdir($dh)) !== false) {
			if (!preg_match($pattern, $file)) continue;
			$matches[] = $file;
		}

		closedir($dh);
		sort($matches);

		return $matches;
	}

}

// vim: syntax=php ts=4
?>
