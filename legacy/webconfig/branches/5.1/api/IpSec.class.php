<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2002-2009 Point Clark Networks.
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
 * IpSec class.
 *
 * @package Api
 * @subpackage Daemon
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2009, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once("Daemon.class.php");
require_once("Locale.class.php");
require_once("File.class.php");
require_once("Folder.class.php");
require_once("Network.class.php");

//////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * IpSec class.
 *
 * @package Api
 * @subpackage Daemon
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2009, Point Clark Networks
 */

class Ipsec extends Daemon
{
    //////////////////////////////////////////////////////////////////////////////
    # V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

	const DIR_IPSEC = '/etc/ipsec.d';

	///////////////////////////////////////////////////////////////////////////////
    # M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * IpSec constructor.
     *
     * @return  void
     */

    function __construct()
    {
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

        parent::__construct('ipsec');
        require_once(GlobalGetLanguageTemplate(__FILE__));
    }

	/**
	 * Sets a network-to-network connection using pre-shared keys.
	 * @param  string  $name  nickname for IpSec tunnel
	 * @param  string  $ipsec_hq  headquarters
	 * @param  string  $ipsec_hq_hop  headquarters first hop
	 * @param  string  $ipsec_hq_subnet  headquarters subnet
	 * @param  string  $ipsec_sat  satellite
	 * @param  string  $ipsec_sat_hop  satellite first hop
	 * @param  string  $ipsec_sat_subnet  satellite subnet
	 * @param  string  $ipsec_psk  private shared key
	 *
	 * @returns  void
	 * @throws  ValidationException, EngineException
	 */

	function SetConnection(
		$name, $ipsec_hq, $ipsec_hq_hop, $ipsec_hq_subnet, $ipsec_sat, $ipsec_sat_hop, $ipsec_sat_subnet, $ipsec_psk)
	{
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		# Validate
		# --------

		$network = new Network();

		if (!$network->IsValidIP($ipsec_hq)) {
            throw new ValidationException(IPSEC_LANG_ERRMSG_HQ_IP_INVALID . $ipsec_hq);
		}

		if (!$network->IsValidIP($ipsec_hq_hop)) {
            throw new ValidationException(IPSEC_LANG_ERRMSG_HQ_HOP_INVALID . $ipsec_hq_hop);
		}

		if (!preg_match("/[\d\.\/]+/", $ipsec_hq_subnet)) {
            throw new ValidationException(IPSEC_LANG_ERRMSG_HQ_NETWORK_INVALID . $ipsec_hq_subnet);
		}

		if (!$network->IsValidIP($ipsec_sat)) {
            throw new ValidationException(IPSEC_LANG_ERRMSG_SATELLITE_IP_INVALID . $ipsec_sat);
		}

		if (!$network->IsValidIP($ipsec_sat_hop)) {
            throw new ValidationException(IPSEC_LANG_ERRMSG_SATELLITE_HOP_INVALID . $ipsec_sat_hop);
		}

		if (!preg_match("/[\d\.\/]+/", $ipsec_sat_subnet)) {
            throw new ValidationException(IPSEC_LANG_ERRMSG_SATELLITE_NETWORK_INVALID . $ipsec_sat_subnet);
		}

		if ($ipsec_sat_subnet == $ipsec_hq_subnet) {
            throw new ValidationException(IPSEC_LANG_ERRMSG_SAME_NETWORK);
		}

		if (!$name) {
            throw new ValidationException(IPSEC_LANG_ERRMSG_CONNECTION_NAME_MISSING);
		}

		if (!preg_match("/^[\w]+$/", $name)) {
            throw new ValidationException(IPSEC_LANG_ERRMSG_CONNECTION_NAME_INVALID);
		}

		if (!$ipsec_psk) {
            throw new ValidationException(IPSEC_LANG_ERRMSG_PASSWORD_MISSING);
		}

		if (preg_match("/[\'\",]/", $ipsec_psk)) {
            throw new ValidationException(IPSEC_LANG_ERRMSG_PASSWORD_INVALID);
		}

		# Delete any existing files
		# -------------------------

		$conf = new File(self::DIR_IPSEC . "/ipsec.$name.conf", true);
		$secrets = new File(self::DIR_IPSEC . "/ipsec.$name.secrets", true);

		try {
			if ($conf->Exists())
				$conf->Delete();

			if ($secrets->Exists())
				$secrets->Delete();

			# Create configuration files
			# --------------------------

			$conf->Create("root", "root", "0644");

			$secrets->Create("root", "root", "0600");

			# Write out configuration files
			# -----------------------------

			$conf->AddLines(
				"conn hqnet$name-satnet$name\n" .
				"\tleft=$ipsec_hq\n" .
				"\tleftnexthop=$ipsec_hq_hop\n" .
				"\tleftsubnet=$ipsec_hq_subnet\n" .
				"\tright=$ipsec_sat\n" .
				"\trightnexthop=$ipsec_sat_hop\n" .
				"\trightsubnet=$ipsec_sat_subnet\n" .
				"\n" .
				"conn hqgate$name-satnet$name\n" .
				"\tleft=$ipsec_hq\n" .
				"\tleftnexthop=$ipsec_hq_hop\n" .
				"\tright=$ipsec_sat\n" .
				"\trightnexthop=$ipsec_sat_hop\n" .
				"\trightsubnet=$ipsec_sat_subnet\n" .
				"\n" .
				"conn hqnet$name-satgate$name\n" .
				"\tleft=$ipsec_hq\n" .
				"\tleftnexthop=$ipsec_hq_hop\n" .
				"\tleftsubnet=$ipsec_hq_subnet\n" .
				"\tright=$ipsec_sat\n" .
				"\trightnexthop=$ipsec_sat_hop\n" .
				"\n" .
				"conn hqgate$name-satgate$name\n" .
				"\tleft=$ipsec_hq\n" .
				"\tleftnexthop=$ipsec_hq_hop\n" .
				"\tright=$ipsec_sat\n" .
				"\trightnexthop=$ipsec_sat_hop\n"
			);

			$secrets->AddLines("$ipsec_sat $ipsec_hq : PSK \"$ipsec_psk\"\n");
		} catch (Exception $e) {
            throw new EngineException ($e->GetMessage(), COMMON_ERROR);
        }
	}

