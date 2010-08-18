<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2006 Point Clark Networks.
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
 * File extension and MIME type class.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('Engine.class.php');
require_once('File.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * File extension and MIME type class.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

class FileTypes extends Engine
{
	///////////////////////////////////////////////////////////////////////////////
	// M E M B E R S
	///////////////////////////////////////////////////////////////////////////////

	const FILE_EXTENSIONS = "/etc/system/fileextensions";
	const FILE_MIME_TYPES = "/etc/system/mimetypes";
	protected $categories = array();

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * File type constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct();

		require_once(GlobalGetLanguageTemplate(__FILE__));

		global $CATEGORIES;
		require_once("FileTypes.inc.php");

		$this->categories = $CATEGORIES;
	}

	/**
	 * Returns the list of known file extensions.
	 *
	 * @return array list of file extensions
	 * @throws EngineException
	 */

	function GetFileExtensions()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$lines = array();
		$extensions = array();

		try {
			$file = new File(self::FILE_EXTENSIONS);
			$lines = $file->GetContentsAsArray();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		foreach ($lines as $line) {
			if (preg_match("/^#/", $line))
				continue;

			if (preg_match("/^\s*$/", $line))
				continue;

			$items = explode(" ", $line, 3);

			$extensions[$items[0]]['type'] = $items[1];
			$extensions[$items[0]]['description'] = $items[2];

			if (isset($this->categories[$items[1]]))
				$extensions[$items[0]]['typetext'] = $this->categories[$items[1]];
			else
				$extensions[$items[0]]['typetext'] = $items[1];
		}

		return $extensions;
	}

	/**
     * Returns the list of known mime types.
     *
     * @return array list of mime types
     * @throws EngineException
     */

    function GetMimeTypes()
    {
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

        $lines = array();
        $types = array();

        try {
            $file = new File(self::FILE_MIME_TYPES);
            $lines = $file->GetContentsAsArray();
        } catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_ERROR);
        }

        foreach ($lines as $line) {
            $items = explode(" ", $line, 2);
            $types[$items[0]] = $items[1];
        }

        return $types;
    }

}

// vim: syntax=php ts=4
?>
