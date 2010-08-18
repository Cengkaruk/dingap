<?php

/////////////////////////////////////////////////////////////////////////////
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
 * Generic file group manager class.
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
require_once('Engine.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Generic file group manager class.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class FileGroupManager extends Engine
{
	///////////////////////////////////////////////////////////////////////////////
	// F I E L D S
	///////////////////////////////////////////////////////////////////////////////

	protected $filename = null;
	protected $demark = null;

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

	/**
	 * Group manager constructor.
	 *
	 * @param string $filename filename for storing group information
	 * @param string $demark demarker
	 */

	public function __construct($filename, $demark = "#")
	{
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->demark = $demark;
		$this->filename = $filename;

        parent::__construct();
	}

	/**
	 * Returns a list of defined groups.
	 *
	 * @return array list of groups
	 * @throws EngineException
	 */

	public function GetGroups()
	{
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$groups = array();
		$file = new File($this->filename);

		if (!$file->Exists()) return $groups;

		$contents = $file->GetContents();

		$lines = explode("\n", $contents);
		$match = array();
		
		foreach($lines as $line) {
			if (!ereg("^$this->demark Webconfig Group Header: (.*)\$", $line, $match))
				continue;

			$groups[] = $match[1];
		}

		return $groups;
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
