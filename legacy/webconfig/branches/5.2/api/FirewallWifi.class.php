<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2004-2006 Point Clark Networks.
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
 * Firewall Wifi base class.
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

require_once("Firewall.class.php");
require_once("FirewallRule.class.php");

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Firewall Wifi base class.
 *
 * @package Api
 * @subpackage Network
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class FirewallWifi extends Firewall
{
	///////////////////////////////////////////////////////////////////////////
	// C O N S T A N T S
	///////////////////////////////////////////////////////////////////////////

	///////////////////////////////////////////////////////////////////////////
	// F I E L D S
	///////////////////////////////////////////////////////////////////////////

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * FirewallWifi constructor.
	 */

	public function __construct() 
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct();
	}

	/** 
	 * Add a MAC address to the allowed list.
	 * 
	 * @param string name name
	 * @param string mac MAC address
	 * @return void
	 * @throws EngineException
	 */

	public function AddWifiMac($name, $mac)
	{
		$rule = new FirewallRule;

		try {
			$rule->SetName($name);
			$rule->SetAddress($mac);
			$rule->SetFlags(FirewallRule::MAC_FILTER | FirewallRule::WIFI | FirewallRule::ENABLED);
			$this->AddRule($rule);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/** 
	 * Delete a MAC address from the allowed list.
	 * 
	 * @param string mac MAC address
	 * @return void
	 * @throws EngineException
	 */

	public function DeleteWifiMac($mac)
	{
		$rule = new FirewallRule;

		try {
			$rule->SetAddress($mac);
			$rule->SetFlags(FirewallRule::MAC_FILTER | FirewallRule::WIFI);
			$this->DeleteRule($rule);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Returns a list of MACs allowed to use Wifi.
	 *
	 * @return array array list of MAC addresses
	 * @throws EngineException
	 */

	public function GetWifiMacs()
	{
		$macs = array();

		try {
			$rules = $this->GetRules();
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}

		foreach ($rules as $rule) {
			if (!$rule->IsEnabled()) continue;
			if (!($rule->GetFlags() & FirewallRule::MAC_FILTER)) continue;
			if (!($rule->GetFlags() & FirewallRule::WIFI)) continue;

			$info = array();
			$info['name'] = $rule->GetName();
			$info['mac'] = $rule->GetAddress();
			$macs[] = $info;
		}

		return $macs;
	}

	/**
	 * Set Wifi interface.
	 * 
	 * @param string eth WIFI interface name
	 * @return void
	 * @throws EngineException
	 */

	public function SetWifiInterface($eth)
	{
		return $this->SetInterface($eth, "WIFIF");
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
