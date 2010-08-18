<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2003-2010 Point Clark Networks.
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
// It is beneficial to keep an LDAP connection open through the life of
// of the object.  This makes the $this->ldaph a bit of a unique 
// implementation in the API.
//
///////////////////////////////////////////////////////////////////////////////

/**
 * User database information.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2010, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once("ClearDirectory.class.php");
require_once("Ldap.class.php");
require_once("User.class.php");

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * User database information.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2010, Point Clark Networks
 */

class UserManager extends Engine
{
	///////////////////////////////////////////////////////////////////////////////
	// F I E L D S
	///////////////////////////////////////////////////////////////////////////////

	protected $ldaph = null;

	const CONSTANT_SYSTEM_ID = 500;
	const CONSTANT_BUILTIN_START = 300;
	const CONSTANT_BUILTIN_END = 399;
	const COMMAND_SYNCUSERS = "/usr/sbin/syncusers";

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * User manager constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct();

		require_once(GlobalGetLanguageTemplate(preg_replace("/Manager/", "", __FILE__)));
	}

	/**
	 * Returns the user list.
	 *
	 * @param string $type service type
	 * @param bool $showhidden include hidden accounts
	 * @return array user list
	 * @throws EngineException
	 */

	function GetAllUsers($type = null, $showhidden = false)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$rawlist = $this->_LdapGetUserList($showhidden, $type);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		$userlist = array();

		foreach ($rawlist as $username => $userinfo)
			$userlist[] = $username;

		return $userlist;
	}
	
	/**
	 * Returns detailed user information for all users.
	 *
	 * @param bool $showhidden include hidden accounts
	 * @return array user information array
	 * @throws EngineException
	 */

	function GetAllUserInfo($showhidden = false)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$rawlist = $this->_LdapGetUserList($showhidden);
		$userlist = array();

		foreach ($rawlist as $index => $userinfo) {

			$userlist[$index] = $userinfo;
		}

		return $userlist;
	}

	/**
	 * Returns home directory list by username.
	 *
	 * @return array home directory list by username
	 * @throws EngineException
	 */

	function GetHomeDirectories()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if ($this->ldaph == null)
			$this->_GetLdapHandle();

		$homedirs = array();
		$result = $this->ldaph->Search(
			"(&(cn=*)(objectclass=pcnAccount))",
			ClearDirectory::GetUsersOu(),
			array("homeDirectory", "uid", "uidNumber")
		);
		$this->ldaph->Sort($result, 'uid');
		$entry = $this->ldaph->GetFirstEntry($result);

		while ($entry) {
			$attributes = $this->ldaph->GetAttributes($entry);

			$uid = $attributes['uidNumber'][0];

			// Skip directories set to /dev/null and system accounts
			if (!preg_match("/\/dev\/null/", $attributes['homeDirectory'][0]) && ($uid >= UserManager::CONSTANT_SYSTEM_ID)) {
				$homedirs[$attributes['uid'][0]]['homedirectory'] = $attributes['homeDirectory'][0];
				$homedirs[$attributes['uid'][0]]['group'] = User::DEFAULT_USER_GROUP;
				$homedirs[$attributes['uid'][0]]['permissions'] = User::DEFAULT_HOMEDIR_PERMS;
			}

			$entry = $this->ldaph->NextEntry($entry);
		}

		return $homedirs;
	}

	/**
	 * Synchronizes user database.
	 *
	 * @return void
	 * @throws EngineException
	 */

	function Synchronize()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$options['background'] = true;
			$shell = new ShellExec();
			$shell->Execute(UserManager::COMMAND_SYNCUSERS, "", true, $options);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
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
		try {
			$this->ldaph = new Ldap();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Returns user information.
	 *
	 * The type (e.g. ClearDirectory::SERVICE_TYPE_OPENVPN) can be used
	 * to filter results.
	 *
	 * @param bool $showhidden include hidden accounts
	 * @param string $type ClearDirectory user service type
	 * @access private
	 * @return array user information
	 */

	protected function _LdapGetUserList($showhidden = false, $type = null)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if ($this->ldaph == null)
			$this->_GetLdapHandle();

		/// TODO: Samba detection needs to be revisited
		if (! is_null($type)) {
			if ($type == ClearDirectory::SERVICE_TYPE_FTP)
				$search = "(pcnFTPFlag=TRUE)";
			else if ($type == ClearDirectory::SERVICE_TYPE_EMAIL)
				$search = "(pcnMailFlag=TRUE)";
			else if ($type == ClearDirectory::SERVICE_TYPE_GOOGLE_APPS)
				$search = "(pcnGoogleAppsFlag=TRUE)";
			else if ($type == ClearDirectory::SERVICE_TYPE_OPENVPN)
				$search = "(pcnOpenVPNFlag=TRUE)";
			else if ($type == ClearDirectory::SERVICE_TYPE_PPTP)
				$search = "(pcnPPTPFlag=TRUE)";
			else if ($type == ClearDirectory::SERVICE_TYPE_PROXY)
				$search = "(pcnProxyFlag=TRUE)";
			else if ($type == ClearDirectory::SERVICE_TYPE_SAMBA)
				$search = "(sambaAcctFlags=[U		 ])";
			else if ($type == ClearDirectory::SERVICE_TYPE_WEBCONFIG)
				$search = "(pcnWebconfigFlag=TRUE)";
			else if ($type == ClearDirectory::SERVICE_TYPE_WEB)
				$search = "(pcnWebFlag=TRUE)";
			else
				throw new EngineException(LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - type", COMMON_WARNING);
		
		} else {
			$search = "";
		}

		$userlist = array();

		$result = $this->ldaph->Search(
			"(&(cn=*)(objectclass=posixAccount)$search)",
			ClearDirectory::GetUsersOu()
		);

		$this->ldaph->Sort($result, 'uid');
		$entry = $this->ldaph->GetFirstEntry($result);

		while ($entry) {
			$attrs = $this->ldaph->GetAttributes($entry);
			$dn = $this->ldaph->GetDn($entry);
			$uid = $attrs['uidNumber'][0];
			$username = $attrs['uid'][0];

			if ($showhidden ||
				 (!(($uid >= self::CONSTANT_BUILTIN_START) && ($uid <= self::CONSTANT_BUILTIN_END)))) {
				$user = new User("not used");
				$userinfo = $user-> _ConvertLdapToArray($attrs);
				$userlist[$username] = $userinfo;
			}

			$entry = $this->ldaph->NextEntry($entry);
		}

		return $userlist;
	}

	/**
	 * @access private
	 */

	function __destruct()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__destruct();
	}
}

?>
