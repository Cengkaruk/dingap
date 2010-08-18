<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2007-2008 Point Clark Networks.
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
 * Provides monitoring/management tools for LSI hardware RAID.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2007-2008, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('Raid.class.php');
require_once('StorageDevice.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Provides monitoring/management tools for LSI hardware RAID.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2007-2008, Point Clark Networks
 */

class RaidLsi extends Raid
{
	///////////////////////////////////////////////////////////////////////////////
	// V A R I A B L E S
	///////////////////////////////////////////////////////////////////////////////

	protected $interactive = false;

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * RaidLsi constructor.
	 *
	 * @return void
	 */

	public function __construct()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct();

		$this->type = self::TYPE_LSI;
	}

	/**
	 * Returns RAID arrays.
	 *
	 * @return array
	 * @throws EngineException
	 */

	function GetArrays()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$myarrays = array();
		$controllers = array();

		$shell = new ShellExec();
		$args = '--newstyle';
		$options['env'] = "LANG=en_US";
		$retval = $shell->Execute(self::CMD_MPT_STATUS, $args, true, $options);

		if ($retval == 1) {
			$erroutput = $shell->GetOutput();
			foreach ($erroutput as $errstr) {
				if (isset($errstr) && $errstr)
					throw new EngineException($errstr, COMMON_WARNING);
			}
		} else {
			$lines = $shell->GetOutput();
			foreach ($lines as $line) {
				if (preg_match("/^ioc:(\d+).*$/", $line, $match))
					$controllers[$match[1]] = array('model'=>RAID_LANG_UNKNOWN, 'ports'=>RAID_LANG_UNKNOWN, 'drives'=>RAID_LANG_UNKNOWN);
			}
		}
		foreach ($controllers as $id => $controller) {
			$myarrays[$id]['model'] = RAID_LANG_UNKNOWN;

			try {
				$args = '/proc/scsi/mptsas/' . $id;
				$shell->Execute(self::CMD_CAT, $args, false, $options);
				# ioc0: LSISAS1068, FwRev=00063200h, Ports=1, MaxQ=511
				if (preg_match("/^ioc(\d+):\s+LSI(\S+),\s+FwRev=(\S+),\s+Ports=(\d+).*$/", $shell->GetFirstOutputLine(), $match)) {
					$myarrays[$id]['model'] = $match[2];
					$myarrays[$id]['ports'] = $match[4];
				}
			} catch (Exception $e) {
				# Do nothing...just model
			}

			foreach ($lines as $line) {
				if (preg_match("/^ioc:(\d+)\s+vol_id:(\d+)\s+type:(\S+)\s+raidlevel:(\S+)\s+num_disks:(\d+)\s+size\(GB\):(\d+)\s+state:(.+)\s+flags:(.+)$/", $line, $match)) {
					# More than 1 unit not possible on these cards?  Let's hope so
					$myarrays[$match[1]]['units'][0]['level'] = strtoupper($match[4]);
					$myarrays[$match[1]]['units'][0]['size'] = $match[6]*1024*1024*1024;
					# Status
					$myarrays[$match[1]]['units'][0]['status'] = self::STATUS_CLEAN;

					if (!preg_match("/.*OPTIMAL.*/", $match[7]))
						$myarrays[$match[1]]['units'][0]['status'] = self::STATUS_DEGRADED;

					if (preg_match("/.*RESYNC_IN_PROGRESS.*/", $match[8]))
						$myarrays[$match[1]]['units'][0]['status'] = self::STATUS_SYNCING;

				} else if (preg_match("/^ioc:(\d+)\s+phys_id:(\d+)\s+scsi_id:(\d+)\s+vendor:(\S+)\s+product_id:(\S+)\s+revision:(\S+)\s+size\(GB\):(\d+)\s+state:\s+(.+)\s+flags:\s+(.+)\s+sync_state:\s+(\d+)\s+(.+)$/", $line, $match)) {
					$myarrays[$match[1]]['units'][0]['devices'][$match[2]]['status'] = self::STATUS_CLEAN;

					if (!preg_match("/.*ONLINE.*/", $match[8]))
						$myarrays[$match[1]]['units'][0]['devices'][$match[2]]['status'] = self::STATUS_DEGRADED;

					if (preg_match("/.*OUT_OF_SYNC.*/", $match[9]) && $myarrays[$match[1]]['units'][0]['status'] == self::STATUS_SYNCING) {
						$myarrays[$match[1]]['units'][0]['devices'][$match[2]]['status'] = self::STATUS_SYNCING;
						$myarrays[$match[1]]['units'][0]['devices'][$match[2]]['recovery'] = $match[10];
					}
				# Spares?
#				} else if (preg_match("/^p(\d+)\s+OK\s+-\s+(\d+.\d+)\s+(\S+)\s+(\d+)\s+(\S+)$/", $line, $match)) {
#					$myarrays[$id]['spares'][$match[1]] = array('size'=>$match[4]*512, 'serial'=>$match[5]);
				}
			}
		}
		
		ksort($myarrays);

		return $myarrays;
	}

	/**
	 * Gets the mapping of a RAID array to physical device as seen by the operating system.
	 *
	 * @param  string  $unit  a unit on the controller
	 * @returns  string  the device
	 */

	function GetMapping($unit)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$id = '';
		$storage = new StorageDevice();
		$devices = $storage->GetDevices();

		foreach ($devices as $dev => $device) {
			if ($device['vendor'] != 'Dell') # TODO...What about non-Dell branded cards?
				continue;

			if (!preg_match("/^VIRTUAL DISK$/", $device['model'], $match))
				continue;

			$id = preg_replace('/\d/', '', $dev);
		}

		return $id;
	}

	/**
	 * Removes a device from the specified controller.
	 *
	 * @param string $controller the controller ID
	 * @param string $port the port ID
	 * @return void
	 * @throws EngineException
	 */

	function RemoveDevice($controller, $port)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$shell = new ShellExec();
			$args = 'remove c' . $controller . ' p' . $port;
			$options['env'] = "LANG=en_US";
			$retval = $shell->Execute(self::CMD_MPT_STATUS, $args, true, $options);

			if ($retval != 0) {
				$erroutput = $shell->GetOutput();
				foreach ($erroutput as $errstr) {
					if (isset($errstr) && $errstr)
						throw new EngineException($errstr, COMMON_WARNING);
				}
			} else {
				$output = $shell->GetOutput();
			}
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage() . " ($controller:$port)", COMMON_WARNING);
		}
	}

	/**
	 * Repair an array with the specified parameters.
	 *
	 * @param string $controller the controller
	 * @param string $unit the unit
	 * @param string $port the port
	 * @return void
	 * @throws EngineException
	 */

	function RepairArray($controller, $unit, $port)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$shell = new ShellExec();
			$args = 'maint rebuild c' . $controller . ' u' . $unit . ' p' . $port;
			$options['env'] = "LANG=en_US";
			$retval = $shell->Execute(self::CMD_MPT_STATUS, $args, true, $options);

			if ($retval != 0) {
				$erroutput = $shell->GetOutput();
				foreach ($erroutput as $errstr) {
					if (isset($errstr) && $errstr)
						throw new EngineException($errstr, COMMON_WARNING);
				}
			} else {
				$output = $shell->GetOutput();
			}
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage() . " ($args)", COMMON_WARNING);
		}
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
