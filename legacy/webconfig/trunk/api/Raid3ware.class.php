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
 * Provides monitoring/management tools to software/hardware RAID arrays.
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
require_once('StorageDevice.class.php');

///////////////////////////////////////////////////////////////////////////////
// E X C E P T I O N  C L A S S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * RAID Management Utility.
 *
 * Class to assist in management and notification of a 3ware hardtware RAID controller.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2007, Point Clark Networks
 */

class Raid3ware extends Raid
{
	///////////////////////////////////////////////////////////////////////////////
	// V A R I A B L E S
	///////////////////////////////////////////////////////////////////////////////

	protected $interactive = true;

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Raid3ware constructor.
	 *
	 */

	public function __construct()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct();

		$this->type = self::TYPE_3WARE;
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
		$controllers = Array();

		$shell = new ShellExec;
		$args = 'rescan';
		$options['env'] = "LANG=en_US";
		$retval = $shell->Execute(self::CMD_TW_CLI, $args, true, $options);
		if ($retval != 0) {
			$errstr = $shell->GetLastOutputLine();
			throw new EngineException($errstr, COMMON_WARNING);
		}
		$args = 'info';
		$retval = $shell->Execute(self::CMD_TW_CLI, $args, true, $options);
		if ($retval != 0) {
			$erroutput = $shell->GetOutput();
			foreach ($erroutput as $errstr) {
				if (isset($errstr) && $errstr)
					throw new EngineException($errstr, COMMON_WARNING);
			}
		} else {
			$lines = $shell->GetOutput();
			foreach ($lines as $line) {
				if (preg_match("/^c(\d+)\s+(\S+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(.*)$/", $line, $match))
					$controllers[$match[1]] = Array('model'=>$match[2], 'ports'=>$match[3], 'drives'=>$match[4]);
			}
		}

		foreach ($controllers as $id => $controller) {
			$args = 'info c' . $id . ' model';
			$retval = $shell->Execute(self::CMD_TW_CLI, $args, true, $options);
			$myarrays[$id]['model'] = RAID_LANG_UNKNOWN;
			if ($retval == 0) {
				$lines = $shell->GetOutput();
				$model = $shell->GetFirstOutputLine();
				if (preg_match("/^.*\s=\s(.*)$/", $model, $match)) 
					$myarrays[$id]['model'] = $match[1];
			}
			$args = 'info c' . $id;
			$retval = $shell->Execute(self::CMD_TW_CLI, $args, true, $options);
			if ($retval != 0) {
				$erroutput = $shell->GetOutput();
				foreach ($erroutput as $errstr) {
					if (isset($errstr) && $errstr)
						throw new EngineException($errstr, COMMON_WARNING);
				}
			} else {
				$lines = $shell->GetOutput();
				foreach ($lines as $line) {
					if (preg_match("/^u(\d+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(\d+.\d).*$/", $line, $match)) {
						$myarrays[$id]['units'][$match[1]]['level'] = strtoupper($match[2]);
						$myarrays[$id]['units'][$match[1]]['size'] = $match[7]*1024*1024;
						# Status
						$myarrays[$id]['units'][$match[1]]['status'] = self::STATUS_CLEAN;
						if ($match[3] != 'OK')
							$myarrays[$id]['units'][$match[1]]['status'] = self::STATUS_DEGRADED;
						if ($match[3] == 'REBUILDING') {
							$myarrays[$id]['units'][$match[1]]['status'] = self::STATUS_SYNCING;
							$recovery = $match[4];
						}
						$args = 'info c' . $id . ' u' . $match[1];
						$retval = $shell->Execute(self::CMD_TW_CLI, $args, true, $options);
						if ($retval != 0) {
							$erroutput = $shell->GetOutput();
							foreach ($erroutput as $errstr) {
								if (isset($errstr) && $errstr)
									throw new EngineException($errstr, COMMON_WARNING);
							}
						} else {
							$details = $shell->GetOutput();
							foreach ($details as $detail) {
								if (preg_match("/^u(\d+)-(\d+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(\d+.\d+)$/", $detail, $match)) {
									$myarrays[$id]['units'][$match[1]]['devices'][$match[2]]['status'] = self::STATUS_CLEAN;
									if ($match[4] != 'OK')
										$myarrays[$id]['units'][$match[1]]['devices'][$match[2]]['status'] = self::STATUS_DEGRADED;
									if ($match[4] == 'DEGRADED' && $match[7] == '-')
										$myarrays[$id]['units'][$match[1]]['devices'][$match[2]]['status'] = self::STATUS_REMOVED;
									if ($match[4] == 'DEGRADED' && $myarrays[$id]['units'][$match[1]]['status'] == self::STATUS_SYNCING) {
										$myarrays[$id]['units'][$match[1]]['devices'][$match[2]]['status'] = self::STATUS_SYNCING;
										$myarrays[$id]['units'][$match[1]]['devices'][$match[2]]['recovery'] = $recovery;
									}
								}
							}
						}
					} else if (preg_match("/^p(\d+)\s+OK\s+-\s+(\d+.\d+)\s+(\S+)\s+(\d+)\s+(\S+)$/", $line, $match)) {
						$myarrays[$id]['spares'][$match[1]] = Array('size'=>$match[4]*512, 'serial'=>$match[5]);
					}
				}
			}
		}
		
		ksort($myarrays);
		return $myarrays;
	}

	/**
	 * Gets the mapping of a RAID array to physical device as seen by the OS.
	 * @param  String  $unit  a unit on the controller
	 *
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
			if ($device['vendor'] != '3ware')
				continue;
			if (!preg_match("/^Logical Disk (\d+)$/", $device['model'], $match))
				continue;
			if ($match[1] == $unit)
				$id = preg_replace('/\d/', '', $dev);
		}
		return $id;
	}

	/**
	 * Removes a device from the specified controller.
	 *
	 * @param string $controller the controller ID
	 * @param string $port  the port ID
	 * @return void
	 * @throws EngineException
	 */

	function RemoveDevice($controller, $port)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$shell = new ShellExec;
			$args = 'remove c' . $controller . ' p' . $port;
			$options['env'] = "LANG=en_US";
			$retval = $shell->Execute(self::CMD_TW_CLI, $args, true, $options);
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
	 * @param string $device the unit
	 * @param string $port the port
	 * @return void
	 * @throws EngineException
	 */

	function RepairArray($controller, $unit, $port)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$shell = new ShellExec;
			$args = 'maint rebuild c' . $controller . ' u' . $unit . ' p' . $port;
			$options['env'] = "LANG=en_US";
			$retval = $shell->Execute(self::CMD_TW_CLI, $args, true, $options);
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

	///////////////////////////////////////////////////////////////////////////////
	// V A L I D A T I O N   M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

}
// vim: syntax=php ts=4
?>
