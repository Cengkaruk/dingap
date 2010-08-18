<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2005-2006 Point Clark Networks.
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
 * Firewall MultiWAN support class.
 * 
 * @package Api
 * @subpackage Network
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2005-2006, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once("Network.class.php");
require_once("File.class.php");
require_once("Firewall.class.php");
require_once("FirewallRule.class.php");

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Firewall MultiWAN support class.
 * 
 * @package Api
 * @subpackage Network
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2005-2006, Point Clark Networks
 */

class FirewallMultiWan extends Firewall
{
	///////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////

	/**
	 * Class constructor.
	 */

	public function __construct() 
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct();

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Add a destination port rule to the firewall.
	 *
	 * @param string name optional rule nickname
	 * @param string proto numeric protocol
	 * @param int port port number
	 * @param string ifn destination interface name
	 * @return void
	 * @throws EngineException
	 */

	public function AddDestinationPortRule($name, $proto, $port, $ifn)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$rule = new FirewallRule;

		try {
			$rule->SetName($name);
			$rule->SetFlags(FirewallRule::SBR_PORT | FirewallRule::ENABLED);
			$rule->SetProtocol($rule->ConvertProtocolName($proto));
			$rule->SetPort($port);
			$rule->SetParameter($ifn);

			if ($rule->CheckValidationErrors())
				$this->errors = $rule->CopyValidationErrors(true);
			else
				$this->AddRule($rule);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Remove a destination port rule from the firewall.
	 *
	 * @param string proto numeric protocol
	 * @param int port port number
	 * @param string ifn destination interface name
	 * @return void
	 * @throws EngineException
	 */

	public function DeleteDestinationPortRule($proto, $port, $ifn)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$rule = new FirewallRule;

		try {
			$rule->SetFlags(FirewallRule::SBR_PORT);
			$rule->SetProtocol($proto);
			$rule->SetPort($port);
			$rule->SetParameter($ifn);
			$this->DeleteRule($rule);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Enable/disable a destination port rule.
	 *
	 * @param boolean enabled rule enabled?
	 * @param string proto numeric protocol
	 * @param int port port number
	 * @param string ifn destination interface name
	 * @return void
	 * @throws EngineException
	 */

	public function ToggleEnablePortRule($enabled, $proto, $port, $ifn)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$rule = new FirewallRule;

		try {
			$rule->SetFlags(FirewallRule::SBR_PORT);
			$rule->SetProtocol($proto);
			$rule->SetPort($port);
			$rule->SetParameter($ifn);

			if(!($rule = $this->FindRule($rule))) return;

			$this->DeleteRule($rule);
			($enabled) ? $rule->Enable() : $rule->Disable();
			$this->AddRule($rule);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Returns an array of destination port rules
	 * with the following hash array entries:
	 *
	 *  info[name]
	 *  info[proto]
	 *  info[port]
	 *  info[ifn]
	 *  info[enabled]
	 *
	 * @return array array list containing destination port rules
	 * @throws EngineException
	 */

	public function GetPortRules()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$list = array();

		try {
			$rules = $this->GetRules();
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}

		foreach ($rules as $rule) {
			if (!($rule->GetFlags() & (FirewallRule::SBR_PORT))) continue;

			$info = array();

			$info['name'] = $rule->GetName();
			$info['proto'] = $rule->GetProtocol();
			$info['port'] = $rule->GetPort();
			$info['ifn'] = $rule->GetParameter();
			$info['enabled'] = $rule->IsEnabled();

			$list[] = $info;
		}

		return $list;
	}

	/**
	 * Add a source-based route rule to the firewall.
	 *
	 * @param string name optional rule nickname
	 * @param string source source address
	 * @param string ifn destination interface name
	 * @return void
	 * @throws EngineException
	 */

	public function AddSourceBasedRoute($name, $source, $ifn)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$rule = new FirewallRule;

		try {
			$rule->SetName($name);
			$rule->SetFlags(FirewallRule::SBR_HOST | FirewallRule::ENABLED);
			$rule->SetAddress($source);
			$rule->SetParameter($ifn);

			if ($rule->CheckValidationErrors())
				$this->errors = $rule->CopyValidationErrors(true);
			else
				$this->AddRule($rule);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Remove a source-based route rule from the firewall.
	 *
	 * @param string source source address
	 * @param string ifn destination interface name
	 * @return void
	 * @throws EngineException
	 */

	public function DeleteSourceBasedRoute($source, $ifn)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$rule = new FirewallRule;

		try {
			$rule->SetFlags(FirewallRule::SBR_HOST);
			$rule->SetAddress($source);
			$rule->SetParameter($ifn);
			$this->DeleteRule($rule);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Enable/disable a source-based route rule.
	 *
	 * @param boolean enabled rule enabled?
	 * @param string source source address
	 * @param string ifn destination interface name
	 * @return void
	 * @throws EngineException
	 */

	public function ToggleEnableSourceBasedRoute($enabled, $source, $ifn)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$rule = new FirewallRule;

		try {
			$rule->SetFlags(FirewallRule::SBR_HOST);
			$rule->SetAddress($source);
			$rule->SetParameter($ifn);

			if(!($rule = $this->FindRule($rule))) return;

			$this->DeleteRule($rule);
			($enabled) ? $rule->Enable() : $rule->Disable();
			$this->AddRule($rule);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Returns an array of source-based route rules
	 * with the following hash array entries:
	 *
	 *  info[name]
	 *  info[source]
	 *  info[ifn]
	 *  info[enabled]
	 *
	 * @return array array list containing source-based route rules
	 */

	public function GetSourceBasedRoutes()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$list = array();

		try {
			$rules = $this->GetRules();
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}

		foreach ($rules as $rule) {
			if (!($rule->GetFlags() & (FirewallRule::SBR_HOST))) continue;

			$info = array();

			$info['name'] = $rule->GetName();
			$info['source'] = $rule->GetAddress();
			$info['ifn'] = $rule->GetParameter();
			$info['enabled'] = $rule->IsEnabled();

			$list[] = $info;
		}

		return $list;
	}


	/**
	 * Returns an array of external interfaces.
	 *
	 * @return array array list containing external interfaces
	 */

	public function GetExternalInterfaces()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$list = array();

		$ph = @popen("source " . Firewall::FILE_CONFIG . " && echo \$EXTIF", "r");
		if(!$ph) return $list;

		$list = explode(" ", chop(fgets($ph)));
		pclose($ph);

		return $list;
	}

	/**
	 * Returns an interfaces' "weight".
	 *
	 * @param string ifn interface name
	 * @return int integer weight value
	 */

	public function GetInterfaceWeight($ifn)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$list = array();

		$ph = @popen("source " . Firewall::FILE_CONFIG . " && echo \$MULTIPATH_WEIGHTS", "r");
		if(!$ph) return $list;

		$list = explode(" ", chop(fgets($ph)));
		pclose($ph);

		foreach ($list as $item) {
			if (preg_match("/\|/", $item)) {
				list($ifn_weight, $weight) = explode("|", $item, 2);
				if($ifn_weight == $ifn) return $weight;
			}
		}

		return 1;
	}

	/**
	 * Sets an interfaces' "weight".
	 *
	 * @param string ifn interface name
	 * @param int weight interface weight
	 * @return void
	 * @throws EngineException
	 */

	public function SetInterfaceWeight($ifn, $weight)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$wanif_list = $this->GetExternalInterfaces();
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}

		if (!in_array($ifn, $wanif_list, true)) {
			throw new EngineException(FIREWALLMULTIWAN_LANG_INVALID_WANIF . " - $ifn",
				COMMON_WARNING);
		}

		$weights = "$ifn|$weight";

		foreach ($wanif_list as $wanif) {
			if($ifn == $wanif) continue;
			$weights = "$weights $wanif|" . $this->GetInterfaceWeight($wanif);
		}

		$cfg = new File(Firewall::FILE_CONFIG);

		try {
			$matches = $cfg->ReplaceLines("/^MULTIPATH_WEIGHTS=.*/",
				"MULTIPATH_WEIGHTS=\"$weights\"\n");

			if ($matches < 1) {
				$cfg->AddLinesAfter("MULTIPATH_WEIGHTS=\"$weights\"\n", "/^MODE=.*$/");
			} else if ($matches > 1) {
				$matches = $cfg->ReplaceLines("/^MULTIPATH_WEIGHTS=.*/", "");
				$cfg->AddLinesAfter("MULTIPATH_WEIGHTS=\"$weights\"\n", "/^MODE=.*$/");
			}
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Returns the preferred dynamic DNS interface.
	 *
	 * @return string ifn interface name
	 * @throws EngineException
	 */

	public function GetDynamicDnsInterface()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$ifn = '';
		$cfg = new File(Firewall::FILE_CONFIG);

		try {
			$ifn = str_replace('"', '', $cfg->LookupValue('/^DNSIF=/'));
		} catch (FileNoMatchException$e) {
			return '';
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}

		if (empty($ifn)) {
			try {
				$wanif_list = $this->GetExternalInterfaces();
			} catch (Exception $e) {
				throw new EngineException($e->getMessage(), COMMON_WARNING);
			}

			if (! empty($wanif_list[0]))
				$ifn = $wanif_list[0];
		}

		return $ifn;
	}

	/**
	 * Sets a preferred dynamic DNS interface.
	 *
	 * @param string ifn interface name
	 * @return void
	 * @throws EngineException
	 */

	public function SetDynamicDnsInterface($ifn)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$wanif_list = $this->GetExternalInterfaces();
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}

