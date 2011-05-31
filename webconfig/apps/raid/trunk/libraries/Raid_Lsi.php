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
 * Raid_Lsi class.
 *
 * @category   Apps
 * @package    Raid
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/raid/
 */

class Raid_Lsi extends Raid
{
    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $interactive = FALSE;

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * RaidLsi constructor.
     *
     * @return void
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        parent::__construct();

        $this->type = self::TYPE_LSI;
    }

    /**
     * Returns RAID arrays.
     *
     * @return array
     *
     * @throws Engine_Exception
     */

    function get_arrays()
    {
        clearos_profile(__METHOD__, __LINE__);

        $myarrays = array();
        $controllers = array();

        $shell = new ShellExec();
        $args = '--newstyle';
        $options['env'] = "LANG=en_US";
        $retval = $shell->execute(self::CMD_MPT_STATUS, $args, TRUE, $options);

        if ($retval == 1) {
            $erroutput = $shell->get_output();
            foreach ($erroutput as $errstr) {
                if (isset($errstr) && $errstr)
                    throw new Engine_Exception($errstr, COMMON_WARNING);
            }
        } else {
            $lines = $shell->get_output();
            foreach ($lines as $line) {
                if (preg_match("/^ioc:(\d+).*$/", $line, $match))
                    $controllers[$match[1]] = array(
                        'model'=>RAID_LANG_UNKNOWN,
                        'ports'=>RAID_LANG_UNKNOWN,
                        'drives'=>RAID_LANG_UNKNOWN
                    );
            }
        }
        foreach ($controllers as $id => $controller) {
            $myarrays[$id]['model'] = RAID_LANG_UNKNOWN;

            try {
                $args = '/proc/scsi/mptsas/' . $id;
                $shell->execute(self::CMD_CAT, $args, FALSE, $options);
                // ioc0: LSISAS1068, FwRev=00063200h, Ports=1, MaxQ=511
                if (preg_match("/^ioc(\d+):\s+LSI(\S+),\s+FwRev=(\S+),\s+Ports=(\d+).*$/", $shell->GetFirstOutputLine(), $match)) {
                    $myarrays[$id]['model'] = $match[2];
                    $myarrays[$id]['ports'] = $match[4];
                }
            } catch (Exception $e) {
                // Do nothing...just model
            }

            $regex1 = "/^ioc:(\d+)\s+vol_id:(\d+)\s+type:(\S+)\s+raidlevel:(\S+)\s+num_disks:(\d+)\s+size\(GB\):(\d+)\s+state:(.+)\s+flags:(.+)$/";
            $regex2 = "/^ioc:(\d+)\s+phys_id:(\d+)\s+scsi_id:(\d+)\s+vendor:(\S+)\s+product_id:(\S+)\s+revision:(\S+)\s+size\(GB\):(\d+)\s+state:\s+(.+)\s+flags:\s+(.+)\s+sync_state:\s+(\d+)\s+(.+)$/";
            foreach ($lines as $line) {
                if (preg_match($regex1, $line, $match)) {
                    // More than 1 unit not possible on these cards?  Let's hope so
                    $myarrays[$match[1]]['units'][0]['level'] = strtoupper($match[4]);
                    $myarrays[$match[1]]['units'][0]['size'] = $match[6]*1024*1024*1024;
                    // Status
                    $myarrays[$match[1]]['units'][0]['status'] = self::STATUS_CLEAN;

                    if (!preg_match("/.*OPTIMAL.*/", $match[7]))
                        $myarrays[$match[1]]['units'][0]['status'] = self::STATUS_DEGRADED;

                    if (preg_match("/.*RESYNC_IN_PROGRESS.*/", $match[8]))
                        $myarrays[$match[1]]['units'][0]['status'] = self::STATUS_SYNCING;
                } else if (preg_match($regex2, $line, $match)) {
                    $myarrays[$match[1]]['units'][0]['devices'][$match[2]]['status'] = self::STATUS_CLEAN;

                    if (!preg_match("/.*ONLINE.*/", $match[8]))
                        $myarrays[$match[1]]['units'][0]['devices'][$match[2]]['status'] = self::STATUS_DEGRADED;

                    if (preg_match("/.*OUT_OF_SYNC.*/", $match[9]) && $myarrays[$match[1]]['units'][0]['status'] == self::STATUS_SYNCING) {
                        $myarrays[$match[1]]['units'][0]['devices'][$match[2]]['status'] = self::STATUS_SYNCING;
                        $myarrays[$match[1]]['units'][0]['devices'][$match[2]]['recovery'] = $match[10];
                    }
                } else if (preg_match("/^p(\d+)\s+OK\s+-\s+(\d+.\d+)\s+(\S+)\s+(\d+)\s+(\S+)$/", $line, $match)) {
                    // Spares?
                    //$myarrays[$id]['spares'][$match[1]] = array('size'=>$match[4]*512, 'serial'=>$match[5]);
                }
            }
        }
        
        ksort($myarrays);

        return $myarrays;
    }

    /**
     * Gets the mapping of a RAID array to physical device as seen by the operating system.
     *
     * @param string $unit a unit on the controller
     *
     * @return string the device
     */

    function get_mapping($unit)
    {
        clearos_profile(__METHOD__, __LINE__);

        $id = '';
        $storage = new StorageDevice();
        $devices = $storage->GetDevices();

        foreach ($devices as $dev => $device) {
            if ($device['vendor'] != 'Dell') // TODO...What about non-Dell branded cards?
                continue;

            if (!preg_match("/^VIRTUAL DISK$/", $device['model'], $match))
                continue;

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
     *
     * @throws Engine_Exception
     */

    function remove_device($controller, $port)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $shell = new ShellExec();
            $args = 'remove c' . $controller . ' p' . $port;
            $options['env'] = "LANG=en_US";
            $retval = $shell->execute(self::CMD_MPT_STATUS, $args, TRUE, $options);

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
     *
     * @throws Engine_Exception
     */

    function repair_array($controller, $unit, $port)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $shell = new ShellExec();
            $args = 'maint rebuild c' . $controller . ' u' . $unit . ' p' . $port;
            $options['env'] = "LANG=en_US";
            $retval = $shell->execute(self::CMD_MPT_STATUS, $args, TRUE, $options);

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
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

}
