<?php

/**
 * OpenLDAP group driver.
 *
 * @category   Apps
 * @package    OpenLDAP_Directory
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
use \clearos\apps\openldap\OpenLDAP as OpenLDAP;
use \clearos\apps\openldap_directory\Directory_Driver as Directory_Driver;
use \clearos\apps\openldap_directory\Utilities as Utilities;
use \clearos\apps\samba\Samba as Samba;
use \clearos\apps\users\User_Manager as User_Manager;

clearos_load_library('base/Engine');
clearos_load_library('base/File');
clearos_load_library('groups/Group');
clearos_load_library('openldap/OpenLDAP');
clearos_load_library('openldap_directory/Directory_Driver');
clearos_load_library('openldap_directory/Utilities');
clearos_load_library('samba/Samba');
clearos_load_library('users/User_Manager');

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\File_No_Match_Exception as File_No_Match_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;
use \clearos\apps\groups\Group_Not_Found_Exception as Group_Not_Found_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('base/File_No_Match_Exception');
clearos_load_library('base/Validation_Exception');
clearos_load_library('groups/Group_Not_Found_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * OpenLDAP group driver.
 *
 * Provides tools for managing user defined groups on the system.  For now,
 * Only the group->exists() method uses both LDAP and Posix groups.  All
 * other public methods refer to LDAP groups only.
 *
 * The RFC2307BIS implementation is used in the underlying implementation of
 * groups.  As with other parts of the API, we want to hide these
 * implementation issues.  In particular, the members of a group will use
 * the more common "list of username" instead of using a "list of full names".
 *
 * With the NIS schema, a group uses the following structure:
 *
 * dn: cn=mygroup,ou=Groups,ou=Accounts,dc=example,dc=org
 * memberUid: bob
 * memberUid: doug
 *
 * With the RFC2307BIS scheam, a group looks like:
 *
 * dn: cn=mygroup,ou=Groups,ou=Accounts,dc=example,dc=org
 * member: cn=Bob McKenzie,ou=Users,ou=Accounts,dc=example,dc=org
 * member: cn=Doug McKenzie,ou=Users,ou=Accounts,dc=example,dc=org
 *
 * @category   Apps
 * @package    OpenLDAP_Directory
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2005-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/openldap_directory/
 */

