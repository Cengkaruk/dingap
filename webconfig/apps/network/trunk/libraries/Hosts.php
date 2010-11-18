<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2003-2010 ClearFoundation
//
///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Lesser General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

/**
 * Hosts utility.
 *
 * @package ClearOS
 * @subpackage API
 * @author {@link http://www.clearfoundation.com/ ClearFoundation}
 * @license http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @copyright Copyright 2003-2010 ClearFoundation
 */

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = isset($_ENV['CLEAROS_BOOTSTRAP']) ? $_ENV['CLEAROS_BOOTSTRAP'] : '/usr/clearos/framework/shared';
require_once($bootstrap . '/bootstrap.php');

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('base');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

clearos_load_library('base/File');
clearos_load_library('network/Network');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Hosts.
 *
 * The hosts class conforms to RFC 952.
 *
 * @package ClearOS
 * @subpackage API
 * @author {@link http://www.clearfoundation.com/ ClearFoundation}
 * @license http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @copyright Copyright 2003-2010 ClearFoundation
 */

class Hosts extends Engine
{

	///////////////////////////////////////////////////////////////////////////////
	// V A R I A B L E S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * @var bool isloaded
	 */

	protected $loaded = null;

	/**
	 * @var array hosts_array
	 */

	protected $hostdata = null;

	const FILE_CONFIG = '/etc/hosts';

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Hosts constructor.
	 *
	 * @return void
	 */

	public function __construct()
	{
		ClearOsLogger::Profile(__METHOD__, __LINE__);

		$this->hostdata = array();
		$this->loaded = false;

		parent::__construct();

	}

	/**
	 * Returns information in the /etc/hosts file in an array.
	 * The array is indexed on IP, and contains an array of associated hosts.
	 *
	 * @return  array  list of host information
	 * @throws EngineException
	 */

	public function GetHosts()
	{
		ClearOsLogger::Profile(__METHOD__, __LINE__);

		if (! $this->loaded)
			$this->_LoadHostList();

		return $this->hostdata;
	}

	/**
	 * Returns the IP address for the given hostname.
	 *
	 * @param  string $hostname  Hostname
	 * @return  string ip
	 * @throws EngineException
	 */

	public function GetIpByHostname($hostname)
	{
		ClearOsLogger::Profile(__METHOD__, __LINE__);

		if (! $this->loaded)
			$this->_LoadHostList();

		foreach ($this->hostdata as $ip => $hostnames) {
			foreach ($hostnames as $hst) {
				if (strcasecmp($hostname, $hst) == 0)
					return $ip;
			}
		}

		return;
	}

	/**
	 * Returns the hostname for the given IP address.
	 *
	 * @param string $ip IP address
	 * @return array an array containing hostnames
	 * @throws EngineException
	 */

	public function GetHostnamesByIp($ip)
	{
		ClearOsLogger::Profile(__METHOD__, __LINE__);

		if (! $this->loaded)
			$this->_LoadHostList();

		$hostnames = array();

		if (isset($this->hostdata[$ip]))
			$hostnames = $this->hostdata[$ip];

		return $hostnames;
	}

	/**
	 * Add a host to the /etc/hosts file.
	 *
	 * @param  string  $ip  IP address
	 * @param  string  $hostnames  array of hostnames
	 * @returns void
	 * @throws  ValidationException, EngineException
	 */

	public function AddHost($ip, $hostnames)
	{
		ClearOsLogger::Profile(__METHOD__, __LINE__);

		if (! $this->loaded)
			$this->_LoadHostList();

		// Validation
		$network = new Network();

		if (! $network->IsValidIp($ip))
			throw new ValidationException(NETWORK_LANG_IP . ' (' . $ip . ') - ' . LOCALE_LANG_INVALID);

		// Already exists
		$existing = $this->GetHostnamesByIp($ip);

		if (count($existing) > 0)
			throw new ValidationException($ip . " - " . LOCALE_LANG_ALREADY_EXISTS);

		// Add
		if (is_array($hostnames)) {
			foreach ($hostnames as $index => $host) {
				if ($network->IsValidHostnameAlias($host)) {
					$duplicate = $this->GetIpByHostname($host);

					if ($duplicate)
						throw new EngineException($duplicate . '/' . $host . ' - ' . LOCALE_LANG_ALREADY_EXISTS, COMMON_ERROR);
				} else {
					throw new ValidationException(HOSTS_LANG_HOSTNAME . " ($host) - " . LOCALE_LANG_INVALID);
				}
			}
		} else {
			// If adding a FQDN, then add the simple hostname also
			$parts = explode('.', $hostnames);

			if (isset($parts[1])) {
				$hostnames = array();
				$hostnames[] = implode('.', $parts);
				$hostnames[] = $parts[0];
				// Recusively add both hosts
				$this->AddHost($ip, $hostnames);
				return;
			} else {
				// Hostnames must have a period.
				throw new ValidationException(HOSTS_LANG_HOSTNAME . ' (' . $hostnames . ') - ' . LOCALE_LANG_INVALID);
			}
		}

		try {
			$file = new File(self::FILE_CONFIG);
			$file->AddLines($ip . ' ' . trim(implode(' ', $hostnames)) . "\n");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		// Force a re-read of the data
		$this->loaded = false;
	}

	/**
	 * Updates hostnames for a given IP address.
	 *
	 * @param   string  $ip  IP address
	 * @param   array  $hostnames  array of hostnames
	 * @returns void
	 * @throws  ValidationException, EngineException
	 */

	public function UpdateHost($ip, $hostnames)
	{
		ClearOsLogger::Profile(__METHOD__, __LINE__);

		// Validation
		$network = new Network();

		if (! $network->IsValidIp($ip))
			throw new ValidationException(NETWORK_LANG_IP . ' (' . $ip . ') - ' . LOCALE_LANG_INVALID);

		if (! is_array($hostnames))
			$hostnames = array($hostnames);

		foreach ($hostnames as $host) {
			if (! $network->IsValidHostnameAlias($host))
				throw new ValidationException(HOSTS_LANG_HOSTNAME . " ($host) - " . LOCALE_LANG_INVALID);
		}

		// If there are no hostnames associated w/the ip then delete it
		if (count(array_filter($hostnames)) == 0)
			$this->DeleteHost($ip);

		try {
			$file = new File(self::FILE_CONFIG);
			$file->ReplaceLines("/^$ip\s+/i", "$ip  " . implode(' ', $hostnames) . "\n");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		// Force a reload
		$this->loaded = false;
	}

	/**
	 * Delete a host from the /etc/hosts file.
	 *
	 * @param  string $ip  IP address
	 * @returns void
	 * @throws  ValidationException, EngineException
	 */

	public function DeleteHost($ip)
	{
		ClearOsLogger::Profile(__METHOD__, __LINE__);

		// Validation
		$network = new Network();

		if (! $network->IsValidIp($ip))
			throw new ValidationException(NETWORK_LANG_IP . ' (' . $ip . ') - ' . LOCALE_LANG_INVALID);

		try {
			$file = new File(self::FILE_CONFIG);
			$hosts = $file->DeleteLines('/^' . $ip . '\s/i');
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		// Force a reload
		$this->loaded = false;
	}

	///////////////////////////////////////////////////////////////////////////////
	// P R I V A T E   M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * @access private
	 */

	public function __destruct()
	{
		ClearOsLogger::Profile(__METHOD__, __LINE__);

		parent::__destruct();
	}

	/**
	 * Load host info from /etc/hosts into array.
	 * @private
	 * @throws  EngineException
	 */

	private function _LoadHostList()
	{
		ClearOsLogger::Profile(__METHOD__, __LINE__);

		if (! $this->loaded) {
			// Get list of descriptions

			try {
				$file = new File(self::FILE_CONFIG);
				$contents = $file->GetContentsAsArray();
				$hostdata = array();
				foreach($contents as $line) {
					if (preg_match('/^#/', $line))
						continue;

					$parts = preg_split('/[\s]+/', $line);

					if ($parts[0]) {
						$network = new Network;
						if ($network->isValidIp($parts[0]) && $parts[1] != '')
							$hostdata[$parts[0]] = array_slice($parts, 1);
					}
				}

				ksort($hostdata);
				$this->hostdata = $hostdata;
				$this->loaded = true;
			} catch (Exception $e) {
				throw new EngineException($e->GetMessage(), COMMON_ERROR);
			}
		}
	}
}

// vim: syntax=php ts=4
?>
