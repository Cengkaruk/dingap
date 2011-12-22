<?php

/**
 * Network utilities class.
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
clearos_load_language('network');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Engine as Engine;

clearos_load_library('base/Engine');

// Exceptions
//-----------

use \clearos\apps\base\Validation_Exception as Validation_Exception;

clearos_load_library('base/Validation_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Network utilities class.
 *
 * General utilities used in dealing with the network.
 *
 * @category   Apps
 * @package    Network
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network/
 */

class Network_Utils extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Network constructor.
     *
     * @return void
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Returns broadcast address for given IP and netmask.
     *
     * @param string $ip      IP address
     * @param string $netmask netmask
     *
     * @return string broadcast address
     * @throws Validation_Exception
     */

    public static function get_broadcast_address($ip, $netmask)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (Network_Utils::is_valid_ip($ip) === FALSE)
            throw new Validation_Exception(lang('network_ip_invalid'));

        if (Network_Utils::is_valid_netmask($netmask) === FALSE)
            throw new Validation_Exception(lang('network_netmask_invalid'));

        // TODO: IPv6 support (replace ip2long with inet_pton)
        $ip_long = ip2long($ip);
        $netmask_long = ip2long($netmask);

        $network = ($ip_long & $netmask_long);
        $broadcast = $network | (~$netmask_long);

        return long2ip($broadcast);
    }

    /**
     * Returns netmask for give prefix (bitmask)
     *
     * @param string $prefix prefix
     *
     * @return string netmask
     * @throws Validation_Exception
     */

    public static function get_netmask($prefix)
    {
        clearos_profile(__METHOD__, __LINE__);

        $prefix_list = Network_Utils::_get_prefix_list();

        if (isset($prefix_list[$prefix]))
            return $prefix_list[$prefix];
        else 
            throw new Validation_Exception(lang('network_prefix_invalid'));
    }

    /**
     * Returns network address for given IP and netmask.
     *
     * @param string $ip      IP address
     * @param string $netmask netmask
     *
     * @return string network address
     * @throws Validation_Exception
     */

    public static function get_network_address($ip, $netmask)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (Network_Utils::is_valid_ip($ip) === FALSE)
            throw new Validation_Exception(lang('network_ip_invalid'));

        if (Network_Utils::is_valid_netmask($netmask) === FALSE)
            throw new Validation_Exception(lang('network_netmask_invalid'));

        // TODO: IPv6 support (replace ip2long with inet_pton)
        $ip_long = ip2long($ip);
        $netmask_long = ip2long($netmask);
        $network_address = ($ip_long & $netmask_long);

        return long2ip($network_address);
    }

    /**
     * Returns prefix (bitmask) for given netmask
     *
     * @param string $netmask netmask
     *
     * @return integer bitmask
     * @throws Validation_Exception
     */

    public static function get_prefix($netmask)
    {
        clearos_profile(__METHOD__, __LINE__);

        $netmask_list = array_flip(Network_Utils::_get_prefix_list());

        if (isset($netmask_list[$netmask]))
            return $netmask_list[$netmask];
        else 
            throw new Validation_Exception(lang('network_netmask_invalid'));
    }

    /**
     * Checks if IP address is in private range.
     *
     * @param string $ip IP address
     *
     * @return boolean TRUE if IP is private
     * @throws Validation_Exception
     */

    public static function is_private_ip($ip)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (Network_Utils::is_valid_ip($ip) === FALSE)
            throw new Validation_Exception(lang('network_ip_invalid'));

        $ip_long = ip2long($ip);

        if (   ( ($ip_long >= ip2long("10.0.0.0")) && ($ip_long <= ip2long("10.255.255.255")) )
            || ( ($ip_long >= ip2long("172.16.0.0")) && ($ip_long <= ip2long("172.31.255.255")) )
            || ( ($ip_long >= ip2long("192.168.0.0")) && ($ip_long <= ip2long("192.168.255.255")) )
        )
            return TRUE;

        return FALSE;
    }

    /**
     * Validates an Internet domain name.
     *
     * @param string $domain Internet domain name
     *
     * @return string error message if Internet domain is invalid
     */

    public static function is_valid_domain($domain)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (preg_match('/^([0-9a-zA-Z\.\-_]+)$/', $domain)) {
            if (substr_count($domain, ".") == 0)
                return FALSE;
        } else
            return FALSE;

        return TRUE;
    }

    /**
     * Validates a hostname.
     *
     * @param string $hostname hostname
     *
     * @return string error message if hostname is invalid
     */

    public static function is_valid_hostname($hostname)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! preg_match('/^([0-9a-zA-Z\.\-_]+)$/', $hostname))
            return FALSE;
        else if (substr_count($hostname, ".") === 0 && !preg_match("/^localhost$/i", $hostname))
            return FALSE;

        return TRUE;
    }

    /**
     * Validates a hostname alias.
     *
     * @param string $alias alias
     *
     * @return string error message if hostname alias is invalid
     */

    public static function is_valid_hostname_alias($alias)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! preg_match('/^([0-9a-zA-Z\.\-_]+)$/', $alias))
            return FALSE;

        return TRUE;
    }

    /**
     * Validates an IP address.
     *
     * @param string $ip IP address
     *
     * @return string error message if IP is invalid
     */

    public static function is_valid_ip($ip)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (@inet_pton($ip) === FALSE)
            return FALSE;

        return TRUE;
    }

    /**
     * Checks to see if test IP is on the network given by IP/netmask.
     *
     * @param string $ip      IP address
     * @param string $netmask netmask
     * @param string $testip  test IP address
     *
     * @return string error message if IP is not on the network
     */

    public static function is_valid_ip_on_network($ip, $netmask, $testip)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (Network_Utils::is_valid_ip($ip) === FALSE)
            return FALSE;

        // TODO: IPv6
        $mask = ip2long($netmask);
        $bin_netmask = long2ip($mask) == $netmask ? $mask : 0xffffffff << (32 - $netmask);
        $bin_gateway = ip2long($testip);

        if ($bin_gateway === FALSE)
            return FALSE;

        $network = $bin_ip & $bin_netmask;
        $broadcast = $network | (~$bin_netmask);

        if ($bin_ip == $bin_gateway || $bin_gateway <= $network || $bin_gateway >= $broadcast)
            return FALSE;

        return TRUE;
    }

    /**
     * Validates an IP address range.
     *
     * @param string $from starting IP address
     * @param string $to   ending IP address
     *
     * @return string error message if IP range is invalid
     */

    public static function is_valid_ip_range($from, $to)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (Network_Utils::is_valid_ip($from) === FALSE || Network_Utils::is_valid_ip($to) === FALSE)
            return FALSE;

        // Convert IP addresses to hexadecimal for comparison
        $from_hex = inet_pton($from);
        $to_hex = inet_pton($to);

        if (strcmp($from_hex, $to_hex) <= 0)
            return TRUE;

        return FALSE;
    }

    /**
     * Checks if IP address/hostname is bound to a local interface.
     *
     * @param string $address IP address or hostname
     *
     * @return string error message if address is not bound to a local interface
     */

    public static function is_valid_local_ip($address)
    {
        clearos_profile(__METHOD__, __LINE__);

        $ip = gethostbyname($address);
        if (Network_Utils::is_valid_ip($ip) === FALSE)
            return FALSE;

        $ph = popen(
            "/sbin/ip -o addr list | egrep 'inet [0-9]{1,3}' | " .
            "sed -e 's/^.*inet \\([0-9]*\\.[0-9]*\\.[0-9]*\\.[0-9]*\\).*/\\1/g'", "r"
        );

        if (!is_resource($ph))
            return FALSE;

        $match = FALSE;
        while (!feof($ph)) {
            if ($ip == chop(fgets($ph, 4096))) {
                $match = TRUE;
                break;
            }
        }

        pclose($ph);

        return $match;
    }

    /**
     * Validates a MAC address.
     *
     * @param string $mac MAC address
     *
     * @return string error message if MAC address is invalid
     */

    public static function is_valid_mac($mac)
    {
        clearos_profile(__METHOD__, __LINE__);

        $mac = strtoupper($mac);

        if (!(preg_match("/^[0-9A-F]{2}:[0-9A-F]{2}:[0-9A-F]{2}:[0-9A-F]{2}:[0-9A-F]{2}:[0-9A-F]{2}$/", $mac)))
            return FALSE;

        return TRUE;
    }

    /**
     * Validates a netmask.
     *
     * @param string $netmask netmask
     *
     * @return string error message if netmask is invalid
     */

    public static function is_valid_netmask($netmask)
    {
        clearos_profile(__METHOD__, __LINE__);

        $netmask_list = array_flip(Network_Utils::_get_prefix_list());

        if (! isset($netmask_list[$netmask]))
            return FALSE;

        return TRUE;
    }

    /**
     * Validates a network address.
     *
     * @param string $network network address
     *
     * @return string error message if network is invalid
     */

    public static function is_valid_network($network)
    {
        clearos_profile(__METHOD__, __LINE__);

        $matches = array();

        if (! preg_match("/^(.*)\/(.*)$/", $network, $matches))
            return FALSE;

        $baseip = $matches[1];
        $netmask_or_prefix = $matches[2];

        if (! Network_Utils::validate_prefix($netmask_or_prefix)) {
            // Convert a prefix (/24) to a netmask (/255.255.255.0)
            $netmask = Network_Utils::get_netmask($netmask_or_prefix);
        } else if (! Network_Utils::validate_netmask($netmask_or_prefix)) {
            $netmask = $netmask_or_prefix;
        } else {
            return FALSE;
        }

        // Make sure the base IP is valid
        $check = Network_Utils::get_network_address($baseip, $netmask);

        if ($check !== $baseip)
            return FALSE;

        return TRUE;
    }

    /**
     * Validates a port number.
     *
     * @param string $port port number
     *
     * @return string error message if port is invalid
     */

    public static function is_valid_port($port)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! preg_match("/^\d+$/", $port))
            return FALSE;

        if (($port > 65535) || ($port <= 0))
            return FALSE;

        return TRUE;
    }

    /**
     * Validates a port range.
     *
     * @param string $from starting port number
     * @param string $to   ending port number
     *
     * @return string error message if port range is invalid
     */

    public static function is_valid_port_range($from, $to)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ((! preg_match("/^\d+$/", $from)) || (! preg_match("/^\d+$/", $from)))
            return FALSE;

        if (($from > 65535) || ($from <= 0) || ($to > 65535) || ($to <= 0))
            return FALSE;

        if ($from > $to)
            return FALSE;

        return TRUE;
    }

    /**
     * Validates a prefix (bitmask).
     *
     * @param string $prefix prefix
     *
     * @return string error message if prefix is invalid
     */

    public static function is_valid_prefix($prefix)
    {
        clearos_profile(__METHOD__, __LINE__);

        $prefix_list = Network_Utils::_get_prefix_list();

        if (isset($prefix_list[$prefix]))
            return TRUE;
        else
            return FALSE;
    }

    /**
     * Validates a protocol.
     *
     * @param string $protocol protocol
     *
     * @return string error message if protocol is invalid
     */

    public static function is_valid_protocol($protocol)
    {
        clearos_profile(__METHOD__, __LINE__);

        // TODO: $protocol should be checked against a static array or /etc/protocols
        if (!preg_match("/^(TCP|UDP)$/", $protocol))
            return FALSE;

        return TRUE;
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E  M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Returns a list of valid prefixes
     *
     * @return void
     */

    protected static function _get_prefix_list()
    {
        clearos_profile(__METHOD__, __LINE__);

        return array(
            0 => '0.0.0.0',
            1 => '128.0.0.0',
            2 => '192.0.0.0',
            3 => '224.0.0.0',
            4 => '240.0.0.0',
            5 => '248.0.0.0',
            6 => '252.0.0.0',
            7 => '254.0.0.0',
            8 => '255.0.0.0',
            9 => '255.128.0.0',
            10 => '255.192.0.0',
            11 => '255.224.0.0',
            12 => '255.240.0.0',
            13 => '255.248.0.0',
            14 => '255.252.0.0',
            15 => '255.254.0.0',
            16 => '255.255.0.0',
            17 => '255.255.128.0',
            18 => '255.255.192.0',
            19 => '255.255.224.0',
            20 => '255.255.240.0',
            21 => '255.255.248.0',
            22 => '255.255.252.0',
            23 => '255.255.254.0',
            24 => '255.255.255.0',
            25 => '255.255.255.128',
            26 => '255.255.255.192',
            27 => '255.255.255.224',
            28 => '255.255.255.240',
            29 => '255.255.255.248',
            30 => '255.255.255.252',
            31 => '255.255.255.254',
            32 => '255.255.255.255'
        );
    }
}
