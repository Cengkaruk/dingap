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
 * Mail filter class.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2007, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('File.class.php');
require_once('Software.class.php');
require_once('UserManager.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Mail filter class.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2007, Point Clark Networks
 */

class MailFilter extends Software
{
	///////////////////////////////////////////////////////////////////////////////
	// M E M B E R S
	///////////////////////////////////////////////////////////////////////////////

	const FILE_CONFIG = '/etc/filter/config.override.php';

	protected $is_loaded = false;
	protected $config = array();

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Mail filter constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		parent::__construct("kolab-filter");
	}

	/**
	 * Returns catch-all mailbox.
	 *
	 * @return boolean state of login block policy
	 * @throws EngineException
	 */

	function GetCatchAllMailbox()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		if (isset($this->config['catch_all_mailbox']))
			return $this->config['catch_all_mailbox'];
		else
			return '';
	}

	/**
	 * Sets catch-all mailbox.
	 *
	 * @param string $mailbox mailbox (username)
	 * @return void
	 * @throws EngineException
	 */

	function SetCatchAllMailbox($mailbox)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! empty($mailbox)) {
			try {
				$usermanager = new UserManager();
				$userlist = $usermanager->GetAllUsers(UserManager::TYPE_EMAIL);
			} catch (Exception $e) {
				throw new EngineException($e->GetMessage(), COMMON_WARNING);
			}

			if (! in_array($mailbox, $userlist))
				throw new EngineException(MAILFILTER_LANG_ERRMSG_CATCHALL_USER_NOT_EXIST, COMMON_WARNING);
		}

		$this->_SetValue("\$conf['catch_all_mailbox']", $mailbox);
	}

	///////////////////////////////////////////////////////////////////////////////
	// P R I V A T E  M E T H O D S
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

		$file = new File(self::FILE_CONFIG);

		$params = array();
		
		try {
			$lines = $file->GetContentsAsArray();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		foreach ($lines as $line) {
			// Only pull out configuration file lines
			if (preg_match('/^\$conf/', $line))
				eval($line);
		}

		$this->config = $conf;
		$this->is_loaded = true;
	}

	/**
	 * Retrieves the value of the specified key from the specified configuration file.
	 *
	 * @access private
	 * @param $key the key to retrieve
	 * @return mixed
	 * @throws EngineException
	 */

	protected function _GetValue($key)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$value = null;

		try {
			$file = new File(self::FILE_CONFIG);
			$file->GetPermissions(); // ensure the file exists and we can read it
			include(self::FILE_CONFIG);
			eval("\$value = $$key;");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		return $value;
	}

	/**
	 * Sets a value in the specified configuration file.
	 *
	 * @access private
	 * @param string $key the parameter to set
	 * @param string $value the new value.
	 * @return void
	 * @throws ValidationException, EngineException
	 */

	protected function _SetValue($key, $value)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (empty($key))
			throw new EngineException(LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " " . $key, COMMON_WARNING);

		$this->is_loaded = false;

		$search = preg_quote($key);
		$replace = "$key = '$value';\n";

		try {
			$file = new File(self::FILE_CONFIG);
			$file->LookupValue("/$search/");
			$file->ReplaceLines("/$search/", $replace);
		} catch (FileNoMatchException $e) {
			$comment = preg_quote("// Catch-all user\n");
			$file->AddLinesBefore($comment . $replace, "/^\?>/");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(),COMMON_ERROR);
		}
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
