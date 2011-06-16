<?php

/**
 * Network interface class.
 *
 * @category    Apps
 * @package     Network
 * @subpackage  Libraries
 * @author      {@link http://www.clearfoundation.com/ ClearFoundation}
 * @copyright   Copyright 2002-2010 ClearFoundation
 * @license     http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link        http://www.clearfoundation.com/docs/developer/apps/network/
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
//
// Maintenance notes
// -----------------
//
// - The Red Hat network scripts have two tags that define the connection type
//   - BOOTPROTO: dhcp, bootp, dialup, static
//   - TYPE:      xDSL, <other>   (i.e. anything else will NOT be xDSL)
//              Though the "TYPE" tag is only used to signify PPPoE, it is
//              also used to store other network types (notably, "dialup"
//              and "wireless").
//
// - The "/sbin/iwconfig | /bin/grep ESSID" is not a great way to detect a
//   wireless interface... but that's the way we'll do it for now.
//
// - Before writing a new config, you must disable the interface.  Otherwise,
//   you won't be able to bring the interface down *after* a config change.
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// N A M E S P A C E
///////////////////////////////////////////////////////////////////////////////

namespace clearos\apps\network;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = isset($_ENV['CLEAROS_BOOTSTRAP']) ? $_ENV['CLEAROS_BOOTSTRAP'] : '/usr/clearos/framework/shared';
require_once($bootstrap . '/bootstrap.php');

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('base');
clearos_load_language('network');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

use \clearos\apps\base\Engine as Engine;
use \clearos\apps\base\File as File;
use \clearos\apps\base\Shell as Shell;
use \clearos\apps\base\Validation_Exception as Validation_Exception;
use \clearos\apps\network\Chap as Chap;
use \clearos\apps\network\Iface_Manager as Iface_Manager;
use \clearos\apps\network\Network_Utils as Network_Utils;

clearos_load_library('base/Engine');
clearos_load_library('base/File');
clearos_load_library('base/Shell');
clearos_load_library('base/Validation_Exception');
clearos_load_library('network/Chap');
clearos_load_library('network/Iface_Manager');
clearos_load_library('network/Network_Utils');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Network interface class.
 *
 * @category    Apps
 * @package     Network
 * @subpackage  Libraries
 * @author      {@link http://www.clearfoundation.com/ ClearFoundation}
 * @copyright   Copyright 2002-2010 ClearFoundation
 * @license     http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link        http://www.clearfoundation.com/docs/developer/apps/network/
 */

