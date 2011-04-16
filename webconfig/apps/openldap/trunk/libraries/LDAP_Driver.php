<?php

/**
 * OpenLDAP driver.
 *
 * @category   Apps
 * @package    OpenLDAP
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
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
clearos_load_language('ldap');
clearos_load_language('openldap');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Factories
//----------

use \clearos\apps\ldap\LDAP_Factory as LDAP;

clearos_load_library('ldap/LDAP_Factory');

// Classes
//--------

use \clearos\apps\base\Configuration_File as Configuration_File;
use \clearos\apps\base\Daemon as Daemon;
use \clearos\apps\base\Engine as Engine;
use \clearos\apps\base\File as File;
use \clearos\apps\base\Folder as Folder;
use \clearos\apps\base\Shell as Shell;
use \clearos\apps\ldap\LDAP_Client as LDAP_Client;
// use \clearos\apps\network\Hostname as Hostname;
use \clearos\apps\network\Network_Utils as Network_Utils;

clearos_load_library('base/Configuration_File');
clearos_load_library('base/Daemon');
clearos_load_library('base/Engine');
clearos_load_library('base/File');
clearos_load_library('base/Folder');
clearos_load_library('base/Shell');
clearos_load_library('ldap/LDAP_Client');
// clearos_load_library('network/Hostname');
clearos_load_library('network/Network_Utils');

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
 * OpenLDAP driver.
 *
 * @category   Apps
 * @package    OpenLDAP
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/openldap/
 */

class LDAP_Driver extends Daemon
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const CONSTANT_BASE_DB_NUM = 3;

