<?php

/**
 * Cron class.
 *
 * @category   Apps
 * @package    Cron
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/cron/
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

namespace clearos\cron;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = isset($_ENV['CLEAROS_BOOTSTRAP']) ? $_ENV['CLEAROS_BOOTSTRAP'] : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('base');

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

use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\File_Not_Found_Exception as File_Not_Found_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;
use \clearos\apps\cron\Cron_Configlet_Not_Found_Exception as Cron_Configlet_Not_Found_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('base/File_Not_Found_Exception');
clearos_load_library('base/Validation_Exception');
clearos_load_library('cron/Cron_Configlet_Not_Found_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Cron class.
 *
 * @category   Apps
 * @package    Cron
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/cron/
 */

class Cron extends Daemon
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const FILE_CRONTAB = "/etc/crontab";
    const PATH_CROND = "/etc/cron.d";

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Cron constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        parent::__construct("cronie");
    }

    /**
     * Add a configlet to cron.d.
     *
     * @param string $name    configlet name
     * @param string $payload valid crond payload
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function add_configlet($name, $payload)
    {
        clearos_profile(__METHOD__, __LINE__);

        // TODO -- validate payload

        try {
            $file = new File(self::PATH_CROND . "/" . $name, TRUE);

            if ($file->exists())
                throw new Validation_Exception(FILE_LANG_ERRMSG_EXISTS . " - " . $name);

            $file->create("root", "root", "0644");

            $file->add_lines("$payload\n");

        } catch (Engine_Exception $e) {
            throw new Engine_Exception($e->get_message(), CLEAROS_ERROR);
        }
    }

    /**
     * Add a configlet to cron.d.
     * 
     * @param string  $name         configlet name
     * @param integer $minute       minute of the day
     * @param integer $hour         hour of the day
     * @param integer $day_of_month day of the month
     * @param integer $month        month
     * @param integer $day_of_week  day of week
     * @param string  $user         user that will run cron command
     * @param string  $command      command
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function add_configlet_by_parts($name, $minute, $hour, $day_of_month, $month, $day_of_week, $user, $command)
    {
        clearos_profile(__METHOD__, __LINE__);

        // TODO: validate variables

        try {
            $file = new File(self::PATH_CROND . "/" . $name, TRUE);

            if ($file->exists())
                throw new Validation_Exception(FILE_LANG_ERRMSG_EXISTS . " - " . $name);

            $file->create("root", "root", "0644");

            $file->add_lines("$minute $hour $day_of_month $month $day_of_week $user $command\n");
        } catch (Engine_Exception $e) {
            throw new Engine_Exception($e->get_message(), CLEAROS_ERROR);
        }
    }

    /**
     * Get contents of a cron.d configlet.
     *
     * @param string $name configlet
     *
     * @return string contents of a cron.d file
     * @throws Cron_Configlet_Not_Found_Exception, Engine_Exception, Validation_Exception
     */

    public function get_configlet($name)
    {
        clearos_profile(__METHOD__, __LINE__);

        // TODO: validate filename, do not allow .. or leading /

        $contents = "";

        try {
            $file = new File(self::PATH_CROND . "/" . $name, TRUE);
            $contents = $file->get_contents();
        } catch (File_Not_Found_Exception $e) {
            throw new Cron_Configlet_Not_Found_Exception($e->get_message(), CLEAROS_INFO);
        } catch (Engine_Exception $e) {
            throw new Engine_Exception($e->get_message(), CLEAROS_ERROR);
        }

        return $contents;
    }

    /**
     * Deletes cron.d configlet.
     *
     * @param string $name cron.d configlet
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function delete_configlet($name)
    {
        clearos_profile(__METHOD__, __LINE__);

        // TODO: validate filename, do not allow .. or leading /

        try {
            $file = new File(self::PATH_CROND . "/" . $name, TRUE);

            if (! $file->exists())
                throw new Validation_Exception(FILE_LANG_ERRMSG_NOTEXIST . " - " . $name);

            $file->delete();
        } catch (Engine_Exception $e) {
            throw new Engine_Exception($e->get_message(), CLEAROS_ERROR);
        }
    }

    /**
     * Checks to see if cron.d configlet exists.
     *
     * @param string $name configlet
     *
     * @return boolean TRUE if file exists
     * @throws Engine_Exception, Validation_Exception
     */

    public function exists_configlet($name)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $file = new File(self::PATH_CROND . "/" . $name, TRUE);

            if ($file->exists())
                return TRUE;
            else
                return FALSE;
        } catch (Engine_Exception $e) {
            throw new Engine_Exception($e->get_message(), CLEAROS_ERROR);
        }
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N  M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validation routine for crontab time.
     *
     * @param string $time crontab time
     *
     * @return boolean TRUE if time entry is valid
     */

    public function validate_time($time)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Could do more validation here...

        $time = preg_replace("/\s+/", " ", $time);

        $parts = explode(" ", $time);

        if (sizeof($parts) != 5)
            return FALSE;

        return TRUE;
    }
}
