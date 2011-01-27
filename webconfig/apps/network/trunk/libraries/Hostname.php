<?php

/**
 * Hostname class.
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

clearos_load_library('base/Engine');
clearos_load_library('base/File');
clearos_load_library('network/Network');

///////////////////////////////////////////////////////////////////////////////
// E X C E P T I O N  C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Hostname exception.
 *
 * @category    Apps
 * @package     Network
 * @subpackage  Exception
 * @author      {@link http://www.clearfoundation.com/ ClearFoundation}
 * @copyright   Copyright 2002-2010 ClearFoundation
 * @license     http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link        http://www.clearfoundation.com/docs/developer/apps/network/
 */

class Hostname_Exception extends Engine_Exception
{
    /**
     * Hostname_Exception constructor.
     *
     * @param string    $message    error message
     * @param int       $code       error code
     */

    public function __construct($message, $code)
    {
        parent::__construct($message, $code);
    }
}

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Hostname class.
 *
 * @category    Apps
 * @package     Network
 * @subpackage  Libraries
 * @author      {@link http://www.clearfoundation.com/ ClearFoundation}
 * @copyright   Copyright 2002-2010 ClearFoundation
 * @license     http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link        http://www.clearfoundation.com/docs/developer/apps/network/
 */

class Hostname extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // M E M B E R S
    ///////////////////////////////////////////////////////////////////////////////

    const FILE_CONFIG = "/etc/sysconfig/network";
    const CMD_HOSTNAME = "/bin/hostname";

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Hostname constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Returns host name from the gethostname system call.
     *
     * @return string host name
     * @throws Hostname_Exception
     */

    public function get_actual()
    {
        clearos_profile(__METHOD__, __LINE__);

        $shell = new Shell_Exec();

        try {
            $exitcode = $shell->execute(self::CMD_HOSTNAME, '', false);
        } catch (Exception $e) {
            throw new Hostname_Exception(
                clearos_exception_message($e), CLEAROS_ERROR
            );
        }

        $output = $shell->get_output();

        // TODO: locale fixes... ask Pete.
        if (! isset($output[0]))
            throw new Hostname_Exception(LOCALE_LANG_ERRMSG_WEIRD, CLEAROS_ERROR);
        else if ($exitcode != 0)
            throw new Hostname_Exception($output[0], CLEAROS_ERROR);
            
        return $output[0];
    }


    /**
     * Returns host name from configuration file.
     *
     * @return string hostname
     * @throws Hostname_Exception
     */

    public function get()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_CONFIG);

        try {
            $hostname = $file->lookup_value("/^HOSTNAME=/");
        } catch (Exception $e) {
            throw new Hostname_Exception(
                clearos_exception_message($e), CLEAROS_ERROR
            );
        }

        $hostname = preg_replace('/"/', '', $hostname);

        return $hostname;
    }


    /**
     * Returns configured domain name.
     *
     * If hostname is two parts or less (eg example.com
     * or example), we just return the hostname.  If hostname has more than
     * two parts (eg www.example.com or www.eastcoast.example.com) it
     * strips the first part.
     *
     * @return string domain name
     * @throws Hostname_Exception
     */

    public function get_domain()
    {
        clearos_profile(__METHOD__, __LINE__);

        $hostname = $this->get();

        if (substr_count($hostname, '.') < 2)
            return $hostname;

        $domain = preg_replace('/^([\w\-]*)\./', '', $hostname);

        return $domain;
    }

    /**
     * Returns true if configured hostname can be resolved.
     *
     * @return boolean true if configured hostname is resolvable
     */

    public function is_resolvable()
    {
        clearos_profile(__METHOD__, __LINE__);

        $hostname = $this->get_actual() . '.';

        $retval = gethostbyname($hostname);

        if ($retval == $hostname)
            return false;

        return true;
    }

    /**
     * Sets host name.
     *
     * Hostname must have at least one period.
     *
     * @param   string $hostname hostname
     * @return  void
     * @throws  Hostname_Exception, Validation_Exception
     */

    public function set($hostname)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validate
        //---------

        $network = new Network_Utils();

        Validation_Exception::is_valid($network->validate_hostname($hostname));

        // Update tag if it exists
        //------------------------

        $file = new File(self::FILE_CONFIG);

        try {
            $match = $file->replace_lines('/^HOSTNAME=/', "HOSTNAME=\"$hostname\"\n");
        } catch (Exception $e) {
            throw new Hostname_Exception(
                clearos_exception_message($e), CLEAROS_ERROR
            );
        }

        // If tag does not exist, add it
        //------------------------------

        if (! $match) {
            try {
                $file->add_lines("HOSTNAME=\"$hostname\"\n");
            } catch (Exception $e) {
                throw new Hostname_Exception(
                    clearos_exception_message($e), CLEAROS_ERROR
                );
            }
        }

        // Run hostname command...
        //------------------------

        $shell = new Shell_Exec();

        try {
            $exitcode = $shell->execute(self::CMD_HOSTNAME, $hostname, true);
        } catch (Exception $e) {
            throw new Hostname_Exception(
                clearos_exception_message($e), CLEAROS_ERROR
            );
        }

        // TODO: what about this -- get_first_output_line as an exception's message?
        if ($exitcode != 0) {
            throw new Hostname_Exception(
                $shell->get_first_output_line(), CLEAROS_ERROR
            );
        }
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////
}
