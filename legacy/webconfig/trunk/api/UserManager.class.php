<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2003-2009 Point Clark Networks.
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
 * @copyright Copyright 2003-2009, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once("File.class.php");
require_once("Ldap.class.php");
require_once("ClearDirectory.class.php");
require_once("User.class.php");
// FIXME: remove unused Os class?
require_once("Os.class.php");

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * User database information.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2009, Point Clark Networks
 */

class UserManager extends Engine
{
	///////////////////////////////////////////////////////////////////////////////
	// F I E L D S
	///////////////////////////////////////////////////////////////////////////////

	protected $ldaph = null;

	const CONSTANT_CONVERTED = "converted";
	const CONSTANT_EXISTS = "exists";
	const CONSTANT_ERROR = "error";
	const CONSTANT_SYSTEM_ID = 500;
	const CONSTANT_BUILTIN_START = 300;
	const CONSTANT_BUILTIN_END = 399;
	const COMMAND_SYNCUSERS = "/usr/sbin/syncusers";
	const COMMAND_USERSETUP = "/usr/sbin/usersetup";
	const FILE_USERS_2X = "/etc/passwd";
	const FILE_USERS_3X = "/etc/users";
	const FILE_CONVERT_STATUS = "/usr/share/system/modules/users/user_upgrade_status";
	const FILE_CONVERT_HIDE = "/usr/share/system/modules/users/user_upgrade_hide";
	const TYPE_FTP = "pcnFTPPassword";
	const TYPE_EMAIL = "pcnMailPassword";
	const TYPE_OPENVPN = "pcnOpenVPNPassword";
	const TYPE_PPTP = "pcnPPTPPassword";
	const TYPE_PROXY = "pcnProxyPassword";
	const TYPE_SAMBA = "pcnSambaPassword";
	const TYPE_WEBCONFIG = "pcnWebconfigPassword";
	const TYPE_WEB = "pcnWebPassword";
	const TYPE_PBX = "pcnPbxPassword";

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
	 * Converts old user database.
	 *
	 * @return array status information, empty array if no conversion
	 * @throws EngineException
	 */

	function ConvertUsers()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$statusinfo = array();

		try {
			// Bail if conversion complete, and status is hidden
			//--------------------------------------------------

			$status_hidden = new File(self::FILE_CONVERT_HIDE);

			if ($status_hidden->Exists())
				return array();

			// Show conversion details if status file exists
			//----------------------------------------------
	
			$convert_status = new File(self::FILE_CONVERT_STATUS);

			if ($convert_status->Exists())
				return $this->GetConversionStatus();

			// Convert the old user databases.
			//--------------------------------------------------

			$old_users_file = new File(self::FILE_USERS_3X);

			$users = array();

			if ($old_users_file->Exists())
				$users = $this->_LoadUsers3x();

			$statusinfo = $this->_UsersToLdap($users);

			// Save the conversion status information
			//----------------------------------------------------

			$loglines = "";

			foreach ($statusinfo as $user => $info) {
				$loglines .= 
					$user . "|" . 
					$info['status'] . "|" . 
					$info['statustext'] . "|" . 
					$info['namechange'] . "|" .
					$info['passwordreset'] .
					"\n";
			}

			$convert_status->Create("root", "root", "0644");
			$convert_status->AddLines($loglines);

		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		return $this->GetConversionStatus();
	}

	/**
	 * Returns conversion status information.
	 *
	 * The status information contains the following fields:
	 * $statusinfo['username']['status']	  status number CONSTANT_CONVERTED, CONSTANT_EXISTS, CONSTANT_ERROR
	 * $statusinfo['username']['statustext']  status in human readable form
	 * $statusinfo['username']['namechange']  flag when name should be checked
	 * $statusinfo['username']['passwordreset']  flag when password reset is required
	 *
	 * @return array status information
	 * @throws EngineException
	 */

