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
 * Ethers class.
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
require_once('Network.class.php');
require_once('Locale.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Ethers class.
 *
 * @package Api
 * @subpackage Network
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class Ethers extends Network
{
	const FILE_CONFIG = '/etc/ethers';

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Ethers constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		parent::__construct();

		$this->ResetEthers();

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Create a new /etc/ethers file.
	 *
	 * @param boolean $force delete the existing file if true
	 * @return void
	 */

	function ResetEthers($force = false)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$file = new File(self::FILE_CONFIG);

		if ($force == true) {
			if ($file->Exists())
				$file->Delete();
		}

		if (! $file->Exists()) {
			$file->Create('root', 'root', '0644');

			$default  = "# This is an auto-generated file. Please do NOT edit\n";
			$default .= "# Comments are used to aid in maintaining your hosts\n";
			$default .= "# file\n";

			$file->AddLines($default);
		}
	}

	/**
	 * Add a MAC/IP pair to the /etc/ethers file.
	 *
	 * @param string $mac MAC address
	 * @param string $ip IP address
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	function AddEther($mac, $ip)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$isvalid = true;
		$network = new Network();

		if (! $network->IsValidMac($mac)) {
			$this->AddValidationError(implode($network->GetValidationErrors(true)), __METHOD__, __LINE__);
			$isvalid = false;
		}

		if (! $network->IsValidIp($ip)) {
			$this->AddValidationError(implode($network->GetValidationErrors(true)), __METHOD__, __LINE__);
			$isvalid = false;
		}

		if (! $isvalid)
			throw new ValidationException(LOCALE_LANG_INVALID);

		$file = new File(self::FILE_CONFIG);

		try {
			$contents = $file->GetContentsAsArray();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		// Already exists?
		foreach ($contents as $key => $line) {
			if (preg_match('/' . $mac . '/', $line))
				throw new EngineException(ETHERS_LANG_MAC_ALREADY_EXISTS, COMMON_ERROR);
		}

		// Add
		$contents[] = $mac . ' ' . $ip;
		$file->DumpContentsFromArray($contents);
	}

	/**
	 * Delete a MAC/HOSTNAME pair from the /etc/ethers file.
	 *
	 * @param string $mac MAC address
	 * @return void
	 */

	function DeleteEther($mac)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if ($this->IsValidMac($mac) === false)
			return;

		$file = new File(self::FILE_CONFIG);
		$contents = $file->GetContentsAsArray();

		$write_out = false;
		foreach ($contents as $key => $line) {
			if (preg_match('/' . $mac . '/', $line)) {
				unset($contents[$key]);
				$write_out = true;
			}
		}

		if ($write_out)
			$file->DumpContentsFromArray($contents);
	}

	/**
	 * Returns the HOSTNAME for the given MAC address.
	 *
	 * @param string $mac MAC address
	 * @return string hostname or null
	 */

	function GetHostnameByMac($mac)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if ($this->IsValidMac($mac) === false)
			return;

		$ethers = $this->GetEthers();

		if (! isset($ethers[$mac]))
			$ret = null;
		else
			$ret = $ethers[$mac];

		if ($this->IsValidHostname($ret) == false)
			$ret = null;

		return $ret;
	}

	/**
	 * Returns information in the /etc/ethers file in an array.
	 *
	 * The array is indexed on MAC with HOSTNAMEs as values.
	 *
	 * @return array list of ether information
	 */

	function GetEthers()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$file = new File(self::FILE_CONFIG);
		$contents = $file->GetContentsAsArray();

		if (! is_array($contents)) {
			$this->ResetEthers(true);
			$contents = $file->GetContentsAsArray();
			if (! is_array($contents)) {
				throw new EngineException(LOCALE_LANG_ERRMSG_PARSE_ERROR, COMMON_ERROR);
			}
		}

		$ethers = array();
		foreach ($contents as $line) {
			// skip comment lines
			if (preg_match('/^[\s]*#/', $line))
				continue;
			$parts = preg_split('/[\s]+/', $line);
			if ($this->isValidMac($parts[0]) && $parts[1] != '')
				$ethers[$parts[0]] = $parts[1];
		}
		return $ethers;
	}

	/**
	 * Returns the MAC address for the given HOSTNAME.
	 *
	 * @param string $hostname hostname
	 * @return string MAC address
	 */

	function GetMacByHostname($hostname)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if ($this->IsValidHostname($hostname) == false) {
			$errors = $this->GetValidationErrors();
			throw new EngineException($errors[0], COMMON_ERROR);
		}

		$ethers = $this->GetEthers();
		foreach ($ethers as $mac => $host)
			if (strcasecmp($hostname, $host) == 0)
				return $mac;
		return;
	}

	/**
	 * Updates HOSTNAME for a given MAC address.
	 *
	 * @param string $mac MAC address
	 * @param string $hostname hostname
	 * @return void
	 */
	
	function UpdateEther($mac, $hostname)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if ($this->IsValidMac($mac) === false)
			return;

		if ($this->IsValidHostname($hostname) == false) {
			$errors = $this->GetValidationErrors();
			throw new EngineException($errors[0], COMMON_ERROR);
		}


		$file = new File(self::FILE_CONFIG);
		$contents = $file->GetContentsAsArray();

		$write_out = false;
		foreach ($contents as $key => $line) {
			if (preg_match('/' . $mac . '/', $line)) {
				$contents[$key] = $mac . ' ' . $hostname;
				$write_out = true;
			}
		}

		// Add
		if ($write_out)
			$file->DumpContentsFromArray($contents);
	}

	///////////////////////////////////////////////////////////////////////////////
	// P R I V A T E   M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * @access private
	 */

	function __destruct()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		parent::__destruct();
	}

}

// vim: syntax=php ts=4
?>
