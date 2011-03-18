<?php

/**
 * Password policy OpenLDAP directory extension.
 *
 * @category   Apps
 * @package    Password_Policies
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/password_policies/
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

namespace clearos\apps\password_policies;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('base');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Engine as Engine;
use \clearos\apps\openldap\Utilities as Utilities;

clearos_load_library('base/Engine');
clearos_load_library('openldap/Utilities');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Password policy OpenLDAP directory extension.
 *
 * @category   Apps
 * @package    Password_Policies
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/password_policies/
 */

class OpenLDAP_Extension extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $info_map = array();

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Password policies OpenLDAP_Extension constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->info_map = array(
            'bad_password_lockout' => array(
                'type' => 'integer',
                'required' => TRUE,
                'validator' => 'validate_bad_password_lockout',
                'objectclass' => 'pwdPolicy',
                'attribute' => 'pwdLockout'
            ),
            'bad_password_lockout_duration' => array(
                'type' => 'integer',
                'required' => TRUE,
                'validator' => 'validate_bad_password_lockout_duration',
                'objectclass' => 'pwdPolicy',
                'attribute' => 'pwdLockoutDuration'
            ),
            'bad_password_lockout_attempts' => array(
                'type' => 'integer',
                'required' => TRUE,
                'validator' => 'validate_bad_password_lockout_attempts',
                'objectclass' => 'pwdPolicy',
                'attribute' => 'pwdMaxFailure'
            ),
            'history_size' => array(
                'type' => 'integer',
                'required' => TRUE,
                'validator' => 'validate_history_size',
                'objectclass' => 'pwdPolicy',
                'attribute' => 'pwdInHistory'
            ),
            'maximum_age' => array(
                'type' => 'integer',
                'required' => TRUE,
                'validator' => 'validate_maximum_age',
                'objectclass' => 'pwdPolicy',
                'attribute' => 'pwdMaxAge'
            ),
            'minimum_age' => array(
                'type' => 'integer',
                'required' => TRUE,
                'validator' => 'validate_minimum_age',
                'objectclass' => 'pwdPolicy',
                'attribute' => 'pwdMinAge'
            ),
            'minimum_length' => array(
                'type' => 'integer',
                'required' => TRUE,
                'validator' => 'validate_minimum_length',
                'objectclass' => 'pwdPolicy',
                'attribute' => 'pwdMinLength'
            ),
        );
    }

    /** 
     * Adds LDAP attributes for given user info hash array.
     *
     * @param array $user_info user information in hash array
     *
     * @return array LDAP attributes
     * @throws Engine_Exception
     */

    public function add_attributes_hook($user_info)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Set defaults
        //-------------

/*
        if (file_exists(CLEAROS_CORE_DIR . "/api/PasswordPolicy.class.php")) {
            require_once("PasswordPolicy.class.php");
            $policy = new PasswordPolicy();
            $policy->Initialize();
            $ldap_object['pwdPolicySubentry'] = "cn=" . LdapPasswordPolicy::DEFAULT_DIRECTORY_OBJECT . "," . ClearDirectory::GetPasswordPoliciesOu();
        }
*/

        // Convert to LDAP attributes
        //---------------------------

        $attributes = Utilities::convert_array_to_attributes($user_info['pbx'], $this->info_map);

        return $attributes;
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
}
