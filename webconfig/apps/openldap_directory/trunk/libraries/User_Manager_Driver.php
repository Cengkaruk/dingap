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
use \clearos\apps\openldap_directory\User_Driver as User_Driver;
use \clearos\apps\openldap_directory\Utilities as Utilities;
use \clearos\apps\users\User_Engine as User_Engine;
use \clearos\apps\users\User_Manager_Engine as User_Manager_Engine;

clearos_load_library('base/Engine');
clearos_load_library('base/Shell');
clearos_load_library('openldap_directory/OpenLDAP');
clearos_load_library('openldap_directory/Group_Driver');
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
     * @param string $app  app extension or plugin
     * @param string $type user type
     *
     * @return array user list
     * @throws Engine_Exception
     */

    public function get_list($app = NULL, $type = User_Engine::TYPE_NORMAL)
    {
        clearos_profile(__METHOD__, __LINE__);

        $raw_list = $this->_get_details($app, $type);

        $user_list = array();

        foreach ($raw_list as $username => $userinfo)
            $user_list[] = $username;

        return $user_list;
    }
    
    /**
     * Returns detailed user information for all users.
     *
     * @param string $app  app extension or plugin
     * @param string $type user type
     *
     * @return array user information array
     * @throws Engine_Exception
     */

    public function get_details($app = NULL, $type = User_Engine::TYPE_NORMAL)
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->_get_details($app, $type);
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Returns user information.
     *
     * @param string $app  app extension or plugin
     * @param string $type user type
     *
     * @access private
     * @return array user information
     */

    protected function _get_details($app, $type)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->ldaph === NULL)
            $this->ldaph = Utilities::get_ldap_handle();

        // FIXME: implement "app" flag
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

            // FIXME - implement type

            $userinfo = Utilities::convert_attributes_to_array($attributes, $this->info_map);

            // FIXME: review this for Active Directory
            if (! isset($userinfo['full_name']))
                $userinfo['full_name'] = $userinfo['first_name'] . ' ' . $userinfo['last_name'];

            if ($username !== Group_Driver::CONSTANT_NO_MEMBERS_USERNAME)
                $userlist[$username] = $userinfo;

            $entry = $this->ldaph->next_entry($entry);
        }

        return $userlist;
    }
}
