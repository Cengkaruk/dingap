<?php

/**
 * ProFTPd class.
 *
 * @category   Apps
 * @package    FTP
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/ftp/
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

namespace clearos\apps\ftp;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('ftp');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Daemon as Daemon;
use \clearos\apps\base\File as File;

clearos_load_library('base/Daemon');
clearos_load_library('base/File');

// Exceptions
//-----------

use \clearos\apps\base\Validation_Exception as Validation_Exception;

clearos_load_library('base/Validation_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * ProFTPd class.
 *
 * @category   Apps
 * @package    FTP
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/ftp/
 */

class ProFTPd extends Daemon
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const FILE_CONFIG = '/etc/proftpd.conf';

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * ProFTPd constructor.
     */

    function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        parent::__construct('proftpd');
    }

    /**
     * Returns max instances.
     *
     * @return integer max instances
     * @throws Engine_Exception
     */

    public function get_max_instances()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_CONFIG);

        $retval = $file->lookup_value("/^MaxInstances\s+/i");

        return $retval;
    }

    /**
     * Returns port number.
     *
     * @return integer port
     * @throws Engine_Exception
     */

    public function get_port()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_CONFIG);

        $retval = $file->lookup_value("/^Port\s+/i");

        return $retval;
    }

    /**
     * Returns server name.
     *
     * @return string server name
     * @throws Engine_Exception
     */

    public function get_server_name()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_CONFIG);

        $retval = $file->lookup_value("/^ServerName\s+/i");
        $retval = preg_replace("/\"/", "", $retval);

        return $retval;
    }

    /**
     * Sets max instances.
     *
     * @param integer $max_instances max instances
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_max_instances($max_instances)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_max_instances($max_instances));

        $file = new File(self::FILE_CONFIG);

        $match = $file->replace_lines("/^MaxInstances\s+/i", "MaxInstances $max_instances\n");

        if (! $match)
            $file->add_lines_after("MaxInstances $max_instances\n", "/^[^#]/");
    }

    /**
     * Sets port number.
     *
     * @param integer $port port
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_port($port)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_port($port));

        $file = new File(self::FILE_CONFIG);

        $match = $file->replace_lines("/^Port\s+/i", "Port $port\n");

        if (! $match)
            $file->add_lines_after("Port $port\n", "/^[^#]/");
    }

    /**
     * Sets server name.
     *
     * @param string $server_name server name
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_server_name($server_name)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_server_name($server_name));

        $file = new File(self::FILE_CONFIG);

        $match = $file->replace_lines("/^ServerName\s+/i", "ServerName \"$server_name\"\n");

        if (! $match)
            $file->add_lines_after("ServerName \"$server_name\"\n", "/^[^#]/");
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   R O U T I N E S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validation routine for server_name
     *
     * @param string $server_name server name
     *
     * @return boolean TRUE if server_name is valid
     */

    public function validate_server_name($server_name)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! preg_match("/^[A-Za-z0-9\.\- ]+$/", $server_name))
            return lang('ftp_server_name_is_invalid');
    }

    /**
     * Validation routine for max_instances
     *
     * @param string $max_instances max instances
     *
     * @return boolean TRUE if max_instances is valid
     */

    public function validate_max_instances($max_instances)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! preg_match("/^[0-9]+$/", $max_instances))
            return lang('ftp_maximum_instances_is_invalid');
    }


    /**
     * Validation routine for port
     *
     * @param integer $port port
     *
     * @return boolean TRUE if port is valid
     */

    public function validate_port($port)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! preg_match("/^[0-9]+$/", $port))
            return lang('ftp_port_is_invalid');
    }
}
