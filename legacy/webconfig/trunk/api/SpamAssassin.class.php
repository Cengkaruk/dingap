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
 * SpamAssassin class.
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

require_once('Cron.class.php');
require_once('Daemon.class.php');
require_once('Engine.class.php');
require_once('File.class.php');
require_once('Folder.class.php');
require_once('ShellExec.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * SpamAssassin class.
 *
 * @package Api
 * @subpackage Daemon
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class SpamAssassin extends Daemon
{
	///////////////////////////////////////////////////////////////////////////////
	// M E M B E R S
	///////////////////////////////////////////////////////////////////////////////

	const FILE_CONFIG = "/etc/mail/spamassassin/local.cf";
	const FILE_SYSCONFIG = "/etc/sysconfig/spamassassin";
	const FILE_CRONFILE = "app-spamassassin";
	const COMMAND_SA_UPDATE = "/usr/bin/sa-update";
	const COMMAND_AUTOUPDATE = "/usr/sbin/app-sa-update";
	const DEFAULT_MAX_CHILDREN = 5;

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * SpamAssassin constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct("spamassassin");

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Returns blacklist addresses.
	 *
	 * @return string addresses
	 * @throws EngineException
	 */

	function GetBlackList()
	{
		$blacklist = "";

		try {
			$file = new File(self::FILE_CONFIG);
			$blacklist = $file->LookupValue("/^blacklist_from/i");
			$blacklist = ereg_replace("[\t ,;:]+", "\n", $blacklist);
		} catch (FileNoMatchException $e) {
			return "";
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		return $blacklist;
	}

	/**
	 * Returns the maximum children spawned by spamd.
	 *
	 * @return int max children spawned
	 * @throws EngineException
	 */

	function GetMaxChildren()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$children = "";

		try {
			$file = new File(self::FILE_SYSCONFIG);
			$children = $file->LookupValue("/^SPAMDOPTIONS=/");
		} catch (FileNoMatchException $e) {
			return self::DEFAULT_MAX_CHILDREN;
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		$children = preg_replace("/\"/", "", $children);
		$children = preg_replace("/.*-m/", "", $children);
		$children = ltrim($children);
		$children = preg_replace("/\s+.*/", "", $children);

		if ($children)
			return $children;
		else
			return self::DEFAULT_MAX_CHILDREN;
	}

	/**
	 * Returns whitelist addresses.
	 *
	 * @return string addresses
	 * @throws EngineException
	 */

	function GetWhiteList()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$whitelist = "";

		try {
			$file = new File(self::FILE_CONFIG);
			$whitelist = $file->LookupValue("/^whitelist_from/i");
			$whitelist = ereg_replace("[\t ,;:]+", "\n", $whitelist);
		} catch (FileNoMatchException $e) {
			return "";
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		return $whitelist;
	}

	/**
	 * Runs auto update.
	 *
	 * @return void
	 * @throws EngineException
	 */

	function RunUpdate()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			// Exit code can be misleading
			$shell = new ShellExec();
			$shell->Execute(self::COMMAND_SA_UPDATE, "", true);

			$amavis = new Daemon("amavisd");
			$amavis->Reset();

		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Sets auto-update cron job.
	 *
	 * @return void
	 * @throws EngineException
	 */

	public function SetAutoUpdateTime()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$cron = new Cron();

			if ($cron->ExistsCrondConfiglet(self::FILE_CRONFILE))
				$cron->DeleteCrondConfiglet(self::FILE_CRONFILE);

			$nextday = date("w") + 1;

			$cron->AddCrondConfigletByParts(self::FILE_CRONFILE, rand(0,59), rand(1,12), "*", "*", $nextday, "root", self::COMMAND_AUTOUPDATE . " >/dev/null 2>&1");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Sets black listed addresses
	 *
	 * @param   addresses addresses
	 * @returns void
	 */

	function SetBlackList($addresses)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		// Put addresses on one line
		$addresses = ereg_replace("[\n\r\t ,;:]+", " ", $addresses);

		try {
			$file = new File(self::FILE_CONFIG);
			$match = $file->ReplaceLines("/^blacklist_from\s*/i", "blacklist_from $addresses\n");
			if (!$match)
				$file->AddLinesAfter("blacklist_from $addresses\n", "/^[^#]/");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Sets white listed addresses
	 *
	 * @param   addresses addresses
	 * @returns void
	 */

	function SetWhiteList($addresses)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		// Put addresses on one line
		$addresses = ereg_replace("[\n\r\t ,;:]+", " ", $addresses);

		try {
			$file = new File(self::FILE_CONFIG);
			$match = $file->ReplaceLines("/^whitelist_from\s*/i", "whitelist_from $addresses\n");
			if (!$match)
				$file->AddLinesAfter("whitelist_from $addresses\n", "/^[^#]/");
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
