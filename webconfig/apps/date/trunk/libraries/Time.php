<?php

/**
 * System time manager class.
 *
 * @category   Apps
 * @package    Date
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/date/
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

namespace clearos\apps\date;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = isset($_ENV['CLEAROS_BOOTSTRAP']) ? $_ENV['CLEAROS_BOOTSTRAP'] : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('base');
clearos_load_language('date');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Configuration_File as Configuration_File;
use \clearos\apps\base\Engine as Engine;
use \clearos\apps\base\File as File;
use \clearos\apps\base\Folder as Folder;
use \clearos\apps\base\ShellExec as ShellExec;

clearos_load_library('base/Configuration_File');
clearos_load_library('base/Engine');
clearos_load_library('base/File');
clearos_load_library('base/Folder');
clearos_load_library('base/ShellExec');

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\File_Not_Found_Exception as File_Not_Found_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('base/File_Not_Found_Exception');
clearos_load_library('base/Validation_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * System time manager class.
 *
 * @category   Apps
 * @package    Date
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/date/
 */

class Time extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const COMMAND_HWCLOCK = "/sbin/hwclock";
    const FILE_CONFIG = "/etc/sysconfig/clock";
    const FILE_TIMEZONE = "/etc/localtime";
    const PATH_ZONEINFO = "/usr/share/zoneinfo/posix";

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Time constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Returns the system time (in seconds since Jan 1, 1970).
     *
     * @return integer system time in seconds since Jan 1, 1970
     */

    public function get_time()
    {
        clearos_profile(__METHOD__, __LINE__);

        return time();
    }

    /**
     * Returns the current time zone.
     *
     * @return string current time zone
     * @throws Engine_Exception
     */

    public function get_time_zone()
    {
        clearos_profile(__METHOD__, __LINE__);

        // Sanity check existence of real time zone file
        //----------------------------------------------
        
        $file = new File(self::FILE_TIMEZONE);
        $fileok = FALSE;

        try {
            $fileok = $file->exists();
        } catch (Engine_Exception $e) {
            throw new Engine_Exception($e->get_message(), CLEAROS_ERROR);
        }

        if (! $fileok)
            return '';

        // Check the /etc/sysconfig/clock file for time zone info
        //-------------------------------------------------------

        try {
            $metafile = new Configuration_File(self::FILE_CONFIG);
            $time_zone = $metafile->load();

            if (isset($time_zone['ZONE']))
                return preg_replace("/\"/", "", $time_zone['ZONE']);
        } catch (File_Not_Found_Exception $e) {
            // Not fatal, use methodology below
        } catch (Engine_Exception $e) {
            throw new Engine_Exception($e->get_message(), CLEAROS_ERROR);
        }

        // If time zone is not defined in /etc/sysconfig/clock, try to
        // determine it by comparing /etc/localtime with time zone data
        //--------------------------------------------------------------

        $currentmd5 = md5_file(self::FILE_TIMEZONE);

        try {
            $folder = new Folder(self::PATH_ZONEINFO);
            $zones = $folder->get_recursive_listing();

            foreach ($zones as $zone) {
                if ($currentmd5 == md5_file(self::PATH_ZONEINFO . "/$zone"))
                    return "$zone";
            }
        } catch (Engine_Exception $e) {
            throw new Engine_Exception($e->get_message(), CLEAROS_ERROR);
        }

        // Ugh -- sometimes the time zone files change.
        try {
            $currenttz = date_default_timezone_get();
            $this->set_time_zone($currenttz);
            return $currenttz;
        } catch (Engine_Exception $e) {
            throw new Engine_Exception(TIME_LANG_ERRMSG_TIMEZONE_INVALID, CLEAROS_ERROR);
        }
    }

    /**
     * Returns a list of available time zones on the system.
     * 
     * @return array a list of available time zones
     * @throws Engine_Exception
     */

    public function get_time_zone_list()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $folder = new Folder(self::PATH_ZONEINFO);
            $zones = $folder->get_recursive_listing();
        } catch (Engine_Exception $e) {
            throw new Engine_Exception($e->get_message(), CLEAROS_ERROR);
        }

        $zonelist = array();

        foreach ($zones as $zone)
            $zonelist[] = $zone;

        return $zonelist;
    }

    /**
     * Sets the hardware clock to the current system time.
     * 
     * @return void
     * @throws Engine_Exception
     */

    public function send_system_to_hardware()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $shell = new ShellExec();
            if ($shell->execute(self::COMMAND_HWCLOCK, "--systohc", TRUE) != 0)
                throw new Engine_Exception($shell->get_first_output_line(), CLEAROS_ERROR);
        } catch (Engine_Exception $e) {
            throw new Engine_Exception($e->get_message(), CLEAROS_ERROR);
        }
    }

    /**
     * Sets the current timzeone.
     *
     * @param string $time_zone time zone
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_time_zone($time_zone)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validate
        //---------

        $error_message = $this->validate_time_zone($time_zone);

        if ($error_message)
            throw new Validation_Exception($error_message);

        // Set /etc/localtime
        //-------------------

        try {
            $file = new File(self::PATH_ZONEINFO . "/" . $time_zone);
            $file->copy_to(self::FILE_TIMEZONE);
        } catch (Engine_Exception $e) {
            throw new Engine_Exception($e->get_message(), CLEAROS_ERROR);
        }

        // Set meta information in /etc/sysconfig/clock
        //---------------------------------------------

        try {
            $info = new File(self::FILE_CONFIG);

            if ($info->exists()) {
                $info->replace_lines("/^ZONE=/", "ZONE=\"$time_zone\"\n");
            } else {
                $info->create("root", "root", "0644");
                $info->add_lines("ZONE=\"$time_zone\"\n");
            }
        } catch (Engine_Exception $e) {
            throw new Engine_Exception($e->get_message(), CLEAROS_ERROR);
        }
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   R O U T I N E S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validates time zone.
     *
     * @param string $time_zone time zone
     *
     * @return boolean TRUE if time zone is valid
     * @throws Engine_Exception
     */

    public function validate_time_zone($time_zone)
    {
        clearos_profile(__METHOD__, __LINE__);

        $error_message = '';

        if (!$time_zone) {
            $error_message = 'Time zone not specified'; // FIXME localize
        } else {
            try {
                $file = new File(self::PATH_ZONEINFO . "/" . $time_zone);

                if (! $file->exists())
                    $error_message = 'Invalid time zone'; // FIXME localize
            } catch (Engine_Exception $e) {
                // FIXME: what should we do here... exception or error message?
                throw new Engine_Exception($e->get_message(), CLEAROS_ERROR);
            }
        }

        return $error_message;
    }
}
