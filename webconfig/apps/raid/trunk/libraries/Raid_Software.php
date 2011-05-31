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
 * Raid_Software class.
 *
 * @category   Apps
 * @package    Raid
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/raid/
 */

class Raid_Software extends Raid
{
    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    const CMD_MDADM = '/sbin/mdadm';
    const CMD_DD = '/bin/dd';
    protected $interactive = TRUE;
    protected $mdstat = Array();

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * RaidSoftware constructor.
     *
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        parent::__construct();

        $this->type = self::TYPE_SOFTWARE;
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

        $this->_get_md_stat();

        $dev = '';
        $physical_devices = Array();
        $raid_level = 0;
        $clean_array = TRUE;
        foreach ($this->mdstat as $line) {
            if (preg_match("/^md([[:digit:]]+)[[:space:]]*:[[:space:]]*(.*)$/", $line, $match)) {
                $dev = '/dev/md' . $match[1];
                list($state, $level, $device_list) = explode(' ', $match[2], 3);
                // Always 'active' and not very useful
                $myarrays[$dev]['state'] = $state;
                $myarrays[$dev]['status'] = self::STATUS_CLEAN;
                $myarrays[$dev]['level'] = strtoupper($level);
                // Try to format for consistency (RAID-1, not RAID1)
                if (preg_match("/^RAID(\d+)$/", strtoupper($level), $match)) {
                    $myarrays[$dev]['level'] = 'RAID-' . $match[1];
                    $raid_level = $match[1];
                }
                
                $devices = explode(' ', $device_list);
                $members = Array();
                foreach ($devices as $device) {
                    if (preg_match("/^(.*)\\[([[:digit:]]+)\\](.*)$/", trim($device), $match))
                        $members[$match[2]] = preg_match("/^\\/dev\\//", $match[1]) ? $match[1] : '/dev/' . $match[1];
                }
                ksort($members);
                foreach ($members as $index => $member) {
                    $myarrays[$dev]['devices'][$index]['dev'] = $member;
                    
                    if (!in_array(preg_replace("/\d+/", "", $member), $physical_devices))
                        $physical_devices[] = preg_replace("/\d+/", "", $member);
                }
            } else if (preg_match("/^[[:space:]]*([[:digit:]]+)[[:space:]]*blocks[[:space:]]*.*\[(.*)\]$/", $line, $match)) {
                $myarrays[$dev]['size'] = $match[1]*1024;
                $clean_array = FALSE;
                if (preg_match("/.*_.*/", $match[2]))
                    $myarrays[$dev]['status'] = self::STATUS_DEGRADED;
                $status = str_split($match[2]);
                $myarrays[$dev]['number'] = count($status);
                $counter = 0;
                foreach ($myarrays[$dev]['devices'] as $index => $myarray) {
                    // If in degraded mode, any index greater than or equal to total disk has failed
                    if ($index >= $myarrays[$dev]['number']) {
                        $myarrays[$dev]['devices'][$index]['status'] = self::STATUS_SPARE;
                        continue;
                    } else if ($status[$counter] == "_") {
                        $myarrays[$dev]['devices'][$index]['status'] = self::STATUS_DEGRADED;
                    } else {
                        $myarrays[$dev]['devices'][$index]['status'] = self::STATUS_CLEAN;
                    }
                    $counter++;
                }
            } else if (preg_match("/^[[:space:]]*(.*)recovery =[[:space:]]+([[:digit:]]+\\.[[:digit:]]+)%[[:space:]]*(.*)$/", $line, $match)) {
                $clean_array = FALSE;
                foreach ($myarrays[$dev]['devices'] as $index => $myarray) {
                    if ($myarrays[$dev]['devices'][$index]['status'] == self::STATUS_DEGRADED) {
                        $myarrays[$dev]['devices'][$index]['status'] = self::STATUS_SYNCING;
                        $myarrays[$dev]['devices'][$index]['recovery'] = $match[2];
                    }
                }
            } else if (preg_match("/^[[:space:]]*(.*)resync =[[:space:]]+([[:digit:]]+\\.[[:digit:]]+)%[[:space:]]*(.*)$/", $line, $match)) {
                $clean_array = FALSE;
                $this->_SetParameter('copy_mbr', '0');
                foreach ($myarrays[$dev]['devices'] as $index => $myarray) {
                    $myarrays[$dev]['devices'][$index]['status'] = self::STATUS_SYNCING;
                    $myarrays[$dev]['devices'][$index]['recovery'] = $match[2];
                }
            } else if (preg_match("/^.*resync=DELAYED.*$/", $line, $match)) {
                $clean_array = FALSE;
                foreach ($myarrays[$dev]['devices'] as $index => $myarray)
                    $myarrays[$dev]['devices'][$index]['status'] = self::STATUS_SYNC_PENDING;
            }
        }
        
        ksort($myarrays);
        //if ((!isset($this->config['copy_mbr']) || $this->config['copy_mbr'] == 0) && $raid_level == 1 && $clean_array) {
        if (FALSE) {
            sort($physical_devices);
            $is_first = TRUE;
            foreach ($physical_devices as $dev) {
                if ($is_first) {
                    $copy_from = $dev;
                    $is_first = FALSE;
                    continue;
                }
                $shell = new Shell();
                $args = 'if=' . $copy_from . ' of=' . $dev . ' bs=512 count=1';
                $retval = $shell->execute(self::CMD_DD, $args, TRUE);
            }
            $this->_SetParameter('copy_mbr', '1');
            $this->loaded = FALSE;
        }
        return $myarrays;
    }

    /**
     * Removes a device from the specified array.
     *
     * @param string $array  the array
     * @param string $device the device
     *
     * @return void
     * @throws Engine_Exception
     */

    function remove_device($array, $device)
    {
        clearos_profile(__METHOD__, __LINE__);

        $shell = new Shell();
        $args = '-r ' . $array . ' ' . $device;
        $options['env'] = "LANG=en_US";
        $retval = $shell->execute(self::CMD_MDADM, $args, TRUE, $options);
        if ($retval != 0) {
            $errstr = $shell->get_last_output_line();
            throw new Engine_Exception($errstr, COMMON_WARNING);
        } else {
            $this->mdstat = $shell->get_output();
        }
    }

    /**
     * Repair an array with the specified device.
     *
     * @param string $array  the array
     * @param string $device the device
     *
     * @return void
     * @throws Engine_Exception
     */

    function repair_array($array, $device)
    {
        clearos_profile(__METHOD__, __LINE__);

        $shell = new Shell();
        $args = '-a ' . $array . ' ' . $device;
        $options['env'] = "LANG=en_US";
        $retval = $shell->execute(self::CMD_MDADM, $args, TRUE, $options);
        if ($retval != 0) {
            $errstr = $shell->get_last_output_line();
            throw new Engine_Exception($errstr, COMMON_WARNING);
        } else {
            $this->mdstat = $shell->get_output();
        }
    }


    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Gets the status according to mdstat.
     *
     * @access private
     *
     * @return void
     * @throws Engine_Exception
     */
    function _get_md_stat()
    {
        clearos_profile(__METHOD__, __LINE__);

        $shell = new Shell();
        $args = self::FILE_MDSTAT;
        $options['env'] = "LANG=en_US";
        $retval = $shell->execute(self::CMD_CAT, $args, FALSE, $options);
        if ($retval != 0) {
            $errstr = $shell->get_last_output_line();
            throw new Engine_Exception($errstr, COMMON_WARNING);
        } else {
            $this->mdstat = $shell->get_output();
        }
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

}