class Iface extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    // Commands
    const COMMAND_ETHTOOL = '/sbin/ethtool ';
    const COMMAND_IFCONFIG = '/sbin/ifconfig ';
    const COMMAND_IFDOWN = '/sbin/ifdown ';
    const COMMAND_IFUP = '/sbin/ifup ';
    const COMMAND_IWCONFIG = '/sbin/iwconfig';

    // Files and paths
    const FILE_LOG = '/var/log/messages';
    const PATH_SYSCONF = '/etc/sysconfig';

    // Boot protocols
    const BOOTPROTO_BOOTP = 'bootp';
    const BOOTPROTO_DHCP = 'dhcp';
    const BOOTPROTO_DIALUP = 'dialup';
    const BOOTPROTO_PPPOE = 'pppoe';
    const BOOTPROTO_STATIC = 'static';

    // Network types
    const TYPE_BONDED = 'Bonded';
    const TYPE_BONDED_SLAVE = 'BondedChild';
    const TYPE_BRIDGED = 'Bridge';
    const TYPE_BRIDGED_SLAVE = 'BridgeChild';
    const TYPE_ETHERNET = 'Ethernet';
    const TYPE_PPPOE = 'xDSL';
    const TYPE_UNKNOWN = 'Unknown';
    const TYPE_VIRTUAL = 'Virtual';
    const TYPE_VLAN = 'VLAN';
    const TYPE_WIRELESS = 'Wireless';

    // Flags
    const IFF_UP = 0x1;
    const IFF_BROADCAST = 0x2;
    const IFF_DEBUG = 0x4;
    const IFF_LOOPBACK = 0x8;
    const IFF_POINTOPOINT = 0x10;
    const IFF_NOTRAILERS = 0x20;
    const IFF_RUNNING = 0x40;
    const IFF_NOARP = 0x80;
    const IFF_PROMISC = 0x100;
    const IFF_ALLMULTI = 0x200;
    const IFF_MASTER = 0x400;
    const IFF_SLAVE = 0x800;
    const IFF_MULTICAST = 0x1000;
    const IFF_PORTSEL = 0x2000;
    const IFF_AUTOMEDIA = 0x4000;
    const IFF_DYNAMIC = 0x8000;
    const IFF_LOWER_UP = 0x10000;
    const IFF_DORMANT = 0x20000;

    /**
     * @var network interface name
     */

    protected $iface = NULL;

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Iface constructor.
     *
     * @param  string  $iface  the interface
     */

    public function __construct($iface = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->iface = $iface;
    }

    /**
     * Deletes interface configuration.
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function delete_config()
    {
        clearos_profile(__METHOD__, __LINE__);

//        Validation_Exception::is_valid($this->validate_interface($interface));

        // KLUDGE: more PPPoE crap

        $info = $this->get_interface_info();

        if (isset($info['ifcfg']['user'])) {
            $chap = new Chap();
            $chap->delete_secret($info['ifcfg']['user']);
        }

        if (isset($info['ifcfg']['eth'])) {
            $pppoedev = new Iface($info['ifcfg']['eth']);
            $pppoedev->delete_config();
        }

        $this->disable();

        sleep(2); // Give it a chance to disappear

        $file = new File(self::PATH_SYSCONF . '/network-scripts/ifcfg-' . $this->iface);

        if ($file->exists())
            $file->delete();
    }

    /**
     * Deletes virtual interface.
     *
     * @return void
     * @throws Engine_Exception
     */

    public function delete_virtual()
    {
        clearos_profile(__METHOD__, __LINE__);

        list($device, $metric) = split(':', $this->iface, 5);

        if (!strlen($metric))
            return;

        $shell = new Shell();
        $retval = $shell->execute(self::COMMAND_IFDOWN, $this->iface, TRUE);

        if ($retval != 0) {
            // Really force it down if ifdown fails.  Don't bother logging errors...
            $retval = $shell->execute(self::COMMAND_IFCONFIG, $this->iface . ' down', TRUE);
        }

        $file = new File(self::PATH_SYSCONF . '/network-scripts/ifcfg-' . $this->iface);

        if ($file->exists())
            $file->delete();
    }

    /**
     * Takes interface down.
     *
     * @param string $iface Interface name (optional)
     * @return  void
     * @throws Engine_Exception
     */

    public function disable($iface = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        if($iface != NULL) $this->iface = $iface;

        try {
            $shell = new Shell();
            $retval = $shell->execute(self::COMMAND_IFDOWN, $this->iface, TRUE);

            if ($retval != 0) {
                // Really force it down if ifdown fails.  Don't bother logging errors...
                $retval = $shell->execute(self::COMMAND_IFCONFIG, $this->iface . ' down', TRUE);
            }
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_WARNING);
        }
    }

    /**
     * Brings interface up.
     *
     * @param boolean $background perform enable in the background
     * @return void
     * @throws Engine_Exception
     */

    public function enable($background = FALSE)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $options = array();

            if ($background)
                    $options['background'] = TRUE;

            $shell = new Shell();
            $retval = $shell->execute(self::COMMAND_IFUP, $this->iface, TRUE, $options);

            if ($retval != 0)
                throw new Engine_Exception($shell->get_first_output_line(), COMMON_WARNING);
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_WARNING);
        }
    }

    /**
     * Returns the boot protocol of interface as a readable string for end users.
     *
     * @return string boot protocol of interface
     * @throws Engine_Exception
     */

    public function get_boot_protocol()
    {
        clearos_profile(__METHOD__, __LINE__);

        $bootproto = '';

        if ($this->is_configured()) {
            $info = $this->read_config();
            $bootproto = $info['bootproto'];

            // PPPOEKLUDGE - set the boot protocol on PPPoE interfaces
            if ($this->get_type() == self::TYPE_PPPOE)
                $bootproto = self::BOOTPROTO_PPPOE;
        }

        return $bootproto;
    }

    /**
     * Returns the boot protocol of interface as a readable string for end users.
     *
     * @return string boot protocol of interface
     * @throws Engine_Exception
     */

    public function get_boot_protocol_text()
    {
        clearos_profile(__METHOD__, __LINE__);

        $bootproto = $this->get_boot_protocol();
        $text = '';

        if ($bootproto == self::BOOTPROTO_DHCP)
            $text = lang('network_bootproto_dhcp');
        else if ($bootproto == self::BOOTPROTO_STATIC)
            $text = lang('network_bootproto_static');
        else if ($bootproto == self::BOOTPROTO_PPPOE)
            $text = lang('network_bootproto_pppoe');

        return $text;
    }

    /**
     * Returns interface information as an associative array.
     *
     * @return  array  interface information
     * @throws  Engine_Exception, Engine_Exception
     */

    public function get_interface_info()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_valid())
            throw new Engine_Exception(IFACE_LANG_ERRMSG_INVALID, COMMON_NOTICE);

        // Using ioctl(2) calls (from custom extension ifconfig.so).

        if (! extension_loaded('ifconfig')) {
            if (!@dl('ifconfig.so')) {
                throw new Engine_Exception(LOCALE_LANG_ERRMSG_WEIRD, CLEAROS_ERROR);
            }
        }

        $info = array();
        $handle = @ifconfig_init();
        $info['address'] = @ifconfig_address($handle, $this->iface);
        $info['netmask'] = @ifconfig_netmask($handle, $this->iface);
        $info['broadcast'] = @ifconfig_broadcast($handle, $this->iface);
        $info['hwaddress'] = @ifconfig_hwaddress($handle, $this->iface);
        $info['mtu'] = @ifconfig_mtu($handle, $this->iface);
        $info['metric'] = @ifconfig_metric($handle, $this->iface) + 1;
        $info['flags'] = @ifconfig_flags($handle, $this->iface);
        $info['debug'] = @ifconfig_debug($handle, $this->iface);

        // TODO: the existence of an IP address has always been used
        // to determine the "state" of the network interface.  This
        // policy should be changed and the $info['state'] should be
        // explicitly defined.

        // TODO II: on a DHCP connection, the interface can have an IP
        // (an old one) and be "up" during the DHCP lease renewal process
        // (even if it fails).  This should be added to the state flag?

        try {
            $info['link'] = $this->get_link_status();
        } catch (Exception $e) {
            // Keep going?
        }

        try {
            $info['speed'] = $this->get_speed();
        } catch (Exception $e) {
            // Keep going?
        }

        try {
            $info['type'] = $this->get_type();
            $info['typetext'] = $this->get_type_text();
        } catch (Exception $e) {
            // Keep going?
        }

        if (preg_match('/^[a-z]+\d+:/', $this->iface)) {
            $info['virtual'] = TRUE;

            $virtualnum = preg_replace('/[a-z]+\d+:/', '', $this->iface);

            if ($virtualnum >= Firewall::CONSTANT_ONE_TO_ONE_NAT_START)
                $info['one-to-one-nat'] = TRUE;
            else
                $info['one-to-one-nat'] = FALSE;
        } else {
            $info['virtual'] = FALSE;
            $info['one-to-one-nat'] = FALSE;
        }

        if ($this->is_configurable())
            $info['configurable'] = TRUE;
        else
            $info['configurable'] = FALSE;

        if ($this->is_configured()) {
            try {
                $info['configured'] = TRUE;
                $info['ifcfg'] = $this->read_config();
            } catch (Exception $e) {
                // Keep going?
            }
        } else {
            $info['configured'] = FALSE;
        }

        return $info;
    }

    /**
     * Returns the last connection status in the logs.
     *
     * @return string
     * @throws Engine_Exception
     */

    public function get_ip_connection_log()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $config = $this->read_config();
            $bootproto = $this->get_boot_protocol();
        
            if ($bootproto == self::BOOTPROTO_PPPOE) {

                $file = new File(self::FILE_LOG, TRUE);
                $results = $file->get_search_results(' (pppd|pppoe)\[\d+\]: ');

                for ($inx = count($results); $inx > (count($results) - 15); $inx--) {
                    if (preg_match('/Timeout waiting for/', $results[$inx]))
                        return IFACE_LANG_ERRMSG_NO_PPPOE_SERVER;
                    else if (preg_match('/LCP: timeout/', $results[$inx]))
                        return IFACE_LANG_ERRMSG_NO_PPPOE_SERVER;
                    else if (preg_match('/PAP authentication failed/', $results[$inx]))
                        return IFACE_LANG_ERRMSG_AUTHENTICATION_FAILED;
                }

            } else if ($bootproto == self::BOOTPROTO_DHCP) {

                $file = new File(self::FILE_LOG, TRUE);
                $results = $file->get_search_results('dhclient\[\d+\]: ');

                for ($inx = count($results); $inx > (count($results) - 10); $inx--) {
                    if (preg_match('/No DHCPOFFERS received/', $results[$inx]))
                        return IFACE_LANG_ERRMSG_NO_DHCP_SERVER;
                    else if (preg_match('/DHCPDISCOVER/', $results[$inx]))
                        return IFACE_LANG_ERRMSG_WAITING_FOR_DHCP;
                }
            }

        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_WARNING);
        }

        return '';
    }

    /**
     * Returns the link status.
     *
     * @return  int FALSE (0) if link is down, TRUE (1) if link present, -1 if not supported by driver.
     * @throws  Engine_Exception, Engine_Exception
     */

    public function get_link_status()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_valid())
            throw new Engine_Exception(IFACE_LANG_ERRMSG_INVALID . ' - ' . $this->iface, CLEAROS_ERROR);

        try {
            $type = $this->get_type();

            // Wireless interfaces always have link.
            // PPPOEKLUDGE -- get link status from underlying PPPoE interface.  Sigh.

            if ($type == self::TYPE_WIRELESS) {
                return 1;
            } else if ($type == self::TYPE_PPPOE) {
                $ifaceconfig = $this->read_config();
                $realiface = $ifaceconfig['eth'];
            } else {
                $realiface = $this->iface;
            }

            $shell = new Shell();
            $retval = $shell->execute(self::COMMAND_ETHTOOL, $realiface, TRUE);

            if ($retval != 0)
                return -1;

            $output = $shell->get_output();

            $match = array();
            
            for ($i = 0; $i < sizeof($output); $i++) {
                if (preg_match('/Link detected: ([A-z]*)/', $output[$i], $match)) {
                    $link = ($match[1] == 'yes') ? 1 : 0;
                    break;
                }
            }
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_WARNING);
        }

        return $link;
    }

    /**
     * @return  string  IP of interface
     * @throws  Engine_Exception, Engine_Exception
     */

    public function get_live_ip()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_valid())
            throw new Engine_Exception(IFACE_LANG_ERRMSG_INVALID, CLEAROS_ERROR);

        // Using ioctl(2) calls (from custom extension ifconfig.so).

        try {
            if (! extension_loaded('ifconfig')) {
                if (!@dl('ifconfig.so'))
                    throw new Engine_Exception(LOCALE_LANG_ERRMSG_WEIRD, COMMON_WARNING);
            }

            // This method is from: /var/webconfig/lib/ifconfig.so
            $handle = @ifconfig_init();
            $ip = @ifconfig_address($handle, $this->iface);
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_WARNING);
        }

        return $ip;
    }

    /**
     * Returns the MAC address.
     *
     * @return string MAC address
     * @throws Engine_Exception, Engine_Exception
     */

    public function get_live_mac()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_valid())
            throw new Engine_Exception(IFACE_LANG_ERRMSG_INVALID, CLEAROS_ERROR);

        try {
            // Using ioctl(2) calls (from custom extension ifconfig.so).

            if (! extension_loaded('ifconfig')) {
                if (!@dl('ifconfig.so'))
                    throw new Engine_Exception(LOCALE_LANG_ERRMSG_WEIRD, COMMON_WARNING);
            }

            // This method is from: /var/webconfig/lib/ifconfig.so
            $handle = @ifconfig_init();
            $mac = @ifconfig_hwaddress($handle, $this->iface);
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_WARNING);
        }

        return $mac;
    }

    /**
     * Returns the netmask.
     *
     * @return  string  netmask of interface
     * @throws  Engine_Exception, Engine_Exception
     */

    public function get_live_netmask()
    {
        // Using ioctl(2) calls (from custom extension ifconfig.so).

        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_valid())
            throw new Engine_Exception(IFACE_LANG_ERRMSG_INVALID, CLEAROS_ERROR);

        try {
            // Using ioctl(2) calls (from custom extension ifconfig.so).

            if (! extension_loaded('ifconfig')) {
                if (!@dl('ifconfig.so'))
                    throw new Engine_Exception(LOCALE_LANG_ERRMSG_WEIRD, COMMON_WARNING);
            }

            // This method is from: /var/webconfig/lib/ifconfig.so
            $handle = @ifconfig_init();
            $netmask = @ifconfig_netmask($handle, $this->iface);
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_WARNING);
        }

        return $netmask;
    }

    /**
     * Gets an interface's MTU.
     *
     * @return int mtu Interface MTU
     * @throws Engine_Exception
     */

    public function get_mtu()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! extension_loaded('ifconfig')) {
            if (!@dl('ifconfig.so'))
                throw new Engine_Exception(LOCALE_LANG_ERRMSG_WEIRD, COMMON_WARNING);
        }

        $handle = @ifconfig_init();

        try {
            $file = new File(self::PATH_SYSCONF . '/network-scripts/ifcfg-' . $this->iface);

            if (! $file->exists())
                return @ifconfig_mtu($handle, $this->iface);

            return preg_replace('/"/', '', $file->lookup_value('/^MTU\s*=\s*/'));
        } catch (File_No_Match_Exception $e) {
            return @ifconfig_mtu($handle, $this->iface);
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_WARNING);
        }
    }

    /**
     * Returns the interface speed.
     *
     * This method may not be supported in all network card drivers.
     *
     * @return  int  speed in megabits per second
     * @throws  Engine_Exception, Engine_Exception
     */

    public function get_speed()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_valid())
            throw new Engine_Exception(IFACE_LANG_ERRMSG_INVALID, CLEAROS_ERROR);

        $speed = -1;

        try {
            $type = $this->get_type();

            // Wireless interfaces
            //--------------------

            if ($type == self::TYPE_WIRELESS) {
                $shell = new Shell();
                $shell->execute(self::COMMAND_IWCONFIG, $this->iface, FALSE);
                $output = $shell->get_output();
                $matches = array();
                
                foreach ($output as $line) {
                    if (preg_match('/Bit Rate:\s*([0-9]*)/', $line, $matches)) {
                        $speed = $matches[1];
                        break;
                    }
                }

            // Non-wireless interfaces
            //------------------------

            } else {
                // PPPOEKLUDGE -- get speed from underlying PPPoE interface.  Sigh.
                if ($type == self::TYPE_PPPOE) {
                    $ifaceconfig = $this->read_config();
                    $realiface = $ifaceconfig['eth'];
                } else {
                    $realiface = $this->iface;
                }

                $shell = new Shell();
                $retval = $shell->execute(self::COMMAND_ETHTOOL, $realiface, TRUE);
                $output = $shell->get_output();
                $matches = array();

                foreach ($output as $line) {
                    if (preg_match('/^\s*Speed: ([0-9]*)/', $line, $matches)) {
                        $speed = $matches[1];
                        break;
                    }
                }
            }

        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_WARNING);
        }

        return $speed;
    }

    /**
     * Returns the type of interface.
     *
     * Return types:
     *  - TYPE_BONDED
     *  - TYPE_BONDED_SLAVE
     *  - TYPE_BRIDGE
     *  - TYPE_BRIDGE_SLAVE
     *  - TYPE_ETHERNET
     *  - TYPE_PPPOE
     *  - TYPE_VIRTUAL
     *  - TYPE_VLAN
     *  - TYPE_WIRELESS
     *  - TYPE_UNKOWN
     *
     * @return  string  type of interface
     * @throws  Engine_Exception
     */

    public function get_type()
    {
        clearos_profile(__METHOD__, __LINE__);

        $isconfigured = $this->is_configured();

        // Not configured?  We can still detect a wireless type
        //-----------------------------------------------------

        if (! $isconfigured) {
            try {
                $shell = new Shell();
                $shell->execute(self::COMMAND_IWCONFIG, $this->iface, FALSE);
                $output = $shell->get_output();
            } catch (Exception $e) {
                throw new Engine_Exception($e->GetMessage(), COMMON_WARNING);
            }

            foreach ($output as $line) {
                if (preg_match('/ESSID/', $line))
                    return self::TYPE_WIRELESS;
            }

            return self::TYPE_ETHERNET;
        }

        $netinfo = $this->read_config();

        // Trust the "type" in the configuration file (if available)
        //----------------------------------------------------------

        if (isset($netinfo['type']))
            return $netinfo['type'];

        // Next, use the interface name as the clue
        //-----------------------------------------

        if (isset($netinfo['device'])) {
            if (preg_match('/^br/', $netinfo['device']))
                return self::TYPE_BRIDGED;

            if (preg_match('/^bond/', $netinfo['device']))
                return self::TYPE_BONDED;
        }

        // Last clue -- unique parameters in the file
        //-------------------------------------------

        if (isset($netinfo['vlan']))
            return self::TYPE_VLAN;

        if (isset($netinfo['bridge']))
            return self::TYPE_BRIDGED_SLAVE;

        if (isset($netinfo['master']))
            return self::TYPE_BONDED_SLAVE;

        if (isset($netinfo['essid']))
            return self::TYPE_WIRELESS;

        return self::TYPE_ETHERNET;
    }

    /**
     * @deprecated
     * @see get_type_text
     */

    public function get_type_name()
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->get_type_text();
    }

    /**
     * Returns the type of interface as a readable string for end users.
     *
     * @return  string  type of interface
     * @throws  Engine_Exception
     */

    public function get_type_text()
    {
        clearos_profile(__METHOD__, __LINE__);

        $type = $this->get_type();

        if ($type == self::TYPE_BONDED)
            return IFACE_LANG_BONDED;
        else if ($type == self::TYPE_BONDED_SLAVE)
            return IFACE_LANG_BONDED_SLAVE;
        else if ($type == self::TYPE_BRIDGED)
            return IFACE_LANG_BRIDGED;
        else if ($type == self::TYPE_BRIDGED_SLAVE)
            return IFACE_LANG_BRIDGED_SLAVE;
        else if ($type == self::TYPE_ETHERNET)
            return IFACE_LANG_ETHERNET;
        else if ($type == self::TYPE_PPPOE)
            return IFACE_LANG_PPPOE;
        else if ($type == self::TYPE_VIRTUAL)
            return IFACE_LANG_VIRTUAL;
        else if ($type == self::TYPE_VLAN)
            return IFACE_LANG_VLAN;
        else if ($type == self::TYPE_WIRELESS)
            return IFACE_LANG_WIRELESS;
        else
            return IFACE_LANG_UNKNOWN;
    }

    /**
     * Sets MAC address.
     *
     * If MAC address is empty, the MAC address for live network interface is configured.
     *
     * @param string $mac MAC address
     * @return void
     * @throws Engine_Exception
     */

    public function set_mac($mac = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $file = new File(self::PATH_SYSCONF . '/network-scripts/ifcfg-' . $this->iface);

            if (! $file->exists())
                return;

            if (is_null($mac))
                $mac = $this->get_live_mac();

            try {
                $file->lookup_value('/^HWADDR\s*=\s*/');
                $file->replace_lines('/^HWADDR\s*=.*$/', "HWADDR=\"$mac\"", 1);
            } catch (File_No_Match_Exception $e) {
                $file->add_lines("HWADDR=\"$mac\"\n");
            }
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_WARNING);
        }
    }

    /**
     * Sets network MTU.
     *
     * @param int mtu Interface MTU
     * @return void
     * @throws Engine_Exception
     */

    public function set_mtu($mtu)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $file = new File(self::PATH_SYSCONF . '/network-scripts/ifcfg-' . $this->iface);

            if (! $file->exists())
                return;

            try {
                $file->lookup_value('/^MTU\s*=\s*/');
                $file->replace_lines('/^MTU\s*=.*$/', "MTU=\"$mtu\"", 1);
            } catch (File_No_Match_Exception $e) {
                $file->add_lines("MTU=\"$mtu\"\n");
            }
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_WARNING);
        }
    }

    /**
     * Reads interface configuration file.
     *
     * @return  array  network configuration settings
     * @throws  Engine_Exception
     */

    public function read_config()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $file = new File(self::PATH_SYSCONF . '/network-scripts/ifcfg-' . $this->iface);

            if (! $file->exists())
                return NULL;

            $lines = $file->get_contents_as_array();

            foreach ($lines as $line) {
                $line = preg_replace('/"/', '', $line);

                if (preg_match('/^\s*#/', $line) || !strlen($line))
                    continue;

                $line = preg_split('/=/', $line);

                $netinfo[strtolower($line[0])] = $line[1];
            }

            // Translate constants into English
            if (isset($netinfo['bootproto'])) {
                // PPPOEKLUDGE - "dialup" is used by PPPoE
                if ($netinfo['bootproto'] == self::BOOTPROTO_DIALUP)
                    $netinfo['bootproto'] = self::BOOTPROTO_PPPOE;

                if ($netinfo['bootproto'] == self::BOOTPROTO_STATIC)
                    $netinfo['bootprototext'] = lang('network_bootproto_static');
                else if ($netinfo['bootproto'] == self::BOOTPROTO_DHCP)
                    $netinfo['bootprototext'] = lang('network_bootproto_dhcp');
                else if ($netinfo['bootproto'] == self::BOOTPROTO_PPPOE)
                    $netinfo['bootprototext'] = lang('network_bootproto_pppoe');
                else if ($netinfo['bootproto'] == self::BOOTPROTO_BOOTP)
                    $netinfo['bootprototext'] = lang('network_bootproto_bootp');
                else 
                    $netinfo['bootprototext'] = lang('network_bootproto_static');
            }

            return $netinfo;

        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_WARNING);
        }
    }

    /**
     * Writes interface configuration file.
     *
     * @return  boolean TRUE if write succeeds
     * @throws  Engine_Exception
     */

    public function write_config($netinfo)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $file = new File(self::PATH_SYSCONF . '/network-scripts/ifcfg-' . $this->iface);

            if ($file->exists())
                $file->delete();

            $file->Create('root', 'root', '0600');

            foreach($netinfo as $key => $value) {
                // The underlying network scripts do not like quotes on DEVICE
                if ($key == 'DEVICE')
                    $file->add_lines(strtoupper($key) . '=' . $value . "\n");
                else
                    $file->add_lines(strtoupper($key) . '="' . $value . "\"\n");
            }

            return TRUE;
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_WARNING);
        }
    }


    /**
     * Creates a PPPoE configuration.
     *
     * @param  string  $eth  ethernet interface to use
     * @param  string  $username  username
     * @param  string  $password  password
     * @param  integer  $mtu  MTU
     * @returns string New/current PPPoE interface name
     * @throws  Engine_Exception
     */

    public function save_pppoe_config($eth, $username, $password, $mtu = NULL, $peerdns = TRUE)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            // PPPoE hacking... again.
            // Before saving over an existing configuration, grab
            // the current configuration and delete the associated
            // password from chap/pap secrets.

            $chap = new Chap();
            $oldiface = new Iface($eth);
            $oldinfo = $oldiface->get_interface_info();

            if (isset($oldinfo['ifcfg']['user']))
                $chap->delete_secret($oldinfo['ifcfg']['user']);

            $physdev = $eth;
            if (substr($eth, 0, 3) == 'ppp') {
                $pppoe = new Iface($eth);
                $ifcfg = $pppoe->get_interface_info();
                $physdev = $ifcfg['ifcfg']['eth'];
            } else {
                for ($i = 0; $i < 64; $i++) {
                    $pppoe = new Iface('ppp' . $i);
                    if (! $pppoe->is_configured()) {
                        $eth = 'ppp' . $i;
                        break;
                    }
                }
            }

            // Blank out the ethernet interface used for PPPoE
            //------------------------------------------------

            $ethernet = new Iface($physdev);
            $liveinfo = $ethernet->get_interface_info();

            $ethinfo = array();
            $ethinfo['DEVICE'] = $physdev;
            $ethinfo['BOOTPROTO'] = 'none';
            $ethinfo['ONBOOT'] = 'no';
            $ethinfo['HWADDR'] = $liveinfo['hwaddress'];

            $ethernet->disable(); // See maintenance note
            $ethernet->write_config($ethinfo);

            // Write PPPoE config
            //-------------------

            $info = array();
            $info['DEVICE'] = $eth;
            $info['TYPE'] = self::TYPE_PPPOE;
            $info['USERCTL'] = 'no';
            $info['BOOTPROTO'] = 'dialup';
            $info['NAME'] = 'DSL' . $eth;
            $info['ONBOOT'] = 'yes';
            $info['PIDFILE'] = '/var/run/pppoe-' . $eth . '.pid';
            $info['FIREWALL'] = 'NONE';
            $info['PING'] = '.';
            $info['PPPOE_TIMEOUT'] = '80';
            $info['LCP_FAILURE'] = '5';
            $info['LCP_INTERVAL'] = '20';
            $info['CLAMPMSS'] = '1412';
            $info['CONNECT_POLL'] = '6';
            $info['CONNECT_TIMEOUT'] = '80';
            $info['DEFROUTE'] = 'yes';
            $info['SYNCHRONOUS'] = 'no';
            $info['ETH'] = $physdev;
            $info['PROVIDER'] = 'DSL' . $eth;
            $info['USER'] = $username;

            if (!$peerdns)
                $info['PEERDNS'] = 'no';

            if (!empty($mtu))
                $info['MTU'] = $mtu;

            $pppoe = new Iface($eth);
            $pppoe->disable(); // See maintenance note
            $pppoe->write_config($info);

            // Add password to chap-secrets
            //-----------------------------

            $chap->AddUser($username, $password);

            return $eth;
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_WARNING);
        }
    }


    /**
     * Creates a standard ethernet configuration.
     *
     * @param  string  $isdhcp  set to TRUE if DHCP
     * @param  boolean $peerdns set to TRUE if you want to use the DHCP peer DNS settings
     * @param  string  $ip  IP address (for static only)
     * @param  string  $netmask  netmask (for static only)
     * @param  string  $gateway  gate (for static only)
     * @param  string  $hostname optional DHCP hostname (for DHCP only)
     * @param  boolean $gateway_required flag if gateway setting is required
     * @returns void
     * @throws  Engine_Exception
     */

    public function save_ethernet_config($isdhcp, $ip, $netmask, $gateway, $hostname, $peerdns, $gateway_required = FALSE)
    {
        clearos_profile(__METHOD__, __LINE__);

        $isvalid = TRUE;
        $network = new Network_Utils();

        if (! $isdhcp) {
            if (! $network->is_validIp($ip)) {
                $this->AddValidationError(NETWORK_LANG_IP . ' - ' . LOCALE_LANG_INVALID, __METHOD__, __LINE__);
                $isvalid = FALSE;
            }

            if (! $network->is_validNetmask($netmask)) {
                $this->AddValidationError(NETWORK_LANG_NETMASK . ' - ' . LOCALE_LANG_INVALID, __METHOD__, __LINE__);
                $isvalid = FALSE;
            }

            if ($gateway) {
                if (! $network->is_validIp($gateway)) {
                    $this->AddValidationError(NETWORK_LANG_GATEWAY . ' - ' . LOCALE_LANG_INVALID, __METHOD__, __LINE__);
                    $isvalid = FALSE;
                }
            } else {
                if ($gateway_required) {
                    $this->AddValidationError(NETWORK_LANG_GATEWAY . ' - ' . LOCALE_LANG_MISSING, __METHOD__, __LINE__);
                        $isvalid = FALSE;
                    }
                }
            }

        if (! $isvalid)
            throw new ValidationException(LOCALE_LANG_INVALID);

        try {
            $liveinfo = $this->get_interface_info();
            $hwaddress = $liveinfo['hwaddress'];

            $this->disable(); // See maintenance note

            $info = array();
            $info['DEVICE'] = $this->iface;
            $info['TYPE'] = self::TYPE_ETHERNET;
            $info['ONBOOT'] = 'yes';
            $info['USERCTL'] = 'no';
            $info['HWADDR'] = $hwaddress;

            if ($isdhcp) {
                $info['BOOTPROTO'] = 'dhcp';
                if (strlen($hostname))
                    $info['DHCP_HOSTNAME'] = $hostname;
                $info['PEERDNS'] = ($peerdns) ? 'yes' : 'no';
            } else {
                $info['BOOTPROTO'] = 'static';
                $info['IPADDR'] = $ip;
                $info['NETMASK'] = $netmask;

                if ($gateway)
                    $info['GATEWAY'] = $gateway;
            }

            $this->write_config($info);

        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_WARNING);
        }
    }

    /**
     * Creates a virtual ethernet configuration.
     *
     * @param  string  $ip  IP address
     * @param  string  $ip  IP address
     * @returns  string  name of virtual interface
     * @throws  Engine_Exception, Engine_Exception
     */

    public function save_virtual_config($ip, $netmask)
    {
        clearos_profile(__METHOD__, __LINE__);

        $isvalid = TRUE;
        $network = new Network_Utils();

        if (! $network->is_validIp($ip)) {
            $this->AddValidationError(NETWORK_LANG_IP . ' - ' . LOCALE_LANG_INVALID, __METHOD__, __LINE__);
            $isvalid = FALSE;
        }

        if (! $network->is_validNetmask($netmask)) {
            $this->AddValidationError(NETWORK_LANG_NETMASK . ' - ' . LOCALE_LANG_INVALID, __METHOD__, __LINE__);
            $isvalid = FALSE;
        }

        if (! $isvalid)
            throw new ValidationException(LOCALE_LANG_INVALID);

        try {
            list($device, $metric) = split('\:', $this->iface, 5);

            if (! strlen($metric)) {
                // Find next free virtual metric

                for ($metric = 0; $metric < 1024; $metric++) {
                    if (! file_exists(self::PATH_SYSCONF .  '/network-scripts/ifcfg-' . $this->iface . ':' . $metric))
                        break;
                }

                // Rename interface
                $this->iface = $this->iface . ':' . $metric;
            }

            $this->disable(); // See maintenance note

            $info = array();
            $info['DEVICE'] = $this->iface;
            $info['TYPE'] = self::TYPE_VIRTUAL;
            $info['ONBOOT'] = 'yes';
            $info['USERCTL'] = 'no';
            $info['BOOTPROTO'] = 'static';
            $info['NO_ALIASROUTING'] = 'yes';
            $info['IPADDR'] = $ip;
            $info['NETMASK'] = $netmask;
            $this->write_config($info);

            return $this->iface;
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_WARNING);
        }
    }

    /**
     * Create a wireless network configuration.
     *
     * @param  string  $isdhcp  set to TRUE if DHCP
     * @param  string  $ip  IP address (for static only)
     * @param  string  $netmask  netmask (for static only)
     * @param  string  $gateway  gateway (for static only)
     * @param  string  $essid  ESSID
     * @param  string  $channel  channel
     * @param  string  $mode  mode
     * @param  string  $key  key
     * @param  string  $rate  rate
     * @param  boolean $peerdns set to TRUE if you want to use the DHCP peer DNS settings
     * @returns void
     * @throws  Engine_Exception, Engine_Exception
     */

    public function save_wireless_config($isdhcp, $ip, $netmask, $gateway, $essid, $channel, $mode, $key, $rate, $peerdns)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            if (!$isdhcp && (! $this->is_validIp($ip))) {
                $errors = $this->GetValidationErrors();
                throw new Engine_Exception($errors[0], CLEAROS_ERROR);
            }

            $this->disable(); // See maintenance note

            $info = array();
            $info['DEVICE'] = $this->iface;
            $info['TYPE'] = self::TYPE_WIRELESS;
            $info['ONBOOT'] = 'yes';
            $info['USERCTL'] = 'no';
            $info['ESSID'] = $essid;
            $info['CHANNEL'] = $channel;
            $info['MODE'] = $mode;
            $info['KEY'] = $key;
            $info['RATE'] = $rate;

            if ($isdhcp) {
                $info['BOOTPROTO'] = 'dhcp';
                $info['PEERDNS'] = ($peerdns) ? 'yes' : 'no';
            } else {
                $info['BOOTPROTO'] = 'static';
                $info['IPADDR'] = $ip;
                $info['NETMASK'] = $netmask;

                if ($gateway)
                    $info['GATEWAY'] = $gateway;
            }

            $this->write_config($info);
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_WARNING);
        }
    }

    /**
     * Checks to see if interface has an associated configuration file.
     *
     * @return  boolean TRUE if configuration file exists
     * @throws  Engine_Exception
     */

    public function is_configured()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $file = new File(self::PATH_SYSCONF . '/network-scripts/ifcfg-' . $this->iface);

            if ($file->exists())
                return TRUE;
            else
                return FALSE;
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_WARNING);
        }
    }

    /**
     * Checks to see if interface name is available on the system.
     *
     * @return  boolean TRUE if interface is valid
     * @throws  Engine_Exception
     */

    public function is_valid()
    {
        clearos_profile(__METHOD__, __LINE__);

        $iface_manager = new Iface_Manager();
        $interfaces = $iface_manager->get_interfaces(FALSE, FALSE);

        foreach ($interfaces as $iface) {
            if (! strcasecmp($this->iface, $iface))
                return TRUE;
        }

        return FALSE;
    }


    /**
     * Returns state of interface.
     *
     * @return  boolean TRUE if active
     * @throws  Engine_Exception
     */

    public function is_active()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_valid())
            throw new Engine_Exception(IFACE_LANG_ERRMSG_INVALID, CLEAROS_ERROR);

        $shell = new Shell();
        $shell->execute(self::COMMAND_IFCONFIG, $this->iface, TRUE);

        $output = $shell->get_output();

        foreach ($output as $line) {
            if (preg_match('/^' .$this->iface . '/', $line))
                return TRUE;
        }

        return FALSE;
    }


    /**
     * Returns the configurability of interface.
     *
     * Dynamic interfaces (e.g. an incoming pppX interface from PPTP VPN)
     * are not configurable.
     *
     * @return  boolean TRUE if configurable
     */

    public function is_configurable()
    {
        clearos_profile(__METHOD__, __LINE__);

        // PPPoE interfaces are configurable, bug only if they already configured.

        if (
            preg_match('/^eth/', $this->iface) ||
            preg_match('/^wlan/', $this->iface) ||
            preg_match('/^ath/', $this->iface) ||
            preg_match('/^br/', $this->iface) ||
            preg_match('/^bond/', $this->iface) ||
            (preg_match('/^ppp/', $this->iface) && $this->is_configured())
            ) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////
}