class Group_Driver extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $ldaph = NULL;
    protected $group_name = NULL;
    protected $info_map = array();
    protected $usermap_dn = NULL;
    protected $usermap_username = NULL;
    public $hidden_list = array();
    public $builtin_list = array();
    public $windows_list = array();

    const LOG_TAG = 'group';
    const FILE_CONFIG = '/etc/group';

    const CONSTANT_NO_MEMBERS_USERNAME = 'nomembers';
    const CONSTANT_NO_MEMBERS_DN = 'No Members';
    const CONSTANT_ALL_WINDOWS_USERS_GROUP = 'domain_users';

    // Group policy
    //-------------

    const ALL_USERS_GROUP = 'allusers';
    const ALL_USERS_GROUP_ID = '63000';

    // Group ID ranges
    //----------------

    const GID_RANGE_SYSTEM_MIN = '0';
    const GID_RANGE_SYSTEM_MAX = '499';
    const GID_RANGE_NORMAL_MIN = '60000';
    const GID_RANGE_NORMAL_MAX = '62999';
    const GID_RANGE_BUILTIN_MIN = '63000';
    const GID_RANGE_BUILTIN_MAX = '63999';
    const GID_RANGE_PLUGIN_MIN = '900000';
    const GID_RANGE_PLUGIN_MAX = '999999';
    const GID_RANGE_WINDOWS_MIN = '1000000';
    const GID_RANGE_WINDOWS_MAX = '1001000';

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Group constructor.
     *
     * @param string $group_name group name.
     *
     * @return void
     */

    public function __construct($group_name)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->group_name = $group_name;

        $this->builtin_list = array(
            'allusers',
            'domain_admins',
            'domain_users'
        );

        $this->hidden_list = array(
            'account_operators',
            'administrators',
            'backup_operators',
            'domain_computers',
            'domain_controllers',
            'domain_guests',
            'guests',
            'power_users',
            'print_operators',
            'server_operators',
            'users'
        );

        $this->windows_list = array(
            'account_operators',
            'administrators',
            'backup_operators',
            'domain_admins',
            'domain_computers',
            'domain_controllers',
            'domain_guests',
            'domain_users',
            'guests',
            'power_users',
            'print_operators',
            'server_operators',
            'users'
        );

        include clearos_app_base('openldap_directory') . '/deploy/group_map.php';

        $this->info_map = $info_map;
    }

    /**
     * Adds a group to the system.
     *
     * @param string $description group description
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function add($description, $members = array())
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validate
        //---------

        Validation_Exception::is_valid($this->validate_group_name($this->group_name));

        if ($this->exists()) {
            $info = $this->_load_group_info();

            if ($info['type'] == Group::TYPE_WINDOWS)
                $warning = lang('groups_group_is_reserved_for_windows');
            else if ($info['type'] == Group::TYPE_BUILTIN)
                $warning = lang('groups_group_is_reserved_for_system');
            else if ($info['type'] == Group::TYPE_SYSTEM)
                $warning = lang('groups_group_is_reserved_for_system');
            else
                $warning = lang('groups_group_already_exists');

            throw new Validation_Exception($warning);
        }

        $directory = new Directory_Driver();
        $unique_warning = $directory->check_uniqueness($this->group_name);

        if ($unique_warning)
            throw new Validation_Exception($unique_warning);

        // FIXME - deal with flexshare conflicts somehow

        // Convert array into LDAP object
        //-------------------------------

        $info['gid_number'] = $this->_get_next_gid_number();
        $info['description'] = $description;
        $info['group_name'] = $this->group_name;

        $ldap_object = Utilities::convert_array_to_attributes($info, $this->info_map);

        // FIXME: add plugin for Samba and Google Apps (Mail)
        // FIXME: and update objectclass handling below
        $ldap_object['objectClass'] = array(
            'top',
            'posixGroup',
            'groupOfNames'
        );

        // Handle group members
        //---------------------

        $ldap_object['member'] = array();

        if (empty($members))
            $members = array(self::CONSTANT_NO_MEMBERS_DN);

        $users_container = $directory->get_users_container();    

        foreach ($members as $member)
            $ldap_object['member'][] = 'cn=' . $member . ',' . $users_container;

        // Add the group to LDAP
        //----------------------

        if ($this->ldaph === NULL)
            $this->ldaph = Utilities::get_ldap_handle();

        $dn = "cn=" . OpenLDAP::dn_escape($this->group_name) . "," . $directory->get_groups_container();

        $this->ldaph->add($dn, $ldap_object);
    }

    /**
     * Adds a member to a group.
     *
     * @param string $username username
     *
     * @return FALSE if user was already a member
     * @throws Validation_Exception, Engine_Exception
     */

    public function add_member($username)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_group_name($this->group_name));

        $members = $this->get_members();

        if (in_array($username, $members)) {
            return FALSE;
        } else {
            $members[] = $username;
            $this->set_members($members);    
            return TRUE;
        }
    }

    /**
     * Deletes a group from the system.
     *
     * @return void
     * @throws Group_Not_Found_Exception, Engine_Exception
     */

    public function delete()
    {
        clearos_profile(__METHOD__, __LINE__);

        // TODO -- it would be nice to check to see if group is still in use
        Validation_Exception::is_valid($this->validate_group_name($this->group_name));

        if (! $this->exists())
            throw new Group_Not_Found_Exception();

        if ($this->ldaph === NULL)
            $this->ldaph = Utilities::get_ldap_handle();

        $directory = new Directory_Driver();

        $dn = "cn=" . OpenLDAP::dn_escape($this->group_name) . "," . $directory->get_groups_container();

        $this->ldaph->delete($dn);
    }

    /**
     * Deletes a member from a group.
     *
     * @param string $username username
     *
     * @return FALSE if user was already not a member
     * @throws Validation_Exception, Engine_Exception
     */

    public function delete_member($username)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_group_name($this->group_name));

        $members = $this->get_members();

        if (in_array($username, $members)) {
            $newmembers = array();

            foreach ($members as $member) {
                if ($member != $username)
                    $newmembers[] = $member;
            }

            $this->set_members($newmembers);    
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * Checks the existence of the group.
     *
     *
     * @return boolean TRUE if group exists
     * @throws Engine_Exception
     */

    public function exists()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $this->_load_group_info();
        } catch (Group_Not_Found_Exception $e) {
            return FALSE;
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_ERROR);
        }

        return TRUE;
    }

    /**
     * Returns a list of group members.
     *
     * @return array list of group members
     * @throws Group_Not_Found_Exception, Engine_Exception
     */

    public function get_members()
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_group_name($this->group_name));

        $info = $this->_load_group_info();

        return $info['members'];
    }

    /**
     * Returns the group description.
     *
     * @return string group description
     * @throws Group_Not_Found_Exception, Engine_Exception
     */

    public function get_description()
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_group_name($this->group_name));

        $info = $this->_load_group_info();

        $description = (empty($info['description'])) ? '' : $info['description'];

        return $description;
    }

    /**
     * Returns the group ID number.
     *
     * @return integer group ID number
     * @throws Group_Not_Found_Exception, Engine_Exception
     */

    public function get_gid_number()
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_group_name($this->group_name));

        $info = $this->_load_group_info();

        return $info['gid_number'];
    }

    /**
     * Returns the group information.
     *
     * @return array group information
     * @throws Group_Not_Found_Exception, Engine_Exception
     */

    public function get_info()
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_group_name($this->group_name));

        $info = $this->_load_group_info();

        return $info;
    }

    /**
     * Sets the group description.
     *
     * @param string $description group description
     *
     * @return void
     * @throws Group_Not_Found_Exception, Engine_Exception, Validation_Exception
     */

    public function set_description($description)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validate
        //---------

        Validation_Exception::is_valid($this->validate_description($description));
        Validation_Exception::is_valid($this->validate_group_name($this->group_name));

        if (! $this->exists())
            throw new Group_Not_Found_Exception();

        // Set description
        //----------------

        $attributes['description'] = $description;

        if ($this->ldaph === NULL)
            $this->ldaph = Utilities::get_ldap_handle();

        $directory = new Directory_Driver();

        $dn = "cn=" . OpenLDAP::dn_escape($this->group_name) . "," . $directory->get_groups_container();

        $this->ldaph->modify($dn, $attributes);
    }

    /**
     * Sets the group member list.
     *
     * @param array $members array of group members
     *
     * @return void
     * @throws Group_Not_Found_Exception, Engine_Exception, Validation_Exception
     */

    public function set_members($members)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validate
        //---------

        Validation_Exception::is_valid($this->validate_group_name($this->group_name));

        if (! $this->exists())
            throw new Group_Not_Found_Exception();

        // Check for invalid users
        //------------------------

        $user_manager = User_Manager::create();
        $user_list = $user_manager->get_list();

        foreach ($members as $user) {
            if (! in_array($user, $user_list))
                throw new Engine_Exception(lang('groups_username_does_not_exist'));
        }

        if (count($members) == 0)
            $members = array(self::CONSTANT_NO_MEMBERS_USERNAME);

        // Set members list
        //-----------------

        if ($this->ldaph === NULL)
            $this->ldaph = Utilities::get_ldap_handle();

        $directory = new Directory_Driver();

        $dn = "cn=" . OpenLDAP::dn_escape($this->group_name) . "," . $directory->get_groups_container();

        if ($this->usermap_username === NULL)
            $this->_load_usermap_from_ldap();

        // FIXME: move to Samba extension
        // TODO: Last minute fix in 5.0.  Make sure winadmin stays in the winadmins group.
        // JHT is this necessary given the special SID for winadmin?
        if (($this->group_name == "domain_admins") && !in_array("winadmin", $members))
            $members[] = "winadmin";

        foreach ($members as $member) {
            if (! empty($this->usermap_username[$member]))
                $attributes['member'][] = $this->usermap_username[$member];
        }

        $this->ldaph->modify($dn, $attributes);
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validation routine for group description.
     *
     * @param string description
     *
     * @return string error message description is invalid
     */

    public function validate_description($description)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! preg_match('/^([\w \.\-]*)$/', $description))
            return lang('groups_description_is_invalid');
    }

    /**
     * Validation routine for group name.
     *
     * Groups must begin with a letter and allow underscores.
     *
     * @param string $group_name group name
     *
     * @return boolean error message if group name is invalid
     */

    public function validate_group_name($group_name)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! preg_match('/^([a-zA-Z]+[0-9a-zA-Z\.\-_\s]*)$/', $group_name))
            return lang('groups_group_name_is_invalid');
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Converts group array to LDAP attributes.
     *
     * The GroupManager class uses this method.  However, we do not want this
     * method to appear in the API documentation since it is really only for
     * internal use.
     *
     * @access private
     *
     * @return group information in an LDAP attributes format
     * @throws Engine_Exception
     */

    public function _convert_array_to_ldap_attributes($group_info)
    {
        clearos_profile(__METHOD__, __LINE__);

        $attributes = array();

        $attributes['objectClass'] = array(
            'top',
            'posixGroup',
            'groupOfNames'
        );

        $attributes['gidNumber'] = $group_info['gid'];
        $attributes['cn'] = $group_info['group'];
        $attributes['description'] = $group_info['description'];

        // Add Samba attributes if it is active
        // FIXME: move to extension
        if (file_exists(COMMON_CORE_DIR . "/api/Samba.class.php")) {
            require_once(COMMON_CORE_DIR . "/api/Samba.class.php");

            $samba = new Samba();
            if ($samba->IsDirectoryInitialized()) {
                $sid = $samba->GetDomainSid();
                $attributes['sambaSID'] = $sid . '-' . $group_info['gid'];
                $attributes['sambaGroupType'] = 2;
                $attributes['displayName'] = $group_info['group'];
                $attributes['objectClass'][] = 'sambaGroupMapping';
            }
        }

        $attributes['member'] = array();

        if (empty($group_info['members']))
            $group_info['members'] = array(self::CONSTANT_NO_MEMBERS_DN);

        $directory = new Directory_Driver();

        foreach ($group_info['members'] as $member)
            $attributes['member'][] = 'cn=' . $member . ',' . $directory->get_users_container();

        return $attributes;
    }

    /**
     * Converts LDAP attribute array into a regular array.
     *
     * The GroupManager class uses this method.  However, we do not want this
     * method to appear in the API documentation since it is really only for
     * internal use.
     *
     * @access private
     *
     * @return group information in an array
     * @throws Engine_Exception
     */

    public function _convert_ldap_attributes_to_array($attributes)
    {
        clearos_profile(__METHOD__, __LINE__);

        $group_info = array();

        $group_info['gid'] = $attributes['gidNumber'][0];
        $group_info['group'] = $attributes['cn'][0];
        $group_info['description'] = $attributes['description'][0];
        $group_info['members'] = array();

        if (isset($attributes['sambaSID'][0]))
            $group_info['sambaSID'] = $attributes['sambaSID'][0];

        // Convert RFC2307BIS CN member list to username member list

        $rawmembers = $attributes['member'];
        array_shift($rawmembers);

        if ($this->usermap_dn == NULL)
            $this->_load_usermap_from_ldap();

        foreach ($rawmembers as $membercn) {
            if (!empty($this->usermap_dn[$membercn]))
                $group_info['members'][] = $this->usermap_dn[$membercn];
        }

        return $group_info;
    }

    /**
     * Returns the next available group ID.
     *
     * @access private
     *
     * @return string next available group Id
     * @throws Engine_Exception
     */

    public function _get_next_gid_number()
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->ldaph === NULL)
            $this->ldaph = Utilities::get_ldap_handle();

        $directory = new Directory_Driver();

        $dn = $directory->get_master_dn();

        $attributes = $this->ldaph->read($dn);

        // TODO: should add semaphore to prevent duplicate IDs
        $next['gidNumber'] = $attributes['gidNumber'][0] + 1;

        $this->ldaph->modify($dn, $next);

        return $attributes['gidNumber'][0];
    }

    /**
     * Loads group information from LDAP.
     *
     * @access private
     * @return void
     * @throws Engine_Exception
     */

    protected function _load_group_from_ldap()
    {
        clearos_profile(__METHOD__, __LINE__);

        // Load LDAP group object
        //-----------------------

        if ($this->ldaph === NULL)
            $this->ldaph = Utilities::get_ldap_handle();

        $directory = new Directory_Driver();

        $result = $this->ldaph->search(
            "(&(cn=" . $this->group_name . ")(objectclass=posixGroup))",
            $directory->get_groups_container()
        );

        $entry = $this->ldaph->get_first_entry($result);

        if (!$entry)
            return array(); 

        // Convert LDAP attributes into info array
        //----------------------------------------

        $attributes = $this->ldaph->get_attributes($entry);

        $info = Utilities::convert_attributes_to_array($attributes, $this->info_map);

        // Convert RFC2307BIS CN member list to username member list
        //----------------------------------------------------------

        $raw_members = $attributes['member'];
        array_shift($raw_members);

        if ($this->usermap_dn === NULL)
            $this->_load_usermap_from_ldap();

        foreach ($raw_members as $membercn) {
            if (!empty($this->usermap_dn[$membercn]))
                $info['members'][] = $this->usermap_dn[$membercn];
        }

        // Add group type
        //---------------

        // FIXME: move to Samba extension
        /*
        if (! empty($info['sambaSID']))
            $info['sambaSID'] = $info['sambaSID'];
        */

        if (($info['gid_number'] >= self::GID_RANGE_NORMAL_MIN) && ($info['gid_number'] < self::GID_RANGE_NORMAL_MAX))
            $info['type'] = Group::TYPE_NORMAL;
        else if (($info['gid_number'] >= self::GID_RANGE_BUILTIN_MIN) && ($info['gid_number'] < self::GID_RANGE_BUILTIN_MAX))
            $info['type'] = Group::TYPE_BUILTIN;
        else if (($info['gid_number'] >= self::GID_RANGE_WINDOWS_MIN) && ($info['gid_number'] < self::GID_RANGE_WINDOWS_MAX))
            $info['type'] = Group::TYPE_WINDOWS;
        else if (($info['gid_number'] >= self::GID_RANGE_SYSTEM_MIN) && ($info['gid_number'] < self::GID_RANGE_SYSTEM_MAX))
            $info['type'] = Group::TYPE_SYSTEM;
        else
            $info['type'] = Group::TYPE_UNKNOWN;

        return $info;
    }

    /**
     * Loads group from Posix.
     *
     * @access private
     * @return void
     * @throws Engine_Exception
     */

    protected function _load_group_from_posix()
    {
        clearos_profile(__METHOD__, __LINE__);

        $info = array();

        $file = new File(self::FILE_CONFIG);

        try {
            $line = $file->lookup_line('/^' . $this->group_name . ':/i');
        } catch (File_No_Match_Exception $e) {
            return array();;
        }

        $parts = explode(':', $line);

        if (count($parts) != 4)
            return;

        $info['group_name'] = $parts[0];
        $info['gid_number'] = $parts[2];
        $info['members'] = explode(',', $parts[3]);

        // Sanity check: check for non-compliant group ID
        //-----------------------------------------------

// FIXME: - no USER_MIN anymore
        if (($info['gid_number'] >= self::GID_RANGE_SYSTEM_MIN) && ($info['gid_number'] <= self::GID_RANGE_SYSTEM_MAX)) {
            $info['type'] = Group::TYPE_SYSTEM;
        } else if (($info['gid_number'] >= self::GID_RANGE_USER_MIN) && ($info['gid_number'] <= self::GID_RANGE_USER_MAX)) {
            $info['type'] = Group::TYPE_NORMAL;
        } else if (($info['gid_number'] >= self::GID_RANGE_NORMAL_MIN) && ($info['gid_number'] <= self::GID_RANGE_NORMAL_MAX)) {
            $info['type'] = Group::TYPE_UNKNOWN;
            // Logger::Syslog(self::LOG_TAG, "Posix group ID in LDAP group range: " . $info['gid_number']);
        } else {
            $info['type'] = Group::TYPE_UNKNOWN;
            // Logger::Syslog(self::LOG_TAG, "Posix group ID out of range: " . $info['gid_number']);
        }

        return $info;
    }

    /**
     * Loads group from information.
     * 
     * This method loads group information from LDAP if the group exists,
     * otherwise, group information is loaded from /etc/groups.
     *
     * @access private
     *
     * @return void
     * @throws Group_Not_Found_Exception, Engine_Exception
     */

    protected function _load_group_info()
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validate group_name

        $posix_info = $this->_load_group_from_posix();

        if (! empty($posix_info))
            return $posix_info;

        $ldap_info = $this->_load_group_from_ldap();

        if (! empty($ldap_info))
            return $ldap_info;

        throw new Group_Not_Found_Exception();
    }

    /**
     * Loads group list arrays to help with mapping usernames to DNs.
     *
     * RFC2307bis lists a group of users by DN (which is a CN/common name
     * in our implementation).  Since we prefer seeing a group listed by
     * usernames, this method is used to create two hash arrays to map
     * the usernames and DNs.
     *
     * @access private
     * @return void
     */

    protected function _load_usermap_from_ldap()
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->ldaph === NULL)
            $this->ldaph = Utilities::get_ldap_handle();

        $this->usermap_dn = array();
        $this->usermap_username = array();

        $directory = new Directory_Driver();

        $result = $this->ldaph->search(
            "(&(cn=*)(objectclass=posixAccount))",
            $directory->get_users_container(),
            array('dn', 'uid')
        );

        $entry = $this->ldaph->get_first_entry($result);

        while ($entry) {
            $attrs = $this->ldaph->get_attributes($entry);
            $dn = $this->ldaph->get_dn($entry);
            $uid = $attrs['uid'][0];

            $this->usermap_dn[$dn] = $uid;
            $this->usermap_username[$uid] = $dn;

            $entry = $this->ldaph->next_entry($entry);
        }
    }
}
