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
 * PCMCIA server.
 *
 * @package Api
 * @subpackage Daemon
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('Daemon.class.php');
require_once('File.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * PCMCIA server.
 *
 * @package Api
 * @subpackage Daemon
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class Pcmcia extends Daemon
{
	///////////////////////////////////////////////////////////////////////////////
	// M E M B E R S
	///////////////////////////////////////////////////////////////////////////////

	const FILE_CONFIG = "/etc/sysconfig/pcmcia";

	protected $config = null;
	protected $is_loaded = false;

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * PCMCIA constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct("pcmcia");

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Returns card manager options.
	 *
	 * @return string card manager options
	 * @throws EngineException
	 */

	function GetCardmgrOptions()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		return $this->config['CARDMGR_OPTS'];
	}

	/**
	 * Returns core options.
	 *
	 * @return string core options
	 * @throws EngineException
	 */

	function GetCoreOptions()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		return $this->config['CORE_OPTS'];
	}

	/**
	 * Returns PCIC driver.
	 *
	 * @return string PCIC driver
	 * @throws EngineException
	 */

	function GetPcic()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		return $this->config['PCIC'];
	}

	/**
	 * Returns PCIC driver options.
	 *
	 * @return string PCIC driver options
	 * @throws EngineException
	 */

	function GetPcicOptions()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		return $this->config['PCIC_OPTS'];
	}

	/**
	 * Returns PCMCIA state.
	 *
	 * @return boolean true if enabled
	 * @throws EngineException
	 */

	function GetState()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		if (preg_match("/yes/i", $this->config['PCMCIA']))
			return true;
		else
			return false;
	}

	/**
	 * Sets core options.
	 *
	 * @param string $options core options
	 * @return void
	 * @throws EngineException
	 */

	function SetCoreOptions($options)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->IsValidCoreOptions($options)) {
			$errmsg = PCMCIA_LANG_COREOPTIONS . " - " . LOCALE_LANG_INVALID;
			$this->AddValidationError($errmsg, __METHOD__, __LINE__);
			return;
		}

		$this->_SetParameter('CORE_OPTS', $options);
	}

	/**
	 * Sets PCIC driver.
	 *
	 * @param string $driver PCIC driver
	 * @return void
	 * @throws EngineException
	 */

	function SetPcic($driver)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->IsValidPcic($driver)) {
			$errmsg = PCMCIA_LANG_PCIC . " - " . LOCALE_LANG_INVALID;
			$this->AddValidationError($errmsg, __METHOD__, __LINE__);
			return;
		}

		$this->_SetParameter('PCIC', $driver);
	}

	/**
	 * Sets PCIC driver options.
	 *
	 * @param string $options PCIC driver options
	 * @return void
	 * @throws EngineException
	 */

	function SetPcicOptions($options)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->IsValidPcicOptions($options)) {
			$errmsg = PCMCIA_LANG_PCICOPTIONS . " - " . LOCALE_LANG_INVALID;
			$this->AddValidationError($errmsg, __METHOD__, __LINE__);
			return;
		}

		$this->_SetParameter('PCIC_OPTS', $options);
	}

	/**
	 * Sets PCMCIA state.
	 *
	 * @param boolean $state PCMCIA state
	 * @return void
	 * @throws EngineException
	 */

	function SetPcmcia($state)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->IsValidPcmcia($state)) {
			$errmsg = PCMCIA_LANG_PCMCIA . " - " . LOCALE_LANG_INVALID;
			$this->AddValidationError($errmsg, __METHOD__, __LINE__);
			return;
		}

		if ($state)
			$pcmcia = "yes";
		else
			$pcmcia = "no";

		$this->_SetParameter('PCMCIA', $pcmcia);
	}

	///////////////////////////////////////////////////////////////////////////////
	// P R I V A T E  M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Loads configuration.
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
			$file = new File(self::FILE_CONFIG);
			$lines = $file->GetContentsAsArray();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		$matches = array();

		foreach ($lines as $line) {
			if (preg_match("/^([^=]*)=(.*)/", $line, $matches))
				$this->config[$matches[1]] = preg_replace("/\"/", "", $matches[2]);
		}

		$this->is_loaded = true;
	}

	/**
	 * Sets parameter in configuration.
	 *
	 * @access private
	 * @param string $key key
	 * @param string $value value
	 * @return void
	 * @throws EngineException
	 */

	function _SetParameter($key, $value)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$file = new File(self::FILE_CONFIG);
			$match = $file->ReplaceLines("/^$key=/i", "$key=\"$value\"\n");
			if (!$match)
				$file->AddLinesAfter("$key=\"$value\"\n", "/^[^#]/");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		$this->is_loaded = false;
	}

	///////////////////////////////////////////////////////////////////////////////
	// V A L I D A T I O N   R O U T I N E S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Validation routine for PCMCIA
	 *
	 * @return boolean true if PCMCIA is valid
	 */

	function IsValidPcmcia($pcmcia)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (is_bool($pcmcia))
			return true;
		return false;
	}

	/**
	 * Validation routine for PCIC.
	 *
	 * @return boolean true if PCIC is valid
	 */

	function IsValidPcic($pcic)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!$pcic)
			return true;
		if (preg_match("/^(i82365|tcic|yenta_socket)$/", $pcic))
			return true;
		return false;
	}

	/**
	 * Validation routine for PCIC options.
	 *
	 * @return boolean true if PCIC options is valid
	 */

	function IsValidPcicOptions($pcicoptions)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (preg_match("/\"/", $pcicoptions))
			return false;
		return true;
	}

	/**
	 * Validation routine for core options.
	 *
	 * @return boolean true if core options is valid
	 */

	function IsValidCoreOptions($coreoptions)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (preg_match("/\"/", $coreoptions))
			return false;
		return true;
	}

	/**
	 * Validation routine for cardmgr options.
	 *
	 * @return boolean true if cardmgr options is valid
	 */

	function IsValidCardmgrOptions($cardmgroptions)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (preg_match("/\"/", $cardmgroptions))
			return false;
		return true;
	}

	/**
	 * @access private
	 */

	public function __destruct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__destruct();
	}
}

// vim: syntax=php ts=4
?>
