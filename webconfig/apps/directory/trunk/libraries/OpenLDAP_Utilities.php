<?php

/**
 * ClearOS OpenLDAP utilities class.
 *
 * @category   Apps
 * @package    Directory
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2006-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/directory/
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

namespace clearos\apps\directory;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

// clearos_load_language('base');
// clearos_load_language('directory');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Configuration_File as Configuration_File;
use \clearos\apps\base\Engine as Engine;
use \clearos\apps\directory\OpenLDAP as OpenLDAP;

clearos_load_library('base/Configuration_File');
clearos_load_library('base/Engine');
clearos_load_library('directory/OpenLDAP');

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;

clearos_load_library('base/Engine_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * ClearOS OpenLDAP utilities class.
 *
 * @category   Apps
 * @package    Directory
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2006-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/directory/
 */

class OpenLDAP_Utilities extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const COMMAND_OPENSSL = "/usr/bin/openssl";
    const FILE_OPENLDAP_CONFIG = '../config/openldap.php';

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Directory utilities constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Converts LDAP attributes into a userinfo array.
     *
     * The attributes array that comes from an ldap_read is not what we want to
     * send back to the API call.  Instead, a basic "userinfo" PHP array is created
     * by mapping LDAP attributes like: 
     *
     *    [facsimileTelephoneNumber] => Array
     *        (
     *            [count] => 1
     *            [0] => 1234567
     *        )
     *    [7] => facsimileTelephoneNumber
     *
     * To:
     *
     *   [fax] => 1234567 
     *
     * @param string $attributes LDAP attributes
     * @param string $mapping    attribute to array mapping information
     *
     * @return array attributes in a PHP friendly array
     */

    public static function convert_attributes_to_array($attributes, $mapping)
    {
        clearos_profile(__METHOD__, __LINE__);

        $userinfo = array();

        foreach ($mapping as $infoname => $detail) {
            if (empty($attributes[$detail['attribute']])) {
                if ($detail['type'] == 'boolean')
                    $userinfo[$infoname] = FALSE;
                else
                    $userinfo[$infoname] = NULL;
            } else {
                if ($infoname != 'password') {
                    if ($detail['type'] == 'boolean') {
                        $userinfo[$infoname] = ($attributes[$detail['attribute']][0] == 'TRUE') ? TRUE : FALSE;
                    } elseif ($detail['type'] == 'stringarray') {
                        array_shift($attributes[$detail['attribute']]);
                        $userinfo[$infoname] = $attributes[$detail['attribute']];
                    } else {
                        $userinfo[$infoname] = $attributes[$detail['attribute']][0];
                    }
                }
            }
        }

        return $userinfo;
    }

    /**
     * Creates an LDAP connection handle.
     *
     * Many libraries that use OpenLDAP need to:
     *
     * - grab LDAP credentials for connecting to the server
     * - connect to LDAP
     * - perform a bunch of LDAP acctions (search, read, etc)
     *
     * This method provides a common function for doing the firt two steps.
     *
     * @return LDAP handle
     * @throws Engine_Exception
     */

    public static function get_ldap_handle()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $config_file = new Configuration_File(self::FILE_OPENLDAP_CONFIG, 'split', '=', 2);
            $config = $config_file->load();
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        try {
            $ldaph = new OpenLDAP($config['base_dn'], $config['bind_dn'], $config['bind_pw']);
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        return $ldaph;
    }
}
