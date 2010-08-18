<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2007 Point Clark Networks.
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
 * Postgrey class.
 *
 * @package Api
 * @subpackage Daemon
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2007, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('Daemon.class.php');
require_once('Postfix.class.php');
require_once('ConfigurationFile.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Postgrey class.
 *
 * @package Api
 * @subpackage Daemon
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

class Postgrey extends Daemon
{
	///////////////////////////////////////////////////////////////////////////////
	// M E M B E R S
	///////////////////////////////////////////////////////////////////////////////

	protected $is_loaded = false;
	protected $config = array();

	const FILE_CONFIG = "/etc/sysconfig/postgrey";
	const DEFAULT_DELAY = 300;
	const DEFAULT_MAX_AGE = 35;
	const DEFAULT_CONFIG = 'OPTIONS="--delay=$DELAY --max-age=$MAXAGE"';
	const CONSTANT_POSTFIX_POLICY_SERVICE = "unix:/var/spool/postfix/postgrey/socket";

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Postgrey constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct("postgrey");

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Returns greylist delay in seconds.
	 *
	 * @return int greylist delay in seconds
	 * @throws EngineException
	 */

	function GetDelay()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!$this->is_loaded)
			$this->_LoadConfig();

		if (isset($this->config['DELAY']))
			return $this->config['DELAY'];
		else
			return self::DEFAULT_DELAY;
	}

	/**
	 * Returns maximum age (in days) for entries in database.
	 *
	 * @return int maximum age for entries in database
	 * @throws EngineException
	 */

	function GetMaxAge()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!$this->is_loaded)
			$this->_LoadConfig();

		if (isset($this->config['MAXAGE']))
			return $this->config['MAXAGE'];
		else
			return self::DEFAULT_MAX_AGE;
	}

	/**
	 * Returns state of filter.
	 *
	 * @return boolean true if filter is enabled
	 * @throws EngineException
	 */

	function GetMailConfigurationState()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!$this->is_loaded)
			$this->_LoadConfig();

		try {
			$postfix = new Postfix();
			$is_in_postfix = $postfix->GetPolicyService(self::CONSTANT_POSTFIX_POLICY_SERVICE);
        } catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_WARNING);
        }
		
		if ($is_in_postfix)
			return true;
		else
			return false;
	}

	/**
	 * Sets greylist delay in seconds.
	 *
	 * @param int $delay greylist delay in seconds
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	function SetDelay($seconds)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$this->_SetParameter("DELAY", $seconds);
	}

	/**
	 * Sets maximum age (in days) for entries in database.
	 *
	 * @param int $maxage maximum age (in days) for entries in database.
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	function SetMaxAge($days)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$this->_SetParameter("MAXAGE", $days);
	}

	/**
	 * Enables greylist service.
	 *
	 * @param boolean $state state of greylist service
	 * @return void
	 * @throws EngineException, ValidationException
	 */
	
	function SetState($state)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			if ($state) {
				$this->SetRunningState(true);
				$this->SetBootState(true);
			} else {
				$this->SetRunningState(false);
				$this->SetBootState(false);
			}

			$postfix = new Postfix();
			$postfix->SetPolicyService(self::CONSTANT_POSTFIX_POLICY_SERVICE, $state);

        } catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_WARNING);
        }
	}

	///////////////////////////////////////////////////////////////////////////////
	// P R I V A T E  M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Sets a parameter.
	 *
	 * @access private
	 * @param string $key key
	 * @param string $value value for preference
	 * @return void
	 * @throws EngineException
	 */

	function _SetParameter($key, $value)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

        try {
            $file = new File(self::FILE_CONFIG);
            $match = $file->ReplaceLines("/^$key=.*/", "$key=\"$value\"\n");
			if (!$match)
				$file->AddLinesBefore("$key=\"$value\"\n", "/^OPTIONS=/");
		} catch (FileNotFoundException $e) {
			$file->Create("root", "root", "0644");
			$file->AddLines(self::DEFAULT_CONFIG . "\n");
			$file->AddLinesBefore("$key=\"$value\"\n", "/^OPTIONS=/");
		} catch (FileNoMatchException $e) {
			$file->AddLines(self::DEFAULT_CONFIG . "\n");
			$file->AddLinesBefore("$key=\"$value\"\n", "/^OPTIONS=/");
        } catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_WARNING);
        }

		$this->is_loaded = false;
	}

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
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__destruct();
	}
}

// vim: syntax=php ts=4
?>
