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
 * Password policy engine.
 *
 * The OpenLDAP ppolicy engine is currently used as the global policy.  This
 * does not need to be the case in the future, and is only a matter of 
 * convenience.
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
require_once('LdapPasswordPolicy.class.php');
require_once('SambaPasswordPolicy.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Password policy engine.
 *
 * @package Api
 * @subpackage Directory
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2010, Point Clark Networks
 */

class PasswordPolicy extends Engine
{
	///////////////////////////////////////////////////////////////////////////////
	// V A R I A B L E S
	///////////////////////////////////////////////////////////////////////////////

	const CONSTANT_LOCKOUT_FOREVER = 0;
	const CONSTANT_MODIFY_ANY_TIME = 0;
	const CONSTANT_NO_HISTORY = 0;
	const CONSTANT_NO_EXPIRE = 0;

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

		require_once(GlobalGetLanguageTemplate(__FILE__));
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

		$policy = new LdapPasswordPolicy();
		$info = $policy->GetDefaultPolicy();

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

		$policy = new LdapPasswordPolicy();
		$policy->Initialize();
	}

	/**
	 * Sets default policy.
	 *
	 * The settings object is defined as follows:
	 * - historySize (integer): the number of passwords to store in history
	 * - maximumAge (seconds): maximum password age
	 * - minimumAge (seconds): minimum password age
	 * - minimumLength (integer): minimum length of a password
	 *
	 * @param array $settings settings object
	 * @return void
	 * @throws EngineException
	 */

	function SetDefaultPolicy($settings)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Update global policy (LDAP) 
		$policy = new LdapPasswordPolicy();
		$policy->SetDefaultPolicy($settings);

		// Update Samba policy
		$samba_policy = new SambaPasswordPolicy();
		$samba_settings = $this->_ConvertPolicyToSamba($settings);
		$samba_policy->SetDefaultPolicy($samba_settings);
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

		// Apply global policy (LDAP) 
		$policy = new LdapPasswordPolicy();
		$policy->ApplyDefaultPolicy();

		// Samba policy is automatically enforced
	}

	///////////////////////////////////////////////////////////////////////////////
	// P R I V A T E  M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Converts the generic password policies to Samba password policies
	 *
	 * @param array $settings policy settings
	 * @return array password policies
	 * @throws EngineException
	 */

	private function _ConvertPolicyToSamba($settings)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if ($settings['historySize'] == PasswordPolicy::CONSTANT_NO_HISTORY)
			$samba_settings['sambaPwdHistoryLength'] = SambaPasswordPolicy::CONSTANT_NO_HISTORY;
		else	
			$samba_settings['sambaPwdHistoryLength'] = $settings['historySize'];

		if ($settings['maximumAge'] == PasswordPolicy::CONSTANT_NO_EXPIRE)
			$samba_settings['sambaMaxPwdAge'] = SambaPasswordPolicy::CONSTANT_NO_EXPIRE;
		else
			$samba_settings['sambaMaxPwdAge'] = $settings['maximumAge'];

		if ($settings['minimumAge'] == PasswordPolicy::CONSTANT_MODIFY_ANY_TIME)
			$samba_settings['sambaMinPwdAge'] = SambaPasswordPolicy::CONSTANT_MODIFY_ANY_TIME;
		else
			$samba_settings['sambaMinPwdAge'] = $settings['minimumAge'];

		$samba_settings['sambaMinPwdLength'] = $settings['minimumLength'];

		return $samba_settings;
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
