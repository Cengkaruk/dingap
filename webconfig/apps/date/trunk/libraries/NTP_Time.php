<?php

/**
 * NTP time class.
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

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('date');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Engine as Engine;
use \clearos\apps\base\File as File;
use \clearos\apps\base\Shell as Shell;
use \clearos\apps\network\Network_Utils as Network_Utils;
use \clearos\apps\tasks\Cron as Cron;

clearos_load_library('base/Engine');
clearos_load_library('base/File');
clearos_load_library('base/Shell');
clearos_load_library('network/Network_Utils');
clearos_load_library('tasks/Cron');

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\File_No_Match_Exception as File_No_Match_Exception;
use \clearos\apps\base\File_Not_Found_Exception as File_Not_Found_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;
use \clearos\apps\tasks\Cron_Configlet_Not_Found_Exception as Cron_Configlet_Not_Found_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('base/File_No_Match_Exception');
clearos_load_library('base/File_Not_Found_Exception');
clearos_load_library('base/Validation_Exception');
clearos_load_library('tasks/Cron_Configlet_Not_Found_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * NTP time class.
 *
 * @category   Apps
 * @package    Date
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/date/
 */

class NTP_Time extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const FILE_CROND = 'app-date';
    const FILE_CONFIG = '/etc/clearos/date';
    const DEFAULT_SERVER = 'time.clearsdn.com';
    const DEFAULT_CRONTAB_TIME = '2 2 * * *';
    const COMMAND_NTPDATE = '/usr/sbin/ntpdate';
    const COMMAND_CRON = '/usr/sbin/timesync';

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * NTP_Time constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Disables automatic time synchronization schedule.
     *
     * @return void
     * @throws Engine_Exception
     */

    public function disable_schedule()
    {
        clearos_profile(__METHOD__, __LINE__);

        $cron = new Cron();

        if ($cron->exists_configlet(self::FILE_CROND))
            $cron->delete_configlet(self::FILE_CROND);
    }

    /**
     * Returns the time server used for synchronization.
     *
     * This will return the default self::DEFAULT_SERVER if a 
     * time server has not been specified.
     *
     * @return string NTP server for synchronization
     * @throws Engine_Exception
     */

    public function get_time_server()
    {
        clearos_profile(__METHOD__, __LINE__);

        $time_server = '';

        try {
            $config = new File(self::FILE_CONFIG);
            $time_server = $config->lookup_value('/^ntp_server\s*=\s*/');
        } catch (File_Not_Found_Exception $e) {
            // Not fatal
        } catch (File_No_Match_Exception $e) {
            // Not fatal
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e));
        }

        if (! $time_server)
            $time_server = self::DEFAULT_SERVER;

        return $time_server;
    }

    /**
     * Returns the schedule status for time synchronization.
     *
     * @return boolean TRUE if system is scheduled to synchronize
     * @throws Engine_Exception
     */

    public function get_schedule_status()
    {
        clearos_profile(__METHOD__, __LINE__);

        $cron = new Cron();
        $exists = $cron->exists_configlet(self::FILE_CROND);

        return $exists;
    }

    /**
     * Returns the time synchronization schedule.
     *
     * @return string current time synchronization schedule
     * @throws Engine_Exception
     */

    public function get_schedule()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $cron = new Cron();
            $contents = $cron->get_configlet(self::FILE_CROND);
        } catch (Cron_Configlet_Not_Found_Exception $e) {
            return self::DEFAULT_CRONTAB_TIME;
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e));
        }

        $lines = explode("\n", $contents);

        foreach ($lines as $line) {
            $matches = array();

            if (preg_match('/([\d\*]+\s+[\d\*]+\s+[\d\*]+\s+[\d\*]+\s+[\d\*]+\s+)/', $line, $matches))
                return $matches[0];
        }

        throw new Engine_Exception(lang('date_time_synchronization_schedule_is_invalid'));
    }

    /**
     * Sets automatic time synchronization schedule.
     *
     * When this feature is set, time will by synchronized via NTP
     * on a regular basis.
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_schedule()
    {
        clearos_profile(__METHOD__, __LINE__);

        $payload = self::DEFAULT_CRONTAB_TIME . ' root ' . self::COMMAND_CRON;

        $cron = new Cron();

        if ($cron->exists_configlet(self::FILE_CROND))
            $cron->delete_configlet(self::FILE_CROND);

        $cron->add_configlet(self::FILE_CROND, $payload);
    }

    /**
     * Sets the time server to be used by NTP.
     *
     * @param string $time_server time server, default will be used if empty
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_time_server($time_server = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (empty($time_server))
            $time_server = self::DEFAULT_SERVER;
        
        Validation_Exception::is_valid($this->validate_time_server($time_server));

        $config = new File(self::FILE_CONFIG);

        if ($config->exists()) {
            if ($config->replace_lines("/^ntp_server\s*=\s*/", "ntp_server = {$time_server}\n") === 0)
                $config->add_lines("ntp_server = $time_server\n");
        } else {
            $config->create('root', 'root', '0644');
            $config->add_lines("ntp_server = $time_server\n");
        }
    }

    /**
     * Synchronizes the clock with NTP server.
     *
     * @param string $time_server time server (optional)
     *
     * @return string offset time
     * @throws Engine_Exception, Validation_Exception
     */

    public function synchronize($time_server = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (is_null($time_server))
            $time_server = $this->get_time_server();

        Validation_Exception::is_valid($this->validate_time_server($time_server));

        $options['env'] = 'LANG=en_US';

        $shell = new Shell();

        $shell->execute(self::COMMAND_NTPDATE, "-u $time_server", TRUE, $options);

        $output = $shell->get_first_output_line();
        $output = preg_replace('/.*offset/', '', $output);
        $output = preg_replace('/\s*sec/', '', $output);

        return trim($output);
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   R O U T I N E S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validation routine for time server.
     *
     * @param string $time_server time server
     *
     * @return string error message if time server is invalid
     */

    public function validate_time_server($time_server)
    {
        clearos_profile(__METHOD__, __LINE__);

        $network_utils = new Network_Utils();

        if (! $network_utils->is_valid_hostname($time_server))
            return lang('date_time_server_is_invalid');
    }
}
