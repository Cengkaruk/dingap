<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2003-2006 Point Clark Networks.
//
///////////////////////////////////////////////////////////////////////////////
//
// This program is free software; you can redistribute it and/or
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
 * Firewall redirect class.
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

require_once('Firewall.class.php');
require_once('FirewallRule.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Firewall redirect class.
 *
 * @package Api
 * @subpackage Network
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class FirewallRedirect extends Firewall
{
	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Firewall redirect constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct();
	}

	/**
	 * Returns proxy bypass rules.
	 *
	 * The returned array contains the following hash entries:
	 * - info[name]
	 * - info[host]
	 * - info[enabled]
	 *
	 * @return array array containing proxy bypass rules
	 * @throws EngineException
	 */

	function GetProxyBypassList()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$list = array();

		$rules = $this->GetRules();

		foreach($rules as $rule) {
			if (!($rule->GetFlags() & (FirewallRule::PROXY_BYPASS)))
				continue;

			$info = array();
			$info['name'] = $rule->GetName();
			$info['host'] = $rule->GetAddress();
			$info['enabled'] = $rule->IsEnabled();
			$list[] = $info;
		}

		return $list;
	}

	/**
	 * Returns the port of the proxy content filter.
	 *
	 * @return int port address of the parent filter
	 * @throws EngineException
	 */

	public function GetProxyFilterPort()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		return $this->GetValue("SQUID_FILTER_PORT");
	}

	/**
	 * Returns state of proxy transparent mode.
	 *
	 * @return boolean true if in trasparent mode enabled
	 * @throws EngineException
	 */

	public function GetProxyTransparentState()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		return $this->GetState("SQUID_TRANSPARENT");
	}

	/**
	 * Adds a proxy bypass rule to the firewall.
	 *
	 * @param string $name rule nickname
	 * @param string $host host/IP address to enable proxy bypass
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	function AddProxyBypass($name, $host)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$rule = new FirewallRule();
			$rule->SetName($name);
			$rule->SetFlags(FirewallRule::PROXY_BYPASS | FirewallRule::ENABLED);
			$rule->SetAddress($host);

			if ($rule->CheckValidationErrors())
				$this->errors = $rule->CopyValidationErrors(true);
			else
				$this->AddRule($rule);

		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Remove a proxy bypass rule from the firewall.
	 *
	 * @param string $host host/IP address to remove
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	function DeleteProxyBypass($host)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$rule = new FirewallRule();
			$rule->SetFlags(FirewallRule::PROXY_BYPASS);
			$rule->SetAddress($host);
			$this->DeleteRule($rule);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Define a port for a parent content filter used by proxy.
	 *
	 * @param int port Port address of the Content Filter
	 * @returns void
	 * @throws EngineException
	 */

	public function SetProxyFilterPort($port)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$this->SetValue($port, "SQUID_FILTER_PORT");
	}


   /**
	 * Set state of proxy transparent mode.
	 *
	 * @param string $state state of transparent mode
	 * @returns void
	 * @throws EngineException
	 */

	public function SetProxyTransparentState($state)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$this->SetState($state, "SQUID_TRANSPARENT");
	}

	/**
	 * Enable/disable a proxy bypass rule.
	 *
	 * @param boolean $enabled state of the rule
	 * @param string $host host/IP address to remove
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	function ToggleEnableProxyBypass($enabled, $host)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$rule = new FirewallRule();
			$rule->SetFlags(FirewallRule::PROXY_BYPASS);
			$rule->SetAddress($host);

			if (!($rule = $this->FindRule($rule)))
				return;

			$this->DeleteRule($rule);
			($enabled) ? $rule->Enable() : $rule->Disable();
			$this->AddRule($rule);

		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

	/**
	 * @access private
	 */

	public function __destruct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__destruct();
	}
}

// vim: syntax=php ts=4
?>
