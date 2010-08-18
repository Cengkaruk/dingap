<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2007-2010 Point Clark Networks.
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
 * Wrapper for JNetTop utility.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2007-2010, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('Engine.class.php');
require_once("ShellExec.class.php");
require_once("ConfigurationFile.class.php");

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Wrapper for JNetTop utility.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2007-2010, Point Clark Networks
 */

class JNetTop extends Engine
{
	///////////////////////////////////////////////////////////////////////////////
	// V A R I A B L E S
	///////////////////////////////////////////////////////////////////////////////

	const CMD_JNETTOP = '/usr/bin/jnettop';
	const FILE_CONFIG = '/etc/system/jnettop.conf';
	const FILE_DUMP = '/usr/webconfig/tmp/jnettop.dmp';

	protected $config = null;
	protected $is_loaded = false;

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * JNetTop constructor.
	 *
	 * @return void
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		parent::__construct();

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Returns a list of fields to monitor.
	 *
	 * @return array an array of fields
	 * @throws EngineException
	 */

	function GetFields()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$fields = array();

			if (!$this->is_loaded)
				$this->_LoadConfig();

			$values = $this->config['fields'];
			$fields = explode(',', $values);
			return $fields;
		} catch (Exception $e) {
			// Return default entry
			return array('srcname');
		}
	}

	/**
	 * Initializes monitor.
	 *
	 * @return void
	 * @throws EngineException
	 */

	function Initialize($interface, $interval)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$file = new File(self::FILE_DUMP);

			if ($file->Exists())
				$file->Delete();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		try {
			$shell = new ShellExec();
			$args = "-i $interface --display text -t $interval --format";
			$fields = $this->GetFields();
			$args .= " '";
			foreach ($fields as $field)
				$args .= "\$" . $field . "\$,"; 
			// Strip off the last comma separator and replace with single quote
			$args = preg_replace("/,$/", "'", $args);
			$options = array('env' => "LANG=en_US", 'background' => true, 'log' => 'jnettop.dmp');
			$retval = $shell->Execute(self::CMD_JNETTOP, $args, true, $options);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		if ($retval != 0) {
			$errstr = $shell->GetLastOutputLine();
			throw new EngineException($errstr, COMMON_WARNING);
		} else {
			$lines = $shell->GetOutput();
			foreach ($lines as $line) {
				echo $line;
			}
		}

	}

	///////////////////////////////////////////////////////////////////////////////
	// P R I V A T E   M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Loads configuration files.
	 *
	 * @access private
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
	 * Sets a parameter in the configuration file
	 *
	 * @access private
	 * @param string $key key name
	 * @param string $value value for the key
	 * @return void
	 * @throws EngineException
	 */

	protected function _SetParameter($key, $value)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$file = new File(self::FILE_CONFIG, true);
			$match = $file->ReplaceLines("/^$key\s*=\s*/", "$key=$value\n");

			if (!$match)
				$file->AddLines("$key=$value\n");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		$this->is_loaded = false;
	}

	/**
	 * @access private
	 */

	function __destruct()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		parent::__destruct();
	}

}

// vim: syntax=php ts=4
?>
