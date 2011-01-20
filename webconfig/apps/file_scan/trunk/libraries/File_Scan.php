<?php

/**
 * Folder manipulation class.
 *
 * @category   Apps
 * @package    File_Scan
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2006-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/file_scan/
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

namespace clearos\apps\file_scan;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('file_scan');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Daemon as Daemon;
use \clearos\apps\base\Engine as Engine;
use \clearos\apps\base\File as File;
use \clearos\apps\base\Folder as Folder;
use \clearos\apps\base\Shell as Shell;
use \clearos\apps\cron\Cron as Cron;

clearos_load_library('base/Daemon');
clearos_load_library('base/Engine');
clearos_load_library('base/File');
clearos_load_library('base/Folder');
clearos_load_library('base/Shell');
clearos_load_library('cron/Cron');

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\Folder_Not_Found_Exception as Folder_Not_Found_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('base/Folder_Not_Found_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * File_Scan base class.
 *
 * @category   Apps
 * @package    File_Scan
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2006-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/file_scan/
 */

class File_Scan extends Engine
{
    ///////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////

    // killall command
    const CMD_KILLALL = '/usr/bin/killall';

    // Antivirus scanner (wrapper)
    const FILE_AVSCAN = 'avscan.php';

    // List of directories to scan for viruses.
    const FILE_CONFIG = '/etc/avscan.conf';

    // Filename of instance (PID) lock file
    const FILE_LOCKFILE = '/var/run/avscan.pid';

    // Location of ClamAV scanner
    const FILE_CLAMSCAN = '/usr/bin/clamscan';

    // Location of scanner state/status file
    const FILE_STATE = '/tmp/.avscan.state';

    // Locating of quarantine directory
    const PATH_QUARANTINE = '/var/lib/quarantine';

    // Status
    const STATUS_IDLE = 0;    
    const STATUS_SCANNING = 1;    
    const STATUS_INTERRUPT = 2;    

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    public $state = array();

    ///////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////

    /**
     * Constructor.
     *
     * @return object
     */

