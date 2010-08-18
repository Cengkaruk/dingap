<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2006-2008 Point Clark Networks.
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
 * Posix user administration.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006-2008, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once("File.class.php");
require_once("Folder.class.php");
require_once("ShellExec.class.php");

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Posix user administration.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

class PosixUser extends Engine
{
	///////////////////////////////////////////////////////////////////////////////
	// F I E L D S
	///////////////////////////////////////////////////////////////////////////////

	private $username;

	const CMD_CHKPWD = "/usr/sbin/app-passwd";
	const CMD_PASSWD = "/usr/bin/passwd";
	const CMD_USERDEL = "/usr/sbin/userdel";

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * User constructor.
	 */

	function __construct($username)
	{
		if (COMMON_DEBUG_MODE)
			parent::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		require_once(GlobalGetLanguageTemplate(__FILE__));

		$this->username = $username;
	}

	/**
	 * Checks the password for the user.
	 *
	 * @param string password password for the user
	 * @return boolean true if password is correct
	 * @throws EngineException
	 */

	function CheckPassword($password)
	{
		if (COMMON_DEBUG_MODE)
			parent::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		sleep(2); // a small delay

		try {
			$options['stdin'] = "$this->username $password";

			$shell = new ShellExec();
			$retval = $shell->Execute(self::CMD_CHKPWD, "", true, $options);

			if ($retval == 0)
				return true;
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		return false;
	}

	/**
	 * Deletes a user from the Posix system.
	 *
	 * @returns void
	 * @throws EngineException
	 */

	function Delete()
	{
		if (COMMON_DEBUG_MODE)
			parent::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$username = escapeshellarg($this->username);
			$shell = new ShellExec();
			$retval = $shell->Execute(self::CMD_USERDEL, "$username", true);
			if ($retval != 0)
				throw new EngineException($shell->GetLastOutputLine(), COMMON_ERROR);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Sets the user's system password.
	 *
	 * @param string $password password
	 * @returns void
	 */

	function SetPassword($password, $verify)
	{
		if (COMMON_DEBUG_MODE)
			parent::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		// Validate
		//---------

		if (! $this->IsValidPassword($password, $verify))
			throw new ValidationException(LOCALE_LANG_INVALID);
		
		// Update
		//-------

		$user = escapeshellarg($this->username);
		$options['stdin'] = $password;

		try {
			$shell = new ShellExec();
			$retval = $shell->Execute(self::CMD_PASSWD, "--stdin $user", true, $options);
			if ($retval != 0)
				throw new EngineException($shell->GetLastOutputLine(), COMMON_WARNING);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Password validation routine.
	 *
	 * @param string $password password
	 * @param string $verify verify
	 * @return boolean true if passwords are valid
	 */

	function IsValidPassword($password, $verify)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$IsValid = true;

		if (empty($password)) {
			$this->AddValidationError(LOCALE_LANG_ERRMSG_REQUIRED_PARAMETER_IS_MISSING . " - " . LOCALE_LANG_PASSWORD, __METHOD__, __LINE__);
			$IsValid = false;
		}

		if (empty($verify)) {
			$this->AddValidationError(LOCALE_LANG_ERRMSG_REQUIRED_PARAMETER_IS_MISSING . " - " . LOCALE_LANG_VERIFY, __METHOD__, __LINE__);
			$IsValid = false;
		}

		if ($IsValid) {
			if ($password == $verify) {
				if (preg_match("/[\|;]/", $password)) {
					$this->AddValidationError(LOCALE_LANG_ERRMSG_PASSWORD_INVALID, __METHOD__, __LINE__);
					$IsValid = false;
				}
			} else {
				$this->AddValidationError(LOCALE_LANG_ERRMSG_PASSWORD_MISMATCH, __METHOD__, __LINE__);
				$IsValid = false;
			}
		}

		return $IsValid;
	}
	
	/**
	 * @access private
	 */

	public function __destruct()
	{
		if (COMMON_DEBUG_MODE)
			parent::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__destruct();
	}
}

// vim: syntax=php ts=4
?>
