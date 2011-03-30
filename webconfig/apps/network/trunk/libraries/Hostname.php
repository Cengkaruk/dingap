<?php

/**
 * Hostname class.
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

clearos_load_language('network');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Engine as Engine;
use \clearos\apps\base\File as File;
use \clearos\apps\base\Shell as Shell;
use \clearos\apps\network\Network_Utils as Network_Utils;

clearos_load_library('base/Engine');
clearos_load_library('base/File');
clearos_load_library('base/Shell');
clearos_load_library('network/Network_Utils');

// Exceptions
//-----------

use \clearos\apps\base\Validation_Exception as Validation_Exception;

clearos_load_library('base/Validation_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Hostname class.
 *
 * @category   Apps
 * @package    Network
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2002-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network/
 */

class Hostname extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // M E M B E R S
    ///////////////////////////////////////////////////////////////////////////////

    const FILE_CONFIG = '/etc/sysconfig/network';
    const CMD_HOSTNAME = '/bin/hostname';

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
     * Returns hostname from the gethostname system call.
     *
     * @return string hostname
     * @throws Exception
     */

    public function get_actual()
    {
        clearos_profile(__METHOD__, __LINE__);

        $shell = new Shell();

        $options['validate_output'] = TRUE;
        $shell->execute(self::CMD_HOSTNAME, '', FALSE, $options);

        return $shell->get_first_output_line();
    }


    /**
     * Returns hostname from configuration file.
     *
     * @return string hostname
     * @throws Exception
     */

    public function get()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_CONFIG);

        $hostname = $file->lookup_value('/^HOSTNAME=/');
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
     * @throws Exception
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
            return FALSE;

        return TRUE;
    }

    /**
     * Sets hostname.
     *
     * Hostname must have at least one period.
     *
     * @param string $hostname hostname
     *
     * @return  void
     * @throws  Exception, Validation_Exception
     */

    public function set($hostname)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validate
        //---------

        if (Network_Utils::is_valid_hostname($hostname) === FALSE) {
            throw new Validation_Exception(
                lang('network_hostname_is_invalid'), CLEAROS_ERROR);
        }

        // Update tag if it exists
        //------------------------

        $file = new File(self::FILE_CONFIG);

        $match = $file->replace_lines('/^HOSTNAME=/', "HOSTNAME=\"$hostname\"\n");

        // If tag does not exist, add it
        //------------------------------

        if (! $match)
            $file->add_lines("HOSTNAME=\"$hostname\"\n");

        // Run hostname command...
        //------------------------

        $shell = new Shell();
        $shell->execute(self::CMD_HOSTNAME, $hostname, TRUE);
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   R O U T I N E S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validates a hostname.
     *
     * @param string $hostname hostname
     *
     * @return string error message if hostname is invalid
     */

    public function validate_hostname($hostname)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! Network_Utils::is_valid_hostname($hostname))
            return lang('network_hostname_is_invalid');
    }
}
