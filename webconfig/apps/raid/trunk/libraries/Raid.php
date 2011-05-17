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

clearos_load_library('base/Daemon');
clearos_load_library('base/File');

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;

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

    protected $interactive = false;
    protected $config = null;
    protected $type = null;
    protected $status = null;
    protected $is_loaded = false;

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

    static function Create()
    {
        $shell = new ShellExec();
        $options['env'] = "LANG=en_US";
        $found = false;
        $type = array (
            'software' => 0,
            '3ware' => 0,
            'lsi' => 0
        );

        try {

            if (! $found) {
                // Test for software RAID
                $args = self::FILE_MDSTAT;
                $retval = $shell->execute(self::CMD_CAT, $args, false, $options);

                if ($retval == 0) {
                    $lines = $shell->get_output();
                    foreach ($lines as $line) {
                        if (ereg("^md([[:digit:]]+).*", $line)) {
                            $type['software'] = true;
                            $found = true;
                            break;
                        }
                    }
                }
            }

            if (! $found) {
                // Test for 3WARE
                $args = 'info';
                $retval = $shell->execute(self::CMD_TW_CLI, $args, true, $options);

                if ($retval == 0) {
                    $lines = $shell->get_output();
                    foreach ($lines as $line) {
                        if (ereg("^Ctl[[:space:]]+Model.*$", $line)) {
                            $type['3ware'] = true;
                            $found = true;
                            break;
                        }
                    }
                }
            }

            if (! $found) {
                // Test forLlSI
                $args = '--autoload';
                $retval = $shell->execute(self::CMD_MPT_STATUS, $args, true, $options);

                // Exit code of mpt-status changes depending on status of RAID
                if ($retval != 1) {
                    $lines = $shell->get_output();
                    foreach ($lines as $line) {
                        if (preg_match("/^ioc(.*)vol_id.*$/", $line)) {
                            $type['lsi'] = true;
                            $found = true;
                            break;
                        }
                    }
                }
            }
        } catch (Exception $e) {
            # Ignore
        }

        if ($type['software']) {
            require_once(COMMON_CORE_DIR . '/api/RaidSoftware.class.php');
            return new Raid_Software();
        } else if ($type['3ware']) {
            require_once(COMMON_CORE_DIR . '/api/Raid3ware.class.php');
            return new Raid_3Ware();
        } else if ($type['lsi']) {
            require_once(COMMON_CORE_DIR . '/api/RaidLsi.class.php');
            return new Raid_Lsi();
        }

        # Return base class
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
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

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
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

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
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		switch ($this->type) {

		case self::TYPE_UNKNOWN:
			return array(
			           'id' => self::TYPE_UNKNOWN,
			           'class' => RAID_LANG_UNKNOWN,
			           'vendor' => RAID_LANG_UNKNOWN
			       );

		case self::TYPE_SOFTWARE:
			return array(
			           'id' => self::TYPE_SOFTWARE,
			           'class' => RAID_LANG_SOFTWARE,
			           'vendor' => RAID_LANG_VENDOR_LINUX
			       );

		case self::TYPE_3WARE:
			return array(
			           'id' => self::TYPE_3WARE,
			           'class' => RAID_LANG_HARDWARE,
			           'vendor' => RAID_LANG_VENDOR_3WARE
			       );

		case self::TYPE_LSI:
			return array(
			           'id' => self::TYPE_LSI,
			           'class' => RAID_LANG_HARDWARE,
			           'vendor' => RAID_LANG_VENDOR_LSI
			       );
		}
	}

	/**
	 * Returns status of RAID.
	 *
	 * @throws Engine_Exception
	 */

	function get_status()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		return RAID_LANG_UNKNOWN;

	}

	/**
	 * Returns RAID level.
	 *
	 * @returns String the raid level
	 *
	 * @throws Engine_Exception
	 */

	function get_level()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		return RAID_LANG_UNKNOWN;

	}

	/**
	 * Formats a value into a human readable byte size.
	 *
	 * @param float $input the value
	 * @param int   $dec   number of decimal places
	 *
	 * @returns string the byte size suitable for display to end user
	 */

	function get_formatted_bytes($input, $dec)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

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
	 * @param  String  $dev  a device
	 * @returns  string  the mount point
	 */

	function get_mount($dev)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$mount = '';
		$shell = new ShellExec();
		$args = $dev;
		$retval = $shell->Execute(self::CMD_DF, $args);

		if ($retval != 0) {
			$errstr = $shell->GetLastOutputLine();
			throw new EngineException($errstr, COMMON_WARNING);
		} else {
			$lines = $shell->GetOutput();
			foreach ($lines as $line) {
				if (preg_match("/^" . str_replace('/', "\\/", $dev) . ".*$/", $line)) {
					$parts = preg_split ("/\s+/", $line);
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
	 * @throws EngineException
	 */

	function get_email()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		return $this->config['email'];
	}

	/**
	 * Get the monitor status.
	 *
	 * @return boolean true if monitoring is enabled
	 */

	function get_monitor_status()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$crontab = new Cron();
			if ($crontab->ExistsCrondConfiglet(self::FILE_CROND))
				return true;
			return false;
		} catch (Exception $e) {
			return false;
		}
	}

	/**
	 * Get the notify status.
	 *
	 * @return String  notification email
	 * @throws EngineException
	 */

	function get_notify()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		return $this->config['notify'];
	}

	/**
	 * Get the notify status.
	 *
	 * @return String  notification email
	 * @throws EngineException
	 */

	function get_devices_in_use()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$devicesinuse = array();

		# Get all block devices in use

		$myarrays = $this->GetArrays();

		foreach ($myarrays as $array) {
			if (isset($array['devices']) && is_array($array['devices'])) {
				foreach ($array['devices'] as $device)
				$devicesinuse[] = $device['dev'];
			}
		}

		# Add swap
		try {
			$shell = new ShellExec();
			$args = '-s';
			$retval = $shell->Execute(self::CMD_SWAPON, $args);

			if ($retval == 0) {
				$lines = $shell->GetOutput();
				foreach ($lines as $line) {
					if (preg_match("/^\/dev\/(\S*).*$/", $line, $match))
						$devicesinuse[] = $match[1];
				}
			}
		} catch (Exception $e) {
			# Ignore

		}

		return $devicesinuse;
	}

	/**
	 * Get partition table.
	 *
	 * @return String  $device  device
	 * @throws EngineException
	 */

	function get_partition_table($device)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$table = array();

		try {
			$shell = new ShellExec();
			$args = '-d ' . $device;
			$options['env'] = "LANG=en_US";
			$retval = $shell->Execute(self::CMD_SFDISK, $args, true, $options);

			if ($retval != 0) {
				$errstr = $shell->GetLastOutputLine();
				throw new EngineException($errstr, COMMON_WARNING);
			} else {
				$lines = $shell->GetOutput();
				foreach ($lines as $line) {
					if (preg_match("/^\/dev\/(\S+) : start=\s*(\d+), size=\s*(\d+), Id=(\S+)(,\s*.*$|$)/", $line, $match)) {
						$table[] = array('size' => $match[3], 'id' => $match[4], 'bootable' => ($match[5]) ? 1 : 0, 'raw' => $line);
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
	 * @param string $to to partition device
	 * @return void
	 * @throws EngineException
	 */

	function copy_partition_table($from, $to)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$shell = new ShellExec();
			$args = '-d ' . $from . ' > ' . COMMON_TEMP_DIR . '/pt.txt';
			$options['env'] = "LANG=en_US";
			$retval = $shell->Execute(self::CMD_SFDISK, $args, true, $options);

			if ($retval != 0) {
				$errstr = $shell->GetLastOutputLine();
				throw new EngineException($errstr, COMMON_WARNING);
			}

			$args = '-f ' . $to . ' < ' . COMMON_TEMP_DIR . '/pt.txt';
			$options['env'] = "LANG=en_US";
			$retval = $shell->Execute(self::CMD_SFDISK, $args, true, $options);

			if ($retval != 0) {
				$errstr = $shell->GetLastOutputLine();
				throw new EngineException($errstr, COMMON_WARNING);
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
	 * @return array
	 * @throws EngineException
	 */

	function sanity_check_partition($array, $check)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$partition_match = array('ok' => false);

		try {
			$myarrays = $this->GetArrays();
			foreach ($myarrays as $dev => $myarray) {
				if ($dev != $array)
					continue;

				if (isset($myarray['devices']) && is_array($myarray['devices'])) {
					foreach ($myarray['devices'] as $device) {
						# Make sure it is clean

						if ($device['status'] != self::STATUS_CLEAN)
							continue;

						$partition_match['dev'] = preg_replace("/\d/", "", $device['dev']);
						$good = $this->GetPartitionTable($partition_match['dev']);
						$check = $this->GetPartitionTable(preg_replace("/\d/", "", $check));
						$ok = true;

						# Check that the same number of partitions exist

						if (count($good) != count($check))
							$ok = false;

						$raw = array();

						for ($index = 0; $index < count($good); $index++) {
							if ($check[$index]['size'] < $good[$index]['size'])
								$ok = false;

							if ($check[$index]['id'] != $good[$index]['id'])
								$ok = false;

							if ($check[$index]['bootable'] != $good[$index]['bootable'])
								$ok = false;

							$raw[] = $good[$index]['raw'];
						}

						$partition_match['table'] = $raw;

						if ($ok) {
							$partition_match['ok'] = true;
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
	 * @throws EngineException
	 */

	function check_status_change()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$lines = array();

		try {
			switch ($this->type) {

			case self::TYPE_UNKNOWN:
				return;

			case self::TYPE_SOFTWARE:
				$myraid = new RaidSoftware();
				$lines = $this->_CreateSoftwareRaidReport($myraid);
				break;

			case self::TYPE_3WARE:
				$myraid = new Raid3ware();
				$lines = $this->_CreateHardwareRaidReport($myraid);
				break;

			case self::TYPE_LSI:
				$myraid = new RaidLsi();
				$lines = $this->_CreateHardwareRaidReport($myraid);
				break;
			}

			$file = new File(COMMON_TEMP_DIR . '/' . self::FILE_RAID_STATUS);

			if ($file->Exists()) {
				$file->MoveTo(COMMON_TEMP_DIR . '/' . self::FILE_RAID_STATUS . '.orig');
				$file = new File(COMMON_TEMP_DIR . '/' . self::FILE_RAID_STATUS);
			}

			$file->Create("webconfig", "webconfig", 0644);
			$file->DumpContentsFromArray($lines);

			# Diff files to see if notification should be sent

			$shell = new ShellExec();
			$args = COMMON_TEMP_DIR . '/raid.status ' . COMMON_TEMP_DIR . '/raid.status.orig';
			$retval = $shell->Execute(self::CMD_DIFF, $args);

			if ($retval != 0)
				$this->SendStatusChangeNotification($lines);
		} catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
		}
	}

	/**
	 * Sends a status change notification to admin.
	 *
	 * @return void
	 * @throws EngineException
	 */

	function SendStatusChangeNotification($lines)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			if (!$this->GetNotify()) {
				Logger::Syslog(self::LOG_TAG, "RAID status updated...notification disabled.");
				return;
			}

			$mailer = new Mailer();
			$hostname = new Hostname();
			$subject = RAID_LANG_EMAIL_NOTIFICATION . ' - ' . $hostname->Get();
			$body = "\n\n" . RAID_LANG_EMAIL_NOTIFICATION . ":\n";
			$body .= str_pad('', strlen(RAID_LANG_EMAIL_NOTIFICATION . ':'), '=') . "\n\n";
			$ntptime = new NtpTime();
			date_default_timezone_set($ntptime->GetTimeZone());

			$thedate = strftime("%b %e %Y");
			$thetime = strftime("%T %Z");
			$body .= str_pad(LOCALE_LANG_DATE . ':', 16) . "\t" . $thedate . ' ' . $thetime . "\n";
			$body .= str_pad(LOCALE_LANG_STATUS . ':', 16) . "\t" . $this->status . "\n\n";
			foreach ($lines as $line)
			$body .= $line . "\n";
			$mailer->AddRecipient($this->GetEmail());
			$mailer->SetSubject($subject);
			$mailer->SetBody($body);
			// May not be a valid sender...TODO
			// $mailer->SetSender('alert@' . $hostname->Get());

			$mailer->SetSender($this->GetEmail());
			$mailer->Send();
		} catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
		}
	}

	/**
	 * Set the RAID notificatoin email.
	 *
	 * @param string $email a valid email
	 * @return void
	 * @throws EngineException
	 */

	function SetEmail($email)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		$mailer = new Mailer();

		// Validation
		// ----------

		if (!$mailer->IsValidEmail($email))
			throw new ValidationException(MAILER_LANG_RECIPIENT . " - " . LOCALE_LANG_INVALID . ' (' . $email . ')');

		$this->_SetParameter('email', $email);
	}

	/**
	 * Set RAID monitoring status.
	 *
	 * @param boolean  $monitor  toggles monitoring
	 * @return void
	 * @throws EngineException
	 */

	function SetMonitorStatus($monitor)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);
		try {
			$crontab = new Cron();
			if ($crontab->ExistsCrondConfiglet(self::FILE_CROND) && $monitor) {
				return;
			} else if ($crontab->ExistsCrondConfiglet(self::FILE_CROND) && !$monitor) {
				$crontab->DeleteCrondConfiglet(self::FILE_CROND);
			} else if (!$crontab->ExistsCrondConfiglet(self::FILE_CROND) && $monitor) {
				$payload  = "# Created by API\n";
				$payload .= self::DEFAULT_CRONTAB_TIME . " root " . self::CMD_RAID_SCRIPT . " >/dev/null 2>&1";
				$crontab->AddCrondConfiglet(self::FILE_CROND, $payload);
			}
		} catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
		}
	}

	/**
	 * Set RAID notification.
	 *
	 * @param boolean  $status  toggles notification
	 * @return void
	 * @throws EngineException
	 */

	function SetNotify($status)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		$this->_SetParameter('notify', (isset($status) && $status ? 1 : 0));
	}

	///////////////////////////////////////////////////////////////////////////////
	// P R I V A T E   M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	* Loads configuration files.
	*
	* @return void
	* @throws EngineException
	*/

	protected function _LoadConfig()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$configfile = new ConfigurationFile(self::FILE_CONFIG);

		try {
			$this->config = $configfile->Load();
		} catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
		}

		$this->is_loaded = true;
	}

	/**
	 * Generic set routine.
	 *
	 * @private
	 * @param  string  $key  key name
	 * @param  string  $value  value for the key
	 * @return  void
	 * @throws EngineException
	 */

	function _SetParameter($key, $value)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$file = new File(self::FILE_CONFIG, true);
			$match = $file->ReplaceLines("/^$key\s*=\s*/", "$key=$value\n");

			if (!$match)
				$file->AddLines("$key=$value\n");
		} catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
		}

		$this->is_loaded = false;
	}

	/**
	 * Report for software RAID.
	 *
	 * @return array
	 * @throws EngineException
	 */

	function _CreateSoftwareRaidReport($myraid)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$this->status = RAID_LANG_CLEAN;

		try {
			$padding = array(10, 10, 10, 10);
			$lines = array();
			$lines[] = str_pad(RAID_LANG_ARRAY, $padding[0]) . "\t" . str_pad(RAID_LANG_SIZE, $padding[1]) . "\t" . str_pad(RAID_LANG_MOUNT, $padding[2]) . "\t" . str_pad(RAID_LANG_LEVEL, $padding[3]) . "\t" . LOCALE_LANG_STATUS;
			$lines[] = str_pad('', strlen($lines[0]) + 4*4, '-');
			$myarrays = $myraid->GetArrays();
			foreach ($myarrays as $dev => $myarray) {
				$status = RAID_LANG_CLEAN;
				$mount = $this->GetMount($dev);

				if ($myarray['status'] != Raid::STATUS_CLEAN) {
					$status = RAID_LANG_DEGRADED;
					$this->status = RAID_LANG_DEGRADED;
				}

				foreach ($myarray['devices'] as $index => $details) {
					if ($details['status'] == Raid::STATUS_SYNCING) {
						$status = RAID_LANG_SYNCING . ' (' . $details['dev'] . ') - ' . $details['recovery'] . '%';
						$this->status = RAID_LANG_SYNCING;
					} else if ($details['status'] == Raid::STATUS_SYNC_PENDING) {
						$status = RAID_LANG_SYNC_PENDING . ' (' . $details['dev'] . ')';
					} else if ($details['status'] == Raid::STATUS_DEGRADED) {
						$status = RAID_LANG_DEGRADED . ' (' . $details['dev'] . ' ' . RAID_LANG_FAILED . ')';
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
	 * @return void
	 * @throws EngineException
	 */

	function _CreateHardwareRaidReport($myraid)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$this->status = RAID_LANG_CLEAN;
		$lines = array();
		$padding = array(20, 15, 12, 10, 12);

		try {
			$controllers = $myraid->GetArrays();
			$lines[] = str_pad(RAID_LANG_CONTROLLER, $padding[0]) . "\t" . str_pad(RAID_LANG_UNIT, $padding[1]) . "\t" . str_pad(RAID_LANG_SIZE, $padding[2]) . "\t" . str_pad(RAID_LANG_DEVICE, $padding[3]) . "\t" . str_pad(RAID_LANG_LEVEL, $padding[4]) . "\t" . LOCALE_LANG_STATUS;
			$lines[] = str_pad('', strlen($lines[0]) + 4*5, '-');

			foreach ($controllers as $controllerid => $controller) {
				foreach ($controller['units'] as $unitid => $unit) {
					$status = RAID_LANG_CLEAN;
					$mount = $myraid->GetMapping('c' . $controllerid);

					if ($unit['status'] != Raid::STATUS_CLEAN) {
						$status = RAID_LANG_DEGRADED;
						$this->status = RAID_LANG_DEGRADED;
					} else if ($unit['status'] == Raid::STATUS_SYNCING) {
						$status = RAID_LANG_SYNCING;
						$this->status = RAID_LANG_SYNCING;
					}

					foreach ($unit['devices'] as $id => $details) {
						if ($details['status'] == Raid::STATUS_SYNCING) {
							# Provide a more detailed status message
							$status = RAID_LANG_SYNCING . ' (' . RAID_LANG_DISK . ' ' . $id . ') - ' . $details['recovery'] . '%';
						} else if ($details['status'] == Raid::STATUS_SYNC_PENDING) {
							# Provide a more detailed status message
							$status = RAID_LANG_SYNC_PENDING . ' (' . RAID_LANG_DISK . ' ' . $id . ')';
						} else if ($details['status'] == Raid::STATUS_DEGRADED) {
							# Provide a more detailed status message
							$status = RAID_LANG_DEGRADED . ' (' . RAID_LANG_DISK . ' ' . $id . ' ' . RAID_LANG_FAILED . ')';
						}
					}

					$lines[] = str_pad($controller['model'] . ", " . RAID_LANG_SLOT . " $controllerid", $padding[0]) .
					   "\t" . str_pad(RAID_LANG_LOGICAL_DISK . " " . $unitid, $padding[1]) . "\t" .
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

}
