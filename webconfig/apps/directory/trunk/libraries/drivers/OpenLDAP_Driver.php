<?php

/**
 * ClearOS LDAP directory class.
 *
 * @category   Apps
 * @package    Directory
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2006-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/directory/
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

namespace clearos\apps\directory;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('base');
clearos_load_language('directory');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Configuration_File as Configuration_File;
use \clearos\apps\base\Daemon as Daemon;
use \clearos\apps\base\Engine as Engine;
use \clearos\apps\base\File as File;
use \clearos\apps\base\Folder as Folder;
use \clearos\apps\base\Shell as Shell;
use \clearos\apps\date\NtpTime as NtpTime;
use \clearos\apps\directory\OpenLDAP as OpenLDAP;
use \clearos\apps\directory\Utilities as Utilities;
//use \clearos\apps\network\Hostname as Hostname;
use \clearos\apps\network\Network_Utils as Network_Utils;

clearos_load_library('base/Configuration_File');
clearos_load_library('base/Daemon');
clearos_load_library('base/Engine');
clearos_load_library('base/File');
clearos_load_library('base/Folder');
clearos_load_library('base/Shell');
clearos_load_library('date/NtpTime');
clearos_load_library('directory/OpenLDAP');
clearos_load_library('directory/Utilities');
// clearos_load_library('network/Hostname');
clearos_load_library('network/Network_Utils');

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\File_No_Match_Exception as File_No_Match_Exception;
use \clearos\apps\base\File_Not_Found_Exception as File_Not_Found_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('base/File_No_Match_Exception');
clearos_load_library('base/File_Not_Found_Exception');
clearos_load_library('base/Validation_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * ClearOS LDAP directory class.
 *
 * @category   Apps
 * @package    Directory
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2006-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/directory/
 */

class Directory extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const CONSTANT_BASE_DB_NUM = 3;
    const LOG_TAG = "directory";

    // Commands
    const COMMAND_OPENSSL = "/usr/bin/openssl";
    const COMMAND_SLAPADD = "/usr/sbin/slapadd";
    const COMMAND_LDAPSETUP = "/usr/sbin/ldapsetup";
    const COMMAND_AUTHCONFIG = "/usr/sbin/authconfig";
    const COMMAND_SLAPCAT = '/usr/sbin/slapcat';
    const COMMAND_SLAPPASSWD = "/usr/sbin/slappasswd";
    const COMMAND_LDAPSYNC = '/usr/sbin/ldapsync';


    // Files and paths
// FIXME: Review these -- moved from OpenLDAP class
    const FILE_LDIF_BACKUP = '/etc/openldap/backup.ldif';
    const FILE_SLAPD_CONFIG = '/etc/openldap/slapd.conf';
    const PATH_LDAP_BACKUP = '/usr/share/system/modules/ldap';

//    const FILE_CONFIG = '/usr/clearos/apps/directory/config/config.php';
    const FILE_CONFIG = '../config/config.php';
    const FILE_DATA = '/etc/openldap/provision.ldif';
    const FILE_DATA_PROVISION = '../config/provision/provision.ldif.template';
    const FILE_SLAPD = '/etc/openldap/slapd.conf';
    const FILE_SLAPD_PROVISION = '../config/provision/slapd.conf.template';
    const FILE_LDAP_CONFIG = '/etc/openldap/ldap.conf';
    const FILE_LDAP_CONFIG_PROVISION = '../config/provision/ldap.conf.template';
    const FILE_DBCONFIG = '/var/lib/ldap/DB_CONFIG';
    const FILE_DBCONFIG_PROVISION = '../config/provision/DB_CONFIG.template';
    const FILE_DBCONFIG_ACCESSLOG = '/var/lib/ldap/accesslog/DB_CONFIG';
    const FILE_DBCONFIG_ACCESSLOG_PROVISION = '../config/provision/DB_CONFIG.accesslog.template';

    const PATH_LDAP = '/var/lib/ldap';
    const FILE_ACCESSLOG_DATA = '/etc/openldap/provision/provision.accesslog.ldif';
    const FILE_LDIF_NEW_DOMAIN = '/etc/openldap/provision/newdomain.ldif';
    const FILE_LDIF_OLD_DOMAIN = '/etc/openldap/provision/olddomain.ldif';
    const FILE_SLAPD_REPLICATE_PROVISION = '/etc/openldap/provision/slapd-replicate.conf.template';
    const FILE_INITIALIZED = '/usr/clearos/apps/directory/config/initialized';
    const FILE_LDAP_EXISTS = '/var/lib/ldap/cn.bdb';
    const FILE_SSL_KEY = '/etc/openldap/cacerts/key.pem';
    const FILE_SSL_CERT = '/etc/openldap/cacerts/cert.pem';



    const PATH_KOLAB = '/etc/kolab';
    const FILE_KOLAB_CONFIG = '/etc/kolab/kolab.conf';
    const FILE_KOLAB_SETUP = '/etc/kolab/.kolab2_configured';

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

    // Directory modes
    const MODE_MASTER = 'master';
    const MODE_SLAVE = 'replicate';
    const MODE_STANDALONE = 'standalone';

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

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Directory constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

