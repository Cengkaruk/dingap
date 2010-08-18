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
 * Hostname validation class.
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
require_once('Iface.class.php');
require_once('Hosts.class.php');
require_once('Hostname.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Hostname checker class.
 *
 * @package Api
 * @subpackage Network
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class HostnameChecker extends Hostname
{

	///////////////////////////////////////////////////////////////////////////////
	// F I E L D S
	///////////////////////////////////////////////////////////////////////////////

	const FILE_CONFIG = '/etc/sysconfig/network';
	const CMD_HOSTNAME = '/bin/hostname';
	const CONST_DEFAULT_HOSTNAME = 'system.clearos.lan';

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * HostnameChecker constructor.
	 *
	 */

	public function __construct()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		parent::__construct();
	}

	/**
	 * Fixes the common "cannot lookup my hostname" issue.
	 * Many software packages (Apache, Squid, ProFTP, ...) require a valid
	 * hostname on startup.  This method will add an entry into the /etc/hosts
	 * file to get around this "feature".
	 *
	 * @param $force boolean force an update even if hostname is valid in DNS
	 * @returns void
	 */

	function AutoFix($force = false)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Hostname is ok - return right away
		//-----------------------------------

		if (!$force && $this->IsLookupable())
			return;

		// Get hostname from the /etc/hosts entry
		//---------------------------------------

		$realhostname = $this->Get();

		// Get the IP for the /etc/hosts entry
		//------------------------------------

		// - Find out what firewall mode is running
		// - Get the IP of the LAN interface on a gateway, eth0/ppp0 on other modes

		$firewall = new Firewall();
		$fwmode = $firewall->GetMode();

		if (($fwmode == Firewall::CONSTANT_TRUSTEDSTANDALONE) || ($fwmode == Firewall::CONSTANT_STANDALONE))
			$eth = $firewall->GetInterfaceDefinition(Firewall::CONSTANT_EXTERNAL);
		else
			$eth = $firewall->GetInterfaceDefinition(Firewall::CONSTANT_LAN);

		if (! $eth)
			throw new EngineException(LOCALE_LANG_ERRMSG_WEIRD, COMMON_ERROR);

		$iface = new Iface($eth);
		$ip = $iface->GetLiveIp();

		// Check for entry in /etc/hosts
		//------------------------------

		$hosts = new Hosts();

		$hostip = $hosts->GetIpByHostname($realhostname);
		if ($hostip)
			return;

		// Grab hostnames for IP (if any)
		//-------------------------------

		$hostnames = $hosts->GetHostnamesByIp($ip);

		// Add/Update /etc/hosts entry
		//----------------------------

		if (empty($hostnames)) {
			$hosts->AddHost($ip, array($realhostname));
		} else {
			if (! in_array($realhostname, $hostnames))
				array_unshift($hostnames, $realhostname);

			// purge default hostname
			$newhostnames = array();

			foreach ($hostnames as $checkname) {
				if ($checkname != HostnameChecker::CONST_DEFAULT_HOSTNAME)
					$newhostnames[] = $checkname;
			}

			$hosts->UpdateHost($ip, $newhostnames);
		}
	}
}
// vim: syntax=php ts=4
?>
