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

/**
 * Network interface class.
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

clearos_load_library('network/Chap');
clearos_load_library('base/File');
clearos_load_library('network/IfaceManager');
clearos_load_library('network/Network');
clearos_load_library('base/ShellExec');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Network interface class.
 *
 * @package ClearOS
 * @subpackage API
 * @author {@link http://www.clearfoundation.com/ ClearFoundation}
 * @license http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @copyright Copyright 2003-2010 ClearFoundation
 */

class Iface extends Network
{
    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    const PROC_DEV = '/proc/net/dev';
    const PATH_SYSCONF = '/etc/sysconfig';
    const CMD_IFUP = '/sbin/ifup ';
    const CMD_IFDOWN = '/sbin/ifdown ';
    const CMD_IFCONFIG = '/sbin/ifconfig ';
    const CMD_IWCONFIG = '/sbin/iwconfig';
    const CMD_ETHTOOL = '/sbin/ethtool ';
    const FILE_LOG = '/var/log/messages';
    const TYPE_BONDED = 'Bonded';
    const TYPE_BONDED_SLAVE = 'BondedChild';
    const TYPE_BRIDGED = 'Bridge';
    const TYPE_BRIDGED_SLAVE = 'BridgeChild';
    const TYPE_ETHERNET = 'Ethernet';
    const TYPE_PPPOE = 'xDSL';
    const TYPE_VIRTUAL = 'Virtual';
    const TYPE_VLAN = 'VLAN';
    const TYPE_WIRELESS = 'Wireless';
    const TYPE_UNKNOWN = 'Unknown';
    const BOOTPROTO_BOOTP = 'bootp';
    const BOOTPROTO_DHCP = 'dhcp';
    const BOOTPROTO_STATIC = 'static';
    const BOOTPROTO_PPPOE = 'pppoe';
    const BOOTPROTO_DIALUP = 'dialup';

    protected $iface = null;

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Iface constructor.
     *
     * @param  string  $iface  the interface
     */

    public function __construct($iface = null)
    {
        ClearOsLogger::Profile(__METHOD__, __LINE__);

        $this->iface = $iface;

        parent::__construct();

    }

    /**
     * Pseudo-constructor, for SOAP callers
     *
     * @param string $iface Interface name
     * @return void
     */

    public function SetInterface($iface)
    {
        $this->iface = $iface;
        if (! $this->IsValid())
            throw new EngineException(IFACE_LANG_ERRMSG_INVALID . " - " . $this->iface, COMMON_ERROR);
    }

    /**
     * Deletes interface configuration.
     *
     * @param string $iface Interface name (optional)
     * @return  void
     * @throws EngineException
     */

