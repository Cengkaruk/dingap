<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2005-2010 Point Clark Networks.
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

/**
 * System group manager.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2005-2010, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('Engine.class.php');
require_once('ClearDirectory.class.php');
require_once('File.class.php');
require_once('Group.class.php');
require_once('Ldap.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * System group manager.
 *
 * Provides tools for listing aggregate group data defined on the system.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class GroupManager extends Engine
{
	///////////////////////////////////////////////////////////////////////////////
	// V A R I A B L E S
	///////////////////////////////////////////////////////////////////////////////

	protected $ldaph = null;
	
	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * GroupManager constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		parent::__construct();

		require_once(GlobalGetLanguageTemplate(preg_replace("/Manager/", "", __FILE__)));
	}

	/**
	 * Deletes the given username from all groups.
	 *
	 * @param string $username username
	 * @return void
	 * @throws EngineException
	 */

	function DeleteGroupMemberships($username)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$grouplist = $this->GetGroupMemberships($username);

		foreach ($grouplist as $groupname) {
			$group = new Group($groupname);
			$group->DeleteMember($username);
		}
	}

	/**
	 * Return a list of groups.
	 *
	 * @param integer $filter filter for specific groups
	 * @return array a list of groups
	 * @throws EngineException
	 */

	function GetAllGroups($filter = Group::FILTER_DEFAULT)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$groupsinfo = $this->_LoadGroups('(cn=*)', $filter);

		$grouplist = array();

		foreach ($groupsinfo as $name => $info)
			$grouplist[] = $info['group'];

		return $grouplist;
	}

	/**
	 * Return a list of all groups definitions.
	 *
	 * @param integer $filter filter for specific groups
	 * @return array an array containing group data
	 * @throws EngineException
	 */

	function GetGroupList($filter = Group::FILTER_DEFAULT)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		return $this->_LoadGroups("(cn=*)", $filter);
	}

	/**
	 * Returns the list of groups for given username.
	 *
	 * @param string $username username
	 * @param integer $filter filter for specific groups
	 * @return array a list of groups
	 * @throws EngineException
	 */

	function GetGroupMemberships($username, $filter = Group::FILTER_DEFAULT)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if ($this->ldaph == null)
			$this->_GetLdapHandle();

		$dn = $this->ldaph->GetDnForUid($username);

		$groupsinfo = $this->_LoadGroups("(member=$dn)", $filter);

		$grouplist = array();

		foreach ($groupsinfo as $name => $info)
			$grouplist[] = $info['group'];

		return $grouplist;
	}

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
	 * @return void
	 */

	function InitalizeGroupMemberships($username)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// See if Samba is up and running
		try {	
			$group = new Group(Group::CONSTANT_ALL_WINDOWS_USERS_GROUP); 
			$samba_active = $group->Exists();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		// Add to "allusers" group
		$group = new Group(Group::CONSTANT_ALL_USERS_GROUP);
		$group->AddMember($username);

		// Add domain_users group if Samba is up and running
		if ($samba_active) {
			$group = new Group(Group::CONSTANT_ALL_WINDOWS_USERS_GROUP);
			$group->AddMember($username);
		}
	}

	/**
	 * Updates group membership for given user.
	 *
	 * This method does not change the settings in built-in groups.  If this
	 * functionality is required, we can add a flag to this method.
	 *
	 * @param string $username username
	 * @param array $groups list of active groups
	 * @return void
	 */

	function UpdateGroupMemberships($username, $groups)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$current = $this->GetGroupMemberships($username);
		$all = $this->GetAllGroups(Group::FILTER_NORMAL);

		foreach ($all as $groupname) {
			if (in_array($groupname, $groups) && !in_array($groupname, $current)) {
				$group = new Group($groupname);
				$group->AddMember($username);
			} else if (!in_array($groupname, $groups) && in_array($groupname, $current)) {
				$group = new Group($groupname);
				$group->DeleteMember($username);
			}
		}
	}

	///////////////////////////////////////////////////////////////////////////////
	// P R I V A T E   M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

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
	 * Loads a full list of groups with detailed information.
	 *
	 * @param string $ldapfilter LDAP filter
	 * @param integer $filter filter for specific groups
	 * @return array an array containing group data
	 * @throws EngineException
	 */

	protected function _LoadGroups($ldapfilter, $filter)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$ldapdata = array();
		$posixdata = array();

		// Load LDAP groups (not required if only looking for the system/Posix groups)
		if (!($filter === Group::FILTER_SYSTEM))
			$ldapdata = $this->_LoadGroupsFromLdap($ldapfilter, $filter);

		if ($filter & Group::FILTER_SYSTEM)
			$posixdata = $this->_LoadGroupsFromPosix();

		$data = array_merge($ldapdata, $posixdata);

		return $data;
	}

	/**
	 * Loads groups from LDAP.
	 *
	 * @access private
	 * @param string $ldapfilter LDAP filter
	 * @param integer $filter filter for specific groups
	 * @throws EngineException
	 */

	protected function _LoadGroupsFromLdap($ldapfilter, $filter)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if ($this->ldaph == null)
			$this->_GetLdapHandle();

		// TODO: static
		$group = new Group("notused");

		$grouplist = array();

		$result = $this->ldaph->Search(
			"(&$ldapfilter(objectclass=posixGroup))", 
			ClearDirectory::GetGroupsOu()
		);

		$this->ldaph->Sort($result, 'cn');
		$entry = $this->ldaph->GetFirstEntry($result);

		while ($entry) {
			$attributes = $this->ldaph->GetAttributes($entry);

			if  (
				(($filter & Group::FILTER_HIDDEN) && in_array($attributes['cn'][0], $group->hiddenlist)) ||
				(($filter & Group::FILTER_BUILTIN) && in_array($attributes['cn'][0], $group->builtinlist)) ||
				(($filter & Group::FILTER_WINDOWS) && in_array($attributes['cn'][0], $group->windowslist)) ||
				(($filter & Group::FILTER_NORMAL) && ($attributes['gidNumber'][0] >= Group::GID_RANGE_NORMAL_MIN) &&
 						($attributes['gidNumber'][0] <= Group::GID_RANGE_NORMAL_MAX))
				) {
				$groupinfo = $group->_ConvertLdapAttributesToArray($attributes);
				$grouplist[$attributes['gidNumber'][0]] = $groupinfo;
			}

			$entry = $this->ldaph->NextEntry($entry);
		}

		return $grouplist;
	}

	/**
	 * Loads groups from Posix.
	 *
	 * @access private
	 * @throws  EngineException
	 */

	protected function _LoadGroupsFromPosix()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$file = new File(Group::FILE_CONFIG);
			$contents = $file->GetContentsAsArray();
		} catch (Exception $e) {
			throw new EngineException(LOCALE_LANG_ERRMSG_PARSE_ERROR, COMMON_ERROR);
		}

		$groupdata = array();

		foreach($contents as $line) {
			$data = explode(":", $line);

			if (count($data) == 4) {
				$gid = $data[2];
				$assoc_data['group'] = $data[0];
				$assoc_data['description'] = '';
				$assoc_data['members'] = explode(',', $data[3]);
				$groupdata[$gid] = $assoc_data;
			}
		}

		ksort($groupdata);

		return $groupdata;
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
