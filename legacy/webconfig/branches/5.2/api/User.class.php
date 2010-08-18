<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2003-2009 Point Clark Networks.
// 
// NT and Lanman password hash comes from Pear's Crypt_CHAP
// Copyright 2002-2003, Michael Bretterklieber <michael@bretterklieber.com>
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
// The API should hide the underlying implementation.  In other words, a
// developer using the User class should see any LDAP-isms in the API.  To help
// with this requirement, a
//
///////////////////////////////////////////////////////////////////////////////

/**
 * User administration.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2008, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once("ClearDirectory.class.php");
require_once("Country.class.php");
require_once("Ldap.class.php");
require_once("Organization.class.php");
require_once("ShellExec.class.php");
require_once("Shell.class.php");

///////////////////////////////////////////////////////////////////////////////
// E X C E P T I O N  C L A S S E S
///////////////////////////////////////////////////////////////////////////////

/**
 * User not found exception.
 *
 * @package Api
 * @subpackage Exception
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2007, Point Clark Networks
 */

class UserNotFoundException extends EngineException
{
	/**
	 * UserNotFoundException constructor.
	 *
	 * @param string $username username
	 */

	function __construct($username)
	{
		parent::__construct(USER_LANG_ERRMSG_USER_NOT_FOUND . " - " . $username, COMMON_NOTICE);
	}
}

/**
 * User already exists exception.
 *
 * There are two ways to trigger a UserAlreadyExistsException:
 * 1) Duplicate usernames
 * 2) Duplicate first/last name
 *
 * The second type of restriction will be phased out (good).
 * To keep things sane for the end user, the "fullname" parameter was
 * added to the constructor.  The end user will then see a more 
 * informative error message.
 *
 * @package Api
 * @subpackage Exception
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006-2007, Point Clark Networks
 */

class UserAlreadyExistsException extends EngineException
{
	/**
	 * UserAlreadyExistsException constructor.
	 *
	 * @param string $username username
	 * @param string $fullname full name of the user
	 */

	function __construct($username, $fullname = '')
	{
		if ($fullname)
			parent::__construct(USER_LANG_ERRMSG_DUPLICATE_FULL_NAME . " - " . $fullname, COMMON_NOTICE);
		else
			parent::__construct(USER_LANG_ERRMSG_USER_ALREADY_EXISTS . " - " . $username, COMMON_NOTICE);
	}
}

/**
 * Username's password is not valid exception.
 *
 * @package Api
 * @subpackage Exception
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2007, Point Clark Networks
 */

class UserPasswordInvalidException extends EngineException
{
	/**
	 * UserPasswordInvalidException constructor.
	 */

	function __construct()
	{
		parent::__construct(LOCALE_LANG_ERRMSG_PASSWORD_INVALID, COMMON_NOTICE);
	}
}

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * User administration.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2008, Point Clark Networks
 */

class User extends Engine
{
	///////////////////////////////////////////////////////////////////////////////
	// F I E L D S
	///////////////////////////////////////////////////////////////////////////////

	protected $ldaph = null;
	protected $username;
	protected $objectclasses;
	protected $infomap;
	protected $attributemap;
	protected $reserved = array('root', 'manager');

	const LOG_TAG = 'user';
	const DEFAULT_HOMEDIR_PATH = '/home';
	const DEFAULT_HOMEDIR_PERMS = '0755';
	const DEFAULT_LOGIN = '/sbin/nologin';
	const DEFAULT_USER_GROUP = 'allusers';
	const DEFAULT_USER_GROUP_ID = '63000';
	const COMMAND_LDAPPASSWD = '/usr/bin/ldappasswd';
	const COMMAND_SYNCMAILBOX = '/usr/sbin/syncmailboxes';
	const COMMAND_SYNCUSERS = '/usr/sbin/syncusers';
	const CONSTANT_TYPE_SHA = 'sha';
	const CONSTANT_TYPE_SHA1 = 'sha1';
	const CONSTANT_TYPE_LANMAN = 'lanman';
	const CONSTANT_TYPE_NT = 'nt';
	const STATUS_LOCKED = 'locked';
	const STATUS_UNLOCKED = 'unlocked';

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * User constructor.
	 */

	function __construct($username)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		require_once(GlobalGetLanguageTemplate(__FILE__));
		require_once(GlobalGetLanguageTemplate(preg_replace("/User.class.php/", "Organization", __FILE__)));

		parent::__construct();

		$this->username = $username;

		// TODO: move kolab/horde out of core
		$this->coreclasses = array(
			'top',
			'posixAccount',
			'shadowAccount',
			'inetOrgPerson',
			'kolabInetOrgPerson',
			'hordePerson',
			'pcnAccount',
		);

		// The infomap array maps userinfo to LDAP attributes and object classes.
		// In the future, the object class might need to be an array... a simple
		// one-to-one ratio will do for now.  The "core" objects are: 
		//
		// - top
		// - posixAccount
		// - shadowAccount
		// - inetOrgPerson
		// - pcnAccount

