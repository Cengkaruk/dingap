<?php

/**
 * Dnsmasq DHCP class.
 *
 * @category   Apps
 * @package    DHCP
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/dhcp/
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

namespace clearos\apps\dhcp;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('dhcp');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Daemon as Daemon;
use \clearos\apps\base\File as File;
use \clearos\apps\network\Ethers as Ethers;
use \clearos\apps\network\Iface as Iface;
use \clearos\apps\network\Iface_Manager as Iface_Manager;
use \clearos\apps\network\Network as Network;
use \clearos\apps\network\Network_Utils as Network_Utils;
use \clearos\apps\network\Routes as Routes;

clearos_load_library('base/Daemon');
clearos_load_library('base/File');
clearos_load_library('network/Ethers');
clearos_load_library('network/Iface');
clearos_load_library('network/Iface_Manager');
clearos_load_library('network/Network');
clearos_load_library('network/Network_Utils');
clearos_load_library('network/Routes');

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;
use \clearos\apps\network\Ethers_Not_Found_Exception as Ethers_Not_Found_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('base/Validation_Exception');
clearos_load_library('network/Ethers_Not_Found_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Dnsmasq DHCP class.
 *
 * This class only deals with the DHCP aspects of Dnsmasq.  Please see the
 * DNS app for other Dnsmasq methods.
 *
 * @category   Apps
 * @package    DHCP
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/dhcp/
 */

