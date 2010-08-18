<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2005-2008 Point Clark Networks.
//
///////////////////////////////////////////////////////////////////////////////
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//
///////////////////////////////////////////////////////////////////////////////
//
// The RFC2307BIS implementation is used in the underlying implementation of
// groups.  As with other parts of the API, we want to hide these
// implementation issues.  In particular, the members of a group will use
// the more common "list of username" instead of using a "list of full names"
//
// With the NIS schema, a group uses the following structure:
//
// dn: cn=mygroup,ou=Groups,ou=Accounts,dc=example,dc=org
// memberUid: bob
// memberUid: doug
// 
//
// With the RFC2307BIS scheam, a group looks like:
//
// dn: cn=mygroup,ou=Groups,ou=Accounts,dc=example,dc=org
// member: cn=Bob McKenzie,ou=Users,ou=Accounts,dc=example,dc=org
// member: cn=Doug McKenzie,ou=Users,ou=Accounts,dc=example,dc=org
//
///////////////////////////////////////////////////////////////////////////////

/**
 * System group manager.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2005-2008, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('Engine.class.php');
require_once('File.class.php');
require_once('GroupManager.class.php');
require_once('Ldap.class.php');
require_once('ShellExec.class.php');
require_once('User.class.php');
require_once('UserManager.class.php');

///////////////////////////////////////////////////////////////////////////////
// E X C E P T I O N  C L A S S E S
///////////////////////////////////////////////////////////////////////////////

/**
 * Group not found exception.
 *
 * @package Api
 * @subpackage Exception
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2005-2008, Point Clark Networks
 */

class GroupNotFoundException extends EngineException
{
	/**
	 * GroupNotFoundException constructor.
	 *
	 * @param string $folder folder name
	 * @param int $code error code
	 */

	function __construct($group)
	{
		parent::__construct(GROUP_LANG_ERRMSG_NOT_EXIST . " ($group)", COMMON_INFO);
	}
}

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * System group manager.
 *
 * Provides tools for managing user defined groups on the system.  For now,
 * Only the Group->Exists() method uses both LDAP and Posix groups.  All
 * other public methods refer to LDAP groups only.
 *
 * Groups have been segregated into three distinct ID rangs:
 * - System groups: 0-499
 * - User groups: 500-60000
 * - Normal groups: 60001-62000
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2005-2008, Point Clark Networks
 */

class Group extends Engine
{
	///////////////////////////////////////////////////////////////////////////////
	// V A R I A B L E S
	///////////////////////////////////////////////////////////////////////////////

	protected $ldaph = null;
	protected $groupname = null;
	protected $info = null;
	protected $loaded = false;
	protected $usermap_dn = null;
	protected $usermap_username = null;

	const LOG_TAG = 'group';
	const FILE_CONFIG = '/etc/group';
	const TYPE_SYSTEM = 'system';
	const TYPE_USER = 'user';
	const TYPE_GROUP = 'group';
	const TYPE_WINDOWS_GROUP = 'wingroup';
	const TYPE_INVALID = 'invalid';
	const CONSTANT_NO_MEMBERS_USERNAME = 'nomembers';
	const CONSTANT_NO_MEMBERS_DN = 'No Members';
	const CONSTANT_ALL_WINDOWS_USERS_GROUP = 'domain_users';
	const CONSTANT_ALL_USERS_GROUP = 'allusers';
	const CONSTANT_ALL_USERS_GROUP_ID = '400';
	const GID_RANGE_SYSTEM_MIN = '0';
	const GID_RANGE_SYSTEM_MAX = '499';
	const GID_RANGE_USER_MIN = '500';
	const GID_RANGE_USER_MAX = '60000';
	const GID_RANGE_GROUP_MIN = '60001';
	const GID_RANGE_GROUP_MAX = '62000';
	const GID_RANGE_GROUP_WINDOWS_MIN = '1000000';
	const GID_RANGE_GROUP_WINDOWS_MAX = '20000000';
 
	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Group constructor.
	 *
	 * @param string $groupname group name.
	 * @return void
	 */

	function __construct($groupname)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->groupname = $groupname;

		parent::__construct();

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Adds a group to the system.
	 *
	 * @param string $description group description
	 * @return void
	 * @throws ValidationException, EngineException
	 */

	function Add($description, $members = array())
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Validate
		//---------

