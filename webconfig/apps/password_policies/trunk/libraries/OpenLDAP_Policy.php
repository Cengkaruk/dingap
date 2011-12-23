<?php

/**
 * OpenLDAP password policies class.
 *
 * @category   Apps
 * @package    Password_Policies
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2010-2011 ClearFoundation
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

clearos_load_language('password_policies');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Engine as Engine;
use \clearos\apps\openldap\LDAP_Driver as LDAP_Driver;
use \clearos\apps\openldap_directory\OpenLDAP as OpenLDAP;

clearos_load_library('base/Engine');
clearos_load_library('openldap/LDAP_Driver');
clearos_load_library('openldap_directory/OpenLDAP');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * OpenLDAP password policies class.
 *
 * @category   Apps
 * @package    Password_Policies
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2010-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/password_policies/
 */

class OpenLDAP_Policy extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const DEFAULT_DIRECTORY_OBJECT = 'default';
    const CONSTANT_NO_HISTORY = 0;
    const CONSTANT_NO_EXPIRE = 0;
    const CONSTANT_MODIFY_ANY_TIME = 0;

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * OpenLDAP password policy engine constructor.
     *
     * @return void
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Returns default password policy information.
     *
     * @return array default password policy information
     * @throws Engine_Exception
     */

    public function get_default_policy()
    {
        clearos_profile(__METHOD__, __LINE__);

        $ldap = new LDAP_Driver();
        $ldaph = $ldap->get_ldap_handle();

        $dn = 'cn=' . self::DEFAULT_DIRECTORY_OBJECT . ',' . OpenLDAP::get_password_policies_container();

        if (! $ldaph->exists($dn))
            $this->initialize();

        $ldap_info = $ldaph->read($dn);

        $info['maximum_age'] = $ldap_info['pwdMaxAge'][0];
        $info['minimum_age'] = $ldap_info['pwdMinAge'][0];
        $info['minimum_length'] = $ldap_info['pwdMinLength'][0];
        $info['history_size'] = $ldap_info['pwdInHistory'][0];
        $info['bad_password_lockout'] = ($ldap_info['pwdLockout'][0] === 'TRUE') ? TRUE : FALSE;
        $info['bad_password_lockout_duration'] = $ldap_info['pwdLockoutDuration'][0];
        $info['bad_password_lockout_attempts'] = $ldap_info['pwdMaxFailure'][0];

        return $info;
    }

    /**
     * Initializes password policy system.
     *
     * @return void
     * @throws Engine_Exception
     */

    public function initialize()
    {
        clearos_profile(__METHOD__, __LINE__);

        $ldap = new LDAP_Driver();
        $ldaph = $ldap->get_ldap_handle();

        // Add password policy container
        //------------------------------

        $dn = OpenLDAP::get_password_policies_container();

        if (! $ldaph->exists($dn)) {
            $ou_attributes['objectClass'] = array('top', 'organizationalUnit');
            $ou_attributes['ou'] = OpenLDAP::get_password_policies_container();
            $ldaph->add($dn, $ou_attributes);
        }

        // Add default policy
        //-------------------

        $dn = 'cn=' . self::DEFAULT_DIRECTORY_OBJECT . ',' . OpenLDAP::get_password_policies_container();

        if (! $ldaph->exists($dn)) {
            $policy_attributes['objectClass'] = array('top', 'person', 'pwdPolicy');
            $policy_attributes['cn'] = 'default';
            $policy_attributes['sn'] = 'password policy';
            $policy_attributes['pwdAllowUserChange'] = 'TRUE';
            $policy_attributes['pwdAttribute'] = 'userPassword';
            $policy_attributes['pwdCheckQuality'] = '2';
            $policy_attributes['pwdExpireWarning'] = '600';
            $policy_attributes['pwdFailureCountInterval'] = '30';
            $policy_attributes['pwdGraceAuthNLimit'] = '5';
            $policy_attributes['pwdInHistory'] = '5';
            $policy_attributes['pwdLockout'] = 'FALSE';
            $policy_attributes['pwdLockoutDuration'] = '0';
            $policy_attributes['pwdMaxAge'] = '0';
            $policy_attributes['pwdMaxFailure'] = '5';
            $policy_attributes['pwdMinAge'] = '0';
            $policy_attributes['pwdMinLength'] = '5';
            $policy_attributes['pwdMustChange'] = 'FALSE';
            $policy_attributes['pwdSafeModify'] = 'FALSE';

            $ldaph->add($dn, $policy_attributes);
        }
    }

    /**
     * Sets global policy.
     *
     * @param array $settings settings object
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_default_policy($settings)
    {
        clearos_profile(__METHOD__, __LINE__);

        $ldap = new LDAP_Driver();
        $ldaph = $ldap->get_ldap_handle();

        $dn = 'cn=' . self::DEFAULT_DIRECTORY_OBJECT . ',' . OpenLDAP::get_password_policies_container();

        if (! $ldaph->exists($dn))
            $this->initialize();

        // Update default password policy object
        //--------------------------------------

        $attributes['pwdMaxAge'] = $settings['maximum_age'];
        $attributes['pwdMinAge'] = $settings['minimum_age'];
        $attributes['pwdMinLength'] = $settings['minimum_length'];
        $attributes['pwdInHistory'] = $settings['history_size'];
        $attributes['pwdLockout'] = ($settings['bad_password_lockout']) ? 'TRUE' : 'FALSE';

        $ldaph->modify($dn, $attributes);
    }

    /**
     * Applies default policy to all users.
     *
     * @return void
     * @throws Engine_Exception
     */

    public function apply_default_policy()
    {
        clearos_profile(__METHOD__, __LINE__);

        $ldap = new LDAP_Driver();
        $ldaph = $ldap->get_ldap_handle();

        $dn = 'cn=' . self::DEFAULT_DIRECTORY_OBJECT . ',' . OpenLDAP::get_password_policies_container();

        if (! $ldaph->exists($dn))
            $this->initialize();

        $result = $ldaph->search(
            "(&(objectclass=clearAccount)(!(pwdPolicySubentry=$dn)))",
            OpenLDAP::get_users_container(),
            array('cn')
        );

        $userlist = array();

        $entry = $ldaph->get_first_entry($result);

        while ($entry) {
            $attributes = $ldaph->get_attributes($entry);
            $userlist[] = $attributes['cn']['0'];
            $entry = $ldaph->next_entry($entry);
        }

        $users_container = OpenLDAP::get_users_container();

        foreach ($userlist as $user) {
            $userdn = 'cn=' . $user . ',' . $users_container; 
            $ldap_object['pwdPolicySubentry'] = $dn;
            $ldaph->modify($userdn, $ldap_object);
        }
    }
}
