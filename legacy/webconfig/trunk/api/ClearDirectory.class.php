<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2006-2009 Point Clark Networks.
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
 * ClearOS LDAP Directory class.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006-2009, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('ConfigurationFile.class.php');
require_once('Daemon.class.php');
require_once('File.class.php');
require_once('Folder.class.php');
require_once('Hostname.class.php');
require_once('Ldap.class.php');
require_once('Network.class.php');
require_once('NtpTime.class.php');
require_once('ShellExec.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * ClearOS LDAP Directory class.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006-2009, Point Clark Networks
 */

class ClearDirectory extends Engine
{
	///////////////////////////////////////////////////////////////////////////////
	// M E M B E R S
	///////////////////////////////////////////////////////////////////////////////

	const CONSTANT_BASE_DB_NUM = 3;
	const LOG_TAG = "directory";

	// Commands
	const COMMAND_OPENSSL = "/usr/bin/openssl";
	const COMMAND_SLAPADD = "/usr/sbin/slapadd";
	const COMMAND_LDAPSETUP = "/usr/sbin/ldapsetup";
	const COMMAND_AUTHCONFIG = "/usr/sbin/authconfig";

	// Files and paths
	const PATH_KOLAB = "/etc/kolab";
	const PATH_LDAP = "/var/lib/ldap";
	const FILE_CONFIG = "/etc/cleardirectory/config";
	const FILE_ACCESSLOG_DATA = "/etc/openldap/provision/provision.accesslog.ldif";
	const FILE_LDIF_NEW_DOMAIN = "/etc/openldap/provision/newdomain.ldif";
	const FILE_LDIF_OLD_DOMAIN = "/etc/openldap/provision/olddomain.ldif";
	const FILE_DBCONFIG = "/var/lib/ldap/DB_CONFIG";
	const FILE_DBCONFIG_PROVISION = "/etc/openldap/provision/DB_CONFIG.template";
	const FILE_DBCONFIG_ACCESSLOG = "/var/lib/ldap/accesslog/DB_CONFIG";
	const FILE_DBCONFIG_ACCESSLOG_PROVISION = "/etc/openldap/provision/DB_CONFIG.accesslog.template";
	const FILE_LDAP_CONFIG = "/etc/openldap/ldap.conf";
	const FILE_LDAP_CONFIG_PROVISION = "/etc/openldap/provision/ldap.conf.template";
	const FILE_SLAPD = "/etc/openldap/slapd.conf";
	const FILE_SLAPD_PROVISION = "/etc/openldap/provision/slapd.conf.template";
	const FILE_SLAPD_REPLICATE_PROVISION = "/etc/openldap/provision/slapd-replicate.conf.template";
	const FILE_DATA = "/etc/openldap/provision/provision.ldif";
	const FILE_DATA_PROVISION = "/etc/openldap/provision/provision.ldif.template";
	const FILE_KOLAB_CONFIG = "/etc/kolab/kolab.conf";
	const FILE_KOLAB_SETUP = "/etc/kolab/.kolab2_configured";
	const FILE_LDAP_SETUP = "/etc/system/initialized/directory";
	const FILE_LDAP_EXISTS = "/var/lib/ldap/cn.bdb";
	const FILE_SSL_KEY = "/etc/openldap/cacerts/key.pem";
	const FILE_SSL_CERT = "/etc/openldap/cacerts/cert.pem";

	// Containers
	const SUFFIX_COMPUTERS = 'ou=Computers,ou=Accounts';
	const SUFFIX_GROUPS = 'ou=Groups,ou=Accounts';
	const SUFFIX_SERVERS = 'ou=Servers';
	const SUFFIX_USERS = 'ou=Users,ou=Accounts';
	const SUFFIX_PASSWORD_POLICIES = 'ou=PasswordPolicies,ou=Accounts';
	const OU_PASSWORD_POLICIES = 'PasswordPolicies';
	const CN_MASTER = 'cn=Master';

	// Status codes for username/group/alias uniqueness
	const STATUS_ALIAS_EXISTS = 'alias';
	const STATUS_GROUP_EXISTS = 'group';
	const STATUS_USERNAME_EXISTS = 'user';
	const STATUS_UNIQUE = 'unique';

	// ClearDiretory modes
	const MODE_MASTER = 'master';
	const MODE_REPLICATE = 'replicate';
	const MODE_STANDALONE = 'standalone';

	// Services available in ClearDirectory
	const SERVICE_TYPE_FTP = 'ftp';
	const SERVICE_TYPE_EMAIL = 'email';
	const SERVICE_TYPE_GOOGLE_APPS = 'googleapps';
	const SERVICE_TYPE_OPENVPN = 'openvpn';
	const SERVICE_TYPE_PPTP = 'pptp';
	const SERVICE_TYPE_PROXY = 'proxy';
	const SERVICE_TYPE_SAMBA = 'samba';
	const SERVICE_TYPE_WEBCONFIG = 'webonfig';
	const SERVICE_TYPE_WEB = 'web';
	const SERVICE_TYPE_PBX = 'pbx';

	protected $ldaph = null;

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * ClearDirectory constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		require_once(GlobalGetLanguageTemplate(__FILE__));

		parent::__construct();

		$this->statuscodes = array(
			ClearDirectory::STATUS_ALIAS_EXISTS => CLEARDIRECTORY_LANG_ALIAS_ALREADY_EXISTS,
			ClearDirectory::STATUS_GROUP_EXISTS => CLEARDIRECTORY_LANG_GROUP_ALREADY_EXISTS,
			ClearDirectory::STATUS_USERNAME_EXISTS => CLEARDIRECTORY_LANG_USERNAME_ALREADY_EXISTS
		);

		$this->modes = array(
			ClearDirectory::MODE_MASTER => CLEARDIRECTORY_LANG_MASTER,
			ClearDirectory::MODE_REPLICATE => CLEARDIRECTORY_LANG_REPLICATE,
			ClearDirectory::MODE_STANDALONE => CLEARDIRECTORY_LANG_STANDALONE
		);
	}

	/**
	 * Returns list of installed services.
	 *
	 * @return array list of installed services
	 * @throws EngineException
	 */

	function GetInstalledServices()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$services = array();

		// TODO: detect these in a more consistent way
		if (file_exists("/etc/system/initialized/sambalocal"))
			$services[] = ClearDirectory::SERVICE_TYPE_SAMBA;

		if (file_exists(COMMON_CORE_DIR . "/api/Cyrus.class.php"))
			$services[] = ClearDirectory::SERVICE_TYPE_EMAIL;

		if (file_exists(COMMON_CORE_DIR . "/api/GoogleApps.class.php"))
			$services[] = ClearDirectory::SERVICE_TYPE_GOOGLE_APPS;

		if (file_exists(COMMON_CORE_DIR . "/api/Pptpd.class.php"))
			$services[] = ClearDirectory::SERVICE_TYPE_PPTP;

		if (file_exists(COMMON_CORE_DIR . "/api/OpenVpn.class.php"))
			$services[] = ClearDirectory::SERVICE_TYPE_OPENVPN;

		if (file_exists(COMMON_CORE_DIR . "/api/Squid.class.php"))
			$services[] = ClearDirectory::SERVICE_TYPE_PROXY;

		if (file_exists(COMMON_CORE_DIR . "/api/Proftpd.class.php"))
			$services[] = ClearDirectory::SERVICE_TYPE_FTP;

		if (file_exists(COMMON_CORE_DIR . "/api/Httpd.class.php"))
			$services[] = ClearDirectory::SERVICE_TYPE_WEB;

		if (file_exists(COMMON_CORE_DIR . "/iplex/Users.class.php"))
			$services[] = ClearDirectory::SERVICE_TYPE_PBX;

		return $services;
	}

	/**
	 * Returns the mode of ClearDirectory.
	 *
	 * The return values are:
	 * - ClearDirectory::MODE_STANDALONE
	 * - ClearDirectory::MODE_MASTER
	 * - ClearDirectory::MODE_REPLICATE
	 *
	 * @return int mode of the directory
	 * @throws EngineException
	 */

	function GetMode()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$configfile = new ConfigurationFile(self::FILE_CONFIG);
			$configdata = $configfile->Load();
		} catch (FileNotFoundException $e) {
			// Not fatal
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
		
		if (isset($configdata['mode'])) {
			if ($configdata['mode'] === ClearDirectory::MODE_MASTER)
				$mode = ClearDirectory::MODE_MASTER;
			else if ($configdata['mode'] === ClearDirectory::MODE_REPLICATE)
				$mode = ClearDirectory::MODE_REPLICATE;
			else if ($configdata['mode'] === ClearDirectory::MODE_STANDALONE)
				$mode = ClearDirectory::MODE_STANDALONE;
			else 
				$mode = ClearDirectory::MODE_STANDALONE;
		} else {
			$mode = ClearDirectory::MODE_STANDALONE;
		}

		return $mode;
	}

	/**
	 * Returns list of services managed in ClearDirectory.
	 *
	 * @return array list of services
	 * @throws EngineException
	 */

	function GetServices()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		// Standalone and replicates only need to show installed services
		//---------------------------------------------------------------

		$mode = $this->GetMode();

		if (($mode === ClearDirectory::MODE_STANDALONE) || ($mode === ClearDirectory::MODE_REPLICATE))
			return $this->GetInstalledServices();

		// Master nodes should show all services
		//--------------------------------------

		$services = array();

		// TODO: Allow user to fine tune which services should appear
		// TODO: For now, Samba has to be initialized first...

		if (file_exists("/etc/system/initialized/sambalocal"))
			$services[] = ClearDirectory::SERVICE_TYPE_SAMBA;

		$services[] = ClearDirectory::SERVICE_TYPE_EMAIL;
		$services[] = ClearDirectory::SERVICE_TYPE_GOOGLE_APPS;
		$services[] = ClearDirectory::SERVICE_TYPE_PPTP;
		$services[] = ClearDirectory::SERVICE_TYPE_OPENVPN;
		$services[] = ClearDirectory::SERVICE_TYPE_PROXY;
		$services[] = ClearDirectory::SERVICE_TYPE_FTP;
		$services[] = ClearDirectory::SERVICE_TYPE_WEB;

		return $services;
	}

	/**
	 * Exports LDAP database to LDIF.
	 *
	 * @param string $ldif LDIF backup file
	 * @param int $dbnum database number
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	function Export($ldif = Ldap::FILE_LDIF_BACKUP, $dbnum = self::CONSTANT_BASE_DB_NUM)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$export = new File($ldif, true);

			if ($export->Exists())
				$export->Delete();

			$ldap = new Ldap();
			$wasrunning = $ldap->GetRunningState();

			if ($wasrunning)
				$ldap->SetRunningState(false);

			$shell = new ShellExec();
			$shell->Execute(Ldap::COMMAND_SLAPCAT, "-n$dbnum -l " . $ldif, true);

			if ($wasrunning)
				$ldap->SetRunningState(true);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Imports backup LDAP database from LDIF.
	 *
	 * @param boolean $background runs import in background if true
	 * @return boolean true if import file exists
	 */

	function Import($background)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$import = new File(Ldap::FILE_LDIF_BACKUP, true);

			if (! $import->Exists())
				return false;
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		$this->_ImportLdif(Ldap::FILE_LDIF_BACKUP, $background);

		return true;
	}

	/**
	 * Generates a random password for LDAP object.
	 *
	 * @return string random password
	 */

	function GeneratePassword()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$shell = new ShellExec();
			$shell->Execute(self::COMMAND_OPENSSL, "rand -base64 12", true);
			$password = $shell->GetFirstOutputLine();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		return $password;
	}

	/**
	 * Initializes the master LDAP database.
	 *
	 * @param string $mode LDAP server mode
	 * @param string $domain domain name
	 * @param string $password bind DN password
	 * @param boolean $background runs import in background if true
	 * @param boolean $start starts LDAP after initialization
	 * @param boolean $force forces initialization even if LDAP server already has data
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	function Initialize($mode, $domain, $password = null, $background = false, $start = true, $force = false, $master_hostname = null)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		// TODO: validate
		// TODO: mode
		// TODO: fix the function call -- too many parameters

		// Bail if LDAP is already initialized (and not a re-initialize)
		//--------------------------------------------------------------

		try {
			if (! $force) {
				$file = new File(self::FILE_LDAP_SETUP);
				if ($file->Exists())
					return;
			}
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		// Determine our hostname and generate an LDAP password (if required)
		//--------------------------------------------------------------

		try {
			$hostnameinfo = new Hostname();
			$hostname = $hostnameinfo->Get();

			if (empty($password))
				$password = $this->GeneratePassword();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		// Run our initialization subroutines
		//-----------------------------------

		if ($mode == ClearDirectory::MODE_REPLICATE) {
			$this->_InitializeReplicateConfiguration($domain, $password, $hostname, $master_hostname);
		} else {
			$this->_InitializeConfiguration($domain, $password, $hostname);
			$this->_ImportLdif(ClearDirectory::FILE_DATA, $background);
		}

		$this->_SetMode($mode);
		$this->_InitializeAuthconfig();
		$this->_RemoveOverlaps();

		// The critical part is done, set flag to indicate LDAP initialization
		//--------------------------------------------------------------------

		$file = new File(self::FILE_LDAP_SETUP);

		if (! $file->Exists())
			$file->Create("root", "root", "0644");

		// Startup LDAP and set onboot flag
		//---------------------------------

		try {
			$ldap = new Ldap();
			$ldap->SetBootState(true);

			$ldapsync = new Daemon("ldapsync");
			$ldapsync->SetBootState(true);

			if ($start) {
				$ldap->Restart();
				$ldapsync->Restart();
			}
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		// Tell LDAP-related apps to synchronize with the latest LDAP
		//-----------------------------------------------------------

		$this->_Synchronize($background);
	}

	/**
	 * Returns the availability of LDAP.
	 *
	 * @return boolean true if LDAP is running
	 */

	function IsAvailable()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if ($this->ldaph == null)
			$this->_GetLdapHandle();

		// Bind to LDAP to see if it is available

		$available = true;

		try {
			$this->ldaph->Bind();
			$this->ldaph->Close();
		} catch (LdapUnavailableException $e) {
			$available = false;
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		return $available;
	}

	/**
	 * Returns state of LDAP setup.
	 *
	 * @return boolean true if LDAP has been initialized
	 */

	function IsInitialized()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$file = new File(self::FILE_LDAP_SETUP);
			if ($file->Exists())
				return true;
			else
				return false;
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Initialized LDAP system.
	 *
	 *  @param string $mode LDAP server mode
	 * @param string $domain domain name
	 * @return void
	 * @throws EngineException
	 */

	function RunInitialize($mode, $domain)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if ($this->IsInitialized())
			return;

		try {
			$options['stdin'] = true;
			$options['background'] = true;

			$password = $this->GeneratePassword();

			$shell = new ShellExec();
			$shell->Execute(ClearDirectory::COMMAND_LDAPSETUP, "-r $mode -d $domain -p $password", true, $options);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Changes base domain used in directory
	 *
	 * @param string $domain domain
	 * @param boolean $background run in background
	 * @return void
	 */

	function SetDomain($domain, $background = false)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$network = new Network();

		if (! $network->IsValidDomain($domain))
			throw new ValidationException(NETWORK_LANG_DOMAIN . " - " . LOCALE_LANG_INVALID, COMMON_WARNING);

		if ($background) {
			try {
				$options['background'] = true;
				$shell = new ShellExec();
				$shell->Execute(Engine::COMMAND_API, 'ClearDirectory SetDomain ' . $domain, true, $options);
			} catch (Exception $e) {
				throw new EngineException($e->GetMessage(), COMMON_WARNING);
			}

			return;
		}

		$ldap = new Ldap();

		$wasrunning = false;

		try {
			// Grab hostname
			//--------------

			$hostnameobj = new Hostname();
			$hostname = $hostnameobj->Get();

			// Dump LDAP database to export file
			//----------------------------------

			$wasrunning = $ldap->GetRunningState();
			$this->Export(self::FILE_LDIF_OLD_DOMAIN, self::CONSTANT_BASE_DB_NUM);

			// Load LDAP export file
			//----------------------

			$export = new File(self::FILE_LDIF_OLD_DOMAIN, true);
			$exportlines = $export->GetContentsAsArray();

			// Load Kolab configuration file
			//------------------------------

			$kolabconfig = new File(Ldap::FILE_KOLAB_CONFIG);
			$kolablines = $kolabconfig->GetContentsAsArray();

			// Load LDAP configuration
			//------------------------

			$ldapconfig = new File(Ldap::FILE_SLAPD_CONFIG);
			$ldaplines = $ldapconfig->GetContentsAsArray();

			// Load LDAP information
			//----------------------

			$basedn = $ldap->GetBaseDn();

		} catch (Exception $e) {
			if ($wasrunning)
				$ldap->SetRunningState(true);

			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
		
		// Remove word wrap from LDIF data
		//--------------------------------

		$cleanlines = array();

		foreach ($exportlines as $line) {
			if (preg_match("/^\s+/", $line)) {
				$previous = array_pop($cleanlines);
				$cleanlines[] = $previous . preg_replace("/^ /", "", $line);
			} else {
				$cleanlines[] = $line;
			}
		}

		// Rewrite LDAP export file
		//-------------------------

		$newbasedn = "dc=" . preg_replace("/\./", ",dc=", $domain);
		$matches = array();

		preg_match("/^dc=([^,]*)/", $basedn, $matches);
		$olddc = $matches[1];

		preg_match("/^dc=([^,]*)/", $newbasedn, $matches);
		$newdc = $matches[1];

		$ldiflines = array();

		foreach ($cleanlines as $line) {
			if (preg_match("/$basedn/", $line))
				$ldiflines[] = preg_replace("/$basedn/", $newbasedn, $line);
			else if (preg_match("/^kolabHomeServer: /", $line))
				$ldiflines[] = "kolabHomeServer: $hostname";
			else if (preg_match("/^mail: /", $line))
				$ldiflines[] = preg_replace("/@.*/", "@$domain", $line);
			else if (preg_match("/^dc: $olddc/", $line))
				$ldiflines[] = "dc: $newdc";
			else if (preg_match("/^uid: calendar@/", $line))
				$ldiflines[] = "uid: calendar@$domain";
			else if (preg_match("/^kolabHost: /", $line))
				$ldiflines[] = "kolabHost: $hostname";
			else if (preg_match("/^postfix-mydomain: /", $line))
				$ldiflines[] = "postfix-mydomain: $domain";
			else if (preg_match("/^postfix-mydestination: /", $line))
				$ldiflines[] = "postfix-mydestination: $domain";
			else
				$ldiflines[] = $line;
		}

		// Rewrite Kolab configuration file
		//---------------------------------

		$newkolablines = array();

		foreach ($kolablines as $line) {
			if (preg_match("/^fqdnhostname/", $line))
				$newkolablines[] = "fqdnhostname : $hostname";
			else
				$newkolablines[] = preg_replace("/$basedn/", $newbasedn, $line);
		}

		// Rewrite LDAP configuration file
		//--------------------------------

		$newldaplines = array();

		foreach ($ldaplines as $line)
			$newldaplines[] = preg_replace("/$basedn/", $newbasedn, $line);

		// Implement file changes
		//-----------------------

		try {
			// LDAP export file
			//-----------------

			$newexport = new File(self::FILE_LDIF_NEW_DOMAIN);

			if ($newexport->Exists())
				$newexport->Delete();

			$newexport->Create("root", "root", "0600");
			$newexport->DumpContentsFromArray($ldiflines);

			// LDAP configuration
			//--------------------

			$newldap = new File(Ldap::FILE_SLAPD_CONFIG, true);

			if ($newldap->Exists())
				$newldap->Delete();

			$newldap->Create("root", "ldap", "0640");
			$newldap->DumpContentsFromArray($newldaplines);

			// Kolab configuration
			//--------------------

			$newconfig = new File(Ldap::FILE_KOLAB_CONFIG, true);

			if ($newconfig->Exists())
				$newconfig->Delete();

			$newconfig->Create("root", "root", "0600");
			$newconfig->DumpContentsFromArray($newkolablines);

			// Import
			//-------

			$this->_ImportLdif(self::FILE_LDIF_NEW_DOMAIN);

			// Perform Authconfig initialization in case LDAP has been manually initialized
			//-----------------------------------------------------------------------------

			$this->_InitializeAuthconfig();

		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		try {
			// TODO: Should not need to explicitly call _CleanSecretsFile
			if (file_exists(COMMON_CORE_DIR . "/api/Samba.class.php")) {
				require_once('Samba.class.php');
				$samba = new Samba();
				$samba->_CleanSecretsFile("");
			}

			// Tell other LDAP dependent apps to grab latest configuration
			// TODO: move this to a daemon
			if ($wasrunning)
				$this->_Synchronize(false);

		} catch (Exception $e) {
			// Not fatal
		}
	}

	///////////////////////////////////////////////////////////////////////////////
	// V A L I D A T I O N
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Check for overlapping usernames, groups and aliases in the directory.
	 *
	 * @param string $id username, group or alias
	 * @access private
	 * @return integer ClearDirectory::STATUS_UNIQUE if unique
	 */

	function IsUniqueId($id)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if ($this->ldaph == null)
			$this->_GetLdapHandle();

		// Check for duplicate user
		//-------------------------

		try {
			$result = $this->ldaph->Search(
				"(&(objectclass=inetOrgPerson)(uid=$id))",
				ClearDirectory::GetUsersOu(),
				array('dn')
			);

			$entry = $this->ldaph->GetFirstEntry($result);

			if ($entry)
				return ClearDirectory::STATUS_USERNAME_EXISTS;
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		// Check for duplicate alias
		//--------------------------

		try {
			$result = $this->ldaph->Search(
				"(&(objectclass=inetOrgPerson)(pcnMailAliases=$id))",
				ClearDirectory::GetUsersOu(),
				array('dn')
			);

			$entry = $this->ldaph->GetFirstEntry($result);

			if ($entry)
				return ClearDirectory::STATUS_ALIAS_EXISTS;
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		// Check for duplicate group
		//--------------------------
	
		// The "displayName" is used in Samba group mapping.  In other words,
		// the "displayName" is what is used by Windows networking (not the cn).

		try {
			$result = $this->ldaph->Search(
				"(&(objectclass=posixGroup)(|(cn=$id)(displayName=$id)))",
				ClearDirectory::GetGroupsOu(),
				array('dn')
			);

			$entry = $this->ldaph->GetFirstEntry($result);

			if ($entry)
				return ClearDirectory::STATUS_GROUP_EXISTS;
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		return ClearDirectory::STATUS_UNIQUE;
	}

	/**
	 * Validates LDAP mode.
	 *
	 * @param string $mode LDAP mode
	 * @return boolean true if mode is valid
	 */

	function IsValidMode($mode)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (isset($mode) && array_key_exists($mode, $this->modes))
			return true;
		else
			return false;
	}

	/**
	 * Validates LDAP password.
	 *
	 * @param string $password LDAP password
	 * @return boolean true if password is valid
	 */

	function IsValidPassword($password)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (!empty($password) && (!preg_match("/[\|;]/", $password)))
			return true;
		else
			return false;
	}

	///////////////////////////////////////////////////////////////////////////////
	// S T A T I C  M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	// TODO: these methods should be changed to a fast caching method
	// TODO: remove duplicate code (need them to be static)

	/** 
	 * Quick static method for loading the base DN.
	 *
	 * @throws EngineException
	 */

	static function GetBaseDn()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$configfile = new ConfigurationFile(ClearDirectory::FILE_KOLAB_CONFIG, 'split', ':', 2);
			$config = $configfile->Load();
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}

		return $config['base_dn'];
	}

	/** 
	 * Returns the master server DN (deprecated)
	 *
	 * @return string DN for master server
	 * @throws EngineException
	 */

	static function GetMasterDn()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		return ClearDirectory::CN_MASTER . ',' . ClearDirectory::SUFFIX_SERVERS . ',' . ClearDirectory::GetBaseDn();
	}

	/** 
	 * Returns the OU for computers.
	 *
	 * @return string OU for computers.
	 * @throws EngineException
	 */

	static function GetComputersOu()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		return ClearDirectory::SUFFIX_COMPUTERS . ',' . ClearDirectory::GetBaseDn();
	}

	/** 
	 * Returns the OU for groups.
	 *
	 * @return string OU for groups
	 * @throws EngineException
	 */

	static function GetGroupsOu()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		return ClearDirectory::SUFFIX_GROUPS . ',' . ClearDirectory::GetBaseDn();
	}

	/** 
	 * Returns the OU for password policies.
	 *
	 * @return string OU for password policies
	 * @throws EngineException
	 */

	static function GetPasswordPoliciesOu()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		return ClearDirectory::SUFFIX_PASSWORD_POLICIES . ',' . ClearDirectory::GetBaseDn();
	}

	/** 
	 * Returns the OU for servers.
	 *
	 * @return string OU for servers.
	 * @throws EngineException
	 */

	static function GetServersOu()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		return ClearDirectory::SUFFIX_SERVERS . ',' . ClearDirectory::GetBaseDn();
	}

	/** 
	 * Returns the OU for users.
	 *
	 * @return string OU for users.
	 * @throws EngineException
	 */

	static function GetUsersOu()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		return ClearDirectory::SUFFIX_USERS . ',' . ClearDirectory::GetBaseDn();
	}

	///////////////////////////////////////////////////////////////////////////////
	// P R I V A T E  M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Creates an LDAP handle.
	 *
	 * @access private
	 * @return void
	 * @throws EngineException
	 */

	protected function _GetLdapHandle()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$this->ldaph = new Ldap();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Initializes authconfig.
	 *
	 * This method will update the nsswitch.conf and pam configuration.
	 */

	protected function _InitializeAuthconfig()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$shell = new ShellExec();
			$shell->Execute(ClearDirectory::COMMAND_AUTHCONFIG, '--enableshadow --enablemd5 --enableldap --enableldapauth --update', true);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Initializes LDAP configuration.
	 */

	protected function _InitializeConfiguration($domain, $password, $hostname)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		// TODO: validate

		$base_dn = preg_replace("/\./", ",dc=", $domain);
		$base_dn = "dc=$base_dn";

		$base_dn_rdn = preg_replace("/,.*/", "", $base_dn);
		$base_dn_rdn = preg_replace("/dc=/", "", $base_dn_rdn);

		$bind_pw = $password;

		// Load up the required kolab.conf values
		//---------------------------------------

		try {
			$shell = new ShellExec();

			$shell->Execute(self::COMMAND_OPENSSL, "rand -base64 30");
			$php_pw = $shell->GetFirstOutputLine();
			$shell->Execute(Ldap::COMMAND_SLAPPASSWD, "-s $php_pw");
			$php_pw_hash = $shell->GetFirstOutputLine();

			$shell->Execute(self::COMMAND_OPENSSL, "rand -base64 30");
			$calendar_pw = $shell->GetFirstOutputLine();
			$shell->Execute(Ldap::COMMAND_SLAPPASSWD, "-s $calendar_pw");
			$calendar_pw_hash = $shell->GetFirstOutputLine();

			$shell->Execute(Ldap::COMMAND_SLAPPASSWD, "-s $bind_pw");
			$bind_pw_hash = $shell->GetFirstOutputLine();

			$bind_dn = "cn=manager,cn=internal,$base_dn";
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		$config = "fqdnhostname : $hostname\n";
		$config .= "is_master : true\n";
		$config .= "base_dn : $base_dn\n";
		$config .= "bind_dn : $bind_dn\n";
		$config .= "bind_pw : $bind_pw\n";
		$config .= "bind_pw_hash : $bind_pw_hash\n";
		$config .= "ldap_uri : ldap://127.0.0.1:389\n";
		$config .= "ldap_master_uri : ldap://127.0.0.1:389\n";
		$config .= "php_dn : cn=nobody,cn=internal,$base_dn\n";
		$config .= "php_pw : $php_pw\n";
		$config .= "calendar_dn : cn=calendar,cn=internal,$base_dn\n";
		$config .= "calendar_pw : $calendar_pw\n";

		// Kolab configuration file
		//--------------------------

		try {
			$folder = new Folder(self::PATH_KOLAB);

			if (! $folder->Exists())
				$folder->Create("root", "root", "0755");

			$file = new File(self::FILE_KOLAB_CONFIG);

			if ($file->Exists())
				$file->Delete();

			$file->Create("root", "root", "0600");
			$file->AddLines($config);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		// slapd.conf configuration
		//-----------------------------

		try {
			$file = new File(self::FILE_SLAPD_PROVISION);
			$contents = $file->GetContents();
			$contents = preg_replace("/\@\@\@base_dn\@\@\@/", $base_dn, $contents);
			$contents = preg_replace("/\@\@\@bind_dn\@\@\@/", $bind_dn, $contents);
			$contents = preg_replace("/\@\@\@bind_pw_hash\@\@\@/", $bind_pw_hash, $contents);
			$contents = preg_replace("/\@\@\@domain\@\@\@/", $domain, $contents);

			$newfile = new File(self::FILE_SLAPD);

			if ($newfile->Exists())
				$newfile->Delete();

			$newfile->Create("root", "ldap", "0640");
			$newfile->AddLines("$contents\n");

		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		// ldap.conf configuration
		//-----------------------------

		try {
			$file = new File(self::FILE_LDAP_CONFIG_PROVISION);
			$contents = $file->GetContents();
			$contents = preg_replace("/\@\@\@base_dn\@\@\@/", $base_dn, $contents);
			$contents = preg_replace("/\@\@\@bind_dn\@\@\@/", $bind_dn, $contents);

			$newfile = new File(self::FILE_LDAP_CONFIG);

			if ($newfile->Exists())
				$newfile->Delete();

			$newfile->Create("root", "root", "0644");
			$newfile->AddLines("$contents\n");

		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		// DB_CONFIG configuration
		//-----------------------------

		try {
			$file = new File(self::FILE_DBCONFIG_PROVISION);
			$contents = $file->GetContents();

			$newfile = new File(self::FILE_DBCONFIG, true);

			if ($newfile->Exists())
				$newfile->Delete();

			$newfile->Create("ldap", "ldap", "0644");
			$newfile->AddLines("$contents\n");

		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		// DB_CONFIG configuration for accesslog
		//--------------------------------------

		try {
			$file = new File(self::FILE_DBCONFIG_ACCESSLOG_PROVISION);
			$contents = $file->GetContents();

			$newfile = new File(self::FILE_DBCONFIG_ACCESSLOG, true);

			if ($newfile->Exists())
				$newfile->Delete();

			$newfile->Create("ldap", "ldap", "0644");
			$newfile->AddLines("$contents\n");

		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		// LDAP provision data file
		//-----------------------------

		try {
			$file = new File(self::FILE_DATA_PROVISION);
			$contents = $file->GetContents();
			$contents = preg_replace("/\@\@\@base_dn\@\@\@/", $base_dn, $contents);
			$contents = preg_replace("/\@\@\@base_dn_rdn\@\@\@/", $base_dn_rdn, $contents);
			$contents = preg_replace("/\@\@\@bind_pw_hash\@\@\@/", $bind_pw_hash, $contents);
			$contents = preg_replace("/\@\@\@php_pw_hash\@\@\@/", $php_pw_hash, $contents);
			$contents = preg_replace("/\@\@\@calendar_pw_hash\@\@\@/", $calendar_pw_hash, $contents);
			$contents = preg_replace("/\@\@\@fqdnhostname\@\@\@/", $hostname, $contents);
			$contents = preg_replace("/\@\@\@domain\@\@\@/", $domain, $contents);

			$newfile = new File(self::FILE_DATA);

			if ($newfile->Exists())
				$newfile->Delete();

			$newfile->Create("root", "ldap", "0640");
			$newfile->AddLines("$contents\n");

		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Initializes LDAP replicate configuration.
	 *
	 * @param password $password LDAP master password
	 * @param hostname $hostname hostname and IP of LDAP master
	 * @param string $domain domain name
	 * @throws EngineException, ValidationException
	 */

	protected function _InitializeReplicateConfiguration($domain, $password, $hostname, $master_hostname)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		// TODO: validate
		// TODO: merge with _InitializeConfiguration

		$base_dn = preg_replace("/\./", ",dc=", $domain);
		$base_dn = "dc=$base_dn";

		$base_dn_rdn = preg_replace("/,.*/", "", $base_dn);
		$base_dn_rdn = preg_replace("/dc=/", "", $base_dn_rdn);

		$bind_pw = $password;

		// Load up the required kolab.conf values
		//---------------------------------------

		try {
			$shell = new ShellExec();

			$shell->Execute(self::COMMAND_OPENSSL, "rand -base64 30");
			$php_pw = $shell->GetFirstOutputLine();
			$shell->Execute(Ldap::COMMAND_SLAPPASSWD, "-s $php_pw");
			$php_pw_hash = $shell->GetFirstOutputLine();

			$shell->Execute(self::COMMAND_OPENSSL, "rand -base64 30");
			$calendar_pw = $shell->GetFirstOutputLine();
			$shell->Execute(Ldap::COMMAND_SLAPPASSWD, "-s $calendar_pw");
			$calendar_pw_hash = $shell->GetFirstOutputLine();

			$shell->Execute(Ldap::COMMAND_SLAPPASSWD, "-s $bind_pw");
			$bind_pw_hash = $shell->GetFirstOutputLine();

			$bind_dn = "cn=manager,cn=internal,$base_dn";
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		$config = "fqdnhostname : $hostname\n";
		$config .= "is_master : false\n";
		$config .= "base_dn : $base_dn\n";
		$config .= "bind_dn : $bind_dn\n";
		$config .= "bind_pw : $bind_pw\n";
		$config .= "bind_pw_hash : $bind_pw_hash\n";
		$config .= "ldap_uri : ldap://127.0.0.1:389\n";
		$config .= "ldap_master_uri : ldap://127.0.0.1:389\n";
		$config .= "php_dn : cn=nobody,cn=internal,$base_dn\n";
		$config .= "php_pw : $php_pw\n";
		$config .= "calendar_dn : cn=calendar,cn=internal,$base_dn\n";
		$config .= "calendar_pw : $calendar_pw\n";

		// Kolab configuration file
		//--------------------------

		try {
			$folder = new Folder(self::PATH_KOLAB);

			if (! $folder->Exists())
				$folder->Create("root", "root", "0755");

			$file = new File(self::FILE_KOLAB_CONFIG);

			if ($file->Exists())
				$file->Delete();

			$file->Create("root", "root", "0600");
			$file->AddLines($config);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		// slapd.conf configuration
		//-----------------------------

		try {
			$file = new File(self::FILE_SLAPD_REPLICATE_PROVISION);
			$contents = $file->GetContents();
			$contents = preg_replace("/\@\@\@base_dn\@\@\@/", $base_dn, $contents);
			$contents = preg_replace("/\@\@\@bind_dn\@\@\@/", $bind_dn, $contents);
			$contents = preg_replace("/\@\@\@bind_pw\@\@\@/", $bind_pw, $contents);
			$contents = preg_replace("/\@\@\@bind_pw_hash\@\@\@/", $bind_pw_hash, $contents);
			$contents = preg_replace("/\@\@\@domain\@\@\@/", $domain, $contents);
			$contents = preg_replace("/\@\@\@master_hostname\@\@\@/", $master_hostname, $contents);

			$newfile = new File(self::FILE_SLAPD);

			if ($newfile->Exists())
				$newfile->Delete();

			$newfile->Create("root", "ldap", "0640");
			$newfile->AddLines("$contents\n");

		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		// ldap.conf configuration
		//-----------------------------

		try {
			$file = new File(self::FILE_LDAP_CONFIG_PROVISION);
			$contents = $file->GetContents();
			$contents = preg_replace("/\@\@\@base_dn\@\@\@/", $base_dn, $contents);
			$contents = preg_replace("/\@\@\@bind_dn\@\@\@/", $bind_dn, $contents);

			$newfile = new File(self::FILE_LDAP_CONFIG);

			if ($newfile->Exists())
				$newfile->Delete();

			$newfile->Create("root", "root", "0644");
			$newfile->AddLines("$contents\n");

		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Imports an LDIF file.
	 *
	 * @param string $ldif LDIF file
	 * @param boolean $background runs import in background if true
	 * @throws EngineException, ValidationException
	 */

	protected function _ImportLdif($ldif)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$ldap = new Ldap();

			Logger::Syslog(self::LOG_TAG, "preparing LDAP import");

			// Shutdown LDAP if running
			//-------------------------

			$wasrunning = $ldap->GetRunningState();

			if ($wasrunning) {
				Logger::Syslog(self::LOG_TAG, "shutting down LDAP server");
				$ldap->SetRunningState(false);
			}

			// Backup old LDAP
			//----------------

			$ntptime = new NtpTime();
			date_default_timezone_set($ntptime->GetTimeZone());

			$filename = Ldap::PATH_LDAP_BACKUP . '/' . "backup-" . strftime("%m-%d-%Y-%H-%M-%S", time()) . ".ldif";
			$this->Export($filename);

			// Clear out old database
			//-----------------------

			$folder = new Folder(Ldap::PATH_LDAP);

			$filelist = $folder->GetRecursiveListing();
			foreach ($filelist as $filename) {
				if (!preg_match("/DB_CONFIG$/", $filename)) {
					$file = new File(Ldap::PATH_LDAP . "/" . $filename, true);
					$file->Delete();
				}
			}

			// Import new database
			//--------------------

			Logger::Syslog(self::LOG_TAG, "loading data into LDAP server");
			$shell = new ShellExec();
			$shell->Execute(Ldap::COMMAND_SLAPADD, "-n2 -l " . self::FILE_ACCESSLOG_DATA, true);
			$shell->Execute(Ldap::COMMAND_SLAPADD, "-n3 -l " . $ldif, true);

			// Set flag to indicate Kolab has been initialized
			//-----------------------------------------------

			$file = new File(self::FILE_KOLAB_SETUP);

			if (! $file->Exists())
				$file->Create("root", "root", "0644");

			// Fix file permissions
			//---------------------

			$folder->Chown("ldap", "ldap", true);

			if ($wasrunning) {
				Logger::Syslog(self::LOG_TAG, "restarting LDAP server");
				$ldap->SetRunningState(true);
			}

		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Removes overlapping groups and users found in /etc/passwd and /etc/group.
	 *
	 * @return void
	 */

	protected function _RemoveOverlaps()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			// TODO: implement this in a generic way.
			$file = new File("/etc/group");
			$file->ReplaceLines("/^users:/", "");
			$file->ReplaceLines("/^domain_users:/", "");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Sets the mode for the ClearDirectory
	 *
	 * @param int $mode mode of the directory
	 * @throws EngineException
	 */

	function _SetMode($mode)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$file = new File(self::FILE_CONFIG);

		try {
			if (! $file->Exists())
				$file->Create("root", "root", "0644");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		try {
			if ($file->LookupValue("/^mode\s*=\s*/"))
				$file->ReplaceLines("/^mode\s*=/", "mode = $mode\n");
		} catch (FileNoMatchException $e) {
			$file->AddLines("mode = $mode\n");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Restarts the relevant daemons in a sane order.
	 *
	 * @param boolean $background runs method in background if true
	 * @return void
	 */

	protected function _Synchronize($background = true)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$options['background'] = $background;
			$shell = new ShellExec();
			$shell->Execute(Ldap::COMMAND_LDAPSYNC, "full", true, $options);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
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
