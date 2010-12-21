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
clearos_load_library('network/NetworkUtils');

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
	 * @var bool is_loaded
	 */

	protected $is_loaded = FALSE;

	/**
	 * @var array hosts_array
	 */

	protected $hostdata = array();

	const FILE_CONFIG = '/etc/hosts';

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Add an entry to the /etc/hosts file.
	 *
	 * @param string $ip IP address
	 * @param string $hostname canonical hostname
	 * @param string $aliases array of aliases
	 * @returns void
	 * @throws  ValidationException, EngineException
	 */

	public function AddEntry($ip, $hostname, $aliases = array())
	{
		ClearOsLogger::Profile(__METHOD__, __LINE__);

		// Validate
		//---------

		if ($error = $this->ValidateIp($ip))
			throw new ValidationException($error);

		if ($error = $this->ValidateHostname($hostname))
			throw new ValidationException($error);

		foreach ($aliases as $alias) {
			if ($error = $this->ValidateAlias($alias))
				throw new ValidationException($error);
		}

		if ($this->EntryExists($ip))
			throw new ValidationException('Entry already exists for this IP'); // FIXME: translate

		// Add
		//----

		$this->_LoadEntries();

		try {
			$file = new File(self::FILE_CONFIG);
			$file->AddLines("$ip $hostname " . implode(' ', $aliases) . "\n");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		// Force a re-read of the data
		$this->is_loaded = FALSE;
	}

	/**
	 * Delete an entry from the /etc/hosts file.
	 *
	 * @param  string $ip  IP address
	 * @returns void
	 * @throws  ValidationException, EngineException
	 */

	public function DeleteEntry($ip)
	{
		ClearOsLogger::Profile(__METHOD__, __LINE__);

		// Validate
		//---------

		if ($error = $this->ValidateIp($ip))
			throw new ValidationException($error);

		// Delete
		//-------

		try {
			$file = new File(self::FILE_CONFIG);
			$hosts = $file->DeleteLines('/^' . $ip . '\s/i');
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		// Force a reload
		$this->is_loaded = FALSE;
	}

	/**
	 * Updates hosts entry for given IP address
	 *
	 * @param string $ip IP address
	 * @param string $hostname caononical hostname
	 * @param array $aliases aliases
	 * @returns void
	 * @throws ValidationException, EngineException
	 */

	public function EditEntry($ip, $hostname, $aliases = array())
	{
		ClearOsLogger::Profile(__METHOD__, __LINE__);

		// Validate
		//---------

		if ($error = $this->ValidateIp($ip))
			throw new ValidationException($error);

		if ($error = $this->ValidateHostname($hostname))
			throw new ValidationException($error);

		foreach ($aliases as $alias) {
			if ($error = $this->ValidateAlias($alias))
				throw new ValidationException($error);
		}

		if (! $this->EntryExists($ip))
			throw new ValidationException('No entry exists for this IP'); // FIXME: translate

		// Update
		//-------

		try {
			$file = new File(self::FILE_CONFIG);
			$file->ReplaceLines("/^$ip\s+/i", "$ip $hostname " . implode(' ', $aliases) . "\n");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		// Force a reload
		$this->is_loaded = FALSE;
	}

	/**
	 * Returns the hostname and aliases for the given IP address.
	 *
	 * @param string $ip IP address
	 * @return array an array containing the hostname and aliases
	 * @throws EngineException
	 */

	public function GetEntry($ip)
	{
		ClearOsLogger::Profile(__METHOD__, __LINE__);

		// Validate
		//---------

		if ($error = $this->ValidateIp($ip))
			throw new ValidationException($error);

		// Get Entry
		//----------

		$this->_LoadEntries();

		foreach ($this->hostdata as $real_ip => $entry) {
			if ($entry['ip'] == $ip)
				return $entry;
		}

		throw new ValidationException("No entry exists for this IP");  // FIXME: translate
	}

	/**
	 * Returns information in the /etc/hosts file in an array.
	 *
	 * The array is indexed on IP, and contains an array of associated hosts.
	 *
	 * @return  array  list of host information
	 * @throws EngineException
	 */

	public function GetEntries()
	{
		ClearOsLogger::Profile(__METHOD__, __LINE__);

		$this->_LoadEntries();

		return $this->hostdata;
	}

	/**
	 * Returns the IP address for the given hostname.
	 *
	 * @param string $hostname hostname
	 * @return string IP address if hostname exists, NULL if it does not
	 * @throws EngineException
	 */

	public function GetIpByHostname($hostname)
	{
		ClearOsLogger::Profile(__METHOD__, __LINE__);

		// Validate
		//---------

		if ($error = $this->ValidateHostname($hostname))
			throw new ValidationException($error);

		// Get Entry
		//----------

		$this->_LoadEntries();

		foreach ($this->hostdata as $real_ip => $entry) {
			if ($entry['hostname'] === $hostname)
				return $entry['ip'];

			foreach ($entry['aliases'] as $alias) {
				if ($alias === $hostname)
					return $entry['ip'];
			}
		}

		return NULL;
	}

	/**
	 * Checks to see if entry exists.
	 *
	 * @param string $ip IP address
	 * @return boolean true if entry exists
	 * @throws EngineException
	 */

	public function EntryExists($ip)
	{
		ClearOsLogger::Profile(__METHOD__, __LINE__);

		// Validate
		//---------

		if ($error = $this->ValidateIp($ip))
			throw new ValidationException($error);

		// Get Entry
		//----------

		$this->_LoadEntries();

		foreach ($this->hostdata as $real_ip => $entry) {
			if ($entry['ip'] == $ip)
				return TRUE;
		}

		return FALSE;
	}

	///////////////////////////////////////////////////////////////////////////////
	// V A L I D A T I O N   R O U T I N E S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Validates a hostname alias.
	 *
	 * @param string $alias alias
	 * @return string error message if alias is invalid
	 */

	public function ValidateAlias($alias)
	{
		ClearOsLogger::Profile(__METHOD__, __LINE__);

		$network = new NetworkUtils();

		return $network->ValidateHostnameAlias($alias);
	}

	/**
	 * Validates a hostname.
	 *
	 * @param string $hostname hostname
	 * @return string error message if hostname is invalid
	 */

	public function ValidateHostname($hostname)
	{
		ClearOsLogger::Profile(__METHOD__, __LINE__);

		$network = new NetworkUtils();

		return $network->ValidateHostname($hostname);
	}

	/**
	 * Validates IP address entry.
	 *
	 * @param string $hostname hostname
	 * @return string error message if hostname is invalid
	 */

	public function ValidateIp($ip)
	{
		ClearOsLogger::Profile(__METHOD__, __LINE__);

		$network = new NetworkUtils();

		return $network->ValidateIp($ip);
	}

	///////////////////////////////////////////////////////////////////////////////
	// P R I V A T E   M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Loads host info from /etc/hosts.
	 *
	 * @access private
	 * @throws EngineException
	 */

	protected function _LoadEntries()
	{
		ClearOsLogger::Profile(__METHOD__, __LINE__);

		if ($this->is_loaded)
			return;

		try {
			$file = new File(self::FILE_CONFIG);
			$contents = $file->GetContentsAsArray();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		$hostdata = array();

		foreach($contents as $line) {

			$entries = preg_split('/[\s]+/', $line);
			$ip = array_shift($entries);

			// TODO: IPv6 won't work with ip2long

			if ($this->ValidateIp($ip))
				continue;

			// Use long IP for proper sorting
			$ip_real = ip2long($ip);

			$this->hostdata[$ip_real]['ip'] = $ip;
			$this->hostdata[$ip_real]['hostname'] = array_shift($entries);
			$this->hostdata[$ip_real]['aliases'] = $entries;
		}

		ksort($this->hostdata);

		$this->is_loaded = TRUE;
	}
}

// vim: syntax=php ts=4
?>
