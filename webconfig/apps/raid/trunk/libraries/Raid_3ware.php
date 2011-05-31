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

use \clearos\apps\raid\Raid as Raid;

clearos_load_library('raid/Raid');

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
 * Raid_3ware class.
 *
 * @category   Apps
 * @package    Raid
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/raid/
 */

class Raid_3ware extends Raid
{
    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $interactive = TRUE;

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Raid3ware constructor.
     *
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        parent::__construct();

        $this->type = self::TYPE_3WARE;
    }

    /**
     * Returns RAID arrays.
     *
     * @return Array
     * @throws Engine_Exception
     */

    function get_arrays()
    {

        clearos_profile(__METHOD__, __LINE__);

        $myarrays = Array();
        $controllers = Array();

        $shell = new ShellExec;
        $args = 'rescan';
        $options['env'] = "LANG=en_US";
        $retval = $shell->execute(self::CMD_TW_CLI, $args, TRUE, $options);
        if ($retval != 0) {
            $errstr = $shell->get_last_output_line();
            throw new Engine_Exception($errstr, COMMON_WARNING);
        }
        $args = 'info';
        $retval = $shell->execute(self::CMD_TW_CLI, $args, TRUE, $options);
        if ($retval != 0) {
            $erroutput = $shell->get_output();
            foreach ($erroutput as $errstr) {
                if (isset($errstr) && $errstr)
                    throw new Engine_Exception($errstr, COMMON_WARNING);
            }
        } else {
            $lines = $shell->get_output();
            foreach ($lines as $line) {
                if (preg_match("/^c(\d+)\s+(\S+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(.*)$/", $line, $match))
                    $controllers[$match[1]] = Array('model'=>$match[2], 'ports'=>$match[3], 'drives'=>$match[4]);
            }
        }

        foreach ($controllers as $id => $controller) {
            $args = 'info c' . $id . ' model';
            $retval = $shell->execute(self::CMD_TW_CLI, $args, TRUE, $options);
            $myarrays[$id]['model'] = lang('raid_unknown');
            if ($retval == 0) {
                $lines = $shell->get_output();
                $model = $shell->get_first_output_line();
                if (preg_match("/^.*\s=\s(.*)$/", $model, $match)) 
                    $myarrays[$id]['model'] = $match[1];
            }
            $args = 'info c' . $id;
            $retval = $shell->execute(self::CMD_TW_CLI, $args, TRUE, $options);
            if ($retval != 0) {
                $erroutput = $shell->get_output();
                foreach ($erroutput as $errstr) {
                    if (isset($errstr) && $errstr)
                        throw new Engine_Exception($errstr, COMMON_WARNING);
                }
                return;
            }
            $lines = $shell->get_output();
            foreach ($lines as $line) {
                if (preg_match("/^u(\d+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(\d+.\d).*$/", $line, $match)) {
                    $myarrays[$id]['units'][$match[1]]['level'] = strtoupper($match[2]);
                    $myarrays[$id]['units'][$match[1]]['size'] = $match[7]*1024*1024;
                    // Status
                    $myarrays[$id]['units'][$match[1]]['status'] = self::STATUS_CLEAN;
                    if ($match[3] != 'OK')
                        $myarrays[$id]['units'][$match[1]]['status'] = self::STATUS_DEGRADED;
                    if ($match[3] == 'REBUILDING') {
                        $myarrays[$id]['units'][$match[1]]['status'] = self::STATUS_SYNCING;
                        $recovery = $match[4];
                    }
                    $args = 'info c' . $id . ' u' . $match[1];
                    $retval = $shell->execute(self::CMD_TW_CLI, $args, TRUE, $options);
                    if ($retval != 0) {
                        $erroutput = $shell->get_output();
                        foreach ($erroutput as $errstr) {
                            if (isset($errstr) && $errstr)
                                throw new Engine_Exception($errstr, COMMON_WARNING);
                        }
                        continue;
                    }
                    $details = $shell->get_output();
                    $regex = "/^u(\d+)-(\d+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(\d+.\d+)$/";
                    foreach ($details as $detail) {
                        if (preg_match($regex, $detail, $match)) {
                            $myarrays[$id]['units'][$match[1]]['devices'][$match[2]]['status'] = self::STATUS_CLEAN;
                            if ($match[4] != 'OK')
                                $myarrays[$id]['units'][$match[1]]['devices'][$match[2]]['status'] = self::STATUS_DEGRADED;
                            if ($match[4] == 'DEGRADED' && $match[7] == '-')
                                $myarrays[$id]['units'][$match[1]]['devices'][$match[2]]['status'] = self::STATUS_REMOVED;
                            if ($match[4] == 'DEGRADED' && $myarrays[$id]['units'][$match[1]]['status'] == self::STATUS_SYNCING) {
                                $myarrays[$id]['units'][$match[1]]['devices'][$match[2]]['status'] = self::STATUS_SYNCING;
                                $myarrays[$id]['units'][$match[1]]['devices'][$match[2]]['recovery'] = $recovery;
                            }
                        }
                    }
                } else if (preg_match("/^p(\d+)\s+OK\s+-\s+(\d+.\d+)\s+(\S+)\s+(\d+)\s+(\S+)$/", $line, $match)) {
                    $myarrays[$id]['spares'][$match[1]] = Array('size'=>$match[4]*512, 'serial'=>$match[5]);
                }
            }
        }
        
        ksort($myarrays);
        return $myarrays;
    }

    /**
     * Gets the mapping of a RAID array to physical device as seen by the OS.
     *
     * @param String $unit a unit on the controller
     *
     * @return string the device
     */

    function get_mapping($unit)
    {
        clearos_profile(__METHOD__, __LINE__);

        $id = '';

        $storage = new StorageDevice();
        $devices = $storage->get_devices();
        foreach ($devices as $dev => $device) {
            if ($device['vendor'] != '3ware')
                continue;
            if (!preg_match("/^Logical Disk (\d+)$/", $device['model'], $match))
                continue;
            if ($match[1] == $unit)
                $id = preg_replace('/\d/', '', $dev);
        }
        return $id;
    }

    /**
     * Removes a device from the specified controller.
     *
     * @param string $controller the controller ID
     * @param string $port       the port ID
     *
     * @return void
     * @throws Engine_Exception
     */

    function remove_device($controller, $port)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $shell = new ShellExec;
            $args = 'remove c' . $controller . ' p' . $port;
            $options['env'] = "LANG=en_US";
            $retval = $shell->execute(self::CMD_TW_CLI, $args, TRUE, $options);
            if ($retval != 0) {
                $erroutput = $shell->get_output();
                foreach ($erroutput as $errstr) {
                    if (isset($errstr) && $errstr)
                        throw new Engine_Exception($errstr, COMMON_WARNING);
                }
            } else {
                $output = $shell->get_output();
            }
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e) . " ($controller:$port)", CLEAROS_ERROR);
        }
    }

    /**
     * Repair an array with the specified parameters.
     *
     * @param string $controller the controller
     * @param string $unit       the unit
     * @param string $port       the port
     *
     * @return void
     * @throws Engine_Exception
     */

    function repair_array($controller, $unit, $port)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $shell = new ShellExec;
            $args = 'maint rebuild c' . $controller . ' u' . $unit . ' p' . $port;
            $options['env'] = "LANG=en_US";
            $retval = $shell->execute(self::CMD_TW_CLI, $args, TRUE, $options);
            if ($retval != 0) {
                $erroutput = $shell->get_output();
                foreach ($erroutput as $errstr) {
                    if (isset($errstr) && $errstr)
                        throw new Engine_Exception($errstr, COMMON_WARNING);
                }
            } else {
                $output = $shell->get_output();
            }
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e) . " ($args)", CLEAROS_ERROR);
        }
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

}
