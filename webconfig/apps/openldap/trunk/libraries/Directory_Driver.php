<?php

/**
 * OpenLDAP directory driver.
 *
 * @category   Apps
 * @package    OpenLDAP
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2006-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/openldap/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Lesser General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// N A M E S P A C E
///////////////////////////////////////////////////////////////////////////////

namespace clearos\apps\openldap;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('base');
clearos_load_language('directory_manager');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Factories
//----------

use \clearos\apps\directory_manager\Directory_Factory as Directory;

clearos_load_library('directory_manager/Directory_Factory');

// Classes
//--------

use \clearos\apps\base\Configuration_File as Configuration_File;
use \clearos\apps\base\Daemon as Daemon;
use \clearos\apps\base\Engine as Engine;
use \clearos\apps\base\File as File;
use \clearos\apps\base\Folder as Folder;
use \clearos\apps\base\Shell as Shell;
//use \clearos\apps\network\Hostname as Hostname;
use \clearos\apps\network\Network_Utils as Network_Utils;
use \clearos\apps\openldap\OpenLDAP as OpenLDAP;
use \clearos\apps\openldap\Utilities as Utilities;
use \clearos\apps\samba\Samba as Samba;

clearos_load_library('base/Configuration_File');
clearos_load_library('base/Daemon');
clearos_load_library('base/Engine');
clearos_load_library('base/File');
clearos_load_library('base/Folder');
clearos_load_library('base/Shell');
// clearos_load_library('network/Hostname');
clearos_load_library('network/Network_Utils');
clearos_load_library('openldap/OpenLDAP');
clearos_load_library('openldap/Utilities');
clearos_load_library('samba/Samba');

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\File_Not_Found_Exception as File_Not_Found_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('base/File_Not_Found_Exception');
clearos_load_library('base/Validation_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * ClearOS OpenLDAP directory driver.
 *
 * @category   Apps
 * @package    OpenLDAP
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2006-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/openldap/
 */

class Directory_Driver extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const CONSTANT_BASE_DB_NUM = 3;
    const LOG_TAG = 'directory';

    // Commands
    const COMMAND_AUTHCONFIG = '/usr/sbin/authconfig';
    const COMMAND_LDAPSETUP = '/usr/sbin/ldapsetup';
    const COMMAND_LDAPSYNC = '/usr/sbin/ldapsync';
    const COMMAND_OPENSSL = '/usr/bin/openssl';
    const COMMAND_SLAPADD = '/usr/sbin/slapadd';
    const COMMAND_SLAPCAT = '/usr/sbin/slapcat';
    const COMMAND_SLAPPASSWD = '/usr/sbin/slappasswd';

    // Files and paths
    const FILE_DBCONFIG = '/var/lib/ldap/DB_CONFIG';
    const FILE_DBCONFIG_ACCESSLOG = '/var/lib/ldap/accesslog/DB_CONFIG';
    const FILE_LDAP_CONFIG = '/etc/openldap/ldap.conf';
    const FILE_SLAPD_CONFIG = '/etc/openldap/slapd.conf';
    const FILE_SYSCONFIG = '/etc/sysconfig/ldap';
    const FILE_DATA = '/etc/openldap/provision.ldif';

    const PATH_LDAP = '/var/lib/ldap';

// FIXME: Review these -- moved from OpenLDAP class
    const FILE_LDIF_BACKUP = '/etc/openldap/backup.ldif';
    const FILE_SLAPD_CONFIG_CONFIG = '/etc/openldap/slapd.conf';
    const PATH_LDAP_BACKUP = '/usr/share/system/modules/ldap';
    const FILE_LDIF_NEW_DOMAIN = '/etc/openldap/provision/newdomain.ldif';
    const FILE_LDIF_OLD_DOMAIN = '/etc/openldap/provision/olddomain.ldif';

// Move to kolab
    const PATH_KOLAB = '/etc/kolab';
    const FILE_KOLAB_CONFIG = '/etc/kolab/kolab.conf';
    const FILE_KOLAB_SETUP = '/etc/kolab/.kolab2_configured';

    // Internal configuration
    const PATH_SYNCHRONIZE = 'config/synchronize';
    const FILE_CONFIG = 'config/config.php';
    const FILE_INITIALIZED = 'config/initialized.php';
    const FILE_PROVISION_ACCESSLOG_DATA = 'config/provision/provision.accesslog.ldif';
    const FILE_PROVISION_DATA = 'config/provision/provision.ldif.template';
    const FILE_PROVISION_DBCONFIG = 'config/provision/DB_CONFIG.template';
    const FILE_PROVISION_LDAP_CONFIG = 'config/provision/ldap.conf.template';
    const FILE_PROVISION_SLAPD_CONFIG = 'config/provision/slapd.conf.template';
    const FILE_PROVISION_SLAPD_CONFIG_REPLICATE = 'config/provision/slapd-replicate.conf.template';

    // Containers
    const SUFFIX_COMPUTERS = 'ou=Computers,ou=Accounts';
    const SUFFIX_GROUPS = 'ou=Groups,ou=Accounts';
    const SUFFIX_SERVERS = 'ou=Servers';
    const SUFFIX_USERS = 'ou=Users,ou=Accounts';
    const SUFFIX_PASSWORD_POLICIES = 'ou=PasswordPolicies,ou=Accounts';
    const OU_PASSWORD_POLICIES = 'PasswordPolicies';
    const CN_MASTER = 'cn=Master';

    // Status codes for username/group/alias uniqueness
