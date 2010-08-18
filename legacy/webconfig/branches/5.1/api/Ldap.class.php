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
// Based on orginal work
// Copyright (c) 2004,2005 Klarälvdalens Datakonsult AB
// Written by Steffen Hansen <steffen@klaralvdalens-datakonsult.se>
//
///////////////////////////////////////////////////////////////////////////////

/**
 * LDAP class.
 *
 * @package Api
 * @subpackage Daemon
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006-2008, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once("ConfigurationFile.class.php");
require_once("Daemon.class.php");
require_once("File.class.php");
require_once("Folder.class.php");
require_once("Hostname.class.php");
require_once("ShellExec.class.php");

///////////////////////////////////////////////////////////////////////////////
// E X C E P T I O N  C L A S S E S
///////////////////////////////////////////////////////////////////////////////

/**
 * LDAP unavailable exception.
 *
 * @package Api
 * @subpackage Exception
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

class LdapUnavailableException extends EngineException
{
    /**
     * LdapUnavailableException constructor.
     */

    public function __construct()
    {
        parent::__construct(LDAP_LANG_DIRECTORY_UNAVAILABLE, COMMON_INFO);
    }
}

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * LDAP class.
 *
 * This is basically a wrapper class for the PHP ldap_* functions.  This not
 * only hides some of the details from the end-user (for instance, the base DN)
 * but also provides PHP exceptions.
 *
 * @package Api
 * @subpackage Daemon
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006-2008, Point Clark Networks
 */

class Ldap extends Daemon
{
	///////////////////////////////////////////////////////////////////////////////
	// F I E L D S
	///////////////////////////////////////////////////////////////////////////////

	protected $connection = null;
	protected $search_result = false;
	protected $bound = false;
	protected $is_loaded = false;
	protected $config = null;

	const COMMAND_SLAPADD = '/usr/sbin/slapadd';
	const COMMAND_SLAPCAT = '/usr/sbin/slapcat';
	const COMMAND_SLAPPASSWD = "/usr/sbin/slappasswd";
	const COMMAND_KOLABCONF = '/usr/sbin/kolabconf';
	const COMMAND_LDAPSYNC = '/usr/sbin/ldapsync';
	const CONSTANT_LOCALHOST = 'localhost';
	const CONSTANT_LAN = 'lan';
	const CONSTANT_COMPUTERS_OU = 'ou=Computers,ou=Accounts';
	const CONSTANT_GROUPS_OU = 'ou=Groups,ou=Accounts';
	const CONSTANT_SERVERS_OU = 'ou=Servers';
	const CONSTANT_USERS_OU = 'ou=Users,ou=Accounts';
	const CONSTANT_MASTER_CN = 'cn=Master';
	const FILE_KOLAB_CONFIG = '/etc/kolab/kolab.conf';
	const FILE_SLAPD_CONFIG = '/etc/openldap/slapd.conf';
	const FILE_SLAPD_SYSCONFIG = '/etc/sysconfig/ldap';
	const FILE_LDIF_BACKUP = '/etc/openldap/backup.ldif';
	const PATH_LDAP = '/var/lib/ldap';
	const PATH_LDAP_BACKUP = '/usr/share/system/modules/ldap';

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Ldap constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called",__METHOD__,__LINE__);

		parent::__construct("ldap");

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Performs LDAP add..
	 *
	 * @param string $dn
	 * @param array $attrs
	 * @return void
	 * @throws EngineException
	 */

