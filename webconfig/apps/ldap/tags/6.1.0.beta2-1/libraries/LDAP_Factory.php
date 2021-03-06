<?php

/**
 * ClearOS LDAP factory.
 *
 * @category   Apps
 * @package    LDAP
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/ldap/
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

namespace clearos\apps\ldap;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('ldap');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Note: factory drivers are loaded on the fly

use \clearos\apps\base\Engine as Engine;

clearos_load_library('base/Engine');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * ClearOS LDAP factory.
 *
 * @category   Apps
 * @package    LDAP
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/ldap/
 */

class LDAP_Factory extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    // LDAP modes
    const MODE_MASTER = 'master';
    const MODE_SLAVE = 'slave';
    const MODE_STANDALONE = 'standalone';

    // Security policies
    const POLICY_LAN = 'lan';
    const POLICY_LOCALHOST = 'localhost';

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * LDAP constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Creates an LDAP instance via the factory framwork.
     *
     * @param string $driver driver name
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public static function create($driver = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        // TODO: move this to a config file
        if ($driver === NULL)
            $driver = 'openldap';

        clearos_load_library($driver . '/LDAP_Driver');

        $class = '\clearos\apps\\' . $driver . '\\LDAP_Driver';

        return new $class;
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

        // TODO: move this to a config file
        $driver = 'openldap';

        return $driver . '/LDAP_Driver';
    }
}
