<?php

/**
 * Ethers class.
 *
 * @package ClearOS
 * @subpackage API
 * @author {@link http://www.clearfoundation.com/ ClearFoundation}
 * @license http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @copyright Copyright 2003-2011 ClearFoundation
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

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

use \clearos\apps\base\Engine as Engine;
use \clearos\apps\base\File as File;

clearos_load_library('base/Engine');
clearos_load_library('base/File');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Ethers class.
 *
 * @package ClearOS
 * @subpackage API
 * @author {@link http://www.clearfoundation.com/ ClearFoundation}
 * @license http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @copyright Copyright 2003-2011 ClearFoundation
 */

class Ethers extends Engine
{
    const FILE_CONFIG = '/etc/ethers';

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Ethers constructor.
     */

    public function __construct()
    {
        ClearOsLogger::Profile(__METHOD__, __LINE__);

        parent::__construct();

        $this->ResetEthers();
    }

    /**
     * Create a new /etc/ethers file.
     *
     * @param boolean $force delete the existing file if true
     * @return void
     */

    public function ResetEthers($force = false)
    {
        ClearOsLogger::Profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_CONFIG);

        if ($force == true) {
            if ($file->Exists())
                $file->Delete();
        }

        if (! $file->Exists()) {
            $file->Create('root', 'root', '0644');

            $default  = "# This is an auto-generated file. Please do NOT edit\n";
            $default .= "# Comments are used to aid in maintaining your hosts\n";
            $default .= "# file\n";

            $file->AddLines($default);
        }
    }

    /**
     * Add a MAC/IP pair to the /etc/ethers file.
     *
     * @param string $mac MAC address
     * @param string $ip IP address
     * @return void
     * @throws EngineException, ValidationException
     */

    public function AddEther($mac, $ip)
    {
        ClearOsLogger::Profile(__METHOD__, __LINE__);

        $isvalid = true;
        $network = new Network();

        if (! $network->IsValidMac($mac)) {
            $this->AddValidationError(implode($network->GetValidationErrors(true)), __METHOD__, __LINE__);
            $isvalid = false;
        }

        if (! $network->IsValidIp($ip)) {
            $this->AddValidationError(implode($network->GetValidationErrors(true)), __METHOD__, __LINE__);
            $isvalid = false;
        }

        if (! $isvalid)
            throw new ValidationException(LOCALE_LANG_INVALID);

        $file = new File(self::FILE_CONFIG);

        try {
            $contents = $file->GetContentsAsArray();
        } catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_ERROR);
        }

        // Already exists?
        foreach ($contents as $key => $line) {
            if (preg_match('/' . $mac . '/', $line))
                throw new EngineException(ETHERS_LANG_MAC_ALREADY_EXISTS, COMMON_ERROR);
        }

        // Add
        $contents[] = $mac . ' ' . $ip;
        $file->DumpContentsFromArray($contents);
    }

    /**
     * Delete a MAC/HOSTNAME pair from the /etc/ethers file.
     *
     * @param string $mac MAC address
     * @return void
     */

    public function DeleteEther($mac)
    {
        ClearOsLogger::Profile(__METHOD__, __LINE__);

        if ($this->IsValidMac($mac) === false)
            return;

        $file = new File(self::FILE_CONFIG);
        $contents = $file->GetContentsAsArray();

        $write_out = false;
        foreach ($contents as $key => $line) {
            if (preg_match('/' . $mac . '/', $line)) {
                unset($contents[$key]);
                $write_out = true;
            }
        }

        if ($write_out)
            $file->DumpContentsFromArray($contents);
    }

    /**
     * Returns the HOSTNAME for the given MAC address.
     *
     * @param string $mac MAC address
     * @return string hostname or null
     */

    public function GetHostnameByMac($mac)
    {
        ClearOsLogger::Profile(__METHOD__, __LINE__);

        if ($this->IsValidMac($mac) === false)
            return;

        $ethers = $this->GetEthers();

        if (! isset($ethers[$mac]))
            $ret = null;
        else
            $ret = $ethers[$mac];

        if ($this->IsValidHostname($ret) == false)
            $ret = null;

        return $ret;
    }

    /**
     * Returns information in the /etc/ethers file in an array.
     *
     * The array is indexed on MAC with HOSTNAMEs as values.
     *
     * @return array list of ether information
     */

    public function GetEthers()
    {
        ClearOsLogger::Profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_CONFIG);
        $contents = $file->GetContentsAsArray();

        if (! is_array($contents)) {
            $this->ResetEthers(true);
            $contents = $file->GetContentsAsArray();
            if (! is_array($contents)) {
                throw new EngineException(LOCALE_LANG_ERRMSG_PARSE_ERROR, COMMON_ERROR);
            }
        }

        $ethers = array();
        foreach ($contents as $line) {
            // skip comment lines
            if (preg_match('/^[\s]*#/', $line))
                continue;
            $parts = preg_split('/[\s]+/', $line);
            if ($this->isValidMac($parts[0]) && $parts[1] != '')
                $ethers[$parts[0]] = $parts[1];
        }
        return $ethers;
    }

    /**
     * Returns the MAC address for the given HOSTNAME.
     *
     * @param string $hostname hostname
     * @return string MAC address
     */

    public function GetMacByHostname($hostname)
    {
        ClearOsLogger::Profile(__METHOD__, __LINE__);

        if ($this->IsValidHostname($hostname) == false) {
            $errors = $this->GetValidationErrors();
            throw new EngineException($errors[0], COMMON_ERROR);
        }

        $ethers = $this->GetEthers();
        foreach ($ethers as $mac => $host)
            if (strcasecmp($hostname, $host) == 0)
                return $mac;
        return;
    }

    /**
     * Updates HOSTNAME for a given MAC address.
     *
     * @param string $mac MAC address
     * @param string $hostname hostname
     * @return void
     */
    
    public function UpdateEther($mac, $hostname)
    {
        ClearOsLogger::Profile(__METHOD__, __LINE__);

        if ($this->IsValidMac($mac) === false)
            return;

        if ($this->IsValidHostname($hostname) == false) {
            $errors = $this->GetValidationErrors();
            throw new EngineException($errors[0], COMMON_ERROR);
        }


        $file = new File(self::FILE_CONFIG);
        $contents = $file->GetContentsAsArray();

        $write_out = false;
        foreach ($contents as $key => $line) {
            if (preg_match('/' . $mac . '/', $line)) {
                $contents[$key] = $mac . ' ' . $hostname;
                $write_out = true;
            }
        }

        // Add
        if ($write_out)
            $file->DumpContentsFromArray($contents);
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * @access private
     */

    public function __destruct()
    {
        ClearOsLogger::Profile(__METHOD__, __LINE__);

        parent::__destruct();
    }

}

// vim: syntax=php ts=4
?>
