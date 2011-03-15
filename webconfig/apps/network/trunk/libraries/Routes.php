<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2003-2010 ClearFoundation
//
///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Lesser General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

/**
 * Network routes class.
 *
 * @package ClearOS
 * @subpackage API
 * @author {@link http://www.clearfoundation.com/ ClearFoundation}
 * @license http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @copyright Copyright 2003-2010 ClearFoundation
 */

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = isset($_ENV['CLEAROS_BOOTSTRAP']) ? $_ENV['CLEAROS_BOOTSTRAP'] : '/usr/clearos/framework/shared';
require_once($bootstrap . '/bootstrap.php');

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('base');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

clearos_load_library('base/File');
clearos_load_library('firewall/Firewall');
clearos_load_library('network/Iface_Manager');
clearos_load_library('network/Network');
clearos_load_library('base/ShellExec');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Network routes class.
 *
 * @package ClearOS
 * @subpackage API
 * @author {@link http://www.clearfoundation.com/ ClearFoundation}
 * @license http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @copyright Copyright 2003-2010 ClearFoundation
 */

class Routes extends Engine
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
        clearos_profile(__METHOD__, __LINE__);

        parent::__construct();
    }

    /**
     * Get all default routes.
     *
     * On multi-WAN systems, you can have more than one default route.
     * This method returns a hash array keyed on interface names.
     *
     * @return  array  default route information
     * @throws Engine_Exception
     */

    public function get_default_info()
    {
        clearos_profile(__METHOD__, __LINE__);

        $routeinfo = array();
        $shell = new ShellExec();

        // Try multi-WAN table first
        //--------------------------

        try {
            $shell->execute(self::CMD_IP, 'route show table 250', false);
            $output = $shell->get_output();
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), CLEAROS_ERROR);
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
            $shell->execute(self::CMD_IP, 'route', false);
            $output = $shell->get_output();
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), CLEAROS_ERROR);
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
     * @see  Routes::get_default_info()
     * @return  string  default route
     * @throws Engine_Exception
     */

    public function get_default()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $file = new File(self::FILE_ACTIVE);
            $contents = $file->get_contents_as_array();

            // Grab the last line in the route table
            $lastline = array_pop($contents);
            $lastline = preg_replace('/\s+/', ' ', $lastline);

            // Grab the second column (contains the default route)
            $lineitem = explode(' ', $lastline);

            // Split the IP up and make it readable
            $ip = str_split($lineitem[2], 2);

            return hexdec($ip[3]) . '.' . hexdec($ip[2]) . '.' . hexdec($ip[1]) . '.' . hexdec($ip[0]);
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), CLEAROS_WARNING);
        }
    }

    /**
     * Returns extra LAN networks configured on the system.
     *
     * @return array list of extra LAN networks
     * @throws Engine_Exception
     */

    public function get_extra_lans()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_SYSTEM_NETWORK);

        try {
            $lans = $file->lookup_value('/^EXTRALANS=/');
        } catch (File_Not_Found_Exception $e) {
            return array();
        } catch (File_No_Match_Exception $e) {
            return array();
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), CLEAROS_WARNING);
        } 

        $lans = preg_replace('/\"/', '', $lans);

        if (empty($lans))
            return array();

        return preg_split("/\s+/", $lans);
    }

    /**
     * Gets the network device (eg eth0) doing the default route.
     *
     * @see  Routes::get_default_info()
     * @return  string  default route device
     * @throws Engine_Exception
     */

    public function get_gateway_device()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $file = new File(self::FILE_NETWORK);
            try {
                $device = $file->lookup_value('/^GATEWAYDEV=/');
            } catch (File_No_Match_Exception $e) {
                return 'eth0'; // Default to eth0
            } 

            $device = preg_replace('/\"/', '', $device);

            return $device;
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), CLEAROS_WARNING);
        }
    }

    /**
     * Sets the network device (eg eth0) doing the default route.
     *
     * @param  string  $device  the default route device
     * @return  void
     * @throws Engine_Exception
     */

    public function set_gateway_device($device)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validate
        //---------
        // TODO

        // Update tag if it exists
        //------------------------

        try {
            $file = new File(self::FILE_NETWORK);
            $match = $file->replace_lines('/^GATEWAYDEV=/', "GATEWAYDEV=\"$device\"\n");

            // If tag does not exist, add it
            //------------------------------

            if (! $match)
                $file->add_lines("GATEWAYDEV=\"" . $device . "\"\n");

        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), CLEAROS_WARNING);
        }
    }

    /**
     * Deletes the network device (eg eth0) doing the default route.
     *
     * @return  void
     * @throws Engine_Exception
     */

    public function delete_gateway_device()
    {
        clearos_profile(__METHOD__, __LINE__);

        // Delete default route
        //---------------------

        try {
            $file = new File(self::FILE_NETWORK);
            $file->delete_lines('/GATEWAYDEV=\".*\"/i');

            $interfaces = new Iface_Manager();
            $ethlist = $interfaces->get_interface_details();
            $wanif = "";

            foreach ($ethlist as $eth => $info) {
                if (isset($info['role']) && ($info['role'] == Firewall::CONSTANT_EXTERNAL)) {
                    $wanif = $eth;
                    break;
                }
            }

            if ($wanif)
                $this->set_gateway_device($wanif);

        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), CLEAROS_WARNING);
        }
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////
}
