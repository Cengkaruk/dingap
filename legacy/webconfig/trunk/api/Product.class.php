<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2010 ClearCenter
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
 * Product class.
 *
 * @package Api
 * @subpackage ClearSDN
 * @author {@link http://www.clearcenter.com/ ClearCenter}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2010, ClearCenter
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('Engine.class.php');
require_once('ConfigurationFile.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Product class.
 *
 * @package Api
 * @subpackage ClearSDN
 * @author {@link http://www.clearcenter.com/ ClearCenter}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2010, ClearCenter
 */

class Product extends Engine
{
	///////////////////////////////////////////////////////////////////////////////
	// F I E L D S
	///////////////////////////////////////////////////////////////////////////////

	protected $is_loaded = false;
	private $config = array();

	const FILE_CONFIG = '/etc/system/product';

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Product constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct();
	}

	/**
	 * Returns free trial state.
	 *
	 * @return boolean state of free trials
	 * @throws EngineException
	 */

	public function GetFreeTrialState()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!$this->is_loaded)
			$this->_LoadConfig();

		if (isset($this->config['free_trial']) && ($this->config['free_trial'] === "0"))
			return false;
		else
			return true;
	}

	/**
	 * Returns the product name.
	 *
	 * @return string product name
	 * @throws EngineException
	 */

	public function GetName()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!$this->is_loaded)
			$this->_LoadConfig();

		return $this->config['name'];
	}

	/**
	 * Returns portal URL.
	 *
	 * @return string portal URL
	 * @throws EngineException
	 */

	public function GetPortalUrl()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!$this->is_loaded)
			$this->_LoadConfig();

		return $this->config['portal_url'];
	}

	/**
	 * Returns the product version.
	 *
	 * @return string product version
	 * @throws EngineException
	 */

	public function GetVersion()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!$this->is_loaded)
			$this->_LoadConfig();

		return $this->config['version'];
	}

	// TODO: move WebServices.GetVendorCode and Register.GetVendorCode to here.
	// - GetVendorCode()
	//
	// TODO: the following will be migrated/added
	// - GetUserGuideUrl()
	// - GetSupportUrl()

	/**
	 * Loads configuration file.
	 *
	 * @access private
	 * @return void
	 * @throws EngineException
	 */

	function _LoadConfig()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$file = new ConfigurationFile(self::FILE_CONFIG);
			$config = $file->Load();
		} catch (FileNotFoundException $e) {
			// Empty configuration
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		if (!empty($config)) {
			foreach ($config as $key => $value)
				$this->config[$key] = preg_replace('/"/', '', $value);
		}

		$this->is_loaded = true;
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
