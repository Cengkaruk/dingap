<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2003-2008 Point Clark Networks.
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
 * MediaTomb class.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2008, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('File.class.php');
require_once('Daemon.class.php');
require_once('Network.class.php');
require_once('Iface.class.php');
require_once('ShellExec.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * MediaTomb mail server.
 *
 * @package Api
 * @subpackage Daemon
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2007, Point Clark Networks
 */

class MediaTomb extends Daemon
{
	const FILE_CONFIG = '/etc/mediatomb.conf';
	const FILE_CONFIG_XML = '/etc/mediatomb/config.xml';
	const CMD_UUIDGEN = '/usr/bin/uuidgen';

	protected $is_loaded = false;
	protected $config = array();

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * MediaTomb constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		parent::__construct('mediatomb');

	}

	///////////////////////////////////////////////////////////////////////////
	// M A I N  M E T H O D S												//
	///////////////////////////////////////////////////////////////////////////

	/**
	 * Indicates whether the modules requires bootstrapping.
	 *
	 * @return boolean
	 * @throws EngineException
	 */

	function RequiresInit()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);


		if (! $this->is_loaded)
			$this->_LoadConfig();

		//echo "<pre>";
		//print_r($this->config);
		//echo "</pre>";

		// The "NOT_SET" constant is default once mediatomb RPM is installed
		if ($this->config['MT_INTERFACE'] == "\"NOT_SET\"") {
			// Set a random UUID and wait for interface to be set
			$this->_SetUuid();
			return true;
		}

		return false;
	}

	/**
	 * Sets the interface mediatomb should listen on
	 *
	 * @param string $iface Interface name
	 * @return void
	 * @throws  ValidationException
	 */

	function SetInterface($iface)
	{
		
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		$this->IsValidInterface($iface);
		$this->config['MT_INTERFACE'] = "\"" . $iface . "\"";
		
		$this->_SaveConfig();

		// Start service and set onboot flag
		$this->SetBootState(true);
		$this->SetRunningState(true);
	}

	///////////////////////////////////////////////////////////////////////////////
	// V A L I D A T I O N   R O U T I N E S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Checks to see if interface name is available on the system.
	 *
	 * @param string $iface Interface name
	 * @return  void
	 * @throws  ValidationException
	 */

	function IsValidInterface($iface)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$iface = new Iface($iface);

			if (! $iface->IsValid())
				throw new EngineException(IFACE_LANG_ERRMSG_INVALID . " - " . $iface, COMMON_ERROR);
		} catch (Exception $e) {
			throw new ValidationException($e->GetMessage(), COMMON_WARNING);
		}
	}

	///////////////////////////////////////////////////////////////////////////////
	// P R I V A T E   M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	* Loads configuration files.
	*
	* @return void
	* @throws EngineException
	*/

	protected function _LoadConfig()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$configfile = new ConfigurationFile(self::FILE_CONFIG);

		try {
			$this->config = $configfile->Load();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		$this->is_loaded = true;
	}

	/**
	* Saves the configuration file.
	*
	* @return void
	* @throws EngineException
	*/

	protected function _SaveConfig()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		try {
			$file = new File(self::FILE_CONFIG);
			if (!$file->Exists())
				throw new FileNotFoundException($this->filename, COMMON_ERROR);
			foreach ($this->config as $key => $value) {
				if ($file->ReplaceLines("/^" . $key . "\s*=.*$/", trim($key) . "=" . trim($value) . "\n", 1) != 1)
					$file->AddLines($key . " = " . $value . "\n");
			}
				
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

	/**
	* Sets the UUID to a unqiue valud in config.xml.
	*
	* @return void
	* @throws EngineException
	*/

	protected function _SetUuid()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);
		$shell = new ShellExec();
		$retval = $shell->Execute(self::CMD_UUIDGEN, null, false);
		if ($retval != 0) {
			$errstr = $shell->GetLastOutputLine();
			throw new EngineException($errstr, COMMON_WARNING);
		}
		$uuid = $shell->GetLastOutputLine();
		try {
			$file = new File(self::FILE_CONFIG_XML);
			if (!$file->Exists())
				throw new FileNotFoundException($this->filename, COMMON_ERROR);
			if ($file->ReplaceLines("/^\s*\\<udn\\>.*\\<\\/udn\\>\s*$/", "    <udn>uuid:$uuid</udn>\n", 1) != 1) 
				throw new EngineException("Could not parse udn", COMMON_WARNING);
				
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

	/**
	 * @access private
	 */

	function __destruct()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__destruct();
	}
}

// vim: syntax=php ts=4
?>
