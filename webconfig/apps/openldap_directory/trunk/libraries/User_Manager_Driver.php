<?php

/**
 * OpenLDAP user manager driver.
 *
 * @category   Apps
 * @package    OpenLDAP_Accounts
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/openldap_directory/
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

namespace clearos\apps\openldap_directory;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('base');
clearos_load_language('users');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Engine as Engine;
use \clearos\apps\base\Shell as Shell;
use \clearos\apps\openldap_directory\OpenLDAP as OpenLDAP;
use \clearos\apps\openldap_directory\Group_Driver as Group_Driver;
use \clearos\apps\openldap_directory\Group_Manager_Driver as Group_Manager_Driver;
use \clearos\apps\openldap_directory\User_Driver as User_Driver;
use \clearos\apps\openldap_directory\Utilities as Utilities;
use \clearos\apps\users\User_Engine as User_Engine;
use \clearos\apps\users\User_Manager_Engine as User_Manager_Engine;

clearos_load_library('base/Engine');
clearos_load_library('base/Shell');
clearos_load_library('openldap_directory/OpenLDAP');
clearos_load_library('openldap_directory/Group_Driver');
clearos_load_library('openldap_directory/Group_Manager_Driver');
clearos_load_library('openldap_directory/User_Driver');
clearos_load_library('openldap_directory/Utilities');
clearos_load_library('users/User_Engine');
clearos_load_library('users/User_Manager_Engine');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * OpenLDAP user manager driver.
 *
 * @category   Apps
 * @package    OpenLDAP_Accounts
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/openldap_directory/
 */

class User_Manager_Driver extends User_Manager_Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $ldaph = NULL;
    protected $info_map = array();

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * User manager constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        include clearos_app_base('openldap_directory') . '/deploy/user_map.php';

        $this->info_map = $info_map;
    }

    /**
     * Returns the user list.
     *
     * @param string $type user type
     *
     * @return array user list
     * @throws Engine_Exception
     */

    public function get_list($type = User_Engine::TYPE_NORMAL)
    {
        clearos_profile(__METHOD__, __LINE__);

        $raw_list = $this->_get_details($type, TRUE);

        $user_list = array();

        foreach ($raw_list as $username => $userinfo)
            $user_list[] = $username;

        return $user_list;
    }
    
    /**
     * Returns core detailed user information for all users.
     *
     * The details only include core user information, i.e.
     * no extension or group information.
     *
     * @param string $type user type
     *
     * @return array user information array
     * @throws Engine_Exception
     */

    public function get_core_details($type = User_Engine::TYPE_NORMAL)
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->_get_details($type, TRUE);
    }

    /**
     * Returns detailed user information for all users.
     *
     * @param string $type user type
     *
     * @return array user information array
     * @throws Engine_Exception
     */

    public function get_details($type = User_Engine::TYPE_NORMAL)
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->_get_details($type, FALSE);
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Returns user information.
     *
     * The core_only flag is nice to have to optimize the method calls.  Pulling
     * in all the extension and group information can be expensive.
     *
     * @param string  $type      user type
     * @param boolean $core_only core details only
     *
     * @access private
     * @return array user information
     */

    protected function _get_details($type, $core_only)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Prep group membership lookup table
        //-----------------------------------

        if (! $core_only) {
            $group_manager = new Group_Manager_Driver();
            $group_data = $group_manager->get_details();
            $group_lookup = array();

            foreach ($group_data as $group => $details) {
                foreach ($details['members'] as $username) {
                    if (array_key_exists($username, $group_lookup))
                        $group_lookup[$username][] = $group;
                    else
                        $group_lookup[$username] = array($group);
                }
            }
        }
        
        // Grab user info from LDAP
        //-------------------------

        if ($this->ldaph === NULL)
            $this->ldaph = Utilities::get_ldap_handle();

        $search = '';

        $userlist = array();

        $result = $this->ldaph->search(
            "(&(cn=*)(objectclass=posixAccount)$search)",
            OpenLDAP::get_users_container()
        );

        $this->ldaph->sort($result, 'uid');
        $entry = $this->ldaph->get_first_entry($result);

        while ($entry) {
            $attributes = $this->ldaph->get_attributes($entry);
            $username = $attributes['uid'][0];

            // Bail if this is the "no members" user
            //--------------------------------------

            if ($username === Group_Driver::CONSTANT_NO_MEMBERS_USERNAME) {
                $entry = $this->ldaph->next_entry($entry);
                continue;
            }

            // TODO: continue filter implementation
            if ($type === User_Engine::TYPE_NORMAL) {
                if (in_array($username, User_Engine::$builtin_list)) {
                    $entry = $this->ldaph->next_entry($entry);
                    continue;
                }
            }

            // Get user info
            //--------------

            $userinfo['core'] = Utilities::convert_attributes_to_array($attributes, $this->info_map);

            if (! $core_only) {
                // Add group memberships
                //----------------------

                if (array_key_exists($username, $group_lookup))
                    $userinfo['groups'] = $group_lookup[$username];
                else
                    $userinfo['groups'] = array();

                // Add user info from extensions
                //------------------------------

                $accounts = new Accounts_Driver();
                $extensions = $accounts->get_extensions();

                foreach ($extensions as $extension_name => $details) {
                    $extension = Utilities::load_user_extension($details);

                    if (method_exists($extension, 'get_info_hook')) {
                        $userinfo['extensions'][$extension_name] = $extension->get_info_hook($attributes);
                    }
                }

                // Add user info map from plugins
                //-------------------------------

                $plugins = $accounts->get_plugins();

                foreach ($plugins as $plugin => $details) {
                    $plugin_name = $plugin . '_plugin';
                    $state = (in_array($plugin_name, $userinfo['groups'])) ? TRUE : FALSE;
                    $userinfo['plugins'][$plugin] = $state;
                }
            }

            // FIXME: review this for Active Directory
            if (! isset($userinfo['core']['full_name']))
                $userinfo['core']['full_name'] = $userinfo['core']['first_name'] . ' ' . $userinfo['core']['last_name'];

            $userlist[$username] = $userinfo;

            $entry = $this->ldaph->next_entry($entry);
        }

        return $userlist;
    }
}
