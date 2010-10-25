<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2010 Point Clark Networks.
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
 * FreeRadius class.
 *
 * @package Api
 * @subpackage Daemon
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2010, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('Daemon.class.php');

// FIXME - translate
define('FREERADIUS_LANG_CLIENT', 'Client');
define('FREERADIUS_LANG_CLIENTS', 'Clients');
define('FREERADIUS_LANG_NICKNAME', 'Nickname');
define('FREERADIUS_LANG_GROUP', 'Group');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * FreeRadius class.
 *
 * @package Api
 * @subpackage Daemon
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2010, Point Clark Networks
 */

class FreeRadius extends Daemon
{
	///////////////////////////////////////////////////////////////////////////////
	// F I E L D S
	///////////////////////////////////////////////////////////////////////////////

	protected $is_loaded = FALSE;
	protected $clients = array();

	const FILE_CLIENTS = '/etc/raddb/clearos-clients.conf';
	const FILE_USERS = '/etc/raddb/clearos-users';

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * FreeRadius constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		parent::__construct('radiusd');

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Adds a client.
	 *
	 * @param string $address client address
	 * @param string $secret client secret
	 * @param string $nickname client nickname
	 * @return array clients information
	 * @throws EngineException
	 */

	function AddClient($address, $secret, $nickname)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		// FIXME: validation

		if (isset($this->clients[$address]))
			throw new EngineException(FREERADIUS_LANG_CLIENT . ' - ' . LOCALE_LANG_EXISTS, COMMON_WARNING);

		// FIXME: allowed passwords?  Should it be quoted?
		$this->clients[$address]['secret'] = $secret;
		$this->clients[$address]['shortname'] = $nickname;

		$this->_SaveConfig();
	}

	/**
	 * Deletes a client.
	 *
	 * @param string $address client address
	 * @return array clients information
	 * @throws EngineException
	 */

	function DeleteClient($address)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		if (! isset($this->clients[$address]))
			throw new EngineException(FREERADIUS_LANG_CLIENT . ' - ' . LOCALE_LANG_INVALID, COMMON_WARNING);

		unset($this->clients[$address]);

		$this->_SaveConfig();
	}

	/**
	 * Returns client information for a given nickname.
	 *
	 * @param string $address client address
	 * @return array clients information
	 * @throws EngineException
	 */

	function GetClientInfo($address)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		if (!isset($this->clients[$address]))
			throw new EngineException(FREERADIUS_LANG_CLIENT . ' - ' . LOCALE_LANG_INVALID, COMMON_WARNING);

		return $this->clients[$address];
	}

	/**
	 * Returns clients information.
	 *
	 * @return array clients information
	 * @throws EngineException
	 */

	function GetClients()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		return $this->clients;
	}
	/**
	 * Returns user defined RADIUS group.
	 *
	 * @return string user defined RADIUS group
	 * @throws EngineException
	 */

	function GetGroup()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$lines = array();

		try {
			$usersfile = new File(self::FILE_USERS, TRUE);
			$lines = $usersfile->GetContentsAsArray();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		$group = '';

		foreach ($lines as $line) {
			$matches = array();
			if (preg_match('/^DEFAULT LDAP-Group .= "([^\"]*)",/', $line, $matches)) {
				$group = $matches[1];
				break;
			}
		}

		return $group;
	}

	/**
	 * Updates client information.
	 *
	 * @param string $address client address
	 * @param string $secret client secret
	 * @param string $nickname client nickname
	 * @return void
	 * @throws EngineException
	 */

	function UpdateClient($address, $secret, $nickname)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		if (! isset($this->clients[$address]))
			throw new EngineException(FREERADIUS_LANG_CLIENT . ' - ' . LOCALE_LANG_INVALID, COMMON_WARNING);

		$this->clients[$address]['shortname'] = $nickname;
		$this->clients[$address]['secret'] = $secret;

		$this->_SaveConfig();
	}

	/**
	 * Updates user defined RADIUS group.
	 *
	 * @param string $group user defined RADIUS group
	 * @return void
	 * @throws EngineException
	 */

	function UpdateGroup($group)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$file = new File(self::FILE_USERS, TRUE);
			if ($file->Exists())
				$file->Delete();

			$file->Create('root', 'radiusd', '0640');
			$file->AddLines("DEFAULT LDAP-Group != \"$group\", Auth-Type := Reject\n");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

	///////////////////////////////////////////////////////////////////////////////
	// P R I V A T E  M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Loads configuration file.
	 *
	 * @access private
	 * @return void
	 * @throws EngineException
	 */

	private function _LoadConfig()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$lines = array();

		try {
			$clientsfile = new File(self::FILE_CLIENTS, TRUE);
			$lines = $clientsfile->GetContentsAsArray();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		$client = '';
		$this->clients = array();

		foreach ($lines as $line) {
			$matches = array();

			if (preg_match('/^\s*client\s*([^\s]+)\s*{/', $line, $matches))
				$client = $matches[1];
			else if (preg_match('/^\s*secret\s*=\s*([^\s]+)/', $line, $matches))
				$this->clients[$client]['secret'] =  $matches[1];
			else if (preg_match('/^\s*shortname\s*=\s*([^\s]+)/', $line, $matches))
				$this->clients[$client]['shortname'] =  $matches[1];
		}

		ksort($this->clients);

		$this->is_loaded = TRUE;
	}

	/**
	 * Saves configuration file.
	 *
	 * @access private
	 * @return void
	 * @throws EngineException
	 */

	private function _SaveConfig()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$contents = '';

		foreach ($this->clients as $client => $details) {
			$contents .= "client $client {\n";
			$contents .= "\tsecret = " . $details['secret'] . "\n";
			$contents .= "\tshortname = " . $details['shortname'] . "\n";
			$contents .= "}\n";
		}

		try {
			$clientsfile = new File(self::FILE_CLIENTS, TRUE);

			if ($clientsfile->Exists())
				$clientsfile->Delete();

			$clientsfile->Create('root', 'radiusd', '0640');
			$clientsfile->AddLines($contents);

			$this->is_loaded = FALSE;
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
