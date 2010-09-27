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
 * Generic file group class
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
 * Generic file group class
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class FileGroup extends Engine
{
	///////////////////////////////////////////////////////////////////////////////
	// F I E L D S
	///////////////////////////////////////////////////////////////////////////////

	protected $group = null;
	protected $filename = null;
	protected $fileowner = null;
	protected $filegroup = null;
	protected $filemode = null;
	protected $demark = null;

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Group manager constructor.
	 *
	 * @param string $group group name
	 * @param string $filename filename for storing group information
	 * @param string $fileowner file owner
	 * @param string $filegroup file group
	 * @param string $filemode file mode
	 * @param string $demark demarker
	 */

	public function __construct($group, $filename, $fileowner = "root", $filegroup = "root", $filemode = "0644", $demark = "#")
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->group = $group;
		$this->filename = $filename;
		$this->fileowner = $fileowner;
		$this->filegroup = $filegroup;
		$this->filemode = $filemode;
		$this->demark = $demark;

		parent::__construct();

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Adds a group.
	 *
	 * @param array $entries list of entries
	 * @return void
	 * @throws EngineException
	 */

	public function Add($entries)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$file = new File($this->filename);

		if (!$file->Exists())
			$file->Create($this->fileowner, $this->filegroup, $this->filemode);

		if ($this->Exists())
			throw new EngineException(FILEGROUP_LANG_ERRMSG_EXISTS, COMMON_ERROR);

		$filedata = sprintf("%s Webconfig Group Header: %s\n", $this->demark, $this->group);

		if ($entries) {
			foreach ($entries as $entry)
			$filedata .= "$entry\n";
		}

		$filedata .= sprintf("%s Webconfig Group Footer: %s\n", $this->demark, $this->group);

		$file->AddLines($filedata);
	}

	/**
	 * Deletes a group.
	 *
	 * @return void
	 * @throws EngineException
	 */

	public function Delete()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$file = new File($this->filename);

		if (!$this->Exists())
			throw new EngineException(FILEGROUP_LANG_ERRMSG_NOT_EXISTS, COMMON_ERROR);

		$header = sprintf("/^%s Webconfig Group Header: %s\$/", $this->demark, $this->group);
		$footer = sprintf("/^%s Webconfig Group Footer: %s\$/", $this->demark, $this->group);

		$match = $file->ReplaceLinesBetween("/.*/", "", $header, $footer);

		if (!$match)
			throw new EngineException(LOCALE_LANG_ERRMSG_PARSE_ERROR, COMMON_ERROR);

		$file->DeleteLines($header);
		$file->DeleteLines($footer);
	}

	/**
	 * Checks for existence of group.
	 *
	 * @return boolean true if group exists
	 * @throws EngineException
	 */

	public function Exists()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$file = new File($this->filename);

		if (!$file->Exists())
			return false;

		$search = sprintf("/^%s Webconfig Group Header: %s\$/", $this->demark, $this->group);

		try {
			$match = $file->LookupLine($search);
		} catch (FileNoMatchException $e) {
			return false;
		}

		if ($match)
			return true;

		return false;
	}

	/**
	 * Checks to see if entry exists in group.
	 *
	 * @return boolean true if entry exists
	 * @throws EngineException
	 */

	public function EntryExists($entry)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (!$this->Exists())
			throw new EngineException(FILEGROUP_LANG_ERRMSG_NOT_EXISTS, COMMON_ERROR);

		$file = new File($this->filename);

		$header = sprintf("/^%s Webconfig Group Header: %s\$/", $this->demark, $this->group);
		$footer = sprintf("/^%s Webconfig Group Footer: %s\$/", $this->demark, $this->group);

		try {
			$match = $file->LookupValueBetween("/^$entry\$/", $header, $footer);
		} catch (FileNoMatchException $e) {
			return false;
		}

		if ($match)
			return true;

		return false;
	}

	/**
	 * Returns list of group entries.
	 *
	 * @return array list of entries
	 * @throws EngineException
	 */

	public function GetEntries()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$entries = array();

		if (!$this->Exists())
			throw new EngineException(FILEGROUP_LANG_ERRMSG_NOT_EXISTS, COMMON_ERROR);

		$file = new File($this->filename);

		$contents = $file->GetContents();

		$lines = explode("\n", $contents);

		$header = sprintf("/^%s Webconfig Group Header: %s\$/", $this->demark, $this->group);
		$footer = sprintf("/^%s Webconfig Group Footer: %s\$/", $this->demark, $this->group);

		foreach ($lines as $line) {
			if (preg_match($header, $line))
				break;

			array_shift($lines);
		}

		array_shift($lines);

		foreach ($lines as $line) {
			if (preg_match($footer, $line))
				break;

			$entries[] = $line;
		}

		return $entries;
	}


	/**
	 * Adds a group member.
	 *
	 * @param string $entry entry
	 * @return void
	 * @throws EngineException
	 */

	public function AddEntry($entry)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (!$this->Exists())
			throw new EngineException(FILEGROUP_LANG_ERRMSG_NOT_EXISTS, COMMON_ERROR);

		if ($this->EntryExists($entry))
			throw new EngineException(FILEGROUP_LANG_ERRMSG_ENTRY_EXISTS, COMMON_ERROR);

		$footer = sprintf("/^%s Webconfig Group Footer: %s\$/", $this->demark, $this->group);

		$file = new File($this->filename);

		$file->PrependLines($footer, $entry . "\n");
	}


	/**
	 * Deletes a group member.
	 *
	 * @param string $entry entry
	 * @return void
	 * @throws EngineException
	 */

	public function DeleteEntry($entry)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (!$this->Exists())
			throw new EngineException(FILEGROUP_LANG_ERRMSG_NOT_EXISTS, COMMON_ERROR);

		if (!$this->EntryExists($entry))
			throw new EngineException(FILEGROUP_LANG_ERRMSG_ENTRY_NOT_EXISTS, COMMON_ERROR);

		$file = new File($this->filename);

		$header = sprintf("/^%s Webconfig Group Header: %s\$/", $this->demark, $this->group);
		$footer = sprintf("/^%s Webconfig Group Footer: %s\$/", $this->demark, $this->group);

		$match = $file->ReplaceLinesBetween("/^$entry\$/", "", $header, $footer);
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
