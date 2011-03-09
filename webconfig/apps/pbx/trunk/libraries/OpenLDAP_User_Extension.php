<?php

/**
 * PBX OpenLDAP user extension.
 *
 * @category   Apps
 * @package    PBX
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/pbx/
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

namespace clearos\apps\pbx;

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
 * PBX OpenLDAP user extension.
 *
 * @category   Apps
 * @package    PBX
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/pbx/
 */

class OpenLDAP_User_Extension extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const DEFAULT_STATE = '0';
    const DEFAULT_PRESENCE_STATE = '0';

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $info_map = array();

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * PBX OpenLDAP_Extension constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->info_map = array(
            'extension' => array(
                'type' => 'string',
                'required' => FALSE,
                'validator' => 'validate_FIXME',
                'object_class' => 'clearPbxAccount',
                'attribute' => 'clearPbxExtension'
            ),
            'password' => array(
                'type' => 'string',
                'required' => FALSE,
                'validator' => 'validate_FIXME',
                'object_class' => 'clearPbxAccount',
                'attribute' => 'clearPbxPassword'
            ),
            'presence_state' => array(
                'type' => 'string',
                'required' => FALSE,
                'validator' => 'validate_FIXME',
                'object_class' => 'clearPbxAccount',
                'attribute' => 'clearPbxPresenceState'
            ),
            'state' => array(
                'type' => 'string',
                'required' => FALSE,
                'validator' => 'validate_FIXME',
                'object_class' => 'clearPbxAccount',
                'attribute' => 'clearPbxState'
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

        if (! isset($user_info['state']))
            $user_info['pbx']['state'] = self::DEFAULT_STATE;

        if (! isset($user_info['presence_state']))
            $user_info['pbx']['presence_state'] = self::DEFAULT_PRESENCE_STATE;

        // Convert to LDAP attributes
        //---------------------------

        $attributes = Utilities::convert_array_to_attributes($user_info['pbx'], $this->info_map);

        return $attributes;
    }

    /**
     * Runs after adding a user.
     *
     * @return void
     */

    public function add_post_processing_hook()
    {
        clearos_profile(__METHOD__, __LINE__);
/*
FIXME
- should be in synchronizer hook

            if (isset($ldap_object['pcnPbxState']) && file_exists(CLEAROS_CORE_DIR . "/iplex/Users.class.php")) {
                require_once(CLEAROS_CORE_DIR . "/iplex/Users.class.php");

                $iplex_user = new IPlexUser();
                // if user data already exists in PBX module, delete it and readd
                if ($iplex_user->Exists())
                    $iplex_user->DeleteIPlexPBXUser($this->username);

                if ($iplex_user->CCAddUser($user_info, $this->username) === 0) {
                    // CCAddUser failed to add PBX user, clear pbx settings so they aren't saved
                    unset($ldap_object['pcnPbxExtension']);
                    $ldap_object['pcnPbxState'] = 0;
                    $ldap_object['pcnPbxPresenceState'] = 0;
                }
            }
*/
    }

    /**
     * Runs delete procedure.
     */

    public function delete_hook()
    {
        clearos_profile(__METHOD__, __LINE__);

        // Delete the IPlex PBX user
        /* FIXME: move to extension
        if (file_exists(CLEAROS_CORE_DIR . "/iplex/Users.class.php")) {
            require_once(CLEAROS_CORE_DIR . "/iplex/Users.class.php");
            $iplex_user = new IPlexUser();
            if($iplex_user->Exists($this->username))
                $iplex_user->DeleteIPlexPBXUser($this->username);
        }
        */
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

        $attributes = array();

/*
            require_once(CLEAROS_CORE_DIR . "/iplex/Users.class.php");

            try {
                $iplex_user = new IPlexUser();

                if (array_key_exists('pbxFlag', $user_info)) {
                    // Delete PBX user
                    if (($user_info['pbxFlag'] != 1) && $iplex_user->Exists($this->username)) {
                        $iplex_user->DeleteIPlexPBXUser($this->username);
                    // Update PBX user
                    } else if (($user_info['pbxFlag'] == 1) && $iplex_user->Exists($this->username)) {
                        $iplex_user->UpdateIPlexPBXUser($user_info, $this->username);
                    // Add PBX user
                    } else if ($user_info['pbxFlag'] == 1) {
                        if ($iplex_user->CCAddUser($user_info, $this->username) == 0) {
                            // CCAddUser failed to add PBX user, clear pbx settings so they aren't saved
                            unset($ldap_object['pcnPbxExtension']);
                            unset($ldap_object['pcnPbxState']);
                            unset($ldap_object['pcnPbxPresenceState']);
                        }
                    }
                }
            } catch (Exception $e) {
                throw new Engine_Exception(clearos_exception_message($e), CLEAROS_WARNING);
            }
*/

        
        return $attributes;
    }
}
