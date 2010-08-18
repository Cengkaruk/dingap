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
 * Network routes class.
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

require_once('File.class.php');
require_once('Firewall.class.php');
require_once('IfaceManager.class.php');
require_once('Network.class.php');
require_once('ShellExec.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Network routes class.
 *
 * @package Api
 * @subpackage Network
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class Routes extends Network
{
	///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

	const FILE_CONFIG = '/etc/sysconfig/network-scripts/route-';
	const FILE_ACTIVE = '/proc/net/route';
	const FILE_NETWORK = '/etc/sysconfig/network';
	const FILE_SYSTEM_NETWORK = '/etc/system/network';
	const CMD_IP = '/sbin/ip';

	///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Routes constructor.
     *
     * @return void
     */

    function __construct()
    {
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

        parent::__construct();
    }

	/**
	 * Get all default routes.
	 *
	 * On multi-WAN systems, you can have more than one default route.
	 * This method returns a hash array keyed on interface names.
	 *
	 * @return  array  default route information
	 * @throws EngineException
	 */

	function GetDefaultInfo()
	{
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$routeinfo = array();
		$shell = new ShellExec();

		// Try multi-WAN table first
		//--------------------------

		try {
			$shell->Execute(self::CMD_IP, 'route show table 250', false);
			$output = $shell->GetOutput();
		} catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_ERROR);
        }

		if (! empty($output)) {
			foreach ($output as $line) {
				if (preg_match('/^\s*nexthop/', $line)) {
					$line = preg_replace('/\s+/', ' ', $line);
					$parts = explode(' ', trim($line));
					if ($parts[5]) {
						$routeinfo[$parts[4]] = $parts[2];
					}
				}
			}
		}

		// Fallback to single WAN
		//-----------------------

		try {
			$shell->Execute(self::CMD_IP, 'route', false);
			$output = $shell->GetOutput();
		} catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_ERROR);
        }

		if (! empty($output)) {
			foreach ($output as $line) {
				if (preg_match('/^default/', $line)) {
					$parts = explode(' ', $line);
					if ($parts[4]) {
						$routeinfo[$parts[4]] = $parts[2];
					}
				}
			}
		}

		return $routeinfo;
	}

	/**
	 * Get default route.
	 *
	 * @see  Routes::GetDefaultInfo()
	 * @return  string  default route
	 * @throws EngineException
	 */

	function GetDefault()
	{
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$file = new File(self::FILE_ACTIVE);
			$contents = $file->GetContentsAsArray();

			// Grab the last line in the route table
			$lastline = array_pop($contents);
			$lastline = preg_replace('/\s+/', ' ', $lastline);

			// Grab the second column (contains the default route)
			$lineitem = explode(' ', $lastline);

			// Split the IP up and make it readable
			$ip = str_split($lineitem[2], 2);

			return hexdec($ip[3]) . '.' . hexdec($ip[2]) . '.' . hexdec($ip[1]) . '.' . hexdec($ip[0]);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Returns extra LAN networks configured on the system.
	 *
	 * @return array list of extra LAN networks
	 * @throws EngineException
	 */

	function GetExtraLans()
	{
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$file = new File(self::FILE_SYSTEM_NETWORK);

		try {
			$lans = $file->LookupValue('/^EXTRALANS=/');
		} catch (FileNotFoundException $e) {
			return array();
		} catch (FileNoMatchException $e) {
			return array();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		} 

		$lans = preg_replace('/\"/', '', $lans);

		if (empty($lans))
			return array();

		return preg_split("/\s+/", $lans);
	}

	/**
	 * Gets the network device (eg eth0) doing the default route.
	 *
	 * @see  Routes::GetDefaultInfo()
	 * @return  string  default route device
	 * @throws EngineException
	 */

	function GetGatewayDevice()
	{
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$file = new File(self::FILE_NETWORK);
			try {
				$device = $file->LookupValue('/^GATEWAYDEV=/');
			} catch (FileNoMatchException $e) {
				return 'eth0'; // Default to eth0
			} 

			$device = preg_replace('/\"/', '', $device);

			return $device;
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Sets the network device (eg eth0) doing the default route.
	 *
	 * @param  string  $device  the default route device
	 * @return  void
	 * @throws EngineException
	 */

	function SetGatewayDevice($device)
	{
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Validate
		//---------
		// TODO

		// Update tag if it exists
		//------------------------

		try {
			$file = new File(self::FILE_NETWORK);
			$match = $file->ReplaceLines('/^GATEWAYDEV=/', "GATEWAYDEV=\"$device\"\n");

			// If tag does not exist, add it
			//------------------------------

			if (! $match)
				$file->AddLines("GATEWAYDEV=\"" . $device . "\"\n");

		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Deletes the network device (eg eth0) doing the default route.
	 *
	 * @return  void
	 * @throws EngineException
	 */

	function DeleteGatewayDevice()
	{
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Delete default route
		//---------------------

		try {
			$file = new File(self::FILE_NETWORK);
			$file->DeleteLines('/GATEWAYDEV=\".*\"/i');

			$interfaces = new IfaceManager();
			$ethlist = $interfaces->GetInterfaceDetails();
			$wanif = "";

			foreach ($ethlist as $eth => $info) {
				if (isset($info['role']) && ($info['role'] == Firewall::CONSTANT_EXTERNAL)) {
					$wanif = $eth;
					break;
				}
			}

			if ($wanif)
				$this->SetGatewayDevice($wanif);

		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
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
