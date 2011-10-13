<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2011 ClearFoundation
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
 * Philesight class.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2011, ClearFoundation
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('File.class.php');
require_once('ShellExec.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Philesight class.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2011, ClearFoundation
 */

class Philesight extends Engine
{
	const PHILESIGHT_COMMAND = '/usr/sbin/philesightcli';
	const FILE_DATA = '/usr/webconfig/tmp/ps.db';

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Philesight constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct();
	}

	/**
	 * Returns a Philesight PNG image.
	 *
	 * @param string $path path
	 * @return image Philesight image
	 * @throws EngineException
	 */

	function GetImage($path = '/')
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		// FIXME - clean path

	     	ob_start();
		passthru(self::PHILESIGHT_COMMAND . ' --action image --path ' . $path);
                $png = ob_get_clean();

		return $png;
	}

	/**
	 * Returns path for given coordinates.
	 *
	 * @param string $path path
	 * @param integer $xcoord x-coordinate
	 * @param integer $ycoord y-coordinate
	 *
	 * @return string path
	 * @throws Engine_Exception
	 */

	function GetPath($path, $xcoord, $ycoord)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);
		// FIXME - clean path

	     	ob_start();
		passthru(self::PHILESIGHT_COMMAND . ' --action find --path ' . $path . ' --xcoord ' . $xcoord . ' --ycoord ' . $ycoord);
                $path = ob_get_clean();

		return $path;
	}

	/**
	 * Returns state of Philesight.
	 *
	 * @return boolean TRUE if Philesight has been initialized
	 * @throws Engine_Exception
	 */

	function Initialized()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$file = new File(self::FILE_DATA);

		if ($file->Exists())
			return TRUE;
		else
			return FALSE;
	}

	/**
	 * Validation routine for time server.
	 *
	 * @param string $timeserver time server
	 * @return boolean true if time server is valid
	 */

	function IsValidTimeServer($timeserver)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (preg_match("/^([\.\-\w]*)$/", $timeserver))
			return true;

		return false;
	}
}

// vim: syntax=php ts=4
?>
