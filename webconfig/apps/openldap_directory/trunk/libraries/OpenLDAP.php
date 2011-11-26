<?php

/**
 * OpenLDAP accounts class.
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
clearos_load_language('openldap_directory');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\accounts\Accounts_Configuration as Accounts_Configuration;
use \clearos\apps\accounts\Nscd as Nscd;
use \clearos\apps\accounts\Nslcd as Nslcd;
use \clearos\apps\base\Engine as Engine;
use \clearos\apps\base\File as File;
use \clearos\apps\base\Shell as Shell;
use \clearos\apps\mode\Mode_Engine as Mode_Engine;
use \clearos\apps\mode\Mode_Factory as Mode_Factory;
use \clearos\apps\openldap\LDAP_Driver as LDAP_Driver;
use \clearos\apps\openldap_directory\User_Driver as User_Driver;
use \clearos\apps\openldap_directory\Utilities as Utilities;

clearos_load_library('accounts/Accounts_Configuration');
clearos_load_library('accounts/Nscd');
clearos_load_library('accounts/Nslcd');
clearos_load_library('base/Engine');
clearos_load_library('base/File');
clearos_load_library('base/Shell');
clearos_load_library('mode/Mode_Engine');
clearos_load_library('mode/Mode_Factory');
clearos_load_library('openldap/LDAP_Driver');
clearos_load_library('openldap_directory/User_Driver');
clearos_load_library('openldap_directory/Utilities');

// Exceptions
//-----------

use \Exception as Exception;
use \clearos\apps\accounts\Accounts_Driver_Not_Set_Exception as Accounts_Driver_Not_Set_Exception;
use \clearos\apps\base\Engine_Exception as Engine_Exception;

clearos_load_library('accounts/Accounts_Driver_Not_Set_Exception');
clearos_load_library('base/Engine_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * OpenLDAP accounts class.
 *
 * @category   Apps
 * @package    OpenLDAP_Directory
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2006-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/openldap_directory/
 */

