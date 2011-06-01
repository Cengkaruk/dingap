<?php

/**
 * Raid class.
 *
 * @category   Apps
 * @package    Raid
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/raid/
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

namespace clearos\apps\raid;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('raid');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Daemon as Daemon;
use \clearos\apps\base\File as File;
use \clearos\apps\base\Configuration_File as Configuration_File;
use \clearos\apps\base\Shell as Shell;
use \clearos\apps\tasks\Cron as Cron;

clearos_load_library('base/Daemon');
clearos_load_library('base/File');
clearos_load_library('base/Configuration_File');
clearos_load_library('base/Shell');
clearos_load_library('tasks/Cron');
clearos_load_library('tasks/Cron_Configlet_Not_Found_Exception');

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;
use \clearos\apps\tasks\Cron_Configlet_Not_Found_Exception as Cron_Configlet_Not_Found_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('base/Validation_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Raid class.
 *
 * @category   Apps
 * @package    Raid
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/raid/
 */

class Raid extends Daemon
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const LOG_TAG = 'raid';
    const FILE_CONFIG = '/etc/system/raid.conf';
    const FILE_MDSTAT = '/proc/mdstat';
    const FILE_RAID_STATUS = 'raid.status';
    const FILE_CROND = "app-raid";
    const DEFAULT_CRONTAB_TIME = "0,30 * * * *";
    const CMD_CAT = '/bin/cat';
    const CMD_DF = '/bin/df';
    const CMD_DIFF = '/usr/bin/diff';
    const CMD_FDISK = '/sbin/fdisk';
    const CMD_SFDISK = '/sbin/sfdisk';
    const CMD_SWAPON = '/sbin/swapon';
    const CMD_TW_CLI = '/usr/sbin/tw_cli';
    const CMD_MPT_STATUS = '/usr/sbin/mpt-status';
    const CMD_RAID_SCRIPT = '/var/webconfig/scripts/raid_notification.php';
    const TYPE_UNKNOWN = 0;
    const TYPE_SOFTWARE = 1;
    const TYPE_3WARE = 2;
    const TYPE_LSI = 3;
    const STATUS_CLEAN = 0;
    const STATUS_DEGRADED = 1;
    const STATUS_SYNCING = 2;
    const STATUS_SYNC_PENDING = 3;
    const STATUS_REMOVED = 4;
    const STATUS_SPARE = 5;

    protected $interactive = FALSE;
    protected $config = NULL;
    protected $type = NULL;
    protected $status = NULL;
    protected $is_loaded = FALSE;

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Raid constructor.
     */

    function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        parent::__construct('raid');
    }

    /**
     * Create a RAID supported class.
     *
     * @return Class
     */

    static function create()
    {
        clearos_profile(__METHOD__, __LINE__);

        $shell = new Shell();
        $options['env'] = "LANG=en_US";
        $found = FALSE;
        $type = array (
            'software' => 0,
            '3ware' => 0,
            'lsi' => 0
        );

        try {

            if (! $found) {
                // Test for software RAID
                $args = self::FILE_MDSTAT;
                $retval = $shell->execute(self::CMD_CAT, $args, FALSE, $options);

                if ($retval == 0) {
                    $lines = $shell->get_output();
                    foreach ($lines as $line) {
                        if (preg_match("/^md([[:digit:]]+).*/", $line)) {
                            $type['software'] = TRUE;
                            $found = TRUE;
                            break;
                        }
                    }
                }
            }

            if (! $found) {
                // Test for 3WARE
                $args = 'info';
                $retval = $shell->execute(self::CMD_TW_CLI, $args, TRUE, $options);

                if ($retval == 0) {
                    $lines = $shell->get_output();
                    foreach ($lines as $line) {
                        if (preg_match("/^Ctl[[:space:]]+Model.*$/", $line)) {
                            $type['3ware'] = TRUE;
                            $found = TRUE;
                            break;
                        }
                    }
                }
            }

            if (! $found) {
                // Test forLlSI
                $args = '--autoload';
                $retval = $shell->execute(self::CMD_MPT_STATUS, $args, TRUE, $options);

                // Exit code of mpt-status changes depending on status of RAID
                if ($retval != 1) {
                    $lines = $shell->get_output();
                    foreach ($lines as $line) {
                        if (preg_match("/^ioc(.*)vol_id.*$/", $line)) {
                            $type['lsi'] = TRUE;
                            $found = TRUE;
                            break;
                        }
                    }
                }
            }
        } catch (Exception $e) {
            // Ignore
        }

        if ($type['software']) {
            include_once clearos_app_base('raid') . '/libraries/Raid_Software.php';
            return new Raid_Software();
        } else if ($type['3ware']) {
            include_once clearos_app_base('raid') . '/libraries/Raid_3ware.php';
            return new Raid_3Ware();
        } else if ($type['lsi']) {
            include_once clearos_app_base('raid') . '/libraries/Raid_Lsi.php';
            return new Raid_Lsi();
        }

        // Return base class
        return new Raid();
    }

    /**
     * Returns type of RAID.
     *
     * @return int
     * @throws Engine_Exception
     */

    function get_type()
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->type;
    }

    /**
     * Returns whether type of RAID supports interaction - i.e. resync etc.
     *
     * @return boolean
     * @throws Engine_Exception
     */

    function get_interactive()
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->interactive;
    }

    /**
     * Returns type of RAID.
     *
     * @return array
     * @throws Engine_Exception
     */

    function get_type_details()
    {
        clearos_profile(__METHOD__, __LINE__);

        switch ($this->type) {

            case self::TYPE_UNKNOWN:
                return array(
                           'id' => self::TYPE_UNKNOWN,
                           'class' => lang('raid_unknown'),
                           'vendor' => lang('raid_unknown') 
                       );

            case self::TYPE_SOFTWARE:
                return array(
                           'id' => self::TYPE_SOFTWARE,
                           'class' => lang('raid_software'),
                           'vendor' => lang('raid_vendor_linux')
                       );

            case self::TYPE_3WARE:
                return array(
                           'id' => self::TYPE_3WARE,
                           'class' => lang('raid_hardware'),
                           'vendor' => lang('raid_vendor_3ware')
                       );

            case self::TYPE_LSI:
                return array(
                           'id' => self::TYPE_LSI,
                           'class' => lang('raid_hardware'),
                           'vendor' => lang('raid_vendor_lsi')
                       );
        }
    }

    /**
     * Returns status of RAID.
     *
     * @return string status
     * @throws Engine_Exception
     */

    function get_status()
    {
        clearos_profile(__METHOD__, __LINE__);

        return lang('raid_unknown');

    }

    /**
     * Returns RAID level.
     *
     * @return String the raid level
     *
     * @throws Engine_Exception
     */

    function get_level()
    {
        clearos_profile(__METHOD__, __LINE__);

        return lang('raid_unknown');

    }

    /**
     * Formats a value into a human readable byte size.
     *
     * @param float $input the value
     * @param int   $dec   number of decimal places
     *
     * @return string the byte size suitable for display to end user
     */

    function get_formatted_bytes($input, $dec)
    {
        clearos_profile(__METHOD__, __LINE__);

        $prefix_arr = array(" B", "KB", "MB", "GB", "TB");
        $value = round($input, $dec);

        $i = 0;

        while ($value>1024) {
            $value /= 1024;
            $i++;
        }

        $display = round($value, $dec) . " " . $prefix_arr[$i];
        return $display;
    }

    /**
     * Returns the mount point.
     *
     * @param String $dev a device
     *
     * @return string the mount point
     * @throws Engine_Exception
     */

    function get_mount($dev)
    {
        clearos_profile(__METHOD__, __LINE__);

        $mount = '';
        $shell = new Shell();
        $args = $dev;
        $retval = $shell->execute(self::CMD_DF, $args);

        if ($retval != 0) {
            $errstr = $shell->get_last_output_line();
            throw new Engine_Exception($errstr, CLEAROS_WARNING);
        } else {
            $lines = $shell->get_output();
            foreach ($lines as $line) {
                if (preg_match("/^" . str_replace('/', "\\/", $dev) . ".*$/", $line)) {
                    $parts = preg_split("/\s+/", $line);
                    $mount = trim($parts[5]);
                    break;
                }
            }
        }

        return $mount;
    }

    /**
     * Get the notification email.
     *
     * @return String  notification email
     * @throws Engine_Exception
     */

    function get_email()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        return $this->config['email'];
    }

    /**
     * Get the monitor status.
     *
     * @return boolean TRUE if monitoring is enabled
     */

    function get_monitor_status()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $cron = new Cron();
            if ($cron->exists_configlet(self::FILE_CROND))
                return TRUE;
            return FALSE;
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * Get the notify status.
     *
     * @return String  notification email
     * @throws Engine_Exception
     */

    function get_notify()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        return $this->config['notify'];
    }

    /**
     * Get the notify status.
     *
     * @return String  notification email
     * @throws Engine_Exception
     */

    function get_devices_in_use()
    {
        clearos_profile(__METHOD__, __LINE__);

        $devicesinuse = array();

        // Get all block devices in use

        $myarrays = $this->get_arrays();

        foreach ($myarrays as $array) {
            if (isset($array['devices']) && is_array($array['devices'])) {
                foreach ($array['devices'] as $device)
                $devicesinuse[] = $device['dev'];
            }
        }

        // Add swap
        try {
            $shell = new Shell();
            $args = '-s';
            $retval = $shell->execute(self::CMD_SWAPON, $args);

            if ($retval == 0) {
                $lines = $shell->get_output();
                foreach ($lines as $line) {
                    if (preg_match("/^\/dev\/(\S*).*$/", $line, $match))
                        $devicesinuse[] = $match[1];
                }
            }
        } catch (Exception $e) {
            // Ignore

        }

        return $devicesinuse;
    }

    /**
     * Get partition table.
     *
     * @param string $device RAID device
     *
     * @return String  $device  device
     * @throws Engine_Exception
     */

    function get_partition_table($device)
    {
        clearos_profile(__METHOD__, __LINE__);

        $table = array();

        try {
            $shell = new Shell();
            $args = '-d ' . $device;
            $options['env'] = "LANG=en_US";
            $retval = $shell->execute(self::CMD_SFDISK, $args, TRUE, $options);

            if ($retval != 0) {
                $errstr = $shell->get_last_output_line();
                throw new Engine_Exception($errstr, CLEAROS_WARNING);
            } else {
                $lines = $shell->get_output();
                $regex = "/^\/dev\/(\S+) : start=\s*(\d+), size=\s*(\d+), Id=(\S+)(,\s*.*$|$)/";
                foreach ($lines as $line) {
                    if (preg_match($regex, $line, $match)) {
                        $table[] = array(
                        'size' => $match[3],
                        'id' => $match[4],
                        'bootable' => ($match[5]) ? 1 : 0, 'raw' => $line
                        );
                    }
                }
            }

            return $table;
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e . " ($device)"), CLEAROS_ERROR);
        }
    }

    /**
     * Copy a partition table from one device to another.
     *
     * @param string $from from partition device
     * @param string $to   to partition device
     *
     * @return void
     * @throws Engine_Exception
     */

    function copy_partition_table($from, $to)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $shell = new Shell();
            $args = '-d ' . $from . ' > ' . COMMON_TEMP_DIR . '/pt.txt';
            $options['env'] = "LANG=en_US";
            $retval = $shell->execute(self::CMD_SFDISK, $args, TRUE, $options);

            if ($retval != 0) {
                $errstr = $shell->get_last_output_line();
                throw new Engine_Exception($errstr, CLEAROS_WARNING);
            }

            $args = '-f ' . $to . ' < ' . COMMON_TEMP_DIR . '/pt.txt';
            $options['env'] = "LANG=en_US";
            $retval = $shell->execute(self::CMD_SFDISK, $args, TRUE, $options);

            if ($retval != 0) {
                $errstr = $shell->get_last_output_line();
                throw new Engine_Exception($errstr, CLEAROS_WARNING);
            }
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Performs a sanity check on partition table to see it matches.
     *
     * @param string $array the array to find a device that is clean
     * @param string $check the device to check partition against
     *
     * @return array
     * @throws Engine_Exception
     */

    function sanity_check_partition($array, $check)
    {
        clearos_profile(__METHOD__, __LINE__);

        $partition_match = array('ok' => FALSE);

        try {
            $myarrays = $this->get_arrays();
            foreach ($myarrays as $dev => $myarray) {
                if ($dev != $array)
                    continue;

                if (isset($myarray['devices']) && is_array($myarray['devices'])) {
                    foreach ($myarray['devices'] as $device) {
                        // Make sure it is clean

                        if ($device['status'] != self::STATUS_CLEAN)
                            continue;

                        $partition_match['dev'] = preg_replace("/\d/", "", $device['dev']);
                        $good = $this->GetPartitionTable($partition_match['dev']);
                        $check = $this->GetPartitionTable(preg_replace("/\d/", "", $check));
                        $ok = TRUE;

                        // Check that the same number of partitions exist

                        if (count($good) != count($check))
                            $ok = FALSE;

                        $raw = array();

                        for ($index = 0; $index < count($good); $index++) {
                            if ($check[$index]['size'] < $good[$index]['size'])
                                $ok = FALSE;

                            if ($check[$index]['id'] != $good[$index]['id'])
                                $ok = FALSE;

                            if ($check[$index]['bootable'] != $good[$index]['bootable'])
                                $ok = FALSE;

                            $raw[] = $good[$index]['raw'];
                        }

                        $partition_match['table'] = $raw;

                        if ($ok) {
                            $partition_match['ok'] = TRUE;
                            break;
                        }
                    }
                }
            }

            return $partition_match;
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Checks the change of status of the RAID array.
     *
     * @return void
     * @throws Engine_Exception
     */

    function check_status_change()
    {
        clearos_profile(__METHOD__, __LINE__);

        $lines = array();

        try {
            switch ($this->type) {

                case self::TYPE_UNKNOWN:
                    return;

                case self::TYPE_SOFTWARE:
                    $myraid = new RaidSoftware();
                    $lines = $this->_create_software_raid_report($myraid);
                    break;

                case self::TYPE_3WARE:
                    $myraid = new Raid3ware();
                    $lines = $this->_create_hardware_raid_report($myraid);
                    break;

                case self::TYPE_LSI:
                    $myraid = new RaidLsi();
                    $lines = $this->_create_hardware_raid_report($myraid);
                    break;
            }

            $file = new File(COMMON_TEMP_DIR . '/' . self::FILE_RAID_STATUS);

            if ($file->exists()) {
                $file->MoveTo(COMMON_TEMP_DIR . '/' . self::FILE_RAID_STATUS . '.orig');
                $file = new File(COMMON_TEMP_DIR . '/' . self::FILE_RAID_STATUS);
            }

            $file->Create("webconfig", "webconfig", 0644);
            $file->DumpContentsFromArray($lines);

            // Diff files to see if notification should be sent

            $shell = new Shell();
            $args = COMMON_TEMP_DIR . '/raid.status ' . COMMON_TEMP_DIR . '/raid.status.orig';
            $retval = $shell->execute(self::CMD_DIFF, $args);

            if ($retval != 0)
                $this->send_status_change_notification($lines);
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Sends a status change notification to admin.
     *
     * @param string $lines the message content
     *
     * @return void
     * @throws Engine_Exception
     */

    function send_status_change_notification($lines)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            if (!$this->GetNotify()) {
                Logger::Syslog(self::LOG_TAG, "RAID status updated...notification disabled.");
                return;
            }

            $mailer = new Mailer();
            $hostname = new Hostname();
            $subject = lang('raid_email_notification') . ' - ' . $hostname->get();
            $body = "\n\n" . lang('raid_email_notification') . ":\n";
            $body .= str_pad('', strlen(lang('raid_email_notification') . ':'), '=') . "\n\n";
            $ntptime = new Ntp_Time();
            date_default_timezone_set($ntptime->get_time_zone());

            $thedate = strftime("%b %e %Y");
            $thetime = strftime("%T %Z");
            $body .= str_pad(lang('base_date') . ':', 16) . "\t" . $thedate . ' ' . $thetime . "\n";
            $body .= str_pad(lang('base_status') . ':', 16) . "\t" . $this->status . "\n\n";
            foreach ($lines as $line)
            $body .= $line . "\n";
            $mailer->add_recipient($this->get_email());
            $mailer->set_subject($subject);
            $mailer->set_body($body);
            // May not be a valid sender...TODO
            // $mailer->set_sender('alert@' . $hostname->get());

            $mailer->set_sender($this->get_email());
            $mailer->send();
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Set the RAID notificatoin email.
     *
     * @param string $email a valid email
     *
     * @return void
     * @throws Engine_Exception
     */

    function set_email($email)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        $mailer = new Mailer();

        // Validation
        // ----------

        if (!$mailer->is_valid_email($email))
            throw new Validation_Exception(
                lang('mailer_recipient-TODO') . " - " . lang('base_invalid') . ' (' . $email . ')'
            );

        $this->_set_parameter('email', $email);
    }

    /**
     * Set RAID monitoring status.
     *
     * @param boolean $monitor toggles monitoring
     *
     * @return void
     * @throws Engine_Exception
     */

    function set_monitor_status($monitor)
    {
        clearos_profile(__METHOD__, __LINE__);
        try {
            $cron = new Cron();
            if ($cron->exists_configlet(self::FILE_CROND) && $monitor) {
                return;
            } else if ($cron->exists_configlet(self::FILE_CROND) && !$monitor) {
                $cron->DeleteCrondConfiglet(self::FILE_CROND);
            } else if (!$cron->exists_configlet(self::FILE_CROND) && $monitor) {
                $payload  = "# Created by API\n";
                $payload .= self::DEFAULT_CRONTAB_TIME . " root " . self::CMD_RAID_SCRIPT . " >/dev/NULL 2>&1";
                $cron->AddCrondConfiglet(self::FILE_CROND, $payload);
            }
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Set RAID notification.
     *
     * @param boolean $status toggles notification
     *
     * @return void
     * @throws Engine_Exception
     */

    function set_notify($status)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        $this->_set_parameter('notify', (isset($status) && $status ? 1 : 0));
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
    * Loads configuration files.
    *
    * @return void
    * @throws Engine_Exception
    */

    protected function _load_config()
    {
        clearos_profile(__METHOD__, __LINE__);

        $configfile = new Configuration_File(self::FILE_CONFIG);

        try {
            $this->config = $configfile->Load();
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        $this->is_loaded = TRUE;
    }

    /**
     * Generic set routine.
     *
     * @param string $key   key name
     * @param string $value value for the key
     *
     * @return  void
     * @throws Engine_Exception
     */

    function _set_parameter($key, $value)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $file = new File(self::FILE_CONFIG, TRUE);
            $match = $file->replace_lines("/^$key\s*=\s*/", "$key=$value\n");

            if (!$match)
                $file->add_lines("$key=$value\n");
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        $this->is_loaded = FALSE;
    }

    /**
     * Report for software RAID.
     *
     * @param String $myraid System RAID
     *
     * @return array
     * @throws Engine_Exception
     */

    function _create_software_raid_report($myraid)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->status = lang('raid_clean');

        try {
            $padding = array(10, 10, 10, 10);
            $lines = array();
            $lines[] = str_pad(lang('raid_array'), $padding[0]) . "\t" .
                str_pad(lang('raid_size'), $padding[1]) . "\t" .
                str_pad(lang('raid_mount'), $padding[2]) . "\t" .
                str_pad(lang('raid_level'), $padding[3]) . "\t" .
                lang('base_status');
            $lines[] = str_pad('', strlen($lines[0]) + 4*4, '-');
            $myarrays = $myraid->get_arrays();
            foreach ($myarrays as $dev => $myarray) {
                $status = lang('raid_clean');
                $mount = $this->GetMount($dev);

                if ($myarray['status'] != self::STATUS_CLEAN) {
                    $status = lang('raid_degraded');
                    $this->status = lang('raid_degraded');
                }

                foreach ($myarray['devices'] as $index => $details) {
                    if ($details['status'] == self::STATUS_SYNCING) {
                        $status = lang('raid_syncing') . ' (' . $details['dev'] . ') - ' . $details['recovery'] . '%';
                        $this->status = lang('raid_syncing');
                    } else if ($details['status'] == self::STATUS_SYNC_PENDING) {
                        $status = lang('raid_sync_pending') . ' (' . $details['dev'] . ')';
                    } else if ($details['status'] == self::STATUS_DEGRADED) {
                        $status = lang('raid_degraded') . ' (' . $details['dev'] . ' ' . lang('raid_failed') . ')';
                    }
                }

                $lines[] = str_pad($dev, $padding[0]) . "\t" .
                    str_pad($this->GetFormattedBytes($myarray['size'], 1), $padding[1]) . "\t" .
                    str_pad($mount, $padding[2]) . "\t" . str_pad($myarray['level'], $padding[3]) . "\t" . $status;
            }

            return $lines;
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Report for hardware RAID.
     *
     * @param String $myraid System RAID
     *
     * @return void
     * @throws Engine_Exception
     */

    function _create_hardware_raid_report($myraid)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->status = lang('raid_clean');
        $lines = array();
        $padding = array(20, 15, 12, 10, 12);

        try {
            $controllers = $myraid->get_arrays();
            $lines[] = str_pad(lang('raid_controller'), $padding[0]) . "\t" .
                str_pad(lang('raid_unit'), $padding[1]) . "\t" .
                str_pad(lang('raid_size'), $padding[2]) . "\t" .
                str_pad(lang('raid_device'), $padding[3]) . "\t" .
                str_pad(lang('raid_level'), $padding[4]) . "\t" .
                lang('base_status')
            ;
            $lines[] = str_pad('', strlen($lines[0]) + 4*5, '-');

            foreach ($controllers as $controllerid => $controller) {
                foreach ($controller['units'] as $unitid => $unit) {
                    $status = lang('raid_clean');
                    $mount = $myraid->GetMapping('c' . $controllerid);

                    if ($unit['status'] != self::STATUS_CLEAN) {
                        $status = lang('raid_degraded');
                        $this->status = lang('raid_degraded');
                    } else if ($unit['status'] == self::STATUS_SYNCING) {
                        $status = lang('raid_syncing');
                        $this->status = lang('raid_syncing');
                    }

                    foreach ($unit['devices'] as $id => $details) {
                        if ($details['status'] == self::STATUS_SYNCING) {
                            // Provide a more detailed status message
                            $status = lang('raid_syncing') . ' (' . lang('raid_disk') . ' ' . $id . ') - ' .
                                $details['recovery'] . '%';
                        } else if ($details['status'] == self::STATUS_SYNC_PENDING) {
                            // Provide a more detailed status message
                            $status = lang('raid_sync_pending') . ' (' . lang('raid_disk') . ' ' . $id . ')';
                        } else if ($details['status'] == self::STATUS_DEGRADED) {
                            // Provide a more detailed status message
                            $status = lang('raid_degraded') . ' (' . lang('raid_disk') . ' ' . $id . ' ' .
                                lang('raid_failed') . ')';
                        }
                    }

                    $lines[] = str_pad(
                        $controller['model'] . ", " . lang('raid_slot') . " $controllerid", $padding[0]
                    ) .
                    "\t" . str_pad(lang('raid_logical_disk') . " " . $unitid, $padding[1]) . "\t" .
                    str_pad($this->GetFormattedBytes($unit['size'], 1), $padding[2]) . "\t" .
                    str_pad($mount, $padding[3], ' ', STR_PAD_RIGHT) . "\t" . str_pad($unit['level'], $padding[4]) .
                    "\t" . $status;
                }
            }

            return $lines;
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   R O U T I N E S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validation routine for email
     *
     * @param string $email email
     *
     * @return boolean TRUE if email is valid
     */

    public function validate_email($email)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! preg_match("/^[0-9]+$/", $email))
            return lang('raid_email_is_invalid');
    }
}
