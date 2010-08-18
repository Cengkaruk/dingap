<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2009 Point Clark Networks.
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
 * Firewall l7-filter support class.
 * 
 * @package Api
 * @subpackage Network
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once("Network.class.php");
require_once("File.class.php");
require_once("Firewall.class.php");

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Firewall l7-filter support class.
 * 
 * @package Api
 * @subpackage Network
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class FirewallLayer7Filter extends Firewall
{
	///////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////

	/**
	 * FirewallLayer7Filter constructor.
	 */

	public function __construct()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		parent::__construct();
	}

	/**
	 * Add a protocol filter host exception.
	 *
	 * @param string $name the exception nickname
	 * @param string $ip the IP address
	 * @return void
	 * @throws EngineException
	 */

	public function AddException($name, $ip)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$rule = new FirewallRule();

		try {
			$rule->SetName($name);
			$rule->SetAddress($ip);
			$rule->SetFlags(FirewallRule::L7FILTER_BYPASS | FirewallRule::ENABLED);

			if ($rule->CheckValidationErrors())
				$this->errors = $rule->CopyValidationErrors(true);
			else
				$this->AddRule($rule);

		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Remove a protocol filter exception rule.
	 *
	 * @param string $ip the IP address
	 * @return void
	 * @throws EngineException
	 */

	public function DeleteException($ip)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$rule = new FirewallRule;

		try {
			$rule->SetAddress($ip);
			$rule->SetFlags(FirewallRule::L7FILTER_BYPASS | $network);
			$this->DeleteRule($rule);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Enable/disable a protocol exception rule.
	 *
	 * @param string $ip the IP address
	 * @return void
	 * @throws EngineException
	 */

	public function ToggleException($ip)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$rule = new FirewallRule;

		try {
			$rule->SetAddress($ip);
			$rule->SetFlags(FirewallRule::L7FILTER_BYPASS);

			if(!($rule = $this->FindRule($rule)))
				return;

			$this->DeleteRule($rule);

			if ($rule->IsEnabled()) $rule->Disable();
			else $rule->Enable();

			$this->AddRule($rule);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Returns an array of protocol filter exceptions.
	 * with the following hash array entries:
	 *
	 *  info[name]
	 *  info[ip]
	 *  info[enabled]
	 *
	 * @return array array list containing protocol filter exceptions 
	 * @throws EngineException
	 */

	public function GetExceptions()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$exceptions = array();

		try {
			$rules = $this->GetRules();
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}

		foreach ($rules as $rule) {
			if (!($rule->GetFlags() & FirewallRule::L7FILTER_BYPASS))
				continue;

			$info = array();
			$info['name'] = $rule->GetName();
			$info['ip'] = $rule->GetAddress();
			$info['enabled'] = $rule->IsEnabled();

			$exceptions[] = $info;
		}

		return $exceptions;
	}

	/**
	 * Returns state of the protocol filter.
	 *
	 * @return boolean true if protocol filter is enabled
	 * @throws EngineException
	 */

	public function GetProtocolFilterState()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		return $this->GetState('PROTOCOL_FILTERING');
	}

	/**
	 * Sets state of the protocol filter.
	 *
	 * @param boolean $state state of protocol filter 
	 * @returns void
	 * @throws EngineException
	 */

	public function SetProtocolFilterState($state)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetState($state, 'PROTOCOL_FILTERING');
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
