<?php

/**
 * Password policies class.
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

clearos_load_language('base');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

use \clearos\apps\base\Engine as Engine;
use \clearos\apps\password_policies\OpenLDAP_Policy as OpenLDAP_Policy;
use \clearos\apps\samba\Samba_Password_Policy as Samba_Password_Policy;

clearos_load_library('base/Engine');
clearos_load_library('password_policies/OpenLDAP_Policy');
// Loaded dynamically: clearos_load_library('samba/Samba_Password_Policy');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Password policies class.
 *
 * @category   Apps
 * @package    Password_Policies
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2010-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/password_policies/
 */

class Password_Policies extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    const CONSTANT_LOCKOUT_FOREVER = 0;
    const CONSTANT_MODIFY_ANY_TIME = 0;
    const CONSTANT_NO_HISTORY = 0;
    const CONSTANT_NO_EXPIRE = 0;

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Password policy engine constructor.
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

        $policy = new OpenLDAP_Policy();
        $info = $policy->get_default_policy();

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

        $policy = new OpenLDAP_Policy();
        $policy->initialize();
    }

    /**
     * Sets default policy.
     *
     * The settings object is defined as follows:
     * - history_size (integer): the number of passwords to store in history
     * - maximum_age (seconds): maximum password age
     * - minimum_age (seconds): minimum password age
     * - minimum_length (integer): minimum length of a password
     *
     * @param array $settings settings object
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_default_policy($settings)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Update global policy (LDAP) 
        $policy = new OpenLDAP_Policy();
        $policy->set_default_policy($settings);

        // Update Samba policy
        if (clearos_library_installed('samba/Samba_Password_Policy')) {
            clearos_load_library('samba/Samba_Password_Policy');
            $samba_policy = new Samba_Password_Policy();
            $samba_settings = $this->_convert_policy_to_samba($settings);
            $samba_policy->set_default_policy($samba_settings);
        }
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

        // Apply global policy (LDAP) 
        $policy = new OpenLDAP_Policy();
        $policy->apply_default_policy();

        // Samba policy is automatically enforced
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E  M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Converts the generic password policies to Samba password policies
     *
     * @param array $settings policy settings
     *
     * @return array password policies
     * @throws Engine_Exception
     */

    private function _convert_policy_to_samba($settings)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($settings['history_size'] == self::CONSTANT_NO_HISTORY)
            $samba_settings['sambaPwdHistoryLength'] = Samba_Password_Policy::CONSTANT_NO_HISTORY;
        else    
            $samba_settings['sambaPwdHistoryLength'] = $settings['history_size'];

        if ($settings['maximum_age'] == self::CONSTANT_NO_EXPIRE)
            $samba_settings['sambaMaxPwdAge'] = Samba_Password_Policy::CONSTANT_NO_EXPIRE;
        else
            $samba_settings['sambaMaxPwdAge'] = $settings['maximum_age'];

        if ($settings['minimum_age'] == self::CONSTANT_MODIFY_ANY_TIME)
            $samba_settings['sambaMinPwdAge'] = Samba_Password_Policy::CONSTANT_MODIFY_ANY_TIME;
        else
            $samba_settings['sambaMinPwdAge'] = $settings['minimum_age'];

        $samba_settings['sambaMinPwdLength'] = $settings['minimum_length'];

        return $samba_settings;
    }
}
