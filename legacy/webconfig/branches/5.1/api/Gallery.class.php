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
 * Photo gallery class.
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
require_once('Folder.class.php');
require_once('Software.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Photo gallery class.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class Gallery extends Software
{
	const CONSTANT_MODE_SETUP = 1;
	const CONSTANT_MODE_SECURE = 2;
	const PATH_SETUP = "/var/www/html/gallery/setup";
	const FILE_CONFIG = "/var/www/html/gallery/config.php";
	const FILE_HTACCESS = "/var/www/html/gallery/.htaccess";
	const FILE_OWNER = "apache";
	const MODE_CONFIGURE = "0755";

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Photo gallery constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct("gallery");
	}

	/**
	 * Returns the mode for gallery.
	 *
	 * @return integer state of Gallery (CONSTANT_MODE_SETUP or CONSTANT_MODE_SECURE)
	 * @throws EngineException
	 */

	function GetMode()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$perms = "";

		try {
			$setup = new Folder(self::PATH_SETUP);
			if (!$setup->Exists())
				throw new EngineException(LOCALE_LANG_ERRMSG_WEIRD, COMMON_ERROR);

			// Fresh install will not have config.php
			$config = new File(self::FILE_CONFIG);

			if (!$config->Exists())
				return self::CONSTANT_MODE_SECURE;

			$perms = $setup->GetPermissions();

		} catch (Exception $e) {
			throw new EngineException(LOCALE_LANG_ERRMSG_WEIRD, COMMON_ERROR);
		}

		if ($perms == self::MODE_CONFIGURE) {
			return self::CONSTANT_MODE_SETUP;
		} else {
			return self::CONSTANT_MODE_SECURE;
		}
	}


	/**
	 * Sets the mode for gallery.
	 *
	 * @param integer $mode security mode (CONSTANT_MODE_SETUP or CONSTANT_MODE_SECURE)
	 * @returns void
	 * @throws EngineException, ValidationException
	 */

	function SetMode($mode)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$setup = new Folder(self::PATH_SETUP);
		$config = new File(self::FILE_CONFIG);
		$htaccess = new File(self::FILE_HTACCESS);

		if ($mode == self::CONSTANT_MODE_SETUP) {
			try {
				$setup->Chmod(self::MODE_CONFIGURE);
				$setup->Chown(self::FILE_OWNER, self::FILE_OWNER);

				if ($config->Exists()) {
					$config->Chmod("0666");
					$config->Chown(self::FILE_OWNER, self::FILE_OWNER);
				} else {
					$config->Create(self::FILE_OWNER, self::FILE_OWNER, "0666");
				}

				if ($htaccess->Exists()) {
					$htaccess->Chmod("0666");
					$htaccess->Chown(self::FILE_OWNER, self::FILE_OWNER);
				} else {
					$htaccess->Create(self::FILE_OWNER, self::FILE_OWNER, "0666");
				}

			} catch (Exception $e) {
				throw new EngineException(LOCALE_LANG_ERRMSG_WEIRD, COMMON_ERROR);
			}
		} else if ($mode == self::CONSTANT_MODE_SECURE) {
			try {
				$setup->Chmod("0400");
				$setup->Chown(self::FILE_OWNER, self::FILE_OWNER);
				$config->Chmod("0644");
				$config->Chown(self::FILE_OWNER, self::FILE_OWNER);
				$htaccess->Chmod("0644");
				$htaccess->Chown(self::FILE_OWNER, self::FILE_OWNER);
			} catch (Exception $e) {
				throw new EngineException(LOCALE_LANG_ERRMSG_WEIRD, COMMON_ERROR);
			}
		} else {
			throw new ValidationException(LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - mode");
		}
	}
}

// vim: syntax=php ts=4
?>
