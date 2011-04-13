<?php

/**
 * OpenLDAP user manager driver.
 *
 * @category   Apps
 * @package    OpenLDAP
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/openldap/
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

namespace clearos\apps\openldap;

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
use \clearos\apps\openldap\Directory_Driver as Directory_Driver;
use \clearos\apps\openldap\User_Driver as User_Driver;
use \clearos\apps\openldap\Utilities as Utilities;
use \clearos\apps\users\user as User;

clearos_load_library('base/Engine');
clearos_load_library('base/Shell');
clearos_load_library('openldap/Directory_Driver');
clearos_load_library('openldap/User_Driver');
clearos_load_library('openldap/Utilities');
clearos_load_library('users/User');

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;

clearos_load_library('base/Engine_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * OpenLDAP user manager driver.
 *
 * @category   Apps
 * @package    OpenLDAP
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/openldap/
 */

class User_Manager_Driver extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const COMMAND_SYNCUSERS = "/usr/sbin/syncusers";

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

        // Load attribute mapping
        include clearos_app_base('openldap') . '/config/user_map.php';
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

    public function get_list($app = NULL, $type = User::TYPE_NORMAL)
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

    public function get_details($app = NULL, $type = User::TYPE_NORMAL)
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->_get_details($app, $type);
    }

    /**
     * Synchronizes user database.
     *
     * @return void
     * @throws Engine_Exception
     */

    public function synchronize()
    {
        clearos_profile(__METHOD__, __LINE__);

        $options['background'] = TRUE;

        $shell = new Shell();
        $shell->execute(self::COMMAND_SYNCUSERS, '', TRUE, $options);
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

        $directory = new Directory_Driver();
        $users_ou = $directory->get_users_ou();

        $result = $this->ldaph->search(
            "(&(cn=*)(objectclass=posixAccount)$search)",
            $users_ou
        );

        $this->ldaph->sort($result, 'uid');
        $entry = $this->ldaph->get_first_entry($result);

        while ($entry) {
            $attributes = $this->ldaph->get_attributes($entry);
            $dn = $this->ldaph->get_dn($entry);
            $uid = $attributes['uidNumber'][0];
            $username = $attributes['uid'][0];

            $process = FALSE;

            if (($type === User::TYPE_NORMAL) 
                && ($uid >= User_Driver::UID_RANGE_NORMAL_MIN)
                && ($uid <= User_Driver::UID_RANGE_NORMAL_MAX)
            ) {
                $process = TRUE;
            } else if (($type === User::TYPE_BUILTIN) 
                && ($uid >= User_Driver::UID_RANGE_BUILTIN_MIN)
                && ($uid <= User_Driver::UID_RANGE_BUILTIN_MAX)
            ) {
                $process = TRUE;
            } else if (($type === User::TYPE_SYSTEM) 
                && ($uid >= User_Driver::UID_RANGE_SYSTEM_MIN)
                && ($uid <= User_Driver::UID_RANGE_SYSTEM_MAX)
            ) {
                $process = TRUE;
            } else if ($type === User::TYPE_ALL) {
                $process = TRUE;
            }

            if ($process) {
                $userinfo = Utilities::convert_attributes_to_array($attributes, $this->info_map);
                $userlist[$username] = $userinfo;
            }

            $entry = $this->ldaph->next_entry($entry);
        }

        return $userlist;
    }
}
