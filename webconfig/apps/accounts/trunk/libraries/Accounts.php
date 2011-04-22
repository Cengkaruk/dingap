<?php

/**
 * ClearOS directory manager factory.
 *
 * @category   Apps
 * @package    Directory_Manager
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/directory_manager/
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

namespace clearos\apps\directory_manager;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('directory_manager');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Engine as Engine;
use \clearos\apps\base\File as File;
use \clearos\apps\base\Folder as Folder;

clearos_load_library('base/Engine');
clearos_load_library('base/File');
clearos_load_library('base/Folder');

// Exceptions
//-----------

use \clearos\apps\base\Validation_Exception as Validation_Exception;

clearos_load_library('base/Validation_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * ClearOS directory factory.
 *
 * @category   Apps
 * @package    Directory_Manager
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/directory_manager/
 */

class Directory_Manager extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const DRIVER_ACTIVE_DIRECTORY = 'active_directory';
    const DRIVER_OPENLDAP = 'openldap';
    const DRIVER_SAMBA = 'samba';

    const FILE_STATE = '/var/clearos/directory_manager/state';
    const PATH_DRIVERS = '/var/clearos/directory_manager/drivers';
    const PATH_PLUGINS = '/var/clearos/directory_manager/plugins';

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Directory manager constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Returns the directory driver.
     *
     * @return string directory driver
     * @throws Engine_Exception
     */

    public function get_driver()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_STATE);

        if ($file->exists())
            $driver = $file->lookup_value('/^driver =/');
        else
            $driver = '';

        return $driver;
    }

    /**
     * Returns the list of installed directory drivers.
     *
     * @return array directory drivers
     * @throws Engine_Exception
     */

    public function get_drivers()
    {
        clearos_profile(__METHOD__, __LINE__);

        $drivers = array();

        $folder = new Folder(self::PATH_DRIVERS);

        $list = $folder->get_listing();

        foreach ($list as $driver_file) {
            if (! preg_match('/^\./', $driver_file)) {
                $driver = array();
                $name = preg_replace('/\.php$/', '', $driver_file);

                include self::PATH_DRIVERS . '/' . $driver_file;

                $drivers[$name] = $driver;
            }
        }

        return $drivers;
    }

    /**
     * Returns a list of installed plugins.
     *
     * @return array plugin list
     * @throws Engine_Exception
     */

    public function get_plugins()
    {
        clearos_profile(__METHOD__, __LINE__);

        // FIXME
        $folder = new Folder(self::PATH_PLUGINS);

        $list = $folder->get_listing();

        foreach ($list as $plugin_file) {
            if (! preg_match('/\.php$/', $plugin_file))
                continue;

            $plugin = array();
            $plugin_basename = preg_replace('/\.php/', '', $plugin_file);

            include self::PATH_PLUGINS . '/' . $plugin_file;

            $plugins[$plugin_basename] = $plugin;
        }

        return $plugins;
    }

    /**
     * Returns the plugin map.
     *
     * @return array plugin map
     * @throws Engine_Exception
     */

    public function get_plugin_map()
    {
        clearos_profile(__METHOD__, __LINE__);

        $map = array(
            'state' => array(
                'type' => 'boolean',
                'field_type' => 'toggle',
                'required' => TRUE,
                'validator' => 'validate_state',
                'validator_class' => 'directory_manager/Directory_Manager',
                'description' => lang('directory_manager_state')
            )
        );

        return $map;
    }

    /**
     * Sets the directory driver.
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_driver($driver)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_driver($driver));

        $file = new File(self::FILE_STATE);

        if ($file->exists())
            $file->delete();

        $file->create('root', 'root', '0644');

        $file->add_lines("driver = $driver\n");
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   R O U T I N E S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validates driver.
     *
     * @param string $driver driver
     *
     * @return string error message if driver is invalid
     * @throws Engine_Exception
     */

    public function validate_driver($driver)
    {
        clearos_profile(__METHOD__, __LINE__);

        $drivers = $this->get_drivers();

        if (! array_key_exists($driver, $drivers))
            return lang('directory_manager_directory_driver_is_invalid');
    }

    /**
     * Validates plugin state.
     *
     * @param string $state state
     *
     * @return string error message if state is invalid
     * @throws Engine_Exception
     */

    public function validate_state($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        // FIXME
    }
}
