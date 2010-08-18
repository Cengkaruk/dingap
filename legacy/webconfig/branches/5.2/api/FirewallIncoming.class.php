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
 * Firewall incoming connections config.
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
require_once("Firewall.list.php");
require_once("Firewall.class.php");
require_once("FirewallRule.class.php");

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Firewall incoming connections config.
 * 
 * @package Api
 * @subpackage Network
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class FirewallIncoming extends Firewall
{
	///////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////

	/**
	 * FirewallIncoming constructor.
	 */

	public function __construct()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct();
	}

	/**
	 * Add a port/to the incoming allow list.
	 *
	 * @param string name rule nickname
	 * @param string protocol the protocol - UDP/TCP
	 * @param int port port number
	 * @return void
	 * @throws EngineException
	 */

	public function AddAllowPort($name, $protocol, $port)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		switch ($protocol) {

		case "TCP":
			$protocolflag = FirewallRule::PROTO_TCP;
			break;

		case "UDP":
			$protocolflag = FirewallRule::PROTO_UDP;
			break;
		}

		try {
			$rule = new FirewallRule();

			$rule->SetName($name);
			$rule->SetProtocol($protocolflag);
			$rule->SetPort($port);
			$rule->SetFlags(FirewallRule::INCOMING_ALLOW | FirewallRule::ENABLED);

			if ($rule->CheckValidationErrors())
				$this->errors = $rule->CopyValidationErrors(true);
			else
				$this->AddRule($rule);

		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Add a port range to the incoming allow list.
	 *
	 * @param string name rule nickname
	 * @param string protocol the protocol - UDP/TCP
	 * @param int from from port number
	 * @param int to to port number
	 * @return void
	 * @throws EngineException
	 */

	public function AddAllowPortRange($name, $protocol, $from, $to)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		switch ($protocol) {

		case "TCP":
			$protocolflag = FirewallRule::PROTO_TCP;
			break;

		case "UDP":
			$protocolflag = FirewallRule::PROTO_UDP;
			break;
		}

		try {
			$rule = new FirewallRule();
			$rule->SetName($name);
			$rule->SetProtocol($protocolflag);
			$rule->SetPortRange($from, $to);
			$rule->SetFlags(FirewallRule::INCOMING_ALLOW | FirewallRule::ENABLED);

			if ($rule->CheckValidationErrors())
				$this->errors = $rule->CopyValidationErrors(true);
			else
				$this->AddRule($rule);

		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Add a standard service to the incoming allow list.
	 *
	 * @param string service service name eg HTTP, FTP, SMTP
	 * @return void
	 * @throws EngineException
	 */

	public function AddAllowStandardService($service)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		global $PORTS;

		try {
			if ($service == "PPTP") {
				$this->SetPptpServerState(true);
			} else if ($service == "IPsec") {
				$this->SetIpsecServerState(true);
			} else {
				if (!$this->IsValidService($service))
					throw new EngineException(FIREWALL_LANG_ERRMSG_SERVICE_INVALID, COMMON_WARNING);

				$rule = new FirewallRule();

				foreach ($PORTS as $port) {
					if ($port[3] != $service)
						continue;

					// Replace / and space with underscore
					$rule->SetName(preg_replace("/[\/ ]/", "_", $service));
					$rule->SetProtocol( $rule->ConvertProtocolName($port[1]) );
					$rule->SetFlags(FirewallRule::INCOMING_ALLOW | FirewallRule::ENABLED);
					if ($port[0] == Firewall::CONSTANT_PORT_RANGE) {
						list($from, $to) = split(":", $port[2], 2);
						$rule->SetPortRange($from, $to);
					} else {
						$rule->SetPort($port[2]);
					}

					if ($rule->CheckValidationErrors())
						$this->errors = $rule->CopyValidationErrors(true);
					else
						$this->AddRule($rule);
				}
			}
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Checks to see if given port is open.
	 *
	 * The return value is one of the following:
	 * - Firewall::CONSTANT_NOT_CONFIGURED
	 * - Firewall::CONSTANT_ENABLED
	 * - Firewall::CONSTANT_DISABLED
	 *
	 * @param string $protocol protocol
	 * @param int $port port number
	 * @return int one of the described return values
	 * @throws EngineException, ValidationException
	 */

	public function CheckPort($protocol, $port)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!$this->IsValidProtocol($protocol))
			throw new ValidationException(FIREWALL_LANG_PROTOCOL . " - " . LOCALE_LANG_INVALID);

		if (!$this->IsValidPort($port))
			throw new ValidationException(FIREWALL_LANG_PORT . " - " . LOCALE_LANG_INVALID);

		$ports = $this->GetAllowPorts();

		foreach ($ports as $portinfo) {
			if (($portinfo['port'] == $port) && ($portinfo['protocol'] == $protocol)) {
				if ($portinfo['enabled'])
					return Firewall::CONSTANT_ENABLED;
				else
					return Firewall::CONSTANT_DISABLED;
			}
		}

		return Firewall::CONSTANT_NOT_CONFIGURED;
	}

	/**
	 * Block incoming host connection(s).
	 *
	 * @param string name rule nickname
	 * @param string host/addr host address
	 * @return void
	 * @throws EngineException
	 */

	public function AddBlockHost($name, $ip)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$rule = new FirewallRule();
			$rule->SetFlags(FirewallRule::INCOMING_BLOCK | FirewallRule::ENABLED);
			$rule->SetAddress($ip);
			$rule->SetName($name);

			if ($rule->CheckValidationErrors())
				$this->errors = $rule->CopyValidationErrors(true);
			else
				$this->AddRule($rule);

		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Delete a port from the incoming allow list.
	 *
	 * @param string protocol the protocol - UDP/TCP
	 * @param int port port number
	 * @return void
	 * @throws EngineException
	 */

	public function DeleteAllowPort($protocol, $port)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$rule = new FirewallRule();
			$rule->SetProtocol( $rule->ConvertProtocolName($protocol) );
			$rule->SetPort($port);
			$rule->SetFlags(FirewallRule::INCOMING_ALLOW);

			if ($rule->CheckValidationErrors())
				$this->errors = $rule->CopyValidationErrors(true);
			else
				$this->DeleteRule($rule);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Delete a port range from the incoming allow list.
	 *
	 * @param string protocol the protocol - UDP/TCP
	 * @param int port port number
	 * @return void
	 * @throws EngineException
	 */

	public function DeleteAllowPortRange($protocol, $from, $to)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$rule = new FirewallRule();
			$rule->SetProtocol( $rule->ConvertProtocolName($protocol) );
			$rule->SetPortRange($from, $to);
			$rule->SetFlags(FirewallRule::INCOMING_ALLOW);

			if ($rule->CheckValidationErrors())
				$this->errors = $rule->CopyValidationErrors(true);
			else
				$this->DeleteRule($rule);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Delete incoming host block rule.
	 *
	 * @param string host/addr host address
	 * @return void
	 * @throws EngineException
	 */

	public function DeleteBlockHost($ip)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$rule = new FirewallRule();
			$rule->SetFlags(FirewallRule::INCOMING_BLOCK);
			$rule->SetAddress($ip);

			if ($rule->CheckValidationErrors())
				$this->errors = $rule->CopyValidationErrors(true);
			else
				$this->DeleteRule($rule);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Enable/disable a port from the incoming allow list.
	 *
	 * @param boolean enabled rule enabled?
	 * @param string protocol the protocol - UDP/TCP
	 * @param int port port number
	 * @return void
	 * @throws EngineException
	 */

	public function ToggleEnableAllowPort($enabled, $protocol, $port)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$rule = new FirewallRule();

			$rule->SetProtocol( $rule->ConvertProtocolName($protocol) );
			$rule->SetPort($port);
			$rule->SetFlags(FirewallRule::INCOMING_ALLOW);

			if(!($rule = $this->FindRule($rule)))
				return;

			$this->DeleteRule($rule);

			($enabled) ? $rule->Enable() : $rule->Disable();

			$this->AddRule($rule);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Enable/disable a port range from the incoming allow list.
	 *
	 * @param boolean enabled rule enabled?
	 * @param string protocol the protocol - UDP/TCP
	 * @param int port port number
	 * @return void
	 * @throws EngineException
	 */

	public function ToggleEnableAllowPortRange($enabled, $protocol, $from, $to)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$rule = new FirewallRule();

			$rule->SetProtocol( $rule->ConvertProtocolName($protocol) );
			$rule->SetPortRange($from, $to);
			$rule->SetFlags(FirewallRule::INCOMING_ALLOW);

			if(!($rule = $this->FindRule($rule)))
				return;

			$this->DeleteRule($rule);

			($enabled) ? $rule->Enable() : $rule->Disable();

			$this->AddRule($rule);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Enable/disable incoming host block rule.
	 *
	 * @param string host/addr host address
	 * @return void
	 * @throws EngineException
	 */

	public function ToggleEnableBlockHost($enabled, $ip)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$rule = new FirewallRule();

		try {
			$rule->SetFlags(FirewallRule::INCOMING_BLOCK);
			$rule->SetAddress($ip);

			if(!($rule = $this->FindRule($rule)))
				return;

			$this->DeleteRule($rule);

			($enabled) ? $rule->Enable() : $rule->Disable();

			$this->AddRule($rule);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Gets allowed incoming port ranges.  The information is an array
	 * with the following hash array entries:
	 *
	 *  info[name]
	 *  info[protocol]
	 *  info[from]
	 *  info[to]
	 *  info[enabled]
	 *
	 * @return array array containing allowed incoming port ranges
	 * @throws EngineException
	 */

	public function GetAllowPortRanges()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$portlist = array();

		try {
			$rules = $this->GetRules();
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}

		foreach($rules as $rule) {
			if (!strstr($rule->GetPort(), ":"))
				continue;

			if (!($rule->GetFlags() & FirewallRule::INCOMING_ALLOW))
				continue;

			if ($rule->GetFlags() & (FirewallRule::WIFI | FirewallRule::CUSTOM))
				continue;

			if ($rule->GetProtocol() != FirewallRule::PROTO_TCP &&
			        $rule->GetProtocol() != FirewallRule::PROTO_UDP)
				continue;

			$info = array();

			switch ($rule->GetProtocol()) {

			case FirewallRule::PROTO_TCP:
				$info['protocol'] = 'TCP';
				break;

			case FirewallRule::PROTO_UDP:
				$info['protocol'] = 'UDP';
				break;
			}

			$info['name'] = $rule->GetName();
			$info['enabled'] = $rule->IsEnabled();
			list($info['from'], $info['to']) = split(":", $rule->GetPort(), 2);
			$info['service'] = $this->LookupService($info['protocol'], $rule->GetPort());

			$portlist[] = $info;
		}

		return $portlist;
	}

	/**
	 * Gets allowed incoming ports.  The information is an array
	 * with the following hash array entries:
	 *
	 *  info[name]
	 *  info[protocol]
	 *  info[port]
	 *  info[service] (FTP, HTTP, etc.)
	 *
	 * @return array array containing allowed incoming ports
	 * @throws EngineException
	 */

	public function GetAllowPorts()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$portlist = array();

		try {
			$rules = $this->GetRules();
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}

		foreach ($rules as $rule) {
			if (strstr($rule->GetPort(), ":"))
				continue;

			if (!($rule->GetFlags() & FirewallRule::INCOMING_ALLOW))
				continue;

			if ($rule->GetFlags() & (FirewallRule::WIFI | FirewallRule::CUSTOM))
				continue;

			if ($rule->GetProtocol() != FirewallRule::PROTO_TCP &&
			        $rule->GetProtocol() != FirewallRule::PROTO_UDP)
				continue;

			$info = array();

			switch ($rule->GetProtocol()) {

			case FirewallRule::PROTO_TCP:
				$info['protocol'] = 'TCP';
				break;

			case FirewallRule::PROTO_UDP:
				$info['protocol'] = 'UDP';
				break;
			}

			$info['name'] = $rule->GetName();
			$info['enabled'] = $rule->IsEnabled();
			$info['port'] = $rule->GetPort();
			$info['service'] = $this->LookupService($info['protocol'], $info['port']);
			$portlist[] = $info;
		}

		return $portlist;
	}

	/**
	 * Gets incoming host block rules.  The information is an array
	 * with the following hash array entries:
	 *
	 *  info[name]
	 *  info[host]
	 *  info[enabled]
	 *
	 * @return array array containing incoming host block rules
	 * @throws EngineException
	 */

	public function GetBlockHosts()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$hosts = array();

		try {
			$rules = $this->GetRules();
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}

		foreach ($rules as $rule) {
			if (!($rule->GetFlags() & FirewallRule::INCOMING_BLOCK))
				continue;

			if ($rule->GetFlags() & FirewallRule::CUSTOM)
				continue;

			$info = array();
			$info['name'] = $rule->GetName();
			$info['host'] = $rule->GetAddress();
			$info['enabled'] = $rule->IsEnabled();

			$hosts[] = $info;
		}

		return $hosts;
	}

	/**
	 * Gets IPSec server settings.
	 *
	 * @return boolean true if firewall allows incoming IPSec traffic
	 */

	public function GetIpsecServerState()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		return $this->GetState("IPSEC_SERVER");
	}

	/**
	 * Gets PPTP server state.
	 *
	 * @return boolean true if firewall allows incoming PPTP traffic
	 */

	public function GetPptpServerState()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		return $this->GetState("PPTP_SERVER");
	}

	/**
	 * Sets IPSec server settings.
	 *
	 * @param boolean state state of the special IPsec rule
	 * @return void
	 */

	public function SetIpsecServerState($state)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$this->SetState($state, "IPSEC_SERVER");
	}

	/**
	 * Sets PPTP server settings.
	 *
	 * @param boolean state state of the special PPTP server rule
	 * @returns void
	 */

	public function SetPptpServerState($state)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$this->SetState($state, "PPTP_SERVER");
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