		if (! $this->IsValidGroupname($this->groupname))
			throw new ValidationException(GROUP_LANG_ERRMSG_NAME_INVALID);

		if ($this->Exists()) {
			if ($this->info['type'] == Group::TYPE_WINDOWS_GROUP)
				$warning = GROUP_LANG_ERRMSG_GROUP_NAME_IS_RESERVED_FOR_WINDOWS;
			else if ($this->info['type'] == Group::TYPE_GROUP)
				$warning = GROUP_LANG_ERRMSG_EXISTS;
			else
				$warning = GROUP_LANG_ERRMSG_GROUP_NAME_IS_RESERVED;

			throw new ValidationException($warning);
		}

		try {
			$user = new User($this->groupname);
			$userexists = $user->Exists();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		if ($userexists)
			throw new ValidationException(GROUP_LANG_ERRMSG_USER_WITH_THIS_NAME_EXISTS);

		try {
			// TODO: this will fail in master/replica mode
			if (file_exists(COMMON_CORE_DIR . "/api/Flexshare.class.php")) {
				require_once(COMMON_CORE_DIR . "/api/Flexshare.class.php");
				$flexshare = new Flexshare();
				$flexname = $flexshare->GetShare($this->groupname);
				// Flexshare name exists if we don't throw FlexshareNotFoundException
				throw new ValidationException(GROUP_LANG_ERRMSG_FLEXSHARE_WITH_THIS_NAME_EXISTS);
			}
		} catch (FlexshareNotFoundException $e) {
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		$info['gid'] = $this->_GetNextGidNumber();
		$info['description'] = $description;
		$info['group'] = $this->groupname;
		$info['members'] = $members;

		$ldap_object = $this->_ConvertArrayToLdapAttributes($info);

		if ($this->ldaph == null)
			$this->_GetLdapHandle();

		try {
			$dn = "cn=" . Ldap::DnEscape($this->groupname) . "," . $this->ldaph->GetGroupsOu();
			$this->ldaph->Add($dn, $ldap_object);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Adds a member to a group.
	 *
	 * @param string $username username
	 * @return false if user was already a member
	 * @throws ValidationException, EngineException
	 */

	function AddMember($username)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$members = $this->GetMembers();

		if (in_array($username, $members)) {
			return false;
		} else {
			$members[] = $username;
			$this->SetMembers($members);	
			$this->loaded = false;
			return true;
		}
	}

	/**
	 * Deletes a group from the system.
	 *
	 * @return void
	 * @throws GroupNotFoundException, EngineException
	 */

	function Delete()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// TODO -- it would be nice to check to see if group is still in use

		if (! $this->IsValidGroupname($this->groupname))
			throw new ValidationException(GROUP_LANG_ERRMSG_NAME_INVALID);

		if (! $this->loaded)
			$this->_LoadGroupInfo();

		if ($this->ldaph == null)
			$this->_GetLdapHandle();

		$dn = "cn=" . Ldap::DnEscape($this->groupname) . "," . $this->ldaph->GetGroupsOu();

		$this->ldaph->Delete($dn);

		$this->loaded = false;
	}

	/**
	 * Deletes a member from a group.
	 *
	 * @param string $username username
	 * @return false if user was already not a member
	 * @throws ValidationException, EngineException
	 */

	function DeleteMember($username)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$members = $this->GetMembers();

		if (in_array($username, $members)) {
			$newmembers = array();

			foreach ($members as $member) {
				if ($member != $username)
					$newmembers[] = $member;
			}

			$this->SetMembers($newmembers);	
			$this->loaded = false;
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Checks the existence of the group.
	 *
	 * @return boolean true if group exists
	 * @throws EngineException
	 */

	function Exists()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$this->_LoadGroupInfo();
		} catch (GroupNotFoundException $e) {
			return false;
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		return true;
	}

	/**
	 * Returns a list of group members.
	 *
	 * @return array list of group members
	 * @throws GroupNotFoundException, EngineException
	 */

	function GetMembers()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->loaded)
			$this->_LoadGroupInfo();