	function GetConversionStatus()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$convert_status = new File(self::FILE_CONVERT_STATUS);
		$statusinfo = array();

		if ($convert_status->Exists()) {
			try {
				$lines = $convert_status->GetContentsAsArray();
				foreach ($lines as $line) {
					$items = preg_split("/\|/", $line);
					$statusinfo[$items[0]]['status'] = $items[1];
					$statusinfo[$items[0]]['statustext'] = $items[2];
					$statusinfo[$items[0]]['namechange'] = $items[3];
					$statusinfo[$items[0]]['passwordreset'] = $items[4];
				}

				return $statusinfo;
			} catch (Exception $e) {
				throw new EngineException($e->GetMessage(), COMMON_WARNING);
			}
		} else {
			return array();
		}
	}

	/**
	 * Returns list of installed services.
	 *
	 * @return array list of installed services
	 */

	function GetInstalledServices()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$services = array();

		// TODO: detect this a better way
		if (file_exists("/usr/bin/smbstatus"))
			$services[] = User::SERVICE_SAMBA;

		if (file_exists(COMMON_CORE_DIR . "/api/Cyrus.class.php"))
			$services[] = User::SERVICE_EMAIL;

		if (file_exists(COMMON_CORE_DIR . "/api/Pptpd.class.php"))
			$services[] = User::SERVICE_PPTP;

		if (file_exists(COMMON_CORE_DIR . "/api/OpenVpn.class.php"))
			$services[] = User::SERVICE_OPENVPN;

		if (file_exists(COMMON_CORE_DIR . "/api/Squid.class.php"))
			$services[] = User::SERVICE_PROXY;

		if (file_exists(COMMON_CORE_DIR . "/api/Proftpd.class.php"))
			$services[] = User::SERVICE_FTP;

		if (file_exists(COMMON_CORE_DIR . "/api/Httpd.class.php"))
			$services[] = User::SERVICE_WEB;

		if (file_exists(COMMON_CORE_DIR . "/iplex/Users.class.php"))
			$services[] = User::SERVICE_PBX;

		return $services;
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
			$this->ldaph->GetUsersOu(),
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
	 * Hides upgrade status.
	 *
	 * @return void
	 * @throws EngineException
	 */

	function HideConversionStatus()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$file = new File(self::FILE_CONVERT_HIDE);

		try {
			if (! $file->Exists())
				$file->Create("root", "root", "0644");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Run user database initialization if domain is configured.
	 *
	 * @return void
	 * @throws EngineException
	 */

	function RunInitialize()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$options['background'] = true;

			$shell = new ShellExec();
			$shell->Execute(UserManager::COMMAND_USERSETUP, "", true, $options);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
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
	 * @param bool $showhidden include hidden accounts
	 * @param string $type service type
	 * @access private
	 * @return array user information
	 */

	protected function _LdapGetUserList($showhidden = false, $type = null)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if ($this->ldaph == null)
			$this->_GetLdapHandle();

		/// TODO: Samba detection needs to be revisited in 5.1
		if (! is_null($type)) {
			if ($type == self::TYPE_FTP)
				$search = "(pcnFTPFlag=TRUE)";
			else if ($type == self::TYPE_EMAIL)
				$search = "(pcnMailFlag=TRUE)";
			else if ($type == self::TYPE_OPENVPN)
				$search = "(pcnOpenVPNFlag=TRUE)";
			else if ($type == self::TYPE_PPTP)
				$search = "(pcnPPTPFlag=TRUE)";
			else if ($type == self::TYPE_PROXY)
				$search = "(pcnProxyFlag=TRUE)";
			else if ($type == self::TYPE_SAMBA)
				$search = "(sambaAcctFlags=[U		 ])";
			else if ($type == self::TYPE_WEBCONFIG)
				$search = "(pcnWebconfigFlag=TRUE)";
			else if ($type == self::TYPE_WEB)
				$search = "(pcnWebFlag=TRUE)";
			else
				throw new EngineException(LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - type", COMMON_WARNING);
		
		} else {
			$search = "";
		}

		$userlist = array();

		$result = $this->ldaph->Search(
			"(&(cn=*)(objectclass=posixAccount)$search)",
			$this->ldaph->GetUsersOu()
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
	 * Loads /etc/users into a hash array.
	 *
	 * @access private
	 * @return array user information
	 */

	function _LoadUsers3x()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$users = array();

		try {
			$users3x = new File(self::FILE_USERS_3X);
			$lines = $users3x->GetContentsAsArray();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		foreach ($lines as $line) {

			// fields:
			// 0  - Username
			// 1  - Posix UID
			// 2  - Posix GID
			// 4  - Full name
			// 5  - Home directory
			// 6  - Weak password
			// 7  - Strong password
			// 10 - E-mail user
			// 13 - PPTP user
			// 14 - Samba user

			$fields = explode("|", $line);

			$username = $fields[0];

			if ($username == "root")
				continue;

			$users[$username]['uidNumber'] = $fields[1];
			$users[$username]['gidNumber'] = $fields[2];
			$users[$username]['fullname'] = $fields[4];
			$users[$username]['password'] = $fields[6];
			$users[$username]['homeDirectory'] = "/home/$username";
			$users[$username]['pcnMailPasswordFlag'] = (preg_match("/1/", $fields[10])) ? true : false;
			$users[$username]['pcnPPTPPasswordFlag'] = (preg_match("/1/", $fields[13])) ? true : false;
			$users[$username]['pcnSambaPasswordFlag'] = (preg_match("/1/", $fields[14])) ? true : false;

			if (file_exists(COMMON_CORE_DIR . "/api/Proftpd.class.php"))
				$users[$username]['pcnFTPPasswordFlag'] = true;
			else
				$users[$username]['pcnFTPPasswordFlag'] = false;

			$users[$username]['pcnOpenVPNPasswordFlag'] = false;
			$users[$username]['pcnProxyPasswordFlag'] = false;
			$users[$username]['pcnWebPasswordFlag'] = false;
		}

		return $users;
	}

	/**
	 * Converts user information array into LDAP.
	 *
	 * $users[$username]['uidNumber']
	 * $users[$username]['gidNumber']
	 * $users[$username]['fullname']
	 * $users[$username]['homeDirectory']
	 * $users[$username]['pcnMailPasswordFlag']
	 * $users[$username]['pcnPPTPPasswordFlag']
	 * $users[$username]['pcnSambaPasswordFlag']
	 * $users[$username]['pcnFTPPasswordFlag']
	 * $users[$username]['pcnProxyPasswordFlag']
	 * $users[$username]['pcnWebPasswordFlag']
	 *
	 * @access private
	 * @param array $users user hash array
	 * @return array status information, empty array if no conversion
	 */

	function _UsersToLdap($users)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$namelist = array();
		$statusinfo = array();

		try {
			$ldap = new Ldap();
			$basedn = $ldap->GetBaseDn();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		foreach ($users as $username => $userinfo) {

			// Old database just had a full name, e.g. Mary Ann Smith, David Van Halen, Sting
			// The new database has first name and last name.  We just make a guess where 
			// the full name was not made of two parts -- and we notify the user.

			$statusinfo[$username]['namechange'] = false;

			$namefields = preg_split("/\s+/", $userinfo['fullname']);

			if (count($namefields) != 2)
				$statusinfo[$username]['namechange'] = true;

			// If DN already exists (two usernames with the same first/last name), fake it

			if (in_array($userinfo['fullname'], $namelist)) {
				$userinfo['fullname'] = $userinfo['fullname'] . " " . $userinfo['uidNumber'];
				$statusinfo[$username]['namechange'] = true;
			}

			$namelist[] = $userinfo['fullname'];

			// Generate user record
			//---------------------

			$namedetails = preg_split("/\s+/", $userinfo['fullname'], 2);

			$ldapinfo['givenName'] = (! empty($namedetails[0])) ? $namedetails[0] : "Unknown_" . $username;
			$ldapinfo['sn'] = (! empty($namedetails[1])) ? $namedetails[1] : "Unknown_" . $username;
			$ldapinfo['dn'] = "cn=" . $ldapinfo['givenName'] . " " . $ldapinfo['sn'] . "," . $basedn;
			$ldapinfo['cn'] = $ldapinfo['givenName'] . " " . $ldapinfo['sn'];
			$ldapinfo['uid'] = $username;

			$ldapinfo['uidNumber'] = $userinfo['uidNumber'];
			$ldapinfo['gidNumber'] = $userinfo['gidNumber'];
			$ldapinfo['homeDirectory'] = $userinfo['homeDirectory'];
			$ldapinfo['pcnMailPasswordFlag'] = $userinfo['pcnMailPasswordFlag'];
			$ldapinfo['pcnOpenVPNPasswordFlag'] = $userinfo['pcnOpenVPNPasswordFlag'];
			$ldapinfo['pcnPPTPPasswordFlag'] = $userinfo['pcnPPTPPasswordFlag'];
			$ldapinfo['pcnSambaPasswordFlag'] = $userinfo['pcnSambaPasswordFlag'];
			$ldapinfo['pcnFTPPasswordFlag'] = $userinfo['pcnFTPPasswordFlag'];
			$ldapinfo['pcnProxyPasswordFlag'] = $userinfo['pcnProxyPasswordFlag'];
			$ldapinfo['pcnWebPasswordFlag'] = $userinfo['pcnWebPasswordFlag'];

			// Check the password (which may be blank or invalid)
			//---------------------------------------------------

			$user = new User($username);

			try {
				if ($user->IsValidPassword($userinfo['password'], $userinfo['password'])) {
					$ldapinfo['password'] = $userinfo['password'];
					$statusinfo[$username]['passwordreset'] = false;
				} else {
					$directory = new ClearDirectory();
					$ldapinfo['password'] = $directory->GeneratePassword();
					$statusinfo[$username]['passwordreset'] = true;
				}
			} catch (Exception $e) {
				// Something really bad has happened.  It will get caught below.
				$ldapinfo['password'] = $userinfo['password'];
				$statusinfo[$username]['passwordreset'] = true;
			}

			$ldapinfo['verify'] = $ldapinfo['password'];

			// Add the user
			//-------------

			try {
				if ($user->Exists()) {
					$statusinfo[$username]['status'] = UserManager::CONSTANT_EXISTS;
					$statusinfo[$username]['statustext'] = USER_LANG_STATUS_EXISTS;
					$statusinfo[$username]['passwordreset'] = false;
					$statusinfo[$username]['namechange'] = false;
				} else {
					$user->Add($ldapinfo);
					$statusinfo[$username]['status'] = UserManager::CONSTANT_CONVERTED;
					$statusinfo[$username]['statustext'] = USER_LANG_STATUS_CONVERTED;
				}
			} catch (ValidationException $e) {
				$valerrors = $user->GetValidationErrors(true);

				$statustext = "";
				foreach ($valerrors as $valerror)
					$statustext .= $valerror . " &#160; ";

				$statusinfo[$username]['status'] = UserManager::CONSTANT_ERROR;
				$statusinfo[$username]['statustext'] = $statustext;
				$statusinfo[$username]['passwordreset'] = false;
				$statusinfo[$username]['namechange'] = false;
			} catch (Exception $e) {
				$statusinfo[$username]['status'] = UserManager::CONSTANT_ERROR;
				$statusinfo[$username]['statustext'] = $e->GetMessage();
				$statusinfo[$username]['passwordreset'] = false;
				$statusinfo[$username]['namechange'] = false;
			}
		}

		return $statusinfo;
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
