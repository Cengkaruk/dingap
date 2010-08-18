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
 * Hostname class.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('File.class.php');
require_once('Network.class.php');

///////////////////////////////////////////////////////////////////////////////
// E X C E P T I O N  C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Hostname exception.
 *
 * @package Api
 * @subpackage Exception
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class HostnameException extends EngineException
{
	/**
	 * HostnameException constructor.
	 *
	 * @param string $message error message
	 * @param int $code error code
	 */

	public function __construct($message, $code)
	{
		parent::__construct($message, $code);
	}
}

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Hostname class.
 *
 * @package Api
 * @subpackage Network
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class Hostname extends Engine
{
	const FILE_CONFIG = "/etc/sysconfig/network";
	const CMD_HOSTNAME = "/bin/hostname";

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Locale constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct();

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Returns host name from the gethostname system call.
	 *
	 * @return string host name
	 * @throws HostnameException
	 */

	public function GetActual()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$shell = new ShellExec();

		try {
			$exitcode = $shell->Execute(self::CMD_HOSTNAME, "", false);
		} catch (Exception $e) {
			throw new HostnameException($e->GetMessage(), COMMON_ERROR);
		}

		$output = $shell->GetOutput();

		if (! isset($output[0]))
			throw new HostnameException(LOCALE_LANG_ERRMSG_WEIRD, COMMON_ERROR);
		else if ($exitcode != 0)
			throw new HostnameException($output[0], COMMON_ERROR);
			
		return $output[0];
	}


	/**
	 * Returns host name for configuration file.
	 *
	 * @return string host name
	 * @throws HostnameException
	 */

	public function Get()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$file = new File(self::FILE_CONFIG);

		try {
			$hostname = $file->LookupValue("/^HOSTNAME=/");
		} catch (Exception $e) {
			throw new HostnameException($e->GetMessage(), COMMON_ERROR);
		}

		$hostname = preg_replace("/\"/", "", $hostname);

		return $hostname;
	}


	/**
	 * Returns configured domain name.
	 *
	 * If hostname is two parts or less (eg example.com
	 * or example), we just return the hostname.  If hostname has more than
	 * two parts (eg www.example.com or www.eastcoast.example.com) it
	 * strips the first part.
	 *
	 * @return string domain name
	 * @throws HostnameException
	 */

	public function GetDomain()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$hostname = $this->Get();

		if (substr_count($hostname, ".") < 2)
			return $hostname;

		$domain = preg_replace("/^([\w\-]*)\./", "", $hostname);

		return $domain;
	}


	/**
	 * Returns true of hostname can resolve.
	 *
	 * @return boolean true if look host can resolve itself
	 */

	public function IsLookupable()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$hostname = $this->GetActual() . ".";

		$retval = gethostbyname($hostname);

		if ($retval == $hostname)
			return false;
		else
			return true;
	}


	/**
	 * Sets host name.
	 *
	 * Hostname must have at least one period.
	 *
	 * @param string $hostname host name
	 * @return void
	 * @throws HostnameException, ValidationException
	 */

	public function Set($hostname)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		// Validate
		//---------

		$network = new Network();

		if (! $network->IsValidHostname($hostname))
			throw new ValidationException(implode($network->GetValidationErrors(true)));

		// Update tag if it exists
		//------------------------

		$file = new File(self::FILE_CONFIG);

		try {
			$match = $file->ReplaceLines("/^HOSTNAME=/", "HOSTNAME=\"$hostname\"\n");
		} catch (Exception $e) {
			throw new HostnameException($e->GetMessage(), COMMON_ERROR);
		}

		// If tag does not exist, add it
		//------------------------------

		if (!$match) {
			try {
				$file->AddLines("HOSTNAME=\"$hostname\"\n");
			} catch (Exception $e) {
				throw new HostnameException($e->GetMessage(), COMMON_ERROR);
			}
		}

		// Run hostname command...
		//------------------------

		$shell = new ShellExec();

		try {
			$exitcode = $shell->Execute(self::CMD_HOSTNAME, "$hostname", true);
		} catch (Exception $e) {
			throw new HostnameException($e->GetMessage(), COMMON_ERROR);
		}

		if ($exitcode != 0)
			throw new HostnameException($shell->GetFirstOutputLine(), COMMON_ERROR);
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