// FIXME: move this to the "LDAP MANAGER" class
    // Modes
    const MODE_MASTER = 'master';
    const MODE_SLAVE = 'slave';
    const MODE_STANDALONE = 'standalone';

    // Policies
    const POLICY_LAN = 'lan';
    const POLICY_LOCALHOST = 'localhost';

    // Commands
    const COMMAND_LDAPSETUP = '/usr/sbin/ldapsetup';
    const COMMAND_LDAPSYNC = '/usr/sbin/ldapsync';
    const COMMAND_OPENSSL = '/usr/bin/openssl';
    const COMMAND_SLAPADD = '/usr/sbin/slapadd';
    const COMMAND_SLAPCAT = '/usr/sbin/slapcat';
    const COMMAND_SLAPPASSWD = '/usr/sbin/slappasswd';

    // Files and paths
    const FILE_CONFIG = '/var/clearos/openldap/config.php';
    const FILE_DATA = '/var/clearos/openldap/provision/provision.ldif';
    const FILE_DBCONFIG = '/var/lib/ldap/DB_CONFIG';
    const FILE_DBCONFIG_ACCESSLOG = '/var/lib/ldap/accesslog/DB_CONFIG';
    const FILE_INITIALIZED = '/var/clearos/openldap/initialized.php';
    const FILE_LDAP_CONFIG = '/etc/openldap/ldap.conf';
    const FILE_SLAPD_CONFIG = '/etc/openldap/slapd.conf';
    const FILE_SYSCONFIG = '/etc/sysconfig/ldap';
    const FILE_LDIF_BACKUP = '/etc/openldap/backup.ldif';
    const FILE_LDIF_NEW_DOMAIN = '/var/clearos/openldap/provision/newdomain.ldif';
    const FILE_LDIF_OLD_DOMAIN = '/var/clearos/openldap/provision/olddomain.ldif';
    const PATH_LDAP = '/var/lib/ldap';
    const PATH_LDAP_BACKUP = '/var/clearos/openldap/provision';
    const PATH_SYNCHRONIZE = '/var/clearos/openldap/synchronize';

    // Internal configuration
    const FILE_PROVISION_ACCESSLOG_DATA = 'deploy/provision/provision.accesslog.ldif';
    const FILE_PROVISION_DATA = 'deploy/provision/provision.ldif.template';
    const FILE_PROVISION_DBCONFIG = 'deploy/provision/DB_CONFIG.template';
    const FILE_PROVISION_LDAP_CONFIG = 'deploy/provision/ldap.conf.template';
    const FILE_PROVISION_SLAPD_CONFIG = 'deploy/provision/slapd.conf.template';
    const FILE_PROVISION_SLAPD_CONFIG_REPLICATE = 'deploy/provision/slapd-replicate.conf.template';

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $ldaph = NULL;
    protected $config = NULL;
    protected $modes = NULL;

    protected $file_provision_accesslog_data = NULL;
    protected $file_provision_data = NULL;
    protected $file_provision_dbconfig = NULL;
    protected $file_provision_ldap_config = NULL;
    protected $file_provision_slapd_config = NULL;
    protected $file_provision_slapd_config_replicate = NULL;

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Driver constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->modes = array(
            self::MODE_MASTER => lang('ldap_master'),
            self::MODE_SLAVE => lang('ldap_slave'),
            self::MODE_STANDALONE => lang('ldap_standalone')
        );

        $this->file_provision_accesslog_data = clearos_app_base('openldap') . '/' . self::FILE_PROVISION_ACCESSLOG_DATA;
        $this->file_provision_data = clearos_app_base('openldap') . '/' . self::FILE_PROVISION_DATA;
        $this->file_provision_dbconfig = clearos_app_base('openldap') . '/' . self::FILE_PROVISION_DBCONFIG;
        $this->file_provision_ldap_config = clearos_app_base('openldap') . '/' . self::FILE_PROVISION_LDAP_CONFIG;
        $this->file_provision_slapd_config = clearos_app_base('openldap') . '/' . self::FILE_PROVISION_SLAPD_CONFIG;
        $this->file_provision_slapd_config_replicate = clearos_app_base('openldap') . '/' . self::FILE_PROVISION_SLAPD_CONFIG_REPLICATE;

        parent::__construct('slapd');
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
                $this->ldaph = $this->get_ldap_handle();

            $was_running = $this->get_running_state();

            if ($was_running)
                $this->set_running_state(FALSE);

            $shell = new Shell();
            $shell->execute(self::COMMAND_SLAPCAT, "-n$dbnum -l " . $ldif, TRUE);

            if ($was_running)
                $this->set_running_state(TRUE);
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Generates a random password.
     *
     * @return string random password
     * @throws Engine_Exception
     */

    public function generate_password()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $shell = new Shell();
            $retval = $shell->execute(self::COMMAND_OPENSSL, 'rand -base64 12', FALSE);
            $output = $shell->get_first_output_line();

            // openssl can return with exit 0 on error, 
            if (($retval != 0) || preg_match('/\s+/', $output))
                throw new Engine_Exception($retval . " " . $output);
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e));
        }

        return $output;
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
            $this->ldaph = $this->get_ldap_handle();

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
     * Creates an LDAP connection handle.
     *
     * Many libraries that use OpenLDAP need to:
     *
     * - grab LDAP credentials for connecting to the server
     * - connect to LDAP
     * - perform a bunch of LDAP acctions (search, read, etc)
     *
     * This method provides a common method for doing the firt two steps.
     *
     * @return LDAP handle
     * @throws Engine_Exception
     */

    public function get_ldap_handle()
    {
        clearos_profile(__METHOD__, __LINE__);

        // FIXME: add security context
        $file = new Configuration_File(self::FILE_CONFIG, 'split', '=', 2);
        $config = $file->load();

        $ldaph = new LDAP_Client($config['base_dn'], $config['bind_dn'], $config['bind_pw']);

        return $ldaph;
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
     * - self::MODE_STANDALONE
     * - self::MODE_MASTER
     * - self::MODE_SLAVE
     *
     * @return string mode of the directory
     * @throws Engine_Exception
     */

    public function get_mode()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $file = new Configuration_File(self::FILE_CONFIG);
            $config = $file->load();
        } catch (File_Not_Found_Exception $e) {
            // Not fatal
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
        
        if (isset($config['mode'])) {
            if ($config['mode'] === self::MODE_MASTER)
                $mode = self::MODE_MASTER;
            else if ($config['mode'] === self::MODE_SLAVE)
                $mode = self::MODE_SLAVE;
            else if ($config['mode'] === self::MODE_STANDALONE)
                $mode = self::MODE_STANDALONE;
            else 
                $mode = self::MODE_STANDALONE;
        } else {
            $mode = self::MODE_STANDALONE;
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
            $password = $this->generate_password();

        $this->_initialize(self::MODE_MASTER, $domain, $password, $options);
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

        $this->_initialize(self::MODE_SLAVE, $domain, $password, $options);
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
            $password = $this->generate_password();

        $this->_initialize(self::MODE_STANDALONE, $domain, $password, $options);
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
            $this->ldaph = $this->get_ldap_handle();

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

        $password = $this->generate_password();

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
            $this->ldaph = $this->get_ldap_handle();

        $was_running = FALSE;

        try {
            // Grab hostname
            //--------------

            $hostnameobj = new Hostname();
            $hostname = $hostnameobj->Get();

            // Dump LDAP database to export file
            //----------------------------------

            $was_running = $this->get_running_state();
            $this->Export(self::FILE_LDIF_OLD_DOMAIN, self::CONSTANT_BASE_DB_NUM);

            // Load LDAP export file
            //----------------------

            $export = new File(self::FILE_LDIF_OLD_DOMAIN, TRUE);
            $exportlines = $export->GetContentsAsArray();

            // Load Kolab configuration file
            //------------------------------

// FIXME - FILE_KOLAB_CONFIG should not be here.
            $kolabconfig = new File(LDAP::FILE_KOLAB_CONFIG);
            $kolablines = $kolabconfig->GetContentsAsArray();

            // Load LDAP configuration
            //------------------------

            $ldapconfig = new File(self::FILE_SLAPD_CONFIG);
            $ldaplines = $ldapconfig->GetContentsAsArray();

            // Load LDAP information
            //----------------------

            $basedn = $this->ldaph->GetBaseDn();

        } catch (Exception $e) {
            if ($was_running)
                $this->set_running_state(TRUE);

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
            $newconfig = new File(LDAP::FILE_KOLAB_CONFIG, TRUE);

            if ($newconfig->Exists())
                $newconfig->Delete();

            $newconfig->Create('root', 'root', '0600');
            $newconfig->DumpContentsFromArray($newkolablines);

            // Import
            //-------

            $this->_import_ldif(self::FILE_LDIF_NEW_DOMAIN);

            // Perform Authconfig initialization in case LDAP has been manually initialized
            //-----------------------------------------------------------------------------

        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        try {
            // TODO: Should not need to explicitly call _CleanSecretsFile
            // FIXME: this shold not be here
            if (file_exists(COMMON_CORE_DIR . '/api/Samba.class.php')) {
                require_once('Samba.class.php');
                // $samba = new Samba();
                $samba->_CleanSecretsFile('');
            }

            // Tell other LDAP dependent apps to grab latest configuration
            // TODO: move this to a daemon
            if ($was_running)
                $this->synchronize(FALSE);

        } catch (Exception $e) {
            // Not fatal
        }
    }

    /** 
     * Sets security policy.
     *
     * The LDAP server can be configured to listen on:
     * -  localhost only: LDAP::POLICY_LOCALHOST
     * -  localhost and all LAN interfaces: LDAP::POLICY_LAN
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
     * Sends a synchronization signal to LDAP aware apps.
     *
     * @return void
     */

    public function synchronize()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->_synchronize_files();

// FIXME: do we still need a background?
//        $options['background'] = $background;
        $shell = new Shell();
        // FIXME
        // $shell->Execute(self::COMMAND_LDAPSYNC, "full", TRUE, $options);
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

        if (($policy !== self::POLICY_LOCALHOST) && ($policy !== self::POLICY_LAN))
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

        $force = isset($options['force']) ? $options['force'] : FALSE;
        $start = isset($options['start']) ? $options['start'] : TRUE;
        $master_hostname = isset($options['master_hostname']) ? $options['master_hostname'] : '';

        // Bail if LDAP is already initialized (and not a re-initialize)
        //--------------------------------------------------------------

        if ($this->is_initialized() && (!$force))
            return;

        // KLUDGE: shutdown Samba or it will try to write information to LDAP
        //-------------------------------------------------------------------

        $samba_list = array('smb', 'nmb', 'winbind');

        try {
            foreach ($samba_list as $daemon) {
                $samba = new Daemon($daemon);

                if ($samba->is_installed())
                    $samba->set_running_state(FALSE);
            }
        } catch (Exception $e) {
            // not fatail
        }

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

        // Set sane security policy
        //-------------------------

        if ($mode === self::MODE_SLAVE)
            $this->set_security_policy(self::POLICY_LAN);
        else
            $this->set_security_policy(self::POLICY_LOCALHOST);
       

        // Import the base LDIF data
        //--------------------------

        $this->_import_ldif(self::FILE_DATA);

        // Do some cleanup tasks
        //----------------------

        $this->_set_initialized();
        $this->_set_startup($start);
        $this->synchronize();
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
        $bind_dn = "cn=manager,ou=Internal,$base_dn";

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

        $file = new File(self::FILE_CONFIG);

        if ($file->exists())
            $file->delete();

        $file->create('root', 'root', '0644'); // FIXME: put permissions back to 0600
        $file->add_lines($config);

        // Create slapd.conf configuration
        //--------------------------------

        if ($mode === self::MODE_SLAVE)
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

        if ($mode === self::MODE_SLAVE)
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
            $this->ldaph = $this->get_ldap_handle();

        // Shutdown LDAP if running
        //-------------------------

        $was_running = $this->get_running_state();

        if ($was_running)
            $this->set_running_state(FALSE);

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
            $this->set_running_state(TRUE);
        }
    }

    /**
     * Sets initialized flag
     *
     */
    protected function _set_initialized()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_INITIALIZED);

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
            $this->ldaph = $this->get_ldap_handle();

        $this->set_boot_state(TRUE);

        /*
        FIXME
        $ldapsync = new Daemon("ldapsync");
        $ldapsync->set_boot_state(TRUE);
        */

        if ($start) {
            $this->restart();
            // FIXME $ldapsync->restart();
        }
    }

    /**
     * Synchronizes template files.
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */
    
    protected function _synchronize_files()
    {
        clearos_profile(__METHOD__, __LINE__);

        // Load directory configuration settings
        //--------------------------------------

        $config_file = new File(self::FILE_CONFIG);

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

        $folder = new Folder(self::PATH_SYNCHRONIZE);

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

            $file = new File(self::PATH_SYNCHRONIZE . '/' . $sync_file);
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
}
