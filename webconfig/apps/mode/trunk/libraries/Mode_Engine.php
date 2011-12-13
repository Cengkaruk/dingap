<?php

/**
 * Mode engine class.
 *
 * @category   Apps
 * @package    Mode
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/mode/
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

namespace clearos\apps\mode;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('mode');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Configuration_File as Configuration_File;
use \clearos\apps\base\Engine as Engine;
use \clearos\apps\base\File as File;

clearos_load_library('base/Configuration_File');
clearos_load_library('base/Engine');
clearos_load_library('base/File');

// Exceptions
//-----------

use \clearos\apps\base\File_Not_Found_Exception as File_Not_Found_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;

clearos_load_library('base/File_Not_Found_Exception');
clearos_load_library('base/Validation_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Mode engine class.
 *
 * @category   Apps
 * @package    Mode
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/mode/
 */

class Mode_Engine extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const FILE_CONFIG = '/var/clearos/mode/mode.conf';

    const MODE_SIMPLE_MASTER = 'simple_master';
    const MODE_SIMPLE_SLAVE = 'simple_slave';
    const MODE_MASTER = 'master';
    const MODE_SLAVE = 'slave';
    const MODE_STANDALONE = 'standalone';

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $is_loaded = FALSE;

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Mode engine constructor.
     *
     * @param string $daemon daemon
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Returns mode.
     *
     * @return string mode
     * @throws Engine_Exception
     */

    public function get_mode()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        return $this->config['mode'];
    }

    /**
     * Returns read only status.
     *
     * @return boolean TRUE if mode is read only
     */

    public function is_read_only()
    {
        clearos_profile(__METHOD__, __LINE__);

        $mode = $this->get_mode();

        if (($mode === self::MODE_SIMPLE_SLAVE) || ($mode === self::MODE_SLAVE))
            return TRUE;
        else
            return FALSE;
    }

    /**
     * Sets mode.
     *
     * @param string $mode    mode
     * @param array  $options mode options
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_mode($mode, $options)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_mode($mode));

        $this->_set_parameter('mode', $mode);
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validation routine for mode.
     *
     * @param string $mode mode
     *
     * @return string error message if mode is invalid
     */

    public function validate_mode($mode)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! array_key_exists($mode, $this->modes))
            return lang('mode_mode_is_invalid');
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E  M E T H O D S 
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Loads configuration files.
     *
     * @access private
     * @return void
     * @throws Engine_Exception
     */

    protected function _load_config()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $config_file = new Configuration_File(self::FILE_CONFIG);
            $this->config = $config_file->load();
        } catch (File_Not_Found_Exception $e) {
            // Not fatal
        }

        $this->is_loaded = TRUE;
    }

    /**
     * Sets a parameter in the config file.
     *
     * @param string $key   name of the key in the config file
     * @param string $value value for the key
     *
     * @access private
     * @return void
     * @throws Engine_Exception
     */

    protected function _set_parameter($key, $value)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->is_loaded = FALSE;

        $file = new File(self::FILE_CONFIG);

        if (! $file->exists())
            $file->create("root", "root", "0644");

        $match = $file->replace_lines("/^$key\s*=\s*/", "$key = $value\n");

        if (!$match)
            $file->add_lines("$key = $value\n");
    }

}