/*
FIXME: what uses this?
        $this->status_codes = array(
            Directory::STATUS_ALIAS_EXISTS => CLEARDIRECTORY_LANG_ALIAS_ALREADY_EXISTS,
            Directory::STATUS_GROUP_EXISTS => CLEARDIRECTORY_LANG_GROUP_ALREADY_EXISTS,
            Directory::STATUS_USERNAME_EXISTS => CLEARDIRECTORY_LANG_USERNAME_ALREADY_EXISTS
        );
*/

        $this->modes = array(
            Directory::MODE_MASTER => lang('directory_master'),
            Directory::MODE_SLAVE => lang('directory_slave'),
            Directory::MODE_STANDALONE => lang('directory_standalone')
        );
    }

    /**
     * Exports LDAP database to LDIF.
     *
     * @param string   $ldif  LDIF backup file
     * @param integere $dbnum database number
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function export($ldif = ldap::FILE_LDIF_BACKUP, $dbnum = self::CONSTANT_BASE_DB_NUM)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $export = new File($ldif, TRUE);

            if ($export->exists())
                $export->delete();

            if ($this->ldaph == NULL)
                $this->_get_ldap_handle();

            $wasrunning = $this->ldaph->get_running_state();

            if ($wasrunning)
                $this->ldaph->set_running_state(FALSE);

            $shell = new Shell();
            $shell->execute(self::COMMAND_SLAPCAT, "-n$dbnum -l " . $ldif, TRUE);

            if ($wasrunning)
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

        if ($this->ldaph == NULL)
            $this->_get_ldap_handle();

        try {
            $base_dn = $this->ldaph->get_base_dn();
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

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

        if ($this->ldaph == NULL)
            $this->_get_ldap_handle();

        try {
            $base_dn = $this->ldaph->get_base_dn();
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        $domain = preg_replace("/(,dc=)/", ".", $base_dn);
        $domain = preg_replace("/dc=/", "", $domain);

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

        return Directory::SUFFIX_COMPUTERS . ',' . $this->get_base_dn();
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

        return Directory::SUFFIX_GROUPS . ',' . $this->get_base_dn();
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
            $services[] = Directory::SERVICE_TYPE_SAMBA;

        if (file_exists(COMMON_CORE_DIR . "/api/Cyrus.class.php"))
            $services[] = Directory::SERVICE_TYPE_EMAIL;

        if (file_exists(COMMON_CORE_DIR . "/api/GoogleApps.class.php"))
            $services[] = Directory::SERVICE_TYPE_GOOGLE_APPS;

        if (file_exists(COMMON_CORE_DIR . "/api/Pptpd.class.php"))
            $services[] = Directory::SERVICE_TYPE_PPTP;

        if (file_exists(COMMON_CORE_DIR . "/api/OpenVpn.class.php"))
            $services[] = Directory::SERVICE_TYPE_OPENVPN;

        if (file_exists(COMMON_CORE_DIR . "/api/Squid.class.php"))
            $services[] = Directory::SERVICE_TYPE_PROXY;

        if (file_exists(COMMON_CORE_DIR . "/api/Proftpd.class.php"))
            $services[] = Directory::SERVICE_TYPE_FTP;

        if (file_exists(COMMON_CORE_DIR . "/api/Httpd.class.php"))
            $services[] = Directory::SERVICE_TYPE_WEB;

        if (file_exists(COMMON_CORE_DIR . "/iplex/Users.class.php"))
            $services[] = Directory::SERVICE_TYPE_PBX;

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
            $services[] = Directory::SERVICE_TYPE_SAMBA;

        $services[] = Directory::SERVICE_TYPE_EMAIL;
        $services[] = Directory::SERVICE_TYPE_GOOGLE_APPS;
        $services[] = Directory::SERVICE_TYPE_PPTP;
        $services[] = Directory::SERVICE_TYPE_OPENVPN;
        $services[] = Directory::SERVICE_TYPE_PROXY;
        $services[] = Directory::SERVICE_TYPE_FTP;
        $services[] = Directory::SERVICE_TYPE_WEB;

        return $services;
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
// FIXME: FILE_CONFIG is different now
            $file = new Configuration_File(self::FILE_CONFIG);
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
     * Returns the OU for password policies.
     *
     * @return string OU for password policies
     * @throws Engine_Exception
     */

    public function get_password_policies_ou()
    {
        clearos_profile(__METHOD__, __LINE__);

        return Directory::SUFFIX_PASSWORD_POLICIES . ',' . $this->get_base_dn();
    }

    /** 
     * Returns the OU for servers.
     *
     * @return string OU for servers.
     * @throws Engine_Exception
     */

    public function get_servers_ou()
    {
        clearos_profile(__METHOD__, __LINE__);

        return Directory::SUFFIX_SERVERS . ',' . $this->get_base_dn();
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

        return Directory::SUFFIX_USERS . ',' . $this->get_base_dn();
    }

    /**
     * Imports backup LDAP database from LDIF.
     *
     * @param boolean $background runs import in background if TRUE
     *
     * @return boolean TRUE if import file exists
     */

    public function import($background)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $import = new File(self::FILE_LDIF_BACKUP, TRUE);

            if (! $import->Exists())
                return FALSE;
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        $this->_import_ldif(self::FILE_LDIF_BACKUP, $background);

        return TRUE;
    }

    /**
     * Initializes the master LDAP database.
     *
     * @param string  $mode LDAP server mode
     * @param string  $domain domain name
     * @param string  $password bind DN password
     * @param boolean $background runs import in background if TRUE
     * @param boolean $start starts LDAP after initialization
     * @param boolean $force forces initialization even if LDAP server already has data
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function initialize($mode, $domain, $password = NULL, $background = FALSE, $start = TRUE, $force = FALSE, $master_hostname = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        // TODO: validate
        // TODO: mode
        // TODO: fix the method call -- too many parameters

        // Bail if LDAP is already initialized (and not a re-initialize)
        //--------------------------------------------------------------

        try {
            if (! $force) {
                $file = new File(self::FILE_INITIALIZED);
                if ($file->exists())
                    return;
            }
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        // Determine our hostname and generate an LDAP password (if required)
        //--------------------------------------------------------------

        try {
            // FIXME: hostname class is busted
            /*
            $hostnameinfo = new Hostname();
            $hostname = $hostnameinfo->Get();
            */
            $hostname = 'test.lan';

            if (empty($password))
                $password = Utilities::generate_password();
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        // Run our initialization subroutines
        //-----------------------------------

        if ($mode == Directory::MODE_SLAVE) {
            $this->_initialize_slave_configuration($domain, $password, $hostname, $master_hostname);
        } else {
            $this->_initialize_master_configuration($domain, $password, $hostname);
            $this->_import_ldif(Directory::FILE_DATA, $background);
        }

        $this->_initialize_authconfig();
        $this->_remove_overlaps();

        // The critical part is done, set flag to indicate LDAP initialization
        //--------------------------------------------------------------------

        $file = new File(self::FILE_INITIALIZED);

        if (! $file->exists())
            $file->create("root", "root", "0644");

        // Startup LDAP and set onboot flag
        //---------------------------------

        try {
            if ($this->ldaph == NULL)
                $this->_get_ldap_handle();

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
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        // Tell LDAP-related apps to synchronize with the latest LDAP
        //-----------------------------------------------------------

        /* FIXME
        $this->_synchronize($background);
        */
    }

    /**
     * Returns the availability of LDAP.
     *
     *
     * @return boolean TRUE if LDAP is running
     */

    public function is_available()
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->ldaph == NULL)
            $this->_get_ldap_handle();

        try {
            $available = $this->ldaph->is_available();
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

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

        try {
            $file = new File(self::FILE_INITIALIZED);
            if ($file->exists())
                return TRUE;
            else
                return FALSE;
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
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

        try {
            $options['stdin'] = TRUE;
            $options['background'] = TRUE;

            $password = Utilities::generate_password();

            $shell = new Shell();
            $shell->Execute(Directory::COMMAND_LDAPSETUP, "-r $mode -d $domain -p $password", TRUE, $options);
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
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

        if ($this->ldaph == NULL)
            $this->_get_ldap_handle();

        $wasrunning = FALSE;

        try {
            // Grab hostname
            //--------------

            $hostnameobj = new Hostname();
            $hostname = $hostnameobj->Get();

            // Dump LDAP database to export file
            //----------------------------------

            $wasrunning = $this->ldaph->get_running_state();
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

            $ldapconfig = new File(self::FILE_SLAPD_CONFIG);
            $ldaplines = $ldapconfig->GetContentsAsArray();

            // Load LDAP information
            //----------------------

            $basedn = $this->ldaph->GetBaseDn();

        } catch (Exception $e) {
            if ($wasrunning)
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

            $newldap = new File(self::FILE_SLAPD_CONFIG, TRUE);

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
            if ($wasrunning)
                $this->_synchronize(FALSE);

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
     *
     * @return integer Directory::STATUS_UNIQUE if unique
     */

    public function validate_id($id)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->ldaph == NULL)
            $this->_get_ldap_handle();

        // Check for duplicate user
        //-------------------------

        try {
            $result = $this->ldaph->Search(
                "(&(objectclass=inetOrgPerson)(uid=$id))",
                Directory::GetUsersOu(),
                array('dn')
            );

            $entry = $this->ldaph->GetFirstEntry($result);

            if ($entry)
                return Directory::STATUS_USERNAME_EXISTS;
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        // Check for duplicate alias
        //--------------------------

        try {
            $result = $this->ldaph->Search(
                "(&(objectclass=inetOrgPerson)(pcnMailAliases=$id))",
                Directory::GetUsersOu(),
                array('dn')
            );

            $entry = $this->ldaph->GetFirstEntry($result);

            if ($entry)
                return Directory::STATUS_ALIAS_EXISTS;
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
                Directory::GetGroupsOu(),
                array('dn')
            );

            $entry = $this->ldaph->GetFirstEntry($result);

            if ($entry)
                return Directory::STATUS_GROUP_EXISTS;
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        return Directory::STATUS_UNIQUE;
    }

    /**
     * Validates LDAP mode.
     *
     * @param string $mode LDAP mode
     *
     * @return boolean TRUE if mode is valid
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
     * @return boolean TRUE if password is valid
     */

    public function validate_password($password)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!empty($password) && (!preg_match('/[\|;]/', $password)))
            return TRUE;
        else
            return FALSE;
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E  M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Creates an LDAP handle.
     *
     * @access private
     * @return void
     * @throws Engine_Exception
     */

    protected function _get_ldap_handle()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $config_file = new Configuration_File(self::FILE_CONFIG, 'split', '=', 2);
            $config = $config_file->load();
        } catch (Exception $e) {
            throw new EngineException($e->getMessage(),COMMON_ERROR);
        }

        try {
            $this->ldaph = new OpenLDAP($config['base_dn'], $config['bind_dn'], $config['bind_pw']);
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Initializes authconfig.
     *
     * This method will update the nsswitch.conf and pam configuration.
     */

    protected function _initialize_authconfig()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $shell = new Shell();
            $shell->execute(Directory::COMMAND_AUTHCONFIG, '--enableshadow --enablemd5 --enableldap --enableldapauth --update', TRUE);
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
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

    protected function _initialize_master_configuration($domain, $password, $hostname)
    {
        clearos_profile(__METHOD__, __LINE__);

        // TODO: validate

        $base_dn = preg_replace('/\./', ',dc=', $domain);
        $base_dn = "dc=$base_dn";

        $base_dn_rdn = preg_replace('/,.*/', '', $base_dn);
        $base_dn_rdn = preg_replace('/dc=/', '', $base_dn_rdn);

        $bind_pw = $password;
        $bind_dn = "cn=manager,cn=internal,$base_dn";

        try {
            $shell = new Shell();

            $shell->execute(self::COMMAND_SLAPPASSWD, "-s $bind_pw");
            $bind_pw_hash = $shell->get_first_output_line();
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        // Directory configuration file
        //--------------------------

        $config = "mode = " . self::MODE_MASTER . "\n";
        $config .= "base_dn = $base_dn\n";
        $config .= "bind_dn = $bind_dn\n";
        $config .= "bind_pw = $bind_pw\n";
        $config .= "bind_pw_hash = $bind_pw_hash\n";

        try {
            $file = new File(self::FILE_CONFIG);

            if ($file->exists())
                $file->delete();

            $file->create('root', 'root', '0644'); // FIXME: put permissions back to 0600
            $file->add_lines($config);
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        // slapd.conf configuration
        //-----------------------------

        try {
            $file = new File(self::FILE_SLAPD_PROVISION);
            $contents = $file->get_contents();
            $contents = preg_replace("/\@\@\@base_dn\@\@\@/", $base_dn, $contents);
            $contents = preg_replace("/\@\@\@bind_dn\@\@\@/", $bind_dn, $contents);
            $contents = preg_replace("/\@\@\@bind_pw_hash\@\@\@/", $bind_pw_hash, $contents);
            $contents = preg_replace("/\@\@\@domain\@\@\@/", $domain, $contents);

            $newfile = new File(self::FILE_SLAPD);

            if ($newfile->exists())
                $newfile->delete();

            $newfile->create('root', 'ldap', '0640');
            $newfile->add_lines("$contents\n");
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        // ldap.conf configuration
        //-----------------------------

        try {
            $file = new File(self::FILE_LDAP_CONFIG_PROVISION);
            $contents = $file->get_contents();
            $contents = preg_replace("/\@\@\@base_dn\@\@\@/", $base_dn, $contents);
            $contents = preg_replace("/\@\@\@bind_dn\@\@\@/", $bind_dn, $contents);

            $newfile = new File(self::FILE_LDAP_CONFIG);

            if ($newfile->exists())
                $newfile->delete();

            $newfile->create('root', 'root', '0644');
            $newfile->add_lines("$contents\n");

        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        // DB_CONFIG configuration
        //-----------------------------

        try {
            $file = new File(self::FILE_DBCONFIG_PROVISION);
            $contents = $file->get_contents();

            $newfile = new File(self::FILE_DBCONFIG, TRUE);

            if ($newfile->exists())
                $newfile->delete();

            $newfile->create('ldap', 'ldap', '0644');
            $newfile->add_lines("$contents\n");

        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        // DB_CONFIG configuration for accesslog
        //--------------------------------------

        try {
            $file = new File(self::FILE_DBCONFIG_ACCESSLOG_PROVISION);
            $contents = $file->get_contents();

            $newfile = new File(self::FILE_DBCONFIG_ACCESSLOG, TRUE);

            if ($newfile->exists())
                $newfile->delete();

            $newfile->create('ldap', 'ldap', '0644');
            $newfile->add_lines("$contents\n");

        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        // LDAP provision data file
        //-----------------------------

        try {
            $file = new File(self::FILE_DATA_PROVISION);
            $contents = $file->get_contents();
            $contents = preg_replace("/\@\@\@base_dn\@\@\@/", $base_dn, $contents);
            $contents = preg_replace("/\@\@\@base_dn_rdn\@\@\@/", $base_dn_rdn, $contents);
            $contents = preg_replace("/\@\@\@bind_pw_hash\@\@\@/", $bind_pw_hash, $contents);

            $newfile = new File(self::FILE_DATA);

            if ($newfile->exists())
                $newfile->delete();

            $newfile->create('root', 'ldap', '0640');
            $newfile->add_lines("$contents\n");

        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Initializes LDAP replicate configuration.
     *
     * @param password $password LDAP master password
     * @param hostname $hostname hostname and IP of LDAP master
     * @param string $domain domain name
     * @throws Engine_Exception, Validation_Exception
     */

    protected function _initialize_slave_configuration($domain, $password, $hostname, $master_hostname)
    {
        clearos_profile(__METHOD__, __LINE__);

        // TODO: validate
        // TODO: merge with _initialize_master_configuration

        $base_dn = preg_replace("/\./", ",dc=", $domain);
        $base_dn = "dc=$base_dn";

        $base_dn_rdn = preg_replace("/,.*/", "", $base_dn);
        $base_dn_rdn = preg_replace("/dc=/", "", $base_dn_rdn);

        $bind_pw = $password;

        // Load up the required kolab.conf values
        //---------------------------------------

        try {
            $shell = new Shell();

            $shell->Execute(self::COMMAND_OPENSSL, "rand -base64 30");
            $php_pw = $shell->get_first_output_line();
            $shell->Execute(self::COMMAND_SLAPPASSWD, "-s $php_pw");
            $php_pw_hash = $shell->get_first_output_line();

            $shell->Execute(self::COMMAND_OPENSSL, "rand -base64 30");
            $calendar_pw = $shell->get_first_output_line();
            $shell->Execute(self::COMMAND_SLAPPASSWD, "-s $calendar_pw");
            $calendar_pw_hash = $shell->get_first_output_line();

            $shell->Execute(self::COMMAND_SLAPPASSWD, "-s $bind_pw");
            $bind_pw_hash = $shell->get_first_output_line();

            $bind_dn = "cn=manager,cn=internal,$base_dn";
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        $config = "fqdnhostname : $hostname\n";
        $config .= "is_master : FALSE\n";
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

            if ($file->exists())
                $file->Delete();

            $file->create("root", "root", "0600");
            $file->add_lines($config);
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
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

            if ($newfile->exists())
                $newfile->Delete();

            $newfile->create("root", "ldap", "0640");
            $newfile->add_lines("$contents\n");

        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        // ldap.conf configuration
        //-----------------------------

        try {
            $file = new File(self::FILE_LDAP_CONFIG_PROVISION);
            $contents = $file->GetContents();
            $contents = preg_replace("/\@\@\@base_dn\@\@\@/", $base_dn, $contents);
            $contents = preg_replace("/\@\@\@bind_dn\@\@\@/", $bind_dn, $contents);

            $newfile = new File(self::FILE_LDAP_CONFIG);

            if ($newfile->exists())
                $newfile->Delete();

            $newfile->create("root", "root", "0644");
            $newfile->add_lines("$contents\n");

        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Imports an LDIF file.
     *
     * @param string $ldif LDIF file
     * @param boolean $background runs import in background if TRUE
     * @throws Engine_Exception, Validation_Exception
     */

    protected function _import_ldif($ldif)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            if ($this->ldaph == NULL)
                $this->_get_ldap_handle();

            // FIXME
            // Logger::Syslog(self::LOG_TAG, "preparing LDAP import");

            // Shutdown LDAP if running
            //-------------------------

            $wasrunning = $this->ldaph->get_running_state();

            if ($wasrunning) {
                // FIXME
                // Logger::Syslog(self::LOG_TAG, "shutting down LDAP server");
                $this->ldaph->set_running_state(FALSE);
            }

            // Backup old LDAP
            //----------------

/*
FIXME: is this necessary?  Set in globals.php?
            $ntptime = new NtpTime();
            date_default_timezone_set($ntptime->GetTimeZone());
*/

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
                if (!preg_match("/DB_CONFIG$/", $filename)) {
                    $file = new File(self::PATH_LDAP . "/" . $filename, TRUE);
                    $file->delete();
                }
            }

            // Import new database
            //--------------------

            // FIXME
            // Logger::Syslog(self::LOG_TAG, "loading data into LDAP server");
            $shell = new Shell();
            $shell->execute(self::COMMAND_SLAPADD, "-n2 -l " . self::FILE_ACCESSLOG_DATA, TRUE);
            $shell->execute(self::COMMAND_SLAPADD, "-n3 -l " . $ldif, TRUE);

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

            if ($wasrunning) {
                // FIXME
                // Logger::Syslog(self::LOG_TAG, "restarting LDAP server");
                $this->ldaph->set_running_state(TRUE);
            }

        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
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

        try {
            $file = new File("/etc/group");
            $file->replace_lines("/^users:/", "");
            $file->replace_lines("/^domain_users:/", "");
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Restarts the relevant daemons in a sane order.
     *
     * @param boolean $background runs method in background if TRUE
     *
     * @return void
     */

    protected function _synchronize($background = TRUE)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $options['background'] = $background;
            $shell = new Shell();
            $shell->Execute(self::COMMAND_LDAPSYNC, "full", TRUE, $options);
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }
}