		$this->infomap = array(
			'city'	 		=> array( 'type' => 'string',  'required' => false, 'validator' => 'IsValidCity', 'objectclass' => 'core', 'attribute' => 'l' ),
			'country'		=> array( 'type' => 'string',  'required' => false, 'validator' => 'IsValidCountry', 'objectclass' => 'core', 'attribute' => 'c' ),
			'description'	=> array( 'type' => 'string',  'required' => false, 'validator' => 'IsValidDescription', 'objectclass' => 'core', 'attribute' => 'description' ),
			'displayName'	=> array( 'type' => 'string',  'required' => false, 'validator' => 'IsValidDisplayName', 'objectclass' => 'core', 'attribute' => 'displayName' ),
			'fax'			=> array( 'type' => 'string',  'required' => false, 'validator' => 'IsValidFaxNumber', 'objectclass' => 'core', 'attribute' => 'facsimileTelephoneNumber' ),
			'firstName'		=> array( 'type' => 'string',  'required' => false, 'validator' => 'IsValidFirstName', 'objectclass' => 'core', 'attribute' => 'givenName' ),
			'gidNumber'		=> array( 'type' => 'integer', 'required' => false, 'validator' => 'IsValidGidNumber', 'objectclass' => 'core', 'attribute' => 'gidNumber' ),
			'homeDirectory'	=> array( 'type' => 'string',  'required' => false, 'validator' => 'IsValidHomeDirectory', 'objectclass' => 'core', 'attribute' => 'homeDirectory' ),
			'loginShell'	=> array( 'type' => 'string',  'required' => false, 'validator' => 'IsValidLoginShell', 'objectclass' => 'core', 'attribute' => 'loginShell' ),
			'mail'			=> array( 'type' => 'string',  'required' => false, 'validator' => 'IsValidMail', 'objectclass' => 'core', 'attribute' => 'mail' ),
			'organization'	=> array( 'type' => 'string',  'required' => false, 'validator' => 'IsValidOrganization', 'objectclass' => 'core', 'attribute' => 'o' ),
			'password'		=> array( 'type' => 'string',  'required' => false, 'validator' => 'IsValidPassword', 'objectclass' => 'core', 'attribute' => 'userPassword', 'locale' => LOCALE_LANG_PASSWORD ),
			'postalCode'	=> array( 'type' => 'string',  'required' => false, 'validator' => 'IsValidPostalCode', 'objectclass' => 'core', 'attribute' => 'postalCode' ),
			'postOfficeBox'	=> array( 'type' => 'string',  'required' => false, 'validator' => 'IsValidPostOfficeBox', 'objectclass' => 'core', 'attribute' => 'postOfficeBox' ),
			'region'	 	=> array( 'type' => 'string',  'required' => false, 'validator' => 'IsValidRegion', 'objectclass' => 'core', 'attribute' => 'st' ),
			'roomNumber'	=> array( 'type' => 'string',  'required' => false, 'validator' => 'IsValidRoomNumber', 'objectclass' => 'core', 'attribute' => 'roomNumber' ),
			'street'		=> array( 'type' => 'string',  'required' => false, 'validator' => 'IsValidStreet', 'objectclass' => 'core', 'attribute' => 'street' ),
			'lastName'		=> array( 'type' => 'string',  'required' => true,  'validator' => 'IsValidLastname', 'objectclass' => 'core', 'attribute' => 'sn', 'locale' => USER_LANG_LAST_NAME ),
			'telephone'		=> array( 'type' => 'string',  'required' => false, 'validator' => 'IsValidTelephoneNumber', 'objectclass' => 'core', 'attribute' => 'telephoneNumber' ),
			'title'			=> array( 'type' => 'string',  'required' => false, 'validator' => 'IsValidTitle', 'objectclass' => 'core', 'attribute' => 'title' ),
			'uidNumber'		=> array( 'type' => 'integer', 'required' => false, 'validator' => 'IsValidUidNumber', 'objectclass' => 'core', 'attribute' => 'uidNumber' ),
			'unit'			=> array( 'type' => 'string',  'required' => false, 'validator' => 'IsValidOrganizationUnit', 'objectclass' => 'core', 'attribute' => 'ou' ),
			'mailquota'		=> array( 'type' => 'string',  'required' => false, 'validator' => 'IsValidMailQuota', 'objectclass' => 'kolabInetOrgPerson', 'attribute' => 'cyrus-userquota' ),
			'aliases'		=> array( 'type' => 'stringarray',  'required' => false, 'validator' => 'IsValidAlias', 'objectclass' => 'pcnMailAccount', 'attribute' => 'pcnMailAliases' ),
			'forwarders'	=> array( 'type' => 'stringarray',  'required' => false, 'validator' => 'IsValidForwarder', 'objectclass' => 'pcnMailAccount', 'attribute' => 'pcnMailForwarders' ),
			'deleteMailbox'	=> array( 'type' => 'string',  'required' => false, 'objectclass' => 'kolabInetOrgPerson', 'attribute' => 'kolabDeleteflag' ),
			'pbxState'		=> array( 'type' => 'integer', 'required' => false, 'validator' => 'IsValidRoomNumber', 'objectclass' => 'pcnPbxAccount', 'attribute' => 'pcnPbxState' ),
			'ftpFlag'		=> array( 'type' => 'boolean', 'required' => false, 'validator' => 'IsValidFlag', 'objectclass' => 'pcnFTPAccount', 'attribute' => 'pcnFTPFlag' , 'passwordfield' => 'pcnFTPPassword', 'passwordtype' => self::CONSTANT_TYPE_SHA ),
			'mailFlag'		=> array( 'type' => 'boolean', 'required' => false, 'validator' => 'IsValidFlag', 'objectclass' => 'pcnMailAccount', 'attribute' => 'pcnMailFlag' , 'passwordfield' => 'pcnMailPassword', 'passwordtype' => self::CONSTANT_TYPE_SHA ),
			'googleAppsFlag'	=> array( 'type' => 'boolean', 'required' => false, 'validator' => 'IsValidFlag', 'objectclass' => 'pcnGoogleAppsAccount', 'attribute' => 'pcnGoogleAppsFlag' , 'passwordfield' => 'pcnGoogleAppsPassword', 'passwordtype' => self::CONSTANT_TYPE_SHA1 ),
			'openvpnFlag'	=> array( 'type' => 'boolean', 'required' => false, 'validator' => 'IsValidFlag', 'objectclass' => 'pcnOpenVPNAccount', 'attribute' => 'pcnOpenVPNFlag' , 'passwordfield' => 'pcnOpenVPNPassword', 'passwordtype' => self::CONSTANT_TYPE_SHA ),
			'pptpFlag'		=> array( 'type' => 'boolean', 'required' => false, 'validator' => 'IsValidFlag', 'objectclass' => 'pcnPPTPAccount', 'attribute' => 'pcnPPTPFlag' , 'passwordfield' => 'pcnPPTPPassword', 'passwordtype' => self::CONSTANT_TYPE_NT ),
			'proxyFlag'		=> array( 'type' => 'boolean', 'required' => false, 'validator' => 'IsValidFlag', 'objectclass' => 'pcnProxyAccount', 'attribute' => 'pcnProxyFlag' , 'passwordfield' => 'pcnProxyPassword', 'passwordtype' => self::CONSTANT_TYPE_SHA ),
			'webconfigFlag'	=> array( 'type' => 'boolean', 'required' => false, 'validator' => 'IsValidFlag', 'objectclass' => 'pcnWebconfigAccount', 'attribute' => 'pcnWebconfigFlag' , 'passwordfield' => 'pcnWebconfigPassword', 'passwordtype' => self::CONSTANT_TYPE_SHA ),
			'webFlag'		=> array( 'type' => 'boolean', 'required' => false, 'validator' => 'IsValidFlag', 'objectclass' => 'pcnWebAccount', 'attribute' => 'pcnWebFlag' , 'passwordfield' => 'pcnWebPassword', 'passwordtype' => self::CONSTANT_TYPE_SHA ),
		);

		// The attributemap contains the reverse mapping of the above infomap.

		$this->attributemap = array();
	
