<?php

/**
 * Network routes class.
 *
 * @category   Apps
 * @package    Network
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network/
 */

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

///////////////////////////////////////////////////////////////////////////////
// N A M E S P A C E
///////////////////////////////////////////////////////////////////////////////

namespace clearos\apps\network;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('base');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////


// Classes
//--------

use \clearos\apps\base\Engine as Engine;
use \clearos\apps\base\File as File;
use \clearos\apps\base\Shell as Shell;
use \clearos\apps\network\Iface_Manager as Iface_Manager;
use \clearos\apps\network\Role as Role;
use \clearos\apps\network\Routes as Routes;

clearos_load_library('base/Engine');
clearos_load_library('base/File');
clearos_load_library('base/Shell');
clearos_load_library('network/Iface_Manager');
clearos_load_library('network/Role');
clearos_load_library('network/Routes');

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\File_No_Match_Exception as File_No_Match_Exception;
use \clearos\apps\base\File_Not_Found_Exception as File_Not_Found_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('base/File_No_Match_Exception');
clearos_load_library('base/File_Not_Found_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Network routes class.
 *
 * @category   Apps
 * @package    Network
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network/
 */

class Routes extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const FILE_CONFIG = '/etc/sysconfig/network-scripts/route-';
    const FILE_ACTIVE = '/proc/net/route';
    const FILE_NETWORK = '/etc/sysconfig/network';
    const FILE_SYSTEM_NETWORK = '/etc/system/network';
    const COMMAND_IP = '/sbin/ip';

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Routes constructor.
     */

    function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);
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
        $shell = new Shell();

        // Try multi-WAN table first
        //--------------------------

        $shell->execute(self::COMMAND_IP, 'route show table 250');
        $output = $shell->get_output();

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

        $shell->execute(self::COMMAND_IP, 'route');
        $output = $shell->get_output();

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
        } catch (Engine_Exception $e) {
            throw new Engine_Exception($e->get_message());
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

        $file = new File(self::FILE_NETWORK);

        try {
            $device = $file->lookup_value('/^GATEWAYDEV=/');
        } catch (File_No_Match_Exception $e) {
            return 'eth0'; // Default to eth0
        } 

        $device = preg_replace('/\"/', '', $device);

        return $device;
    }

    /**
     * Sets the network device (eg eth0) doing the default route.
     *
     * @param string $device the default route device
     *
     * @return void
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

        $file = new File(self::FILE_NETWORK);
        $match = $file->replace_lines('/^GATEWAYDEV=/', "GATEWAYDEV=\"$device\"\n");

        // If tag does not exist, add it
        //------------------------------

        if (! $match)
            $file->add_lines("GATEWAYDEV=\"" . $device . "\"\n");
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

        $file = new File(self::FILE_NETWORK);
        $file->delete_lines('/GATEWAYDEV=\".*\"/i');

        $interfaces = new Iface_Manager();
        $ethlist = $interfaces->get_interface_details();
        $wanif = "";

        // FIXME: Firewall dependency needs to be handled
        foreach ($ethlist as $eth => $info) {
            if (isset($info['role']) && ($info['role'] == Role::ROLE_EXTERNAL)) {
                $wanif = $eth;
                break;
            }
        }

        if ($wanif)
            $this->set_gateway_device($wanif);
    }
}
