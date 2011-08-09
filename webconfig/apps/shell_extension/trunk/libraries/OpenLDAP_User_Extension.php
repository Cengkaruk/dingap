<?php

/**
 * Login shell OpenLDAP user extension.
 *
 * @category   Apps
 * @package    Shell_Extension
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/shell_extension/
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

namespace clearos\apps\shell_extension;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('shell_extension');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Engine as Engine;
use \clearos\apps\base\Login_Shell as Login_Shell;
use \clearos\apps\openldap_directory\Utilities as Utilities;

clearos_load_library('base/Engine');
clearos_load_library('base/Login_Shell');
clearos_load_library('openldap_directory/Utilities');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Login shell OpenLDAP user extension.
 *
 * @category   Apps
 * @package    Shell_Extension
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/shell_extension/
 */

class OpenLDAP_User_Extension extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $info_map = array();

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Login shell OpenLDAP_User_Extension constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        include clearos_app_base('shell_extension') . '/deploy/user_map.php';

        $this->info_map = $info_map;
    }

    /** 
     * Add LDAP attributes hook.
     *
     * @param array $user_info user information in hash array
     * @param array $ldap_object LDAP object
     *
     * @return array LDAP attributes
     * @throws Engine_Exception
     */

    public function add_attributes_hook($user_info, $ldap_object)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Set defaults
        //-------------

        $user_info['extensions']['shell']['login_shell'] = '/sbin/nologin';

        // Convert to LDAP attributes
        //---------------------------

        $attributes = Utilities::convert_array_to_attributes($user_info['extensions']['shell'], $this->info_map);

        return $attributes;
    }

    /**
     * Returns default values.
     *
     * @param array $attributes LDAP attributes
     *
     * @return array user info array
     * @throws Engine_Exception
     */

    public function get_info_defaults_hook($attributes)
    {
        clearos_profile(__METHOD__, __LINE__);

        $info['login_shell'] = '/sbin/nologin';

        return $info;
    }

    /**
     * Returns user info hash array.
     *
     * @param array $attributes LDAP attributes
     *
     * @return array user info array
     * @throws Engine_Exception
     */

    public function get_info_hook($attributes)
    {
        clearos_profile(__METHOD__, __LINE__);

        $info = Utilities::convert_attributes_to_array($attributes, $this->info_map);

        return $info;
    }

    /**
     * Returns user info map hash array.
     *
     * @return array user info array
     * @throws Engine_Exception
     */

    public function get_info_map_hook()
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->info_map;
    }

    /** 
     * Update LDAP attributes hook.
     *
     * @param array $user_info user information in hash array
     * @param array $ldap_object LDAP object
     *
     * @return array LDAP attributes
     * @throws Engine_Exception
     */

    public function update_attributes_hook($user_info, $ldap_object)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Return if nothing needs to be done
        //-----------------------------------

        if (! isset($user_info['extensions']['shell']))
            return array();

        // Convert to LDAP attributes
        //---------------------------

        $attributes = Utilities::convert_array_to_attributes($user_info['extensions']['shell'], $this->info_map);

        return $attributes;
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validation routine for login shell.
     *
     * @param string $shell login shell
     *
     * @return boolean TRUE if login shell is valid
     */

    public function validate_login_shell($shell)
    {
        clearos_profile(__METHOD__, __LINE__);

        $login_shell = new Login_Shell();
        $login_list = $login_shell->get_list();

        if (! in_array($shell, $login_list))
            return lang('shell_extension_login_shell_invalid');
    }
}