// FIXME: might return just strings instead
    const STATUS_ALIAS_EXISTS = 'alias';
    const STATUS_GROUP_EXISTS = 'group';
    const STATUS_USERNAME_EXISTS = 'user';
    const STATUS_UNIQUE = 'unique';

    // Services available in Directory
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

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $ldaph = NULL;
    protected $config = NULL;
    protected $modes = NULL;

    protected $file_config = NULL;
    protected $file_initialized = NULL;
    protected $file_provision_accesslog_data = NULL;
    protected $file_provision_data = NULL;
    protected $file_provision_dbconfig = NULL;
    protected $file_provision_ldap_config = NULL;
    protected $file_provision_slapd_config = NULL;
    protected $file_provision_slapd_config_replicate = NULL;
    protected $path_synchronize = NULL;

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * OpenLDAP_Driver constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->modes = array(
            Directory::MODE_MASTER => lang('directory_manager_master'),
            Directory::MODE_SLAVE => lang('directory_manager_slave'),
            Directory::MODE_STANDALONE => lang('directory_manager_standalone')
        );

        $this->path_synchronize = clearos_app_base('openldap') . '/' . self::PATH_SYNCHRONIZE;
        $this->file_config = clearos_app_base('openldap') . '/' . self::FILE_CONFIG;
        $this->file_initialized = clearos_app_base('openldap') . '/' . self::FILE_INITIALIZED;
        $this->file_provision_accesslog_data = clearos_app_base('openldap') . '/' . self::FILE_PROVISION_ACCESSLOG_DATA;
        $this->file_provision_data = clearos_app_base('openldap') . '/' . self::FILE_PROVISION_DATA;
        $this->file_provision_dbconfig = clearos_app_base('openldap') . '/' . self::FILE_PROVISION_DBCONFIG;
        $this->file_provision_ldap_config = clearos_app_base('openldap') . '/' . self::FILE_PROVISION_LDAP_CONFIG;
        $this->file_provision_slapd_config = clearos_app_base('openldap') . '/' . self::FILE_PROVISION_SLAPD_CONFIG;
        $this->file_provision_slapd_config_replicate = clearos_app_base('openldap') . '/' . self::FILE_PROVISION_SLAPD_CONFIG_REPLICATE;
    }

    /**
     * Check for overlapping usernames, groups and aliases in the directory.
     *
     * @param string $id username, group or alias
     *
     * @return string warning message if ID is not unique
     */

    public function check_uniqueness($id)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->ldaph === NULL)
            $this->ldaph = Utilities::get_ldap_handle();

        // Check for duplicate user
        //-------------------------

        try {
            $result = $this->ldaph->search(
                "(&(objectclass=inetOrgPerson)(uid=$id))",
                self::get_users_ou(),
                array('dn')
            );

            $entry = $this->ldaph->get_first_entry($result);

            if ($entry)
                return "Username already exists."; // FIXME self::STATUS_USERNAME_EXISTS;
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        // Check for duplicate alias
        //--------------------------

        try {
            $result = $this->ldaph->Search(
                "(&(objectclass=inetOrgPerson)(clearMailAliases=$id))",
                self::get_users_ou(),
                array('dn')
            );

            $entry = $this->ldaph->get_first_entry($result);

            if ($entry)
                return "Mail alias already exists."; // FIXME self::STATUS_ALIAS_EXISTS;
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        // Check for duplicate group
        //--------------------------
    
        // The "displayName" is used in Samba group mapping.  In other words,
        // the "displayName" is what is used by Windows networking (not the cn).

        try {
            $result = $this->ldaph->Search(
                "(&(objectclass=posixGroup)(|(cn=$id)(displayName=$id)))",
                self::get_groups_ou(),
                array('dn')
            );

            $entry = $this->ldaph->get_first_entry($result);

            if ($entry)
                return "Group already exists."; // self::STATUS_GROUP_EXISTS;
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        // FIXME: Flexshares?  How do we deal with this in master/replica mode?
    }

    /**
     * Exports LDAP database to LDIF.
     *
     * @param string  $ldif  LDIF backup file
     * @param integer $dbnum database number
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function export($ldif = self::FILE_LDIF_BACKUP, $dbnum = self::CONSTANT_BASE_DB_NUM)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $export = new File($ldif, TRUE);

            if ($export->exists())
                $export->delete();

            if ($this->ldaph === NULL)
                $this->ldaph = Utilities::get_ldap_handle();

            $was_running = $this->ldaph->get_running_state();

            if ($was_running)
                $this->ldaph->set_running_state(FALSE);

            $shell = new Shell();
            $shell->execute(self::COMMAND_SLAPCAT, "-n$dbnum -l " . $ldif, TRUE);

            if ($was_running)
                $this->ldaph->set_running_state(TRUE);
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /** 
     * Returns the base DN.
     *
     * @return string base DN
     * @throws Engine_Exception
     */

    public function get_base_dn()
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->config['base_dn'] !== NULL) 
            return $this->config['base_dn'];

        if ($this->ldaph === NULL)
            $this->ldaph = Utilities::get_ldap_handle();

        $base_dn = $this->ldaph->get_base_dn();

        $this->config['base_dn'] = $base_dn;

        return $base_dn;
    }

    /** 
     * Returns base DN in Internet domain format.
     *
     * @return string default domain
     * @throws Engine_Exception
     */

    public function get_base_internet_domain()
    {
        clearos_profile(__METHOD__, __LINE__);

        $base_dn = $this->get_base_dn();

        $domain = preg_replace('/(,dc=)/', '.', $base_dn);
        $domain = preg_replace('/dc=/', '', $domain);

        return $domain;
    }

    /** 
     * Returns the OU container for computers.
     *
     * @return string OU container for computers.
     * @throws Engine_Exception
     */

    public function get_computers_ou()
    {
        clearos_profile(__METHOD__, __LINE__);

        return self::SUFFIX_COMPUTERS . ',' . $this->get_base_dn();
    }

    /** 
     * Returns the OU container for groups.
     *
     * @return string OU container for groups
     * @throws Engine_Exception
     */

    public function get_groups_ou()
    {
        clearos_profile(__METHOD__, __LINE__);

        return self::SUFFIX_GROUPS . ',' . $this->get_base_dn();
    }

    /**
     * Returns list of directory extensions.
     *
     * @return array list of installed extensions
     * @throws Engine_Exception
     */

    public function get_installed_extensions()
    {
        clearos_profile(__METHOD__, __LINE__);

        // FIXME: extension auto-load
        if (file_exists("/etc/system/initialized/sambalocal"))
            $services[] = self::SERVICE_TYPE_SAMBA;

        if (file_exists(COMMON_CORE_DIR . "/api/Cyrus.class.php"))
            $services[] = self::SERVICE_TYPE_EMAIL;

        if (file_exists(COMMON_CORE_DIR . "/api/GoogleApps.class.php"))
            $services[] = self::SERVICE_TYPE_GOOGLE_APPS;

        if (file_exists(COMMON_CORE_DIR . "/api/Pptpd.class.php"))
            $services[] = self::SERVICE_TYPE_PPTP;

        if (file_exists(COMMON_CORE_DIR . "/api/OpenVpn.class.php"))
            $services[] = self::SERVICE_TYPE_OPENVPN;

        if (file_exists(COMMON_CORE_DIR . "/api/Squid.class.php"))
            $services[] = self::SERVICE_TYPE_PROXY;

        if (file_exists(COMMON_CORE_DIR . "/api/Proftpd.class.php"))
            $services[] = self::SERVICE_TYPE_FTP;

        if (file_exists(COMMON_CORE_DIR . "/api/Httpd.class.php"))
            $services[] = self::SERVICE_TYPE_WEB;

        if (file_exists(COMMON_CORE_DIR . "/iplex/Users.class.php"))
            $services[] = self::SERVICE_TYPE_PBX;

        return $services;
    }

    /**
     * Returns list of services managed in Directory.
     *
     * @return array list of services
     * @throws Engine_Exception
     */

    public function get_installed_plugins()
    {
        clearos_profile(__METHOD__, __LINE__);

        // Standalone and replicates only need to show installed services
        //---------------------------------------------------------------

        $mode = $this->get_mode();

        if (($mode === Directory::MODE_STANDALONE) || ($mode === Directory::MODE_SLAVE))
            return $this->GetInstalledServices();

        // Master nodes should show all services
        //--------------------------------------

        $services = array();

        // TODO: Allow user to fine tune which services should appear
        // TODO: For now, Samba has to be initialized first...

        if (file_exists("/etc/system/initialized/sambalocal"))
            $services[] = self::SERVICE_TYPE_SAMBA;

        $services[] = self::SERVICE_TYPE_EMAIL;
        $services[] = self::SERVICE_TYPE_GOOGLE_APPS;
        $services[] = self::SERVICE_TYPE_PPTP;
        $services[] = self::SERVICE_TYPE_OPENVPN;
        $services[] = self::SERVICE_TYPE_PROXY;
        $services[] = self::SERVICE_TYPE_FTP;
        $services[] = self::SERVICE_TYPE_WEB;

        return $services;
    }

    /**
     * Returns the DN of the master server 
     *
     * @return string DN of the master server
     * @throws Engine_Exception
     */

    public function get_master_dn()
    {
        clearos_profile(__METHOD__, __LINE__);

        return "cn=Master,ou=Servers," . $this->get_base_dn();
    }

    /**
     * Returns the mode of directory.
     *
     * The return values are:
     * - Directory::MODE_STANDALONE
     * - Directory::MODE_MASTER
     * - Directory::MODE_SLAVE
     *
     * @return string mode of the directory
     * @throws Engine_Exception
     */

    public function get_mode()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $file = new Configuration_File($this->file_config);
            $config = $file->load();
        } catch (File_Not_Found_Exception $e) {
            // Not fatal
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
        
        if (isset($config['mode'])) {
            if ($config['mode'] === Directory::MODE_MASTER)
                $mode = Directory::MODE_MASTER;
            else if ($config['mode'] === Directory::MODE_SLAVE)
                $mode = Directory::MODE_SLAVE;
            else if ($config['mode'] === Directory::MODE_STANDALONE)
                $mode = Directory::MODE_STANDALONE;
            else 
                $mode = Directory::MODE_STANDALONE;
        } else {
            $mode = Directory::MODE_STANDALONE;
        }

        return $mode;
    }

    /**
     * Returns a list of available modes.
     *
     * @return array list of modes
     * @throws Engine_Exception
     */

    public function get_modes()
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->modes;
    }

    /** 
     * Returns the OU for password policies.
     *
     * @return string OU for password policies
     * @throws Engine_Exception
     */

    public function get_password_policies_ou()
    {
        clearos_profile(__METHOD__, __LINE__);

        return self::SUFFIX_PASSWORD_POLICIES . ',' . $this->get_base_dn();
    }

    /** 
     * Returns security policy.
     * 
     * @return integer security policy constant
     * @throws Engine_Exception
     * @see set_security_policy
     */

    public function get_security_policy()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new Configuration_File(self::FILE_SYSCONFIG);

        try {
            $sysconfig = $file->load();

            if (empty($sysconfig['BIND_POLICY']))
                return Directory::POLICY_LOCALHOST;
            else if ($sysconfig['BIND_POLICY'] == Directory::POLICY_LAN)
                return Directory::POLICY_LAN;
            else
                return Directory::POLICY_LOCALHOST;

        } catch (File_Not_Found_Exception $e) {
            return Directory::POLICY_LOCALHOST;
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /** 
     * Returns the OU for servers.
     *
     * @return string OU for servers.
     * @throws Engine_Exception
     */

    // FIXME: might remove this
    public function get_servers_ou()
    {
        clearos_profile(__METHOD__, __LINE__);

        return self::SUFFIX_SERVERS . ',' . $this->get_base_dn();
    }

    /** 
     * Returns the OU for users.
     *
     * @return string OU for users.
     * @throws Engine_Exception
     */

    public function get_users_ou()
    {
        clearos_profile(__METHOD__, __LINE__);

        return self::SUFFIX_USERS . ',' . $this->get_base_dn();
    }

    /**
     * Imports backup LDAP database from LDIF.
     *
     * @return boolean TRUE if import file exists
     */

// FIXME
// add a "run_import" for background mode
    public function import()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $import = new File(self::FILE_LDIF_BACKUP, TRUE);

            if (! $import->Exists())
                return FALSE;
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        $this->_import_ldif(self::FILE_LDIF_BACKUP);

        return TRUE;
    }

    /**
     * Common initialization routine for the LDAP modes.
     *
     * @param string $mode LDAP server mode
     * @param string $domain domain name
     * @param string $password bind DN password
     * @param options options array depending on mode
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    protected function _initialize($mode, $domain, $password, $options)
    {
        clearos_profile(__METHOD__, __LINE__);

        $force = $options['force'];
        $start = $options['start'];
        $master_hostname = $options['master_hostname'];

        // Bail if LDAP is already initialized (and not a re-initialize)
        //--------------------------------------------------------------

        if ($this->is_initialized() && (!$force))
            return;

        // KLUDGE: shutdown Samba or it will try to write information to LDAP
        //-------------------------------------------------------------------

        $samba = new Samba();

        if ($samba->is_installed())
            $samba->set_running_state(FALSE);

        // Determine our hostname and generate an LDAP password (if required)
        //-------------------------------------------------------------------

        // FIXME: hostname class is busted
        /*
        $hostnameinfo = new Hostname();
        $hostname = $hostnameinfo->Get();
        */
        $hostname = 'test.lan';

        // Generate the configuration files
        //---------------------------------

        $this->_initialize_configuration($mode, $domain, $password, $hostname, $master_hostname);

        if ($mode === Directory::MODE_SLAVE) {
            $master_hostname = '192.168.2.248';
            // $this->_initialize_slave_configuration($domain, $password, $hostname, $master_hostname);
            $this->set_security_policy(Directory::POLICY_LAN);
        } else {
            $this->set_security_policy(Directory::POLICY_LOCALHOST);
        }

        // Do the post-config heavy lifting
        //---------------------------------

        //$this->_import_ldif(self::FILE_DATA);
        $this->_initialize_authconfig();
        $this->_remove_overlaps();
        $this->_set_initialized();
        $this->_set_startup($start);

        // Synchronize
        //------------

        $this->_synchronize();

        // Initialize Samba elements in LDAP
        //----------------------------------

        if ($mode !== Directory::MODE_SLAVE) {
/*
        // FIXME
            if (!$samba->IsDirectoryInitialized()) {
                $workgroup = $samba->GetWorkgroup();
                $samba->InitializeDirectory($workgroup);
            }
*/
        }
    }

    /**
     * Initializes the LDAP database in master mode.
     *
     * @param string $mode LDAP server mode
     * @param string $domain domain name
     * @param string $password bind DN password
     * @param boolean $start starts LDAP after initialization
     * @param boolean $force forces initialization even if LDAP server already has data
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function initialize_master($domain, $password = NULL, $force = FALSE, $start = TRUE)
    {
        clearos_profile(__METHOD__, __LINE__);

        $options['force'] = $force;
        $options['start'] = $start;

        if (empty($password))
            $password = Utilities::generate_password();

        $this->_initialize(Directory::MODE_MASTER, $domain, $password, $options);
    }

    /**
     * Initializes the LDAP database in slave mode.
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function initialize_slave($domain, $password, $master_hostname, $force = FALSE, $start = TRUE)
    {
        clearos_profile(__METHOD__, __LINE__);

        $options['force'] = $force;
        $options['start'] = $start;
        $options['master_hostname'] = $master_hostname;

        $this->_initialize(Directory::MODE_SLAVE, $domain, $password, $options);
    }

    /**
     * Initializes the LDAP database in standalone mode.
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function initialize_standalone($domain, $password = NULL, $force = FALSE, $start = TRUE)
    {
        clearos_profile(__METHOD__, __LINE__);

        $options['force'] = $force;
        $options['start'] = $start;

        if (empty($password))
            $password = Utilities::generate_password();

        $this->_initialize(Directory::MODE_STANDALONE, $domain, $password, $options);
    }

    /**
     * Returns the availability of LDAP.
     *
     * @return boolean TRUE if LDAP is running
     */

    public function is_available()
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->ldaph === NULL)
            $this->ldaph = Utilities::get_ldap_handle();

        $available = $this->ldaph->is_available();

        return $available;
    }

    /**
     * Returns state of LDAP setup.
     *
     *
     * @return boolean TRUE if LDAP has been initialized
     */

    public function is_initialized()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File($this->file_initialized);

        if ($file->exists())
            return TRUE;
        else
            return FALSE;
    }

    /**
     * Initialized LDAP system.
     *
     * @param string $mode LDAP server mode
     * @param string $domain domain name
     *
     * @return void
     * @throws Engine_Exception
     */

    public function run_initialize($mode, $domain)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->IsInitialized())
            return;

        $options['stdin'] = TRUE;
        $options['background'] = TRUE;

        $password = Utilities::generate_password();

        $shell = new Shell();
        $shell->Execute(self::COMMAND_LDAPSETUP, "-r $mode -d $domain -p $password", TRUE, $options);
    }

    /**
     * Changes base domain used in directory
     *
     * @param string $domain domain
     * @param boolean $background run in background
     *
     * @return void
     */

    public function set_domain($domain, $background = FALSE)
    {
        clearos_profile(__METHOD__, __LINE__);

        $network = new Network_Utils();

        if (! $network->IsValidDomain($domain))
            throw new Validation_Exception(NETWORK_LANG_DOMAIN . " - " . LOCALE_LANG_INVALID);

        if ($background) {
            try {
                $options['background'] = TRUE;
                $shell = new Shell();
                $shell->Execute(Engine::COMMAND_API, 'Directory SetDomain ' . $domain, TRUE, $options);
            } catch (Exception $e) {
                throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
            }

            return;
        }

        if ($this->ldaph === NULL)
            $this->ldaph = Utilities::get_ldap_handle();

        $was_running = FALSE;

        try {
            // Grab hostname
            //--------------

            $hostnameobj = new Hostname();
            $hostname = $hostnameobj->Get();

            // Dump LDAP database to export file
            //----------------------------------

            $was_running = $this->ldaph->get_running_state();
            $this->Export(self::FILE_LDIF_OLD_DOMAIN, self::CONSTANT_BASE_DB_NUM);

            // Load LDAP export file
            //----------------------

            $export = new File(self::FILE_LDIF_OLD_DOMAIN, TRUE);
            $exportlines = $export->GetContentsAsArray();

            // Load Kolab configuration file
            //------------------------------

// FIXME - FILE_KOLAB_CONFIG should not be here.
            $kolabconfig = new File(OpenLDAP::FILE_KOLAB_CONFIG);
            $kolablines = $kolabconfig->GetContentsAsArray();

            // Load LDAP configuration
            //------------------------

            $ldapconfig = new File(self::FILE_SLAPD_CONFIG_CONFIG);
            $ldaplines = $ldapconfig->GetContentsAsArray();

            // Load LDAP information
            //----------------------

            $basedn = $this->ldaph->GetBaseDn();

        } catch (Exception $e) {
            if ($was_running)
                $this->ldaph->set_running_state(TRUE);

            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
        
        // Remove word wrap from LDIF data
        //--------------------------------

        $cleanlines = array();

        foreach ($exportlines as $line) {
            if (preg_match('/^\s+/', $line)) {
                $previous = array_pop($cleanlines);
                $cleanlines[] = $previous . preg_replace('/^ /', '', $line);
            } else {
                $cleanlines[] = $line;
            }
        }

        // Rewrite LDAP export file
        //-------------------------

        $newbasedn = 'dc=' . preg_replace('/\./', ',dc=', $domain);
        $matches = array();

        preg_match('/^dc=([^,]*)/', $basedn, $matches);
        $olddc = $matches[1];

        preg_match('/^dc=([^,]*)/', $newbasedn, $matches);
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

            $newexport->Create('root', 'root', '0600');
            $newexport->DumpContentsFromArray($ldiflines);

            // LDAP configuration
            //--------------------

            $newldap = new File(self::FILE_SLAPD_CONFIG_CONFIG, TRUE);

            if ($newldap->Exists())
                $newldap->Delete();

            $newldap->Create('root', 'ldap', '0640');
            $newldap->DumpContentsFromArray($newldaplines);

            // Kolab configuration
            //--------------------

// FIXME - FILE_KOLAB_CONFIG should not be here.
            $newconfig = new File(OpenLDAP::FILE_KOLAB_CONFIG, TRUE);

            if ($newconfig->Exists())
                $newconfig->Delete();

            $newconfig->Create('root', 'root', '0600');
            $newconfig->DumpContentsFromArray($newkolablines);

            // Import
            //-------

            $this->_import_ldif(self::FILE_LDIF_NEW_DOMAIN);

            // Perform Authconfig initialization in case LDAP has been manually initialized
            //-----------------------------------------------------------------------------

            $this->_initialize_authconfig();

        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        try {
            // TODO: Should not need to explicitly call _CleanSecretsFile
            // FIXME: this shold not be here
            if (file_exists(COMMON_CORE_DIR . '/api/Samba.class.php')) {
                require_once('Samba.class.php');
                $samba = new Samba();
                $samba->_CleanSecretsFile('');
            }

            // Tell other LDAP dependent apps to grab latest configuration
            // TODO: move this to a daemon
            if ($was_running)
                $this->_synchronize(FALSE);

        } catch (Exception $e) {
            // Not fatal
        }
    }

    /** 
     * Sets security policy.
     *
     * The LDAP server can be configured to listen on:
     * -  localhost only: OpenLDAP::POLICY_LOCALHOST
     * -  localhost and all LAN interfaces: OpenLDAP::POLICY_LAN
     *
     * @param boolean $policy policy setting
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_security_policy($policy)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($errmsg = $this->validate_security_policy($policy))
            throw new Validation_Exception($errmsg);

        $file = new File(self::FILE_SYSCONFIG);

        if ($file->exists()) {
            $matches = $file->replace_lines('/^BIND_POLICY=.*/', "BIND_POLICY=$policy\n");
            if ($matches === 0)
                $file->add_lines("BIND_POLICY=$policy\n");
        } else {
            $file->create('root', 'root', '0644');
            $file->add_lines("BIND_POLICY=$policy\n");
        }
    }

    /**
     * Synchronizes template files.
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */
    
    public function synchronize_configs()
    {
        clearos_profile(__METHOD__, __LINE__);

        // Load directory configuration settings
        //--------------------------------------

        $config_file = new File($this->file_config);

        $lines = $config_file->get_contents_as_array();

        $base_dn = '';
        $bind_dn = '';
        $bind_pw = '';
        $bind_pw_hash = '';

        foreach ($lines as $line) {
            if (preg_match('/^base_dn\s*=/', $line))
                $base_dn = preg_replace('/^base_dn\s*=\s*/', '', $line);
            if (preg_match('/^bind_dn\s*=/', $line))
                $bind_dn = preg_replace('/^bind_dn\s*=\s*/', '', $line);
            if (preg_match('/^bind_pw\s*=/', $line))
                $bind_pw = preg_replace('/^bind_pw\s*=\s*/', '', $line);
            if (preg_match('/^bind_pw_hash\s*=/', $line))
                $bind_pw_hash = preg_replace('/^bind_pw_hash\s*=\s*/', '', $line);
        }

        // Synchronize all the configs 
        //----------------------------

        $folder = new Folder($this->path_synchronize);

        $sync_files = $folder->get_listing();

        foreach ($sync_files as $sync_file) {

            // Pull out metadata from sync files
            //----------------------------------

            $contents = '';
            $target = '';
            $owner = '';
            $group = '';
            $permissions = '';
            $warning = "Please do not edit - this file is automatically generated.\n\n";

            $file = new File($this->path_synchronize . '/' . $sync_file);
            $sync_contents = $file->get_contents_as_array();

            foreach ($sync_contents as $line) {
                if (preg_match('/CLEAROS_DIRECTORY_TARGET=/', $line))
                    $target = preg_replace('/.*CLEAROS_DIRECTORY_TARGET=/', '', $line);
                else if (preg_match('/CLEAROS_DIRECTORY_PERMISSIONS=/', $line))
                    $permissions = preg_replace('/.*CLEAROS_DIRECTORY_PERMISSIONS=/', '', $line);
                else if (preg_match('/CLEAROS_DIRECTORY_OWNER=/', $line))
                    $owner = preg_replace('/.*CLEAROS_DIRECTORY_OWNER=/', '', $line);
                else if (preg_match('/CLEAROS_DIRECTORY_GROUP=/', $line))
                    $group = preg_replace('/.*CLEAROS_DIRECTORY_GROUP=/', '', $line);
                else if (preg_match('/CLEAROS_DIRECTORY_WARNING_MESSAGE/', $line))
                    $contents .= preg_replace('/CLEAROS_DIRECTORY_WARNING_MESSAGE/', $warning, $line);
                else
                    $contents .= $line . "\n";
            }

            // Perform search replace on variables
            //------------------------------------

            $contents = preg_replace("/\@\@\@base_dn\@\@\@/", $base_dn, $contents);
            $contents = preg_replace("/\@\@\@bind_dn\@\@\@/", $bind_dn, $contents);
            $contents = preg_replace("/\@\@\@bind_pw\@\@\@/", $bind_pw, $contents);
            $contents = preg_replace("/\@\@\@bind_pw_hash\@\@\@/", $bind_pw_hash, $contents);

            // Write out file
            //---------------

            $target = new File($target);

            if ($target->exists())
                $target->delete();

            $target->create($owner, $group, $permissions);
            $target->add_lines($contents);
        }
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validates domain.
     *
     * @param string $domain domain
     *
     * @return string error message if domain is invalid
     */

    public function validate_domain($domain)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! Network_Utils::is_valid_domain($domain))
            return lang('openldap_domain_is_invalid');
    }

    /**
     * Validates LDAP mode.
     *
     * @param string $mode LDAP mode
     *
     * @return string error message if LDAP mode is invalid
     */

    public function validate_mode($mode)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (isset($mode) && array_key_exists($mode, $this->modes))
            return TRUE;
        else
            return FALSE;
    }

    /**
     * Validates LDAP password.
     *
     * @param string $password LDAP password
     *
     * @return string error message if LDAP password is invalid
     */

    public function validate_password($password)
    {
        clearos_profile(__METHOD__, __LINE__);

        // FIXME
        if (empty($password))
            return lang('base_password_is_invalid');
    }

    /**
     * Validates security policy.
     *
     * @param string $policy policy
     *
     * @return string error message if security is invalid
     */

    public function validate_security_policy($policy)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (($policy !== Directory::POLICY_LOCALHOST) && ($policy !== Directory::POLICY_LAN))
            return lang('openldap_security_policy_is_invalid');
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E  M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Initializes authconfig.
     *
     * This method will update the nsswitch.conf and pam configuration.
     */

    protected function _initialize_authconfig()
    {
        clearos_profile(__METHOD__, __LINE__);

        // FIXME: Add winbind stuff
        $shell = new Shell();
        $shell->execute(self::COMMAND_AUTHCONFIG, 
            '--enableshadow --enablemd5 --enableldap --enableldapauth --update', 
            TRUE
        );
    }

    /**
     * Initializes LDAP configuration.
     *
     * @param string $domain   Internet domain
     * @param string $password directory administrator password 
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    protected function _initialize_configuration($mode, $domain, $password, $hostname, $master_hostname = '')
    {
        clearos_profile(__METHOD__, __LINE__);

        // TODO: validate

        // Initialize some variables
        //--------------------------

        $base_dn = preg_replace('/\./', ',dc=', $domain);
        $base_dn = "dc=$base_dn";

        $base_dn_rdn = preg_replace('/,.*/', '', $base_dn);
        $base_dn_rdn = preg_replace('/dc=/', '', $base_dn_rdn);

        $bind_pw = $password;
