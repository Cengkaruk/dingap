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
 * SnortSam intrusion prevention class.
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

require_once('Daemon.class.php');
require_once('File.class.php');
require_once('ShellExec.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * SnortSam intrusion prevention class.
 *
 * @package Api
 * @subpackage Daemon
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class SnortSam extends Daemon
{
	///////////////////////////////////////////////////////////////////////////////
	// M E M B E R S
	///////////////////////////////////////////////////////////////////////////////

	const FILE_CONFIG = "/etc/snortsam.conf";
	const FILE_STATE = "/var/db/snortsam.state";
	const FILE_WHITELIST = "/etc/snortsam/webconfig-whitelist.conf";
	const COMMAND_STATE = "/usr/bin/snortsam-state";

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * SnortSam constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct("snortsam");

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Adds IP address to white list.
	 *
	 * @param string $ip IP address
	 * @returns void
	 * @throws EngineException, ValidationException
	 */

	function AddWhitelistIp($ip)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$list = array();

		$file = new File(self::FILE_WHITELIST);

		try {
			if (! $file->Exists())
				$file->Create("root", "root", "0640");
			else
				$list = $this->GetWhitelist();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		foreach ($list as $entry) {
			if ($ip == $entry)
				throw new DuplicateException(LOCALE_LANG_ERRMSG_DUPLICATE_VALUE . " - " . $ip);
		}

		try {
			$file->AddLines("dontblock $ip\n");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Delete a blocked host.
	 *
	 * @param string $crc CRC of blocked host to delete (can also be 'all')
	 * @return void
	 */

	function DeleteBlockedCrc($crc)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$shell = new ShellExec();
			$shell->Execute(self::COMMAND_STATE, "-D $crc", true);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Delete a blocked host.
	 *
	 * @param string $ip IP address to unblock
	 * @return void
	 */

	function DeleteBlockedIp($ip)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$blocklist = $this->GetBlocklist();

		foreach ($blocklist as $key => $info) {
			if ($info['peerip'] == $ip) {
				$this->DeleteBlockedCrc($info['crc']);
				return;
			}
		}
	}

	/**
	 * Deletes IP address from white list.
	 *
	 * @param string $ip IP address
	 * @returns void
	 * @throws EngineException, ValidationException
	 */

	function DeleteWhitelistIp($ip)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$file = new File(self::FILE_WHITELIST);

		$ip = preg_quote($ip, "/");

		try {
			$file->DeleteLines("/^dontblock\s+$ip$/");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Returns the current block list.
	 *
	 * @return array information on blocked IPs 
	 * @throws EngineException
	 */

	function GetBlockList()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$file = new File(self::FILE_STATE);

			if (! $file->Exists())
				return;
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		$lines = array();

		try {
			$shell = new ShellExec();
			$shell->Execute(self::COMMAND_STATE, " -q -d :", true);
			$lines = $shell->GetOutput();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		$blockinfo = array();
		$blocklist = array();

		foreach ($lines as $line) {
			if (!strlen($line))
				continue;

			$fields = explode(":", $line);

			// timestamp is first key (for sorting)
			$blockinfo["timestamp"] = $fields[5];
			$blockinfo["sid"] = $fields[0];
			$blockinfo["blockedip"] = $fields[1];
			$blockinfo["peerip"] = $fields[2];
			$blockinfo["peerport"] = $fields[3];
			$blockinfo["protocol"] = strtoupper($fields[4]);
			$blockinfo["duration"] = $fields[6];
			$blockinfo["crc"] = $fields[8];
			$blocklist[] = $blockinfo;
		}

		rsort($blocklist);

		return $blocklist;
	}

	/**
	 * Return an array of IP addresses in the white list.
	 *
	 * @return array list of IP addresses within the white list
	 * @throws EngineException
	 */

	function GetWhitelist()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$list = array();

		try {
			$file = new File(self::FILE_WHITELIST);
			if (! $file->Exists())
				return $list;
			$output = $file->GetContentsAsArray();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		$matches = array();

		foreach ($output as $line) {
			if (preg_match("/^dontblock\s+(.*)/i", $line, $matches))
				$list[] = $matches[1];
		}

		return $list;
	}

	/**
	 * Resets the current block list.
	 *
	 * @return void
	 * @throws EngineException
	 */

	function ResetBlocklist()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$shell = new ShellExec();
			$shell->Execute(self::COMMAND_STATE, "-D all", true);
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
}

// vim: syntax=php ts=4
?>
