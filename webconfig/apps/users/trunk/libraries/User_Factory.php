<?php

/**
 * ClearOS user factory.
 *
 * @category   Apps
 * @package    Users
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/users/
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

namespace clearos\apps\users;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('users');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\accounts\Accounts_Configuration as Accounts_Configuration;
use \clearos\apps\base\Engine as Engine;

clearos_load_library('accounts/Accounts_Configuration');
clearos_load_library('base/Engine');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * ClearOS user factory.
 *
 * @category   Apps
 * @package    Users
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/users/
 */

class User_Factory extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    // User types
    //-----------

    const TYPE_BUILTIN = 'builtin';
    const TYPE_NORMAL = 'normal';
    const TYPE_SYSTEM = 'system';
    const TYPE_UNKNOWN = 'unknown';
    const TYPE_ALL = 'all';

    // Password types
    //---------------

    const PASSWORD_TYPE_SHA = 'sha';
    const PASSWORD_TYPE_SHA1 = 'sha1';
    const PASSWORD_TYPE_NT = 'nt';

    // Account status codes
    //---------------------

    const STATUS_LOCKED = 'locked';
    const STATUS_UNLOCKED = 'unlocked';
    const STATUS_ENABLED = 'enabled';
    const STATUS_DISABLED = 'disabled';

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * User constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Creates a user instance via the factory framwork.
     *
     * @param string $username username
     *
     * @return void
     * @throws Engine_Exception
     */

    public static function create($username)
    {
        clearos_profile(__METHOD__, __LINE__);

        $driver_location = Accounts_Configuration::get_driver_location();

        clearos_load_library($driver_location . '/User_Driver');

        $class = '\clearos\apps\\' . $driver_location . '\\User_Driver';

        return new $class($username);
    }

    /**
     * Returns the driver name for use in the framework.
     *
     * This method is used by the web framework to create a factory object.
     * Though the method is public, it is only intended for the web framework.
     *
     * @access private
     * @return string driver name
     * @throws Engine_Exception
     */

    public function framework_create()
    {
        clearos_profile(__METHOD__, __LINE__);

        $driver_location = Accounts_Configuration::get_driver_location();

        return $driver_location . '/User_Driver';
    }
}