// FIXME: testing to version 5.x
//        $bind_dn = "cn=manager,ou=Internal,$base_dn";
        $bind_dn = "cn=manager,cn=internal,$base_dn";

        $shell = new Shell();

        $shell->execute(self::COMMAND_SLAPPASSWD, "-s $bind_pw");
        $bind_pw_hash = $shell->get_first_output_line();

        // Create internal configuration file
        //-----------------------------------

        $config = "mode = " . $mode . "\n";
        $config .= "base_dn = $base_dn\n";
        $config .= "bind_dn = $bind_dn\n";
        $config .= "bind_pw = $bind_pw\n";
        $config .= "bind_pw_hash = $bind_pw_hash\n";

        $file = new File($this->file_config);

        if ($file->exists())
            $file->delete();

        $file->create('root', 'root', '0644'); // FIXME: put permissions back to 0600
        $file->add_lines($config);

        // Create slapd.conf configuration
        //--------------------------------

        if ($mode === Directory::MODE_SLAVE)
            $slapd = $this->file_provision_slapd_config_replicate;
        else
            $slapd = $this->file_provision_slapd_config;

        $file = new File($slapd);

        $contents = $file->get_contents();
        $contents = preg_replace("/\@\@\@base_dn\@\@\@/", $base_dn, $contents);
        $contents = preg_replace("/\@\@\@bind_dn\@\@\@/", $bind_dn, $contents);
        $contents = preg_replace("/\@\@\@bind_pw\@\@\@/", $bind_pw, $contents);
        $contents = preg_replace("/\@\@\@bind_pw_hash\@\@\@/", $bind_pw_hash, $contents);
        $contents = preg_replace("/\@\@\@domain\@\@\@/", $domain, $contents);
        $contents = preg_replace("/\@\@\@master_hostname\@\@\@/", $master_hostname, $contents);

        $newfile = new File(self::FILE_SLAPD_CONFIG);

        if ($newfile->exists())
            $newfile->delete();

        $newfile->create('root', 'ldap', '0640');
        $newfile->add_lines("$contents\n");

        // Create ldap.conf configuration
        //-------------------------------

        $file = new File($this->file_provision_ldap_config);

        $contents = $file->get_contents();
        $contents = preg_replace("/\@\@\@base_dn\@\@\@/", $base_dn, $contents);

        $newfile = new File(self::FILE_LDAP_CONFIG);

        if ($newfile->exists())
            $newfile->delete();

        $newfile->create('root', 'root', '0644');
        $newfile->add_lines("$contents\n");

        // Slave mode... bug out, we're done
        //----------------------------------

        if ($mode === Directory::MODE_SLAVE)
            return;

        // Create DB_CONFIG configuration
        //-------------------------------

        $file = new File($this->file_provision_dbconfig);

        $contents = $file->get_contents();

        $newfile = new File(self::FILE_DBCONFIG, TRUE);

        if ($newfile->exists())
            $newfile->delete();

        $newfile->create('ldap', 'ldap', '0644');
        $newfile->add_lines("$contents\n");

        // DB_CONFIG configuration for accesslog
        //--------------------------------------

        $file = new File($this->file_provision_dbconfig);

        $contents = $file->get_contents();

        $newfile = new File(self::FILE_DBCONFIG_ACCESSLOG, TRUE);

        if ($newfile->exists())
            $newfile->delete();

        $newfile->create('ldap', 'ldap', '0644');
        $newfile->add_lines("$contents\n");

        // LDAP provision data file
        //-------------------------

        $file = new File($this->file_provision_data);

        $contents = $file->get_contents();
        $contents = preg_replace("/\@\@\@base_dn\@\@\@/", $base_dn, $contents);
        $contents = preg_replace("/\@\@\@base_dn_rdn\@\@\@/", $base_dn_rdn, $contents);
        $contents = preg_replace("/\@\@\@bind_pw_hash\@\@\@/", $bind_pw_hash, $contents);

        $newfile = new File(self::FILE_DATA);

        if ($newfile->exists())
            $newfile->delete();

        $newfile->create('root', 'ldap', '0640');
        $newfile->add_lines("$contents\n");
    }

    /**
     * Imports an LDIF file.
     *
     * @param string $ldif LDIF file
     * @throws Engine_Exception, Validation_Exception
     */

    protected function _import_ldif($ldif)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->ldaph === NULL)
            $this->ldaph = Utilities::get_ldap_handle();

        // FIXME
        // Logger::Syslog(self::LOG_TAG, "preparing LDAP import");

        // Shutdown LDAP if running
        //-------------------------

        $was_running = $this->ldaph->get_running_state();

        if ($was_running) {
            // FIXME
            // Logger::Syslog(self::LOG_TAG, "shutting down LDAP server");
            $this->ldaph->set_running_state(FALSE);
        }

        // Backup old LDAP
        //----------------

