<?php

/**
 * OpenLDAP directory driver.
 *
 * @category   Apps
 * @package    OpenLDAP_Directory
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2006-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/openldap_directory/
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

namespace clearos\apps\openldap_directory;

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

use \clearos\apps\base\Engine as Engine;
use \clearos\apps\base\File as File;
use \clearos\apps\base\Folder as Folder;
use \clearos\apps\base\Shell as Shell;
use \clearos\apps\network\Network_Utils as Network_Utils;
use \clearos\apps\openldap\LDAP_Driver as LDAP_Driver;
use \clearos\apps\openldap_directory\Nslcd as Nslcd;
use \clearos\apps\openldap_directory\Utilities as Utilities;

clearos_load_library('base/Engine');
clearos_load_library('base/File');
clearos_load_library('base/Folder');
clearos_load_library('base/Shell');
clearos_load_library('network/Network_Utils');
clearos_load_library('openldap/LDAP_Driver');
clearos_load_library('openldap_directory/Nslcd');
clearos_load_library('openldap_directory/Utilities');

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;

clearos_load_library('base/Engine_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * ClearOS OpenLDAP directory driver.
 *
 * @category   Apps
 * @package    OpenLDAP_Directory
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2006-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/openldap_directory/
 */

class Directory_Driver extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const CONSTANT_BASE_DB_NUM = 3;

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
    const FILE_INITIALIZED = '/var/clearos/openldap_directory/initialized.php';

    const PATH_LDAP = '/var/lib/ldap';
    const PATH_EXTENSIONS = '/var/clearos/openldap_directory/extensions';

// FIXME: Review these -- moved from OpenLDAP class
    const FILE_LDIF_BACKUP = '/etc/openldap/backup.ldif';
    const FILE_SLAPD_CONFIG_CONFIG = '/etc/openldap/slapd.conf';
    const PATH_LDAP_BACKUP = '/usr/share/system/modules/ldap';
    const FILE_LDIF_NEW_DOMAIN = '/etc/openldap/provision/newdomain.ldif';
    const FILE_LDIF_OLD_DOMAIN = '/etc/openldap/provision/olddomain.ldif';

    // Internal configuration
    const PATH_SYNCHRONIZE = 'config/synchronize';
    const FILE_CONFIG = 'config/config.php';

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

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $ldaph = NULL;
    protected $config = NULL;
    protected $modes = NULL;

    protected $file_config = NULL;

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
                self::get_users_container(),
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
                self::get_users_container(),
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
                self::get_groups_container(),
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
     * Exports users, groups and computers to LDIF.
     *
     * @param string  $ldif  LDIF backup file
     * @param integer $dbnum database number
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception

     * @return void
     */

    public function export($ldif = self::FILE_LDIF_BACKUP, $dbnum = self::CONSTANT_BASE_DB_NUM)
    {
        clearos_profile(__METHOD__, __LINE__);

        // TODO: split code from OpenLDAP driver

        $ldap = new LDAP_Driver();
        $ldap->export($ldif, $dbnum);
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

        $ldap = new LDAP_Driver();
        return $ldap->get_base_dn();
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

        $ldap = new LDAP_Driver();
        return $ldap->get_base_internet_domain();
    }

    /** 
     * Returns the container for computers.
     *
     * @return string container for computers.
     * @throws Engine_Exception
     */

    public function get_computers_container()
    {
        clearos_profile(__METHOD__, __LINE__);

        return self::SUFFIX_COMPUTERS . ',' . $this->get_base_dn();
    }

    /** 
     * Returns the ontainer for groups.
     *
     * @return string container for groups
     * @throws Engine_Exception
     */

    public function get_groups_container()
    {
        clearos_profile(__METHOD__, __LINE__);

        return self::SUFFIX_GROUPS . ',' . $this->get_base_dn();
    }

    /**
     * Returns list of directory extensions.
     *
     * @return array list of extensions
     * @throws Engine_Exception
     */

    public function get_extensions()
    {
        clearos_profile(__METHOD__, __LINE__);

        $folder = new Folder(self::PATH_EXTENSIONS);

        $list = $folder->get_listing();

        $extensions = array();

        foreach ($list as $extension) {
            if (! preg_match('/^\./', $extension))
                $extensions[] = $extension;
        }

        return $extensions;
    }

    /**
     * Returns list of plugins.
     *
     * @return array list of services
     * @throws Engine_Exception
     */

    public function get_plugins()
    {
        clearos_profile(__METHOD__, __LINE__);
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

        return self::CN_MASTER . ',' . self::SUFFIX_SERVERS . ',' . $this->get_base_dn();
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

        $ldap = new LDAP_Driver();

        return $ldap->get_mode();
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

        $ldap = new LDAP_Driver();

        return $ldap->get_modes();
    }

    /** 
     * Returns the container for password policies.
     *
     * @return string container for password policies
     * @throws Engine_Exception
     */

    public function get_password_policies_container()
    {
        clearos_profile(__METHOD__, __LINE__);

        return self::SUFFIX_PASSWORD_POLICIES . ',' . $this->get_base_dn();
    }


    /** 
     * Returns the container for servers.
     *
     * @return string container for servers.
     * @throws Engine_Exception
     */

    public function get_servers_container()
    {
        clearos_profile(__METHOD__, __LINE__);

        return self::SUFFIX_SERVERS . ',' . $this->get_base_dn();
    }

    /** 
     * Returns the container for users.
     *
     * @return string container for users.
     * @throws Engine_Exception
     */

    public function get_users_container()
    {
        clearos_profile(__METHOD__, __LINE__);

        return self::SUFFIX_USERS . ',' . $this->get_base_dn();
    }

    /**
     * Imports users, groups and computers from LDIF.
     *
     * @return void
     */

    public function import()
    {
        clearos_profile(__METHOD__, __LINE__);

        // TODO: split code from OpenLDAP driver

        $ldap = new LDAP_Driver();
        $ldap->import();
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

        $file = new File(self::FILE_INITIALIZED);

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

        // TODO: split code from OpenLDAP driver

        $ldap = new LDAP_Driver();
        $ldap->set_domain($domain, $background);
    }

    /**
     * Restarts the relevant daemons in a sane order.
     *
     * @return void
     */

    public function synchronize()
    {
        clearos_profile(__METHOD__, __LINE__);

        $ldap = new LDAP_Driver();
        $ldap->synchronize();

        $nslcd = new Nslcd();
        $nslcd->reset();
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

        $this->_initialize_authconfig();
        $this->_remove_overlaps();
        $this->synchronize();

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

        $nslcd = new Nslcd();
        $nslcd->set_boot_state(TRUE);

        $this->_set_initialized();
    }

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
     */

    protected function _set_initialized()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_INITIALIZED);

        if (! $file->exists())
            $file->create("root", "root", "0644");
    }
}
