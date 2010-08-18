<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2006-2009 Point Clark Networks.
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
 * Operating system information.
 *
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package Api
 * @copyright Copyright 2006-2009, Point Clark Networks
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
 * Software package class.
 *
 * @package Api
 */

class Os extends Engine
{
	///////////////////////////////////////////////////////////////////////////////
	// M E M B E R S
	///////////////////////////////////////////////////////////////////////////////

	private $os = null;
	private $version = null;
	private $previous_os = null;
	private $previous_version = null;

	const FILE_CORE_RELEASE = '/etc/system/release';
	const FILE_RELEASE = '/etc/release';

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Os constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct();

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Returns the name of the operating system/distribution.
	 *
	 * @return string OS name
	 * @throws EngineException
	 */

	public function GetName()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (is_null($this->os))
			$this->_LoadConfig();

		return $this->os;
	}

	/**
	 * Returns the version of the operating system/distribution.
	 *
	 * @return string OS version
	 * @throws EngineException
	 */

	public function GetVersion()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (is_null($this->version))
			$this->_LoadConfig();

		return $this->version;
	}

	/**
	 * Returns the technical version of the operating system/distribution.
	 *
	 * @return string technical version
	 * @throws EngineException
	 */

	public function GetCoreVersion()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$file = new File(Os::FILE_CORE_RELEASE);
			$contents = $file->GetContents();
		} catch (FileNotFoundException $e) {
			return $this->GetVersion();
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}

		$osinfo = explode(" release ", $contents);

		if (count($osinfo) != 2)
			throw new EngineException(OS_LANG_ERRMSG_NAME_UNKNOWN, COMMON_ERROR);

		return $osinfo[1];
	}

	/**
	 * Populates version and name fields.
	 *
	 * @access private
	 * @throws EngineException
	 */

	protected function _LoadConfig()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$file = new File(self::FILE_RELEASE);
			$contents = $file->GetContents();
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}

		$osinfo = explode(" release ", $contents);

		if (count($osinfo) != 2)
			throw new EngineException(OS_LANG_ERRMSG_NAME_UNKNOWN, COMMON_ERROR);

		$this->os = $osinfo[0];
		$this->version = $osinfo[1];
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