		foreach ($this->infomap as $info => $details)
			$this->attributemap[$details['attribute']] = array( 'objectclass' => $details['objectclass'], 'info' => $info );
	}

	/**
	 * Adds a user to the system.
	 *
	 * @param array $userinfo user information
	 * @return void
	 * @throws ValidationException, EngineException
	 */

	function Add($userinfo)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if ($this->ldaph == null)
			$this->_GetLdapHandle();

		// Validate userinfo
		//------------------

		$isvalid = true;

		if (! $this->IsValidUsername($this->username))
			$isvalid = false;

		if (! $this->_ValidateUserinfo($userinfo, false))
			$isvalid = false;

		$cleardirectory = new ClearDirectory();
		$isunique = $cleardirectory->IsUniqueId($this->username);

		if ($isunique != ClearDirectory::STATUS_UNIQUE) {
			$this->AddValidationError($cleardirectory->statuscodes[$isunique], __METHOD__, __LINE__);
			$isvalid = false;
		}

		try {
			// TODO: this will fail in master/replica mode
			if (file_exists(COMMON_CORE_DIR . "/api/Flexshare.class.php")) {
				require_once(COMMON_CORE_DIR . "/api/Flexshare.class.php");
				$flexshare = new Flexshare();
				$flexname = $flexshare->GetShare($this->username);
				// Flexshare name exists if we don't throw FlexshareNotFoundException
				$isvalid = false;
				$this->AddValidationError(USER_LANG_ERRMSG_FLEXSHARE_WITH_THIS_NAME_EXISTS, __METHOD__, __LINE__);
			}
		} catch (FlexshareNotFoundException $e) {
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		if (! $isvalid)
			throw new ValidationException(LOCALE_LANG_INVALID);

		// Convert userinfo into LDAP attributes
		//--------------------------------------

		$ldap_object = $this->_ConvertArrayToLdap($userinfo, false);

		// Check for existing user ID and existing DN
		//-------------------------------------------

		$dn = 'cn=' . Ldap::DnEscape($ldap_object['cn']) . ',' .ClearDirectory::GetUsersOu();
		$uidfordn = $this->ldaph->UidForDn($dn);

		if ($uidfordn)
			throw new UserAlreadyExistsException($this->username, $ldap_object['cn']);

		if ($this->Exists())
			throw new UserAlreadyExistsException($this->username);

		// Add the LDAP user object
		//-------------------------

		try {
			// TODO: PBX plugin
			if (isset($ldap_object['pcnPbxState']) && file_exists(COMMON_CORE_DIR . "/iplex/Users.class.php")) {
				require_once(COMMON_CORE_DIR . "/iplex/Users.class.php");

				$iplex_user = new IPlexUser();
				// if user data already exists in PBX module, delete it and readd
				if ($iplex_user->Exists())
					$iplex_user->DeleteIPlexPBXUser($this->username);

				if ($iplex_user->CCAddUser($userinfo, $this->username) === 0) {
					// CCAddUser failed to add PBX user, clear pbx settings so they aren't saved
					unset($ldap_object['pcnPbxExtension']);
					$ldap_object['pcnPbxState'] = 0;
					$ldap_object['pcnPbxPresenceState'] = 0;
				}
			}

			// Add to LDAP
			$this->ldaph->Add($dn, $ldap_object);

			// Initialize default group memberships
			$groupmanager = new GroupManager();
			$groupmanager->InitalizeGroupMemberships($this->username);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		$this->_Synchronize(true);
	}

	/**
	 * Adds mail alias.
	 *
	 * @param string $alias mail alias
	 * @return void
	 * @throws ValidationException, EngineException
	 */

	function AddAlias($alias)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!$this->IsValidAlias($alias))
			throw new EngineException(LOCALE_LANG_INVALID . " - " . USER_LANG_MAIL_ALIAS, COMMON_INFO);

		$cleardirectory = new ClearDirectory();
		$isunique = $cleardirectory->IsUniqueId($alias);

		if ($isunique != ClearDirectory::STATUS_UNIQUE)
			throw new EngineException($cleardirectory->statuscodes[$isunique], COMMON_INFO);

		if ($this->ldaph == null)
			$this->_GetLdapHandle();

		$oldattributes = $this->_GetUserInfo();

		// Add brand new alias
		if (! isset($oldattributes['pcnMailAliases'])) {
			$ldap_object['pcnMailAliases'] = array();
		// Append to already existing aliases
		} else if (! in_array($alias, $oldattributes['pcnMailAliases'])) {
			array_shift($oldattributes['pcnMailAliases']);
			$ldap_object['pcnMailAliases'] = $oldattributes['pcnMailAliases'];
		// Already exists
		} else if (in_array($alias, $oldattributes['pcnMailAliases'])) {
			throw new EngineException(USER_LANG_MAIL_ALIAS . " - " . LOCALE_LANG_ALREADY_EXISTS, COMMON_INFO);
			return;
		}

		array_push($ldap_object['pcnMailAliases'], $alias);
			
		$this->ldaph->Modify($oldattributes['dn'], $ldap_object);
	}

	/**
	 * Checks the password for the user.
	 *
	 * @param string $password password for the user
	 * @param string $attribute LDAP attribute
	 * @return boolean true if password is correct
	 * @throws EngineException
	 */

	function CheckPassword($password, $attribute)
	{
		if (COMMON_DEBUG_MODE)
			parent::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		sleep(2); // a small delay

		if ($attribute == 'pcnWebconfigPassword') {
			$shapassword = '{sha}' . $this->_CalculateShaPassword($password);
		} else {
			return false;
		}

		if ($this->ldaph == null)
			$this->_GetLdapHandle();

		$attrs = $this->_GetUserInfo();

		if (isset($attrs[$attribute][0]) && ($shapassword == $attrs[$attribute][0]))
			return true;
		else
			return false;
	}

	/**
	 * Deletes a user from the system.
	 *
	 * @return void
	 */

	function Delete()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if ($this->ldaph == null)
			$this->_GetLdapHandle();

		try {
			// Delete the user from all the groups
			$groupmanager = new GroupManager();
			$groupmanager->DeleteGroupMemberships($this->username);

			// Delete the IPlex PBX user
			if (file_exists(COMMON_CORE_DIR . "/iplex/Users.class.php")) {
				require_once(COMMON_CORE_DIR . "/iplex/Users.class.php");
				$iplex_user = new IPlexUser();
				if($iplex_user->Exists($this->username))
					$iplex_user->DeleteIPlexPBXUser($this->username);
			}

			// Delete the user
			$dn = $this->ldaph->GetDnForUid($this->username);

			$ldap_object = array();
			// TODO: only set this if mailbox exists
			$ldap_object['kolabDeleteflag'] = $this->ldaph->GetDefaultHomeServer();

			// Write random garbage into passwd field to lock the user out
			// TODO: disable all flags
			$ldap_object['userPassword'] = '{sha}' . base64_encode(pack('H*', sha1(mt_rand())));

			$this->ldaph->Modify($dn, $ldap_object);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		$this->_Synchronize(false);
	}

	/**
	 * Deletes mail alias.
	 *
	 * @param string $alias mail alias
	 * @return void
	 * @throws ValidationException, EngineException
	 */

	function DeleteAlias($alias)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!$this->IsValidAlias($alias))
			throw new EngineException(LOCALE_LANG_INVALID . " - " . USER_LANG_MAIL_ALIAS, COMMON_INFO);

		if ($this->ldaph == null)
			$this->_GetLdapHandle();

		$oldattributes = $this->_GetUserInfo();

		array_shift($oldattributes['pcnMailAliases']);
		
		$ldap_object['pcnMailAliases'] = array();

		foreach ($oldattributes['pcnMailAliases'] as $oldalias) {
			if ($oldalias != $alias)
				array_push($ldap_object['pcnMailAliases'], $oldalias);
		}

		$this->ldaph->Modify($oldattributes['dn'], $ldap_object);
	}

	/**
	 * Updates a user on the system.
	 *
	 * @param array $userinfo user information
	 * @param array $acl access control list
	 * @return void
	 * @throws ValidationException, EngineException
	 */

	function Update($userinfo, $acl = null)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if ($this->ldaph == null)
			$this->_GetLdapHandle();

		// Validate
		//---------

		if (isset($acl)) {
			foreach ($userinfo as $key => $value) {
				if (! in_array($key, $acl))
					throw new EngineException(USER_LANG_ERRMSG_ACCESS_CONTROL_VIOLATION, COMMON_WARNING);
			}
		}

		// User does not exist error
		//--------------------------

		$attrs = $this->_GetUserInfo();

		if (!isset($attrs['uid'][0]))
			throw new UserNotFoundException($this->username);

		// Input validation errors
		//------------------------

		if (! $this->_ValidateUserinfo($userinfo, true))
			throw new ValidationException(LOCALE_LANG_INVALID);

		// Convert user info to LDAP object
		//---------------------------------

		$ldap_object = $this->_ConvertArrayToLdap($userinfo, true);

		// TODO: Update PBX user via plugin
		//---------------------------------

		if (file_exists(COMMON_CORE_DIR . "/iplex/Users.class.php")) {
			require_once(COMMON_CORE_DIR . "/iplex/Users.class.php");

			try {
				$iplex_user = new IPlexUser();

				if (array_key_exists('pbxFlag', $userinfo)) {
					// Delete PBX user
					if (($userinfo['pbxFlag'] != 1) && $iplex_user->Exists($this->username)) {
						$iplex_user->DeleteIPlexPBXUser($this->username);
					// Update PBX user
					} else if (($userinfo['pbxFlag'] == 1) && $iplex_user->Exists($this->username)) {
						$iplex_user->UpdateIPlexPBXUser($userinfo, $this->username);
					// Add PBX user
					} else if ($userinfo['pbxFlag'] == 1) {
						if ($iplex_user->CCAddUser($userinfo, $this->username) == 0) {
							// CCAddUser failed to add PBX user, clear pbx settings so they aren't saved
							unset($ldap_object['pcnPbxExtension']);
							unset($ldap_object['pcnPbxState']);
							unset($ldap_object['pcnPbxPresenceState']);
						}
					}
				}
			} catch (Exception $e) {
				throw new EngineException($e->GetMessage(), COMMON_WARNING);
			}
		}

		// Handle LDAP
		//------------

		$new_dn = "cn=" . Ldap::DnEscape($ldap_object['cn']) . "," . ClearDirectory::GetUsersOu();

		if ($new_dn != $attrs['dn']) {
		    $rdn = "cn=" . Ldap::DnEscape($ldap_object['cn']);
		    $this->ldaph->Rename($attrs['dn'], $rdn, ClearDirectory::GetUsersOu());
		}

		$this->ldaph->Modify($new_dn, $ldap_object);

		$this->_Synchronize(false);
	}

	/**
	 * Checks if given user exists.
	 *
	 * @return boolean true if user exists
	 * @throws EngineException
	 */

	function Exists()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$attrs = $this->_GetUserInfo();

		if (isset($attrs['uid'][0]))
			return true;
		else
			return false;
	}

	/**
	 * Retrieves information for user from LDAP.
	 *
	 * @throws EngineException
	 * @return array user details
	 */

	function GetInfo()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$attrs = $this->_GetUserInfo();

		if (!isset($attrs['uid'][0]))
			throw new UserNotFoundException($this->username);

		return $this->_ConvertLdapToArray($attrs);
	}

	/**
	 * Reset the passwords for the user.
	 *
	 * Similar to SetPassword, but it uses administrative privileges.  This is
	 * typically used for resetting a password while bypassing password
	 * policies.  For example, an administrator may need to set a password
	 * even when the password policy dictates that the password is not allowed
	 * to change (minimum password age).
	 *
	 * @param string $password password
	 * @param string $verify password verify
	 * @param string $requested_by username requesting the password change
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	function ResetPassword($password, $verify, $requested_by)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		// Validate
		//---------

		if (! $this->IsValidUsername($requested_by, true))
			throw new ValidationException(LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . LOCALE_LANG_USERNAME);

		if (! $this->IsValidPasswordAndVerify($password, $verify)) {
			$errors = $this->GetValidationErrors();
			throw new ValidationException($errors[0]);
		}

		// Set passwords in LDAP
		//----------------------

		$this->_SetPassword($password);

		Logger::Syslog(self::LOG_TAG, "password reset for user - " . $this->username . " / by - " . $requested_by);
	}

	/**
	 * Sets the password for the user.
	 *
	 * Ignore the includesamba flag,  It is a workaround required for password
	 * changes using the change password tool from Windows desktops.
	 *
	 * @param string $oldpassword old password
	 * @param string $password password
	 * @param string $verify password verify
	 * @param string $requested_by username requesting the password change
	 * @param boolean $includesamba workaround for Samba password changes
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	function SetPassword($oldpassword, $password, $verify, $requested_by, $includesamba = true)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		// TODO: something odd is going on when password histories are enabled.
		// The following block of code will fail if the sleep(1) is omitted.
		//
		//	$password = "password';
		//	$user = new User("test1");
		//	$userinfo['telephone'] = '867-5309';
		//	$user->Update($userinfo);
		//	$user->SetPassword($password, $password, "testscript");

		// Validate
		//---------

		if (! $this->IsValidUsername($requested_by, true))
			throw new ValidationException(LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . LOCALE_LANG_USERNAME);

		if (! $this->IsValidPasswordAndVerify($password, $verify)) {
			$errors = $this->GetValidationErrors();
			throw new ValidationException($errors[0]);
		}

		// Sanity check the password using the ldappasswd command
		//-------------------------------------------------------

		if ($this->ldaph == null)
			$this->_GetLdapHandle();

		try {
			$dn = $this->ldaph->GetDnForUid($this->username);

			sleep(2); // see comment above

			$shell = new ShellExec();
			$intval = $shell->Execute(User::COMMAND_LDAPPASSWD, 
				'-x ' .
				'-D "' . $dn . '" ' .
				'-w "' . $oldpassword . '" ' .
				'-s "' . $password . '" ' .
				'"' . $dn . '"', 
				false);
		
			if ($intval != 0)
				$output = $shell->GetOutput();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		if (! empty($output)) {
			// Dirty.  Try to catch common error strings so that we can translate.
			$errormessage = isset($output[1]) ? $output[1] : $output[0]; // Default if our matching fails

			foreach ($output as $line) {
				if (preg_match("/Invalid credentials/", $line))
					$errormessage = USER_LANG_OLD_PASSWORD_INVALID;
				else if (preg_match("/Password is in history of old passwords/", $line))
					$errormessage = USER_LANG_PASSWORD_IN_HISTORY;
				else if (preg_match("/Password is not being changed from existing value/", $line))
					$errormessage = USER_LANG_PASSWORD_NOT_CHANGED;
				else if (preg_match("/Password fails quality checking policy/", $line))
					$errormessage = USER_LANG_PASSWORD_VIOLATES_QUALITY_CHECK;
				else if (preg_match("/Password is too young to change/", $line))
					$errormessage = USER_LANG_PASSWORD_TOO_YOUNG;
			}

			throw new ValidationException($errormessage);
		}

		// Set passwords in LDAP
		//----------------------

		$this->_SetPassword($password, $includesamba);

		Logger::Syslog(self::LOG_TAG, "password updated for user - " . $this->username . " / by - " . $requested_by);
	}

	/**
	 * Unlocks a user account.
	 *
	 * @return void
	 * @throws EngineException
	 */

	function Unlock()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		// This only applies to Samba right now
		if (file_exists(COMMON_CORE_DIR . "/api/Samba.class.php")) {
			require_once("Samba.class.php");

			$samba = new Samba();
			$samba->UnlockAccount($this->username);
		}
	}

	///////////////////////////////////////////////////////////////////////////////
	// V A L I D A T I O N   M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Validation routine for mail aliases.
	 *
	 * @param string $alias alias
	 * @return boolean true if alias is valid
	 */

	function IsValidAlias($alias)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (empty($alias) || preg_match("/^([a-z0-9_\-\.\$]+)$/", $alias)) {
			return true;
		} else {
			$this->AddValidationError(
				LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . USER_LANG_MAIL_ALIAS, __METHOD__, __LINE__
			);
			return false;
		}
	}

	/**
	 * Validation routine for city.
	 *
	 * @param string $city city
	 * @return boolean true if city is valid
	 */

	function IsValidCity($city)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!preg_match("/([:;\/#!@])/", $city)) {
			return true;
		} else {
			$this->AddValidationError(
				LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . ORGANIZATION_LANG_CITY, __METHOD__, __LINE__
			);
			return false;
		}
	}

	/**
	 * Validation routine for country.
	 *
	 * @param string $country country
	 * @return boolean true if country is valid
	 */

	function IsValidCountry($country)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if ($country == "")
			return true;

		try {
			$countryobj = new Country();
			$countrylist = $countryobj->GetList();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		if (array_key_exists($country, $countrylist)) {
			return true;
		} else {
			$this->AddValidationError(
				LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . ORGANIZATION_LANG_COUNTRY, __METHOD__, __LINE__
			);
			return false;
		}
	}

	/**
	 * Validation routine for description.
	 *
	 * @param string $description description
	 * @return boolean true if description is valid
	 */

	function IsValidDescription($description)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!preg_match("/([:;\/#!@])/", $description)) {
			return true;
		} else {
			$this->AddValidationError(
				LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . LOCALE_LANG_DESCRIPTION, __METHOD__, __LINE__
			);
			return false;
		}
	}

	/**
	 * Validation routine for display name.
	 *
	 * @param string $displayname display name
	 * @return boolean true if display name is valid
	 */

	function IsValidDisplayName($displayname)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!preg_match("/([:;\/#!@])/", $displayname)) {
			return true;
		} else {
			$this->AddValidationError(
				LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . USER_LANG_DISPLAY_NAME, __METHOD__, __LINE__
			);
			return false;
		}
	}

	/**
	 * Validation routine for fax number.
	 *
	 * @param string $number fax number
	 * @return boolean true if fax number is valid
	 */

	function IsValidFaxNumber($number)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!preg_match("/([:;\/#!@])/", $number)) {
			return true;
		} else {
			$this->AddValidationError(
				LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . ORGANIZATION_LANG_FAX, __METHOD__, __LINE__
			);
			return false;
		}
	}

	/**
	 * Validation routine for first name.
	 *
	 * @param string $name first name
	 * @return boolean true if first name is valid
	 */

	function IsValidFirstName($name)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!preg_match("/([:;\/#!@])/", $name)) {
			return true;
		} else {
			$this->AddValidationError(
				LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . USER_LANG_FIRST_NAME, __METHOD__, __LINE__
			);
			return false;
		}
	}

	/**
	 * Validation routine for flags
	 *
	 * @param boolean $flag flag
	 * @return boolean true if flag is a boolean
	 */

	function IsValidFlag($flag)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);
	
		return true;
	}

	/**
	 * Validation routine for mail forwarders
	 *
	 * @param string $forwarder forwarder
	 * @return boolean true if forwarder is valid
	 */

	function IsValidForwarder($forwarder)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (preg_match("/^([a-z0-9_\-\.\$]+)@/", $forwarder)) {
			return true;
		} else {
			$this->AddValidationError(
				LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . USER_LANG_MAIL_FORWARDER, __METHOD__, __LINE__
			);
			return false;
		}
	}

	/**
	 * Validation routine for full name.
	 *
	 * @param string $name full name
	 * @return boolean true if full name is valid
	 */

	function IsValidFullName($name)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!preg_match("/([:;\/#!@])/", $name)) {
			return true;
		} else {
			$this->AddValidationError(
				LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . USER_LANG_FULLNAME, __METHOD__, __LINE__
			);
			return false;
		}
	}

	/**
	 * Validation routine for GID number.
	 *
	 * @param int $gidnumber GID number
	 * @return boolean true if GID number is valid
	 */

	function IsValidGidNumber($gidnumber)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (preg_match("/^\d+/", $gidnumber)) {
			return true;
		} else {
			$this->AddValidationError(
				LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . USER_LANG_GIDNUMBER, __METHOD__, __LINE__
			);
			return false;
		}
	}

	/**
	 * Validation routine for home directory
	 *
	 * @param string $homedir home directory
	 * @return boolean true if home directory is valid
	 */

	function IsValidHomeDirectory($homedir)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!preg_match("/([:;#!@])/", $homedir)) {
			return true;
		} else {
			$this->AddValidationError(
				LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . USER_LANG_HOMEDIR, __METHOD__, __LINE__
			);
			return false;
		}
	}

	/**
	 * Validation routine for last name.
	 *
	 * @param string $name last name
	 * @return boolean true if last name is valid
	 */

	function IsValidLastName($name)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!preg_match("/([:;\/#!@])/", $name)) {
			return true;
		} else {
			$this->AddValidationError(
				LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . USER_LANG_LAST_NAME, __METHOD__, __LINE__
			);
			return false;
		}
	}

	/**
	 * Validation routine for login shell.
	 *
	 * @param string $shell login shell
	 * @return boolean true if login shell is valid
	 */

	function IsValidLoginShell($loginshell)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$shell = new Shell();
			$allshells = $shell->GetList();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		if (in_array($loginshell, $allshells)) {
			return true;
		} else {
			$this->AddValidationError(
				LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . USER_LANG_SHELL, __METHOD__, __LINE__
			);
			return false;
		}
	}

	/**
	 * Validation routine for mail address.
	 *
	 * @param string $mail mail address
	 * @return boolean true if mail address is valid
	 */

	function IsValidMail($address)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		// TODO: new regex
		if (preg_match("/^([a-z0-9_\-\.\$]+)@/", $address)) {
			return true;
		} else {
			$this->AddValidationError(
				LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . USER_LANG_MAIL_ADDRESS, __METHOD__, __LINE__
			);
			return false;
		}
	}

	/**
	 * Validation routine for quota.
	 *
	 * @param int $quota quota
	 * @return boolean true if quota is valid
	 */

	function IsValidMailQuota($quota)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if ((! $quota) || preg_match("/\d+/", $quota)) {
			return true;
		} else {
			$this->AddValidationError(
				LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . USER_LANG_QUOTA, __METHOD__, __LINE__
			);
			return false;
		}
	}

	/**
	 * Validation routine for organization.
	 *
	 * @param string $organization organization
	 * @return boolean true if organization is valid
	 */

	function IsValidOrganization($organization)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!preg_match("/([:;\/#!@])/", $organization)) {
			return true;
		} else {
			$this->AddValidationError(
				LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . ORGANIZATION_LANG_ORGANIZATION, __METHOD__, __LINE__
			);
			return false;
		}
	}

	/**
	 * Validation routine for organization unit.
	 *
	 * @param string $unit organization unit
	 * @return boolean true if organization unit is valid
	 */

	function IsValidOrganizationUnit($unit)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!preg_match("/([:;\/#!@])/", $unit)) {
			return true;
		} else {
			$this->AddValidationError(
				LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . ORGANIZATION_LANG_ORGANIZATION_UNIT, __METHOD__, __LINE__
			);
			return false;
		}
	}

	/**
	 * Password validation routine.
	 *
	 * @param string $password password
	 * @return boolean true if password is valid
	 */

	function IsValidPassword($password)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (preg_match("/[\|;\*]/", $password) || !preg_match("/^[a-zA-Z0-9]/", $password)) {
			$this->AddValidationError(LOCALE_LANG_ERRMSG_PASSWORD_INVALID, __METHOD__, __LINE__);
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Password/verify validation routine.
	 *
	 * @param string $password password
	 * @param string $verify verify
	 * @return boolean true if password and verify are valid and equal
	 */

	function IsValidPasswordAndVerify($password, $verify)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$isvalid = true;

		if (empty($password)) {
			$this->AddValidationError(LOCALE_LANG_ERRMSG_REQUIRED_PARAMETER_IS_MISSING . " - " . LOCALE_LANG_PASSWORD, __METHOD__, __LINE__);
			$isvalid = false;
		}

		if (empty($verify)) {
			$this->AddValidationError(LOCALE_LANG_ERRMSG_REQUIRED_PARAMETER_IS_MISSING . " - " . LOCALE_LANG_VERIFY, __METHOD__, __LINE__);
			$isvalid = false;
		}

		if ($isvalid) {
			if ($password == $verify) {
				$isvalid = $this->IsValidPassword($password);
			} else {
				$this->AddValidationError(LOCALE_LANG_ERRMSG_PASSWORD_MISMATCH, __METHOD__, __LINE__);
				$isvalid = false;
			}
		}

		return $isvalid;
	}

	/**
	 * Validation routine for post office box.
	 *
	 * @param string $pobox post office box
	 * @return boolean true if post office box is valid
	 */

	function IsValidPostOfficeBox($pobox)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!preg_match("/([:;\/#!@])/", $pobox)) {
			return true;
		} else {
			$this->AddValidationError(
				LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . ORGANIZATION_LANG_POST_OFFICE_BOX, __METHOD__, __LINE__
			);
			return false;
		}
	}

	/**
	 * Validation routine for postal code.
	 *
	 * @param string $postalcode postal code
	 * @return boolean true if postal code is valid
	 */

	function IsValidPostalCode($postalcode)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!preg_match("/([:;\/#!@])/", $postalcode)) {
			return true;
		} else {
			$this->AddValidationError(
				LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . ORGANIZATION_LANG_POSTAL_CODE, __METHOD__, __LINE__
			);
			return false;
		}
	}

	/**
	 * Validation routine for room number.
	 *
	 * @param string $room room number
	 * @return boolean true if room number is valid
	 */

	function IsValidRoomNumber($room)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!preg_match("/([:;\/#!@])/", $room)) {
			return true;
		} else {
			$this->AddValidationError(
				LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . ORGANIZATION_LANG_ROOM_NUMBER, __METHOD__, __LINE__
			);
			return false;
		}
	}

	/**
	 * Validation routine for state or province.
	 *
	 * @param string $region region
	 * @return boolean true if region is valid
	 */

	function IsValidRegion($region)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!preg_match("/([:;\/#!@])/", $region)) {
			return true;
		} else {
			$this->AddValidationError(
				LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . ORGANIZATION_LANG_REGION, __METHOD__, __LINE__
			);
			return false;
		}
	}

	/**
	 * Validation routine for street.
	 *
	 * @param string $street street
	 * @return boolean true if street is valid
	 */

	function IsValidStreet($street)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!preg_match("/([:;\/#!@])/", $street)) {
			return true;
		} else {
			$this->AddValidationError(
				LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . ORGANIZATION_LANG_STREET, __METHOD__, __LINE__
			);
			return false;
		}
	}

	/**
	 * Validation routine for phone number extension.
	 *
	 * @param string $extension phone number extension
	 * @return boolean true if phone number extension is valid
	 */

	function IsValidTelephoneExtension($extension)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!preg_match("/([:;\/#!@])/", $extension)) {
			return true;
		} else {
			$this->AddValidationError(
				LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . USER_LANG_EXTENSION, __METHOD__, __LINE__
			);
			return false;
		}
	}

	/**
	 * Validation routine for phone number.
	 *
	 * @param string $number phone number
	 * @return boolean true if phone number is valid
	 */

	function IsValidTelephoneNumber($number)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!preg_match("/([:;\/#!@])/", $number)) {
			return true;
		} else {
			$this->AddValidationError(
				LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . ORGANIZATION_LANG_PHONE, __METHOD__, __LINE__
			);
			return false;
		}
	}

	/**
	 * Validation routine for title.
	 *
	 * @param string $title title
	 * @return boolean true if title is valid
	 */

	function IsValidTitle($title)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!preg_match("/([:;\/#!@])/", $title)) {
			return true;
		} else {
			$this->AddValidationError(
				LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . USER_LANG_TITLE, __METHOD__, __LINE__
			);
			return false;
		}
	}

	/**
	 * Validation routine for UID number.
	 *
	 * @param int $uidnumber UID number
	 * @return boolean true if UID number is valid
	 */

	function IsValidUidNumber($uidnumber)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (preg_match("/^\d+/", $uidnumber)) {
			return true;
		} else {
			$this->AddValidationError(
				LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . USER_LANG_UIDNUMBER, __METHOD__, __LINE__
			);
			return false;
		}
	}

	/**
	 * Validation routine for username.
	 *
	 * @param string $username username
	 * @param boolean $allowreserved do not invalidate reserved usernames
	 * @return boolean true if username is valid
	 */

	function IsValidUsername($username, $allowreserved = false)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (preg_match("/^([a-z0-9_\-\.\$]+)$/", $username)) {
			if (!$allowreserved && in_array($username, $this->reserved)) {
				$this->AddValidationError(USER_LANG_ERRMSG_RESERVED_SYSTEM_USER, __METHOD__, __LINE__);
				return false;
			} else {
				return true;
			}
		} else {
			$this->AddValidationError(
				LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . LOCALE_LANG_USERNAME, __METHOD__, __LINE__
			);
			return false;
		}
	}

	///////////////////////////////////////////////////////////////////////////////
	// P R I V A T E   M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Calculates Lanman password.
	 *
	 * @access private
	 * @return string Lanman password
	 */

	function _CalculateLanmanPassword($password)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$password = substr(strtoupper($password), 0, 14);

		while (strlen($password) < 14)
			 $password .= "\0";

		$deshash = $this->_DesHash(substr($password, 0, 7)) . $this->_DesHash(substr($password, 7, 7));

		return strtoupper(bin2hex($deshash));
	}

	/**
	 * Calculates NT password.
	 *
	 * @access private
	 * @return string NT password
	 */

	function _CalculateNtPassword($password)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		return strtoupper(bin2hex(mhash(MHASH_MD4, self::_StringToUnicode($password))));
	}

	/**
	 * Calculates SHA password.
	 *
	 * @access private
	 * @return string SHA password
	 */

	function _CalculateShaPassword($password)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		return base64_encode(pack('H*', sha1($password)));
	}

	/**
	 * Converts SHA password to SHA1.
	 *
	 * @access private
	 * @return string SHA1 password
	 */

	function _ConvertShaToSha1($shapassword)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		// Strip out prefix if it exists
		$shapassword = preg_replace("/^{sha}/", "", $shapassword);

		$sha1 = unpack("H*", base64_decode($shapassword));

		return $sha1[1];
	}

	/**
	 * Converts LDAP attributes into a userinfo array.
	 *
	 * @access private
	 * @param string $attributes LDAP attributes
	 * @throws EngineException
	 * @return array
	 */

	function _ConvertLdapToArray($attributes)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$userinfo = array();

		foreach ($this->infomap as $infoname => $detail) {
			if (empty($attributes[$detail['attribute']])) {
				if ($detail['type'] == 'boolean')
					$userinfo[$infoname] = false;
				else
					$userinfo[$infoname] = null;
			} else {
				if ($infoname != 'password') {
					if ($detail['type'] == 'boolean') {
						$userinfo[$infoname] = ($attributes[$detail['attribute']][0] == 'TRUE') ? true : false;
					} elseif ($detail['type'] == 'stringarray') {
						array_shift($attributes[$detail['attribute']]);
						$userinfo[$infoname] = $attributes[$detail['attribute']];
					} else {
						$userinfo[$infoname] = $attributes[$detail['attribute']][0];
					}
				}
			}
		}

		// TODO: should uid be put into the infomap?
		// Add uid field
		$userinfo['uid'] =  $attributes['uid'][0];

		// TODO: Handle external userinfo fields via plugin

		if (file_exists(COMMON_CORE_DIR . "/api/Samba.class.php")) {
			require_once("Samba.class.php");

			// The 'D' flag indicates a disabled account
			if (isset($attributes['sambaAcctFlags']) && !preg_match('/D/', $attributes['sambaAcctFlags'][0]))
				$userinfo['sambaFlag'] = true;
			else
				$userinfo['sambaFlag'] = false;

			// The 'L' flag indicates a locaked account
			if (isset($attributes['sambaAcctFlags']) && preg_match('/L/', $attributes['sambaAcctFlags'][0]))
				$userinfo['sambaAccountLocked'] = true;
			else
				$userinfo['sambaAccountLocked'] = false;
		}

		if (file_exists(COMMON_CORE_DIR . "/iplex/Users.class.php")) {
			$userinfo['pbxFlag'] = isset($attributes['pcnPbxState'][0]) ? $attributes['pcnPbxState'][0] : '0';
			$userinfo['pbxPresenceFlag'] = isset($attributes['pcnPbxPresenceState'][0]) ? $attributes['pcnPbxPresenceState'][0] : '0';
			$userinfo['pbxExtension'] = isset($attributes['pcnPbxExtension'][0]) ? $attributes['pcnPbxExtension'][0] : '';
		}

		return $userinfo;
	}

	/**
	 * Converts a userinfo array into an LDAP object.
	 *
	 * @access private
	 * @param array $userinfo user information array
	 * @param boolean $ismodify set to true if using results on LDAP modify
	 * @return array LDAP attribute array
	 * @throws EngineException, ValidationException
	 */

	function _ConvertArrayToLdap($userinfo, $ismodify)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		/**
		 * This method is the meat and potatoes of the User class.  There
		 * are quite a few non-intuitive steps in here, but hopefully the 
		 * documentation will guide the way.
		 */

		$ldap_object = array();
		$oldattributes = $this->_GetUserInfo();

		/**
		 * Step 1 - convert userinfo fields to LDAP fields
		 *
		 * Gotcha: in order to delete an attribute on an update, the LDAP object item
		 * must be set to an empty array.  See http://ca.php.net/ldap_modify for
		 * more information.  However, the empty array on a new user causes
		 * an error.  In this case, leaving the LDAP object item undefined
		 * is the correct behavior.
		 */

		foreach ($userinfo as $info => $value) {
			if (isset($this->infomap[$info]['attribute'])) {
				$attribute = $this->infomap[$info]['attribute'];

				// Delete
				if ($value === NULL) {
					if ($ismodify)
						$ldap_object[$attribute] = array();

				// Add/modify
				} else {
					if ($this->infomap[$info]['type'] == 'boolean') {
						$ldap_object[$attribute] = ($value) ? 'TRUE' : 'FALSE';
					} else {
						$ldap_object[$attribute] = $userinfo[$info];
					}
				}
			}
		}

		/**
		 * Step 2 - handle derived fields
		 *
		 * Some LDAP attributes are derived from other variables, notably:
		 * - uid: this is the username given in the User.__constructor
		 * - cn: this is the "first name + last name"
		 *
		 * For some built-in accounts (e.g. Flexshare) it is more desirable
		 * to explicitly set the 'cn', so we allow it.
		 */

		$ldap_object['uid'] = $this->username;

		if (isset($userinfo['cn'])) {
			$ldap_object['cn'] = $userinfo['cn'];
		} else {
			if (isset($userinfo['firstName']) || isset($userinfo['lastName']))
				$ldap_object['cn'] = $userinfo['firstName'] . ' ' . $userinfo['lastName'];
			else
				$ldap_object['cn'] = $oldattributes['cn'][0];
		}

		/**
		 * Step 3 - handle defaults
		 *
		 * On a new user record, some attributes can be set to defaults.  For
		 * the 'uidNumber' and 'gidNumber', we allow the developer to specify
		 * the values.  For all other cases, defaults are forced to specific
		 * values.
		 */

		if (! $ismodify) {
			// UID and GID numbers
			if (isset($userinfo['gidNumber']))
				$gidinfo['id'] = $userinfo['gidNumber'];
			else
				$gidinfo = $this->_GetDirectoryDefaultGroup();

			$ldap_object['uidNumber'] = isset($userinfo['uidNumber']) ? $userinfo['uidNumber'] : $this->_GetNextUidNumber();
			$ldap_object['gidNumber'] = isset($userinfo['gidNumber']) ? $userinfo['gidNumber'] : $gidinfo['id'];

			// Login shell
			$ldap_object['loginShell'] = User::DEFAULT_LOGIN;
		}

		if (! isset($oldattributes['kolabHomeServer'][0]))
			$ldap_object['kolabHomeServer'] = $this->ldaph->GetDefaultHomeServer();

		if (! isset($oldattributes['kolabInvitationPolicy'][0]))
			$ldap_object['kolabInvitationPolicy'] = "ACT_MANUAL";

		if (! isset($oldattributes['homeDirectory'][0]))
			$ldap_object['homeDirectory'] = "/home/" . $this->username;

		// E-mail address handling - mail address needs to exist if mail services are enabled:
		// - Local ClearOS mail
		// - Google Apps mail
		// - Zarafa mail
		//
		// TODO: handle this in a generic way

		$mailservices = array('pcnMailFlag', 'pcnGoogleAppsFlag', 'pcnZarafaFlag');
		$setmail = false;

		foreach ($mailservices as $service) {
			// if mail flag is set on this update, use it
			if (isset($ldap_object[$service])) {
				if ($ldap_object[$service] == 'TRUE') {
					$setmail = true;
					break;
				}
			// otherwise, check the existing flag in LDAP
			} else if (isset($oldattributes[$service][0]) && ($oldattributes[$service][0] == 'TRUE')) {
				$setmail = true;
				break;
			}
		}

		if ($setmail) {
			$ldap_object['mail'] = $this->username . "@" . $this->ldaph->GetDefaultDomain();
		} else if ($ismodify) {
			$ldap_object['mail'] = array();
		}

		/**
		 * Step 4 - manage all the passwords
		 *
		 * In order to maintain flexibility, every single service (e.g. proxy) 
		 * maintains its own password field.  Right now, we are synchronizing
		 * these passwords for all the services... but it does not have to be
		 * that way.
		 */

		// TODO: move this to SetPassword?
		// if (! $ismodify) {
		if (! empty($userinfo['password'])) {
			$ldap_object['userPassword'] = '{sha}' . $this->_CalculateShaPassword($userinfo['password']);
			$ldap_object['pcnSHAPassword'] = $ldap_object['userPassword'];
			$ldap_object['pcnMicrosoftNTPassword'] = $this->_CalculateNtPassword($userinfo['password']);
			$ldap_object['pcnMicrosoftLanmanPassword'] = $this->_CalculateLanmanPassword($userinfo['password']);

			$pw_sha =  $ldap_object['pcnSHAPassword'];
			$pw_nt = $ldap_object['pcnMicrosoftNTPassword'];
			$pw_lanman = $ldap_object['pcnMicrosoftLanmanPassword'];
		} else {
			$ldap_object['userPassword'] = $oldattributes['userPassword'][0];
			$pw_sha = $oldattributes['pcnSHAPassword'][0];
			$pw_nt = $oldattributes['pcnMicrosoftNTPassword'][0];
			$pw_lanman = $oldattributes['pcnMicrosoftLanmanPassword'][0];
		}

		foreach ($this->infomap as $key => $value) {
			if (isset($this->infomap[$key]['passwordtype'])) {
				// if 
				//   new, set only the passwords needed by $userinfo, or
				//	 modify, set only the passwords needed by $userinfo
				//	 modify, set only the passwords currently in LDAP, or
				if (
					(! $ismodify) && isset($userinfo[$key]) || 
					($ismodify && isset($userinfo[$key])) ||
					($ismodify && isset($oldattributes[$this->infomap[$key]['passwordfield']]))
					) {

					if ($this->infomap[$key]['passwordtype'] == self::CONSTANT_TYPE_SHA)
						$ldap_object[$this->infomap[$key]['passwordfield']] = $pw_sha;
					elseif ($this->infomap[$key]['passwordtype'] == self::CONSTANT_TYPE_SHA1)
						$ldap_object[$this->infomap[$key]['passwordfield']] = $this->_ConvertShaToSha1($pw_sha);
					elseif ($this->infomap[$key]['passwordtype'] == self::CONSTANT_TYPE_LANMAN)
						$ldap_object[$this->infomap[$key]['passwordfield']] = $pw_lanman;
					elseif ($this->infomap[$key]['passwordtype'] == self::CONSTANT_TYPE_NT)
						$ldap_object[$this->infomap[$key]['passwordfield']] = $pw_nt;
				}
			}
		}

		/**
		 * Step 5 - determine which object classes are necessary
		 *
		 * To keep things tidy, we only add the object classes that we need.
		 */

		$classes = array();

		foreach ($oldattributes as $attribute => $detail) {
			// If attribute has not been erased
			// and attribute is in the attribute map
			// and attribute is not part of the core attributes
			if (
				(!(isset($ldap_object[$attribute]) && ($ldap_object[$attribute] == array()))) &&
				isset($this->attributemap[$attribute]) &&
				isset($this->attributemap[$attribute]['objectclass']) &&
				($this->attributemap[$attribute]['objectclass'] != 'core')
				) {
				$classes[] = $this->attributemap[$attribute]['objectclass'];
			}
		}

		foreach ($userinfo as $info => $detail) {
			if (isset($this->infomap[$info]['objectclass']) && ($this->infomap[$info]['objectclass'] != 'core'))
				$classes[] = $this->infomap[$info]['objectclass'];
		}

		// PHPism.  Merged arrays have gaps in the keys of the array;
		// LDAP does not like this, so we need to rekey:
		$merged = array_merge($this->coreclasses, $classes);
		$merged = array_unique($merged);

		foreach ($merged as $class)
			$ldap_object['objectClass'][] = $class;

		/**
		 * Step 6 - handle external userinfo fields.
		 *
		 * Samba and other user extensions.
		 * TODO: create a plugin architecture in 6.0, lots of temporary hardcoding and hacks in here!
		 */

		if (file_exists(COMMON_CORE_DIR . "/api/PasswordPolicy.class.php")) {
			require_once("PasswordPolicy.class.php");
			$policy = new PasswordPolicy();
			$policy->Initialize();
			$ldap_object['pwdPolicySubentry'] = "cn=" . LdapPasswordPolicy::DEFAULT_DIRECTORY_OBJECT . "," . ClearDirectory::GetPasswordPoliciesOu();
		}

		if (file_exists(COMMON_CORE_DIR . "/api/Samba.class.php")) {
			if (isset($userinfo['sambaFlag'])) {
				require_once("Samba.class.php");

				try {
					$samba = new Samba();
					$initialized = $samba->IsDirectoryInitialized();
				} catch (Exception $e) {
					throw new EngineException($e->GetMessage(), COMMON_WARNING);
				}

				$samba_enabled = (isset($userinfo['sambaFlag']) && $userinfo['sambaFlag'] && $initialized) ? true : false;
				$oldclasses = isset($oldattributes['objectClass']) ? $oldattributes['objectClass'] : array();

				// Only change Samba attributes if enabled, or they already exist
				if ($samba_enabled || in_array("sambaSamAccount", $oldclasses)) {
					// TODO: cleanup this logic
					$samba_uid = isset($ldap_object['uidNumber']) ? $ldap_object['uidNumber'] : "";

					if (empty($samba_uid))
						$samba_uid = isset($oldattributes['uidNumber'][0]) ? $oldattributes['uidNumber'][0] : "";

					$samba_ntpassword = isset($ldap_object['pcnMicrosoftNTPassword']) ? $ldap_object['pcnMicrosoftNTPassword'] : "";

					if (empty($samba_ntpassword))
						$samba_ntpassword = isset($oldattributes['pcnMicrosoftNTPassword'][0]) ? $oldattributes['pcnMicrosoftNTPassword'][0] : "";


					$samba_lmpassword = isset($ldap_object['pcnMicrosoftLanmanPassword']) ? $ldap_object['pcnMicrosoftLanmanPassword'] : "";
					if (empty($samba_lmpassword))
						$samba_lmpassword = isset($oldattributes['pcnMicrosoftLanmanPassword'][0]) ? $oldattributes['pcnMicrosoftLanmanPassword'][0] : "";

					try {
						$samba = new Samba();
						$samba_object = $samba->AddLdapUserAttributes(
							$this->username,
							$samba_enabled,
							$samba_uid,
							$samba_ntpassword,
							$samba_lmpassword
						);

						$ldap_object = array_merge($ldap_object, $samba_object);
						$ldap_object['objectClass'][] = 'sambaSamAccount';
					} catch (Exception $e) {
						throw new EngineException($e->GetMessage(), COMMON_WARNING);
					}
				}
			// TODO: when updating non-Samba info, this is necessary.  This whole
			// block of code and the Samba hooks need to be redone!
			} else {
				if (isset($oldattributes['sambaAcctFlags']))
					$ldap_object['objectClass'][] = 'sambaSamAccount';
			}
		}

		// tODO: last minute 5.0 addition. Remove old pcnSambaPassword:
		if (isset($oldattributes['pcnSambaPassword']))
			$ldap_object['pcnSambaPassword'] = array();

		// TODO: PBX plugin
		if (file_exists(COMMON_CORE_DIR . "/iplex/Users.class.php")) {
			if (! in_array("pcnPbxAccount", $ldap_object['objectClass']))
				$ldap_object['objectClass'][] = "pcnPbxAccount";

			$ldap_object['pcnPbxState'] = (isset($userinfo['pbxFlag']) && $userinfo['pbxFlag']) ? '1' : '0';
			$ldap_object['pcnPbxPresenceState'] = (isset($userinfo['pbxPresenceFlag']) && $userinfo['pbxPresenceFlag']) ? '1' : '0';
			$ldap_object['pcnPbxExtension'] = (empty($userinfo['pbxExtension'])) ? 'none' : $userinfo['pbxExtension'];
		}

		// TODO: Legacy 4.x PBX LDAP cruft
		if (isset($ldap_object['pcnPbxState']) && empty($ldap_object['pcnPbxState']))
			$ldap_object['pcnPbxState'] = 0;

		return $ldap_object;
	}

	/**
	 * Generates an irreversible HASH.
	 *
	 * @access private
	 * @return string
	 */

	protected function _DesHash($plain)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$key = $this->_DesAddParity($plain);
		$td = mcrypt_module_open(MCRYPT_DES, '', MCRYPT_MODE_ECB, '');
		$iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
		mcrypt_generic_init($td, $key, $iv);
		$hash = mcrypt_generic($td, 'KGS!@#$%');
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);

		return $hash;
	}

   /**
	 * Adds the parity bit to the given DES key.
	 *
	 * @access private
	 * @param  string  $key 7-Bytes Key without parity
	 * @return string
	 */

	protected function _DesAddParity($key)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		static $odd_parity = array(
				1,  1,  2,  2,  4,  4,  7,  7,  8,  8, 11, 11, 13, 13, 14, 14,
				16, 16, 19, 19, 21, 21, 22, 22, 25, 25, 26, 26, 28, 28, 31, 31,
				32, 32, 35, 35, 37, 37, 38, 38, 41, 41, 42, 42, 44, 44, 47, 47,
				49, 49, 50, 50, 52, 52, 55, 55, 56, 56, 59, 59, 61, 61, 62, 62,
				64, 64, 67, 67, 69, 69, 70, 70, 73, 73, 74, 74, 76, 76, 79, 79,
				81, 81, 82, 82, 84, 84, 87, 87, 88, 88, 91, 91, 93, 93, 94, 94,
				97, 97, 98, 98,100,100,103,103,104,104,107,107,109,109,110,110,
				112,112,115,115,117,117,118,118,121,121,122,122,124,124,127,127,
				128,128,131,131,133,133,134,134,137,137,138,138,140,140,143,143,
				145,145,146,146,148,148,151,151,152,152,155,155,157,157,158,158,
				161,161,162,162,164,164,167,167,168,168,171,171,173,173,174,174,
				176,176,179,179,181,181,182,182,185,185,186,186,188,188,191,191,
				193,193,194,194,196,196,199,199,200,200,203,203,205,205,206,206,
				208,208,211,211,213,213,214,214,217,217,218,218,220,220,223,223,
				224,224,227,227,229,229,230,230,233,233,234,234,236,236,239,239,
				241,241,242,242,244,244,247,247,248,248,251,251,253,253,254,254);

		$bin = '';
		for ($i = 0; $i < strlen($key); $i++)
			$bin .= sprintf('%08s', decbin(ord($key{$i})));

		$str1 = explode('-', substr(chunk_split($bin, 7, '-'), 0, -1));
		$x = '';

		foreach($str1 as $s)
			$x .= sprintf('%02s', dechex($odd_parity[bindec($s . '0')]));

		return pack('H*', $x);
	}

	/**
	 * Returns the default group information details for new users.
	 *
	 * @throws EngineException
	 * @return void
	 */

	function _GetDirectoryDefaultGroup()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		// TODO: remove the added complexity?
		// If needed, this could be expanded to allow an adminstrator to
		// specify the default group in a configuration file.

		$default['name'] = User::DEFAULT_USER_GROUP;
		$default['id'] = User::DEFAULT_USER_GROUP_ID;

		// See if the GID has been changed for the given group name

		if ($this->ldaph == null)
			$this->_GetLdapHandle();

		try {
			$result = $this->ldaph->Search(
				"(&(cn=" . $default['name'] . ")(objectclass=posixGroup))",
				ClearDirectory::GetGroupsOu(),
				array('gidNumber')
			);

			$entry = $this->ldaph->GetFirstEntry($result);

			if ($entry) {
				$attributes = $this->ldaph->GetAttributes($entry);
				$default['id'] = $attributes['gidNumber'][0];
			}
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		return $default;
	}

	/**
	 * Creates an LDAP handle.
	 *
	 * @access private
	 * @return void
	 * @throws ValidationException, EngineException
	 */

	protected function _GetLdapHandle()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$this->ldaph = new Ldap();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Returns the next available user ID.
	 *
	 * @access private
	 * @return integer next available user ID
	 * @throws EngineException
	 */

	function _GetNextUidNumber()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if ($this->ldaph == null)
			$this->_GetLdapHandle();

		try {
			$dn = ClearDirectory::GetMasterDn();
			$attributes = $this->ldaph->Read($dn);

			// TODO: should have some kind of semaphore to prevent duplicate IDs
			$next['uidNumber'] = $attributes['uidNumber'][0] + 1;
			$this->ldaph->Modify($dn, $next);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		return $attributes['uidNumber'][0];
	}

	/**
	 * Returns LDAP user information in hash array.
	 *
	 * @access private
	 * @return array hash array of user information
	 * @throws EngineException
	 */

	protected function _GetUserInfo()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if ($this->ldaph == null)
			$this->_GetLdapHandle();

		try {
			$dn = $this->ldaph->GetDnForUid($this->username);
			$attributes = $this->ldaph->Read($dn);
			$attributes['dn'] = $dn;
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		if (! $attributes)
			throw new EngineException(USER_LANG_ERRMSG_USER_NOT_FOUND, COMMON_WARNING);

		return $attributes;
	}

	/**
	 * Sets the password using ClearDirectory conventions.
	 *
	 * @access private
	 * @param string $password password
	 * @param boolean $includesamba workaround for Samba password changes
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	protected function _SetPassword($password, $includesamba = true)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		// Already validated by SetPassword and ResetPassword

		// TODO: merge this with section in _ConvertArrayToLdap
		$ldap_object['userPassword'] = '{sha}' . $this->_CalculateShaPassword($password);
		$ldap_object['pcnSHAPassword'] = $ldap_object['userPassword'];
		$ldap_object['pcnMicrosoftNTPassword'] = $this->_CalculateNtPassword($password);
		$ldap_object['pcnMicrosoftLanmanPassword'] = $this->_CalculateLanmanPassword($password);

		$oldattributes = $this->_GetUserInfo();

		// If necessary, add pcnAccount object class for the above passwords
		if (! in_array('pcnAccount', $oldattributes['objectClass'])) {
			$classes = $oldattributes['objectClass'];
			array_shift($classes);
			$classes[] = 'pcnAccount';
			$ldap_object['objectClass']= $classes;
		}

		foreach ($this->infomap as $key => $value) {
			if (isset($this->infomap[$key]['passwordtype'])) {
				if (isset($oldattributes[$this->infomap[$key]['passwordfield']])) {
					if ($this->infomap[$key]['passwordtype'] == self::CONSTANT_TYPE_SHA)
						$ldap_object[$this->infomap[$key]['passwordfield']] = $ldap_object['pcnSHAPassword'];
					elseif ($this->infomap[$key]['passwordtype'] == self::CONSTANT_TYPE_SHA1)
						$ldap_object[$this->infomap[$key]['passwordfield']] = $this->_ConvertShaToSha1($ldap_object['pcnSHAPassword']);
					elseif ($this->infomap[$key]['passwordtype'] == self::CONSTANT_TYPE_LANMAN)
						$ldap_object[$this->infomap[$key]['passwordfield']] = $ldap_object['pcnMicrosoftLanmanPassword'];
					elseif ($this->infomap[$key]['passwordtype'] == self::CONSTANT_TYPE_NT)
						$ldap_object[$this->infomap[$key]['passwordfield']] = $ldap_object['pcnMicrosoftNTPassword'];
				}
			}
		}

		// TODO / Samba hook should be removed if possible
		if ($includesamba) {
			if (isset($oldattributes['sambaSID'])) {
				$ldap_object['sambaLMPassword'] = $ldap_object['pcnMicrosoftLanmanPassword'] ;
				$ldap_object['sambaNTPassword'] = $ldap_object['pcnMicrosoftNTPassword'];
				$ldap_object['sambaPwdLastSet'] = time();
			}
		}

		sleep(2); // see comment in SetPassword

		$this->ldaph->Modify($oldattributes['dn'], $ldap_object);
	}

	/**
	 * Converts string to unicode.
	 *
	 * This will be a native PHP method in the not too distant future.
	 *
	 * @access private
	 * @return void
	 */

	protected function _StringToUnicode($string)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$unicode = "";

		for ($i = 0; $i < strlen($string); $i++) {
			$a = ord($string{$i}) << 8;
			$unicode .= sprintf("%X", $a);
		}

		return pack('H*', $unicode);
	}

	/**
	 * Synchronizes home directories and mailboxes.
	 *
	 * @param boolean $homedir flag to create homedirs
	 * @access private
	 * @return voide
	 */

	protected function _Synchronize($homedirs)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		// TODO: move this to an external daemon... just a hack for now
		try {
			$options['background'] = true;
			$shell = new ShellExec();
			if ($homedirs)
				$shell->Execute(User::COMMAND_SYNCUSERS, '', true, $options);
			$shell->Execute(User::COMMAND_SYNCMAILBOX, '', true, $options);
		} catch (Exception $e) {
			// Not fatal
		}
	}

	/**
	 * Validates a userinfo array.
	 *
	 * @access private
	 * @param array $userinfo user information array
	 * @param boolean $ismodify set to true if using results on LDAP modif
	 * @return boolean true if userinfo is valid
	 * @throws EngineException
	 */

	protected function _ValidateUserinfo($userinfo, $ismodify)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$isvalid = true;
		$invalid_attrs = array();

		// Check userinfo type
		//--------------------

		if (!is_array($userinfo)) {
			$this->AddValidationError( LOCALE_LANG_ERRMSG_INVALID_TYPE . " - userinfo", __METHOD__, __LINE__);
			return false;
		}

		// Validate user information using validator defined in $this->infomap
		//--------------------------------------------------------------------

		foreach ($userinfo as $attribute => $detail) {
			if (isset($this->infomap[$attribute]) && isset($this->infomap[$attribute]['validator'])) {
				// TODO: afterthought -- password/verify check is done below
				if ($attribute == 'password')
					continue;

				$validator = $this->infomap[$attribute]['validator'];
				if (! $this->$validator($detail)) {
					$isvalid = false;
					$invalid_attrs[] = $attribute;
				}
			}
		}

		// Validate passwords
		//-------------------

		if (!empty($userinfo['password']) || !empty($userinfo['verify'])) {
			if (!($this->IsValidPasswordAndVerify($userinfo['password'], $userinfo['verify']))) {
				$isvalid = false;
				$invalid_attrs[] = 'password';
			}
		}

		// When adding a new user, check for missing attributes
		//-----------------------------------------------------

		if (! $ismodify) {
			foreach ($this->infomap as $attribute => $details) {
				if (empty($userinfo[$attribute]) && 
					($details['required'] == true) &&
					(!in_array($attribute, $invalid_attrs))
					) {
						$isvalid = false;
						$this->AddValidationError(
							LOCALE_LANG_ERRMSG_REQUIRED_PARAMETER_IS_MISSING . " - " . $details['locale'], __METHOD__, __LINE__
						);
				} 
			}
		}

		if ($isvalid)
			return true;
		else
			return false;
	}

	/**
	 * @access private
	 */

	function __destruct()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		parent::__destruct();
	}
}

// vim: syntax=php ts=4
?>
