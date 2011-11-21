<?php

/**
 * Network interface manager class.
 *
 * @category   Apps
 * @package    Network
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2002-2011 ClearFoundation
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
clearos_load_language('network');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Engine as Engine;
use \clearos\apps\base\Folder as Folder;
use \clearos\apps\network\Iface as Iface;
use \clearos\apps\network\Network as Network;
use \clearos\apps\network\Role as Role;

clearos_load_library('base/Engine');
clearos_load_library('base/Folder');
clearos_load_library('network/Iface');
clearos_load_library('network/Network');
clearos_load_library('network/Role');

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;

clearos_load_library('base/Engine_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Network interface manager class.
 *
 * @category   Apps
 * @package    Network
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2002-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network/
 */

class Iface_Manager extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const EXTERNAL_ROLE = 'EXTIF'; // TODO: should match firewall/Role constant
    const PATH_NET_CONFIG = '/etc/sysconfig/network-scripts';

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $is_loaded = FALSE;
    protected $ethinfo = array();

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Iface_Manager constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Returns array of interfaces (real and dynamic).
     *
     * Filter options:
     * - filter_imq: filters out IMQ interfaces (default: TRUE)
     * - filter_ppp: filters out PPP interfaces (default: FALSE)
     * - filter_loopback: filter out loopback interface (default: TRUE)
     * - filter_pptp: filters out PPTP VPN interfaces (default: TRUE)
     * - filter_sit: filters out sit interfaces (default: TRUE)
     * - filter_tun: filters out tunnel (OpenVPN) interfaces (default: TRUE)
     * - filter_virtual: filters out virtual interfaces (default: TRUE)
     *
     * @param array $filter filter options
     *
     * @return array list of network devices (using ifconfig.so)
     * @throws Engine_Exception
     */

    public function get_interfaces($options = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        $options['filter_imq'] = isset($options['filter_imq']) ? $options['filter_imq'] : TRUE;
        $options['filter_ppp'] = isset($options['filter_ppp']) ? $options['filter_ppp'] : FALSE;
        $options['filter_loopback'] = isset($options['filter_loopback']) ? $options['filter_loopback'] : TRUE;
        $options['filter_pptp'] = isset($options['filter_pptp']) ? $options['filter_pptp'] : TRUE;
        $options['filter_sit'] = isset($options['filter_sit']) ? $options['filter_sit'] : TRUE;
        $options['filter_tun'] = isset($options['filter_tun']) ? $options['filter_tun'] : TRUE;
        $options['filter_virtual'] = isset($options['filter_virtual']) ? $options['filter_virtual'] : TRUE;

        if (! extension_loaded('ifconfig')) {
            if (!@dl('ifconfig.so'))
                throw new Engine_Exception(lang('network_network_error_occurred'));
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

        $matches = array();
        $folder = new Folder(self::PATH_NET_CONFIG);
        $listing = $folder->get_listing();

        foreach ($listing as $netconfig) {
            if (preg_match('/^ifcfg-(.*)/', $netconfig, $matches))
                $rawlist[] = $matches[1];
        }

        // Purge unwanted interfaces
        //--------------------------

        $rawlist = array_unique($rawlist);
        $interfaces = array();

        foreach ($rawlist as $iface) {
            if ($options['filter_imq'] && preg_match('/^imq/', $iface))
                continue;

            if ($options['filter_loopback'] && $iface == 'lo')
                continue;

            if ($options['filter_ppp'] && preg_match('/^ppp/', $iface))
                continue;

            if ($options['filter_pptp'] && preg_match('/^pptp/', $iface))
                continue;

            if ($options['filter_sit'] && preg_match('/^sit/', $iface))
                continue;

            if ($options['filter_tun'] && preg_match('/^tun/', $iface))
                continue;

            if ($options['filter_virtual'] && preg_match('/:/', $iface))
                continue;

            $interfaces[] = $iface;
        }

        return $interfaces;
    }

    /**
     * Returns interface count (real interfaces only).
     *
     * @return int number of real network devices (using ifconfig.so)
     * @throws Engine_Exception
     */

    public function get_interface_count()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!extension_loaded('ifconfig')) {
            if (!@dl('ifconfig.so'))
                throw new Engine_Exception(lang('network_network_error_occurred'));
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
     * See get_interfaces for details on the options parameter. This method
     * also adds the following options:
     *
     * - filter_ppp: filters out PPP interfaces (default: FALSE)
     *
     * @param array $options filter options
     *
     * @return array information on all network interfaces.
     * @throws Engine_Exception
     */

    public function get_interface_details($options = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        $options['filter_1to1_nat'] = isset($options['filter_1to1_nat']) ? $options['filter_1to1_nat'] : TRUE;
        $options['filter_non_configurable'] = isset($options['filter_non_configurable']) ? $options['filter_non_configurable'] : TRUE;
        $options['filter_slave'] = isset($options['filter_slave']) ? $options['filter_slave'] : TRUE;

        if ($this->is_loaded)
            return $this->ethinfo;

        $slaveif = array();
        $ethlist = $this->get_interfaces($options);

        foreach ($ethlist as $eth) {

            $interface = new Iface($eth);
            $ifdetails = $interface->get_info();

            // Filter options
            //---------------

            if ($options['filter_non_configurable'] && isset($ifdetails['configurable']) && !$ifdetails['configurable'])
                continue;

            if ($options['filter_slave'] && isset($ifdetails['master']) && $ifdetails['master'])
                continue;

            if ($options['filter_1to1_nat'] && isset($ifdetails['one-to-one-nat']) && $ifdetails['one-to-one-nat'])
                continue;

            // Core configuration
            //-------------------

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
                $role = new Role();
                $role_code = $role->get_interface_role($eth);
                $role_name = $role->get_interface_role_text($eth);

                $ethinfo[$eth]['role'] = $role_code;
                $ethinfo[$eth]['roletext'] = $role_name;
            } catch (Exception $e) {
                // keep going
            }
        }

        foreach ($slaveif as $master => $slave) {
            $ethinfo[$slave]['role'] = $ethinfo[$master]['role'];
            $ethinfo[$slave]['roletext'] = $ethinfo[$master]['roletext'];
        }

        $this->ethinfo = $ethinfo;
        $this->is_loaded = TRUE;

        return $ethinfo;
    }

    /**
     * Returns an array of trusted IP addresses.
     *
     * @return array LAN IPs
     * @throws Engine_Exception
     */

    public function get_trusted_ips()
    {
        clearos_profile(__METHOD__, __LINE__);

        $ips = array();

        $role_object = new Role();
        $network = new Network();
        $iface_manager = new Iface_Manager();

        $mode = $network->get_mode();
        $ifaces = $iface_manager->get_interfaces();

        foreach ($ifaces as $if) {
            $iface = new Iface($if);
            $ifinfo = $iface->get_info();

            // If the interface is down, ignore it
            if (! ($ifinfo['flags'] & IFF_UP))
                continue;

            // Determine role of interface
            if (isset($ifinfo["ifcfg"]["device"]))
                $ifcfg = $ifinfo["ifcfg"]["device"];

            $role = $role_object->get_interface_role($ifcfg);

            switch ($role) {
                case Role::ROLE_DMZ:
                    break;

                case Role::ROLE_LAN:
                    $ips[] = $ifinfo["address"];
                    break;

                case Role::ROLE_EXTERNAL:
                    switch ($mode) {
                        case Network::MODE_STANDALONE:
                            $ips[] = $ifinfo["address"];
                            break;
                        case Network::MODE_TRUSTED_STANDALONE:
                            $ips[] = $ifinfo["address"];
                            break;
                    }

                    break;
            }
        }

        return $ips;
    }

    /**
     * Returns list of available LAN networks.
     *
     * @return array list of available LAN networks.
     * @throws Engine_Exception
     */

    public function get_lan_networks()
    {
        clearos_profile(__METHOD__, __LINE__);

        $network = new Network();
        $mode = $network->get_mode();

        $ethlist = $this->get_interface_details();

        $lans = array();

        foreach ($ethlist as $eth => $details) {
            // Only interested in configured interfaces
            if (! $details['configured'])
                continue;

            // Gateway mode
            if (($details['role'] == Role::ROLE_LAN) && (! empty($details['address'])) && (! empty($details['netmask']))) {
                $basenetwork = Network_Utils::get_network_address($details['address'], $details['netmask']);
                $lans[] = $basenetwork . "/" . $details['netmask'];
            }

            // Standalone mode
            if (($details['role'] == Role::ROLE_EXTERNAL) && (! empty($details['address'])) && (! empty($details['netmask']))
                && ($mode == Network::MODE_TRUSTEDSTANDALONE) || ($mode == Network::MODE_STANDALONE)
            ) {
                $basenetwork = Network_Utils::get_network_address($details['address'], $details['netmask']);
                $lans[] = $basenetwork . "/" . $details['netmask'];
            }
        }

        return $lans;
    }

    /**
     * Returns list of Wifi interfaces.
     *
     * @return array list of Wifi interfaces
     * @throws Engine_Exception
     */

    public function get_wifi_interfaces()
    {
        clearos_profile(__METHOD__, __LINE__);

        $ethlist = $this->get_interface_details();
        $wifilist = array();

        foreach ($ethlist as $eth => $details) {
            if ($details['type'] == Iface::TYPE_WIRELESS)
                $wifilist[] = $eth;
        }

        return $wifilist;
    }
    
    /**
     * Returns the external IP address
     *
     * @return external IP address
     * @throws Engine_Exception
     */

    public function get_external_ip_address()
    {
        $interface = $this->get_external_interface();

        if ($interface != NULL)
            return $interface['address'];
    }

    /**
     * Returns the external interface.
     *
     * @return external interface
     * @throws Engine_Exception
     */

    public function get_external_interface()
    {
        $ethlist = $this->get_interface_details();

        foreach ($ethlist as $eth => $details) {
            if ($details['role'] == 'EXTIF')
                return $details;
        }
    }

    /**
     * Returns a list of interfaces configured with the given role.
     *
     * @return array list of external interfaces
     * @throws Engine_Exception
     */

    public function get_external_interfaces()
    {
        clearos_profile(__METHOD__, __LINE__);

        $ifaces = array();
        $ethlist = $this->get_interface_details();

        foreach ($ethlist as $eth => $info) {
            if ($info['role'] != Role::ROLE_EXTERNAL)
                continue;

            $ifaces[] = $eth;   
        }

        return $ifaces;
    }
}
