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

clearos_load_language('base');
clearos_load_language('date');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Engine as Engine;
use \clearos\apps\base\File as File;
use \clearos\apps\base\Shell as Shell;
use \clearos\apps\cron\Cron as Cron;
use \clearos\apps\network\Network_Utils as Network_Utils;

clearos_load_library('base/Engine');
clearos_load_library('base/File');
clearos_load_library('base/Shell');
clearos_load_library('cron/Cron');
clearos_load_library('network/Network_Utils');

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\File_No_Match_Exception as File_No_Match_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;
use \clearos\apps\cron\Cron_Configlet_Not_Found_Exception as Cron_Configlet_Not_Found_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('base/File_No_Match_Exception');
clearos_load_library('base/Validation_Exception');
clearos_load_library('cron/Cron_Configlet_Not_Found_Exception');

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

class NtpTime extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const FILE_CROND = "app-ntp";
    const FILE_CONFIG = "/etc/system/ntpdate";
    const DEFAULT_SERVER = "time.clearsdn.com";
    const DEFAULT_CRONTAB_TIME = "2 2 * * *";
    const COMMAND_NTPDATE = "/usr/sbin/ntpdate";
    const COMMAND_CRON = "/usr/sbin/timesync";

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * NtpTime constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Deletes the cron entry for auto-synchronizing with an NTP server.
     *
     * @return void
     * @throws Engine_Exception
     */

    public function delete_auto_sync()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $crontab = new Cron();
            if ($crontab->exists_crond_configlet(self::FILE_CROND))
                $crontab->DeleteCrondConfiglet(self::FILE_CROND);
        } catch (Engine_Exception $e) {
            throw new Engine_Exception($e->get_message(), CLEAROS_WARNING);
        }
    }

    /**
     * Returns the time server to be used on the system.
     *
     * This will return the default self::DEFAULT_SERVER if a 
     * time server has not been specified.
     *
     * @return string current auto-sync NTP server
     * @throws Engine_Exception
     */

    public function get_auto_sync_server()
    {
        clearos_profile(__METHOD__, __LINE__);

        $time_server = "";

        try {
            $config = new File(self::FILE_CONFIG);
            $time_server = $config->lookup_value("/^ntp_syncserver\s*=\s*/");
        } catch (File_No_Match_Exception $e) {
            $time_server = NtpTime::DEFAULT_SERVER;
        } catch (Engine_Exception $e) {
            throw new Engine_Exception($e->get_message(), CLEAROS_WARNING);
        }

        if (! $time_server)
            $time_server = NtpTime::DEFAULT_SERVER;

        /*
        // FIXME
        $network = new Network_Utils();

        if (!($network->IsValidHostname($time_server) || $network->IsValidIp($time_server)))
            throw new Engine_Exception(NTPTIME_LANG_ERRMSG_TIMESERVER_INVALID, CLEAROS_ERROR);
        */

        return $time_server;
    }

    /**
     * Returns the status of the auto-sync feature.
     *
     * @return boolean TRUE if auto-sync is on
     * @throws Engine_Exception
     */

    public function get_auto_sync_status()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $cron = new Cron();
        } catch (Engine_Exception $e) {
            throw new Engine_Exception($e->get_message(), CLEAROS_ERROR);
        }

        return $cron->exists_crond_configlet(self::FILE_CROND);
    }

    /**
     * Returns the time configuration in the auto-synchronize cron entry. 
     *
     * Returns the default if an entry does not exist.
     *
     * @return string current auto-sync cron time
     * @throws Engine_Exception
     */

    public function get_auto_sync_time()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $crontab = new Cron();
            $contents = $crontab->get_crond_configlet(self::FILE_CROND);
        } catch (Cron_Configlet_Not_Found_Exception $e) {
            return self::DEFAULT_CRONTAB_TIME;
        } catch (Engine_Exception $e) {
            throw new Engine_Exception($e->get_message(), CLEAROS_WARNING);
        }

        $lines = explode("\n", $contents);

        foreach ($lines as $line) {
            $matches = array();

            if (preg_match("/([\d\*]+\s+[\d\*]+\s+[\d\*]+\s+[\d\*]+\s+[\d\*]+\s+)/", $line, $matches))
                return $matches[0];
        }

        throw new Engine_Exception(NTPTIME_LANG_ERRMSG_CRONTIME_INVALID, CLEAROS_WARNING);
    }

    /**
     * Creates a cron file for auto-synchronizng the system clock.
     *
     * The cron_time parameter ist optional -- the system will select
     * a defaults if non is specified.
     *
     * @param string $cron_time crontab time
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_auto_sync($cron_time = self::DEFAULT_CRONTAB_TIME)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validate
        //---------

        $validtime = FALSE;
        $crontab = new Cron();

        try {
            $validtime = $crontab->validate_time($cron_time);
        } catch (Engine_Exception $e) {
            throw new Engine_Exception($e->get_message(), CLEAROS_WARNING);
        }

        if (! $validtime)
            throw new Validation_Exception(NTPTIME_LANG_ERRMSG_CRONTIME_INVALID);

        // Set auto sync
        //--------------

        try {
            $cron = new Cron();

            if ($cron->exists_crond_configlet(self::FILE_CROND))
                $this->delete_auto_sync();

            $payload  = "# Created by API\n";

            if (file_exists(self::COMMAND_CRON))
                $payload .= "$cron_time root " . self::COMMAND_CRON;
            else
                throw new Engine_Exception(LOCALE_LANG_MISSING . " - " . self::COMMAND_CRON, CLEAROS_WARNING);

            $crontab->add_crond_configlet(self::FILE_CROND, $payload);
        } catch (Engine_Exception $e) {
            throw new Engine_Exception($e->get_message(), CLEAROS_WARNING);
        }
    }

    /**
     * Sets the time server to be used on the system.
     *
     * @param string $time_server auto-sync NTP server, if empty the default is set
     *
     * @return boolean TRUE on successful update
     * @throws Engine_Exception
     */

    public function set_auto_sync_server($time_server = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (empty($time_server))
            $time_server = NtpTime::DEFAULT_SERVER;
        
        if ($time_server == $this->get_auto_sync_server())
            return FALSE;

        $error_message = $this->validate_time_server($time_server);

        if ($error_message)
            throw new Engine_Exception($error_message, CLEAROS_WARNING);

        try {
            $config = new File(self::FILE_CONFIG);
            $config->replace_lines("/^ntp_syncserver\s*=\s*/", "ntp_syncserver = {$time_server}\n");
        } catch (Engine_Exception $e) {
            throw new Engine_Exception($e->get_message(), CLEAROS_WARNING);
        }

        return TRUE;
    }

    /**
     * Synchronizes the clock. 
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
            $time_server = $this->get_auto_sync_server();

        // Validate
        //---------

        if ($error_message = $this->validate_time_server($time_server))
            throw new Validation_Exception($error_message);

        // Synchronize
        //------------

        $output = "";

        try {
            $shell = new Shell();

            $options['env'] = "LANG=fr_FR";

            if ($shell->execute(self::COMMAND_NTPDATE, "-u $time_server", TRUE, $options) != 0)
                throw new Engine_Exception(NTPTIME_LANG_ERRMSG_SYNCHRONIZE_FAILED, CLEAROS_ERROR);

            $output = $shell->get_first_output_line();
            $output = preg_replace("/.*offset/", "", $output);
        } catch (Engine_Exception $e) {
            throw new Engine_Exception($e->get_message(), CLEAROS_WARNING);
        }

        return $output;
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   R O U T I N E S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validation routine for time server.
     *
     * @param string $time_server time server
     *
     * @return boolean TRUE if time server is valid
     */

    public function validate_time_server($time_server)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (preg_match("/^([\.\-\w]*)$/", $time_server))
            return '';
        else
            return 'Invalid time server'; // FIXME: localize 
    }
}