	/**
	 * Returns settings on all static network-to-network connections.
	 *
	 * @returns array  a list of information on connections
	 */

	function GetConnectionData()
	{
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$connectioninfo = array();
		$connectionlist = array();

		$folder = new Folder(self::DIR_IPSEC, true);
		try {
			$listing = $folder->GetListing();

			$name = array();
			
			foreach ($listing as $file) {
				if (preg_match("/ipsec\.managed\./", $file)) {
					continue;
				} else if (preg_match("/ipsec.empty/", $file)) {
					continue;
				} else if (preg_match("/ipsec\.(.*)\.conf/", $file, $name)) {

					# Connection settings
					# -------------------

					$ipseccfg = new File(self::DIR_IPSEC . "/ipsec.$name[1].conf", true);
					$connectioninfo["name"] = $name[1];
					$connectioninfo["left"] = $ipseccfg->LookupValue("/\s*left\s*=\s*/");
					$connectioninfo["leftnexthop"] = $ipseccfg->LookupValue("/\s*leftnexthop\s*=\s*/");
					$connectioninfo["leftsubnet"] = $ipseccfg->LookupValue("/\s*leftsubnet\s*=\s*/");
					$connectioninfo["right"] = $ipseccfg->LookupValue("/\s*right\s*=\s*/");
					$connectioninfo["rightnexthop"] = $ipseccfg->LookupValue("/\s*rightnexthop\s*=\s*/");
					$connectioninfo["rightsubnet"] = $ipseccfg->LookupValue("/\s*rightsubnet\s*=\s*/");

					# Shared secret
					# -------------

					$secrets = new File(self::DIR_IPSEC . "/ipsec.$name[1].secrets", true);
					if (! $secrets->Exists())
						throw new EngineException(LOCALE_LANG_ERRMSG_NO_MATCH . " ($name[1])", COMMON_ERROR);

					$contents = $secrets->GetContents();
					$psk = preg_replace("/.*PSK\s*/", "", $contents);
					$connectioninfo["secret"]  = preg_replace("/\"/", "", $psk);

					# Add settings to connection list array
					# -------------------------------------

					$connectionlist[] = $connectioninfo;
				}
			}

		} catch (Exception $e) {
            throw new EngineException ($e->GetMessage(), COMMON_ERROR);
        }

		return $connectionlist;
	}

	/**
	 * Returns settings for a configured static VPN connection.
	 * @param  string  $name  nickname for IpSec tunnel
	 *
	 * @returns array  a list of settings for the connection
	 * @throws  EngineException
	 */

	function GetConnection($name)
	{
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$connectioninfo = array();

		try {
			# Connection settings
			# -------------------

			$ipseccfg = new File(self::DIR_IPSEC . "/ipsec.$name.conf", true);
			if (! $ipseccfg->Exists())
				throw new EngineException(LOCALE_LANG_ERRMSG_NO_MATCH . " ($name)", COMMON_ERROR);

			$connectioninfo["name"] = $name;
			$connectioninfo["left"] = $ipseccfg->LookupValue("/\s*left\s*=\s*/");
			$connectioninfo["leftnexthop"] = $ipseccfg->LookupValue("/\s*leftnexthop\s*=\s*/");
			$connectioninfo["leftsubnet"] = $ipseccfg->LookupValue("/\s*leftsubnet\s*=\s*/");
			$connectioninfo["right"] = $ipseccfg->LookupValue("/\s*right\s*=\s*/");
			$connectioninfo["rightnexthop"] = $ipseccfg->LookupValue("/\s*rightnexthop\s*=\s*/");
			$connectioninfo["rightsubnet"] = $ipseccfg->LookupValue("/\s*rightsubnet\s*=\s*/");

			# Shared secret
			# -------------

			$secrets = new File(self::DIR_IPSEC . "/ipsec.$name.secrets", true);
			if (! $secrets->Exists())
				throw new EngineException(LOCALE_LANG_ERRMSG_NO_MATCH . " ($name)", COMMON_ERROR);

			$contents = $secrets->GetContents();
			$psk = preg_replace("/.*PSK\s*/", "", $contents);
			$connectioninfo["secret"]  = preg_replace("/\"/", "", $psk);
		} catch (Exception $e) {
            throw new EngineException ($e->GetMessage(), COMMON_ERROR);
        }

		return $connectioninfo;
	}

	/**
	 * Deletes the named connection.
	 * @param  string  $name  nickname for IpSec tunnel
	 *
	 * @returns  void
	 */

	function DeleteConnection($name)
	{
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$secrets = new File(self::DIR_IPSEC . "/ipsec.$name.secrets", true);
		$conf = new File(self::DIR_IPSEC . "/ipsec.$name.conf", true);

		try {
			# Delete .conf file
			# -----------------
			if ($conf->Exists())
				$conf->Delete();

			# Delete .secrets file
			# --------------------
			if ($secrets->Exists())
				$secrets->Delete();
		} catch (Exception $e) {
            throw new EngineException ($e->GetMessage(), COMMON_ERROR);
        }
	}
}
// vim: syntax=php ts=4
?>
