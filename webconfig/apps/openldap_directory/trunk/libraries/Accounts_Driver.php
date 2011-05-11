<?php

/**
 * OpenLDAP accounts driver class.
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

// clearos_load_language('base');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\accounts\Accounts_Engine as Accounts_Engine;
use \clearos\apps\base\File as File;
use \clearos\apps\base\Folder as Folder;
use \clearos\apps\openldap\LDAP_Driver as LDAP_Driver;
use \clearos\apps\openldap_directory\Nslcd as Nslcd;
use \clearos\apps\openldap_directory\Utilities as Utilities;

clearos_load_library('accounts/Accounts_Engine');
clearos_load_library('base/File');
clearos_load_library('base/Folder');
clearos_load_library('openldap/LDAP_Driver');
clearos_load_library('openldap_directory/Nslcd');
clearos_load_library('openldap_directory/Utilities');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * OpenLDAP accounts driver class.
 *
 * @category   Apps
 * @package    OpenLDAP_Directory
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2006-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/openldap_directory/
 */

class Accounts_Driver extends Accounts_Engine
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
    protected $extensions = array();

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
     * OpenLDAP_Accounts constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Exports users, groups and computers.
     *
     * @throws Engine_Exception, Validation_Exception
     * @return string
     */

    public function export()
    {
        clearos_profile(__METHOD__, __LINE__);

        // TODO: move from userimport class
    }

    /**
     * Returns list of directory extensions.
     *
     * @return array extension list
     * @throws Engine_Exception
     */

    public function get_extensions()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! empty($this->extensions))
            return $this->extensions;

        $folder = new Folder(self::PATH_EXTENSIONS);

        $list = $folder->get_listing();

        foreach ($list as $extension_file) {
            if (preg_match('/\.php$/', $extension_file)) {
                $extension = array();
                include self::PATH_EXTENSIONS . '/' . $extension_file;
                $this->extensions[$extension['extension']] = $extension;
            }
        }

        return $this->extensions;
    }

    /**
     * Returns the mode of the accounts engine.
     *
     * The return values are:
     * - MODE_STANDALONE
     * - MODE_MASTER
     * - MODE_SLAVE
     *
     * @return string mode of the directory
     * @throws Engine_Exception
     */

    public function get_mode()
    {
        clearos_profile(__METHOD__, __LINE__);

        // TODO: review
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

        // TODO: review
        $ldap = new LDAP_Driver();

        return $ldap->get_modes();
    }

    /**
     * Imports users, groups and computers from LDIF.
     *
     * @return void
     */

    public function import()
    {
        clearos_profile(__METHOD__, __LINE__);

        // TODO: move from userimport class
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
}
