<?php

/**
 * Freshclam class.
 *
 * @category   Apps
 * @package    Antivirus
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2007-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/antivirus/
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

namespace clearos\apps\antivirus;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('antivirus');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Configuration_File as Configuration_File;
use \clearos\apps\base\Engine as Engine;
use \clearos\apps\base\File as File;
use \clearos\apps\base\Shell as Shell;

clearos_load_library('base/Configuration_File');
clearos_load_library('base/Engine');
clearos_load_library('base/File');
clearos_load_library('base/Shell');

// Exceptions
//-----------

use \clearos\apps\base\Validation_Exception as Validation_Exception;

clearos_load_library('base/Validation_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Freshclam class.
 *
 * @category   Apps
 * @package    Antivirus
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2007-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/antivirus/
 */

class Freshclam extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const FILE_CONFIG = '/etc/freshclam.conf';
    const FILE_MIRRORS = '/var/lib/clamav/mirrors.dat';
    const CMD_FRESHCLAM = '/usr/bin/freshclam';
    const DEFAULT_CHECKS = 12;

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $is_loaded = FALSE;
    protected $config = array();

    ///////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////

    /**
     * Freshclam constructor.
     */

    public function __construct() 
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Returns information on the mirror that provided the last update.
     *
     * @return array mirror information
     * @throws Engine_Exception
     */

    public function get_last_change_info()
    {
        clearos_profile(__METHOD__, __LINE__);

        $details = $this->get_mirror_details();

        $last_update = 0;
        $update_mirror = array();

        foreach ($details as $mirror => $mirror_info) {
            foreach ($mirror_info as $key => $value) {
                if (($key == 'accessed') && ($value >= $last_update)) {
                    $last_update = $value;
                    $update_mirror = $mirror_info;
                }
            }
        }

        return $update_mirror;
    }

    /**
     * Returns time of last attempted update.
     *
     * @return integer time since last attempted update
     * @throws Engine_Exception
     */

    public function get_last_check()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_MIRRORS);

        if (! $file->exists())
            return 0;

        $modified = $file->last_modified();

        return $modified;
    }

    /**
     * Returns details on the update mirrors.
     *
     * @return array details on the update mirrors
     * @throws Engine_Exception
     */

    public function get_mirror_details()
    {
        clearos_profile(__METHOD__, __LINE__);

        // If no update has occurred, return an empty array
        $file = new File(self::FILE_MIRRORS);

        if (! $file->exists())
            return array();

        $shell = new Shell();
        $options['env'] = 'LANG=en_US';

        $shell->execute(self::CMD_FRESHCLAM, '--list-mirrors', TRUE, $options);
        $rawdata = $shell->get_output();

        $current = 1;

        foreach ($rawdata as $item) {
            $matches = array();

            if (preg_match('/^Mirror #(\d+)/i', $item, $matches)) {
                $current = $matches[1];
            } else if (preg_match('/^IP: ([\d\.]+)/i', $item, $matches)) {
                $details[$current]['ip'] = $matches[1];
            } else if (preg_match('/^Successes: ([\d]+)/i', $item, $matches)) {
                $details[$current]['successes'] = $matches[1];
            } else if (preg_match('/^Failures: ([\d]+)/i', $item, $matches)) {
                $details[$current]['failures'] = $matches[1];
            } else if (preg_match('/^Ignore: (\w+)/i', $item, $matches)) {
                $details[$current]['ignore'] = $matches[1];
            } else if (preg_match('/^Last access: (.*)/i', $item, $matches)) {
                $details[$current]['accessed'] = strtotime($matches[1]);
            }
        }

        return $details;
    }

    /**
     * Returns the number of antivirus updates per day.
     *
     * @return integer number of updates per day
     * @throws Engine_Exception
     */

    public function get_checks_per_day()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!$this->is_loaded)
            $this->_load_config();

        if (isset($this->config['Checks']))
            return $this->config['Checks'];
        else
            return self::DEFAULT_CHECKS;
    }

    /**
     * Sets the number of antivirus updates per day.
     *
     * @param integer $count updates number of updates per day
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_checks_per_day($count)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_checks_per_day($count));

        $this->_set_parameter('Checks', $count);
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validation for checks per day.
     *
     * @param integer $count number of checks per day
     *
     * @return error message if number of checks is invalid
     */

    public function validate_checks_per_day($count)
    {
        clearos_profile(__METHOD__, __LINE__);

        $count = (int)$count;

        if (!is_int($count) || ($count < 1) || ($count > 24))
            return lang('antivirus_updates_per_day_invalid');
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E  M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Sets a parameter.
     *
     * @param string $key   key
     * @param string $value value for preference
     *
     * @access private
     * @return void
     * @throws Engine_Exception
     */

    protected function _set_parameter($key, $value)
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_CONFIG);
        $match = $file->replace_lines("/^$key\s+/", "$key $value\n");

        if ($match === 0)
            $file->add_lines("$key $value\n");

        $this->is_loaded = FALSE;
    }

    /**
     * Loads configuration file.
     *
     * @access private
     * @return void
     * @throws Engine_Exception
     */

    protected function _load_config()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new Configuration_File(self::FILE_CONFIG, 'split', '\s+');
        $this->config = $file->Load();
        $this->is_loaded = TRUE;
    }
}