class Dnsmasq extends Daemon
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const FILE_DHCP = '/etc/dnsmasq.d/dhcp.conf';
    const FILE_CONFIG = '/etc/dnsmasq.conf';
    const FILE_LEASES = '/var/lib/dnsmasq/dnsmasq.leases';

    const DEFAULT_LEASETIME = '12'; // in hours
    const CONSTANT_UNLIMITED_LEASE = 'infinite';

    const TYPE_STATIC = 'static';
    const TYPE_DYNAMIC = 'dynamic';

    const OPTION_SUBNET_MASK = 1;
    const OPTION_GATEWAY = 3;
    const OPTION_DNS = 6;
    const OPTION_BROADCAST = 28;
    const OPTION_WINS = 44;
    const OPTION_NETBIOS_NODE_TYPE = 46;
    const OPTION_TFTP = 66;
    const OPTION_NTP = 42;

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $is_loaded = FALSE;
    protected $config = array();
    protected $subnets = array();
    protected $types = array();

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Dnsmasq constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        parent::__construct('dnsmasq');

        $this->types = array(
            self::TYPE_STATIC => lang('dhcp_static'),
            self::TYPE_DYNAMIC => lang('dhcp_dynamic')
        );
    }

    /**
     * Adds a static lease to DHCP server.
     *
     * @param string $mac MAC address
     * @param string $ip  IP address
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function add_static_lease($mac, $ip)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_mac($mac));
        Validation_Exception::is_valid($this->validate_ip($ip));

        $ethers = new Ethers();

        $ethers->add_ether($mac, $ip);
    }

    /**
     * Adds info for specific network subnet.
     *
     * @param string  $interface  network interface
     * @param string  $start      starting IP for DHCP range
     * @param string  $end        ending IP for DHCP range
     * @param integer $lease_time lease time in hours
     * @param string  $gateway    gateway IP address
     * @param array   $dns_list   DNS server list
     * @param string  $wins       WINS server
     * @param string  $tftp       TFTP server
     * @param string  $ntp        NTP server
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function add_subnet(
        $interface,
        $start,
        $end,
        $lease_time = self::DEFAULT_LEASETIME,
        $gateway = NULL,
        $dns_list = NULL,
        $wins = NULL,
        $tftp = NULL,
        $ntp = NULL
    )
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_ip_range($interface, $start, $end));
        Validation_Exception::is_valid($this->validate_lease_time($lease_time));
        Validation_Exception::is_valid($this->validate_gateway($gateway));

        if (! empty($wins))
            Validation_Exception::is_valid($this->validate_wins_server($wins));

        if (! empty($tftp))
            Validation_Exception::is_valid($this->validate_tftp_server($tftp));

        if (! empty($ntp))
            Validation_Exception::is_valid($this->validate_ntp_server($ntp));

        if (! empty($dns_list))
            Validation_Exception::is_valid($this->validate_dns_server_list($dns_list));

        if (! $this->is_loaded)
            $this->_load_config();

        $dns_array = array();
        $dns_line = '';

        if (count($dns_list) > 0) {
            // TODO: purges empty array elements.  Move to controller.
            foreach ($dns_list as $server) {
                if (!empty($server))
                    $dns_array[] = $server;
            }

            $dns_line = implode(",", $dns_array);
        }

        $ethinfo = new Iface($interface);

        $ip = $ethinfo->get_live_ip();
        $netmask = $ethinfo->get_live_netmask();
        $broadcast = Network_Utils::get_broadcast_address($ip, $netmask);

        if (isset($this->config['dhcp-range']['count']))
            $range_count = $this->config['dhcp-range']['count'];
        else
            $range_count = 1;

        if (isset($this->config['dhcp-option']['count']))
            $option_count = $this->config['dhcp-option']['count'];
        else
            $option_count = 1;

        if ($lease_time != self::CONSTANT_UNLIMITED_LEASE)
            $lease_time = $lease_time . "h";

        $this->config['dhcp-range']['line'][++$range_count] = "$interface,$start,$end,$lease_time";

        if ($netmask)
            $this->config['dhcp-option']['line'][++$option_count] = "$interface," . self::OPTION_SUBNET_MASK . ",$netmask";

        if ($gateway)
            $this->config['dhcp-option']['line'][++$option_count] = "$interface," . self::OPTION_GATEWAY . ",$gateway";

        if (! empty($dns_line))
            $this->config['dhcp-option']['line'][++$option_count] = "$interface," . self::OPTION_DNS . ",$dns_line";

        if ($broadcast)
            $this->config['dhcp-option']['line'][++$option_count] = "$interface," . self::OPTION_BROADCAST . ",$broadcast";

        if ($wins) {
            $this->config['dhcp-option']['line'][++$option_count] = "$interface," . self::OPTION_WINS . ",$wins";
            $this->config['dhcp-option']['line'][++$option_count] = "$interface," . self::OPTION_NETBIOS_NODE_TYPE . ",8";
        }

        if ($tftp)
            $this->config['dhcp-option']['line'][++$option_count] = "$interface," . self::OPTION_TFTP . ",\"$tftp\"";

        if ($ntp)
            $this->config['dhcp-option']['line'][++$option_count] = "$interface," . self::OPTION_NTP . ",$ntp";

        $this->_save_config();
    }

    /**
     * Deletes a lease from DHCP server.
     *
     * @param string $mac MAC address
     * @param string $ip  IP address
     *
     * @return void
     * @throws Engine_Exception
     */

    public function delete_lease($mac, $ip)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->_delete_static_lease($mac);
        $this->_delete_dynamic_lease($mac, $ip);
    }

    /**
     * Removes DHCP subnet from configuration file.
     *
     * @param string $interface network interface
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function delete_subnet($interface)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        foreach ($this->config as $key => $details) {
            foreach ($details['line'] as $lineno => $value) {
                if (preg_match("/^$interface,/", $value))
                    unset($this->config[$key]['line'][$lineno]);
            }
        }

        $this->_save_config();
    }

    /**
     * Determines if subnet already exists.
     *
     * @param string $interface network interface
     *
     * @return boolean TRUE if subnet exists
     * @throws Engine_Exception
     */

    public function subnet_exists($interface)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        if (isset($this->subnets[$interface]["start"]))
            return TRUE;
        else
            return FALSE;
    }

    /**
     * Returns authoritative status.
     *
     * @return boolean TRUE if authoritative
     * @throws Engine_Exception
     */

    public function get_authoritative_state()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        if (isset($this->config['dhcp-authoritative']['line'][1]))
            return TRUE;
        else
            return FALSE;
    }

    /**
     * Returns a list of network interfaces that can use DHCP.
     *
     * PPTP and other interfaces are not included in the list.
     *
     * @return array list of valid DHCP interfaces
     * @throws Engine_Exception
     */

    public function get_dhcp_interfaces()
    {
        clearos_profile(__METHOD__, __LINE__);

        $options['filter_ppp'] = TRUE;

        $interfaces = new Iface_Manager();

        $ethlist = $interfaces->get_interfaces($options);
        $validlist = array();

        foreach ($ethlist as $eth) {
            $ethinfo = new Iface($eth);

            $validlist[] = $eth;
        }

        return $validlist;
    }

    /**
     * Returns default domain name.
     *
     * @return string default domain name
     * @throws Engine_Exception
     */

    public function get_domain_name()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        if (isset($this->config['domain']['line'][1]))
            return $this->config['domain']['line'][1];
        else
            return "";
    }

    /**
     * Returns lease information.
     *
     * @param string $mac MAC address
     * @param string $ip  IP address
     *
     * @return array array containing lease data
     * @throws Engine_Exception
     */

    public function get_lease($mac, $ip)
    {
        clearos_profile(__METHOD__, __LINE__);

        $leases = $this->get_leases();

        foreach ($leases as $lease) {
            if (($lease['mac'] === $mac) && ($lease['ip'] === $ip))
                return $lease;
        }
    }

    /**
     * Returns merged list of active and static leases.
     *
     * Lease data is keyed by MAC address, but sorted by IP.
     *
     * @return array array containing lease data
     * @throws Engine_Exception
     */

    public function get_leases()
    {
        clearos_profile(__METHOD__, __LINE__);

        // Pull in MAC address database
        include clearos_app_base('dhcp') . '/deploy/mac_database.php';

        /**
         * The MAC/IP pair in the static leases (/etc/ethers) and active
         * leases (/var/lib/misc/dnsmasq.leases) could be different.  For
         * example, a machine may grab a dynamic lease at first but an
         * administrator could later add a static entry for future use.
         *
         * For this reason, the list of leases is keyed on the MAC/IP pairing.
         * There is a little trickery going on to handle the key.  First,
         * ip2long is used so that 192.168.1.20 comes before
         * 192.168.1.100.  In addition, the MAC address becomes a decimal,
         * e.g. 11:22:33:44:55:66 becomes 0.112233445566.  The unique keys
         * would look similar to 3232236157.112233445566.
         */

        $active = $this->_get_active_leases();
        $static = $this->_get_ethers_data();
        $leases = array();
        $ip_ndx = array();

        foreach ($static as $mac => $details) {
            $key = sprintf("%u.%s", ip2long($details['ip']), hexdec(preg_replace("/\:/", "", $mac)));
            $mac_prefix = strtoupper(substr($mac, 0, 8));

            $leases[$key]['vendor'] = isset($mac_database[$mac_prefix]) ? $mac_database[$mac_prefix] : '';
            $leases[$key]['mac'] = $mac;
            $leases[$key]['ip'] = $details['ip'];
            $leases[$key]['end'] = 0;
            $leases[$key]['is_active'] = TRUE;
            $leases[$key]['type'] = self::TYPE_STATIC;
            $leases[$key]['hostname'] = $details['hostname'];
        }

        foreach ($active as $mac => $details) {
            $key = sprintf("%u.%s", ip2long($details['ip']), hexdec(preg_replace("/\:/", "", $mac)));
            $mac_prefix = strtoupper(substr($mac, 0, 8));

            $leases[$key]['vendor'] = isset($mac_database[$mac_prefix]) ? $mac_database[$mac_prefix] : '';
            $leases[$key]['mac'] = $mac;
            $leases[$key]['ip'] = $details['ip'];
            $leases[$key]['end'] = $details['end'];
            $leases[$key]['is_active'] = TRUE;
            $leases[$key]['type'] = self::TYPE_DYNAMIC;
            $leases[$key]['hostname'] = $details['hostname'];
        }

        ksort($leases);

        return $leases;
    }

    /**
     * Returns static leases.
     *
     * Lease data is keyed by MAC address, but sorted by IP.
     *
     * @return array array containing lease data
     * @throws Engine_Exception
     */

    public function get_static_leases()
    {
        clearos_profile(__METHOD__, __LINE__);

        $leases = $this->get_leases();

        $statics = array();

        foreach ($leases as $lease => $details) {
            if ($details['type'] === self::TYPE_STATIC)
                $statics[$lease] = $details;
        }

        return $statics;
    }

    /**
     * Returns subnet information for a given interface.
     *
     * @param string $iface network interface
     *
     * @return array subnet information
     * @throws Engine_Exception
     */

    public function get_subnet($iface)
    {
        clearos_profile(__METHOD__, __LINE__);

        $subnets = $this->get_subnets();

        $subnet['interface'] = $iface;
        $subnet['network'] = isset($subnets[$iface]['network']) ? $subnets[$iface]['network'] : '';
        $subnet['gateway'] = isset($subnets[$iface]['gateway']) ? $subnets[$iface]['gateway'] : '';
        $subnet['start'] = isset($subnets[$iface]['start']) ? $subnets[$iface]['start'] : '';
        $subnet['end'] = isset($subnets[$iface]['end']) ? $subnets[$iface]['end'] : '';
        $subnet['dns'] = isset($subnets[$iface]['dns']) ? $subnets[$iface]['dns'] : '';
        $subnet['wins'] = isset($subnets[$iface]['wins']) ? $subnets[$iface]['wins'] : '';
        $subnet['tftp'] = isset($subnets[$iface]['tftp']) ? $subnets[$iface]['tftp'] : '';
        $subnet['ntp'] = isset($subnets[$iface]['ntp']) ? $subnets[$iface]['ntp'] : '';
        $subnet['lease_time'] = isset($subnets[$iface]['lease_time']) ? $subnets[$iface]['lease_time'] : '';

        return $subnet;
    }

    /**
     * Returns default subnet information for a given interface.
     *
     * This method will return default subnet information based on
     * the configured IP address.
     *
     * @param string $iface network interface
     *
     * @return array subnet information
     * @throws Engine_Exception
     */

    public function get_subnet_default($iface)
    {
        clearos_profile(__METHOD__, __LINE__);

        $ethinfo = new Iface($iface);
        $routes = new Routes();
        $network_obj = new Network();

        $ip = $ethinfo->get_live_ip();
        $netmask = $ethinfo->get_live_netmask();
        $network = Network_Utils::get_network_address($ip, $netmask);
        $broadcast = Network_Utils::get_broadcast_address($ip, $netmask);

        $mode = $network_obj->get_mode();
        $defroute = $routes->get_default();

        // Calculate some intelligent defaults
        //------------------------------------

        $long_nw = ip2long($network);
        $long_bc = ip2long($broadcast);

        if (($mode === Network::MODE_STANDALONE) || ($mode === Network::MODE_TRUSTED_STANDALONE))
            $subnet['gateway'] = $defroute;
        else
            $subnet['gateway'] = $ip;

        $subnet['network'] = $network;
        $subnet['start'] = long2ip($long_bc - round(($long_bc - $long_nw) * 3 / 5, 0) - 2);
        $subnet['end'] = long2ip($long_bc - 1);
        $subnet['lease_time'] = "24";
        $subnet['dns'] = array($ip);

        // TODO: add WINS and NTP check

        return $subnet;
    }

    /**
     * Returns list of declared subnets.
     *
     * @return array list of declared subnets 
     * @throws Engine_Exception
     */

    public function get_subnets()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        // Along with the configured subnets, we'll return information
        // on DHCP-liess interfaces

        $subnets = $this->subnets;
        $ethlist = $this->get_dhcp_interfaces();

        foreach ($ethlist as $eth) {
            if (!isset($this->subnets[$eth]["isconfigured"])) {
                try {
                    $ethinfo = new Iface($eth);
                    $ethip = $ethinfo->get_live_ip();

                    // Bail on interface if no IP exists
                    if (! $ethip)
                        continue;

                    $ethnetmask = $ethinfo->get_live_netmask();
                    $ethnetwork = Network_Utils::get_network_address($ethip, $ethnetmask);
                } catch (Engine_Exception $e) {
                    // Keep going
                }

                $subnets[$eth]["network"] = $ethnetwork;
                $subnets[$eth]["netmask"] = $ethnetmask;
                $subnets[$eth]["isvalid"] = TRUE;
                $subnets[$eth]["isconfigured"] = FALSE;
                $subnets[$eth]["start"] = "";
                $subnets[$eth]["end"] = "";
            }
        }

        return $subnets;
    }

    /**
     * Returns lease types.
     *
     * @return array lease types
     * @throws Engine_Exception
     */

    public function get_types()
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->types;
    }

    /**
     * Returns state of lease for given MAC address.
     *
     * @param string $mac MAC address
     *
     * @return array array containing lease data
     * @throws Engine_Exception
     */

    public function exists_lease($mac)
    {
        clearos_profile(__METHOD__, __LINE__);

        $leases = $this->get_leases();

        foreach ($leases as $lease) {
            if ($lease['mac'] === $mac)
                return TRUE;
        }

        return FALSE;
    }

    /**
     * Sets state of authoritative flag.
     *
     * @param boolean $state authoritative state
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_authoritative_state($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_authoritative_state($state));

        if (! $this->is_loaded)
            $this->_load_config();

        // Cleans out invalid duplicates
        if (isset($this->config['dhcp-authoritative']))
            unset($this->config['dhcp-authoritative']);

        if ($state)
            $this->config['dhcp-authoritative']['line'][1] = '';

        $this->_save_config();
    }

    /**
     * Sets global domain name.
     *
     * @param string $domain domain name
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_domain_name($domain)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_domain($domain));

        // Cleans out invalid duplicates
        if (! $this->is_loaded)
            $this->_load_config();

        if ($this->config['domain'])
            unset($this->config['domain']);

        $this->config['domain']['line'][1] = $domain;

        $this->_save_config();
    }

    /**
     * Updates subnet.
     *
     * @param string  $interface  network interface
     * @param string  $start      starting IP for DHCP range
     * @param string  $end        ending IP for DHCP range
     * @param integer $lease_time lease time in hours
     * @param string  $gateway    gateway IP address
     * @param array   $dns_list   DNS server list
     * @param string  $wins       WINS server
     * @param string  $tftp       TFTP server
     * @param string  $ntp        NTP server
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function update_subnet($interface, $start, $end, $lease_time = self::DEFAULT_LEASETIME, $gateway = NULL, $dns_list = NULL, $wins = NULL, $tftp = NULL, $ntp = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_ip_range($interface, $start, $end));
        Validation_Exception::is_valid($this->validate_lease_time($lease_time));
        Validation_Exception::is_valid($this->validate_gateway($gateway));

        if (! empty($wins))
            Validation_Exception::is_valid($this->validate_wins_server($wins));

        if (! empty($tftp))
            Validation_Exception::is_valid($this->validate_tftp_server($tftp));

        if (! empty($ntp))
            Validation_Exception::is_valid($this->validate_ntp_server($ntp));

        if (! empty($dns_list))
            Validation_Exception::is_valid($this->validate_dns_server_list($dns_list));
            
        if (! $this->is_loaded)
            $this->_load_config();

        if ($this->subnet_exists($interface))
            $this->delete_subnet($interface);

        $this->add_subnet($interface, $start, $end, $lease_time, $gateway, $dns_list, $wins, $tftp, $ntp);
    }

    /**
     * Updates an existing lease.
     *
     * @param string $mac  MAC address
     * @param string $ip   IP address
     * @param string $type lease type
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function update_lease($mac, $ip, $type)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_mac($mac));
        Validation_Exception::is_valid($this->validate_ip($ip));
        Validation_Exception::is_valid($this->validate_lease_type($type));

        $this->_delete_static_lease($mac);
        $this->_delete_dynamic_lease($mac, $ip);

        if ($type === self::TYPE_STATIC)
            $this->add_static_lease($mac, $ip);
        else
            $this->_add_dynamic_lease($mac, $ip);
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E  M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Adds a dynamiuc lease to DHCP server.
     *
     * @param string $mac MAC address
     * @param string $ip  IP address
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    protected function _add_dynamic_lease($mac, $ip)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_mac($mac));
        Validation_Exception::is_valid($this->validate_ip($ip));

        $file = new File(self::FILE_LEASES);

        if (! $file->exists())
            $file->create('root', 'root', '0644');

        $lease_time = time() + 3600;

        $file->add_lines($lease_time . ' ' . strtolower($mac) . ' ' . $ip . " * *\n");
    }

    /**
     * Deletes a dynamic lease from DHCP server.
     *
     * @param string $mac MAC address
     * @param string $ip  IP address
     *
     * @return void
     * @throws Engine_Exception
     */

    protected function _delete_dynamic_lease($mac, $ip)
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_LEASES);

        // TODO: we may need to match on the IP too
        if ($file->exists()) 
            $file->delete_lines("/[0-9]*\s+$mac\s+/i");
    }

    /**
     * Deletes a static lease from DHCP server.
     *
     * @param string $mac MAC address
     *
     * @return void
     * @throws Engine_Exception
     */

    protected function _delete_static_lease($mac)
    {
        clearos_profile(__METHOD__, __LINE__);

        $ethers = new Ethers();

        try {
            $ethers->delete_ether($mac);
        } catch (Ethers_Not_Found_Exception $e) {
            // Not fatal
        }
    }

    /**
     * Returns active leases.
     *
     * @access private
     * @return array array containing lease data
     * @throws Engine_Exception
     */

    protected function _get_active_leases()
    {
        clearos_profile(__METHOD__, __LINE__);

        $leases = array();
        $leasefile = new File(self::FILE_LEASES);

        if (! $leasefile->exists())
            return array();

        $leasedata = $leasefile->get_contents_as_array();

        foreach ($leasedata as $line) {
            if (empty($line))
                continue;

            $parts = preg_split('/[\s]+/', $line);

            $key = $parts[1];

            $leases[$key]['end'] = isset($parts[0]) ? $parts[0] : "";
            $leases[$key]['ip'] = isset($parts[2]) ? $parts[2] : "";
            $leases[$key]['hostname'] = isset($parts[3]) ? $parts[3] : "";
        }

        return $leases;
    }

    /**
     * Returns static leases from /etc/ethers.
     *
     * @access private
     * @return array lease data keyed by MAC and sorted by IP
     * @throws Engine_Exception
     */

    protected function _get_ethers_data()
    {
        clearos_profile(__METHOD__, __LINE__);

        $leases = array();
        $ip_ndx = array();

        if (! $this->is_loaded)
            $this->_load_config();

        // Bail if read-ethers feature is disabled
        if (! isset($this->config['read-ethers']))
            return array();

        $ethers = new Ethers();
        $mac_ip_pairs = $ethers->get_ethers();

        foreach ($mac_ip_pairs as $mac => $host_or_ip) {

            // Find a hostname for IP address entries
            // Find an IP for hostname entries

            if (Network_Utils::is_valid_ip($host_or_ip)) {
                $ip = $host_or_ip;
                $hostname = gethostbyaddr($host_or_ip);
                if ($hostname == $host_or_ip)    
                    $hostname = '';
            } else {
                $hostname = $host_or_ip;
                $ip = gethostbyname($host_or_ip);
                if ($ip == $host_or_ip)    
                    $ip = '';
            }

            // Keep an index to sort by IP

            $ip_long = sprintf("%u", ip2long($ip));
            $ip_ndx[$ip_long] = $mac;

            $leases[$mac]['ip'] = $ip;
            $leases[$mac]['hostname'] = $hostname;
        }

        ksort($ip_ndx);

        $sorted_leases = array();

        foreach ($ip_ndx as $ip => $mac) {
            $sorted_leases[$mac]['ip'] = $leases[$mac]['ip'];
            $sorted_leases[$mac]['hostname'] = $leases[$mac]['hostname'];
        }

        return $sorted_leases;
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validates authoritative state
     *
     * @param boolean $state authoritative state
     *
     * @return string error message if authoritative state is invalid
     */

    public function validate_authoritative_state($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! clearos_is_valid_boolean($state))
            return lang('dhcp_authoritative_state_invalid');
    }

    /**
     * Validates DHCP state
     *
     * @param boolean $state DHCP state
     *
     * @return string error message if DHCP state is invalid
     */

    public function validate_dhcp_state($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! clearos_is_valid_boolean($state))
            return lang('dhcp_state_invalid');
    }

    /**
     * Validates DNS server.
     *
     * @param string $dns DNS server
     *
     * @return string error message if DNS server is invalid
     */

    public function validate_dns_server($dns)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! Network_Utils::is_valid_ip($dns))
            return lang('dhcp_dns_server_invalid');
    }

    /**
     * Validates DNS server list.
     *
     * @param string $dns_list list of DNS servers
     *
     * @return string error message if DNS server list is invalid
     */

    public function validate_dns_server_list($dns_list)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! is_array($dns_list))
            return lang('dhcp_dns_server_list_invalid');

        foreach ($dns_list as $dns) {
           if (!empty($dns) && $this->validate_dns_server($dns))
                return lang('dhcp_dns_server_list_invalid');
        }
    }

    /**
     * Validates DHCP domain name.
     *
     * @param string $domain domain
     *
     * @return string error message if domain is invalid
     */

    public function validate_domain($domain)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! Network_Utils::is_valid_domain($domain))
            return lang('dhcp_domain_invalid');
    }

    /**
     * Validates network interface.
     *
     * @param string $interface network interface
     *
     * @return string error message if network interface is invalid
     */

    public function validate_interface($interface)
    {
        clearos_profile(__METHOD__, __LINE__);

        $iface = new Iface($interface);

        if (! $iface->is_valid())
            return lang('network_network_interface_invalid');
    }

    /**
     * Validates IP.
     *
     * @param string $ip IP address
     *
     * @return string error message if IP is invalid
     */

    public function validate_ip($ip)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! Network_Utils::is_valid_ip($ip))
            return lang('network_ip_invalid');
    }

    /**
     * Validates DHCP IP range.
     *
     * @param string $interface network interface
     * @param string $start     start IP
     * @param string $end       end IP
     *
     * @return string error message if IP range is invalid
     */

    public function validate_ip_range($interface, $start, $end)
    {
        clearos_profile(__METHOD__, __LINE__);

        // TODO: should we make sure range is valid for given interface?
        // Or, are there real world scenarios where out-of-range is used?

        if ($this->validate_interface($interface))
            return lang('network_network_interface_invalid');

        if (! Network_Utils::is_valid_ip_range($start, $end))
            return lang('dhcp_ip_range_invalid');
    }

    /**
     * Validates lease time.
     *
     * @param integer $time lease time
     *
     * @return string error message if lease time is invalid
     */

    public function validate_lease_time($time)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! (preg_match("/^\d+$/", $time) || ($time === self::CONSTANT_UNLIMITED_LEASE)))
            return lang('dhcp_lease_time_invalid');
    }

    /**
     * Validates lease type.
     *
     * @param string $type lease type
     *
     * @return string error message if lease type is invalid
     */

    public function validate_lease_type($type)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! array_key_exists($type, $this->types))
            return lang('dhcp_lease_type_invalid');
    }

    /**
     * Validates MAC address
     *
     * @param string  $mac              MAC address
     * @param boolean $check_uniqueness checks to see if MAC is unique
     *
     * @return string error message if MAC address is invalid
     */

    public function validate_mac($mac, $check_uniqueness = FALSE)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! Network_Utils::is_valid_mac($mac))
            return lang('network_mac_address_invalid');

        if ($check_uniqueness && $this->exists_lease($mac))
            return lang('network_mac_address_already_exists');
    }

    /**
     * Validates start IP in DHCP range.
     *
     * @param string $start start IP
     *
     * @return string error message if start IP is invalid
     */

    public function validate_start_ip($start)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! Network_Utils::is_valid_ip($start))
            return lang('dhcp_start_ip_invalid');
    }

    /**
     * Validates end IP in DHCP range.
     *
     * @param string $end end IP
     *
     * @return string error message if end IP is invalid
     */

    public function validate_end_ip($end)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! Network_Utils::is_valid_ip($end))
            return lang('dhcp_end_ip_invalid');
    }

    /**
     * Validates gateway server setting.
     *
     * @param string $gateway gateway server
     *
     * @return string error message if gateway is invalid
     */

    public function validate_gateway($gateway)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! Network_Utils::is_valid_ip($gateway))
            return lang('dhcp_gateway_invalid');
    }

    /**
     * Validates NTP server setting.
     *
     * @param string $ntp NTP server
     *
     * @return string error message if NTP server is invalid
     */

    public function validate_ntp_server($ntp)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! Network_Utils::is_valid_ip($ntp))
            return lang('dhcp_ntp_server_invalid');
    }

    /**
     * Validates TFTP server setting.
     *
     * @param string $tftp TFTP server
     *
     * @return string error message if TFTP server is invalid
     */

    public function validate_tftp_server($tftp)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! Network_Utils::is_valid_ip($tftp))
            return lang('dhcp_tftp_server_invalid');
    }

    /**
     * Validates a WINS server.
     *
     * @param string $wins WINS server
     *
     * @return string error message if WINS server is invalid
     */

    public function validate_wins_server($wins)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! Network_Utils::is_valid_ip($wins))
            return lang('dhcp_wins_server_invalid');
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E  M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Loads configuration file.
     *
     * The Dnsmasq configuration file can have multiple keys with the same
     * value.  For instance:
     *
     * - dhcp-option=eth1,44,192.168.2.16
     * - dhcp-option=eth1,1,255.255.255.0
     * - dhcp-option=eth1,28,192.168.2.255
     *
     * The config() array is in the following format to deal with this:
     * 
     * config[key][count]
     * config[key][line][line_index]
     *
     * In our example, this would look like:
     *
     * config['dhcp-option']['count'] = 3
     * config['dhcp-option']['line'][1] = eth1,44,192.168.2.16
     * config['dhcp-option']['line'][2] = eth1,1,255.255.255.0
     * config['dhcp-option']['line'][3] = eth1,28,192.168.2.255
     *
     * The $subnet array contains the following data structure:
     *
     * - $subnet[interface][netmask]
     * - $subnet[interface][start]
     * - $subnet[interface][end]
     * - $subnet[interface][lease_time]
     * - $subnet[interface][wins]
     * - $subnet[interface][broadcast]
     * - $subnet[interface][gateway]
     * - $subnet[interface][dns] (array)
     * - $subnet[interface][isconfigured]
     * - $subnet[interface][option][rfc_id]
     *
     * @access private
     * @return void
     * @throws Engine_Exception
     */

    protected function _load_config()
    {
        clearos_profile(__METHOD__, __LINE__);

        $lines = array();

        $dhcp_file = new File(self::FILE_DHCP);
        $dnsmasq_file = new File(self::FILE_CONFIG);

        $lines = $dnsmasq_file->get_contents_as_array();

        if ($dhcp_file->exists())
            $lines = array_merge($lines, $dhcp_file->get_contents_as_array());

        $matches = array();

        foreach ($lines as $line) {
            if (preg_match("/^#/", $line) || preg_match("/^\s*$/", $line)) {
                continue;
            } else if (preg_match("/(.*)=(.*)/", $line, $matches)) {
                $key = $matches[1];
                $value = $matches[2];
            } else {
                $key = trim($line);
                $value = "";
            }

            if (isset($this->config[$key]['count']))
                $this->config[$key]['count']++;
            else
                $this->config[$key]['count'] = 1;

            // $count is just to make code more readable
            $count = $this->config[$key]['count'];
            $this->config[$key]['line'][$count] = $value;
        }

        // Subnet information
        //-------------------

        if (isset($this->config['dhcp-range'])) {
            foreach ($this->config["dhcp-range"]["line"] as $line) {
                $items = preg_split("/,/", $line);
                $this->subnets[$items[0]]["start"] = $items[1];
                $this->subnets[$items[0]]["end"] = $items[2];
                $this->subnets[$items[0]]["lease_time"] = preg_replace("/[hsm]\s*$/", "", $items[3]);
                $this->subnets[$items[0]]["isconfigured"] = TRUE;
            }
        }

        if (isset($this->config["dhcp-option"])) {
            foreach ($this->config["dhcp-option"]["line"] as $line) {
                $items = preg_split("/,/", $line, 3);
                if ($items[1] == self::OPTION_SUBNET_MASK) {
                    $key = "netmask";
                    $value = $items[2];
                } else if ($items[1] == self::OPTION_GATEWAY) {
                    $key = "gateway";
                    $value = $items[2];
                } else if ($items[1] == self::OPTION_DNS) {
                    $key = "dns";
                    $value = explode(",", $items[2]);
                } else if ($items[1] == self::OPTION_BROADCAST) {
                    $key = "broadcast";
                    $value = $items[2];
                } else if ($items[1] == self::OPTION_WINS) {
                    $key = "wins";
                    $value = $items[2];
                } else if ($items[1] == self::OPTION_TFTP) {
                    $key = "tftp";
                    $value = preg_replace("/\"/", "", $items[2]);
                } else if ($items[1] == self::OPTION_NTP) {
                    $key = "ntp";
                    $value = $items[2];
                } else if (count($items) == 2) {
                    // Skip over dhcp-option tags without subnet definition
                    continue;
                } else {
                    $key = $items[1];
                    $value = $items[2];
                }

                $this->subnets[$items[0]][$key] = $value;
            }
        }

        /**
         * Calculates network setting.
         *
         * Check to see if configuration is valid for given interface.
         *
         * $subnet[interface][network]
         * $subnet[interface][isvalid]
         */

        foreach ($this->subnets as $eth => $subnetinfo) {
            if (isset($this->subnets[$eth]['start']) && isset($this->subnets[$eth]['netmask'])) {
                $configured_network = Network_Utils::get_network_address($this->subnets[$eth]['start'], $this->subnets[$eth]['netmask']);
                $this->subnets[$eth]['network'] = $configured_network;
            } else {
                $configured_network = "";
            }

            $this->subnets[$eth]["isvalid"] = FALSE;

            try {
                $ethinfo = new Iface($eth);
                $ethip = $ethinfo->get_live_ip();
                $ethnetmask = $ethinfo->get_live_netmask();
                $ethnetwork = Network_Utils::get_network_address($ethip, $ethnetmask);

                if ($ethnetwork == $configured_network)
                    $this->subnets[$eth]["isvalid"] = TRUE;
            } catch (Engine_Exception $e) {
                // Not fatal
            }
        }

        $this->is_loaded = TRUE;
    }

    /**
     * Save configuration changes.
     *
     * @access private
     * @return void
     * @throws Engine_Exception
     */

    protected function _save_config()
    {
        clearos_profile(__METHOD__, __LINE__);

        $dhcp_file = new File(self::FILE_DHCP);
        $dnsmasq_file = new File(self::FILE_CONFIG);

        // Go through the existing configuration files and save
        // any user-created comments.

        $dhcp_comments = array();
        $dnsmasq_comments = array();

        if ($dhcp_file->exists()) {
            $lines = $dhcp_file->get_contents_as_array();
            foreach ($lines as $line) {
                if (preg_match("/^#/", $line))
                    $dhcp_comments[] = $line;
            }
        }

        if ($dnsmasq_file->exists()) {
            $lines = $dnsmasq_file->get_contents_as_array();
            foreach ($lines as $line) {
                if (preg_match("/^#/", $line))
                    $dnsmasq_comments[] = $line;
            }
        }

        // The DHCP options in Dnsmasq go into a separate dhcp.conf file.  The
        // relevant parameters are as follows:
        //
        // - read-ethers
        // - dhcp-range
        // - dhcp-option
        //
        // All other options go into dnsmasq.conf
        //
        // Some keys in the configuration files do not have values, while others do:
        // - log-queries  (no value)
        // - domain=pointclark.net (value)

        $dhcp_lines = array();
        $dnsmasq_lines = array();

        // Always enable read-ethers for now
        $this->config['read-ethers']['line'][1] = "";

        foreach ($this->config as $key => $details) {
            foreach ($details['line'] as $lineno => $value) {
                if ($value)
                    $line = "$key=$value";
                else
                    $line = "$key";

                if (in_array($key, array('read-ethers', 'dhcp-range'))) {
                    $dhcp_lines[] = $line;
                } else if ($key == "dhcp-option") {
                    // The dhcp-option does not require a subnet definition.
                    // These items should go in the main dnsmasq_lines array.

                    $items = preg_split("/,/", $value, 3);

                    if (count($items) == 2)
                        $dnsmasq_lines[] = $line;
                    else
                        $dhcp_lines[] = $line;
                } else {
                    $dnsmasq_lines[] = $line;
                }
            }
        }

        // Append any user-created comments to the file.

        $dnsmasq_lines = array_merge($dnsmasq_lines, $dnsmasq_comments);
        $dhcp_lines = array_merge($dhcp_lines, $dhcp_comments);

        // Write out the files
        //--------------------

        if (! $dhcp_file->exists())
            $dhcp_file->create("root", "root", "0644");

        if (! $dnsmasq_file->exists())
            $dnsmasq_file->create("root", "root", "0644");

        sort($dnsmasq_lines);
        sort($dhcp_lines);

        $dhcp_file->dump_contents_from_array($dhcp_lines);
        $dnsmasq_file->dump_contents_from_array($dnsmasq_lines);

        // Reset our internal data structures
        //-----------------------------------

        $this->is_loaded = FALSE;
        $this->config = array();
        $this->subnets = array();
    }
}
