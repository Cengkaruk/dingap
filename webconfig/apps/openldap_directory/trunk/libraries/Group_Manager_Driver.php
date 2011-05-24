<?php

/**
 * OpenLDAP group manager driver.
 *
 * @category   Apps
 * @package    OpenLDAP_Accounts
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2005-2011 ClearFoundation
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

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Engine as Engine;
use \clearos\apps\base\File as File;
use \clearos\apps\groups\Group as Group;
use \clearos\apps\openldap_directory\Group_Driver as Group_Driver;
use \clearos\apps\openldap_directory\OpenLDAP as OpenLDAP;
use \clearos\apps\openldap_directory\Utilities as Utilities;

clearos_load_library('base/Engine');
clearos_load_library('base/File');
clearos_load_library('groups/Group');
clearos_load_library('openldap_directory/Group_Driver');
clearos_load_library('openldap_directory/OpenLDAP');
clearos_load_library('openldap_directory/Utilities');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * OpenLDAP group manager driver.
 *
 * @category   Apps
 * @package    OpenLDAP_Directory
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2005-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/openldap_directory/
 */

class Group_Manager_Driver extends Engine
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
     * Group_Manager_Driver constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        include clearos_app_base('openldap_directory') . '/deploy/group_map.php';

        $this->info_map = $info_map;
    }

    /**
     * Deletes the given username from all groups.
     *
     * @param string $username username
     *
     * @return void
     * @throws Engine_Exception
     */

    public function delete_group_memberships($username)
    {
        clearos_profile(__METHOD__, __LINE__);

        $group_list = $this->get_group_memberships($username);

        foreach ($group_list as $group_name) {
            $group = new Group_Driver($group_name);
            $group->delete_member($username);
        }
    }

    /**
     * Return a list of groups.
     *
     * @param string $type group type
     *
     * @return array a list of groups
     * @throws Engine_Exception
     */

    public function get_list($type = Group::TYPE_NORMAL)
    {
        clearos_profile(__METHOD__, __LINE__);

        $details = $this->_get_details($type);

        $group_list = array();

        foreach ($details as $name => $info)
            $group_list[] = $info['group_name'];

        return $group_list;
    }

    /**
     * Return a list of groups with detailed information.
     *
     * @param integer $filter filter for specific groups
     *
     * @return array an array containing group data
     * @throws Engine_Exception
     */

    public function get_details($type = Group::TYPE_NORMAL)
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->_get_details($type);
    }

    /**
     * Returns the list of groups for given username.
     *
     * @param string  $username username
     * @param integer $filter   filter for specific groups
     *
     * @return array a list of groups
     * @throws Engine_Exception
     */