class OpenLDAP extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    // General
    const DEFAULT_DOMAIN = 'system.lan';
    const CONSTANT_BASE_DB_NUM = 3;

    // Paths
    const COMMAND_AUTHCONFIG = '/usr/sbin/authconfig';
    const COMMAND_SLAPCAT = '/usr/sbin/slapcat';
    const FILE_INITIALIZING = '/var/clearos/openldap_directory/initializing';
    const FILE_LDIF_BACKUP = '/etc/openldap/backup.ldif';
    const PATH_LDAP_BACKUP = '/var/clearos/openldap_directory/backup/';
    const PATH_LDAP = '/var/lib/ldap';

    // Containers
    const SUFFIX_COMPUTERS = 'ou=Computers,ou=Accounts';
    const SUFFIX_GROUPS = 'ou=Groups,ou=Accounts';
    const SUFFIX_SERVERS = 'ou=Servers';
    const SUFFIX_USERS = 'ou=Users,ou=Accounts';
    const SUFFIX_PASSWORD_POLICIES = 'ou=PasswordPolicies,ou=Accounts';
    const OU_PASSWORD_POLICIES = 'PasswordPolicies';
    const CN_MASTER = 'cn=Master';
    const DRIVER_NAME = 'openldap_directory';

    // Status codes for username/group/alias uniqueness
    const STATUS_ALIAS_EXISTS = 'alias';
    const STATUS_GROUP_EXISTS = 'group';
    const STATUS_USERNAME_EXISTS = 'user';
    const STATUS_UNIQUE = 'unique';

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $ldaph = NULL;

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * OpenLDAP_Driver constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Check for overlapping usernames, groups and aliases in the directory.
     *
     * @param string $id username, group or alias
     *
     * @return string warning type if ID is not unique
     */

    public function check_uniqueness($id)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->ldaph === NULL)
            $this->ldaph = Utilities::get_ldap_handle();

        // Check for duplicate user
        //-------------------------

        $result = $this->ldaph->search(
            "(&(objectclass=inetOrgPerson)(uid=$id))",
            self::get_users_container(),
            array('dn')
        );

        $entry = $this->ldaph->get_first_entry($result);

        if ($entry)
            return self::STATUS_USERNAME_EXISTS;

        // Check for duplicate alias
        //--------------------------

        $result = $this->ldaph->Search(
            "(&(objectclass=inetOrgPerson)(clearMailAliases=$id))",
            self::get_users_container(),
            array('dn')
        );

        $entry = $this->ldaph->get_first_entry($result);

        if ($entry)
            return self::STATUS_ALIAS_EXISTS;

        // Check for duplicate group
        //--------------------------
    
        // The "displayName" is used in Samba group mapping.  In other words,
        // the "displayName" is what is used by Windows networking (not the cn).

        $result = $this->ldaph->Search(
            "(&(objectclass=posixGroup)(|(cn=$id)(displayName=$id)))",
            self::get_groups_container(),
            array('dn')
        );

        $entry = $this->ldaph->get_first_entry($result);

        if ($entry)
            return self::STATUS_GROUP_EXISTS;

        // TODO: Flexshares?  How do we deal with this in master/replica mode?

        return self::STATUS_UNIQUE;
    }

    /**
     * Check for overlapping usernames, groups and aliases in the directory.
     *
     * @param string $id username, group or alias
     *
     * @return string warning message if ID is not unique
     */

    public function check_uniqueness_message($id)
    {
        clearos_profile(__METHOD__, __LINE__);

        $status = $this->check_uniqueness($id);

        if ($status === self::STATUS_USERNAME_EXISTS)
            return lang('openldap_directory_username_with_this_name_exists');
        else if ($status === self::STATUS_ALIAS_EXISTS)
            return lang('openldap_directory_alias_with_this_name_exists');
        else if ($status === self::STATUS_GROUP_EXISTS)
            return lang('openldap_directory_group_with_this_name_exists');
        else
            return '';
    }

    /** 
     * Returns the base DN.
     *
     * @return string base DN
     * @throws Engine_Exception
     */

    public static function get_base_dn()
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

    public static function get_base_internet_domain()
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

    public static function get_computers_container()
    {
        clearos_profile(__METHOD__, __LINE__);

        return self::SUFFIX_COMPUTERS . ',' . self::get_base_dn();
    }

    /** 
     * Returns the ontainer for groups.
     *
     * @return string container for groups
     * @throws Engine_Exception
     */

    public static function get_groups_container()
    {
        clearos_profile(__METHOD__, __LINE__);

        return self::SUFFIX_GROUPS . ',' . self::get_base_dn();
    }

    /**
     * Returns the DN of the master server 
     *
     * @return string DN of the master server
     * @throws Engine_Exception
     */

    public static function get_master_dn()
    {
        clearos_profile(__METHOD__, __LINE__);

        return self::CN_MASTER . ',' . self::SUFFIX_SERVERS . ',' . self::get_base_dn();
    }

    /** 
     * Returns the container for password policies.
     *
     * @return string container for password policies
     * @throws Engine_Exception
     */

    public static function get_password_policies_container()
    {
        clearos_profile(__METHOD__, __LINE__);

        return self::SUFFIX_PASSWORD_POLICIES . ',' . self::get_base_dn();
    }

    /** 
     * Returns the container for servers.
     *
     * @return string container for servers.
     * @throws Engine_Exception
     */

    public static function get_servers_container()
    {
        clearos_profile(__METHOD__, __LINE__);

        return self::SUFFIX_SERVERS . ',' . self::get_base_dn();
    }

    /** 
     * Returns the container for users.
     *
     * @return string container for users.
     * @throws Engine_Exception
     */

    public static function get_users_container()
    {
        clearos_profile(__METHOD__, __LINE__);

        return self::SUFFIX_USERS . ',' . self::get_base_dn();
    }

    /**
     * Returns the initialization status.
     *
     * @return boolean TRUE if initialized
     * @throws Engine_Exception
     */
    public function is_initialized()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $accounts = new Accounts_Configuration();
            $accounts->get_driver();
        } catch (Accounts_Driver_Not_Set_Exception $e) {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Imports backup LDAP database from LDIF.
     *
     * @return boolean true if import file exists
     */

    public function import()
    {
        clearos_profile(__METHOD__, __LINE__);

        $import = new File(self::FILE_LDIF_BACKUP, true);

        if (! $import->exists())
            return FALSE;

        $this->_import_ldif(self::FILE_LDIF_BACKUP);

        return TRUE;
    }

    /**
     * Initializes the OpenLDAP accounts system.
     *
     * @param string  $domain base domain
     * @param boolean $force nforces initialization if TRUE
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function initialize($domain = self::DEFAULT_DOMAIN, $force = FALSE)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (($this->is_initialized() && !$force))
            return;

        // Set initializing
        //-----------------

        $file = new File(self::FILE_INITIALIZING);

        if (! $file->exists())
            $file->create('root', 'root', '0644');

        // Go through initalization process
        //---------------------------------

        try {
            // Set driver so status information knows where to look
            //-----------------------------------------------------

            $this->_set_driver();
            $ldap = new LDAP_Driver();

            // Initialize LDAP with appropriate mode
            //--------------------------------------

            $sysmode = Mode_Factory::create();
            $mode = $sysmode->get_mode();

            // FIXME: add slave mode
            if ($mode === Mode_Engine::MODE_MASTER)
                $ldap->initialize_master($domain, NULL, $force);
            else if ($mode === Mode_Engine::MODE_STANDALONE)
                $ldap->initialize_standalone($domain, NULL, $force);

            // Post LDAP tasks
            //----------------

            $this->_initialize_authconfig();
            $this->_remove_overlaps();
            $this->_initialize_caching();

            // FIXME: add Samba here for now
            if (clearos_load_library('samba/OpenLDAP_Driver')) {
                $samba_driver = new \clearos\apps\samba\OpenLDAP_Driver();
                $samba_driver->initialize_master_system('CLEARSYSTEM', NULL, $force);

                clearos_load_library('samba/Winbind');
                $winbind = new \clearos\apps\samba\Winbind();
                $winbind->set_boot_state(TRUE);
                $winbind->set_running_state(TRUE);
            }
        } catch (Exception $e) {
            $file->delete();
            throw new Engine_Exception(clearos_exception_message($e));
        }

        $file->delete();
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validates ID.
     *
     * @param string $id ID
     *
     * @return string error message if ID is invalid
     */

    public function validate_id($id)
    {
        clearos_profile(__METHOD__, __LINE__);

        // TODO
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E  M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Imports an LDIF file.
     *
     * @param string $ldif LDIF file
     *
     * @access private
     * @return void
     * @throws EngineException, ValidationException
     */

    protected function _import_ldif($ldif)
    {
        clearos_profile(__METHOD__, __LINE__);

        $ldap = new LDAP_Driver();

        clearos_log('openldap_directory', lang('openldap_directory_preparing_import'));

        // Shutdown LDAP if running
        //-------------------------

        $was_running = $ldap->get_running_state();

        if ($was_running) {
            clearos_log('openldap_directory', lang('openldap_directory_shutting_down_ldap_server'));
            $ldap->set_running_state(FALSE);
        }

        // Backup old LDAP
        //----------------

        $filename = self::PATH_LDAP_BACKUP . '/' . "backup-" . strftime("%m-%d-%Y-%H-%M-%S", time()) . ".ldif";
        $this->export($filename);

        // Clear out old database
        //-----------------------

        $folder = new Folder(self::PATH_LDAP);

        $file_list = $folder->GetRecursiveListing();

        foreach ($file_list as $filename) {
            if (!preg_match('/DB_CONFIG$/', $filename)) {
                $file = new File(self::PATH_LDAP . '/' . $filename, TRUE);
                $file->delete();
            }
        }

        // Import new database
        //--------------------

        clearos_log('openldap_directory', lang('openldap_directory_importing_data'));

        $shell = new Shell();
        $shell->Execute(self::COMMAND_SLAPADD, '-n2 -l ' . self::FILE_ACCESSLOG_DATA, TRUE);
        $shell->Execute(self::COMMAND_SLAPADD, '-n3 -l ' . $ldif, true);
    }

    /**
     * Initializes authconfig.
     *
     * This method will update the nsswitch.conf and pam configuration.
     */

    protected function _initialize_authconfig()
    {
        clearos_profile(__METHOD__, __LINE__);

        $shell = new Shell();
        $shell->execute(self::COMMAND_AUTHCONFIG, 
            '--enableshadow --passalgo=sha512 ' .
            '--enablecache --enablelocauthorize --enablemkhomedir ' .
            '--disablewinbind --disablewinbindauth ' .
            '--enableldap --enableldapauth --disablefingerprint --update', 
            TRUE
        );

        // TODO: the authconfig command seems to break the bind_dn in places (?)
        // Use the synchronize routine as a workaround.

        $ldap = new LDAP_Driver();
        $ldap->synchronize();
    }

    /**
     * Initializes authentication caching.
     *
     * @return void
     * @throws Engine_Exception
     */

    protected function _initialize_caching()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $nslcd = new Nslcd();
            $nslcd->set_boot_state(TRUE);

            if ($nslcd->get_running_state())
                $nslcd->reset(TRUE);
            else
                $nslcd->set_running_state(TRUE);

            $nscd = new Nscd();
            $nscd->set_boot_state(TRUE);

            if ($nscd->get_running_state())
                $nscd->reset(TRUE);
            else
                $nscd->set_running_state(TRUE);

        } catch (Engine_Exception $e) {
            // Not fatal
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

        // TODO: move to a separate class in the base app, cleanup
        $file = new File('/etc/group');
        $file->replace_lines('/^users:/', '');

        $file = new File('/etc/default/useradd');
        $file->replace_lines('/^GROUP=/', "GROUP=" . User_Driver::DEFAULT_USER_GROUP_ID . "\n");
    }

    /**
     * Sets driver.
     */

    protected function _set_driver()
    {
        clearos_profile(__METHOD__, __LINE__);

        $accounts = new Accounts_Configuration();
        $accounts->set_driver(self::DRIVER_NAME);
    }
}