	function Add($dn, $attrs)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->bound)
			$this->Bind();

		if (! ldap_add($this->connection, $dn, $attrs))
			throw new EngineException(ldap_error($this->connection), COMMON_ERROR);
	}

	/**
	 * Loads default settings, connects and binds to LDAP server.
	 *
	 * @access private
	 * @return void
	 * @throws EngineException, LdapUnavailableException
	 */

	function Bind()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called",__METHOD__,__LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		$this->connection = ldap_connect('127.0.0.1');

		if (! ldap_set_option($this->connection, LDAP_OPT_PROTOCOL_VERSION, 3))
			throw new EngineException(LDAP_LANG_ERRMSG_FUNCTION_FAILED, COMMON_ERROR);

		if (! @ldap_bind($this->connection, $this->config['bind_dn'], $this->config['bind_pw'])) {
			if (ldap_errno($this->connection) === -1)
				throw new LdapUnavailableException();
			else
				throw new EngineException($this->Error(), COMMON_WARNING);
		}

		$this->bound = true;
	}

	/**
	 * Closes LDAP connection.
	 *
	 * @return void
	 */

	function Close()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if ($this->search_result) {
			ldap_free_result($this->search_result);
			$this->search_result = null;
		}

		ldap_close($this->connection);

		$this->connection = null;
		$this->bound = false;
	}

	/**
	 * Deletes an an LDAP object
	 *
	 * @param string $dn DN
	 * @return void
	 * @throws EngineException
	 */

	function Delete($dn)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! ldap_delete($this->connection, $dn))
			throw new EngineException(LDAP_LANG_ERRMSG_FUNCTION_FAILED, COMMON_ERROR);
	}

	/**
	 * Checks the existence of given DN.
	 *
	 * @param string $dn
	 * @return true if DN exists
	 * @throws EngineException
	 */

	function Exists($dn)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->bound)
			$this->Bind();

		$result = @ldap_read($this->connection, $dn, "(objectclass=*)");

		if (empty($result))
			return false;
		else
			return true;
	}

	/**
	 * Returns LDAP error.
	 *
	 * @return string LDAP error
	 */

	function Error()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		return ldap_error($this->connection);
	}

	/**
	 * Returns attributes from a search result.
	 *
	 * @return array attributes in array format
	 * @throws EngineException
	 */

	function GetAttributes($entry)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$attributes = ldap_get_attributes($this->connection, $entry);

		if (! $attributes)
			throw new EngineException(LDAP_LANG_ERRMSG_FUNCTION_FAILED, COMMON_ERROR);

		return $attributes;
	}

	/** 
	 * Returns configured base DN.
	 *
	 * @return string base DN
	 * @throws EngineException
	 */

	function GetBaseDn()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		return $this->config['base_dn'];
	}

	/** 
	 * Returns configured bind DN.
	 *
	 * @return string bind DN
	 * @throws EngineException
	 */

	function GetBindDn()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		return $this->config['bind_dn'];
	}

	/** 
	 * Returns configured bind password.
	 *
	 * @return string bind password
	 * @throws EngineException
	 */

	function GetBindPassword()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		return $this->config['bind_pw'];
	}

	/** 
	 * Returns bind policy.
	 * 
	 * @return int policy constant
	 * @throws EngineException
	 */

	function GetBindPolicy()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$file = new ConfigurationFile(self::FILE_SLAPD_SYSCONFIG);

		try {
			$sysconfig = $file->Load();

			if (empty($sysconfig['BIND_POLICY']))
				return self::CONSTANT_LOCALHOST;
			else if ($sysconfig['BIND_POLICY'] == self::CONSTANT_LAN)
				return self::CONSTANT_LAN;
			else
				return self::CONSTANT_LOCALHOST;

		} catch (FileNotFoundException $e) {
			return self::CONSTANT_LOCALHOST;
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(),COMMON_ERROR);
		}
	}

	/** 
	 * Returns the OU for computers.
	 *
	 * @return string OU for computers.
	 * @throws EngineException
	 */

	function GetComputersOu()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		return Ldap::CONSTANT_COMPUTERS_OU . ',' . $this->config['base_dn'];
	}

	/** 
	 * Returns configured default home server.
	 *
	 * @return string default home server
	 * @throws EngineException
	 */

	function GetDefaultHomeServer()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		return $this->config['fqdnhostname'];
	}

	/** 
	 * Returns configured default domain.
	 *
	 * @return string default domain
	 * @throws EngineException
	 */

	function GetDefaultDomain()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		$domain = preg_replace("/(,dc=)/", ".", $this->config['base_dn']);
		$domain = preg_replace("/dc=/", "", $domain);

		return $domain;
	}

	/**
	 * Returns DN of a result entry.
	 *
	 * @return string DN
	 * @throws EngineException
	 */

	function GetDn($entry)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$dn = ldap_get_dn($this->connection, $entry);

		if (! $dn)
			throw new EngineException(ldap_error($this->connection), COMMON_ERROR);

		return $dn;
	}

	/**
	 * Return DN for given user ID (username).
	 *
	 * @param string $uid user ID
	 * @return string DN
	 * @throws EngineException
	 */

	function GetDnForUid($uid)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->bound)
			$this->Bind();

		$this->Search('(&(objectclass=posixAccount)(uid=' . $this->Escape($uid) . '))');
		$entry = $this->GetFirstEntry();

		$dn = "";

		if ($entry)
			$dn = $this->GetDn($entry);

		return $dn;
	}

	/**
	 * Returns LDAP entries.
	 *
	 * @return array complete result information in a multi-dimensional array
	 * @throws EngineException
	 */

	function GetEntries()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$entries = ldap_get_entries($this->connection, $this->search_result);

		if (! $entries)
			throw new EngineException(ldap_error($this->connection), COMMON_ERROR);

		return $entries;
	}

	/**
	 * Returns first LDAP entry.
	 *
	 * @return resource result entry identifier for the first entry.
	 */

	function GetFirstEntry()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$entry = ldap_first_entry($this->connection, $this->search_result);

		return $entry;
	}

	/** 
	 * Returns the OU for groups.
	 *
	 * @return string OU for groups.
	 * @throws EngineException
	 */

	function GetGroupsOu()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		return Ldap::CONSTANT_GROUPS_OU . ',' . $this->config['base_dn'];
	}

	/** 
	 * Returns configured LDAP URI.
	 *
	 * @return string LDAP URI
	 * @throws EngineException
	 */

	function GetLdapUri()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		return $this->config['ldap_uri'];
	}

	/** 
	 * Returns the DN for master LDAP server.
	 *
	 * @return string DN for master LDAP server.
	 * @throws EngineException
	 */

	function GetMasterDn()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		return Ldap::CONSTANT_MASTER_CN . ',' . Ldap::CONSTANT_SERVERS_OU . ',' . $this->config['base_dn'];
	}

	/** 
	 * Returns the OU for servers.
	 *
	 * @return string OU for servers.
	 * @throws EngineException
	 */

	function GetServersOu()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		return Ldap::CONSTANT_SERVERS_OU . ',' . $this->config['base_dn'];
	}

	/** 
	 * Returns the OU for users.
	 *
	 * @return string OU for users.
	 * @throws EngineException
	 */

	function GetUsersOu()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		return Ldap::CONSTANT_USERS_OU . ',' . $this->config['base_dn'];
	}

	/**
	 * Modifies LDAP entry.
	 *
	 * @param string $dn DN
	 * @param string $entry entry
	 * @return void
	 * @throws EngineException
	 */

	function Modify($dn, $entry)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$ok = ldap_modify($this->connection, $dn, $entry);

		if (!$ok)
			throw new EngineException(ldap_error($this->connection), COMMON_ERROR);
	}

	/**
	 * Modifies LDAP list of entries.
	 *
	 * @param string $dn DN
	 * @param string $entry entry
	 * @return void
	 * @throws EngineException
	 */

	function ModifyMembers($dn, $entries)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$ok = ldap_modify($this->connection, $dn, $entry);

		if (!$ok)
			throw new EngineException(ldap_error($this->connection), COMMON_ERROR);
	}

	/**
	 * Returns next result entry.
	 *
	 * @return resource next result entry
	 */

	function NextEntry($entry)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		return ldap_next_entry($this->connection, $entry);
	}

	/**
	 * Performs LDAP read operation.
	 *
	 * @param string $dn DN
	 * @return array complete entry information
	 * @throws EngineException
	 */

	function Read($dn)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->bound)
			$this->Bind();

		$result = @ldap_read($this->connection, $dn, "(objectclass=*)");

		if (!$result)
			throw new EngineException(ldap_error($this->connection), COMMON_ERROR);

		$entry = ldap_first_entry($this->connection, $result);

		if (!$entry) {
			ldap_free_result($result);
			return;
		}

		$ldap_object = ldap_get_attributes($this->connection, $entry);

		if (! $ldap_object)
			throw new EngineException(ldap_error($this->connection), COMMON_ERROR);

		ldap_free_result($result);

		return $ldap_object;
	}

	/**
	 * Performs LDAP rename.
	 *
	 * @param string $dn DN
	 * @param string $rdn new RDN
	 * @param string $newparent new parent
	 * @return void
	 * @throws EngineException
	 */

	function Rename($dn, $rdn, $newparent = null)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->bound)
			$this->Bind();

		if (empty($newparent))
			$newparent = $this->base_dn;

		if (! ldap_rename($this->connection, $dn, $rdn, $newparent, true))
			throw new EngineException(ldap_error($this->connection), COMMON_WARNING);
	}

	/**
	 * Performs LDAP search.
	 *
	 * @param string $filter filter
	 * @param array $attrs attributes
	 * @return handle search result identifier.
	 * @throws EngineException
	 */

	function Search($filter, $base_dn = null, $attrs = null)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->bound)
			$this->Bind();

		$this->_FreeSearchResult();

		if ($base_dn == null)
			$base_dn = $this->base_dn;

		if ($attrs == null)
			$this->search_result = ldap_search($this->connection, $base_dn, $filter);
		else
			$this->search_result = ldap_search($this->connection, $base_dn, $filter, $attrs);

		if (! $this->search_result)
			throw new EngineException(ldap_error($this->connection), COMMON_ERROR);

		return $this->search_result;
	}

	/** 
	 * Sets bind policy.
	 *
	 * The LDAP server can be configured to listen on localhost only 
	 * CONSTANT_LOCALHOST, or localhost and all LAN interfaces (CONSTANT_LAN).
	 *
	 * @param boolean $policy policy setting
	 *
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	function SetBindPolicy($policy)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! (($policy == self::CONSTANT_LOCALHOST) || ($policy == self::CONSTANT_LAN)))
			throw new ValidationException(LDAP_LANG_BIND_POLICY . " - " . LOCALE_LANG_INVALID);

		try {
			$file = new File(self::FILE_SLAPD_SYSCONFIG);

			if ($file->Exists()) {
				$matches = $file->ReplaceLines("/^BIND_POLICY=.*/", "BIND_POLICY=$policy\n");
				if ($matches === 0)
					$file->AddLines("BIND_POLICY=$policy\n");
			} else {
				$file->Create("root", "root", "0644");
				$file->AddLines("BIND_POLICY=$policy\n");
			}
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(),COMMON_ERROR);
		}
	}

	/**
	 * Sorts LDAP result.
	 *
	 * @param handle $result search handle
	 * @param string $sortfilter attribute used for sorting
	 * @return void
	 */

	function Sort(&$result, $sortfilter)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		ldap_sort($this->connection, $result, $sortfilter);
	}

	/**
	 * Returns user ID (username) for given DN.
	 *
	 * @param string $dn
	 * @return string user ID
	 * @throws EngineException
	 */

	function UidForDn($dn)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->bound)
			$this->Bind();

		$res = @ldap_read($this->connection, $dn, '(objectclass=*)', array('uid'));

		if (! $res) {
			if (ldap_errno($this->connection) == 0x20) // LDAP_NO_SUCH_OBJECT
				return "";
			else
				throw new EngineException(ldap_error($this->connection), COMMON_ERROR);
		}

		$entries = ldap_get_entries($this->connection, $res);
		ldap_free_result($res);

		if ($entries['count'] >= 1)
			return $entries[0]['uid'][0];
		else
			return "";
	}

	///////////////////////////////////////////////////////////////////////////////
	// S T A T I C  M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * LDAP DN escaping as described in RFC 2253.
	 *
	 * @access private
	 * @param string $string string
	 * @return string escaped string
	 */

	static function DnEscape($string)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$string = str_replace('\\', '\\\\', $string);
		$string = str_replace(',', '\\,', $string);
		$string = str_replace('+', '\\,', $string);
		$string = str_replace('<', '\\<', $string);
		$string = str_replace('>', '\\>', $string);
		$string = str_replace(';', '\\;', $string);

		if ($string[0] == '#')
			$string = '\\' . $string;

		return $string;
	}

	/**
	 * LDAP sring escaping as described in RFC-2254.
	 *
	 * If a value should contain any of the following characters
	 * - * 0x2a
	 * - ( 0x28
	 * - ) 0x29
	 * - \ 0x5c
	 * - NUL 0x00
	 *
	 * the character must be encoded as the backslash '\' character (ASCII
	 * 0x5c) followed by the two hexadecimal digits representing the ASCII
	 * value of the encoded character. The case of the two hexadecimal
	 * digits is not significant.
	 *
	 * @access private
	 * @param string $string string
	 * @return string escaped string
	 */

	static function Escape($string)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$string = str_replace('\\', '\\5c', $string);
		$string = str_replace('*',  '\\2a', $string);
		$string = str_replace('(',  '\\28', $string);
		$string = str_replace(')',  '\\29', $string);
		$string = str_replace('\0', '\\00', $string);

		return $string;
	}

	///////////////////////////////////////////////////////////////////////////////
	// P R I V A T E  M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Clears search results.
	 *
	 * @access private
	 * @return void
	 */

	protected function _FreeSearchResult()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if ($this->search_result) {
			ldap_free_result($this->search_result);
			$this->search_result = false;
		}
	}

	/**
	 * Loads configuration file.
	 *
	 * @access private
	 * @return void
	 * @throws EngineException
	 */

	protected function _LoadConfig()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$configfile = new ConfigurationFile(self::FILE_KOLAB_CONFIG, 'split', ':', 2);

		try {
			$this->config = $configfile->Load();
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(),COMMON_ERROR);
		}

		$this->base_dn = $this->config['base_dn'];

		$this->is_loaded = true;
	}

	/**
	 * @access private
	 */

	function __destruct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if ($this->bound)
			$this->Close();

		parent::__destruct();
	}
}

// vim: syntax=php ts=4
?>