    public function DeleteConfig($iface = null)
    {
        ClearOsLogger::Profile(__METHOD__, __LINE__);

        if($iface != null) $this->iface = $iface;

        // More PPPoE crap

        try {
            $info = $this->GetInterfaceInfo();

            if (isset($info['ifcfg']['user'])) {
                $chap = new Chap();
                $chap->DeleteUser($info['ifcfg']['user']);
            }

            if (isset($info["ifcfg"]["eth"])) {
                $pppoedev = new Iface($info["ifcfg"]["eth"]);
                $pppoedev->DeleteConfig();
            }

            $this->Disable();

            sleep(2); // Give it a chance to disappear

            $file = new File(self::PATH_SYSCONF . '/network-scripts/ifcfg-' . $this->iface);

            if ($file->Exists())
                $file->Delete();
        } catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_WARNING);
        }
    }

    /**
     * Deletes virtual interface.
     *
     * @return void
     * @throws EngineException
     */

    public function DeleteVirtual()
    {
        ClearOsLogger::Profile(__METHOD__, __LINE__);

        list($device, $metric) = split(':', $this->iface, 5);

        if (!strlen($metric))
            return;

        $shell = new ShellExec();
        $retval = $shell->Execute(self::CMD_IFDOWN, $this->iface, true);

        if ($retval != 0) {
            // Really force it down if ifdown fails.  Don't bother logging errors...
            $retval = $shell->Execute(self::CMD_IFCONFIG, $this->iface . ' down', true);
        }

        $file = new File(self::PATH_SYSCONF . '/network-scripts/ifcfg-' . $this->iface);

        if ($file->Exists())
            $file->Delete();
    }

    /**
     * Takes interface down.
     *
     * @param string $iface Interface name (optional)
     * @return  void
     * @throws EngineException
     */

    public function Disable($iface = null)
    {
        ClearOsLogger::Profile(__METHOD__, __LINE__);

        if($iface != null) $this->iface = $iface;

        try {
            $shell = new ShellExec();
            $retval = $shell->Execute(self::CMD_IFDOWN, $this->iface, true);

            if ($retval != 0) {
                // Really force it down if ifdown fails.  Don't bother logging errors...
                $retval = $shell->Execute(self::CMD_IFCONFIG, $this->iface . ' down', true);
            }
        } catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_WARNING);
        }
    }

    /**
     * Brings interface up.
     *
     * @param boolean $background perform enable in the background
     * @return void
     * @throws EngineException
     */

    public function Enable($background = false)
    {
        ClearOsLogger::Profile(__METHOD__, __LINE__);

        try {
            $options = array();

            if ($background)
                    $options['background'] = true;

            $shell = new ShellExec();
            $retval = $shell->Execute(self::CMD_IFUP, $this->iface, true, $options);

            if ($retval != 0)
                throw new EngineException($shell->GetFirstOutputLine(), COMMON_WARNING);
        } catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_WARNING);
        }
    }

    /**
     * Returns the boot protocol of interface as a readable string for end users.
     *
     * @return string boot protocol of interface
     * @throws EngineException
     */

    public function GetBootProtocol()
    {
        ClearOsLogger::Profile(__METHOD__, __LINE__);

        $bootproto = "";

        if ($this->IsConfigured()) {
            $info = $this->ReadConfig();
            $bootproto = $info["bootproto"];

            // PPPOEKLUDGE - set the boot protocol on PPPoE interfaces
            if ($this->GetType() == self::TYPE_PPPOE)
                $bootproto = self::BOOTPROTO_PPPOE;
        }

        return $bootproto;
    }

    /**
     * Returns the boot protocol of interface as a readable string for end users.
     *
     * @return string boot protocol of interface
     * @throws EngineException
     */

    public function GetBootProtocolText()
    {
        ClearOsLogger::Profile(__METHOD__, __LINE__);

        $bootproto = $this->GetBootProtocol();
        $text = "";

        if ($bootproto == self::BOOTPROTO_DHCP)
            $text = IFACE_LANG_DHCP;
        else if ($bootproto == self::BOOTPROTO_STATIC)
            $text = IFACE_LANG_STATIC;
        else if ($bootproto == self::BOOTPROTO_PPPOE)
            $text = IFACE_LANG_PPPOE;

        return $text;
    }

    /**
     * Returns interface information as an associative array.
     *
     * @return  array  interface information
     * @throws  EngineException, EngineException
     */

    public function GetInterfaceInfo()
    {
        ClearOsLogger::Profile(__METHOD__, __LINE__);

        try {
            if (! $this->IsValid())
                throw new EngineException(IFACE_LANG_ERRMSG_INVALID, COMMON_NOTICE);

            // Using ioctl(2) calls (from custom extension ifconfig.so).

            if (! extension_loaded('ifconfig')) {
                if (!@dl('ifconfig.so')) {
                    throw new EngineException(LOCALE_LANG_ERRMSG_WEIRD, COMMON_ERROR);
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
                $info['link'] = $this->GetLinkStatus();
            } catch (Exception $e) {
                // Keep going?
            }

            try {
                $info['speed'] = $this->GetSpeed();
            } catch (Exception $e) {
                // Keep going?
            }

            try {
                $info['type'] = $this->GetType();
                $info['typetext'] = $this->GetTypeText();
            } catch (Exception $e) {
                // Keep going?
            }

            if (preg_match("/^[a-z]+\d+:/", $this->iface)) {
                $info['virtual'] = true;

                $virtualnum = preg_replace("/[a-z]+\d+:/", "", $this->iface);

                if ($virtualnum >= Firewall::CONSTANT_ONE_TO_ONE_NAT_START)
                    $info['one-to-one-nat'] = true;
                else
                    $info['one-to-one-nat'] = false;
            } else {
                $info['virtual'] = false;
                $info['one-to-one-nat'] = false;
            }

            if ($this->IsConfigurable())
                $info['configurable'] = true;
            else
                $info['configurable'] = false;

            if ($this->IsConfigured()) {
                try {
                    $info['configured'] = true;
                    $info['ifcfg'] = $this->ReadConfig();
                } catch (Exception $e) {
                    // Keep going?
                }
            } else {
                $info['configured'] = false;
            }

            return $info;
        } catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_WARNING);
        }
    }

    /**
     * Returns the last connection status in the logs.
     *
     * @return string
     * @throws EngineException
     */

    public function GetIpConnectionLog()
    {
        ClearOsLogger::Profile(__METHOD__, __LINE__);

        try {
            $config = $this->ReadConfig();
            $bootproto = $this->GetBootProtocol();
        
            if ($bootproto == self::BOOTPROTO_PPPOE) {

                $file = new File(self::FILE_LOG, true);
                $results = $file->GetSearchResults(" (pppd|pppoe)\[\d+\]: ");

                for ($inx = count($results); $inx > (count($results) - 15); $inx--) {
                    if (preg_match("/Timeout waiting for/", $results[$inx]))
                        return IFACE_LANG_ERRMSG_NO_PPPOE_SERVER;
                    else if (preg_match("/LCP: timeout/", $results[$inx]))
                        return IFACE_LANG_ERRMSG_NO_PPPOE_SERVER;
                    else if (preg_match("/PAP authentication failed/", $results[$inx]))
                        return IFACE_LANG_ERRMSG_AUTHENTICATION_FAILED;
                }

            } else if ($bootproto == self::BOOTPROTO_DHCP) {

                $file = new File(self::FILE_LOG, true);
                $results = $file->GetSearchResults("dhclient\[\d+\]: ");

                for ($inx = count($results); $inx > (count($results) - 10); $inx--) {
                    if (preg_match("/No DHCPOFFERS received/", $results[$inx]))
                        return IFACE_LANG_ERRMSG_NO_DHCP_SERVER;
                    else if (preg_match("/DHCPDISCOVER/", $results[$inx]))
                        return IFACE_LANG_ERRMSG_WAITING_FOR_DHCP;
                }
            }

        } catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_WARNING);
        }

        return "";
    }

    /**
     * Returns the link status.
     *
     * @return  int false (0) if link is down, true (1) if link present, -1 if not supported by driver.
     * @throws  EngineException, EngineException
     */

    public function GetLinkStatus()
    {
        ClearOsLogger::Profile(__METHOD__, __LINE__);

        if (! $this->IsValid())
            throw new EngineException(IFACE_LANG_ERRMSG_INVALID . " - " . $this->iface, COMMON_ERROR);

        try {
            $type = $this->GetType();

            // Wireless interfaces always have link.
            // PPPOEKLUDGE -- get link status from underlying PPPoE interface.  Sigh.

            if ($type == self::TYPE_WIRELESS) {
                return 1;
            } else if ($type == self::TYPE_PPPOE) {
                $ifaceconfig = $this->ReadConfig();
                $realiface = $ifaceconfig['eth'];
            } else {
                $realiface = $this->iface;
            }

            $shell = new ShellExec();
            $retval = $shell->Execute(self::CMD_ETHTOOL, $realiface, true);

            if ($retval != 0)
                return -1;

            $output = $shell->GetOutput();

            $match = array();
            
            for ($i = 0; $i < sizeof($output); $i++) {
                if (eregi("Link detected: ([A-z]*)", $output[$i], $match)) {
                    $link = ($match[1] == "yes") ? 1 : 0;
                    break;
                }
            }
        } catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_WARNING);
        }

        return $link;
    }

    /**
     * @return  string  IP of interface
     * @throws  EngineException, EngineException
     */

    public function GetLiveIp()
    {
        ClearOsLogger::Profile(__METHOD__, __LINE__);

        if (! $this->IsValid())
            throw new EngineException(IFACE_LANG_ERRMSG_INVALID, COMMON_ERROR);

        // Using ioctl(2) calls (from custom extension ifconfig.so).

        try {
            if (! extension_loaded('ifconfig')) {
                if (!@dl('ifconfig.so'))
                    throw new EngineException(LOCALE_LANG_ERRMSG_WEIRD, COMMON_WARNING);
            }

            // This method is from: /var/webconfig/lib/ifconfig.so
            $handle = @ifconfig_init();
            $ip = @ifconfig_address($handle, $this->iface);
        } catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_WARNING);
        }

        return $ip;
    }

    /**
     * Returns the MAC address.
     *
     * @return string MAC address
     * @throws EngineException, EngineException
     */

    public function GetLiveMac()
    {
        ClearOsLogger::Profile(__METHOD__, __LINE__);

        if (! $this->IsValid())
            throw new EngineException(IFACE_LANG_ERRMSG_INVALID, COMMON_ERROR);

        try {
            // Using ioctl(2) calls (from custom extension ifconfig.so).

            if (! extension_loaded('ifconfig')) {
                if (!@dl('ifconfig.so'))
                    throw new EngineException(LOCALE_LANG_ERRMSG_WEIRD, COMMON_WARNING);
            }

            // This method is from: /var/webconfig/lib/ifconfig.so
            $handle = @ifconfig_init();
            $mac = @ifconfig_hwaddress($handle, $this->iface);
        } catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_WARNING);
        }

        return $mac;
    }

    /**
     * Returns the netmask.
     *
     * @return  string  netmask of interface
     * @throws  EngineException, EngineException
     */

    public function GetLiveNetmask()
    {
        // Using ioctl(2) calls (from custom extension ifconfig.so).

        ClearOsLogger::Profile(__METHOD__, __LINE__);

        if (! $this->IsValid())
            throw new EngineException(IFACE_LANG_ERRMSG_INVALID, COMMON_ERROR);

        try {
            // Using ioctl(2) calls (from custom extension ifconfig.so).

            if (! extension_loaded('ifconfig')) {
                if (!@dl('ifconfig.so'))
                    throw new EngineException(LOCALE_LANG_ERRMSG_WEIRD, COMMON_WARNING);
            }

            // This method is from: /var/webconfig/lib/ifconfig.so
            $handle = @ifconfig_init();
            $netmask = @ifconfig_netmask($handle, $this->iface);
        } catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_WARNING);
        }

        return $netmask;
    }

    /**
     * Gets an interface's MTU.
     *
     * @return int mtu Interface MTU
     * @throws EngineException
     */

    public function GetMtu()
    {
        ClearOsLogger::Profile(__METHOD__, __LINE__);

        if (! extension_loaded('ifconfig')) {
            if (!@dl('ifconfig.so'))
                throw new EngineException(LOCALE_LANG_ERRMSG_WEIRD, COMMON_WARNING);
        }

        $handle = @ifconfig_init();

        try {
            $file = new File(self::PATH_SYSCONF . '/network-scripts/ifcfg-' . $this->iface);

            if (! $file->Exists())
                return @ifconfig_mtu($handle, $this->iface);

            return preg_replace("/\"/", "", $file->LookupValue("/^MTU\s*=\s*/"));
        } catch (FileNoMatchException $e) {
            return @ifconfig_mtu($handle, $this->iface);
        } catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_WARNING);
        }
    }

    /**
     * Returns the interface speed.
     *
     * This method may not be supported in all network card drivers.
     *
     * @return  int  speed in megabits per second
     * @throws  EngineException, EngineException
     */

    public function GetSpeed()
    {
        ClearOsLogger::Profile(__METHOD__, __LINE__);

        if (! $this->IsValid())
            throw new EngineException(IFACE_LANG_ERRMSG_INVALID, COMMON_ERROR);

        $speed = -1;

        try {
            $type = $this->GetType();

            // Wireless interfaces
            //--------------------

            if ($type == self::TYPE_WIRELESS) {
                $shell = new ShellExec();
                $shell->Execute(self::CMD_IWCONFIG, $this->iface, false);
                $output = $shell->GetOutput();
                $matches = array();
                
                foreach ($output as $line) {
                    if (preg_match("/Bit Rate:\s*([0-9]*)/", $line, $matches)) {
                        $speed = $matches[1];
                        break;
                    }
                }

            // Non-wireless interfaces
            //------------------------

            } else {
                // PPPOEKLUDGE -- get speed from underlying PPPoE interface.  Sigh.
                if ($type == self::TYPE_PPPOE) {
                    $ifaceconfig = $this->ReadConfig();
                    $realiface = $ifaceconfig['eth'];
                } else {
                    $realiface = $this->iface;
                }

                $shell = new ShellExec();
                $retval = $shell->Execute(self::CMD_ETHTOOL, $realiface, true);
                $output = $shell->GetOutput();
                $matches = array();

                foreach ($output as $line) {
                    if (preg_match("/^\s*Speed: ([0-9]*)/", $line, $matches)) {
                        $speed = $matches[1];
                        break;
                    }
                }
            }

        } catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_WARNING);
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
     * @throws  EngineException
     */

    public function GetType()
    {
        ClearOsLogger::Profile(__METHOD__, __LINE__);

        $isconfigured = $this->IsConfigured();

        // Not configured?  We can still detect a wireless type
        //-----------------------------------------------------

        if (! $isconfigured) {
            try {
                $shell = new ShellExec();
                $shell->Execute(self::CMD_IWCONFIG, $this->iface, false);
                $output = $shell->GetOutput();
            } catch (Exception $e) {
                throw new EngineException($e->GetMessage(), COMMON_WARNING);
            }

            foreach ($output as $line) {
                if (preg_match("/ESSID/", $line))
                    return self::TYPE_WIRELESS;
            }

            return self::TYPE_ETHERNET;
        }

        $netinfo = $this->ReadConfig();

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
     * @see GetTypeText
     */

    public function GetTypeName()
    {
        ClearOsLogger::Profile(__METHOD__, __LINE__);

        return $this->GetTypeText();
    }

    /**
     * Returns the type of interface as a readable string for end users.
     *
     * @return  string  type of interface
     * @throws  EngineException
     */

    public function GetTypeText()
    {
        ClearOsLogger::Profile(__METHOD__, __LINE__);

        $type = $this->GetType();

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
     * @throws EngineException
     */

    public function SetMac($mac = null)
    {
        ClearOsLogger::Profile(__METHOD__, __LINE__);

        try {
            $file = new File(self::PATH_SYSCONF . '/network-scripts/ifcfg-' . $this->iface);

            if (! $file->Exists())
                return;

            if (is_null($mac))
                $mac = $this->GetLiveMac();

            try {
                $file->LookupValue("/^HWADDR\s*=\s*/");
                $file->ReplaceLines("/^HWADDR\s*=.*$/", "HWADDR=\"$mac\"", 1);
            } catch (FileNoMatchException $e) {
                $file->AddLines("HWADDR=\"$mac\"\n");
            }
        } catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_WARNING);
        }
    }

    /**
     * Sets network MTU.
     *
     * @param int mtu Interface MTU
     * @return void
     * @throws EngineException
     */

    public function SetMtu($mtu)
    {
        ClearOsLogger::Profile(__METHOD__, __LINE__);

        try {
            $file = new File(self::PATH_SYSCONF . '/network-scripts/ifcfg-' . $this->iface);

            if (! $file->Exists())
                return;

            try {
                $file->LookupValue("/^MTU\s*=\s*/");
                $file->ReplaceLines("/^MTU\s*=.*$/", "MTU=\"$mtu\"", 1);
            } catch (FileNoMatchException $e) {
                $file->AddLines("MTU=\"$mtu\"\n");
            }
        } catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_WARNING);
        }
    }

    /**
     * Reads interface configuration file.
     *
     * @return  array  network configuration settings
     * @throws  EngineException
     */

    public function ReadConfig()
    {
        ClearOsLogger::Profile(__METHOD__, __LINE__);

        try {
            $file = new File(self::PATH_SYSCONF . '/network-scripts/ifcfg-' . $this->iface);

            if (! $file->Exists())
                return null;

            $lines = $file->GetContentsAsArray();

            foreach ($lines as $line) {
                $line = eregi_replace('\"', '', $line);

                if (ereg('^[[:space:]]*#', $line) || !strlen($line))
                    continue;

                $line = split('[=]', $line);

                $netinfo[strtolower($line[0])] = $line[1];
            }

            // Translate constants into English
            if (isset($netinfo['bootproto'])) {
                // PPPOEKLUDGE - "dialup" is used by PPPoE
                if ($netinfo['bootproto'] == self::BOOTPROTO_DIALUP)
                    $netinfo['bootproto'] = self::BOOTPROTO_PPPOE;

                if ($netinfo['bootproto'] == self::BOOTPROTO_STATIC)
                    $netinfo['bootprototext'] = IFACE_LANG_STATIC;
                else if ($netinfo['bootproto'] == self::BOOTPROTO_DHCP)
                    $netinfo['bootprototext'] = IFACE_LANG_DHCP;
                else if ($netinfo['bootproto'] == self::BOOTPROTO_PPPOE)
                    $netinfo['bootprototext'] = IFACE_LANG_PPPOE;
                else if ($netinfo['bootproto'] == self::BOOTPROTO_BOOTP)
                    $netinfo['bootprototext'] = IFACE_LANG_BOOTP;
                else 
                    $netinfo['bootprototext'] = IFACE_LANG_STATIC;
            }

            return $netinfo;

        } catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_WARNING);
        }
    }

    /**
     * Writes interface configuration file.
     *
     * @return  boolean  true if write succeeds
     * @throws  EngineException
     */

    public function WriteConfig($netinfo)
    {
        ClearOsLogger::Profile(__METHOD__, __LINE__);

        try {
            $file = new File(self::PATH_SYSCONF . '/network-scripts/ifcfg-' . $this->iface);

            if ($file->Exists())
                $file->Delete();

            $file->Create('root', 'root', '0600');

            foreach($netinfo as $key => $value) {
                // The underlying network scripts do not like quotes on DEVICE
                if ($key == "DEVICE")
                    $file->AddLines(strtoupper($key) . '=' . $value . "\n");
                else
                    $file->AddLines(strtoupper($key) . '="' . $value . "\"\n");
            }

            return true;
        } catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_WARNING);
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
     * @throws  EngineException
     */

    public function SavePppoeConfig($eth, $username, $password, $mtu = null, $peerdns = true)
    {
        ClearOsLogger::Profile(__METHOD__, __LINE__);

        try {
            // PPPoE hacking... again.
            // Before saving over an existing configuration, grab
            // the current configuration and delete the associated
            // password from chap/pap secrets.

            $chap = new Chap();
            $oldiface = new Iface($eth);
            $oldinfo = $oldiface->GetInterfaceInfo();

            if (isset($oldinfo['ifcfg']['user']))
                $chap->DeleteUser($oldinfo['ifcfg']['user']);

            $physdev = $eth;
            if (substr($eth, 0, 3) == 'ppp') {
                $pppoe = new Iface($eth);
                $ifcfg = $pppoe->GetInterfaceInfo();
                $physdev = $ifcfg['ifcfg']['eth'];
            } else {
                for ($i = 0; $i < 64; $i++) {
                    $pppoe = new Iface('ppp' . $i);
                    if (! $pppoe->IsConfigured()) {
                        $eth = 'ppp' . $i;
                        break;
                    }
                }
            }

            // Blank out the ethernet interface used for PPPoE
            //------------------------------------------------

            $ethernet = new Iface($physdev);
            $liveinfo = $ethernet->GetInterfaceInfo();

            $ethinfo = array();
            $ethinfo['DEVICE'] = $physdev;
            $ethinfo['BOOTPROTO'] = 'none';
            $ethinfo['ONBOOT'] = 'no';
            $ethinfo['HWADDR'] = $liveinfo['hwaddress'];

            $ethernet->Disable(); // See maintenance note
            $ethernet->WriteConfig($ethinfo);

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
            $pppoe->Disable(); // See maintenance note
            $pppoe->WriteConfig($info);

            // Add password to chap-secrets
            //-----------------------------

            $chap->AddUser($username, $password);

            return $eth;
        } catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_WARNING);
        }
    }


    /**
     * Creates a standard ethernet configuration.
     *
     * @param  string  $isdhcp  set to true if DHCP
     * @param  boolean $peerdns set to true if you want to use the DHCP peer DNS settings
     * @param  string  $ip  IP address (for static only)
     * @param  string  $netmask  netmask (for static only)
     * @param  string  $gateway  gate (for static only)
     * @param  string  $hostname optional DHCP hostname (for DHCP only)
     * @param  boolean $gatewayrequired flag if gateway setting is required
     * @returns void
     * @throws  EngineException
     */

    public function SaveEthernetConfig($isdhcp, $ip, $netmask, $gateway, $hostname, $peerdns, $gatewayrequired = false)
    {
        ClearOsLogger::Profile(__METHOD__, __LINE__);

        $isvalid = true;
        $network = new Network();

        if (! $isdhcp) {
            if (! $network->IsValidIp($ip)) {
                $this->AddValidationError(NETWORK_LANG_IP . ' - ' . LOCALE_LANG_INVALID, __METHOD__, __LINE__);
                $isvalid = false;
            }

            if (! $network->IsValidNetmask($netmask)) {
                $this->AddValidationError(NETWORK_LANG_NETMASK . ' - ' . LOCALE_LANG_INVALID, __METHOD__, __LINE__);
                $isvalid = false;
            }

            if ($gateway) {
                if (! $network->IsValidIp($gateway)) {
                    $this->AddValidationError(NETWORK_LANG_GATEWAY . ' - ' . LOCALE_LANG_INVALID, __METHOD__, __LINE__);
                    $isvalid = false;
                }
            } else {
                if ($gatewayrequired) {
                    $this->AddValidationError(NETWORK_LANG_GATEWAY . ' - ' . LOCALE_LANG_MISSING, __METHOD__, __LINE__);
                        $isvalid = false;
                    }
                }
            }

        if (! $isvalid)
            throw new ValidationException(LOCALE_LANG_INVALID);

        try {
            $liveinfo = $this->GetInterfaceInfo();
            $hwaddress = $liveinfo['hwaddress'];

            $this->Disable(); // See maintenance note

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
                $info['PEERDNS'] = ($peerdns) ? "yes" : "no";
            } else {
                $info['BOOTPROTO'] = 'static';
                $info['IPADDR'] = $ip;
                $info['NETMASK'] = $netmask;

                if ($gateway)
                    $info['GATEWAY'] = $gateway;
            }

            $this->WriteConfig($info);

        } catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_WARNING);
        }
    }

    /**
     * Creates a virtual ethernet configuration.
     *
     * @param  string  $ip  IP address
     * @param  string  $ip  IP address
     * @returns  string  name of virtual interface
     * @throws  EngineException, EngineException
     */

    public function SaveVirtualConfig($ip, $netmask)
    {
        ClearOsLogger::Profile(__METHOD__, __LINE__);

        $isvalid = true;
        $network = new Network();

        if (! $network->IsValidIp($ip)) {
            $this->AddValidationError(NETWORK_LANG_IP . ' - ' . LOCALE_LANG_INVALID, __METHOD__, __LINE__);
            $isvalid = false;
        }

        if (! $network->IsValidNetmask($netmask)) {
            $this->AddValidationError(NETWORK_LANG_NETMASK . ' - ' . LOCALE_LANG_INVALID, __METHOD__, __LINE__);
            $isvalid = false;
        }

        if (! $isvalid)
            throw new ValidationException(LOCALE_LANG_INVALID);

        try {
            list($device, $metric) = split("\:", $this->iface, 5);

            if (! strlen($metric)) {
                // Find next free virtual metric

                for ($metric = 0; $metric < 1024; $metric++) {
                    if (! file_exists(self::PATH_SYSCONF .  '/network-scripts/ifcfg-' . $this->iface . ':' . $metric))
                        break;
                }

                // Rename interface
                $this->iface = $this->iface . ':' . $metric;
            }

            $this->Disable(); // See maintenance note

            $info = array();
            $info['DEVICE'] = $this->iface;
            $info['TYPE'] = self::TYPE_VIRTUAL;
            $info['ONBOOT'] = 'yes';
            $info['USERCTL'] = 'no';
            $info['BOOTPROTO'] = 'static';
            $info['NO_ALIASROUTING'] = 'yes';
            $info['IPADDR'] = $ip;
            $info['NETMASK'] = $netmask;
            $this->WriteConfig($info);

            return $this->iface;
        } catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_WARNING);
        }
    }

    /**
     * Create a wireless network configuration.
     *
     * @param  string  $isdhcp  set to true if DHCP
     * @param  string  $ip  IP address (for static only)
     * @param  string  $netmask  netmask (for static only)
     * @param  string  $gateway  gateway (for static only)
     * @param  string  $essid  ESSID
     * @param  string  $channel  channel
     * @param  string  $mode  mode
     * @param  string  $key  key
     * @param  string  $rate  rate
     * @param  boolean $peerdns set to true if you want to use the DHCP peer DNS settings
     * @returns void
     * @throws  EngineException, EngineException
     */

    public function SaveWirelessConfig($isdhcp, $ip, $netmask, $gateway, $essid, $channel, $mode, $key, $rate, $peerdns)
    {
        ClearOsLogger::Profile(__METHOD__, __LINE__);

        try {
            if (!$isdhcp && (! $this->IsValidIp($ip))) {
                $errors = $this->GetValidationErrors();
                throw new EngineException($errors[0], COMMON_ERROR);
            }

            $this->Disable(); // See maintenance note

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
                $info['PEERDNS'] = ($peerdns) ? "yes" : "no";
            } else {
                $info['BOOTPROTO'] = 'static';
                $info['IPADDR'] = $ip;
                $info['NETMASK'] = $netmask;

                if ($gateway)
                    $info['GATEWAY'] = $gateway;
            }

            $this->WriteConfig($info);
        } catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_WARNING);
        }
    }

    /**
     * Checks to see if interface has an associated configuration file.
     *
     * @return  boolean  true if configuration file exists
     * @throws  EngineException
     */

    public function IsConfigured()
    {
        ClearOsLogger::Profile(__METHOD__, __LINE__);

        try {
            $file = new File(self::PATH_SYSCONF . "/network-scripts/ifcfg-" . $this->iface);

            if ($file->Exists())
                return true;
            else
                return false;
        } catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_WARNING);
        }
    }

    /**
     * Checks to see if interface name is available on the system.
     *
     * @return  boolean  true if interface is valid
     * @throws  EngineException
     */

    public function IsValid()
    {
        ClearOsLogger::Profile(__METHOD__, __LINE__);

        try {
            $iface_manager = new IfaceManager();
            $interfaces = $iface_manager->GetInterfaces(false, false);

            foreach ($interfaces as $int) {
                if (! strcasecmp($this->iface, $int))
                    return true;
            }

            return false;
        } catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_WARNING);
        }
    }


    /**
     * Returns state of interface.
     *
     * @return  boolean  true if active
     * @throws  EngineException
     */

    public function IsActive()
    {
        ClearOsLogger::Profile(__METHOD__, __LINE__);

        try {
            if (! $this->IsValid())
                throw new EngineException(IFACE_LANG_ERRMSG_INVALID, COMMON_ERROR);

            $shell = new ShellExec();
            $retval = $shell->Execute(self::CMD_IFCONFIG, $this->iface, true);

            if ($retval != 0)
                return false;

            $output = $shell->GetOutput();

            foreach ($output as $line) {
                if (preg_match('/^' .$this->iface . '/', $line))
                    return true;
            }

            return false;
        } catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_WARNING);
        }
    }


    /**
     * Returns the configurability of interface.
     *
     * Dynamic interfaces (e.g. an incoming pppX interface from PPTP VPN)
     * are not configurable.
     *
     * @return  boolean  true if configurable
     */

    public function IsConfigurable()
    {
        ClearOsLogger::Profile(__METHOD__, __LINE__);

        // PPPoE interfaces are configurable, bug only if they already configured.

        if (
            preg_match('/^eth/', $this->iface) ||
            preg_match('/^wlan/', $this->iface) ||
            preg_match('/^ath/', $this->iface) ||
            preg_match('/^br/', $this->iface) ||
            preg_match('/^bond/', $this->iface) ||
            (preg_match('/^ppp/', $this->iface) && $this->IsConfigured())
            ) {
            return true;
        } else {
            return false;
        }
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * @ignore
     */

    public function __destruct()
    {
        ClearOsLogger::Profile(__METHOD__, __LINE__);

        parent::__destruct();
    }

}

// vim: syntax=php ts=4
?>
