<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2003-2007 Point Clark Networks.
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
 * Firewall 1:1 NAT config.
 * 
 * @package Api
 * @subpackage Network
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2007, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once("Network.class.php");
require_once("Firewall.class.php");
require_once("FirewallRule.class.php");

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Firewall 1:1 NAT config.
 * 
 * @package Api
 * @subpackage Network
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2007, Point Clark Networks
 */

class FirewallOneToOneNat extends Firewall
{
	///////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////

	/**
	 * FirewallOneToOneNat constructor.
	 */

	public function __construct() 
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct();
	}

	/**
	 * Adds a 1:1 NAT rule.
	 *
	 * @param string $name optional rule nickname
	 * @param string $fromip WAN IP address
	 * @param string $toip LAN IP address
	 * @param string $ifn External interface name (ie: eth0)
	 * @return void
	 * @throws EngineException
	 */
	
	public function Add($name, $fromip, $toip, $ifn)
	{
		$rule = new FirewallRule();

		try {
			$rule->SetName($name);
			$rule->SetAddress($fromip);
			$rule->SetFlags(FirewallRule::ONE_TO_ONE | FirewallRule::ENABLED);

			if ($this->IsValidIp($toip))
				$rule->SetParameter($ifn . "_" . $toip);
			else
				$this->AddValidationError(FIREWALLRULE_LANG_ERRMSG_INVALID_ADDR, __METHOD__, __LINE__);

			if ($rule->CheckValidationErrors() || (! empty($this->errors)))
				$this->errors = array_merge($rule->CopyValidationErrors(true), $this->errors);
			else
				$this->AddRule($rule);

		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Adds a port only 1:1 NAT rule.
	 *
	 * @param string $name optional rule nickname
	 * @param string $fromip WAN IP address
	 * @param string $toip LAN IP address
	 * @param string $protocol protocol - TCP or UDP
	 * @param int $port port number
	 * @param string $ifn External interface name (ie: eth0)
	 * @return void
	 * @throws EngineException
	 */

	public function AddPort($name, $fromip, $toip, $protocol, $port, $ifn)
	{
		$rule = new FirewallRule();

		try {
			$rule->SetName($name);
			$rule->SetProtocol( $rule->ConvertProtocolName($protocol) );
			$rule->SetPort($port);
			$rule->SetFlags(FirewallRule::ONE_TO_ONE | FirewallRule::ENABLED);
			$rule->SetAddress($fromip);

			if ($this->IsValidIp($toip))
				$rule->SetParameter($ifn . "_" . $toip);
			else
				$this->AddValidationError(FIREWALLRULE_LANG_ERRMSG_INVALID_ADDR, __METHOD__, __LINE__);

			if ($rule->CheckValidationErrors() || (! empty($this->errors)))
				$this->errors = array_merge($rule->CopyValidationErrors(true), $this->errors);
			else
				$this->AddRule($rule);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Adds a port range 1:1 NAT rule.
	 *
	 * @param string $name optional rule nickname
	 * @param string $fromip WAN IP address
	 * @param string $toip LAN IP address
	 * @param string $protocol protocol - TCP or UDP
	 * @param int $fromport from port number
	 * @param int $toport to port number
	 * @param string $ifn External interface name (ie: eth0)
	 * @return void
	 * @throws EngineException
	 */

	public function AddPortRange($name, $fromip, $toip, $protocol, $fromport, $toport, $ifn)
	{
		$rule = new FirewallRule();

		try {
			$rule->SetName($name);
			$rule->SetProtocol( $rule->ConvertProtocolName($protocol) );
			$rule->SetPortRange($fromport, $toport);
			$rule->SetFlags(FirewallRule::ONE_TO_ONE | FirewallRule::ENABLED);
			$rule->SetAddress($fromip);

			if ($this->IsValidIp($toip))
				$rule->SetParameter($ifn . "_" . $toip);
			else
				$this->AddValidationError(FIREWALLRULE_LANG_ERRMSG_INVALID_ADDR, __METHOD__, __LINE__);

			if ($rule->CheckValidationErrors() || (! empty($this->errors)))
				$this->errors = array_merge($rule->CopyValidationErrors(true), $this->errors);
			else
				$this->AddRule($rule);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Delete an existing 1:1 NAT rule.
	 *
	 * @param string $fromip WAN IP address
	 * @param string $toip LAN IP address
	 * @param string $ifn External interface name (ie: eth0)
	 * @return void
	 * @throws EngineException
	 */

	public function Delete($fromip, $toip, $ifn)
	{
		$rule = new FirewallRule();

		try {
			$rule->SetAddress($fromip);
			if (!strlen($ifn))
				$rule->SetParameter($toip);
			else
				$rule->SetParameter($ifn . "_" . $toip);
			$rule->SetFlags(FirewallRule::ONE_TO_ONE);
			$this->DeleteRule($rule);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Delete an existing 1:1 NAT port rule.
	 *
	 * @param string $fromip WAN IP address
	 * @param string $toip LAN IP address
	 * @param string $protocol protocol - TCP or UDP
	 * @param int $port port number
	 * @param string $ifn External interface name (ie: eth0)
	 * @return void
	 * @throws EngineException
	 */

	public function DeletePort($fromip, $toip, $protocol, $port, $ifn)
	{
		$rule = new FirewallRule();

		try {
			$rule->SetAddress($fromip);
			if (!strlen($ifn))
				$rule->SetParameter($toip);
			else
				$rule->SetParameter($ifn . "_" . $toip);

			switch ($protocol) {
			case "TCP":
				$rule->SetProtocol(FirewallRule::PROTO_TCP);
				break;

			case "UDP":
				$rule->SetProtocol(FirewallRule::PROTO_UDP);
				break;
			}

			$rule->SetPort($port);
			$rule->SetFlags(FirewallRule::ONE_TO_ONE);
			$this->DeleteRule($rule);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Delete an existing 1:1 NAT port range rule.
	 *
	 * @param string $fromip WAN IP address
	 * @param string $toip LAN IP address
	 * @param string $protocol protocol - TCP or UDP
	 * @param int $fromport from port number
	 * @param int $toport to port number
	 * @param string $ifn External interface name (ie: eth0)
	 * @return void
	 * @throws EngineException
	 */

	public function DeletePortRange($fromip, $toip, $protocol, $fromport, $toport, $ifn)
	{
		$rule = new FirewallRule();

		try {
			$rule->SetAddress($fromip);
			if (!strlen($ifn))
				$rule->SetParameter($toip);
			else
				$rule->SetParameter($ifn . "_" . $toip);

			switch ($protocol) {
			case "TCP":
				$rule->SetProtocol(FirewallRule::PROTO_TCP);
				break;

			case "UDP":
				$rule->SetProtocol(FirewallRule::PROTO_UDP);
				break;
			}

			$rule->SetPortRange($fromport, $toport);
			$rule->SetFlags(FirewallRule::ONE_TO_ONE);
			$this->DeleteRule($rule);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Enable/disable an existing 1:1 NAT rule.
	 *
	 * @param boolean $enabled state of rule
	 * @param string $fromip WAN IP address
	 * @param string $toip LAN IP address
	 * @param string $ifn External interface name (ie: eth0)
	 * @return void
	 * @throws EngineException
	 */

	public function ToggleEnable($enabled, $fromip, $toip, $ifn)
	{
		$rule = new FirewallRule();

		try {
			$rule->SetAddress($fromip);
			$rule->SetParameter($ifn . "_" . $toip);
			$rule->SetFlags(FirewallRule::ONE_TO_ONE);

			if (!($rule = $this->FindRule($rule))) return;

			$this->DeleteRule($rule);
			($enabled) ? $rule->Enable() : $rule->Disable();
			$this->AddRule($rule);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Delete an existing 1:1 NAT port rule.
	 *
	 * @param boolean $enabled state of rule
	 * @param string $fromip WAN IP address
	 * @param string $toip LAN IP address
	 * @param string $protocol protocol - TCP or UDP
	 * @param int $port port number
	 * @param string $ifn External interface name (ie: eth0)
	 * @return void
	 * @throws EngineException
	 */

	public function ToggleEnablePort($enabled, $fromip, $toip, $protocol, $port, $ifn)
	{
		$rule = new FirewallRule();

		try {
			$rule->SetAddress($fromip);
			$rule->SetParameter($ifn . "_" . $toip);

			switch ($protocol) {
			case "TCP":
				$rule->SetProtocol(FirewallRule::PROTO_TCP);
				break;

			case "UDP":
				$rule->SetProtocol(FirewallRule::PROTO_UDP);
				break;
			}

			$rule->SetPort($port);
			$rule->SetFlags(FirewallRule::ONE_TO_ONE);

			if (!($rule = $this->FindRule($rule))) return;

			$this->DeleteRule($rule);
			($enabled) ? $rule->Enable() : $rule->Disable();
			$this->AddRule($rule);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Delete an existing 1:1 NAT port range rule.
	 *
	 * @param boolean $enabled state of rule
	 * @param string $fromip WAN IP address
	 * @param string $toip LAN IP address
	 * @param string $protocol protocol - TCP or UDP
	 * @param int $fromport from port number
	 * @param int $toport to port number
	 * @param string $ifn External interface name (ie: eth0)
	 * @return void
	 * @throws EngineException
	 */

	public function ToggleEnablePortRange($enabled, $fromip, $toip, $protocol, $fromport, $toport, $ifn)
	{
		$rule = new FirewallRule();

		try {
			$rule->SetAddress($fromip);
			$rule->SetParameter($ifn . "_" . $toip);

			switch ($protocol) {
			case "TCP":
				$rule->SetProtocol(FirewallRule::PROTO_TCP);
				break;

			case "UDP":
				$rule->SetProtocol(FirewallRule::PROTO_UDP);
				break;
			}

			$rule->SetPortRange($fromport, $toport);
			$rule->SetFlags(FirewallRule::ONE_TO_ONE);

			if (!($rule = $this->FindRule($rule))) return;

			$this->DeleteRule($rule);
			($enabled) ? $rule->Enable() : $rule->Disable();
			$this->AddRule($rule);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Returns list of 1:1 NAT rules.
	 *
	 * @return array array list of 1:1 NAT rules
	 * @throws EngineException
	 */

	public function Get()
	{
		$natlist = array();

		try {
			$rules = $this->GetRules();
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}

		foreach ($rules as $rule) {
			if (!($rule->GetFlags() & FirewallRule::ONE_TO_ONE)) continue;
			if ($rule->GetPort()) continue;

			$info = array();
			$info["name"] = $rule->GetName();
			$info["enabled"] = $rule->IsEnabled();

			if (strpos($rule->GetParameter(), "_") === false) {
				$ifn = "";
				$toip = $rule->GetParameter();
			} else {
				list($ifn, $toip) = explode("_", $rule->GetParameter());
			}

			$info["host"] = sprintf("%s|%s", $toip, $rule->GetAddress());
			$info["ifn"] = $ifn;

			$natlist[] = $info;
		}

		return $natlist;
	}

	/**
	 * Returns list of 1:1 NAT port rules.
	 *
	 * @return array array list of 1:1 NAT port rules
	 * @throws EngineException
	 */

	public function GetPort()
	{
		$natlist = array();

		try {
			$rules = $this->GetRules();
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}

		foreach ($rules as $rule) {
			if (!($rule->GetFlags() & FirewallRule::ONE_TO_ONE)) continue;
			if (!$rule->GetPort()) continue;
            if (strstr($rule->GetPort(), ":")) continue;

			switch ($rule->GetProtocol()) {
			case FirewallRule::PROTO_TCP:
				$proto = "TCP";
				break;

			case FirewallRule::PROTO_UDP:
				$proto = "UDP";
				break;
			}

			$info = array();
			$info["name"] = $rule->GetName();
			$info["enabled"] = $rule->IsEnabled();

			if (strpos($rule->GetParameter(), "_") === false) {
				$ifn = "";
				$toip = $rule->GetParameter();
			} else {
				list($ifn, $toip) = explode("_", $rule->GetParameter());
			}

			$info["host"] = sprintf("%s|%s|%s|%d", $toip, $rule->GetAddress(), $proto, $rule->GetPort());
			$info["ifn"] = $ifn;

			$natlist[] = $info;
		}

		return $natlist;
	}

	/**
	 * Returns list of 1:1 NAT port range rules.
	 *
	 * @return array array list of 1:1 NAT port rules
	 * @throws EngineException
	 */

	public function GetPortRange()
	{
		$natlist = array();

		try {
			$rules = $this->GetRules();
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}

		foreach ($rules as $rule) {
			if (!($rule->GetFlags() & FirewallRule::ONE_TO_ONE)) continue;
			if (!$rule->GetPort()) continue;
            if (!strstr($rule->GetPort(), ":")) continue;

			switch ($rule->GetProtocol()) {
			case FirewallRule::PROTO_TCP:
				$proto = "TCP";
				break;

			case FirewallRule::PROTO_UDP:
				$proto = "UDP";
				break;
			}

			$info = array();
			$info["name"] = $rule->GetName();
			$info["enabled"] = $rule->IsEnabled();

			if (strpos($rule->GetParameter(), "_") === false) {
				$ifn = "";
				$toip = $rule->GetParameter();
			} else {
				list($ifn, $toip) = explode("_", $rule->GetParameter());
			}

			$match = array();
			
			preg_match("/(.*):(.*)/", $rule->GetPort(), $match);	
			$info["host"] = sprintf("%s|%s|%s|%d|%d", $toip, $rule->GetAddress(), $proto, $match[1], $match[2]);
			$info["ifn"] = $ifn;

			$natlist[] = $info;
		}

		return $natlist;
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
