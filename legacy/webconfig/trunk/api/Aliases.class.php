<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2003-2007 Point Clark Networks.
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
 * Mail aliases class.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2007, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('File.class.php');
require_once('Postfix.class.php');
require_once('ShellExec.class.php');
require_once('UserManager.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Mail aliases class.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2007, Point Clark Networks
 */

class Aliases extends Engine
{
	///////////////////////////////////////////////////////////////////////////////
	// V A R I A B L E S
	///////////////////////////////////////////////////////////////////////////////

	protected $is_loaded = false;
	protected $virtual = null;
	protected $user_list = array();
	protected $email_list = array();
	protected $alias_list = array();
	protected $protected_aliases = array('MAILER-DAEMON', 'abuse', 'postmaster', 'security', 'root');

	const FILE_ALIASES = '/etc/aliases';
	const FILE_VIRTUAL = '/etc/postfix/virtual';
	const CMD_POSTMAP = '/usr/sbin/postmap';
	const CMD_NEWALIASES = '/usr/bin/newaliases';

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Aliases constructor.
	 *
	 * @param string $virtual virtual domain
	 * @return void
	 */

	function __construct($virtual = null)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->virtual = $virtual;

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Returns list of available users.
	 *
	 * @return array list of users
	 * @throws EngineException
	 */

	function GetUsers()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		return $this->user_list;
	}

	/**
	 * Returns list of external e-mail addresses.
	 *
	 * @return array list of external e-mail addresses
	 * @throws EngineException
	 */

	function GetEmails()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		return $this->email_list;
	}

	/**
	 * Returns list of aliases.
	 *
	 * @return array list of aliases
	 * @throws EngineException
	 */

	function GetAliases()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		return $this->alias_list;
	}

	/**
	 * Returns list of redirects for given alias.
	 *
	 * @return array list of alias redirect 
	 * @throws ValidationException, EngineException
	 */

	function GetRedirect($alias)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$redirect = array();

		if (! $this->IsValidAlias($alias)) {
			$errmsg = ALIASES_LANG_ERRMSG_ALIAS_INVALID1 . " $alias " . ALIASES_LANG_ERRMSG_ALIAS_INVALID2;
			throw new ValidationException($errmsg);
		}

		try {
			if ($this->virtual == null) {
				$file = new File(self::FILE_ALIASES);
				$line = $file->LookupValue("/^$alias:/i");
			} else {
				$file = new File(self::FILE_VIRTUAL);
				$line = $file->LookupValue("/^$alias@" . $this->virtual . "/i");
			}

			if ($line)
				$redirect = explode(',', preg_replace("/ /", "", $line));
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		return $redirect;
	}

	/**
	 * Adds	an alias.
	 *
	 * @param string $alias alias
	 * @param array $addresses list of email addresses to alias to
	 * @return string redirection email
	 * @throws ValidationException, DuplicateException, EngineException
	 */

	function AddAlias($alias, $addresses)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		if (! $this->IsValidAlias($alias)) {
			$errmsg = ALIASES_LANG_ERRMSG_ALIAS_INVALID1 . " $alias " . ALIASES_LANG_ERRMSG_ALIAS_INVALID2;
			throw new ValidationException($errmsg);
		}

		# Check if alias exists
		if (in_array($alias, $this->alias_list)) {
			$errmsg = ALIASES_LANG_ERRMSG_ALIAS_EXISTS1 . " $alias " . ALIASES_LANG_ERRMSG_ALIAS_EXISTS2;
			throw new DuplicateException($errmsg);
		}

		# Check for at least one redirect
		if (count($addresses) == 0)
			throw new ValidationException(ALIASES_LANG_ERRMSG_REQUIRE_AT_LEAST_ONE_ENTRY);

		# Validate emails
		foreach ($addresses as $address) {
			// TODO - Fix flexshare aliases
			if ($address == "flexshare")
				continue;

			if (in_array($address, $this->user_list))
				continue;

			if (in_array($address, $this->email_list))
				continue;

			if (in_array($address, $this->alias_list))
				continue;

			if ($this->IsValidEmail($address))
				continue;

			throw new ValidationException(ALIASES_LANG_ERRMSG_INVALID_ADDRESS . " ($alias -> $address)");
		}

		# Convert to comma seperated string
		$address_list = implode(', ', $addresses);

		# Add alias to the aliases file
		try {
			if ($this->virtual == null) {
				$file = new File(self::FILE_ALIASES);
				$file->AddLines("$alias: $address_list\n");
			} else {
				$file = new File(self::FILE_VIRTUAL);

				try {
					$file->AddLinesAfter("$alias@" . $this->virtual . " $address_list\n", "/^" . $this->virtual . "/i");
				} catch (FileNoMatchException $e) {
					$file->AddLines($this->virtual . "\n");
					$file->AddLines("$alias@" . $this->virtual . " $address_list\n");
				}
			}
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		$this->_RunNewAliases();
		$this->is_loaded = false;
	}

	/**
	 * Adds an e-mail redirect to an existing alias.
	 *
	 * @param string $alias alias
	 * @param array $emails list of emails
	 * @return void
	 * @throws EngineException
	 */

	function AddRedirectEmails($alias, $emails)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$current_redirects = $this->GetRedirect($alias);
		$new_redirects = array_merge($current_redirects, $emails);

		$this->SetAlias($alias, $new_redirects);

		$this->_RunNewAliases();
		$this->is_loaded = false;
	}

	/**
	 * Adds a user redirect to an existing alias.
	 *
	 * @param string $alias alias
	 * @param array $users list of users
	 * @return void
	 * @throws EngineException
	 */

	function AddRedirectUsers($alias, $users)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$current = $this->GetRedirect($alias);

		$this->SetAlias($alias, array_merge($current, $users));

		$this->_RunNewAliases();
		$this->is_loaded = false;
	}

	/**
	 * Deletes an alias.
	 *
	 * @param string $alias alias
	 * @return void
	 * @throws ValidationException, EngineException
	 */

	function DeleteAlias($alias)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		if (! $this->IsValidAlias($alias)) {
			$errmsg = ALIASES_LANG_ERRMSG_ALIAS_INVALID1 . " $alias " . ALIASES_LANG_ERRMSG_ALIAS_INVALID2;
			throw new ValidationException($errmsg);
		}

		# Check if alias exists
		if (! in_array($alias, $this->alias_list)) {
				$errmsg = ALIASES_LANG_ERRMSG_ALIAS_MISSING1 . " $alias " . ALIASES_LANG_ERRMSG_ALIAS_MISSING2;
				throw new ValidationException($errmsg);
			}

		try {
			if ($this->virtual == null) {
				$file = new File(self::FILE_ALIASES);
				$file->DeleteLines("/^$alias:/i");
			} else {
				$file = new File(self::FILE_VIRTUAL);
				$file->DeleteLines("/^$alias@" . $this->virtual . "/i");
			}
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		$this->_RunNewAliases();
		$this->is_loaded = false;
	}

	/**
	 * Sets an alias.
	 *
	 * @param string $alias  alias
	 * @param array $addresses  list of email addresses to alias to
	 * @return void
	 * @throws ValidationException, EngineException
	 */

	function SetAlias($alias, $addresses)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		if (! $this->IsValidAlias($alias)) {
			$errmsg = ALIASES_LANG_ERRMSG_ALIAS_INVALID1 . " $alias " . ALIASES_LANG_ERRMSG_ALIAS_INVALID2;
			throw new ValidationException($errmsg);
		}

		# Check if alias exists
		if (! in_array($alias, $this->alias_list)) {
				$errmsg = ALIASES_LANG_ERRMSG_ALIAS_MISSING1 . " $alias " . ALIASES_LANG_ERRMSG_ALIAS_MISSING2;
				throw new ValidationException($errmsg);
			}

		# Check for at least one redirect
		if (count($addresses) == 0) {
				$errmsg = ALIASES_LANG_ERRMSG_REQUIRE_AT_LEAST_ONE_ENTRY;
				throw new ValidationException($errmsg);
			}

		# Validate emails
		foreach ($addresses as $address) {
			// TODO - Fix flexshare aliases
			if ($address == "flexshare")
				continue;

			if (in_array($address, $this->user_list))
				continue;

			if (in_array($address, $this->email_list))
				continue;

			if (in_array($address, $this->alias_list))
				continue;

			if ($this->IsValidEmail($address))
				continue;

			// The preg_match below is just a workaround for the following 
			// scenario: a required alias (e.g. root) is pointing to a now
			// non-existent account. There is no way to delete this alias
			// without triggering a validation exception

			if (! preg_match("/@/", $address))
				continue;

			throw new ValidationException(ALIASES_LANG_ERRMSG_INVALID_ADDRESS . " ($alias -> $address)");
		}

		# Convert to comma seperated string
		$address_list = implode(', ', $addresses);

		# Update alias within the '/etc/aliases' file

		try {
			if ($this->virtual == null) {
				$file = new File(self::FILE_ALIASES);
				$file->ReplaceLines("/^$alias:/i", "$alias: $address_list\n");
			} else {
				$file = new File(self::FILE_VIRTUAL);
				$file->ReplaceLines("/^$alias@" . $this->virtual . "/i", "$alias@" . $this->virtual . " $address_list\n");
			}
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		$this->_RunNewAliases();
		$this->is_loaded = false;
	}

	/**
	 * Returns a list of required aliases.
	 *
	 * @return array a list of protected aliases
	 * @throws ValidationException, EngineException
	 */

	function GetProtectedAliases()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		return $this->protected_aliases;
	}

	///////////////////////////////////////////////////////////////////////////////
	// V A L I D A T I O N   R O U T I N E S
	///////////////////////////////////////////////////////////////////////////////

	function IsAliasEmpty($alias)
	{
		if (!$alias) {
			$errmsg = ALIASES_LANG_ERRMSG_ALIAS_EMPTY;
			return true;
		}

		return false;
	}

	function IsValidAlias($alias)
	{
		if(!eregi("^[a-z0-9\._-]", $alias)) {
			$errmsg = ALIASES_LANG_ERRMSG_ALIAS_INVALID1 . " '$alias' " . ALIASES_LANG_ERRMSG_ALIAS_INVALID2;
			return false;
		}

		return true;
	}

	function IsValidEmail($target)
	{
		if(!eregi("^[a-z0-9\._-]+@+[a-z0-9\._-]+\.+[a-z]{2,4}$", $target)) {
			$errmsg = ALIASES_LANG_ERRMSG_TARGET_INVALID1 . " '$target' " . ALIASES_LANG_ERRMSG_TARGET_INVALID2;
			return false;
		}

		return true;
	}

	///////////////////////////////////////////////////////////////////////////////
	// P R I V A T E   M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Runs the newaliases command.
	 *
	 * @access private
	 * @return void
	 * @throws EngineException
	 */

	function _RunNewAliases()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$shell = new ShellExec();

			if (empty($this->virtual)) {
				$shell->Execute(self::CMD_NEWALIASES, "", true);
			} else {
				$file = new File(self::FILE_VIRTUAL);
				if ($file->Exists())
					$shell->Execute(self::CMD_POSTMAP, self::FILE_VIRTUAL, true);
			}
		} catch (Exception $e) {
			throw new EngineException ($e->GetMessage(), COMMON_ERROR);
		}
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
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		# Reset our data structures
		$this->is_loaded = false;
		$this->user_list = array();
		$this->email_list = array();
		$this->alias_list = array();
		$users = array();

		# If domain is found in virtual domains, use it...otherwise, set to null (it came from master)

		if ($this->virtual != null) {
			try {
				$postfix = new Postfix();
				$virtual_hosts = $postfix->GetVirtualDomains();

				if (! in_array($this->virtual, $virtual_hosts))
					$this->virtual = null;
			} catch (Exception $e) {
				throw new EngineException ($e->GetMessage(), COMMON_ERROR);
			}
		}

		# Get user list
		try {
			$usermanager = new UserManager();
			$users = $usermanager->GetAllUsers(UserManager::TYPE_EMAIL);
		} catch (Exception $e) {
			throw new EngineException ($e->GetMessage(), COMMON_ERROR);
		}

		# Non-virtual uses direct list
		if ($this->virtual == null)
			$this->user_list = $users;

		# Get aliases & external e-mail addresses

		try {
			if ($this->virtual != null) {
				$file = new File(self::FILE_VIRTUAL);

				if (! $file->Exists())
					throw new EngineException(FILE_LANG_ERRMSG_EXISTS . " - " . self::FILE_VIRTUAL, COMMON_ERROR);
			} else {
				$file = new File(self::FILE_ALIASES);

				if (! $file->Exists())
					throw new EngineException(FILE_LANG_ERRMSG_EXISTS . " - " . self::FILE_ALIASES, COMMON_ERROR);
			}

			$lines = $file->GetContentsAsArray();
		} catch (Exception $e) {
			throw new EngineException ($e->GetMessage(), COMMON_ERROR);
		}

		// Load data structures
		//---------------------

		foreach ($lines as $line) {
			# Virtual domain

			if ($this->virtual != null) {
				if ($line && !preg_match("/^[#\s]/", $line) && preg_match("/@" . $this->virtual . "/", $line)) {
					$line_arr = explode(",", $line);
					$sub_line_arr = explode(" ", $line_arr[0]);

					# Add the alias to the alias_lists.

					$sub_line_arr[0] = trim($sub_line_arr[0]);
					$alias = explode("@", $sub_line_arr[0]);

					if(!$alias[0])
						continue;

					if(!in_array($alias[0], $users))
						$this->alias_list[] = trim($alias[0]);

					# Get users

					if (strncasecmp($alias[0], $sub_line_arr[1], sizeof($alias[0])) == 0) {
						if(in_array($sub_line_arr[1], $users) && !in_array(trim($sub_line_arr[1]), $this->user_list))
							$this->user_list[] = trim($sub_line_arr[1]);
					}

					# Checking all alias redirections
					$line_arr[0] = $sub_line_arr[1];

					$line_arr[0] = trim($line_arr[0]);

					for ($index = 0; $index < sizeof($line_arr); $index++) {
						$address = eregi_replace("[[:space:]]+", "", $line_arr[$index]);

						# If e-mail address then add to the external address_list

							if ($this->IsValidEmail($address))
								$this->email_list[] = $address;
					}
				}

				# Master domain

			} else {
				$found   = strstr($line, ':');
				$comment = strstr($line, '#');

				if ($found && ! $comment) {
					$line  = split("[:]", $line);
					$alias = eregi_replace("[[:space:]]+", '', $line[0]);

					if (strncasecmp($alias, '', strlen($alias)) != 0) {
						$address_arr = split("[,]", $line[1]);

						// Checking all alias redirections

						for ($index = 0; $index < sizeof($address_arr); $index++) {
							$address = eregi_replace("[[:space:]]+", '', $address_arr[$index]);

							// If e-mail address then add to the external address_list

							if ($this->IsValidEmail($address))
								$this->email_list[] = $address;
						}

						// Add the alias to the alias_lists.
						$this->alias_list[] = $alias;
					}
				}
			}
		}

		sort($this->user_list);
		sort($this->email_list);
		sort($this->alias_list);
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
