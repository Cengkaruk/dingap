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
 * Provides monitoring/management tools to software RAID arrays.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('Raid.class.php');

///////////////////////////////////////////////////////////////////////////////
// E X C E P T I O N  C L A S S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * RAID Management Utility.
 *
 * Class to assist in management and notification of a software RAID (mdadm) array.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2007, Point Clark Networks
 */

class RaidSoftware extends Raid
{
	///////////////////////////////////////////////////////////////////////////////
	// V A R I A B L E S
	///////////////////////////////////////////////////////////////////////////////

	const CMD_MDADM = '/sbin/mdadm';
	const CMD_DD = '/bin/dd';
	protected $interactive = true;
	protected $mdstat = Array();

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * RaidSoftware constructor.
	 *
	 */

	public function __construct()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct();

		$this->type = self::TYPE_SOFTWARE;
	}

	/**
	 * Returns RAID arrays.
	 *
	 * @return Array
	 * @throws EngineException
	 */

	function GetArrays()
	{

		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$myarrays = Array();

		$this->_GetMdStat();

		$dev = '';
		$physical_devices = Array();
		$raid_level = 0;
		$clean_array = true;
		foreach ($this->mdstat as $line) {
			if (ereg("^md([[:digit:]]+)[[:space:]]*:[[:space:]]*(.*)$", $line, $match)) {
				$dev = '/dev/md' . $match[1];
				list($state, $level, $device_list) = explode(' ', $match[2], 3);
				$myarrays[$dev]['state'] = $state; # Always 'active' and not very useful
				$myarrays[$dev]['status'] = self::STATUS_CLEAN; #Default
				$myarrays[$dev]['level'] = strtoupper($level);
				# Try to format for consistency (RAID-1, not RAID1)
				if (preg_match("/^RAID(\d+)$/", strtoupper($level), $match)) {
					$myarrays[$dev]['level'] = 'RAID-' . $match[1];
					$raid_level = $match[1];
				}
				
				$devices = explode(' ', $device_list);
				$members = Array();
				foreach ($devices as $device) {
					if (ereg("^(.*)\\[([[:digit:]]+)\\](.*)$", trim($device), $match))
						$members[$match[2]] = preg_match("/^\\/dev\\//", $match[1]) ? $match[1] : '/dev/' . $match[1];
				}
				ksort($members);
				foreach ($members as $index => $member) {
					$myarrays[$dev]['devices'][$index]['dev'] = $member;
					
					if (!in_array(preg_replace("/\d+/", "", $member), $physical_devices))
						$physical_devices[] = preg_replace("/\d+/", "", $member);
				}
			} else if (ereg("^[[:space:]]*([[:digit:]]+)[[:space:]]*blocks[[:space:]]*.*\[(.*)\]$", $line, $match)) {
				$myarrays[$dev]['size'] = $match[1]*1024;
				$clean_array = false;
				if (ereg('_', $match[2]))
					$myarrays[$dev]['status'] = self::STATUS_DEGRADED;
				$status = str_split($match[2]);
				$myarrays[$dev]['number'] = count($status);
				$counter = 0;
				foreach ($myarrays[$dev]['devices'] as $index => $myarray) {
					# If in degraded mode, any index greater than or equal to total disk has failed
					if ($index >= $myarrays[$dev]['number']) {
						$myarrays[$dev]['devices'][$index]['status'] = self::STATUS_SPARE;
						continue;
					} else if ($status[$counter] == "_") {
						$myarrays[$dev]['devices'][$index]['status'] = self::STATUS_DEGRADED;
					} else {
						$myarrays[$dev]['devices'][$index]['status'] = self::STATUS_CLEAN;
					}
					$counter++;
				}
			} else if (ereg("^[[:space:]]*(.*)recovery =[[:space:]]+([[:digit:]]+\\.[[:digit:]]+)%[[:space:]]*(.*)$", $line, $match)) {
				$clean_array = false;
				foreach ($myarrays[$dev]['devices'] as $index => $myarray) {
					if ($myarrays[$dev]['devices'][$index]['status'] == self::STATUS_DEGRADED) {
						$myarrays[$dev]['devices'][$index]['status'] = self::STATUS_SYNCING;
						$myarrays[$dev]['devices'][$index]['recovery'] = $match[2];
					}
				}
			} else if (ereg("^[[:space:]]*(.*)resync =[[:space:]]+([[:digit:]]+\\.[[:digit:]]+)%[[:space:]]*(.*)$", $line, $match)) {
				$clean_array = false;
				$this->_SetParameter('copy_mbr', '0');
				foreach ($myarrays[$dev]['devices'] as $index => $myarray) {
					$myarrays[$dev]['devices'][$index]['status'] = self::STATUS_SYNCING;
					$myarrays[$dev]['devices'][$index]['recovery'] = $match[2];
				}
			} else if (ereg("^.*resync=DELAYED.*$", $line, $match)) {
				$clean_array = false;
				foreach ($myarrays[$dev]['devices'] as $index => $myarray)
					$myarrays[$dev]['devices'][$index]['status'] = self::STATUS_SYNC_PENDING;
			}
		}
		
		ksort($myarrays);
		//if ((!isset($this->config['copy_mbr']) || $this->config['copy_mbr'] == 0) && $raid_level == 1 && $clean_array) {
		if (false) {
			sort($physical_devices);
			$is_first = true;
			foreach ($physical_devices as $dev) {
				if ($is_first) {
					$copy_from = $dev;
					$is_first = false;
					continue;
				}
				$shell = new ShellExec;
				$args = 'if=' . $copy_from . ' of=' . $dev . ' bs=512 count=1';
				$retval = $shell->Execute(self::CMD_DD, $args, true);
			}
			$this->_SetParameter('copy_mbr', '1');
			$this->loaded = false;
		}
		return $myarrays;
	}

	/**
	 * Removes a device from the specified array.
	 *
	 * @param string $array the array
	 * @param string $device the device
	 * @return void
	 * @throws EngineException
	 */

	function RemoveDevice($array, $device)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$shell = new ShellExec;
		$args = '-r ' . $array . ' ' . $device;
		$options['env'] = "LANG=en_US";
		$retval = $shell->Execute(self::CMD_MDADM, $args, true, $options);
		if ($retval != 0) {
			$errstr = $shell->GetLastOutputLine();
			throw new EngineException($errstr, COMMON_WARNING);
		} else {
			$this->mdstat = $shell->GetOutput();
		}
		#$this->loaded = true;
	}

	/**
	 * Repair an array with the specified device.
	 *
	 * @param string $array the array
	 * @param string $device the device
	 * @return void
	 * @throws EngineException
	 */

	function RepairArray($array, $device)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$shell = new ShellExec;
		$args = '-a ' . $array . ' ' . $device;
		$options['env'] = "LANG=en_US";
		$retval = $shell->Execute(self::CMD_MDADM, $args, true, $options);
		if ($retval != 0) {
			$errstr = $shell->GetLastOutputLine();
			throw new EngineException($errstr, COMMON_WARNING);
		} else {
			$this->mdstat = $shell->GetOutput();
		}
		#$this->loaded = true;
	}


	///////////////////////////////////////////////////////////////////////////////
	// P R I V A T E   M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * @access private
	 */
	function _GetMdStat()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$shell = new ShellExec;
		$args = self::FILE_MDSTAT;
		$options['env'] = "LANG=en_US";
		$retval = $shell->Execute(self::CMD_CAT, $args, false, $options);
		if ($retval != 0) {
			$errstr = $shell->GetLastOutputLine();
			throw new EngineException($errstr, COMMON_WARNING);
		} else {
			$this->mdstat = $shell->GetOutput();
		}
		#$this->loaded = true;
	}

	/**
	 * @access private
	 */

	function __destruct()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		parent::__destruct();
	}

	///////////////////////////////////////////////////////////////////////////////
	// V A L I D A T I O N   M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

}
// vim: syntax=php ts=4
?>