		if (strlen($ifn) && !in_array($ifn, $wanif_list, true)) {
			throw new EngineException(FIREWALLMULTIWAN_LANG_INVALID_WANIF . " - $ifn",
				COMMON_WARNING);
		}

		$cfg = new File(Firewall::FILE_CONFIG);

		try {
			$matches = $cfg->ReplaceLines("/^DNSIF=.*/", "DNSIF=\"$ifn\"\n");

			if ($matches < 1) {
				$cfg->AddLinesAfter("DNSIF=\"$ifn\"\n", "/^MODE=.*$/");
			} else if ($matches > 1) {
				$matches = $cfg->ReplaceLines("/^DNSIF=.*/", "");
				$cfg->AddLinesAfter("DNSIF=\"$ifn\"\n", "/^MODE=.*$/");
			}
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Enable/disable multi-WAN mode.
	 *
	 * @param boolean enable enable or diable multi-WAN
	 * @return void
	 * @throws EngineException
	 */

	public function EnableMultiWan($enable)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$file = new File(Firewall::FILE_CONFIG);

		try {
			$matches = $file->ReplaceLines(sprintf("/^%s=/i", Firewall::CONSTANT_MULTIPATH),
				sprintf("%s=\"%s\"\n", Firewall::CONSTANT_MULTIPATH,
				($enable) ? Firewall::CONSTANT_ON : Firewall::CONSTANT_OFF));
			if ($matches < 1) {
					$file->AddLinesAfter(sprintf("%s=\"%s\"\n", Firewall::CONSTANT_MULTIPATH,
						($enable) ? Firewall::CONSTANT_ON : Firewall::CONSTANT_OFF),
						"/^MODE=/i");
			}
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Is MultiWAN enabled?
	 *
	 * @return boolean true if multi-WAN is enabled
	 */

	public function IsEnabled()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$ph = @popen("source " . Firewall::FILE_CONFIG . " && echo \$MULTIPATH", "r");
		if (!$ph) return false;

		$enabled = false;
		if (chop(fgets($ph)) == Firewall::CONSTANT_ON) $enabled = true;
		if (pclose($ph) != 0) return false;

		return $enabled;
	}

	/** Check firewall mode, if set to DMZ with MultiWAN and no source-based
	 * routes for DMZ networks found, display warning...
	 */

	public function SanityCheckDmz($link = true)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$firewall = new Firewall;

		try {
			switch ($firewall->GetMode()) {
			case Firewall::CONSTANT_AUTO:
			case Firewall::CONSTANT_GATEWAY:
				break;
			default:
				return;
			}

			$dmzif = $firewall->GetInterfaceDefinition(Firewall::CONSTANT_DMZ);
			if (empty($dmzif))
				return;

			$networks = array();
			foreach ($dmzif as $iface) {
				$ifn = new Iface($iface);
				$info = $ifn->GetInterfaceInfo();
				$networks[$iface]['network'] =
					long2ip(ip2long($info['address']) & ip2long($info['netmask']));
				$networks[$iface]['netmask'] =
					substr_count(decbin(ip2long($info['netmask'])), '1');
			}

			$rules = $firewall->GetRules();
			foreach ($rules as $rule) {
				if (! ($rule->GetFlags() & FirewallRule::ENABLED)) continue;
				if (! ($rule->GetFlags() & FirewallRule::SBR_HOST)) continue;
				if (($slash = strpos($rule->GetAddress(), '/')) === false) continue;

				$network = substr($rule->GetAddress(), 0, $slash);
				$netmask = substr($rule->GetAddress(), $slash + 1);

				foreach ($networks as $iface => $dmznet) {
					if ($dmznet['network'] != $network) continue;
					unset($networks[$iface]);
				}
			}

			if (count($networks)) {
				$warning = FIREWALLMULTIWAN_LANG_DMZ_WARNING . '<br><ul>';
				foreach($networks as $iface => $network) {
					$warning .= "<li>$iface: " . $network['network'];
					$warning .= '/' . $network['netmask'] . '</li>';
				}
				$warning .= '</ul>';
				if ($link) {
					$warning . FIREWALLMULTIWAN_LANG_DMZ_SBR . ' &#160; ';
					$warning .= WebButtonContinue("AddSourceBasedRoute");

					WebFormOpen();
				}

				echo WebDialogWarning($warning);

				if ($link) WebFormClose();
			}
		} catch (Exception $e) {
			echo WebDialogWarning($e->getMessage());
		}
	}

	/**
	 * @ignore
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