		return $this->info['members'];
	}

	/**
	 * Returns the group ID.
	 *
	 * @return integer group ID
	 * @throws GroupNotFoundException, EngineException
	 */

	function GetGid()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->loaded)
			$this->_LoadGroupInfo();

		return $this->info['gid'];
	}

	/**
	 * Returns the group description.
	 *
	 * @return string group description
	 * @throws GroupNotFoundException, EngineException
	 */

	function GetDescription()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$description = '';

		if (! $this->loaded)
			$this->_LoadGroupInfo();

		if (empty($this->info['description']))
			return "";
		else
			return $this->info['description'];
	}

	/**
	 * Returns the group information.
	 *
	 * @return array group information
	 * @throws GroupNotFoundException, EngineException
	 */

	function GetInfo()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->loaded)
			$this->_LoadGroupInfo();

		return $this->info;
	}

	/**
	 * Sets the group description.
	 *
	 * @param string $description group description
	 * @return void
	 * @throws GroupNotFoundException, EngineException, ValidationException
	 */

	function SetDescription($description)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->loaded)
			$this->_LoadGroupInfo();

		if (! $this->IsValidDescription($description))
			throw new ValidationException(GROUP_LANG_ERRMSG_DESCRIPTION_INVALID);

		$attributes['description'] = $description;

		if ($this->ldaph == null)
			$this->_GetLdapHandle();

		$dn = "cn=" . Ldap::DnEscape($this->groupname) . "," . $this->ldaph->GetGroupsOu();

		$this->ldaph->Modify($dn, $attributes);

		$this->loaded = false;
	}

	/**
	 * Sets the group member list.
	 *
	 * @param array $members array of group members
	 * @return void
	 * @throws GroupNotFoundException, EngineException, ValidationException
	 */

	function SetMembers($members)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->loaded)
			$this->_LoadGroupInfo();

		// Check for invalid users
		//------------------------

		try {
			$usermanager = new UserManager();
			$userlist = $usermanager->GetAllUsers(null, true);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		foreach ($members as $user) {
			if (! in_array($user, $userlist))
				throw new EngineException(USER_LANG_ERRMSG_USERNAME_NOT_EXIST . " - " . $user, COMMON_ERROR);
		}

		if (count($members) == 0)
			$members = array(self::CONSTANT_NO_MEMBERS_USERNAME);

		if ($this->ldaph == null)
			$this->_GetLdapHandle();

		$dn = "cn=" . Ldap::DnEscape($this->groupname) . "," . $this->ldaph->GetGroupsOu();

		if ($this->usermap_username == null)
			$this->_LoadUsermapFromLdap();

		// TODO: Last minute fix in 5.0.  Make sure winadmin stays in the winadmins group.
		// JHT is this necessary given the special SID for winadmin?
		if (($this->groupname == "domain_admins") && !in_array("winadmin", $members))
			$members[] = "winadmin";

		foreach ($members as $member) {
			if (! empty($this->usermap_username[$member]))
				$attributes['member'][] = $this->usermap_username[$member];
		}

		$this->ldaph->Modify($dn, $attributes);

		$this->loaded = false;
	}

	///////////////////////////////////////////////////////////////////////////////
	// V A L I D A T I O N   M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Validation routine for group description.
	 *
	 * @param string description
	 * @return boolean true if description is valid
	 */

	function IsValidDescription($description)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! preg_match('/^([\w \.\-]*)$/', $description)) {
			$this->AddValidationError(GROUP_LANG_ERRMSG_DESCRIPTION_INVALID, __METHOD__ ,__LINE__);
			return false;
		}

		return true;
	}

	/**
	 * Validation routine for group name.
	 *
	 * Groups must begin with a letter and allow underscores.
	 *
	 * @param string groupname
	 * @return boolean true if group name is valid
	 */

	function IsValidGroupname($groupname)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$state = preg_match('/^([a-zA-Z]+[0-9a-zA-Z\.\-_\s]*)$/', $groupname);

		if (!$state) {
			$this->AddValidationError(GROUP_LANG_ERRMSG_NAME_INVALID, __METHOD__ ,__LINE__);
			return false;
		}

		return true;
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
	 * @return group information in an LDAP attributes format
	 * @throws EngineException
	 */

	function _ConvertArrayToLdapAttributes($groupinfo)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$attributes = array();

		$attributes['objectClass'] = array(
			'top',
			'posixGroup',
			'groupOfNames'
		);

		$attributes['gidNumber'] = $groupinfo['gid'];
		$attributes['cn'] = $groupinfo['group'];
		$attributes['description'] = $groupinfo['description'];

		// Add Samba attributes if it is active
		if (file_exists(COMMON_CORE_DIR . "/api/Samba.class.php")) {
			require_once(COMMON_CORE_DIR . "/api/Samba.class.php");

			$samba = new Samba();
			if ($samba->IsDirectoryInitialized()) {
				$sid = $samba->GetDomainSid();
				$attributes['sambaSID'] = $sid . '-' . $groupinfo['gid'];
				$attributes['sambaGroupType'] = 2;
				$attributes['displayName'] = $groupinfo['group'];
				$attributes['objectClass'][] = 'sambaGroupMapping';
			}
		}

		$attributes['member'] = array();

		if (empty($groupinfo['members']))
			$groupinfo['members'] = array(self::CONSTANT_NO_MEMBERS_DN);

		$ldap = new Ldap();
		foreach ($groupinfo['members'] as $member)
			$attributes['member'][] = 'cn=' . $member . ',' . Ldap::CONSTANT_USERS_OU  . ',' . $ldap->GetBaseDn();

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
	 * @return group information in an array
	 * @throws EngineException
	 */

	function _ConvertLdapAttributesToArray($attributes)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$groupinfo = array();

		$groupinfo['gid'] = $attributes['gidNumber'][0];
		$groupinfo['group'] = $attributes['cn'][0];
		$groupinfo['description'] = $attributes['description'][0];
		$groupinfo['members'] = array();

		if (isset($attributes['sambaSID'][0]))
			$groupinfo['sambaSID'] = $attributes['sambaSID'][0];

		// Convert RFC2307BIS CN member list to username member list

		$rawmembers = $attributes['member'];
		array_shift($rawmembers);

		if ($this->usermap_dn == null)
			$this->_LoadUsermapFromLdap();

		foreach ($rawmembers as $membercn) {
			if (!empty($this->usermap_dn[$membercn]))
				$groupinfo['members'][] = $this->usermap_dn[$membercn];
			/*
			else
				Logger::Syslog(self::LOG_TAG, "Found non-existent user in group: " . $membercn . " - " . $this->groupname);
			*/
		}

		return $groupinfo;
	}

	/**
	 * Creates an LDAP handle.
	 *
	 * @access private
	 * @return void
	 * @throws EngineException
	 */

	protected function _GetLdapHandle()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$this->ldaph = new Ldap();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Returns the next available group ID.
	 *
	 * @access private
	 * @return string next available group Id
	 * @throws EngineException
	 */

	function _GetNextGidNumber()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if ($this->ldaph == null)
			$this->_GetLdapHandle();

		try {
			$dn = $this->ldaph->GetMasterDn();
			$attributes = $this->ldaph->Read($dn);
			// TODO: should add semaphore to prevent duplicate IDs
			$next['gidNumber'] = $attributes['gidNumber'][0] + 1;
			$this->ldaph->Modify($dn, $next);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		return $attributes['gidNumber'][0];
	}

	/**
	 * Loads group from LDAP.
	 *
	 * @access private
	 * @return void
	 * @throws EngineException
	 */

	protected function _LoadGroupFromLdap()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if ($this->ldaph == null)
			$this->_GetLdapHandle();

		$result = $this->ldaph->Search(
			"(&(cn=" . $this->groupname . ")(objectclass=posixGroup))",
			$this->ldaph->GetGroupsOu()
		);

		$entry = $this->ldaph->GetFirstEntry($result);

		if (!$entry)
			return; 

		$attributes = $this->ldaph->GetAttributes($entry);
		$groupinfo = Group::_ConvertLdapAttributesToArray($attributes);

		$this->info['group'] = $groupinfo['group'];
		$this->info['valid'] = true;
		$this->info['gid'] = $groupinfo['gid'];
		$this->info['description'] = empty($groupinfo['description']) ? "" : $groupinfo['description'];
		$this->info['members'] = empty($groupinfo['members']) ? array() : $groupinfo['members'];

		if (! empty($groupinfo['sambaSID']))
			$this->info['sambaSID'] = $groupinfo['sambaSID'];

		if (($groupinfo['gid'] >= Group::GID_RANGE_GROUP_WINDOWS_MIN) && ($groupinfo['gid'] < Group::GID_RANGE_GROUP_WINDOWS_MAX))
			$this->info['type'] = Group::TYPE_WINDOWS_GROUP;
		else
			$this->info['type'] = Group::TYPE_GROUP;

		$this->loaded = true;
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

	protected function _LoadUsermapFromLdap()
	{
		if (COMMON_DEBUG_MODE) 
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if ($this->ldaph == null)
			$this->_GetLdapHandle();

		$this->usermap_dn = array();
		$this->usermap_username = array();

		$result = $this->ldaph->Search(
			"(&(cn=*)(objectclass=posixAccount))", 
			$this->ldaph->GetUsersOu(), 
			array('dn', 'uid')
		);

		$entry = $this->ldaph->GetFirstEntry($result);

		while ($entry) {
			$attrs = $this->ldaph->GetAttributes($entry);
			$dn = $this->ldaph->GetDn($entry);
			$uid = $attrs['uid'][0];

			$this->usermap_dn[$dn] = $uid;
			$this->usermap_username[$uid] = $dn;

			$entry = $this->ldaph->NextEntry($entry);
		}
	}

	/**
	 * Loads group from Posix.
	 *
	 * @access private
	 * @return void
	 * @throws EngineException
	 */

	protected function _LoadGroupFromPosix()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$file = new File(self::FILE_CONFIG);

		try {
			$line = $file->LookupLine('/^' . $this->groupname . ':/i');
		} catch (FileNoMatchException $e) {
			return;
		}

		$parts = explode(':', $line);

		if (count($parts) != 4)
			return;

		$group = $parts[0];
		$gid = $parts[2];
		$members = explode(',', $parts[3]);

		// Sanity check #1: check for duplicate Posix/LDAP groups
		//-------------------------------------------------------

		$duplicate = false;

		if (!empty($this->info['group']) && ($this->info['group'] === $group)) {
			// Do not log the "allusers" group
			/*
			if ($group != Group::CONSTANT_ALL_USERS_GROUP)
				Logger::Syslog(self::LOG_TAG, "Posix group overlaps with LDAP group: " . $group);
			*/

			$duplicate = true;
		}

		if (!empty($this->info['gid']) && ($this->info['gid'] === $gid)) {
			// Do not log the "allusers" group
			/*
			if ($gid != Group::CONSTANT_ALL_USERS_GROUP_ID)
				Logger::Syslog(self::LOG_TAG, "Posix group ID overlaps with LDAP group ID: " . $gid);
			*/

			$duplicate = true;
		}

		if ($duplicate)
			return;

		// Sanity check #2: check for non-compliant group ID
		//-------------------------------------------------------

		if (($gid >= Group::GID_RANGE_SYSTEM_MIN) && ($gid <= Group::GID_RANGE_SYSTEM_MAX)) {
			$this->info['type'] = Group::TYPE_SYSTEM;
		} else if (($gid >= Group::GID_RANGE_USER_MIN) && ($gid <= Group::GID_RANGE_USER_MAX)) {
			$this->info['type'] = Group::TYPE_USER;
			// TODO: should either be in LDAP or non-existent?
		} else if (($gid >= Group::GID_RANGE_GROUP_MIN) && ($gid <= Group::GID_RANGE_GROUP_MAX)) {
			$this->info['type'] = Group::TYPE_INVALID;
			Logger::Syslog(self::LOG_TAG, "Posix group ID in LDAP group range: " . $gid);
		} else {
			$this->info['type'] = Group::TYPE_INVALID;
			Logger::Syslog(self::LOG_TAG, "Posix group ID out of range: " . $gid);
		}

		$this->info['valid'] = true;
		$this->info['group'] = $group;
		$this->info['gid'] = $gid;
		$this->info['members'] = $members;
		$this->loaded = true;
	}

	/**
	 * Loads group from information.
	 * 
	 * This method loads group information from LDAP if the group exists,
	 * otherwise, group information is loaded from /etc/groups.
	 *
	 * @access private
	 * @return void
	 * @throws GroupNotFoundException, EngineException
	 */

	protected function _LoadGroupInfo()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (is_null($this->groupname)) {
			$this->loaded = false;
			throw new EngineException(GROUP_LANG_ERRMSG_GROUP_NOT_SET, COMMON_ERROR);
		}

		$this->_LoadGroupFromLdap();
		$this->_LoadGroupFromPosix();

		if (empty($this->info['valid']) || (! $this->info['valid']))
			throw new GroupNotFoundException($this->groupname);
	}

	/**
	 * @access private
	 */

	public function __destruct()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__destruct();
	}
}

// vim: syntax=php ts=4
?>
