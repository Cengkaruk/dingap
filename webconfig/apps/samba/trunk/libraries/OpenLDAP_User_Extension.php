<?php

/**
 * Samba OpenLDAP user extension.
 *
 * @category   Apps
 * @package    Samba
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/samba/
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

namespace clearos\apps\samba;

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
 * Samba OpenLDAP user extension.
 *
 * @category   Apps
 * @package    Samba
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/samba/
 */

class OpenLDAP_User_Extension extends Engine
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
     * Samba OpenLDAP_Extension constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->info_map = array(
            'account_flags' => array(
                'type' => 'string',
                'required' => FALSE,
                'validator' => 'validate_account_flags',
                'objectclass' => 'sambaSamAccount',
                'attribute' => 'sambaAcctFlags'
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

        // Convert to LDAP attributes
        //---------------------------
        $attributes = Utilities::convert_array_to_attributes($user_info, $this->info_map);
        /*
        FIXME
            // The 'D' flag indicates a disabled account
            if (isset($attributes['sambaAcctFlags']) && !preg_match('/D/', $attributes['sambaAcctFlags'][0]))
                $userinfo['sambaFlag'] = TRUE;
            else
                $userinfo['sambaFlag'] = FALSE;

            // The 'L' flag indicates a locaked account
            if (isset($attributes['sambaAcctFlags']) && preg_match('/L/', $attributes['sambaAcctFlags'][0]))
                $userinfo['sambaAccountLocked'] = TRUE;
            else
                $userinfo['sambaAccountLocked'] = FALSE;
        */

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

        /*
        FIXME
            // The 'D' flag indicates a disabled account
            if (isset($attributes['sambaAcctFlags']) && !preg_match('/D/', $attributes['sambaAcctFlags'][0]))
                $userinfo['sambaFlag'] = TRUE;
            else
                $userinfo['sambaFlag'] = FALSE;

            // The 'L' flag indicates a locaked account
            if (isset($attributes['sambaAcctFlags']) && preg_match('/L/', $attributes['sambaAcctFlags'][0]))
                $userinfo['sambaAccountLocked'] = TRUE;
            else
                $userinfo['sambaAccountLocked'] = FALSE;
        */
        $info['disabled'] = FALSE;
        $info['locked'] = TRUE;

        return $info;
    }
}
