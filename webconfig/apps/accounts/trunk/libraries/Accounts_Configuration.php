<?php

/**
 * Accounts configuration class.
 *
 * @category   Apps
 * @package    Accounts
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/accounts/
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

namespace clearos\apps\accounts;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('accounts');

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
 * Accounts configuration class.
 *
 * @category   Apps
 * @package    Accounts
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/accounts/
 */

class Accounts_Configuration extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const FILE_STATE = '/var/clearos/accounts/state';
    const PATH_DRIVERS = '/var/clearos/accounts/drivers';

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
     * Returns the accounts driver.
     *
     * @return string accounts driver
     * @throws Engine_Exception
     */

    public function get_driver()
    {
        clearos_profile(__METHOD__, __LINE__);

        return self::get_accounts_driver();
    }

    /**
     * Returns the accounts driver information.
     *
     * @return array accounts driver information
     * @throws Engine_Exception
     */

    public function get_driver_info()
    {
        clearos_profile(__METHOD__, __LINE__);

        return self::get_accounts_driver_info();
    }

    /**
     * Returns the list of installed accounts drivers.
     *
     * @return array accounts drivers
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
     * Sets the accounts driver.
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
    // F R I E N D   R O U T I N E S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the accounts driver.
     *
     * The Accounts_Factory classes uses this method.  To avoid circular references
     * in the factory class, this common static routine was created.
     *
     * @access private
     * @return string accounts driver
     * @throws Engine_Exception
     */

    public static function get_accounts_driver()
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
     * Returns the accounts driver information.
     *
     * @access private
     * @return array accounts driver information
     * @throws Engine_Exception
     */

    public static function get_accounts_driver_info()
    {
        clearos_profile(__METHOD__, __LINE__);

        $driver_name = self::get_accounts_driver();

        include self::PATH_DRIVERS . '/' . $driver_name . '.php';

        return $driver;
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
            return lang('accounts_accounts_driver_is_invalid');
    }
}
