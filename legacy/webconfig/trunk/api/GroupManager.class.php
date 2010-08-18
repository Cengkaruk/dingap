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
	protected $groupdata = null;
	protected $hiddenlist = array();
	protected $builtinlist = array();
	private $loaded = false;

	const FILE_CONFIG = '/etc/group';
	const GID_MIN = 60001;
	const GID_MAX = 62000;
	const TYPE_DEFAULT = 12;
	const TYPE_IGNORE_SYSTEM = 14;
	const TYPE_SYSTEM = 1;
	const TYPE_HIDDEN = 2;
	const TYPE_BUILTIN = 4;
	const TYPE_USER_DEFINED = 8;
	
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

		$this->builtinlist = array('allusers', 'domain_admins');
		$this->hiddenlist = array(
			'account_operators',
			'administrators',
			'backup_operators',
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
	 * @param integer $types types of groups
	 * @return array a list of groups
	 * @throws EngineException
	 */

	function GetAllGroups($types = self::TYPE_DEFAULT)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		return $this->_LoadGroupsByFilter('(cn=*)', $types);
	}

	/**
	 * Return a list of all groups definitions.
	 *
	 * @param integer $types types of groups
	 * @return array an array containing group data
	 * @throws EngineException
	 */

	function GetGroupList($types = self::TYPE_DEFAULT)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$ldapdata = array();
		$posixdata = array();

		if (($types & self::TYPE_USER_DEFINED) || ($types & self::TYPE_HIDDEN) || ($types & self::TYPE_BUILTIN))
			$ldapdata = $this->_LoadGroupListFromLdap($types);

		if ($types & self::TYPE_SYSTEM)
			$posixdata = $this->_LoadGroupListFromPosix();

		$data = array_merge($ldapdata, $posixdata);

		return $data;
	}

	/**
	 * Returns the list of groups for given username.
	 *
	 * @param string $username username
	 * @return array a list of groups
	 * @throws EngineException
	 */

	function GetGroupMemberships($username)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if ($this->ldaph == null)
			$this->_GetLdapHandle();

		$dn = $this->ldaph->GetDnForUid($username);

		return $this->_LoadGroupsByFilter("(member=$dn)", GroupManager::TYPE_DEFAULT);
	}

	/**
	 * Updates group membership for given user.
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
		$all = $this->GetAllGroups(GroupManager::TYPE_IGNORE_SYSTEM);

		// Add required groups
		if (! in_array(Group::CONSTANT_ALL_USERS_GROUP, $groups))
			$groups[] = Group::CONSTANT_ALL_USERS_GROUP;

		if (! in_array(Group::CONSTANT_ALL_WINDOWS_USERS_GROUP, $groups))
			$groups[] = Group::CONSTANT_ALL_WINDOWS_USERS_GROUP;

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
	// V A L I D A T I O N   M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Validation routine for group ID.
	 *
	 * @param  gid  a GID
	 * @return  boolean true if gid is valid
	 */

	function IsValidGid($gid)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! is_int($gid) || $gid < self::GID_MIN || $gid > self::GID_MAX) {
			$this->AddValidationError(GROUP_LANG_ERRMSG_GID_INVALID, __METHOD__, __LINE__);
			return false;
		}

		return true;
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
	 * Loads a simple group list from LDAP given filter.
	 *
	 * @param string $filter LDAP filter
	 * @param integer $types types of groups
	 * @access private
	 * @throws EngineException
	 */

	protected function _LoadGroupsByFilter($filter, $types)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if ($this->ldaph == null)
			$this->_GetLdapHandle();

		$grouplist = array();

		$result = $this->ldaph->Search(
			"(&$filter(objectclass=posixGroup))", 
			$this->ldaph->GetGroupsOu(),
			array('cn')
		);

		$this->ldaph->Sort($result, 'cn');
		$entry = $this->ldaph->GetFirstEntry($result);

		while ($entry) {
			$attributes = $this->ldaph->GetAttributes($entry);

			// TODO: temporarily hide printadmins
			if ($attributes['cn'][0] == "printadmins") {
				$entry = $this->ldaph->NextEntry($entry);
				continue;
			}

			if (($types & self::TYPE_HIDDEN) && in_array($attributes['cn'][0], $this->hiddenlist) ||
				($types & self::TYPE_BUILTIN) && in_array($attributes['cn'][0], $this->builtinlist) ||
				(($types & self::TYPE_USER_DEFINED) && !in_array($attributes['cn'][0], $this->hiddenlist) && !in_array($attributes['cn'][0], $this->builtinlist)))
				$grouplist[] = $attributes['cn'][0];

			$entry = $this->ldaph->NextEntry($entry);
		}

		return $grouplist;
	}

	/**
	 * Loads groups from LDAP.
	 *
	 * @access private
	 * @param integer $types types of groups
	 * @throws EngineException
	 */

	protected function _LoadGroupListFromLdap($types)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if ($this->ldaph == null)
			$this->_GetLdapHandle();

		$grouplist = array();
		$result = $this->ldaph->Search(
			"(&(cn=*)(objectclass=posixGroup))",
			$this->ldaph->GetGroupsOu()
		);
		$this->ldaph->Sort($result, 'cn');
		$entry = $this->ldaph->GetFirstEntry($result);

		while ($entry) {
			$attributes = $this->ldaph->GetAttributes($entry);

			// TODO: temporarily hide printadmins
			if ($attributes['cn'][0] == "printadmins") {
				$entry = $this->ldaph->NextEntry($entry);
				continue;
			}

			if (($types & self::TYPE_HIDDEN) && in_array($attributes['cn'][0], $this->hiddenlist) ||
				($types & self::TYPE_BUILTIN) && in_array($attributes['cn'][0], $this->builtinlist) ||
				(($types & self::TYPE_USER_DEFINED) && !in_array($attributes['cn'][0], $this->hiddenlist) && !in_array($attributes['cn'][0], $this->builtinlist))) {
				$group = new Group("notused");
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

	protected function _LoadGroupListFromPosix()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$file = new File(self::FILE_CONFIG);
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
