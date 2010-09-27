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
 * Awstats class.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2005-2006, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('Engine.class.php');
require_once('Folder.class.php');
require_once('ShellExec.class.php');
require_once('Software.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Awstats class.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2005-2006, Point Clark Networks
 */

class Awstats extends Software
{
	///////////////////////////////////////////////////////////////////////////////
	// M E M B E R S
	///////////////////////////////////////////////////////////////////////////////

	const PATH_CONFIG = '/etc/awstats';
	const FILE_PASSWORD = '/var/webconfig/reports/awstats/htpasswd';
	const COMMAND_HTPASSWD = '/usr/bin/htpasswd';

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Awstats constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct("awstats");
	}

	/**
	 * Returns list of domains using awstats.
	 *
	 * @return array list of working WAN interfaces
	 * @throws EngineException
	 */

	function GetDomainList()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$rawlist = array();

		try {
			$folder = new Folder(self::PATH_CONFIG);
			$rawlist = $folder->GetListing();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		$list = array();
		$matches = array();

		foreach ($rawlist as $reportname) {
			if (!preg_match("/^awstats\..*\.conf$/", $reportname))
				continue;

			if (preg_match("/^awstats\.model\.conf$/", $reportname))
				continue;

			if (preg_match("/^awstats\.(.*)\.conf$/", $reportname, $matches))
				$list[] = trim($matches[1]);
		}

		return $list;
	}

	/**
	 * Sets password for report access.
	 *
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	function SetPassword($password)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$file = new File(self::FILE_PASSWORD);

		if ((!$password) || preg_match("/[;\|]/", $password))
			throw new ValidationException(LOCALE_LANG_ERRMSG_PASSWORD_INVALID);

		try {
			if ($file->Exists())
				$file->Delete();

			$file->Create("webconfig", "webconfig", "0640");

			$shell = new ShellExec();
			$retval = $shell->Execute(self::COMMAND_HTPASSWD, "-b -c '" . self::FILE_PASSWORD . "' awstats '$password'");

			if ($retval != 0)
				throw new EngineException($shell->GetFirstOutputLine(), COMMON_WARNING);
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
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__destruct();
	}
}

// vim: syntax=php ts=4
?>
