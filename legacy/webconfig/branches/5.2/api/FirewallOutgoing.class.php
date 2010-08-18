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
 * Firewall outgoing connections config.
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
 * Firewall outgoing connections config.
 * 
 * @package Api
 * @subpackage Network
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class FirewallOutgoing extends Firewall
{
	///////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////

	/**
	 * Firewall constructor.
	 */

	public function __construct()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct();
	}

	/**
	 * Add a common service to the block list.
	 *
	 * @param string destination destination address
	 * @return void
	 * @throws EngineException
	 */

	public function AddBlockCommonDestination($destination)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		global $DOMAINS;

		if (! $destination) {
			$this->AddValidationError(FIREWALL_LANG_ERRMSG_SERVICE_INVALID, __METHOD__, __LINE__);
			return;
		}

		$mydomains = $DOMAINS;

		foreach ($mydomains as $domaininfo) {
			if ($domaininfo[1] == $destination) {
				$rule = new FirewallRule();

				$rule->SetAddress($domaininfo[0]);
				$rule->SetFlags(FirewallRule::OUTGOING_BLOCK | FirewallRule::ENABLED);
				if ($rule->CheckValidationErrors())
					$this->errors = $rule->CopyValidationErrors(true);
				else
					$this->AddRule($rule);
			}
		}
	}

	/**
	 * Add common destination to block list.
	 *
	 * @param string name rule nickname
	 * @param string destination destination address
	 * @return void
	 * @throws EngineException
	 */

	public function AddBlockDestination($name, $destination)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$rule = new FirewallRule();

		try {
			$rule->SetName($name);
			$rule->SetAddress($destination);
			$rule->SetFlags(FirewallRule::OUTGOING_BLOCK | FirewallRule::ENABLED);

			if ($rule->CheckValidationErrors())
				$this->errors = $rule->CopyValidationErrors(true);
			else
				$this->AddRule($rule);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Add a port/to the outgoing allow list.
	 *
	 * @param string name rule nickname
	 * @param string protocol the protocol - UDP/TCP
	 * @param int port port number
	 * @return void
	 * @throws EngineException
	 */

	public function AddBlockPort($name, $protocol, $port)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$rule = new FirewallRule();

		try {
			$rule->SetName($name);
			$rule->SetPort($port);
			$rule->SetProtocol( $rule->ConvertProtocolName($protocol) );
			$rule->SetFlags(FirewallRule::OUTGOING_BLOCK | FirewallRule::ENABLED);

			if ($rule->CheckValidationErrors())
				$this->errors = $rule->CopyValidationErrors(true);
			else
				$this->AddRule($rule);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Add a port range to the outgoing allow list.
	 *
	 * @param string name rule nickname
	 * @param string protocol the protocol - UDP/TCP
	 * @param int from from port number
	 * @param int to to port number
	 * @return void
	 * @throws EngineException
	 */

	public function AddBlockPortRange($name, $protocol, $from, $to)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$rule = new FirewallRule();

		try {
			$rule->SetName($name);
			$rule->SetProtocol( $rule->ConvertProtocolName($protocol) );
			$rule->SetPortRange($from, $to);
			$rule->SetFlags(FirewallRule::OUTGOING_BLOCK | FirewallRule::ENABLED);

			if ($rule->CheckValidationErrors())
				$this->errors = $rule->CopyValidationErrors(true);
			else
				$this->AddRule($rule);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Add a standard service to the outgoing allow list.
	 *
	 * @param string service service name eg HTTP, FTP, SMTP
	 * @return void
	 * @throws EngineException
	 */

	public function AddBlockStandardService($service)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		global $PORTS;

		if ($service == "PPTP") {
			throw new EngineException("TODO: No support for blocking outgoing PPTP traffic", COMMON_WARNING);
		} else if ($service == "IPsec") {
			throw new EngineException("TODO: No support for blocking outgoing IPsec traffic", COMMON_WARNING);
		} else {
			if (!$this->IsValidService($service))
				throw new EngineException(FIREWALL_LANG_ERRMSG_SERVICE_INVALID, COMMON_WARNING);

			$rule = new FirewallRule();

			try {
				foreach ($PORTS as $port) {
					if ($port[3] != $service)
						continue;

					$rule->SetPort($port[2]);
					$rule->SetProtocol( $rule->ConvertProtocolName($port[1]) );
					$rule->SetName(preg_replace("/\//", "", $service));
					$rule->SetFlags(FirewallRule::OUTGOING_BLOCK | FirewallRule::ENABLED);
					$this->AddRule($rule);
				}
			} catch (Exception $e) {
				throw new EngineException($e->getMessage(), COMMON_WARNING);
			}
		}
	}

	/**
	 * Enable/disable a host, IP or network from the block outgoing hosts list.
	 *
	 * @param boolean enabled rule enabled?
	 * @param string host host, IP or network
	 * @return void
	 * @throws EngineException
	 */

	public function ToggleEnableBlockDestination($enabled, $host)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$rule = new FirewallRule();

		try {
			$rule->SetAddress($host);
			$rule->SetFlags(FirewallRule::OUTGOING_BLOCK);

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
	 * Enable/disable a port from the outgoing allow list.
	 *
	 * @param boolean enabled rule enabled?
	 * @param string protocol the protocol - UDP/TCP
	 * @param int port port number
	 * @return void
	 * @throws EngineException
	 */

	public function ToggleEnableBlockPort($enabled, $protocol, $port)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$rule = new FirewallRule();

		try {
			$rule->SetProtocol( $rule->ConvertProtocolName($protocol) );
			$rule->SetPort($port);
			$rule->SetFlags(FirewallRule::OUTGOING_BLOCK);

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
	 * Enable/disable a port range from the outgoing allow list.
	 *
	 * @param boolean enabled rule enabled?
	 * @param string protocol the protocol - UDP/TCP
	 * @param int port port number
	 * @return void
	 * @throws EngineException
	 */

	public function ToggleEnableBlockPortRange($enabled, $protocol, $from, $to)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$rule = new FirewallRule();

		try {
			$rule->SetProtocol( $rule->ConvertProtocolName($protocol) );
			$rule->SetPortRange($from, $to);
			$rule->SetFlags(FirewallRule::OUTGOING_BLOCK);

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
	 * Delete a host, IP or network from the block outgoing hosts list.
	 *
	 * @param string host host, IP or network
	 * @return void
	 * @throws EngineException
	 */

	public function DeleteBlockDestination($host)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$rule = new FirewallRule();

		try {
			$rule->SetAddress($host);
			$rule->SetFlags(FirewallRule::OUTGOING_BLOCK);
			$this->DeleteRule($rule);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Delete a port from the outgoing allow list.
	 *
	 * @param string protocol the protocol - UDP/TCP
	 * @param int port port number
	 * @return void
	 * @throws EngineException
	 */

	public function DeleteBlockPort($protocol, $port)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$rule = new FirewallRule();

		try {
			$rule->SetProtocol( $rule->ConvertProtocolName($protocol) );
			$rule->SetPort($port);
			$rule->SetFlags(FirewallRule::OUTGOING_BLOCK);
			$this->DeleteRule($rule);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Delete a port range from the outgoing allow list.
	 *
	 * @param string protocol the protocol - UDP/TCP
	 * @param int port port number
	 * @return void
	 * @throws EngineException
	 */

	public function DeleteBlockPortRange($protocol, $from, $to)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$rule = new FirewallRule();

		try {
			$rule->SetProtocol( $rule->ConvertProtocolName($protocol) );
			$rule->SetPortRange($from, $to);
			$rule->SetFlags(FirewallRule::OUTGOING_BLOCK);
			$this->DeleteRule($rule);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Return a list of common servers that people block.
	 *
	 * @return array array list of common services blocked
	 */

	public function GetCommonBlockList()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		global $DOMAINS;

		$byname = array();

		foreach ($DOMAINS as $item)
		array_push($byname, $item[1]);

		return $byname;
	}

	/**
	 * Returns list of blocked hosts.
	 *
	 * @return array array list of blocked hosts
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
			if (!($rule->GetFlags() & FirewallRule::OUTGOING_BLOCK))
				continue;

			if ($rule->GetFlags() & (FirewallRule::WIFI | FirewallRule::CUSTOM))
				continue;

			if (!strlen($rule->GetAddress()))
				continue;

			$hostinfo = array();
			$hostinfo['name'] = $rule->GetName();
			$hostinfo['enabled'] = $rule->IsEnabled();
			$hostinfo['host'] = $rule->GetAddress();
			$hostinfo['metainfo'] = $this->LookupHostMetainfo($hostinfo[host]);

			$hosts[] = $hostinfo;
		}

		return $hosts;
	}

	/**
	 * Gets allowed outgoing port ranges.  The information is an array
	 * with the following hash array entries:
	 *
	 *  info[name]
	 *  info[protocol]
	 *  info[from]
	 *  info[to]
	 *  info[enabled]
	 *
	 * @return string allowed outgoing port ranges
	 * @throws EngineException
	 */

	public function GetBlockPortRanges()
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
			if (!strstr($rule->GetPort(), ":"))
				continue;

			if (!($rule->GetFlags() & FirewallRule::OUTGOING_BLOCK))
				continue;

			if ($rule->GetFlags() & (FirewallRule::WIFI | FirewallRule::CUSTOM))
				continue;

			if ($rule->GetProtocol() != FirewallRule::PROTO_TCP && $rule->GetProtocol() != FirewallRule::PROTO_UDP)
				continue;

			$info = array();

			switch ($rule->GetProtocol()) {

			case FirewallRule::PROTO_TCP:
				$info['protocol'] = "TCP";
				break;

			case FirewallRule::PROTO_UDP:
				$info['protocol'] = "UDP";
				break;
			}

			$info['name'] = $rule->GetName();
			$info['enabled'] = $rule->IsEnabled();
			list($info['from'], $info['to']) = split(":", $rule->GetPort(), 2);

			$portlist[] = $info;
		}

		return $portlist;
	}

	/**
	 * Gets allowed outgoing ports.  The information is an array
	 * with the following hash array entries:
	 *
	 *  info[name]
	 *  info[protocol]
	 *  info[port]
	 *  info[service] (FTP, HTTP, etc.)
	 *  info[enabled]
	 *
	 * @return string allowed outgoing ports
	 * @throws EngineException
	 */

	public function GetBlockPorts()
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

			if (!($rule->GetFlags() & FirewallRule::OUTGOING_BLOCK))
				continue;

			if ($rule->GetFlags() & (FirewallRule::WIFI | FirewallRule::CUSTOM))
				continue;

			if ($rule->GetProtocol() != FirewallRule::PROTO_TCP && $rule->GetProtocol() != FirewallRule::PROTO_UDP)
				continue;

			$info = array();

			switch ($rule->GetProtocol()) {

			case FirewallRule::PROTO_TCP:
				$info['protocol'] = "TCP";
				break;

			case FirewallRule::PROTO_UDP:
				$info['protocol'] = "UDP";
				break;
			}

			$info['port'] = $rule->GetPort();
			$info['name'] = $rule->GetName();
			$info['enabled'] = $rule->IsEnabled();
			$info['service'] = $this->LookupService($info['protocol'], $info['port']);
			$portlist[] = $info;
		}

		return $portlist;
	}

	/**
	 * Returns state of egress mode.
	 *
	 * @return boolean true if egress mode is enabled
	 * @throws EngineException
	 */

	public function GetEgressState()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		return $this->GetState("EGRESS_FILTERING");
	}

	/**
	 * Sets state of egress mode.
	 *
	 * @param boolean $state state of egress mode
	 * @returns void
	 * @throws EngineException
	 */

	public function SetEgressState($state)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$this->SetState($state, "EGRESS_FILTERING");
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
