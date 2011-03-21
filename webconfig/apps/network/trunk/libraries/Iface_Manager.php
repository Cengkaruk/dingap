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
 * Network interface manager class.
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

clearos_load_library('firewall/Firewall');
clearos_load_library('base/Folder');
clearos_load_library('network/Iface');
clearos_load_library('network/Network');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Network interface manager class.
 *
 * @package ClearOS
 * @subpackage API
 * @author {@link http://www.clearfoundation.com/ ClearFoundation}
 * @license http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @copyright Copyright 2003-2010 ClearFoundation
 */

class Iface_Manager extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
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
     * Iface_Manager constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Returns array of interfaces (real and dynamic).
     *
     * @param bool $ignore_ppp ignore PPP interfaces
     * @param bool $ignore_lo ignore loopback interfaces
     * @return array list of network devices (using ifconfig.so)
     * @throws Engine_Exception
     */

    public function get_interfaces($ignore_ppp, $ignore_lo)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! extension_loaded('ifconfig')) {
            if (!@dl('ifconfig.so'))
                throw new Engine_Exception(LOCALE_LANG_ERRMSG_WEIRD, CLEAROS_WARNING);
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
            $listing = $folder->get_listing();

            foreach ($listing as $netconfig) {
                if (preg_match('/^ifcfg-(.*)/', $netconfig, $matches))
                    $rawlist[] = $matches[1];
            }
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), CLEAROS_WARNING);
        }

        // Purge unwanted interfaces
        //--------------------------

        $rawlist = array_unique($rawlist);
        $interfaces = array();

        foreach ($rawlist as $iface) {
            // Ignore IPv6-related sit0 interface for now
            if (preg_match('/^sit/', $iface))
                continue;

            if ($ignore_ppp && preg_match('/^pp/', $iface))
                continue;

            if ($ignore_lo && $iface == 'lo')
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
                throw new Engine_Exception(LOCALE_LANG_ERRMSG_WEIRD, CLEAROS_WARNING);
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
     * @throws Engine_Exception
     */

    public function get_interface_details()
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->is_loaded)
            return $this->ethinfo;

        $slaveif = array();
        $ethlist = $this->get_interfaces(false, true);

        foreach ($ethlist as $eth) {

            $interface = new Iface($eth);
            $ifdetails = $interface->get_interface_info();

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
                $role = $firewall->get_interface_role($eth);
                $rolename = $firewall->get_interface_role_text($eth);

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
     * @throws Engine_Exception
     */

    public function get_lan_networks()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $network = new Network();
            $firewall = new Firewall();
            $mode = $network->GetMode();
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), CLEAROS_WARNING);
        }

        $ethlist = $this->get_interface_details();

        $lans = array();

        foreach ($ethlist as $eth => $details) {
            // Only interested in configured interfaces
            if (! $details['configured'])
                continue;

            // Gateway mode
            if (($details['role'] == Firewall::CONSTANT_LAN) && (! empty($details['address'])) && (! empty($details['netmask']))) {
                $basenetwork = $network->get_network_address($details['address'], $details['netmask']);
                $lans[] = $basenetwork . "/" . $details['netmask'];
            }

            // Standalone mode
            if (($details['role'] == Firewall::CONSTANT_EXTERNAL) && (! empty($details['address'])) && (! empty($details['netmask'])) &&
                ($mode == Network::MODE_TRUSTEDSTANDALONE) || ($mode == Network::MODE_STANDALONE)) {
                $basenetwork = $network->get_network_address($details['address'], $details['netmask']);
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
     * @throws Engine_Exception
     */

    public function get_external_ip_address()
    {
        $interface = $this->get_external_interface();

        if ($interface != null)
            return $interface['address'];
    }

    /**
     * Returns the external interface
     *
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
     * @param boolean $exclude_virtual exclude virtual interfaces
     * @throws Engine_Exception
     */

    public function get_external_interfaces($exclude_virtual = true)
    {
        clearos_profile(__METHOD__, __LINE__);

        $ifaces = array();
        $ethlist = $this->get_interface_details();

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

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////
}
