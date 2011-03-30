<?php

/**
 * Ethers class.
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

// Classes
//--------

use \clearos\apps\base\Engine as Engine;
use \clearos\apps\base\File as File;
use \clearos\apps\network\Network_Utils as Network_Utils;

clearos_load_library('base/Engine');
clearos_load_library('base/File');
clearos_load_library('network/Network_Utils');

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;
use \clearos\apps\network\Ethers_Not_Found_Exception as Ethers_Not_Found_Exception;
use \clearos\apps\network\Ethers_Already_Exists_Exception as Ethers_Already_exists_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('base/Validation_Exception');
clearos_load_library('network/Ethers_Not_Found_Exception');
clearos_load_library('network/Ethers_Already_Exists_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Ethers class.
 *
 * @category    Apps
 * @package     Network
 * @subpackage  Libraries
 * @author      {@link http://www.clearfoundation.com/ ClearFoundation}
 * @copyright   Copyright 2002-2010 ClearFoundation
 * @license     http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link        http://www.clearfoundation.com/docs/developer/apps/network/
 */

class Ethers extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // M E M B E R S
    ///////////////////////////////////////////////////////////////////////////////

    const FILE_CONFIG = '/etc/ethers';

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Ethers constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->reset_ethers();
    }

    /**
     * Create a new /etc/ethers file.
     *
     * @param   boolean $force delete the existing file if true
     * @return  void
     * @throws  Exception
     */

    public function reset_ethers($force = false)
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_CONFIG);

        if ($force == true) {
            if ($file->exists())
                $file->delete();
        }

        if (! $file->exists()) {
            $file->create('root', 'root', '0644');

            $default  = "# This is an auto-generated file. Please do NOT edit\n";
            $default .= "# Comments are used to aid in maintaining your hosts\n";
            $default .= "# file\n";

            $file->add_lines($default);
        }
    }

    /**
     * Add a MAC/IP pair to the /etc/ethers file.
     *
     * @param   string $mac   MAC address
     * @param   string $ip    IP address
     * @return  void
     * @throws  Exception, Validation_Exception, Ethers_Already_Exists_Exception
     */

    public function add_ether($mac, $ip)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (Network_Utils::is_valid_mac($mac) === FALSE) {
            throw new Validation_Exception(
                lang('network_mac_address_is_invalid'), CLEAROS_ERROR);
        }
        if (Network_Utils::is_valid_ip($ip) === FALSE) {
            throw new Validation_Exception(
                lang('network_ip_is_invalid'), CLEAROS_ERROR);
        }

        $file = new File(self::FILE_CONFIG);
        $contents = $file->get_contents_as_array();

        // Already exists?
        foreach ($contents as $key => $line) {
            if (preg_match("/$mac/", $line))
                throw new Ethers_Already_Exists_Exception($mac, CLEAROS_ERROR);
        }

        // Add
        $contents[] = "$mac $ip";
        $file->dump_contents_from_array($contents);
    }

    /**
     * Delete a MAC/HOSTNAME pair from the /etc/ethers file.
     *
     * @param   string $mac MAC address
     * @return  void
     * @throws  Exception, Validation_Exception, Ethers_Not_Found_Exception
     */

    public function delete_ether($mac)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (Network_Utils::is_valid_mac($mac) === FALSE) {
            throw new Validation_Exception(
                lang('network_mac_address_is_invalid'), CLEAROS_ERROR);
        }

        $file = new File(self::FILE_CONFIG);
        $contents = $file->get_contents_as_array();

        $write_out = false;
        foreach ($contents as $key => $line) {
            if (preg_match("/$mac/", $line)) {
                unset($contents[$key]);
                $write_out = true;
                break;
            }
        }

        if ($write_out)
            $file->dump_contents_from_array($contents);
        else
            throw new Ethers_Not_Found_Exception($mac, CLEAROS_ERROR);
    }

    /**
     * Returns the hostname for the given MAC address.
     *
     * @param   string $mac MAC address
     * @return  string hostname
     * @throws  Exception, Validation_Exception, Ethers_Not_Found_Exception
     */

    public function get_hostname_by_mac($mac)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (Network_Utils::is_valid_mac($mac) === FALSE) {
            throw new Validation_Exception(
                lang('network_mac_address_is_invalid'), CLEAROS_ERROR);
        }

        $ethers = $this->get_ethers();

        if (! isset($ethers[$mac]))
            throw new Ethers_Not_Found_Exception($mac, CLEAROS_ERROR);

        if (Network_Utils::is_valid_hostname($ethers[$mac]) === FALSE) {
            throw new Validation_Exception(
                lang('network_hostname_is_invalid'), CLEAROS_ERROR);
        }

        return $ethers[$mac];
    }

    /**
     * Returns the MAC address for the given hostname.
     *
     * @param   string $hostname hostname
     * @return  string MAC address if found
     * @throws  Exception, Validation_Exception, Ethers_Not_Found_Exception
     */

    public function get_mac_by_hostname($hostname)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (Network_Utils::is_valid_hostname($hostname) === FALSE) {
            throw new Validation_Exception(
                lang('network_hostname_is_invalid'), CLEAROS_ERROR);
        }

        $mac = NULL;
        $ethers = $this->get_ethers();
        foreach ($ethers as $key => $value) {
            if (strcasecmp($hostname, $value) != 0) continue;
            if (Network_Utils::is_valid_mac($key) === FALSE) {
                throw new Validation_Exception(
                    lang('network_mac_address_is_invalid'), CLEAROS_ERROR);
            }
            $mac = $key;
            break;
        }
        if ($mac === NULL)
            throw new Ethers_Not_Found_Exception($hostname, CLEAROS_ERROR);
    }

    /**
     * Returns information from the /etc/ethers file as an array.
     *
     * The array is keyed on MAC address with hostname values.
     *
     * @return array list of ether information
     * @throws Exception
     */

    public function get_ethers()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_CONFIG);
        $contents = $file->get_contents_as_array();

        if (! is_array($contents)) {
            $this->reset_ethers(true);
            $contents = $file->get_contents_as_array();
            if (! is_array($contents))
                $contents = array();
        }

        $ethers = array();
        foreach ($contents as $line) {
            // skip comment lines
            if (preg_match('/^[\s]*#/', $line))
                continue;
            $parts = preg_split('/[\s]+/', $line);
            try {
                if (Network_Utils::is_valid_mac($parts[0]) === FALSE) {
                    throw new Validation_Exception(
                        lang('network_mac_address_is_invalid'), CLEAROS_ERROR);
                }
                if (Network_Utils::is_valid_hostname($parts[1]) === FALSE) {
                    throw new Validation_Exception(
                        lang('network_hostname_is_invalid'), CLEAROS_ERROR);
                }
                $ethers[$parts[0]] = $parts[1];
            } catch (Validation_Exception $e) {
            }
        }

        return $ethers;
    }

    /**
     * Updates hostname for a given MAC address.
     *
     * @param   string $mac MAC address
     * @param   string $hostname hostname
     * @return  void
     * @throws  Exception, Validation_Exception, Ethers_Not_Found_Exception
     */
    
    public function update_ether($mac, $hostname)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (Network_Utils::is_valid_mac($mac) === FALSE) {
            throw new Validation_Exception(
                lang('network_mac_address_is_invalid'), CLEAROS_ERROR);
        }
        if (Network_Utils::is_valid_hostname($hostname) === FALSE) {
            throw new Validation_Exception(
                lang('network_hostname_is_invalid'), CLEAROS_ERROR);
        }

        $file = new File(self::FILE_CONFIG);
        $contents = $file->get_contents_as_array();

        $write_out = false;
        foreach ($contents as $key => $line) {
            if (preg_match("/$mac/", $line)) {
                $contents[$key] = "$mac $hostname";
                $write_out = true;
                break;
            }
        }

        if ($write_out)
            $file->dump_contents_from_array($contents);
        else
            throw new Ethers_Not_Found_Exception($mac, CLEAROS_ERROR);
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////
}
