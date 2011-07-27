<?php

/**
 * ClearOS user engine.
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

use \clearos\apps\base\Engine as Engine;

clearos_load_library('base/Engine');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * ClearOS user engine.
 *
 * @category   Apps
 * @package    Users
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/users/
 */

class User_Engine extends Engine
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

    // Capabilities
    //-------------

    const CAPABILITY_READ_ONLY = 'read_only';
    const CAPABILITY_READ_WRITE = 'read_write';

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    public static $builtin_list = array(
        'email-archive',
        'flexshare',
        'guest',
        'winadmin'
    );

    public static $system_list = array();

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * User_Engine constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);
    }
}
