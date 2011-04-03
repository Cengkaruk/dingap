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
use \clearos\apps\openldap\Utilities as Utilities;
use \clearos\apps\users\User as User;

clearos_load_library('base/Engine');
clearos_load_library('base/Shell');
clearos_load_library('openldap/Directory_Driver');
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
     * @param string $type service type
     * @param bool $showhidden include hidden accounts
     *
     * @return array user list
     * @throws Engine_Exception
     */

    public function get_users($type = NULL, $showhidden = FALSE)
    {
        clearos_profile(__METHOD__, __LINE__);

        $rawlist = $this->_ldap_get_user_list($showhidden, $type);
        $userlist = array();

        foreach ($rawlist as $username => $userinfo)
            $userlist[] = $username;

        return $userlist;
    }
    
    /**
     * Returns detailed user information for all users.
     *
     * @param bool $showhidden include hidden accounts
     *
     * @return array user information array
     * @throws Engine_Exception
     */

    public function get_users_info($showhidden = FALSE)
    {
        clearos_profile(__METHOD__, __LINE__);

        $rawlist = $this->_ldap_get_user_list($showhidden);
        $userlist = array();

        foreach ($rawlist as $index => $userinfo)
            $userlist[$index] = $userinfo;

        return $userlist;
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
     * The type (e.g. ClearDirectory::SERVICE_TYPE_OPENVPN) can be used
     * to filter results.
     *
     * @param bool $showhidden include hidden accounts
     * @param string $type ClearDirectory user service type
     * @access private
     *
     * @return array user information
     */

    protected function _ldap_get_user_list($showhidden = FALSE, $type = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->ldaph === NULL)
            $this->ldaph = Utilities::get_ldap_handle();

        // FIXME: implement "type" flag
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

            if ($showhidden ||
                 (!(($uid >= User::UID_RANGE_BUILTIN_MIN) && ($uid <= User::UID_RANGE_BUILTIN_MAX)))) {
                $user = new User("not used");
                $userinfo = Utilities::convert_attributes_to_array($attributes, $this->info_map);

                $userlist[$username] = $userinfo;
            }

            $entry = $this->ldaph->next_entry($entry);
        }

        return $userlist;
    }
}