/*
FIXME: re-enable backup
        $filename = self::PATH_LDAP_BACKUP . '/' . "backup-" . strftime("%m-%d-%Y-%H-%M-%S", time()) . ".ldif";
        $this->export($filename);
*/
        // Clear out old database
        //-----------------------

        $folder = new Folder(self::PATH_LDAP);

        $filelist = $folder->get_recursive_listing();

        foreach ($filelist as $filename) {
            if (!preg_match('/DB_CONFIG$/', $filename)) {
                $file = new File(self::PATH_LDAP . '/' . $filename, TRUE);
                $file->delete();
            }
        }

        // Import new database
        //--------------------

        // FIXME
        // Logger::Syslog(self::LOG_TAG, "loading data into LDAP server");
        $shell = new Shell();
        $shell->execute(self::COMMAND_SLAPADD, '-n2 -l ' . $this->file_provision_accesslog_data, TRUE);
        $shell->execute(self::COMMAND_SLAPADD, '-n3 -l ' . $ldif, TRUE);

        // Set flag to indicate Kolab has been initialized
        //-----------------------------------------------

        /* FIXME: move to kolab
        $file = new File(self::FILE_KOLAB_SETUP);

        if (! $file->exists())
            $file->create("root", "root", "0644");
        */

        // Fix file permissions
        //---------------------

        $folder->chown("ldap", "ldap", TRUE);

        if ($was_running) {
            // FIXME
            // Logger::Syslog(self::LOG_TAG, "restarting LDAP server");
            $this->ldaph->set_running_state(TRUE);
        }
    }

    /**
     * Removes overlapping groups and users found in Posix.
     *
     * Some default users/groups found in the Posix system overlap with LDAP
     * entries.  For example, the group "users" is often listed in /etc/group.
     * Since a Windows Network considers the "Users" group in a special way,
     * it is best to not have it floating around.
     *
     * @return void
     * @throws Engine_Exception
     */

    protected function _remove_overlaps()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File("/etc/group");
        $file->replace_lines("/^users:/", "");
        $file->replace_lines("/^domain_users:/", "");
    }

    /**
     * Sets initialized flag
     *
     */
    protected function _set_initialized()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File($this->file_initialized);

        if (! $file->exists())
            $file->create("root", "root", "0644");
    }

    /**
     * Sets startup policy
     *
     */

    protected function _set_startup($start)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->ldaph === NULL)
            $this->ldaph = Utilities::get_ldap_handle();

        $this->ldaph->set_boot_state(TRUE);

        /*
        FIXME
        $ldapsync = new Daemon("ldapsync");
        $ldapsync->set_boot_state(TRUE);
        */

        if ($start) {
            $this->ldaph->restart();
            // FIXME $ldapsync->restart();
        }
    }

    /**
     * Restarts the relevant daemons in a sane order.
     *
     * @return void
     */

    protected function _synchronize()
    {
        clearos_profile(__METHOD__, __LINE__);

// FIXME: do we still need a background?
//        $options['background'] = $background;
        $shell = new Shell();
        // FIXME
        // $shell->Execute(self::COMMAND_LDAPSYNC, "full", TRUE, $options);
    }
}
