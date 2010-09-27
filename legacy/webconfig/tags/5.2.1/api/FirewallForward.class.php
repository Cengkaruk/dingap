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
 * Firewall forward connections config.
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
 * Firewall forward connections config.
 * 
 * @package Api
 * @subpackage Network
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class FirewallForward extends Firewall
{
	/**
	 * FirewallForward constructor.
	 */

	public function __construct()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct();

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Add a port/to the forward allow list.
	 *
	 * @param string nickname optional rule nickname
	 * @param string protocol the protocol - UDP/TCP
	 * @param int port port number
	 * @return void
	 * @throws EngineException
	 */

	public function AddForwardPort($nickname, $protocol, $fromport, $toport, $toip)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$rule = new FirewallRule();

		// Validate
		//---------

		try {
			if (strlen($nickname))
				$rule->SetName($nickname);

			if ($this->IsValidPort($fromport)) {
				$rule->SetParameter($fromport);
			} else {
				$this->AddValidationError(FIREWALL_LANG_ERRMSG_PORT_INVALID, __METHOD__, __LINE__);
			}

			$rule->SetPort($toport);
			$rule->SetProtocol( $rule->ConvertProtocolName($protocol) );
			$rule->SetAddress($toip);
			$rule->SetFlags(FirewallRule::FORWARD | FirewallRule::ENABLED);

			if ($rule->CheckValidationErrors() || (! empty($this->errors)))
				$this->errors = array_merge($rule->CopyValidationErrors(true), $this->errors);
			else
				$this->AddRule($rule);

		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Add a port range to the forward allow list.
	 *
	 * @param string nickname optional rule nickname
	 * @param string protocol the protocol - UDP/TCP
	 * @param int lowport low port number
	 * @param int highport high port number
	 * @param string toip the destination ip address
	 * @return void
	 * @throws EngineException
	 * 
	 */

	public function AddForwardPortRange($nickname, $protocol, $lowport, $highport, $toip)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$rule = new FirewallRule();

		try {
			if (strlen($nickname))
				$rule->SetName($nickname);

			if ($this->IsValidPortRange($lowport, $highport))
				$rule->SetParameter("$lowport:$highport");
			else
				$this->AddValidationError(FIREWALL_LANG_ERRMSG_PORT_RANGE_INVALID, __METHOD__, __LINE__);

			$rule->SetProtocol( $rule->ConvertProtocolName($protocol) );
			$rule->SetAddress($toip);
			$rule->SetFlags(FirewallRule::FORWARD | FirewallRule::ENABLED);

			if ($rule->CheckValidationErrors() || (! empty($this->errors)))
				$this->errors = array_merge($rule->CopyValidationErrors(true), $this->errors);
			else
				$this->AddRule($rule);

		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Add a standard service to the forward allow list.
	 *
	 * @param string nickname optional rule nickname
	 * @param string service service name eg HTTP, FTP, SMTP
	 * @param string toip the destination ip address
	 * @return void
	 * @throws EngineException
	 */

	public function AddForwardStandardService($nickname, $service, $toip)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		global $PORTS;

		// Validate: toip and nickname validated by AddForwardPort method
		//---------

		if (!$this->IsValidService($service)) {
			throw new EngineException(FIREWALL_LANG_ERRMSG_SERVICE_INVALID, COMMON_WARNING);
		} else if ($service == "IPsec") {
			throw new EngineException(LANG_ERRMSG_NOT_SUPPORTED, COMMON_WARNING);
		} else if ($service == "PPTP") {
			try {
				$this->SetPptpServer($toip);
			} catch (Exception $e) {
				throw new EngineException($e->getMessage(), COMMON_WARNING);
			}

			return;
		}

		$myports = $PORTS;

		foreach ($myports as $portinfo) {
			if ($portinfo[3] == $service) {
				if (preg_match("/:/", $portinfo[2])) {
					$ports = explode(":", $portinfo[2]);
					$this->AddForwardPortRange($nickname, $portinfo[1], $ports[0], $ports[1], $toip);
				} else {
					$this->AddForwardPort($nickname, $portinfo[1], $portinfo[2], $portinfo[2], $toip);
				}
			}
		}
	}

	/**
	 * Enable/disable a port from the forward allow list.
	 *
	 * @param boolean enabled rule enabled?
	 * @param string protocol the protocol - UDP/TCP
	 * @param int fromport from port number
	 * @param int toport to port number
	 * @param string toip target IP address
	 * @return void
	 * @throws EngineException
	 */

	public function ToggleEnableForwardPort($enabled, $protocol, $fromport, $toport, $toip)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$rule = new FirewallRule();

		try {
			$rule->SetProtocol( $rule->ConvertProtocolName($protocol) );
			$rule->SetAddress($toip);
			$rule->SetPort($toport);
			$rule->SetParameter($fromport);
			$rule->SetFlags(FirewallRule::FORWARD);

			if (!($rule = $this->FindRule($rule)))
				return;

			$this->DeleteRule($rule);

			($enabled) ? $rule->Enable() : $rule->Disable();

			$this->AddRule($rule);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Enable/disable a port range from the forward range allow list.
	 *
	 * @param boolean enabled rule enabled?
	 * @param string protocol the protocol - UDP/TCP
	 * @param int lowport low port number
	 * @param int highport high port number
	 * @param string toip target IP address
	 * @return void
	 * @throws EngineException
	 */

	public function ToggleEnableForwardPortRange($enabled, $protocol, $lowport, $highport, $toip)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$rule = new FirewallRule();

		try {
			$rule->SetProtocol( $rule->ConvertProtocolName($protocol) );
			$rule->SetAddress($toip);
			$rule->SetParameter("$lowport:$highport");
			$rule->SetFlags(FirewallRule::FORWARD);

			if (!($rule = $this->FindRule($rule)))
				return;

			$this->DeleteRule($rule);

			($enabled) ? $rule->Enable() : $rule->Disable();

			$this->AddRule($rule);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Enable/disable PPTP forwarding.
	 *
	 * @param boolean enabled rule enabled?
	 * @param string ip IP of PPTP server
	 * @return void
	 * @throws EngineException
	 */

	public function ToggleEnablePptpServer($enabled, $ip)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$rule = new FirewallRule();

		try {
			$rule->SetPort(1723);
			$rule->SetProtocol(FirewallRule::PROTO_GRE);
			$rule->SetFlags(FirewallRule::PPTP_FORWARD);
			$rule->SetAddress($ip);

			if (!($rule = $this->FindRule($rule)))
				return;

			$this->DeleteRule($rule);

			($enabled) ? $rule->Enable() : $rule->Disable();

			$this->AddRule($rule);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Delete a port from the forward allow list.
	 *
	 * @param string protocol the protocol - UDP/TCP
	 * @param int fromport from port number
	 * @param int toport to port number
	 * @param string toip target IP address
	 * @return void
	 * @throws EngineException
	 */

	public function DeleteForwardPort($protocol, $fromport, $toport, $toip)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$rule = new FirewallRule();

		try {
			$rule->SetProtocol( $rule->ConvertProtocolName($protocol) );
			$rule->SetAddress($toip);
			$rule->SetPort($toport);
			$rule->SetParameter($fromport);
			$rule->SetFlags(FirewallRule::FORWARD);
			$this->DeleteRule($rule);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Delete a port range from the forward range allow list.
	 *
	 * @param string protocol the protocol - UDP/TCP
	 * @param int lowport low port number
	 * @param int highport high port number
	 * @param string toip target IP address
	 * @return void
	 * @throws EngineException
	 */

	public function DeleteForwardPortRange($protocol, $lowport, $highport, $toip)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$rule = new FirewallRule();

		try {
			$rule->SetProtocol( $rule->ConvertProtocolName($protocol) );
			$rule->SetAddress($toip);
			$rule->SetParameter("$lowport:$highport");
			$rule->SetFlags(FirewallRule::FORWARD);
			$this->DeleteRule($rule);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Gets allowed forward ports.  The information is an array
	 * with the following hash array entries:
	 *
	 *  info[enabled]
	 *  info[protocol]
	 *  info[nickname]
	 *  info[fromport]
	 *  info[toip]
	 *  info[toport]
	 *  info[service] (FTP, HTTP, etc.)
	 *
	 * @return array array containing allowed forward ports
	 * @throws EngineException
	 */

	public function GetForwardPorts()
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
			if (strstr($rule->GetParameter(), ":"))
				continue;

			if (!($rule->GetFlags() & FirewallRule::FORWARD))
				continue;

			if ($rule->GetFlags() & (FirewallRule::WIFI | FirewallRule::CUSTOM))
				continue;

			$portinfo = array();

			switch ($rule->GetProtocol()) {

			case FirewallRule::PROTO_TCP:
				$portinfo['protocol'] = "TCP";
				break;

			case FirewallRule::PROTO_UDP:
				$portinfo['protocol'] = "UDP";
				break;
			}

			$portinfo['nickname'] = $rule->GetName();
			$portinfo['enabled'] = $rule->IsEnabled();
			$portinfo['toip'] = $rule->GetAddress();
			$portinfo['toport'] = $rule->GetPort();
			$portinfo['fromport'] = $rule->GetParameter();
			$portinfo['service'] = $this->LookupService($portinfo['protocol'], $portinfo['toport']);
			$portlist[] = $portinfo;
		}

		return $portlist;
	}

	/**
	 * Gets allowed forward port ranges.  The information is an array
	 * with the following hash array entries:
	 *
	 *  info[enabled]
	 *  info[protocol]
	 *  info[nickname]
	 *  info[toip]
	 *  info[lowport]
	 *  info[highport]
	 *
	 * @return array array containing allowed forward ports
	 */

	public function GetForwardPortRanges()
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
			if (!strstr($rule->GetParameter(), ":"))
				continue;

			if (!($rule->GetFlags() & FirewallRule::FORWARD))
				continue;

			if ($rule->GetFlags() & (FirewallRule::WIFI | FirewallRule::CUSTOM))
				continue;

			$portinfo = array();

			switch ($rule->GetProtocol()) {

			case FirewallRule::PROTO_TCP:
				$portinfo['protocol'] = "TCP";
				break;

			case FirewallRule::PROTO_UDP:
				$portinfo['protocol'] = "UDP";
				break;
			}

			$portinfo['nickname'] = $rule->GetName();
			$portinfo['enabled'] = $rule->IsEnabled();
			$portinfo['toip'] = $rule->GetAddress();
			$portinfo['service'] = "";
			list($portinfo['lowport'], $portinfo['highport']) = split(":", $rule->GetParameter());
			$portlist[] = $portinfo;
		}

		return $portlist;
	}

	/**
	 * Gets IP of PPTP server behind the firewall.  The result is a single element array
	 * with the following fields:
	 *
	 *  info[enabled]
	 *  info[host]
	 *
	 * @return array array containing the IP of a PPTP server
	 * @throws EngineException
	 */

	public function GetPptpServer()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$rules = $this->GetRules();
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}

		$info = array();

		foreach ($rules as $rule) {
			if (!($rule->GetFlags() & FirewallRule::PPTP_FORWARD))
				continue;

			$info['host'] = $rule->GetAddress();
			$info['enabled'] = $rule->IsEnabled();
			break; // Can only have one of these...
		}

		return $info;
	}

	/**
	 * Sets PPTP forwarding to the given IP address.
	 *
	 * @param string ip IP of PPTP server
	 * @return void
	 * @throws EngineException
	 */

	public function SetPptpServer($ip)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$rule = new FirewallRule();

		try {
			$rule->SetPort(1723);
			$rule->SetProtocol(FirewallRule::PROTO_GRE);
			$rule->SetFlags(FirewallRule::PPTP_FORWARD | FirewallRule::ENABLED);

			$hostinfo = $this->GetPptpServer();

			$oldip = $hostinfo['host'];

			if (strlen($oldip)) {
				$rule->SetAddress($oldip);
				$this->DeleteRule($rule);
			}

			if (strlen($ip)) {
				$rule->SetAddress($ip);
				$this->AddRule($rule);
			}
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
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
