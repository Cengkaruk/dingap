<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2003-2009 Point Clark Networks.
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
 * Mysql class.
 *
 * @package Api
 * @subpackage Daemon
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2009, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('Daemon.class.php');
require_once('Engine.class.php');
require_once('Hostname.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Mysql class.
 *
 * @package Api
 * @subpackage Daemon
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2009, Point Clark Networks
 */

class Mysql extends Daemon
{
	///////////////////////////////////////////////////////////////////////////////
	// M E M B E R S
	///////////////////////////////////////////////////////////////////////////////

	protected $hostname;

	const CMD_MYSQLADMIN = "/usr/bin/mysqladmin";
	const CMD_MYSQL = "/usr/bin/mysql";

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Mysql constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct("mysqld");
	}

	/**
	 * Checks that the password for given hostname is set.
	 *
	 * @param string $username username
	 * @param string $hostname hostname
	 * @return boolean true if set
	 * @throws EngineException, ValidationException
	 */

	function IsPasswordSet($username, $hostname)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$shell = new ShellExec();
			$retval = $shell->Execute(self::CMD_MYSQLADMIN, "-u$username -h$hostname --protocol=tcp status");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		if ($retval == 0)
			return false;
		else
			return true;
	}

	/**
	 * Checks that the password for both localhost and hostname is set.
	 *
	 * @return boolean true if set
	 * @throws EngineException
	 */

	function IsRootPasswordSet()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if ($this->hostname == null)
			$this->_GetHostname();

		if ($this->IsPasswordSet('root', 'localhost'))
			return true;
		else
			return false;
	}

	/**
	 * Sets the database password for localhost and hostname.
	 *
	 * @param string $username username
	 * @param string $oldpassword old password
	 * @param string $password password
	 * @param string $hostname hostname
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	function SetPassword($username, $oldpassword, $password, $hostname)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if ($oldpassword)
			$passwd_param = "-p\"$oldpassword\"";
		else
			$passwd_param = "";

		try {
			$shell = new ShellExec();
			$options = array();
            $options['escape'] = true;

			$retval = $shell->Execute(self::CMD_MYSQLADMIN, "-u$username $passwd_param -h$hostname --protocol=tcp password \"$password\"", false, $options);

			if ($retval != 0)
				throw new EngineException($shell->GetLastOutputLine(), COMMON_WARNING);

			// Not fatal if it fails
			$shell->Execute(self::CMD_MYSQLADMIN, "-u$username $passwd_param -h$hostname --protocol=tcp flush-privileges");

		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Sets the root password.
	 */

	function SetRootPassword($oldpassword, $password)
	{
		if ($this->hostname == null)
			$this->_GetHostname();

		$this->SetPassword('root', $oldpassword, $password, 'localhost');

		// Set password for 127.0.0.1 as well
		try {
			$this->SetPassword('root', $oldpassword, $password, '127.0.0.1');
		} catch (Exception $e) {
			// Not fatal
		}
	}

	/**
	 * Sets the hostname field.
	 * 
	 * @return void
	 * @throws EngineException
	 */

	function _GetHostname()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$host = new Hostname();
			$this->hostname = $host->Get();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}
}

// vim: syntax=php ts=4
?>
