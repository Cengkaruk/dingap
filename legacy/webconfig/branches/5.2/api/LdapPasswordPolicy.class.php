<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2010 Point Clark Networks.
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
 * LDAP password policy engine.
 *
 * @package Api
 * @subpackage Directory
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2010, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('Engine.class.php');
require_once('ClearDirectory.class.php');
require_once('UserManager.class.php');
require_once('User.class.php');
require_once('Ldap.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * LDAP password policy engine.
 *
 * @package Api
 * @subpackage Directory
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2010, Point Clark Networks
 */

class LdapPasswordPolicy extends Engine
{
	///////////////////////////////////////////////////////////////////////////////
	// V A R I A B L E S
	///////////////////////////////////////////////////////////////////////////////

	const DEFAULT_DIRECTORY_OBJECT = 'default';
	const CONSTANT_NO_HISTORY = 0;
	const CONSTANT_NO_EXPIRE = 0;
	const CONSTANT_MODIFY_ANY_TIME = 0;

	protected $ldaph = null;

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Password policy engine constructor.
	 *
	 * @return void
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		parent::__construct();
	}

	/**
	 * Returns default password policy information.
	 *
	 * @return array default password policy information
	 * @throws EngineException
	 */

	function GetDefaultPolicy()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if ($this->ldaph == null)
			$this->_GetLdapHandle();

		$dn = "cn=" . LdapPasswordPolicy::DEFAULT_DIRECTORY_OBJECT . "," . ClearDirectory::GetPasswordPoliciesOu();

		if (! $this->ldaph->Exists($dn))
			$this->Initialize();

		$ldapinfo = $this->ldaph->Read($dn);

		$info['maximumAge'] = $ldapinfo['pwdMaxAge'][0];
		$info['minimumAge'] = $ldapinfo['pwdMinAge'][0];
		$info['minimumLength'] = $ldapinfo['pwdMinLength'][0];
		$info['historySize'] = $ldapinfo['pwdInHistory'][0];
		$info['badPasswordLockout'] = ($ldapinfo['pwdLockout'][0] === 'TRUE') ? true : false;
		$info['badPasswordLockoutDuration'] = $ldapinfo['pwdLockoutDuration'][0];
		$info['badPasswordLockoutAttempts'] = $ldapinfo['pwdMaxFailure'][0];

		return $info;
	}

	/**
	 * Initializes password policy system.
	 *
	 * @return void
	 * @throws EngineException
	 */

	function Initialize()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if ($this->ldaph == null)
			$this->_GetLdapHandle();

		// Add password policy container
		//------------------------------

		try {
			$dn = ClearDirectory::GetPasswordPoliciesOu();

			if (! $this->ldaph->Exists($dn)) {
				$ou_attributes['objectClass'] = array('top', 'organizationalUnit');
				$ou_attributes['ou'] = ClearDirectory::OU_PASSWORD_POLICIES;
				$this->ldaph->Add($dn, $ou_attributes);
			}

		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		// Add default policy
		//-------------------

		try {
			$dn = "cn=" . LdapPasswordPolicy::DEFAULT_DIRECTORY_OBJECT . "," . ClearDirectory::GetPasswordPoliciesOu();

			if (! $this->ldaph->Exists($dn)) {
				$policy_attributes['objectClass'] = array('top', 'person', 'pwdPolicy');
				$policy_attributes['cn'] = 'default';
				$policy_attributes['sn'] = 'password policy';
				$policy_attributes['pwdAllowUserChange'] = 'TRUE';
				$policy_attributes['pwdAttribute'] = 'userPassword';
				$policy_attributes['pwdCheckQuality'] = '2';
				$policy_attributes['pwdExpireWarning'] = '600';
				$policy_attributes['pwdFailureCountInterval'] = '30';
				$policy_attributes['pwdGraceAuthNLimit'] = '5';
				$policy_attributes['pwdInHistory'] = '5';
				$policy_attributes['pwdLockout'] = 'TRUE';
				$policy_attributes['pwdLockoutDuration'] = '0';
				$policy_attributes['pwdMaxAge'] = '0';
				$policy_attributes['pwdMaxFailure'] = '5';
				$policy_attributes['pwdMinAge'] = '0';
				$policy_attributes['pwdMinLength'] = '5';
				$policy_attributes['pwdMustChange'] = 'FALSE';
				$policy_attributes['pwdSafeModify'] = 'FALSE';

				$this->ldaph->Add($dn, $policy_attributes);
			}
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Sets global policy.
	 *
	 * @param array $settings settings object
	 * @return void
	 * @throws EngineException
	 */

	function SetDefaultPolicy($settings)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if ($this->ldaph == null)
			$this->_GetLdapHandle();

		$policy_dn = "cn=" . LdapPasswordPolicy::DEFAULT_DIRECTORY_OBJECT . "," . ClearDirectory::GetPasswordPoliciesOu();

		if (! $this->ldaph->Exists($policy_dn))
			$this->Initialize();

		// Update default password policy object
		//--------------------------------------

		$attributes['pwdMaxAge'] = $settings['maximumAge'];
		$attributes['pwdMinAge'] = $settings['minimumAge'];
		$attributes['pwdMinLength'] = $settings['minimumLength'];
		$attributes['pwdInHistory'] = $settings['historySize'];
		$attributes['pwdLockout'] = ($settings['badPasswordLockout']) ? 'TRUE' : 'FALSE';

		try {
			$this->ldaph->Modify($policy_dn, $attributes);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Applies default policy to all users.
	 *
	 * @return void
	 * @throws EngineException
	 */

	function ApplyDefaultPolicy()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if ($this->ldaph == null)
			$this->_GetLdapHandle();

		if (! $this->ldaph->Exists($policy_dn))
			$this->Initialize();

		$policy_dn = "cn=" . LdapPasswordPolicy::DEFAULT_DIRECTORY_OBJECT . "," . ClearDirectory::GetPasswordPoliciesOu();

		try {
			$result = $this->ldaph->Search(
				"(&(objectclass=pcnAccount)(!(pwdPolicySubentry=$policy_dn)))",
				ClearDirectory::GetUsersOu(),
				array("cn")
			);

			$userlist = array();

			$entry = $this->ldaph->GetFirstEntry($result);

			while ($entry) {
				$attributes = $this->ldaph->GetAttributes($entry);
				$userlist[] = $attributes['cn']['0'];
				$entry = $this->ldaph->NextEntry($entry);
			}
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		try {
			$usersou = ClearDirectory::GetUsersOu();

			foreach ($userlist as $user) {
				$userdn = "cn=" . $user . "," . $usersou; 
				$ldap_object['pwdPolicySubentry'] = $policy_dn;
				$this->ldaph->Modify($userdn, $ldap_object);
			}
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
	}

	///////////////////////////////////////////////////////////////////////////////
	// P R I V A T E  M E T H O D S
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
