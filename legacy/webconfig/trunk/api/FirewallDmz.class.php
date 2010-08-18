<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2003-2006 Point Clark Networks.
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
 * Firewall DMZ connections config.
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
require_once("Firewall.list.php");
require_once("Firewall.class.php");
require_once("FirewallRule.class.php");

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Firewall DMZ connections config.
 * 
 * @package Api
 * @subpackage Network
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class FirewallDmz extends Firewall
{
	///////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////

	/**
	 * Firewall DMZ constructor.
	 */

	public function __construct() 
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct();
	}

	/**
	 * Add a port/to the forward allow list.
	 *
	 * @param string nickname optional rule nickname
	 * @param string ip IP address
	 * @param string protocol the protocol - UDP/TCP
	 * @param int port port number
	 * @return void
	 * @throws EngineException
	 */

	public function AddForwardPort($nickname, $ip, $protocol, $port)
	{
		$rule = new FirewallRule();

		try {
			$rule->SetProtocol( $rule->ConvertProtocolName($protocol) );
			$rule->SetName($nickname);
			$rule->SetAddress($ip);
			$rule->SetPort($port);
			$rule->SetFlags(FirewallRule::DMZ_INCOMING | FirewallRule::ENABLED);

			if ($rule->CheckValidationErrors() || (! empty($this->errors)))
				$this->errors = $rule->CopyValidationErrors(true);
			else
				$this->AddRule($rule);

		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Add a port/to the pinhole allow list.
	 *
	 * @param string nickname optional rule nickname
	 * @param string ip IP address
	 * @param string protocol the protocol - UDP/TCP
	 * @param int port port number
	 * @return void
	 * @throws EngineException
	 */

	public function AddPinholePort($nickname, $ip, $protocol, $port)
	{
		$rule = new FirewallRule();

		try {
			$rule->SetProtocol( $rule->ConvertProtocolName($protocol) );
			$rule->SetName($nickname);
			$rule->SetAddress($ip);
			$rule->SetPort($port);
			$rule->SetFlags(FirewallRule::DMZ_PINHOLE | FirewallRule::ENABLED);

			if ($rule->CheckValidationErrors() || (! empty($this->errors)))
				$this->errors = $rule->CopyValidationErrors(true);
			else
				$this->AddRule($rule);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Delete a port from the pinhole allow list.
	 *
	 * @param string ip target IP address
	 * @param string protocol the protocol - UDP/TCP
	 * @param int port to port number
	 * @return void
	 * @throws EngineException
	 */

	public function DeletePinholePort($ip, $protocol, $port)
	{
		$rule = new FirewallRule();

		try {
			switch ($protocol) {
			case "TCP":
				$rule->SetProtocol(FirewallRule::PROTO_TCP);
				break;

			case "UDP":
				$rule->SetProtocol(FirewallRule::PROTO_UDP);
				break;
			}

			$rule->SetAddress($ip);
			$rule->SetPort(($port) ? $port : 0);
			$rule->SetFlags(FirewallRule::DMZ_PINHOLE);
			$this->DeleteRule($rule);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Delete a port from the forward allow list.
	 *
	 * @param string ip target IP address
	 * @param string protocol the protocol - UDP/TCP
	 * @param int port to port number
	 * @return void
	 * @throws EngineException
	 */

	public function DeleteForwardPort($ip, $protocol, $port)
	{
		$rule = new FirewallRule();

		try {
			switch ($protocol) {
			case "TCP":
				$rule->SetProtocol(FirewallRule::PROTO_TCP);
				break;

			case "UDP":
				$rule->SetProtocol(FirewallRule::PROTO_UDP);
				break;
			}

			$rule->SetAddress($ip);
			$rule->SetPort(($port) ? $port : 0);
			$rule->SetFlags(FirewallRule::DMZ_INCOMING);
			$this->DeleteRule($rule);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Enable/disable a port from the pinhole allow list.
	 *
	 * @param boolean enabled enable or disable rule?
	 * @param string ip target IP address
	 * @param string protocol the protocol - UDP/TCP
	 * @param int port to port number
	 * @return void
	 * @throws EngineException
	 */

	public function ToggleEnablePinholePort($enabled, $ip, $protocol, $port)
	{
		$rule = new FirewallRule();

		try {
			switch ($protocol) {
			case "TCP":
				$rule->SetProtocol(FirewallRule::PROTO_TCP);
				break;

			case "UDP":
				$rule->SetProtocol(FirewallRule::PROTO_UDP);
				break;
			}

			$rule->SetAddress($ip);
			$rule->SetPort(($port) ? $port : 0);
			$rule->SetFlags(FirewallRule::DMZ_PINHOLE);

			if(!($rule = $this->FindRule($rule))) return;

			$this->DeleteRule($rule);
			($enabled) ? $rule->Enable() : $rule->Disable();
			$this->AddRule($rule);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Enable/disable a port from the forward allow list.
	 *
	 * @param boolean enabled enable or disable rule?
	 * @param string ip target IP address
	 * @param string protocol the protocol - UDP/TCP
	 * @param int port to port number
	 * @return void
	 * @throws EngineException
	 */

	public function ToggleEnableForwardPort($enabled, $ip, $protocol, $port)
	{
		$rule = new FirewallRule();

		try {
			switch ($protocol) {
			case "TCP":
				$rule->SetProtocol(FirewallRule::PROTO_TCP);
				break;

			case "UDP":
				$rule->SetProtocol(FirewallRule::PROTO_UDP);
				break;
			}

			$rule->SetAddress($ip);
			$rule->SetPort(($port) ? $port : 0);
			$rule->SetFlags(FirewallRule::DMZ_INCOMING);

			if(!($rule = $this->FindRule($rule))) return;

			$this->DeleteRule($rule);
			($enabled) ? $rule->Enable() : $rule->Disable();
			$this->AddRule($rule);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Gets forwarded DMZ ports.  The information is an array
	 * with the following hash array entries:
	 *
	 *  info[name]
	 *  info[protocol]
	 *  info[ip]
	 *  info[port]
	 *  info[enabled]
	 *
	 * @return array array list of allowed forward ports
	 * @throws EngineException
	 */

	public function GetForwardPorts()
	{
		$portlist = array();

		try {
			$rules = $this->GetRules();
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}

		foreach ($rules as $rule) {
			if (!($rule->GetFlags() & FirewallRule::DMZ_INCOMING)) continue;

			$portinfo = array();

			switch ($rule->GetProtocol()) {
			case FirewallRule::PROTO_TCP:
				$portinfo['protocol'] = "TCP";
				break;

			case FirewallRule::PROTO_UDP:
				$portinfo['protocol'] = "UDP";
				break;

			default:
				$portinfo['protocol'] = Firewall::CONSTANT_ALL_PROTOCOLS;
				break;
			}

			$portinfo['name'] = $rule->GetName();
			$portinfo['ip'] = $rule->GetAddress();
			$portinfo['port'] = $rule->GetPort();
			$portinfo['enabled'] = $rule->IsEnabled();

			$portlist[] = $portinfo;
		}

		return $portlist;
	}

	/**
	 * Gets forwarded DMZ ports.  The information is an array
	 * with the following hash array entries:
	 *
	 *  info[name]
	 *  info[protocol]
	 *  info[ip]
	 *  info[port]
	 *  info[enabled]
	 *
	 * @return array array list of allowed forward ports
	 */

	public function GetPinholePorts()
	{
		$portlist = array();

		try {
			$rules = $this->GetRules();
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}

		foreach ($rules as $rule) {
			if (!($rule->GetFlags() & FirewallRule::DMZ_PINHOLE)) continue;

			$portinfo = array();

			switch ($rule->GetProtocol()) {
			case FirewallRule::PROTO_TCP:
				$portinfo['protocol'] = "TCP";
				break;

			case FirewallRule::PROTO_UDP:
				$portinfo['protocol'] = "UDP";
				break;

			default:
				$portinfo['protocol'] = Firewall::CONSTANT_ALL_PROTOCOLS;
				break;
			}

			$portinfo['name'] = $rule->GetName();
			$portinfo['ip'] = $rule->GetAddress();
			$portinfo['port'] = $rule->GetPort();
			$portinfo['enabled'] = $rule->IsEnabled();

			$portlist[] = $portinfo;
		}

		return $portlist;
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
