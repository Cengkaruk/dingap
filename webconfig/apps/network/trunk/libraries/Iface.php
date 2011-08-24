<?php

/**
 * Network interface class.
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
use \clearos\apps\base\File as File;
use \clearos\apps\base\Shell as Shell;
use \clearos\apps\network\Chap as Chap;
use \clearos\apps\network\Iface as Iface;
use \clearos\apps\network\Iface_Manager as Iface_Manager;
use \clearos\apps\network\Network_Utils as Network_Utils;

clearos_load_library('base/Engine');
clearos_load_library('base/File');
clearos_load_library('base/Shell');
clearos_load_library('network/Chap');
clearos_load_library('network/Iface');
clearos_load_library('network/Iface_Manager');
clearos_load_library('network/Network_Utils');

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\File_No_Match_Exception as File_No_Match_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('base/File_No_Match_Exception');
clearos_load_library('base/Validation_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Network interface class.
 *
 * @category   Apps
 * @package    Network
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2002-2011 ClearFoundation
 *role @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network/
 */

class Iface extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    // Misc
    const CONSTANT_ONE_TO_ONE_NAT_START = 200;

    // Commands
    const COMMAND_ETHTOOL = '/sbin/ethtool';
    const COMMAND_IFCONFIG = '/sbin/ifconfig';
    const COMMAND_IFDOWN = '/sbin/ifdown';
    const COMMAND_IFUP = '/sbin/ifup';
    const COMMAND_IWCONFIG = '/sbin/iwconfig';

    // Files and paths
    const FILE_LOG = '/var/log/messages';
    const FILE_PCI_ID = '/usr/share/hwdata/pci.ids';
    const FILE_USB_ID = '/usr/share/hwdata/usb.ids';
    const PATH_SYS_CLASS_NET = '/sys/class/net';
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

    protected $iface = NULL;

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Iface constructor.
     *
     * @param string $iface interface
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

        Validation_Exception::is_valid($this->validate_interface($this->iface));

        // KLUDGE: more PPPoE crap

        $info = $this->get_info();

        if (isset($info['ifcfg']['user'])) {
            $chap = new Chap();
            $chap->delete_secret($info['ifcfg']['user']);
        }

        if (isset($info['ifcfg']['eth'])) {
            $pppoedev = new Iface($info['ifcfg']['eth']);
            $pppoedev->delete_config();
        }

        try {
            $this->disable();
        } catch (Engine_Exception $e) {
            // Not fatal
        }

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

        list($device, $metric) = preg_split('/:/', $this->iface, 5);

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
     *
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
     *
     * @return void
     * @throws Engine_Exception
     */

    public function enable($background = FALSE)
    {
        clearos_profile(__METHOD__, __LINE__);

        $options = array();

        if ($background)
            $options['background'] = TRUE;

        $shell = new Shell();
        $retval = $shell->execute(self::COMMAND_IFUP, $this->iface, TRUE, $options);
    }

    /**
     * Returns the boot protocol of interface in user-friendly text.
     *
     * @return string boot protocol of interface
     * @throws Engine_Exception
     */

    public function get_boot_protocol()
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_interface($this->iface));

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

        Validation_Exception::is_valid($this->validate_interface($this->iface));

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

    public function get_info()
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_interface($this->iface));

        // Using ioctl(2) calls (from custom extension ifconfig.so).

        if (! extension_loaded('ifconfig')) {
            if (!@dl('ifconfig.so'))
                throw new Engine_Exception(lang('base_something_weird_happened'));
        }

        $handle = @ifconfig_init();

        $info = array();

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
            $info['type_text'] = $this->get_type_text();
        } catch (Exception $e) {
            // Keep going?
        }

        // Vendor info
        //------------

        try {
            $vendor_stuff = $this->get_vendor_info();
            $info = array_merge($info, $vendor_stuff);
        } catch (Exception $e) {
            // Keep going?
        }

        // Role info
        //----------

        $role = new Role();
        $info['role'] = $role->get_interface_role($this->iface);
        $info['role_text'] = $role->get_interface_role_text($this->iface);

        // Other info
        //-----------

        if (preg_match('/^[a-z]+\d+:/', $this->iface)) {
            $info['virtual'] = TRUE;

            $virtualnum = preg_replace('/[a-z]+\d+:/', '', $this->iface);

            if ($virtualnum >= self::CONSTANT_ONE_TO_ONE_NAT_START)
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

        Validation_Exception::is_valid($this->validate_interface($this->iface));

        $config = $this->read_config();
        $bootproto = $this->get_boot_protocol();
    
        if ($bootproto == self::BOOTPROTO_PPPOE) {

            $file = new File(self::FILE_LOG, TRUE);
            $results = $file->get_search_results(' (pppd|pppoe)\[\d+\]: ');
            $last_lines = (count($results) < 15) ? count($results) : 15;

            for ($inx = count($results); $inx > (count($results) - $last_lines); $inx--) {
                if (preg_match('/Timeout waiting for/', $results[$inx]))
                    return lang('network_no_pppoe_server_found');
                else if (preg_match('/LCP: timeout/', $results[$inx]))
                    return lang('network_no_pppoe_server_found');
                else if (preg_match('/PAP authentication failed/', $results[$inx]))
                    return lang('network_pppoe_authentication_failed');
            }

        } else if ($bootproto == self::BOOTPROTO_DHCP) {

            $file = new File(self::FILE_LOG, TRUE);
            $results = $file->get_search_results('dhclient\[\d+\]: ');
            $last_lines = (count($results) < 10) ? count($results) : 10;

            for ($inx = count($results); $inx > (count($results) - $last_lines); $inx--) {
                if (preg_match('/No DHCPOFFERS received/', $results[$inx]))
                    return lang('network_no_dhcp_server_found');
                else if (preg_match('/DHCPDISCOVER/', $results[$inx]))
                    return lang('network_too_long_waiting_for_dhcp');
            }
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

        Validation_Exception::is_valid($this->validate_interface($this->iface));

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

        return $link;
    }

    /**
     * Returns the live IP address of the interface.
     *
     * @return string IP of interface
     * @throws Engine_Exception, Engine_Exception
     */

    public function get_live_ip()
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_interface($this->iface));

        // Using ioctl(2) calls (from custom extension ifconfig.so).

        if (! extension_loaded('ifconfig')) {
            if (!@dl('ifconfig.so'))
                throw new Engine_Exception(lang('base_something_weird_happened'));
        }

        $handle = @ifconfig_init();
        $ip = @ifconfig_address($handle, $this->iface);

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

        Validation_Exception::is_valid($this->validate_interface($this->iface));

        // Using ioctl(2) calls (from custom extension ifconfig.so).

        if (! extension_loaded('ifconfig')) {
            if (!@dl('ifconfig.so'))
                throw new Engine_Exception(lang('base_something_weird_happened'));
        }

        $handle = @ifconfig_init();
        $mac = @ifconfig_hwaddress($handle, $this->iface);

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
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_interface($this->iface));

        // Using ioctl(2) calls (from custom extension ifconfig.so).
        if (! extension_loaded('ifconfig')) {
            if (!@dl('ifconfig.so'))
                throw new Engine_Exception(lang('base_something_weird_happened'));
        }

        // This method is from: /var/webconfig/lib/ifconfig.so
        $handle = @ifconfig_init();
        $netmask = @ifconfig_netmask($handle, $this->iface);

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

        Validation_Exception::is_valid($this->validate_interface($this->iface));

        if (! extension_loaded('ifconfig')) {
            if (!@dl('ifconfig.so'))
                throw new Engine_Exception(lang('base_something_weird_happened'));
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

        Validation_Exception::is_valid($this->validate_interface($this->iface));

        $speed = -1;

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

        return $speed;
    }

    /**
     * Returns supported bootprotos for the interface.
     *
     * @return array supported bootprotos
     * @throws Engine_Exception
     */

    public function get_supported_bootprotos()
    {
        clearos_profile(__METHOD__, __LINE__);

        return array(
            self::BOOTPROTO_DHCP => lang('network_bootproto_dhcp'),
            self::BOOTPROTO_STATIC => lang('network_bootproto_static'),
            self::BOOTPROTO_PPPOE => lang('network_bootproto_pppoe'),
        );
    }

    /**
     * Returns supported roles for the interface.
     *
     * @return array supported roles
     * @throws Engine_Exception
     */

    public function get_supported_roles()
    {
        clearos_profile(__METHOD__, __LINE__);

        $role = new Role();

        return $role->get_interface_roles($this->iface);
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
     * @return string  type of interface
     * @throws Engine_Exception
     */

    public function get_type()
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_interface($this->iface));

        $isconfigured = $this->is_configured();

        // Not configured?  We can still detect a wireless type
        //-----------------------------------------------------

        if (! $isconfigured) {
            $shell = new Shell();
            $shell->execute(self::COMMAND_IWCONFIG, $this->iface, FALSE);
            $output = $shell->get_output();

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
     * Returns type text.
     *
     * @deprecated
     * @see get_type_text
     * @return string type text
     */

    public function get_type_name()
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_interface($this->iface));

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

        Validation_Exception::is_valid($this->validate_interface($this->iface));

        $type = $this->get_type();

        if ($type == self::TYPE_BONDED)
            return lang('network_type_bonded');
        else if ($type == self::TYPE_BONDED_SLAVE)
            return lang('network_type_bonded_slave');
        else if ($type == self::TYPE_BRIDGED)
            return lang('network_type_bridged');
        else if ($type == self::TYPE_BRIDGED_SLAVE)
            return lang('network_type_bridged_slave');
        else if ($type == self::TYPE_ETHERNET)
            return lang('network_type_ethernet');
        else if ($type == self::TYPE_PPPOE)
            return lang('network_type_pppoe');
        else if ($type == self::TYPE_VIRTUAL)
            return lang('network_type_virtual');
        else if ($type == self::TYPE_VLAN)
            return lang('network_type_vlan');
        else if ($type == self::TYPE_WIRELESS)
            return lang('network_type_wireless');
        else
            return lang('network_type_unknown');
    }

    /**
     * Returns vendor information.
     *
     * TODO: This method uses fopen/fread/fgets directly rather than the file class
     * for performance reasons.  We don't need super-user access to gather interface
     * details.
     *
     * @return array vendor information
     * @throws Engine_Exception
     */

    public function get_vendor_info()
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_interface($this->iface));

        $details = array();
        $details['vendor'] = NULL;
        $details['device'] = NULL;
        $details['sub_device'] = NULL;
        $details['bus'] = NULL;

        $id_vendor = 0;
        $id_device = 0;
        $id_sub_vendor = 0;
        $id_sub_device = 0;

        $device_link = self::PATH_SYS_CLASS_NET . '/' . $this->iface . '/device';

        if (!file_exists($device_link))
            return array();

        // Determine if this is a USB device
        $is_usb = FALSE;

        if (!($path = readlink($device_link)))
            return '';

        if (strstr($path, 'usb'))
            $is_usb = TRUE;

        // Obtain vendor ID number
        $path = $device_link . (($is_usb) ? '/../idVendor' : '/vendor');

        if (!($fh = fopen($path, 'r')))
            return '';

        fscanf($fh, '%x', $id_vendor);
        fclose($fh);

        if ($id_vendor == 0)
            return '';

        // Obtain device ID number
        $path = $device_link . (($is_usb) ? '/../idProduct' : '/device');

        if (!($fh = fopen($path, "r")))
            return '';

        fscanf($fh, '%x', $id_device);
        fclose($fh);

        if ($id_device == 0)
            return '';

        if (!$is_usb) {
            // Obtain (optional) sub-vendor ID number (PCI devices only)
            if (($fh = fopen("$device_link/subsystem_vendor", 'r'))) {
                fscanf($fh, '%x', $id_sub_vendor);
                fclose($fh);

                if ($id_sub_vendor == 0)
                    return '';
            }

            // Obtain (optional) sub-device ID number (PCI devices only)
            if (($fh = fopen("$device_link/subsystem_device", 'r'))) {
                fscanf($fh, '%x', $id_sub_device);
                fclose($fh);

                if ($id_sub_device == 0)
                    return '';
            }
        }

        // Scan PCI/USB Id database for vendor/device[/sub-vendor/sub-device]
        if (!($fh = fopen((!$is_usb ? self::FILE_PCI_ID : self::FILE_USB_ID), 'r')))
            return '';

        $details['bus'] = ($is_usb) ? 'USB' : 'PCI';

        // Find vendor id first
        $search = sprintf('%04x', $id_vendor);

        while (!feof($fh)) {
            $buffer = chop(fgets($fh, 4096));
            if (substr($buffer, 0, 4) != $search)
                continue;
            $details['vendor'] = substr($buffer, 6);
            break;
        }

        if ($details['vendor'] == NULL) {
            fclose($fh);
            return '';
        }

        // Find device id next
        $search = sprintf('%04x', $id_device);

        while (!feof($fh)) {
            $byte = fread($fh, 1);
            if ($byte == '#') {
                fgets($fh, 4096);
                continue;
            } else if ($byte != "\t") {
                break;
            }

            $buffer = chop(fgets($fh, 4096));
            if (substr($buffer, 0, 4) != $search)
                continue;
            $details['device'] = substr($buffer, 6);
            break;
        }

        if ($details['device'] == NULL) {
            if (!$is_usb) {
                fclose($fh);
                throw new Engine_Exception(lang('base_something_weird_happened'));
            }

            // For USB devices, this isn't an error
            // XXX: Probably isn't for PCI devices either?
            return $details;
        }

        if ($id_sub_vendor == 0) {
            fclose($fh);
            return $details;
        }

        // Find (optional) sub-vendor id next
        $search = sprintf('%04x %04x', $id_sub_vendor, $id_sub_device);

        while (!feof($fh)) {
            $byte = fread($fh, 1);
            if ($byte == '#') {
                fgets($fh, 4096);
                continue;
            } else if ($byte != "\t") {
                break;
            }

            if(fread($fh, 1) != "\t")
                break;

            $buffer = chop(fgets($fh, 4096));
            if (substr($buffer, 0, 9) != $search)
                continue;
            $details['sub_device'] = substr($buffer, 11);
            break;
        }

        fclose($fh);

        return $details;
    }
    
    /**
     * Returns state of interface.
     *
     * @return boolean TRUE if active
     * @throws Engine_Exception
     */

    public function is_active()
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_interface($this->iface));

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

        if (preg_match('/^eth/', $this->iface)
            || preg_match('/^wlan/', $this->iface)
            || preg_match('/^ath/', $this->iface)
            || preg_match('/^br/', $this->iface) 
            || preg_match('/^bond/', $this->iface)
            || (preg_match('/^ppp/', $this->iface) && $this->is_configured())
        ) {
            return TRUE;
        } else {
            return FALSE;
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

        $file = new File(self::PATH_SYSCONF . '/network-scripts/ifcfg-' . $this->iface);

        if ($file->exists())
            return TRUE;
        else
            return FALSE;
    }

    /**
     * Checks to see if interface name is available on the system.
     *
     * @return boolean TRUE if interface is valid
     * @throws Engine_Exception
     */

    public function is_valid()
    {
        clearos_profile(__METHOD__, __LINE__);

        $iface_manager = new Iface_Manager();
        $interfaces = $iface_manager->get_interfaces(FALSE, FALSE);

        foreach ($interfaces as $iface) {
            if ($this->iface === $iface)
                return TRUE;
        }

        return FALSE;
    }

    /**
     * Sets MAC address.
     *
     * If MAC address is empty, the MAC address for live network interface is configured.
     *
     * @param string $mac MAC address
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_mac($mac = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_interface($this->iface));

        $file = new File(self::PATH_SYSCONF . '/network-scripts/ifcfg-' . $this->iface);

        if (! $file->exists())
            return;

        if (is_null($mac))
            $mac = $this->get_live_mac();

        try {
            $file->lookup_value('/^HWADDR\s*=\s*/');
            $file->replace_lines('/^HWADDR\s*=.*$/', "HWADDR=\"$mac\"\n", 1);
        } catch (File_No_Match_Exception $e) {
            $file->add_lines("HWADDR=\"$mac\"\n");
        }
    }

    /**
     * Sets network MTU.
     *
     * @param integer $mtu interface network MTU
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_mtu($mtu)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_interface($this->iface));

        $file = new File(self::PATH_SYSCONF . '/network-scripts/ifcfg-' . $this->iface);

        if (! $file->exists())
            return;

        try {
            $file->lookup_value('/^MTU\s*=\s*/');
            $file->replace_lines('/^MTU\s*=.*$/', "MTU=\"$mtu\"\n", 1);
        } catch (File_No_Match_Exception $e) {
            $file->add_lines("MTU=\"$mtu\"\n");
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

        Validation_Exception::is_valid($this->validate_interface($this->iface));

        $file = new File(self::PATH_SYSCONF . '/network-scripts/ifcfg-' . $this->iface);

        if (! $file->exists())
            return NULL;

        $lines = $file->get_contents_as_array();

        foreach ($lines as $line) {
            $line = preg_replace('/"/', '', $line);

            if (preg_match('/^\s*#/', $line) || !strlen($line))
                continue;

            $line = preg_split('/=/', $line);

            if (preg_match('/^no$/i', $line[1]))
                $netinfo[strtolower($line[0])] = FALSE;
            else if (preg_match('/^yes$/i', $line[1]))
                $netinfo[strtolower($line[0])] = TRUE;
            else
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
    }

    /**
     * Writes interface configuration file.
     *
     * @param array $netinfo network information
     *
     * @return boolean TRUE if write succeeds
     * @throws Engine_Exception
     */

    public function write_config($netinfo)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_interface($this->iface));

        $file = new File(self::PATH_SYSCONF . '/network-scripts/ifcfg-' . $this->iface);

        if ($file->exists())
            $file->delete();

        $file->create('root', 'root', '0600');

        foreach ($netinfo as $key => $value) {
            // The underlying network scripts do not like quotes on DEVICE
            if ($key == 'DEVICE')
                $file->add_lines(strtoupper($key) . '=' . $value . "\n");
            else
                $file->add_lines(strtoupper($key) . '="' . $value . "\"\n");
        }

        return TRUE;
    }

    /**
     * Creates a PPPoE configuration.
     *
     * @param string  $eth      ethernet interface to use
     * @param string  $username username
     * @param string  $password password
     * @param integer $mtu      MTU
     * @param boolean $peerdns  set DNS servers
     *
     * @return string New/current PPPoE interface name
     * @throws Engine_Exception
     */

    public function save_pppoe_config($eth, $username, $password, $mtu = NULL, $peerdns = TRUE)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_interface($this->iface));
        Validation_Exception::is_valid($this->validate_interface($eth));
        Validation_Exception::is_valid($this->validate_username($username));
        Validation_Exception::is_valid($this->validate_password($password));
        Validation_Exception::is_valid($this->validate_mtu($mtu));
        Validation_Exception::is_valid($this->validate_peerdns($peerdns));

        // PPPoE hacking... again.
        // Before saving over an existing configuration, grab
        // the current configuration and delete the associated
        // password from chap/pap secrets.

        $chap = new Chap();
        $oldiface = new Iface($eth);
        $oldinfo = $oldiface->get_info();

        if (isset($oldinfo['ifcfg']['user']))
            $chap->delete_secret($oldinfo['ifcfg']['user']);

        if (isset($oldinfo['role'])) {
            try {
                $role = new Role();
                $role->remove_interface_role($eth);
            } catch (Engine_Exception $e) {
                // Not fatal
            }
        }

        $physdev = $eth;

        if (substr($eth, 0, 3) == 'ppp') {
            $pppoe = new Iface($eth);
            $ifcfg = $pppoe->get_info();
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
        $liveinfo = $ethernet->get_info();

        $ethinfo = array();
        $ethinfo['DEVICE'] = $physdev;
        $ethinfo['BOOTPROTO'] = 'none';
        $ethinfo['ONBOOT'] = 'no';
        $ethinfo['HWADDR'] = $liveinfo['hwaddress'];

        try {
            $ethernet->disable(); // See maintenance note
        } catch (Engine_Exception $e) {
            // Not fatal
        }

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
        $info['PEERDNS'] = ($peerdns) ? 'yes' : 'no';
        $info['USER'] = $username;

        if (!empty($mtu))
            $info['MTU'] = $mtu;

        $pppoe = new Iface($eth);

        try {
            $pppoe->disable();
        } catch (Engine_Exception $e) {
            // Not fatal
        }

        $pppoe->write_config($info);

        // Add password to chap-secrets
        //-----------------------------

        $chap->add_secret($username, $password);

        return $eth;
    }


    /**
     * Creates a standard ethernet configuration.
     *
     * @param string  $hostname         optional DHCP hostname (for DHCP only)
     * @param boolean $peerdns          set to TRUE if you want to use the DHCP peer DNS settings
     *
     * @return void
     * @throws  Engine_Exception
     */

    public function save_dhcp_config($hostname, $peerdns)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_interface($this->iface));
        Validation_Exception::is_valid($this->validate_peerdns($peerdns));

        if (! empty($hostname))
            Validation_Exception::is_valid($this->validate_hostname($hostname));

        $liveinfo = $this->get_info();
        $hwaddress = $liveinfo['hwaddress'];

        // Disable interface - see maintenance note
        try {
            $this->disable();
        } catch (Engine_Exception $e) {
            // Not fatal
        }

        $info = array();
        $info['DEVICE'] = $this->iface;
        $info['TYPE'] = self::TYPE_ETHERNET;
        $info['ONBOOT'] = 'yes';
        $info['USERCTL'] = 'no';
        $info['HWADDR'] = $hwaddress;
        $info['BOOTPROTO'] = 'dhcp';
        $info['PEERDNS'] = ($peerdns) ? 'yes' : 'no';

        if (strlen($hostname))
            $info['DHCP_HOSTNAME'] = $hostname;

        $this->write_config($info);
    }

    /**
     * Creates a standard ethernet configuration.
     *
     * @param string  $ip               IP address (for static only)
     * @param string  $netmask          netmask (for static only)
     * @param string  $gateway          gate (for static only)
     *
     * @return void
     * @throws  Engine_Exception
     */

    public function save_static_config($ip, $netmask, $gateway = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_ip($ip));
        Validation_Exception::is_valid($this->validate_netmask($netmask));

        if (! empty($gateway))
            Validation_Exception::is_valid($this->validate_gateway($gateway));

        $liveinfo = $this->get_info();
        $hwaddress = $liveinfo['hwaddress'];

        // Disable interface - see maintenance note

        try {
            $this->disable();
        } catch (Engine_Exception $e) {
            // Not fatal
        }

        $info = array();
        $info['DEVICE'] = $this->iface;
        $info['TYPE'] = self::TYPE_ETHERNET;
        $info['ONBOOT'] = 'yes';
        $info['USERCTL'] = 'no';
        $info['HWADDR'] = $hwaddress;
        $info['BOOTPROTO'] = 'static';
        $info['IPADDR'] = $ip;
        $info['NETMASK'] = $netmask;

        if (! empty($gateway))
            $info['GATEWAY'] = $gateway;

        $this->write_config($info);
    }

    /**
     * Creates a virtual ethernet configuration.
     *
     * @param string $ip      IP address
     * @param string $netmask netmask
     *
     * @return string  name of virtual interface
     * @throws Engine_Exception, Engine_Exception
     */

    public function save_virtual_config($ip, $netmask)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_interface($this->iface));
        Validation_Exception::is_valid($this->validate_ip($ip));
        Validation_Exception::is_valid($this->validate_netmask($netmask));

        list($device, $metric) = preg_split('/:/', $this->iface, 5);

        if (! strlen($metric)) {
            // Find next free virtual metric

            for ($metric = 0; $metric < 1024; $metric++) {
                if (! file_exists(self::PATH_SYSCONF .  '/network-scripts/ifcfg-' . $this->iface . ':' . $metric))
                    break;
            }

            // Rename interface
            $this->iface = $this->iface . ':' . $metric;
        }

        // Disable interface - see maintenance note

        try {
            $this->disable();
        } catch (Engine_Exception $e) {
            // Not fatal
        }

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
    }

    /**
     * Create a wireless network configuration.
     *
     * @param string  $isdhcp  set to TRUE if DHCP
     * @param string  $ip      IP address (for static only)
     * @param string  $netmask netmask (for static only)
     * @param string  $gateway gateway (for static only)
     * @param string  $essid   ESSID
     * @param string  $channel channel
     * @param string  $mode    mode
     * @param string  $key     key
     * @param string  $rate    rate
     * @param boolean $peerdns set to TRUE if you want to use the DHCP peer DNS settings
     *
     * @return void
     * @throws  Engine_Exception, Engine_Exception
     */

    public function save_wireless_config($isdhcp, $ip, $netmask, $gateway, $essid, $channel, $mode, $key, $rate, $peerdns)
    {
        clearos_profile(__METHOD__, __LINE__);

        // TODO: is this still used?
        return;

        // Disable interface - see maintenance note

        try {
            $this->disable();
        } catch (Engine_Exception $e) {
            // Not fatal
        }

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
    }

    ///////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   R O U T I N E S
    ///////////////////////////////////////////////////////////////////////////

    /**
     * Validation routine for boot protocol.
     *
     * @param string $boot_protocol boot protocol
     *
     * @return string error message if boot protocol is invalid
     */

    public function validate_boot_protocol($boot_protocol)
    {
        clearos_profile(__METHOD__, __LINE__);

        $supported = $this->get_supported_bootprotos();

        if (! array_key_exists($boot_protocol, $supported))
            return lang('network_boot_protocol_invalid');
    }

    /**
     * Validation routine for gateway.
     *
     * @param string $gateway gateway
     *
     * @return string error message if gateway is invalid
     */

    public function validate_gateway($gateway)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! Network_Utils::is_valid_ip($gateway))
            return lang('network_gateway_invalid');
    }

    /**
     * Validation routine for gateway flag.
     *
     * @param string $gateway_flag gateway flag
     *
     * @return string error message if gateway flag is invalid
     */

    public function validate_gateway_flag($gateway_flag)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! clearos_is_valid_boolean($gateway_flag))
            return lang('network_gateway_flag_invalid');
    }

    /**
     * Validation routine for hostname.
     *
     * @param string $hostname hostname
     *
     * @return string error message if hostname is invalid
     */

    public function validate_hostname($hostname)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!(Network_Utils::is_valid_hostname_alias($hostname) || Network_Utils::is_valid_hostname($hostname)))
            return lang('network_hostname_invalid');
    }

    /**
     * Validation routine for network interface.
     *
     * @param string $interface network interface
     *
     * @return string error message if network interface is invalid
     */

    public function validate_interface($interface)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! preg_match('/^[a-zA-Z0-9:]+$/', $interface))
            return lang('network_network_interface_invalid' . $interface);
    }

    /**
     * Validation routine for IP address.
     *
     * @param string $ip IP address
     *
     * @return string error message if IP address is invalid
     */

    public function validate_ip($ip)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! Network_Utils::is_valid_ip($ip))
            return lang('network_ip_invalid');
    }

    /**
     * Validation routine for netmask.
     *
     * @param string $netmask netmask
     *
     * @return string error message if netmask is invalid
     */

    public function validate_netmask($netmask)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! Network_Utils::is_valid_netmask($netmask))
            return lang('network_netmask_invalid');
    }

    /**
     * Validation routine for password.
     *
     * @param string $password password
     *
     * @return string error message if password is invalid
     */

    public function validate_password($password)
    {
        clearos_profile(__METHOD__, __LINE__);

        // FIXME
    }

    /**
     * Validation routine for network peerdns.
     *
     * @param string $peerdns network peerdns
     *
     * @return string error message if network peerdns is invalid
     */

    public function validate_peerdns($peerdns)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! clearos_is_valid_boolean($peerdns))
            return lang('network_peerdns_invalid');
    }

    /**
     * Validation routine for network MTU.
     *
     * @param string $mtu network MTU
     *
     * @return string error message if network MTU is invalid
     */

    public function validate_mtu($mtu)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! preg_match('/^[0-9]+$/', $mtu))
            return lang('network_mtu_invalid');
    }

    /**
     * Validation routine for username.
     *
     * @param string $username username
     *
     * @return string error message if username is invalid
     */

    public function validate_username($username)
    {
        clearos_profile(__METHOD__, __LINE__);

        // FIXME
        // if (! preg_match('/^[a-zA-Z0-9:]+$/', $username))
        //    return lang('network_username_invalid');
    }
}