/*
    public function get_group_memberships($username, $type = Group::TYPE_NORMAL)
    {
        clearos_profile(__METHOD__, __LINE__);

        $groups_info = $this->_get_details($type);

        $group_list = array();

        foreach ($groups_info as $group_name => $group_details) {
            if (in_array($username, $group_details['members']))
                $group_list[] = $group_name;
        }

        return $group_list;
    }
*/

    /**
     * Initializes default group memberships.
     *
     * Both Linux and Windows (and perhaps other operating systems) require
     * a default group to be assigned.  This method handles this assignment
     * and also provides some extra group handling for built-in groups:
     *
     *  - allusers (a group that tracks all users)
     *  - domain_users (a group that tracks all Windows domain users)
     *
     * @param string $username username
     *
     * @return void
     */

    public function initalize_group_memberships($username)
    {
        clearos_profile(__METHOD__, __LINE__);

        // FIXME: add Samba hook
        // See if Samba is up and running
        /*
        $group = new Group_Driver(Group_Driver::CONSTANT_ALL_WINDOWS_USERS_GROUP); 
        $samba_active = $group->exists();
        */

        // Add to "allusers" group
        $group = new Group_Driver(Group_Driver::ALL_USERS_GROUP);
        $group->add_member($username);

        // Add domain_users group if Samba is up and running
        /*
        if ($samba_active) {
            $group = new Group_Driver(Group_Driver::CONSTANT_ALL_WINDOWS_USERS_GROUP);
            $group->add_member($username);
        }
        */
    }

    /**
     * Updates group membership for given user.
     *
     * This method does not change the settings in built-in groups.  If this
     * functionality is required, we can add a flag to this method.
     *
     * @param string $username username
     * @param array  $groups   list of active groups
     *
     * @return void
     */

    public function update_group_memberships($username, $groups)
    {
        clearos_profile(__METHOD__, __LINE__);

        $current = $this->get_group_memberships($username);
        $all = $this->get_list(Group_Driver::FILTER_NORMAL);

        foreach ($all as $group_name) {
            if (in_array($group_name, $groups) && !in_array($group_name, $current)) {
                $group = new Group_Driver($group_name);
                $group->add_member($username);
            } else if (!in_array($group_name, $groups) && in_array($group_name, $current)) {
                $group = new Group_Driver($group_name);
                $group->delete_member($username);
            }
        }
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Loads a full list of groups with detailed information.
     *
     * @param string $type group type
     *
     * @return array an array containing group data
     * @throws Engine_Exception
     */

    protected function _get_details($type)
    {
        clearos_profile(__METHOD__, __LINE__);

        $ldap_data = array();
        $posix_data = array();

        $ldap_data = $this->_get_details_from_ldap($type);

        if (($type === Group::TYPE_SYSTEM) || ($type === Group::TYPE_ALL))
            $posix_data = $this->_get_details_from_posix($type);

        $data = array_merge($ldap_data, $posix_data);

        return $data;
    }

    /**
     * Loads groups from LDAP.
     *
     * @param string $type group type
     *
     * @access private
     * @throws Engine_Exception
     * @return array group information
     */

    protected function _get_details_from_ldap($type)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->ldaph === NULL)
            $this->ldaph = Utilities::get_ldap_handle();

        $group_list = array();

        // Load groups from LDAP
        //----------------------

        $result = $this->ldaph->search(
            "(&(objectclass=posixGroup))", 
            OpenLDAP::get_groups_container()
        );

        $this->ldaph->sort($result, 'cn');
        $entry = $this->ldaph->get_first_entry($result);

        while ($entry) {
            $attributes = $this->ldaph->get_attributes($entry);
            $gid = $attributes['gidNumber'][0];

            $process = FALSE;

            if (($type === Group::TYPE_NORMAL)
                && ($gid >= Group_Driver::GID_RANGE_NORMAL_MIN)
                && ($gid <= Group_Driver::GID_RANGE_NORMAL_MAX)
            ) {
                $process = TRUE;
            } else if (($type === Group::TYPE_BUILTIN)
                && ($gid >= Group_Driver::GID_RANGE_BUILTIN_MIN)
                && ($gid <= Group_Driver::GID_RANGE_BUILTIN_MAX)
            ) {
                $process = TRUE;
            } else if (($type === Group::TYPE_PLUGIN)
                && ($gid >= Group_Driver::GID_RANGE_PLUGIN_MIN)
                && ($gid <= Group_Driver::GID_RANGE_PLUGIN_MAX)
            ) {
                $process = TRUE;
            } else if (($type === Group::TYPE_SYSTEM)
                && ($gid >= Group_Driver::GID_RANGE_SYSTEM_MIN)
                && ($gid <= Group_Driver::GID_RANGE_SYSTEM_MAX)
            ) {
                $process = TRUE;
            } else if (($type === Group::TYPE_WINDOWS)
                && ($gid >= Group_Driver::GID_RANGE_WINDOWS_MIN)
                && ($gid <= Group_Driver::GID_RANGE_WINDOWS_MAX)
            ) {
                $process = TRUE;
            } else if ($type === Group::TYPE_ALL) {
                $process = TRUE;
            }

            if ($process) {

                // Convert LDAP attributes to PHP array
                //-------------------------------------

                $group_info = array();

                $group_info = Utilities::convert_attributes_to_array($attributes, $this->info_map);

                if (($gid >= Group_Driver::GID_RANGE_NORMAL_MIN) && ($gid <= Group_Driver::GID_RANGE_NORMAL_MAX))
                    $group_info['type'] = Group::TYPE_NORMAL;
                else if (($gid >= Group_Driver::GID_RANGE_BUILTIN_MIN) && ($gid <= Group_Driver::GID_RANGE_BUILTIN_MAX))
                    $group_info['type'] = Group::TYPE_BUILTIN;
                else if (($gid >= Group_Driver::GID_RANGE_PLUGIN_MIN) && ($gid <= Group_Driver::GID_RANGE_PLUGIN_MAX))
                    $group_info['type'] = Group::TYPE_PLUGIN;
                else if (($gid >= Group_Driver::GID_RANGE_SYSTEM_MIN) && ($gid <= Group_Driver::GID_RANGE_SYSTEM_MAX))
                    $group_info['type'] = Group::TYPE_SYSTEM;
                else if (($gid >= Group_Driver::GID_RANGE_WINDOWS_MIN) && ($gid <= Group_Driver::GID_RANGE_WINDOWS_MAX))
                    $group_info['type'] = Group::TYPE_WINDOWS;
                else
                    $group_info['type'] = Group::TYPE_UNKNOWN;

                // Handle membership
                //------------------
                // Convert RFC2307BIS CN member list to username member list

                $raw_members = $attributes['member'];
                array_shift($raw_members);

                $usermap_dn = Utilities::get_usermap_by_dn();

                foreach ($raw_members as $membercn) {
                    if (!empty($usermap_dn[$membercn]))
                        $group_info['members'][] = $usermap_dn[$membercn];
                }

                // Add group to list
                //------------------

                $group_list[$group_info['group_name']] = $group_info;
            }

            $entry = $this->ldaph->next_entry($entry);
        }

        return $group_list;
    }

    /**
     * Loads groups from Posix.
     *
     * @access private
     * @return array group information
     * @throws Engine_Exception
     */

    protected function _get_details_from_posix()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(Group_Driver::FILE_CONFIG);
        $contents = $file->get_contents_as_array();

        $group_data = array();

        foreach ($contents as $line) {
            $data = explode(":", $line);

            $gid = $data[2];

            if (($gid >= Group_Driver::GID_RANGE_SYSTEM_MIN) && ($gid <= Group_Driver::GID_RANGE_SYSTEM_MAX)) {
                $assoc_data['group_name'] = $data[0];
                $assoc_data['type'] = Group::TYPE_SYSTEM;
                $assoc_data['description'] = '';
                $assoc_data['members'] = explode(',', $data[3]);
                $group_data[$data[0]] = $assoc_data;
            }
        }

        ksort($group_data);

        return $group_data;
    }
}
