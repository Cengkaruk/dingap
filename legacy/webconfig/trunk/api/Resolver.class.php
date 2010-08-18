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
 * The Resolver class manages the /etc/resolv.conf file.
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

require_once('File.class.php');
require_once('Folder.class.php');
require_once('Network.class.php');
require_once('ShellExec.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Resolver class.
 *
 * Provides tools for editing /etc/resolv.conf.
 *
 * @package Api
 * @subpackage Network
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class Resolver extends Network
{
	///////////////////////////////////////////////////////////////////////////////
	// V A R I A B L E S
	///////////////////////////////////////////////////////////////////////////////

	const FILE_CONFIG = "/etc/resolv.conf";
	const CONST_TEST_HOST = 'sdn1.clearsdn.com';

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Resolver constructor.
	 *
	 * @return void
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		parent::__construct();

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * A generic method to grab information from /etc/resolv.conf.
	 *
	 * @access private
	 * @param string $key parameter - eg domain
	 * @return string value for given key
	 * @throws EngineException
	 */

	function GetParameter($key)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$file = new File(self::FILE_CONFIG);

		if (! $file->Exists())
			return "";

		try {
			$value = $file->LookupValue("/^$key\s+/");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		return $value;
	}

	/**
	 * Returns domain.
	 *
	 * @return string domain
	 * @throws EngineException
	 */

	function GetLocalDomain()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$domain = $this->GetParameter('domain');
		return $domain;
	}

	/**
	 * Returns DNS servers.
	 *
	 * @return array DNS servers in an array
	 * @throws EngineException
	 */

	function GetNameservers()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$file = new File(self::FILE_CONFIG);

		if (! $file->Exists())
			return array();

		// Fill the array
		//---------------

		$nameservers = array();

		$lines = $file->GetContentsAsArray();

		try {
			foreach ($lines as $line) {
				if (preg_match('/^nameserver\s+/', $line))
					array_push($nameservers, preg_replace('/^nameserver\s+/', '', $line));
			}
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		return $nameservers;
	}

	/**
	 * Returns search parameter.
	 *
	 * @return string search
	 * @throws EngineException
	 */

	function GetSearch()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$search = $this->GetParameter('search');
		return $search;
	}

	/**
	 * Generic set parameter for /etc/resolv.conf.
	 *
	 * @access private
	 * @param string $key parameter that is being replaced
	 * @param string $replacement the full replacement (could be multiple lines)
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	function SetParameter($key, $replacement)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$file = new File(self::FILE_CONFIG);

			// Create file if it does not exist
			//---------------------------------

			if (! $file->Exists())
				$file->Create('root', 'root', '0644');

			$file->ReplaceLines('/^' . $key . '/', '');

			// Add domain (if it exists)
			//--------------------------

			if ($replacement) {
				if (is_array($replacement)) {
					foreach ($replacement as $line)
					$file->AddLines($line . "\n");
				} else {
					$file->AddLines($replacement . "\n");
				}
			}
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage());
		}
	}

	/**
	 * Sets domain. 
	 *
	 * Setting the domain to blank will remove the line from /etc/resolv.conf.
	 *
	 * @param string $domain domain
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	function SetLocalDomain($domain)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Validate
		//---------

		if (! $this->IsValidLocalDomain($domain))
			throw new ValidationException(RESOLVER_LANG_ERRMSG_DOMAIN_INVALID);

		// Set the parameter
		//------------------

		if ($domain)
			$this->SetParameter('domain', 'domain ' . $domain);
		else
			$this->SetParameter('domain', '');
	}

	/**
	 * Sets DNS servers.
	 *
	 * Setting the DNS servers to blank will remove the line from /etc/resolv.conf.
	 *
	 * @param array $nameservers DNS servers
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	function SetNameservers($nameservers)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! is_array($nameservers))
			$nameservers = array($nameservers);

		// Validate
		//---------

		$thelist = Array();

		foreach ($nameservers as $server) {
			$server = trim($server);

			if (! $server) {
				continue;
			} else if (! $this->IsValidIp($server)) {
				throw new ValidationException(RESOLVER_LANG_ERRMSG_NAMESERVERS_INVALID);
			} else {
				$thelist[] = 'nameserver ' . $server;
			}
		}

		if (count($thelist) > 0)
			$this->SetParameter('nameserver', $thelist);
		else
			$this->SetParameter('nameserver', '');
	}

	/**
	 * Sets search parameter.
	 *
	 * Setting the search to blank will remove the line from /etc/resolv.conf.
	 *
	 * @param string $search search
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	function SetSearch($search)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Validate
		//---------

		if (! $this->IsValidSearch($search))
			throw new ValidationException(RESOLVER_LANG_ERRMSG_SEARCH_INVALID);

		// Set the parameter
		//------------------

		if ($search)
			$this->SetParameter('search', 'search ' . $search);
		else
			$this->SetParameter('search', '');

	}

	/**
	 * Perform DNS lookup.
	 *
	 * Performs a test DNS lookup using an external DNS resolver.  The PHP
	 * system will cache the contents of /etc/resolv.conf.  That's leads to
	 * false DNS lookup errors when DNS servers happen to change.
	 *
	 * @param string $domain domain name to look-up
	 * @param int $timeout number of seconds until we time-out
	 * @return array DNS test results
	 * @throws EngineException, ValidationException
	 */

	function TestLookup($domain = CONST_TEST_HOST, $timeout = 10)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$result = array();
		$shell = new ShellExec();

		try {
			$servers = $this->GetNameservers();

			foreach ($servers as $server) {
				if ($shell->Execute("/usr/bin/dig", "@$server $domain +time=$timeout") == 0)
					return true;
			}
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}

		return false;
	}

	/**
	 * Perform DNS test.
	 *
	 * Performs a DNS look-up on each name server.
	 *
	 * @param string $domain domain name to look-up
	 * @param int $timeout number of seconds until we time-out
	 * @return array DNS test results
	 * @throws EngineException, ValidationException
	 */

	function TestNameservers($domain = CONST_TEST_HOST, $timeout = 10)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$result = array();
		$shell = new ShellExec();

		try {
			$servers = $this->GetNameservers();

			foreach ($servers as $server) {
				if ($shell->Execute("/usr/bin/dig", "@$server $domain +time=$timeout") == 0) {
					$result[$server]["success"] = true;
				} else {
					$result[$server]["success"] = false;
				}
			}
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}

		return $result;
	}

	///////////////////////////////////////////////////////////////////////////////
	// V A L I D A T I O N   M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Validation routine for domain.
	 *
	 * @param string $domain domain
	 * @return boolean true if domain is valid
	 */

	function IsValidLocalDomain($domain)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $domain)
			return true;

		if ($this->IsValidDomain($domain))
			return true;

		return false;
	}


	/**
	 * Validation routine for search.
	 *
	 * @param string $search search
	 * @return boolean true if search is valid
	 */

	function IsValidSearch($search)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $search)
			return true;

		if ($this->IsValidDomain($search))
			return true;

		return false;
	}

	///////////////////////////////////////////////////////////////////////////////
	// P R I V A T E   M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

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