    public function __construct() 
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->reset_state();
    }

    /**
     * Adds directory to scan list.
     *
     * @param string $dir Directory to scan
     *
     * @throws Engine_Exception
     * @return void
     */

    public function add_directory($dir)
    {
        clearos_profile(__METHOD__, __LINE__);

        // TODO: Wrong, should be using File class
        if (!file_exists($dir))
            throw new Engine_Exception(ANTIVIRUS_LANG_DIR_NOT_FOUND, CLEAROS_ERROR);

        $dirs = $this->get_directories();

        if (count($dirs) && in_array($dir, $dirs))
            throw new Engine_Exception(ANTIVIRUS_LANG_DIR_EXISTS, CLEAROS_ERROR);

        $dirs[] = $dir; sort($dirs);

        $file = new File(File_Scan::FILE_CONFIG);

        if (!$file->exists()) {
            try {
                $file->Create('root', 'root', '0644');
            } catch (Engine_Exception $e) {
                throw new Engine_Exception($e->get_message(), CLEAROS_ERROR);
            }
        }

        try {
            $file->dump_contents_from_array($dirs);
        } catch (Engine_Exception $e) {
            throw new Engine_Exception($e->get_message(), CLEAROS_ERROR);
        }
    }

    /**
     * Deletes a quarantined virus.
     *
     * @param string $hash MD5 hash of virus filename to delete
     *
     * @return void
     * @throws Engine_Exception
     */

    public function delete_quarantined_virus($hash)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $nfo = new File(File_Scan::PATH_QUARANTINE . "/$hash.nfo", TRUE);
            $nfo->Delete();

            $dat = new File(File_Scan::PATH_QUARANTINE . "/$hash.dat", TRUE);
            $dat->Delete();
        } catch (Engine_Exception $e) {
            throw new Engine_Exception($e->get_message(), CLEAROS_ERROR);
        }
    }

    /**
     * Deletes a virus.
     *
     * @param string $hash MD5 hash of virus filename to delete
     *
     * @throws Engine_Exception
     * @return void
     */

    public function delete_virus($hash)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!file_exists(File_Scan::FILE_STATE)) {
            throw new Engine_Exception(ANTIVIRUS_LANG_STATE_ERROR, CLEAROS_ERROR);
        }

        // XXX: Here we use fopen rather than the File class.  This is because the File
        // class provides us with no way to do file locking (flock).  The state file
        // is therefore owned by webconfig so that we can manipulate it's contents.
        if (!($fh = @fopen(File_Scan::FILE_STATE, 'a+'))) {
            throw new Engine_Exception(ANTIVIRUS_LANG_STATE_ERROR, CLEAROS_ERROR);
        }

        if ($this->unserialize_state($fh) === FALSE) {
            fclose($fh);
            throw new Engine_Exception(ANTIVIRUS_LANG_STATE_ERROR, CLEAROS_ERROR);
        }

        if (!isset($this->state['virus'][$hash])) {
            throw new Engine_Exception(ANTIVIRUS_LANG_FILE_NOT_FOUND, CLEAROS_ERROR);
        }

        try {
            $virus = new File($this->state['virus'][$hash]['filename'], TRUE);
            $virus->Delete();
        } catch (Engine_Exception $e) {
            throw new Engine_Exception($e->get_message(), CLEAROS_ERROR);
        }

        // Update state file, delete virus
        unset($this->state['virus'][$hash]);

        $this->serialize_state($fh);
    }

    /**
     * Enables antivirus definition updates.
     *
     * @return void
     * @throws Engine_Exception
     */

    public function enable_updates()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $freshclam = new Daemon("freshclam");
            $freshclam->set_boot_state(TRUE);
            $freshclam->set_running_state(TRUE);
        } catch (Engine_Exception $e) {
            throw new Engine_Exception($e->get_message(), CLEAROS_ERROR);
        }
    }

    /**
     * Returns array of directories configured to scan for viruses.
     *
     * @return array of directory names
     * @throws Engine_Exception
     */

    public function get_directories()
    {
        clearos_profile(__METHOD__, __LINE__);

        $dirs = array();
        $fh = @fopen(File_Scan::FILE_CONFIG, 'r');

        if(!$fh) return $dirs;

        while (!feof($fh)) {
            $dir = chop(fgets($fh, 4096));
            // TODO: Wrong, should be using File class
            if (strlen($dir) && file_exists($dir)) $dirs[] = $dir;
        }

        fclose($fh);
        sort($dirs);

        return $dirs;
    }

    /**
     * Returns array of preset directories.
     *
     * @return array of human directory names keyed by filessytem directory name
     */

    public function get_directory_presets()
    {
        clearos_profile(__METHOD__, __LINE__);

        $AVDIRS = array();
        
        include 'File_Scan.inc.php';

        $dirs = $AVDIRS;

        foreach ($dirs as $dir => $label) {
            // TODO: Wrong, should be using File class
            if (!file_exists($dir)) unset($dirs[$dir]);
        }

        return $dirs;
    }

    /**
     * Returns information on the scan.
     *
     * @return array of the scanner's status and information
     * @throws Engine_Exception
     */

    public function get_info()
    {
        clearos_profile(__METHOD__, __LINE__);

        // Unserialize the scanner state file (if it exists)
        //--------------------------------------------------

        if (file_exists(File_Scan::FILE_STATE)) {
            if (($fh = @fopen(File_Scan::FILE_STATE, 'r'))) {
                $this->unserialize_state($fh);
                fclose($fh);
            }
        }

        // Set the last run timestamp if available
        //----------------------------------------

        if ($this->state['timestamp'] != 0)
            $info['last_run'] = strftime('%D %T', $this->state['timestamp']);
        else
            $info['last_run'] = lang('base_unknown');

        // Determine the scanner's status
        //-------------------------------

        $info['state'] = File_Scan::STATUS_IDLE;
        $info['state_text'] = lang('file_scan_idle');

        if (file_exists(File_Scan::FILE_LOCKFILE)) {
            if (($fh = @fopen(File_Scan::FILE_LOCKFILE, 'r'))) {
                list($pid) = fscanf($fh, '%d');

                if (!file_exists("/proc/$pid")) {
                    $info['state'] = File_Scan::STATUS_INTERRUPT;
                    $info['state_text'] = lang('file_scan_interrupted');
                } else {
                    $info['state'] = File_Scan::STATUS_SCANNING;
                    $info['state_text'] = lang('file_scan_scanning');
                }

                fclose($fh);
            }
        }

        // Calculate the completed percentage if possible
        //-----------------------------------------------

        $info['progress'] = 0;

        if ($this->state['count'] != 0 || $this->state['total'] != 0)
            $info['progress'] = sprintf('%.02f', $this->state['count'] * 100 / $this->state['total']);

        // ClamAV error codes as per clamscan(1) man page.
        // TODO: Perhaps all possible error strings should be localized?
        //--------------------------------------------------------------

        switch ($this->state['rc']) {
            case 0:
                $info['last_result'] = lang('file_scan_no_malware_found');
                break;
            case 1:
                $info['last_result'] = lang('file_scan_malware_found');
                break;
            case 40:
                $info['last_result'] = 'Unknown option passed';
                break;
            case 50:
                $info['last_result'] = 'Database initialization error';
                break;
            case 52:
                $info['last_result'] = 'Not supported file type';
                break;
            case 53:
                $info['last_result'] = 'Can\'t open directory';
                break;
            case 54:
                $info['last_result'] = 'Can\'t open file';
                break;
            case 55:
                $info['last_result'] = 'Error reading file';
                break;
            case 56:
                $info['last_result'] = 'Can\'t stat input file / directory';
                break;
            case 57:
                $info['last_result'] = 'Can\'t get absolute path name of current working directory';
                break;
            case 58:
                $info['last_result'] = 'I/O error, please check your file system';
                break;
            case 59:
                $info['last_result'] = 'Can\'t get information about current user from /etc/passwd';
                break;
            case 60:
                $info['last_result'] = 'Can\'t get  information about user (clamav) from /etc/passwd';
                break;
            case 61:
                $info['last_result'] = 'Can\'t fork';
                break;
            case 62:
                $info['last_result'] = 'Can\'t initialize logger';
                break;
            case 63:
                $info['last_result'] = 'Can\'t create temporary files/directories (check permissions)';
                break;
            case 64:
                $info['last_result'] = 'Can\'t write to temporary directory (please specify another one)';
                break;
            case 70:
                $info['last_result'] = 'Can\'t allocate and clear memory (calloc)';
                break;
            case 71:
                $info['last_result'] = 'Can\'t allocate memory (malloc)';
                break;
            default:
                $info['last_result'] = lang('base_unknown');
        }

        // Other information
        //------------------

        $info['error_count'] = count($this->state['error']);
        $info['malware_count'] = count($this->state['virus']);
        $info['current_scandir'] = $this->state['dir'];

        // Create a generic status message for the state of the scanner
        //-------------------------------------------------------------

        if ($info['state'] === File_Scan::STATUS_IDLE)
            $info['status'] = sprintf(lang('file_scan_last_run'), $info['last_run']);
        else if ($info['state'] === File_Scan::STATUS_SCANNING)
            $info['status'] = sprintf(lang('file_scan_currently_scanning'), $info['current_scandir']);
        else
            $info['status'] = '...';

        return $info;
    }

    /**
     * Returns array of quarantined viruses.
     *
     * @returns array Array of viruses in quarantine
     * @throws Engine_Exception
     * @return array virus information
     */

    public function get_quarantined_viruses()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $dir = new Folder(File_Scan::PATH_QUARANTINE, TRUE);
            $files = $dir->get_listing();
        } catch (Folder_Not_Found_Exception $e) {
            return array();
        } catch (Engine_Exception $e) {
            throw new Engine_Exception($e->get_message(), CLEAROS_ERROR);
        }

        $viruses = array();

        foreach ($files as $file) {
            if (stristr($file, '.nfo') === FALSE) continue;

            try {
                $nfo = new File(File_Scan::PATH_QUARANTINE . "/$file", TRUE);
                $buffer = unserialize($nfo->get_contents());
            } catch (Engine_Exception $e) {
                throw new Engine_Exception($e->get_message(), CLEAROS_ERROR);
            }

            $viruses[md5($buffer['filename'])] = $buffer;
        }

        return $viruses;
    }

    /**
     * Returns configured antivirus schedule.
     *
     * @return array of the scanner's configured schedule. 
     * @throws Engine_Exception
     */

    public function get_scan_schedule()
    {
        clearos_profile(__METHOD__, __LINE__);

        $hour = '*';
        $day_of_month = '*';
        $month = '*';
        $cron = new Cron();

        if (!$cron->exists_configlet('app-antivirus')) return array('*', '*', '*');

        try {
            list($minute, $hour, $day_of_month, $month, $day_of_week) 
                = explode(' ', $cron->get_configlet('app-antivirus'), 5);
        } catch (Engine_Exception $e) {
            throw new Engine_Exception($e->get_message(), CLEAROS_ERROR);
        }

        $schedule['hour'] = $hour;
        $schedule['day_of_month'] = $day_of_month;
        $schedule['month'] = $month;

        return $schedule;
    }

    /**
     * Checks status of scanner.
     *
     * @return boolea TRUE if scan is running
     */

    public function is_scan_running()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!file_exists(self::FILE_LOCKFILE))
            return FALSE;

        $fh = @fopen(self::FILE_LOCKFILE, 'r');
        list($pid) = fscanf($fh, '%d');
        fclose($fh);

        // Perhaps this is a stale lock file?
        if (!file_exists("/proc/$pid")) {
            // Yes, the process 'appears' to no longer be running...
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Quarantines a virus.
     *
     * @param string $hash MD5 hash of virus filename to quarantine
     *
     * @throws Engine_Exception
     * @return void
     */

    public function quarantine_virus($hash)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!file_exists(File_Scan::FILE_STATE)) {
            throw new Engine_Exception(ANTIVIRUS_LANG_STATE_ERROR, CLEAROS_ERROR);
        }

        // XXX: Here we use fopen rather than the File class.  This is because the File
        // class provides us with no way to do file locking (flock).  The state file
        // is therefore owned by webconfig so that we can manipulate it's contents.
        if (!($fh = @fopen(File_Scan::FILE_STATE, 'a+'))) {
            throw new Engine_Exception(ANTIVIRUS_LANG_STATE_ERROR, CLEAROS_ERROR);
        }

        if ($this->unserialize_state($fh) === FALSE) {
            fclose($fh);
            throw new Engine_Exception(ANTIVIRUS_LANG_STATE_ERROR, CLEAROS_ERROR);
        }

        if (!isset($this->state['virus'][$hash])) {
            throw new Engine_Exception(ANTIVIRUS_LANG_FILE_NOT_FOUND, CLEAROS_ERROR);
        }

        try {
            $virus = new File($this->state['virus'][$hash]['filename'], TRUE);
            $virus->move_to(File_Scan::PATH_QUARANTINE . "/$hash.dat");
            $virus = new File(File_Scan::PATH_QUARANTINE . "/$hash.nfo");

            $virus->create('webconfig', 'webconfig', '0640');
            $virus->add_lines(serialize($this->state['virus'][$hash]));
        } catch (Engine_Exception $e) {
            throw new Engine_Exception($e->get_message(), CLEAROS_ERROR);
        }

        // Update state file, delete virus
        unset($this->state['virus'][$hash]);
        $this->serialize_state($fh);
    }

    /**
     * Removes directory from scan list.
     *
     * @param string $dir Directory to remove from scan
     *
     * @throws Engine_Exception
     * @return void
     */

    public function remove_directory($dir)
    {
        clearos_profile(__METHOD__, __LINE__);

        $dirs = $this->get_directories();

        if (!count($dirs) || !in_array($dir, $dirs))
            throw new Engine_Exception(ANTIVIRUS_LANG_DIR_NOT_FOUND, CLEAROS_ERROR);

        foreach ($dirs as $id => $entry) {
            if ($entry != $dir) continue;
            unset($dirs[$id]);
            sort($dirs);
            break;
        }

        $file = new File(File_Scan::FILE_CONFIG);

        try {
            $file->dump_contents_from_array($dirs);
        } catch (Engine_Exception $e) {
            throw new Engine_Exception($e->get_message(), CLEAROS_ERROR);
        }
    }

    /**
     * Removes an antivirus schedule.
     *
     * @return void
     * @throws Engine_Exception
     */

    public function remove_scan_schedule()
    {
        clearos_profile(__METHOD__, __LINE__);

        $cron = new Cron();

        try {
            if ($cron->exists_configlet('app-antivirus'))
                $cron->delete_configlet('app-antivirus');
        } catch (Engine_Exception $e) {
            throw new Engine_Exception($e->get_message(), CLEAROS_ERROR);
        }
    }

    /**
     * Resets state
     *
     * @return void
     */

    public function reset_state()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->state['rc'] = 0;
        $this->state['dir'] = '-';
        $this->state['filename'] = '-';
        $this->state['result'] = NULL;
        $this->state['count'] = 0;
        $this->state['total'] = 0;
        $this->state['error'] = array();
        $this->state['virus'] = array();
        $this->state['timestamp'] = 0;
    }

    /**
     * Restores a quarantined virus to its orignal location/filename.
     *
     * @param string $hash MD5 hash of virus filename to restore
     *
     * @return void
     * @throws Engine_Exception
     */

    public function restore_quarantined_virus($hash)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $nfo = new File(File_Scan::PATH_QUARANTINE . "/$hash.nfo", TRUE);
            $virus = unserialize($nfo->get_contents());

            $dat = new File(File_Scan::PATH_QUARANTINE . "/$hash.dat", TRUE);
            $dat->move_to($virus['filename']);
            $nfo->delete();
        } catch (Engine_Exception $e) {
            throw new Engine_Exception($e->get_message(), CLEAROS_ERROR);
        }
    }

    /**
     * Checks for existence of scan schedule.
     *
     * @return boolean TRUE if a cron configlet exists.
     * @throws Engine_Exception
     */

    public function scan_schedule_exists()
    {
        clearos_profile(__METHOD__, __LINE__);

        $cron = new Cron();

        return $cron->exists_configlet('app-antivirus');
    }

    /**
     * Locks state file and writes serialized state.
     *
     * @param string $fh file handle
     *
     * @return boolean TRUE if method succeeds
     */

    public function serialize_state($fh)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (flock($fh, LOCK_EX) === FALSE)
            return FALSE;

        if (ftruncate($fh, 0) === FALSE) {
            flock($fh, LOCK_UN);
            return FALSE;
        }

        if (fseek($fh, SEEK_SET, 0) == -1) {
            flock($fh, LOCK_UN);
            return FALSE;
        }

        if (fwrite($fh, serialize($this->state)) === FALSE) {
            flock($fh, LOCK_UN);
            return FALSE;
        }

        fflush($fh);

        if (flock($fh, LOCK_UN) === FALSE)
            return FALSE;

        return TRUE;
    }

    /**
     * Sets an antivirus schedule.
     *
     * @param string $minute     cron minute value
     * @param string $hour       cron hour value
     * @param string $dayofmonth cron day-of-month value
     * @param string $month      cron month value
     * @param string $dayofweek  cron day-of-week value
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_scan_schedule($minute, $hour, $dayofmonth, $month, $dayofweek)
    {
        clearos_profile(__METHOD__, __LINE__);

        $cron = new Cron();

        try {
            $cron->add_configlet_by_parts(
                'app-antivirus',
                $minute, $hour, $dayofmonth, $month, $dayofweek,
                'root', COMMON_CORE_DIR . '/scripts/' . self::FILE_AVSCAN . " >/dev/null 2>&1"
            );
        } catch (Engine_Exception $e) {
            throw new Engine_Exception($e->get_message(), CLEAROS_ERROR);
        }
    }

    /**
     * Starts virus scanner.
     *
     * @throws Engine_Exception
     * @return void
     */

    public function start_scan()
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->is_scan_running())
            throw new Engine_Exception(ANTIVIRUS_LANG_RUNNING, CLEAROS_WARNING);

        $this->enable_updates();

        try {
            $options = array();
            $options['background'] = TRUE;
            $shell = new Shell();
            $shell->execute(COMMON_CORE_DIR . '/scripts/' . self::FILE_AVSCAN, '', TRUE, $options);
        } catch (Engine_Exception $e) {
            throw new Engine_Exception($e->get_message(), CLEAROS_ERROR);
        }
    }

    /**
     * Stops virus scanner.
     *
     * @throws Engine_Exception
     * @return void
     */

    public function stop_scan()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!$this->is_scan_running())
            throw new Engine_Exception(ANTIVIRUS_LANG_NOT_RUNNING, CLEAROS_WARNING);

        try {
            $options = array();
            $options['background'] = TRUE;
            $shell = new Shell();
            $shell->execute(self::CMD_KILLALL, self::FILE_AVSCAN . ' ' . basename(AVSCAN_SCANNER), TRUE, $options);
        } catch (Engine_Exception $e) {
            throw new Engine_Exception($e->get_message(), CLEAROS_ERROR);
        }
    }

    /**
     * Locks state file, reads and unserialized status.
     *
     * @param string $fh file handle
     *
     * @return boolean TRUE if method succeeds
     */

    public function unserialize_state($fh)
    {
        clearos_profile(__METHOD__, __LINE__);

        clearstatcache();
        $stats = fstat($fh);
        
        if ($stats['size'] == 0) {
            $this->reset_state();
            return TRUE;
        }

        if (flock($fh, LOCK_EX) === FALSE)
            return FALSE;

        if (fseek($fh, SEEK_SET, 0) == -1) {
            flock($fh, LOCK_UN);
            return FALSE;
        }

        if (($contents = stream_get_contents($fh)) === FALSE) {
            flock($fh, LOCK_UN);
            return FALSE;
        }

        if (($this->state = unserialize($contents)) === FALSE) {
            flock($fh, LOCK_UN);
            return FALSE;
        }

        if (flock($fh, LOCK_UN) === FALSE)
            return FALSE;

        return TRUE;
    }
}

// vi: ts=4
