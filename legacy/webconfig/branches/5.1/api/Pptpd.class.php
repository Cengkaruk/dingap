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
 * PPTP VPN class.
 *
 * @package Api
 * @subpackage Daemon
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('Engine.class.php');
require_once('Daemon.class.php');
require_once('File.class.php');
require_once('IfaceManager.class.php');
require_once('Network.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * PPTP VPN class.
 *
 * @package Api
 * @subpackage Daemon
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class Pptpd extends Daemon
{
	///////////////////////////////////////////////////////////////////////////////
	// M E M B E R S
	///////////////////////////////////////////////////////////////////////////////

	const FILE_CONFIG = "/etc/pptpd.conf";
	const FILE_OPTIONS = "/etc/ppp/options.pptpd";
	const FILE_STATS = "/proc/net/dev";
	const CONSTANT_PPPNAME = "pptp-vpn";
	const DEFAULT_KEY_SIZE = 128;

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Pptp constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct("pptpd");

		$network = new Network(); // need locale

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Returns list of active interfaces.
	 *
	 * @return array list of active PPTP connections
	 */

	function GetActiveList()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$ethlist = array();
		$ethinfolist = array();

		try {
			$ifs = new IfaceManager();
			$ethlist = $ifs->GetInterfaces(false, true);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		foreach ($ethlist as $eth) {
			if (! preg_match("/^pptp[0-9]/", $eth))
				continue;

			$ifdetails = array();

			try {
				$if = new Iface($eth);

				// TODO: YAPH - yet another PPPoE hack
				if ($if->IsConfigured())
					continue;

				$address = $if->GetLiveIp();
				$remote = $if->GetLiveIp();
			} catch (Exception $e) {
				throw new EngineException($e->GetMessage(), COMMON_WARNING);
			}

			$ifinfo = array();
			$ifinfo['name'] = $eth;
			$ifinfo['address'] = $address;

			$ethinfolist[] = $ifinfo;
		}

		return $ethinfolist;
    }

	/**
	 * Returns the DNS server.
	 *
	 * @return string DNS server
	 */

	function GetDnsServer()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		return $this->_GetOptionsParameter("ms-dns");
	}

	/**
	 * Returns the domain.
	 *
	 * @return string domain
	 */

	function GetDomain()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		return $this->_GetOptionsParameter("domain");
	}

	/**
	 * Returns encryption key size.
	 *
	 * @return int 40 or 128-bit key
	 */

	function GetKeySize()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$value = "";

		try {
			$file = new File(self::FILE_OPTIONS);
			$value = $file->LookupLine("/^require-mppe-[0-9]+/i");
			$value = preg_replace("/require-mppe-/", "", $value);
		} catch (FileNoMatchException $e) {
			return self::DEFAULT_KEY_SIZE;
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		return $value;
	}

	/**
	 * Returns interface statistics.
	 * 
	 * @return array interface statistics
	 */

	function GetInterfaceStatistics()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		// TODO: move this to the Iface class
		$stats = array();

		try {
			$file = new File(self::FILE_STATS);
			$lines = $file->GetContentsAsArray();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		$matches = array();

		foreach ($lines as $line) {
			if (preg_match("/^\s*([^:]*):(.*)/", $line, $matches)) {
				$items = preg_split("/\s+/", $matches[2]);
				$stats[$matches[1]]['received'] = $items[1];
				$stats[$matches[1]]['sent'] = $items[9];
			}
		}

		return $stats;
	}

	/**
	 * Returns the local IP settings.
	 *
	 * @return string local IP
	 */

	function GetLocalIp()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		return $this->_GetConfigParameter("localip");
	}

	/**
	 * Returns remote IP settings.
	 *
	 * @return string remote IP
	 */

	function GetRemoteIp()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		return $this->_GetConfigParameter("remoteip");
	}

	/**
	 * Returns the  WINS server.
	 *
	 * @return string WINS server
	 */

	function GetWinsServer()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		return $this->_GetOptionsParameter("ms-wins");
	}


	/**
	 * Sets the DNS server.
	 *
	 * @param string server DNS server
	 * @return void
	 */

	function SetDnsServer($server)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$network = new Network(); // Locale

		if (! $this->IsValidDnsServer($server))
			throw new ValidationException(NETWORK_LANG_DNS_SERVER . " - " . LOCALE_LANG_INVALID);

		$this->_SetOptionsParameter("ms-dns", $server);
	}


	/**
	 * Sets the domain.
	 *
	 * @param string $domain domain
	 * @return void
	 */

	function SetDomain($domain)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->IsValidDomain($domain))
			throw new ValidationException(NETWORK_LANG_DOMAIN . " - " . LOCALE_LANG_INVALID);

		$this->_SetOptionsParameter("domain", $domain);
	}


	/**
	 * Sets key size to 40 or 128 bits.
	 *
	 * @param int $keysize 40 or 128-bit key size
	 * @return void
	 */

	function SetKeySize($keysize)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->IsValidKeySize($keysize))
			throw new ValidationException(PPTPD_LANG_KEYSIZE . " - " . LOCALE_LANG_INVALID);

		try {
			$file = new File(self::FILE_OPTIONS);
			$match = $file->ReplaceLines("/^require-mppe-\d+/i", "require-mppe-$keysize\n");
			if (!$match)
				$file->AddLinesAfter("require-mppe-$keysize\n", "/^[^#]/");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Sets local IP.
	 *
	 * @param string $ip local IP
	 * @return void
	 */

	function SetLocalIp($ip)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->IsValidPptpIp($ip))
			throw new ValidationException(PPTPD_LANG_LOCALIP . " - " . LOCALE_LANG_INVALID);

		$this->_SetConfigParameter("localip", $ip);
	}

	/**
	 * Sets remote IP.
	 *
	 * @param string $ip remote IP
	 * @return void
	 */

	function SetRemoteIp($ip)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->IsValidPptpIp($ip))
			throw new ValidationException(PPTPD_LANG_REMOTEIP . " - " . LOCALE_LANG_INVALID);

		$this->_SetConfigParameter("remoteip", $ip);
	}


	/**
	 * Sets the WINS server.
	 *
	 * @param string $server WINS server
	 * @return void
	 */

	function SetWinsServer($server)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->IsValidWinsServer($server))
			throw new ValidationException(NETWORK_LANG_WINS_SERVER . " - " . LOCALE_LANG_INVALID);

		$this->_SetOptionsParameter("ms-wins", $server);
	}

	///////////////////////////////////////////////////////////////////////////////
	// P R I V A T E  M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Returns parameter from ppp options file.
	 *
	 * @access private
	 * @param string $parameter parameter in options file
	 * @return void
	 */

	function _GetOptionsParameter($parameter)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$value = "";

		try {
			$file = new File(self::FILE_OPTIONS);
			$value = $file->LookupValue("/^$parameter\s+/i");
		} catch (FileNoMatchException $e) {
			return;
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		return $value;
	}

	/**
	 * Returns parameter from PPTP configuration file.
	 *
	 * @access private
	 * @param string $parameter parameter in options file
	 * @return void
	 */

	function _GetConfigParameter($parameter)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$value = "";

		try {
			$file = new File(self::FILE_CONFIG);
			$value = $file->LookupValue("/^$parameter\s+/i");
		} catch (FileNoMatchException $e) {
			return;
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		return $value;
	}

	/**
	 * Set parameter in ppp options file.
	 *
	 * @access private
	 * @param string $parameter parameter in options file
	 * @param string $value value for given parameter
	 * @return void
	 */

	function _SetOptionsParameter($parameter, $value)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$file = new File(self::FILE_OPTIONS);
			if (empty($value)) {
				$file->DeleteLines("/^$parameter\s*/i");
			} else {
				$match = $file->ReplaceLines("/^$parameter\s*/i", "$parameter $value\n");
				if (!$match)
					$file->AddLinesAfter("$parameter $value\n", "/^[^#]/");
			}
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Set parameter in PPTP configuration file.
	 *
	 * @access private
	 * @param string $parameter parameter in options file
	 * @param string $value value for given parameter
	 * @return void
	 */

	function _SetConfigParameter($parameter, $value)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$file = new File(self::FILE_CONFIG);
			$match = $file->ReplaceLines("/^$parameter\s*/i", "$parameter $value\n");
			if (!$match)
				$file->AddLinesAfter("$parameter $value\n", "/^[^#]/");
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

	///////////////////////////////////////////////////////////////////////////////
	// V A L I D A T I O N   R O U T I N E S
	///////////////////////////////////////////////////////////////////////////////


	/**
	 * Validation routine for localip/remoteip.
	 *
	 * @return boolean true if PPTP IP format is valid
	 */

	function IsValidPptpIp($pptpip)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (empty($pptpip))
			return false;

		if (preg_match("/^([0-9\.\-]*)$/", $pptpip))
			return true;
		else
			return false;
	}

	/**
	 * Validation routine for WINS server.
	 *
	 * @return boolean true if WINS server is valid
	 */

	function IsValidWinsServer($winsserver)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (preg_match("/^([0-9\.]*)$/", $winsserver))
			return true;
		else
			return false;
	}


	/**
	 * Validation routine for DNS server.
	 *
	 * @return boolean true if DNS server is valid
	 */

	function IsValidDnsServer($dnsserver)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (preg_match("/^([0-9\.]*)$/", $dnsserver))
			return true;
		else
			return false;
	}


	/**
	 * Validation routine for domain.
	 *
	 * @return boolean true if domain is valid
	 */

	function IsValidDomain($domain)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (preg_match("/^([A-Za-z0-9\.\-]*)$/", $domain))
			return true;
		else
			return false;
	}


	/**
	 * Validation routine for keysize.
	 *
	 * @return boolean true if keysize is valid
	 */

	function IsValidKeySize($keysize)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (preg_match("/^(40|128)$/", $keysize))
			return true;
		else
			return false;
	}
}

// vim: syntax=php ts=4
?>
