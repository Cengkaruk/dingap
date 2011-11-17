<?php

/**
 * Samba server.
 *
 * @category   Apps
 * @package    Samba
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/samba/
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

namespace clearos\apps\samba;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('samba');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Factories
//----------

use \clearos\apps\users\User_Factory as User;

clearos_load_library('users/User_Factory');

// Classes
//--------

use \clearos\apps\base\File as File;
use \clearos\apps\base\File_No_Match_Exception as File_No_Match_Exception;
use \clearos\apps\base\Shell as Shell;
use \clearos\apps\base\Software as Software;
use \clearos\apps\network\Hostname as Hostname;
use \clearos\apps\network\Network_Utils as Network_Utils;
use \clearos\apps\samba\Nmbd as Nmbd;
use \clearos\apps\samba\OpenLDAP_Driver as OpenLDAP_Driver;
use \clearos\apps\samba\Samba as Samba;
use \clearos\apps\samba\Smbd as Smbd;
use \clearos\apps\samba\Winbind as Winbind;

clearos_load_library('base/File');
clearos_load_library('base/File_No_Match_Exception');
clearos_load_library('base/Shell');
clearos_load_library('base/Software');
clearos_load_library('network/Hostname');
clearos_load_library('network/Network_Utils');
clearos_load_library('samba/Nmbd');
clearos_load_library('samba/OpenLDAP_Driver');
clearos_load_library('samba/Samba');
clearos_load_library('samba/Smbd');
clearos_load_library('samba/Winbind');

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;
use \clearos\apps\samba\Samba_Connection_Exception as Samba_Connection_Exception;
use \clearos\apps\samba\Samba_Not_Initialized_Exception as Samba_Not_Initialized_Exception;
use \clearos\apps\samba\Samba_Share_Not_Found_Exception as Samba_Share_Not_Found_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('base/Validation_Exception');
clearos_load_library('samba/Samba_Connection_Exception');
clearos_load_library('samba/Samba_Not_Initialized_Exception');
clearos_load_library('samba/Samba_Share_Not_Found_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Samba server.
 *
 * @category   Apps
 * @package    Samba
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/samba/
 */

class Samba extends Software
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    // Files and paths
    const FILE_CONFIG = '/etc/samba/smb.conf';
    const FILE_DOMAIN_SID = '/etc/samba/domainsid';
    const FILE_LOCAL_SID = '/etc/samba/localsid';
    const FILE_LOCAL_SYSTEM_INITIALIZED = '/etc/system/initialized/sambalocal';
    const FILE_DOMAIN_SID_CACHE = '/var/clearos/samba/domain_sid_cache';
    const PATH_STATE = '/var/lib/samba';
    const PATH_STATE_BACKUP = '/var/lib/samba';

    // Commands
    const COMMAND_NET = '/usr/bin/net';
    const COMMAND_SMBPASSWD = '/usr/bin/smbpasswd';
    const COMMAND_ADD_SAMBA_DIRS = '/usr/sbin/add-samba-directories';

    // Modes
    const MODE_PDC = 'pdc';
    const MODE_BDC = 'bdc';
    const MODE_MEMBER = 'member';
    const MODE_SIMPLE_SERVER = 'simple';
    const MODE_CUSTOM = 'custom';

    // General
    const PRINTING_DISABLED = 'disabled';
    const PRINTING_POINT_AND_CLICK = 'pointnclick';
    const PRINTING_RAW = 'raw';

    // Security settings
    const SECURITY_ADS = 'ads';
    const SECURITY_USER = 'user';
    const SECURITY_SHARE = 'share';
    const SECURITY_DOMAIN = 'domain';
    
    // SID types 
    const TYPE_SID_DOMAIN = 'domain';
    const TYPE_SID_LOCAL = 'local';

    // General
    const CONSTANT_NULL_LINE = -1;
    const CONSTANT_ENABLED = 'Yes';
    const CONSTANT_DISABLED = 'No';
    const CONSTANT_DEFAULT = 'default';
    const CONSTANT_DOMAIN_USERS_RID = '513';
    const CONSTANT_WINADMIN_CN = 'Windows Administrator';
    const CONSTANT_WINADMIN_USERNAME = 'winadmin';
    const CONSTANT_GUEST_CN = 'Guest Account';
    const CONSTANT_GID_DOMAIN_COMPUTERS = '1000515';
    const CONSTANT_DOMAIN_SID_CACHE_TIME = 120;

    // Default configuration values
    const DEFAULT_PASSWORD_CHAT = '*password:* %n\n *password:* %n\n *successfully.*';
    const DEFAULT_PASSWORD_PROGRAM = '/usr/sbin/userpasswd %u';
    const DEFAULT_ADD_MACHINE_SCRIPT = '/usr/sbin/samba-add-machine "%u"';
    const DEFAULT_OS_LEVEL = '20';
    const DEFAULT_ADMIN_PRIVS = 'SeMachineAccountPrivilege SePrintOperatorPrivilege SeAddUsersPrivilege SeDiskOperatorPrivilege SeMachineAccountPrivilege SeTakeOwnershipPrivilege';

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $loaded = FALSE;
    protected $shares = array();
    protected $values = array();
    protected $booleans = array();
    protected $modes = array();
    protected $raw_lines = array();

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Samba constructor.
     *
     * @return void
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        parent::__construct('samba-common');

        $this->modes = array(
            self::MODE_PDC => lang('samba_pdc'),
            self::MODE_BDC => lang('samba_bdc'),
            self::MODE_MEMBER => lang('samba_member_server'),
            self::MODE_SIMPLE_SERVER => lang('samba_simple_server'),
        );

        $this->booleans = array(
            'use client driver',
            'printable',
            'read only',
            'browseable',
            'available',
            'special'
        );
    }

    /**
     * Returns domain logons state.
     *
     * @return boolean TRUE if domain logons is enabled
     * @throws Engine_Exception
     */

    public function get_domain_logons()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->loaded)
            $this->_load();

        $value = $this->_get_boolean($this->shares['global']['domain logons']['value']);

        if ($value === self::CONSTANT_ENABLED)
            return TRUE;
        else
            return FALSE;
    }

    /**
     * Returns domain master state.
     *
     * @return boolean TRUE if domain master is enabled
     * @throws Engine_Exception
     */

    public function get_domain_master()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->loaded)
            $this->_load();

        $value = $this->_get_boolean($this->shares['global']['domain master']['value']);

        if ($value === self::CONSTANT_ENABLED) {
            return TRUE;
        } else if ($value === self::CONSTANT_DEFAULT) {
            // From smb.conf man page:
            // If domain logons = yes, then the default behavior is to enable the domain master parameter. If domain
            // logons is not enabled (the default setting), then neither will domain master be enabled by default.
            return $this->get_domain_logons();
        } else {
            return FALSE;
        }
    }

    /**
     * Returns domain SID.
     *
     * @return string domain SID
     * @throws Engine_Exception, Samba_Not_Initialized_Exception
     */

    public function get_domain_sid()
    {
        clearos_profile(__METHOD__, __LINE__);

        // Using the net command is expensive, so cache this data
        //-------------------------------------------------------

        clearstatcache();

        $file = new File(self::FILE_DOMAIN_SID_CACHE, FALSE);

        if ($file->exists()) {
            $stat = stat(self::FILE_DOMAIN_SID_CACHE);
            $cache_time = time() - $stat['ctime'];

            if ($cache_time < self::CONSTANT_DOMAIN_SID_CACHE_TIME) {
                $sid = $file->get_contents();
                return $sid;
            }

            $file->delete();
        }

        // Get SID from net command
        //-------------------------

        $shell = new Shell();
        $shell->execute(self::COMMAND_NET, 'getdomainsid', TRUE);

        $raw_output = $shell->get_output();

        $sid = '';

        // KLUDGE: parsing command line output
        foreach ($raw_output as $line) {
            if (preg_match('/SID for domain/', $line)) {
                $sid = preg_replace('/.*:\s*/', '', $line);
                break;
            }
        }

        // Cache SID
        //----------

        $file = new File(self::FILE_DOMAIN_SID_CACHE, TRUE);

        if ($file->exists())
            $file->delete();

        $file->create('root', 'webconfig', '0644');  // FIXME: change to 640 post-testing
        $file->add_lines("$sid\n");

        return $sid;
    }

    /**
     * Returns listening network interfaces.
     *
     * @return array list of network interfaces
     * @throws Engine_Exception
     */

    public function get_interfaces()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->loaded)
            $this->_load();

        return $this->shares['global']['interfaces']['value'];
    }

    /**
     * Returns local master state.
     *
     * @return boolean local master state
     * @throws Engine_Exception
     */

    public function get_local_master()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->loaded)
            $this->_load();

        $value = $this->_get_boolean($this->shares['global']['local master']['value']);

        if (($value === self::CONSTANT_ENABLED) || ($value === self::CONSTANT_DEFAULT))
            return TRUE;
        else
            return FALSE;
    }

    /**
     * Returns local SID.
     *
     * @return string local SID
     * @throws Engine_Exception
     */

    public function get_local_sid()
    {
        clearos_profile(__METHOD__, __LINE__);

        $shell = new Shell();

        $shell->execute(self::COMMAND_NET, 'getlocalsid', TRUE);

        $sid = $shell->get_last_output_line();
        $sid = preg_replace("/.*: /", "", $sid);

        return $sid;
    }

    /**
     * Returns logon drive.
     *
     * @return string logon drive
     * @throws Engine_Exception
     */

    public function get_logon_drive()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->loaded)
            $this->_load();

        if (empty($this->shares['global']['logon drive']['value']))
            return '';
        else
            return $this->shares['global']['logon drive']['value'];
    }

    /**
     * Returns logon home.
     *
     * @return string logon home
     * @throws Engine_Exception
     */

    public function get_logon_home()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->loaded)
            $this->_load();

        if (empty($this->shares['global']['logon home']['value']))
            return "";
        else
            return $this->shares['global']['logon home']['value'];
    }

    /**
     * Returns logon path.
     *
     * @return string logon path
     * @throws Engine_Exception
     */

    public function get_logon_path()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->loaded)
            $this->_load();

        if (empty($this->shares['global']['logon path']['value']))
            return "";
        else
            return $this->shares['global']['logon path']['value'];
    }

    /**
     * Returns logon script.
     *
     * @return string logon script
     * @throws Engine_Exception
     */

    public function get_logon_script()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->loaded)
            $this->_load();

        if (empty($this->shares['global']['logon script']['value']))
            return "";
        else
            return $this->shares['global']['logon script']['value'];
    }

    /**
     * Gets available default modes.
     *
     * The default modes are described as follows.
     *
     *                  +  PDC  +  BDC  +  Simple
     * Preferred Master |   y   | auto  |    y
     *    Domain Master |   y   |   n   |    y
     *    Domain Logons |   y   |   y   |    n
     *       [netlogon] |   y   |   n   |    n
     *
     * @return string mode
     * @throws Engine_Exception
     */

    public function get_mode()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $domain_logons = $this->get_domain_logons();
            $preferred_master = $this->get_preferred_master();
            $netlogon_info = $this->get_share_info('netlogon');
        } catch (Samba_Share_Not_Found_Exception $e) {
            // Not fatal
        }

        $netlogon = (isset($netlogon_info)) ? $netlogon_info['available'] : FALSE;

        if ($preferred_master && $domain_logons && $netlogon)
            return self::MODE_PDC;
        else if (!$preferred_master && !$domain_logons && !$netlogon)
            return self::MODE_BDC;
        else if ($preferred_master && $domain_logons && !$netlogon)
            return self::MODE_SIMPLE_SERVER;
        else
            return self::MODE_CUSTOM;
    }

    /**
     * Returns a list of modes
     *
     * @return array mode information
     * @throws Engine_Exception
     */

    public function get_modes()
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->modes;
    }

    /**
     * Gets system/netbios name.
     *
     * @return string system name
     * @throws Engine_Exception
     */

    public function get_netbios_name()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->loaded)
            $this->_load();

        return $this->shares['global']['netbios name']['value'];
    }

    /**
     * Gets OS level.
     *
     * @return integer OS level
     * @throws Engine_Exception
     */

    public function get_os_level()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->loaded)
            $this->_load();

        if (empty($this->shares['global']['os level']['value']))
            return Samba::DEFAULT_OS_LEVEL;
        else
            return $this->shares['global']['os level']['value'];
    }

    /**
     * Gets password program.
     *
     * @return string password program
     * @throws Engine_Exception
     */

    public function get_password_chat()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->loaded)
            $this->_load();

        if (empty($this->shares['global']['passwd chat']['value']))
            return "";
        else
            return $this->shares['global']['passwd chat']['value'];
    }

    /**
     * Gets password program.
     *
     * @return string password program
     * @throws Engine_Exception
     */

    public function get_password_program()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->loaded)
            $this->_load();

        if (empty($this->shares['global']['passwd program']['value']))
            return "";
        else
            return $this->shares['global']['passwd program']['value'];
    }

    /**
     * Returns password server list.
     *
     * @return array list of password servers
     * @throws Engine_Exception
     */

    public function get_password_servers()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->loaded)
            $this->_load();

        $raw_value = $this->shares['global']['password server']['value'];

        $raw_value = preg_replace('/,/', ' ', $raw_value);
        $servers = preg_split('/\s+/', $raw_value);

        return $servers;
    }

    /**
     * Gets preferred master setting.
     *
     * @return boolean TRUE preferred master is enable
     * @throws Engine_Exception
     */

    public function get_preferred_master()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->loaded)
            $this->_load();

        $value = $this->_get_boolean($this->shares['global']['preferred master']['value']);

        if ($value === self::CONSTANT_ENABLED) {
            return TRUE;
        } else if ($value === self::CONSTANT_DEFAULT) {
            // TODO: man page is unclear about the default behavior
            return FALSE;
        } else {
            return FALSE;
        }
    }

    /**
     * Returns printing share information.
     *
     * @return array information about printers
     * @throws Engine_Exception
     */

    public function get_printing_info()
    {
        clearos_profile(__METHOD__, __LINE__);

        $info = array();

        if ($this->share_exists('printers')) {
            $info['printers'] = $this->get_share_info('printers');
            $info['enabled'] = (isset($info['printers']['available']) && $info['printers']['available']) ? TRUE : FALSE;
        } else {
            $info['enabled'] = FALSE;
        }

        if ($this->share_exists("print$"))
            $info['print$'] = $this->get_share_info("print$");

        return $info;
    }

    /**
     * Returns realm.
     *
     * @return string realm
     * @throws Engine_Exception
     */

    public function get_realm()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->loaded)
            $this->_load();

        return $this->shares['global']['realm']['value'];
    }

    /**
     * Returns roaming profiles state.
     *
     * @return boolean state of roaming profiles
     * @throws Engine_Exception
     */

    public function get_roaming_profiles_state()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $info = $this->get_share_info("profiles");
        } catch (Samba_Share_Not_Found_Exception $e) {
            return FALSE;
        }

        if (! isset($info['available']) || $info['available'])
            return TRUE;
        else
            return FALSE;
    }

    /**
     * Returns security type.
     *
     * @return string security type
     * @throws Engine_Exception
     */

    public function get_security()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->loaded)
            $this->_load();

        return $this->shares['global']['security']['value'];
    }

    /**
     * Returns server string.
     *
     * @return  string  server string
     * @throws Engine_Exception
     */

    public function get_server_string()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->loaded)
            $this->_load();

        return $this->shares['global']['server string']['value'];
    }

    /**
     * Returns list of shares.
     *
     * @return array list of shares
     * @throws Engine_Exception
     */

    public function get_shares()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->loaded)
            $this->_load();

        $shares = array();

        foreach ($this->shares as $share => $keys) {
            if ($share == 'global')
                continue;

            $shares[] = $share;
        }

        return $shares;
    }

    /**
     * Gets share information for a given share.
     *
     *
     * @return array share information
     * @throws Samba_Share_Not_Found_Exception, Engine_Exception
     */

    public function get_share_info($share)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->loaded)
            $this->_load();

        if (! $this->share_exists($share))
            throw new Samba_Share_Not_Found_Exception($share);

        $info = array();

        foreach ($this->shares[$share] as $key => $value) {
            if ($key == "line") // skip comments, blank lines
                continue;

            if (in_array($key, $this->booleans)) {
                $boolvalue = $this->_get_boolean($value['value']);

                if ($boolvalue == self::CONSTANT_ENABLED)
                    $info[$key] = TRUE;
                else 
                    $info[$key] = FALSE;
            } else {
                $info[$key] = $value['value'];
            }
        }

        if (! isset($info['available']))
            $info['available'] = TRUE;

        if ($this->is_special_share($share))
            $info['special'] = TRUE;
        else
            $info['special'] = FALSE;

        return $info;
    }

    /**
     * Returns info on special shares - homes, printers, etc.
     *
     * @return array details of special shares
     */

    public function get_special_share_defaults()
    {
        clearos_profile(__METHOD__, __LINE__);

        $sharelist = array();

        $shareinfo = array();
        $shareinfo['name'] = 'homes';
        $shareinfo['browseable'] = 'No';
        $shareinfo['read only'] = 'No';
        $shareinfo['valid users'] = '%D\%S';
        $shareinfo['available'] = 'Yes';
        $shareinfo['path'] = '/home/%U';
        $shareinfo['comment'] = lang('samba_home_directory');
        $sharelist[] = $shareinfo;

        $shareinfo = array();
        $shareinfo['name'] = 'netlogon';
        $shareinfo['browseable'] = 'No';
        $shareinfo['locking'] = 'No';
        $shareinfo['read only'] = 'No';
        $shareinfo['available'] = 'Yes';
        $shareinfo['path'] = "/var/samba/netlogon";
        $shareinfo['comment'] = lang('samba_netlogon_directory');
        $sharelist[] = $shareinfo;

        $shareinfo = array();
        $shareinfo['name'] = 'profiles';
        $shareinfo['browseable'] = 'No';
        $shareinfo['profile acls'] = 'Yes';
        $shareinfo['read only'] = 'No';
        $shareinfo['available'] = 'Yes';
        $shareinfo['path'] = "/var/samba/profiles";
        $shareinfo['force group'] = "domain_users"; // TODO: should be constant
        $shareinfo['force directory mode'] = "02775";
        $shareinfo['force directory security mode'] = "02775";
        $shareinfo['comment'] = lang('samba_profiles_directory');
        $sharelist[] = $shareinfo;

        $shareinfo = array();
        $shareinfo['name'] = 'printers';
        $shareinfo['browseable'] = 'No';
        $shareinfo['read only'] = 'No';
        $shareinfo['printable'] = 'Yes';
        $shareinfo['use client driver'] = 'Yes';
        $shareinfo['cups options'] = 'raw';
        $shareinfo['printing'] = 'cups';
        $shareinfo['available'] = 'No';
        $shareinfo['path'] = "/var/spool/samba";
        $shareinfo['comment'] = lang('samba_printer_spool');
        $sharelist[] = $shareinfo;

        $shareinfo = array();
        $shareinfo['name'] = 'print$';
        $shareinfo['browseable'] = 'No';
        $shareinfo['read only'] = 'No';
        $shareinfo['available'] = 'No';
        $shareinfo['path'] = "/var/samba/drivers";
        $shareinfo['comment'] = lang('samba_printer_drivers');
        $sharelist[] = $shareinfo;

        return $sharelist;
    }

    /**
     * Returns WINS server.
     *
     * @return string WINS server
     * @throws Engine_Exception
     */

    public function get_wins_server()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->loaded)
            $this->_load();

        return $this->shares['global']['wins server']['value'];
    }

    /**
     * Returns WINS support.
     *
     * @return boolean TRUE if WINS support is enabled
     * @throws Engine_Exception
     */

    public function get_wins_support()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->loaded)
            $this->_load();

        $value = $this->_get_boolean($this->shares['global']['wins support']['value']);

        if ($value === self::CONSTANT_ENABLED)
            return TRUE;
        else
            return FALSE;
    }

    /**
     * Returns workgroup name.
     *
     * @return string workgroup name
     * @throws Engine_Exception
     */

    public function get_workgroup()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->loaded)
            $this->_load();

        return $this->shares['global']['workgroup']['value'];
    }

    /**
     * Initializes the local Samba system environment.
     *
     * @param string $netbios_name netbios_name
     * @param string $password password for winadmin
     *
     * @return void
     * @throws Engine_Exception, Samba_Not_Initialized_Exception
     */

    public function initialize_local_system($netbios_name, $password)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Directory needs to be initialized
        //----------------------------------

        $ldap = new OpenLDAP_Driver();
        if (! $ldap->is_directory_initialized())
            throw new Samba_Not_Initialized_Exception();

        // Set the netbios name
        //---------------------

        $this->set_netbios_name($netbios_name);

        // TODO: assuming PDC mode for now
        //------------------------------

        $this->set_mode(Samba::MODE_PDC);

        // Save the LDAP an Idmap passwords
        //---------------------------------

        $this->_save_bind_password();
        $this->_save_idmap_password();

        // Set the domain SID
        // FIXME: investigate in BDC mode
        // $this->set_domain_sid();

        // Net calls for privs an joining system to domain
        // Note: Samba needs to be running for the next steps
        //---------------------------------------------------

        $nmbd = new Nmbd();
        $smbd = new Smbd();
        $nmbd_wasrunning = $nmbd->get_running_state();
        $smbd_wasrunning = $smbd->get_running_state();

        if (! $smbd_wasrunning)
            $smbd->set_running_state(TRUE);

        if (! $nmbd_wasrunning)
            $nmbd->set_running_state(TRUE);

        $net_error = ''; 
        
        for ($i = 0; $i < 5; $i++) { 
            sleep(3); // wait or daemons to start, not atomic

            try {
                // Grant default privileges to winadmin et al
                $this->_net_grant_default_privileges($password);

                // If PDC, join the local system to itself
                $this->_net_rpc_join($password);

                $net_error = '';
                continue;
            } catch (Engine_Exception $e) {
                $net_error = clearos_exception_message($e);
            }
        }

        if (! empty($net_error))
            throw new Engine_Exception($net_error);

        // Stop daemons if that was their previous state
        //----------------------------------------------

        try {
            if (! $smbd_wasrunning)
                $smbd->set_running_state(FALSE);
            if (! $nmbd_wasrunning)
                $nmbd->set_running_state(FALSE);
        } catch (Exception $e) {
            // Not fatal
        }

        // Update local file permissions
        //------------------------------

        $this->update_local_file_permissions();

        // Set the local system initialized flag
        $this->set_local_system_initialized(TRUE);
    }

    /**
     * Initializes master node with the necessary Samba elements.
     *
     * You do not need to have the server components of Samba installed
     * to run this initialization routine.  This simply initializes the
     * necessary bits to get LDAP up and running.
     *
     * @param string  $domain   workgroup / domain
     * @param string  $password password for winadmin
     * @param boolean $force    force initialization
     *
     * @return void
     * @throws Engine_Exception
     */

    public function initialize_master_system($domain, $password = NULL, $force = FALSE)
    {
        clearos_profile(__METHOD__, __LINE__);

        $ldap = new OpenLDAP_Driver();

        return $ldap->initialize_master_system($domain, $password, $force);
    }

    /**
     * Checks to see if local Samba system had been initialized.
     *
     * @return boolean TRUE if local Samba system has been initialized
     * @throws Engine_Exception
     */

    public function is_local_system_initialized()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(Samba::FILE_LOCAL_SYSTEM_INITIALIZED);

        if ($file->exists())
            return TRUE;
        else
            return FALSE;
    }

    /**
     * Check routine for identifying special shares.
     *
     * @param string $name special share name name
     *
     * @return boolean TRUE if share name is special
     */

    public function is_special_share($name)
    {
        clearos_profile(__METHOD__, __LINE__);

        $sharedata = array();
        $sharedata = $this->get_special_share_defaults();

        foreach ($sharedata as $shareinfo) {
            if ($shareinfo["name"] == $name)
                return TRUE;
        }

        return FALSE;
    }

    /**
     * Validation routine for Samba booleans.
     *
     * Samba allows boolean values to be yes/no, TRUE/FALSE, 1/0.  This is
     * a simple method to chech for this type of value.
     *
     * @param string $value Samba boolean value
     *
     * @return boolean TRUE if valid
     */

    public function is_valid_boolean($value)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (preg_match("/^(yes|TRUE|1|no|FALSE|0)$/i", $value))
            return TRUE;
        else
            return FALSE;
    }

    /**
     * Runs net rpc join command.
     *
     * @access private
     * @param string $password winadmin password
     *
     * @return void
     * @throws Engine_Exception
     */

    public function net_ads_join($server, $password, $administrator = 'Administrator')
    {
        clearos_profile(__METHOD__, __LINE__);

        // FIXME: password handling via Shell
        $options['env'] = 'LANG=en_US';
        $options['validate_exit_code'] = FALSE;

        $shell = new Shell();
        $exit_code = $shell->execute(self::COMMAND_NET, 'ads join' .
            " -S '$server' " .
            " -U '" . $administrator . '%' . $password . "'", 
            TRUE, 
            $options
        );

        // Translate common error messages, fallback to command output
        //------------------------------------------------------------

        if ($exit_code !== 0) {
            $output = $shell->get_last_output_line();

            if (preg_match('/Logon failure/', $output))
                $message = lang('samba_authentication_failure');
            else if (preg_match('/network name cannot be found/', $output))
                $message = lang('samba_server_name_could_not_be_found');
            else if (preg_match('/connection was refused/', $output))
                $message = lang('samba_connection_to_server_was_refused');
            else if (preg_match('/NT_STATUS_HOST_UNREACHABLE/', $output))
                $message = lang('samba_server_is_not_reachable');
            else if (preg_match('/NT_STATUS_IO_TIMEOUT/', $output))
                $message = lang('samba_server_response_took_too_long');
            else
                $message = $output;

            throw new Samba_Connection_Exception($message);
        }
    }

    /**
     * Sets add machine script.
     *
     * @param string $script add machine script
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_add_machine_script($script)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->_set_share_info('global', 'add machine script', $script);
    }

    /**
     * Sets domain logons.
     *
     * @param boolean $state state of domain logons
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_domain_logons($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_domain_logons($state));

        $state_value = ($state) ? 'Yes' : 'No';

        $this->_set_share_info('global', 'domain logons', $state_value);
    }

    /**
     * Sets domain master state.
     *
     * @param boolean $state state of domain master
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_domain_master($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_domain_master($state));

        $state_value = ($state) ? 'Yes' : 'No';

        $this->_set_share_info('global', 'domain master', $state_value);
    }

    /**
     * Sets domain SID.
     *
     * @param string $sid domain SID
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_domain_sid($sid = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (empty($sid)) {
            $file = new File(self::FILE_DOMAIN_SID, TRUE);

            if ($file->exists()) {
                $lines = $file->get_contents_as_array();
                $sid = $lines[0];
            }
        }

        if (! empty($sid)) {
            $shell = new Shell();
            $shell->execute(self::COMMAND_NET, 'setdomainsid ' . $sid, TRUE);
        }
    }

    /**
     * Sets local master.
     *
     * @param  string  $state  local master state
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_local_master($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_local_master($state));

        $state_value = ($state) ? 'Yes' : 'No';

        $this->_set_share_info('global', 'local master', $state_value);
    }

    /**
     * Sets local SID.
     *
     * @param string $sid local SID
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_local_sid($localsid = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            if (empty($localsid)) {
                $file = new File(self::FILE_DOMAIN_SID, TRUE);

                if ($file->exists()) {
                    $lines = $file->get_contents_as_array();
                    $localsid = $lines[0];
                }
            }

            $shell = new Shell();
            $shell->execute(self::COMMAND_NET, 'setlocalsid ' . $localsid, TRUE);
        } catch (Engine_Exception $e) {
            // TODO: Ignore for now?
        }
    }

    /**
     * Sets logon drive.
     *
     * @param string $drive logon drive
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_logon_drive($drive)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_logon_drive($drive));

        $this->_set_share_info('global', 'logon drive', $drive);
    }

    /**
     * Sets logon home.
     *
     * @param string $home logon home
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_logon_home($home)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_logon_home($home));

        $home = preg_quote($home, '\\');

        $this->_set_share_info('global', 'logon home', $home);
    }

    /**
     * Sets logon path (profiles).
     *
     * @param string $path logon path
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_logon_path($path)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_logon_path($path));

        $path = preg_quote($path, '\\');

        // TODO: setting an empty path will delete "logon path"... that's not what
        // we want right now.  Set it to a space for now.  Yes, a kludge.
        $temppath = empty($path) ? " " : $path;

        $this->_set_share_info('global', 'logon path', $temppath);
    }

    /**
     * Sets logon script.
     *
     * @param  string  $script  logon script
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_logon_script($script)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->_set_share_info('global', 'logon script', $script);
    }

    /**
     * Sets server mode.
     *
     * @param string $mode server mode
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_mode($mode)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_mode($mode));

        // TODO: move this somewhere else
        $this->set_logon_home('\\\\%L\%U');

        if ($mode == self::MODE_PDC) {
            $this->set_domain_logons(TRUE);
            $this->set_domain_master(TRUE);
            $this->set_preferred_master(TRUE);
            $this->set_share_availability('netlogon', TRUE);
            $this->set_unix_password_sync_state(TRUE);
            $this->set_security(Samba::SECURITY_USER);
            $this->_set_ldap_includes(TRUE);
        } else if ($mode == self::MODE_BDC) {
            $this->set_domain_logons(FALSE);
            $this->set_domain_master(FALSE);
            $this->set_preferred_master(FALSE);
            $this->set_share_availability('netlogon', FALSE);
            $this->set_share_availability('profiles', FALSE);
            $this->set_roaming_profiles_state(FALSE);
            $this->set_unix_password_sync_state(FALSE);
            $this->set_security(Samba::SECURITY_DOMAIN);
            $this->_set_ldap_includes(TRUE);
        } else if ($mode == self::MODE_SIMPLE_SERVER) {
            $this->set_domain_logons(TRUE);
            $this->set_domain_master(TRUE);
            $this->set_preferred_master(TRUE);
            $this->set_share_availability('netlogon', FALSE);
            $this->set_share_availability('profiles', FALSE);
            $this->set_roaming_profiles_state(FALSE);
            $this->set_unix_password_sync_state(TRUE);
            $this->set_security(Samba::SECURITY_USER);
            $this->_set_ldap_includes(TRUE);
        } else if ($mode == self::MODE_MEMBER) {
            // FIXME: David review
            $this->set_domain_logons(FALSE);
            $this->set_domain_master(FALSE);
            $this->set_preferred_master(FALSE);
/*
            $this->set_share_availability('netlogon', FALSE);
            $this->set_share_availability('profiles', FALSE);
            $this->set_roaming_profiles_state(FALSE);
*/
            $this->set_unix_password_sync_state(FALSE);
            $this->set_security(Samba::SECURITY_ADS);
            $this->_set_ldap_includes(FALSE);
        }
    }

    /**
     * Sets system/netbios name.
     *
     * @param  string  netbios_name     system name
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_netbios_name($netbios_name)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_netbios_name($netbios_name));

        // Change smb.conf
        $this->_set_share_info('global', 'netbios name', $netbios_name);

        // Clean up secrets file
        $this->_clean_secrets_file();
    }

    /**
     * Sets OS level.
     *
     * @param  string  $oslevel  OS level
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_os_level($oslevel)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_os_level($oslevel));

        $this->_set_share_info('global', 'os level', $oslevel);
    }

    /**
     * Sets password chat.
     *
     * @param string $chat chat string
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_password_chat($chat)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->_set_share_info('global', 'passwd chat', $chat);
    }

    /**
     * Sets password program.
     *
     * @param string $program password program
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_password_program($program)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->_set_share_info('global', 'passwd program', $program);
    }

    /**
     * Sets password server list.
     *
     * @param array $servers password server list
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_password_servers($servers)
    {
        clearos_profile(__METHOD__, __LINE__);

        $server_list = implode($servers, ' ');

        $this->_set_share_info('global', 'password server', $server_list);
    }

    /**
     * Sets preferred master state.
     *
     * @param boolean $state preferred master state.
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_preferred_master($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_preferred_master($state));

        $state_value = ($state) ? 'Yes' : 'No';

        $this->_set_share_info('global', 'preferred master', $state_value);
    }

    /**
     * Sets printing info.
     *
     * @param string $mode print mode
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_printing_mode($mode)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($mode == self::PRINTING_DISABLED) {
            $this->_set_share_info('printers', 'available', 'No');
            $this->_set_share_info('print$', 'available', 'No');
        } else if ($mode == self::PRINTING_POINT_AND_CLICK) {
            $this->_set_share_info('printers', 'cups options', '');
            $this->_set_share_info('printers', 'use client driver', 'No');
            $this->_set_share_info('printers', 'available', 'Yes');
            $this->_set_share_info('print$', 'available', 'Yes');
        } else if ($mode == self::PRINTING_RAW) {
            $this->_set_share_info('printers', 'cups options', 'raw');
            $this->_set_share_info('printers', 'use client driver', 'Yes');
            $this->_set_share_info('printers', 'available', 'Yes');
            $this->_set_share_info('print$', 'available', 'Yes');
        }
    }

    /**
     * Sets security type.
     *
     * @param string $type security type
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_security($type)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_security($type));

        $this->_set_share_info('global', 'security', $type);
    }

    /**
     * Sets server string.
     *
     * @param string $server_string server string
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_server_string($server_string)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_server_string($server_string));

        $this->_set_share_info('global', 'server string', $server_string);
    }

    /**
     * Sets system initialized flag.
     *
     * @param boolean $state flag indicating system initialization state
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_local_system_initialized($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(Samba::FILE_LOCAL_SYSTEM_INITIALIZED);

        if ($state) {
            if (! $file->exists())
                $file->create("root", "root", "0644");
        } else {
            if ($file->exists())
                $file->delete();
        }
    }

    /**
     * Sets realm.
     *
     * @param string $realm realm
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_realm($realm)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! empty($realm))
            Validation_Exception::is_valid($this->validate_realm($realm));

        $realm = strtolower($realm);

        $this->_set_share_info('global', 'realm', $realm);
    }

    /**
     * Sets roaming profile behavior.
     *
     * @param boolean $state TRUE if roaming profiles should be enabled
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_roaming_profiles_state($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! is_bool($state))
            throw new Validation_Exception(LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - state");

        if ($state) {
            $path = '\\\\%L\profiles\%U';
            $profiles = TRUE;
        } else {
            $path = '';
            $profiles = FALSE;
        }

        $this->set_logon_path($path);
        $this->set_share_availability('profiles', $profiles);
    }

    /**
     * Sets availability for a given share.
     *
     * @param string $share share name
     * @param string $state state of availbility
     *
     * @return void
     * @throws Samba_Share_Not_Found_Exception, Validation_Exception, Engine_Exception
     */

    public function set_share_availability($share, $state)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! is_bool($state))
            throw new Validation_Exception(LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - state");

        $state_value = ($state) ? 'Yes' : 'No';

        $this->_set_share_info($share, 'available', $state_value);
    }

    /**
     * Sets WINS server and support.
     *
     * @param string $server WINS server
     * @param boolean $support_flag WINS support flag
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_wins_server_and_support($server, $is_wins)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_wins_server($server));
        Validation_Exception::is_valid($this->validate_wins_support($is_wins));

        // You cannot have "wins server" and "wins support" at the same time
        if ($is_wins && $server)
            throw new Validation_Exception(lang('samba_wins_configuration_conflict'));

        $is_wins_param = ($is_wins) ? 'Yes' : 'No';

        $this->_set_share_info('global', 'wins support', $is_wins_param);
        $this->_set_share_info('global', 'wins server', trim($server));
    }

    /**
     * Sets Unix password synchronization state.
     *
     * @param boolean $state state of Unix password synchronization
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_unix_password_sync_state($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        $state_val = ($state) ? 'Yes' : 'No';

        $this->_set_share_info('global', 'unix password sync', $state_val);
    }

    /**
     * Sets workgroup name.
     *
     * @param string $workgroup workgroup name
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_workgroup($workgroup)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_workgroup($workgroup));

        $workgroup = strtoupper($workgroup);

        // Change smb.conf
        $this->_set_share_info('global', 'workgroup', $workgroup);

        // Update LDAP object
// FIXME: handle LDAP dependency
/*
        $ldap = new OpenLDAP_Driver();
        $ldap->set_workgroup($workgroup);
*/

        // Clean up secrets file
// FIXME: LDAP again
//        $this->_clean_secrets_file();
    }

    /**
     * Share look-up
     *
     * @param  string  $name
     *
     * @return  boolean  TRUE if share 'name' is exists
     */

    public function share_exists($name)
    {
        clearos_profile(__METHOD__, __LINE__);

        foreach ($this->shares as $share => $keys) {
            if ($share == $name)
                return TRUE;
        }

        return FALSE;
    }

    /**
     * Updates file permissions.
     *
     * Ugh.  There's a bit of a chicken and egg issue with respect to some file
     * permissions.  When an RPM is installed, we can't count on LDAP access.  
     * This is problematic when the RPM needs to set group permissions on a file
     * or directory (e.g. /var/samba/drivers owned by domain_users).  This method 
     * cleans up this issue.
     *
     *
     * @return void
     * @throws Engine_Exception, Samba_Not_Initialized_Exception
     */

    public function update_local_file_permissions()
    {
        clearos_profile(__METHOD__, __LINE__);

        // TODO: Swap this around.  The logic in the add-samba-directories script
        // should be moved here and the script should then call this method.

        try {
            $shell = new Shell();
            $shell->Execute(self::COMMAND_ADD_SAMBA_DIRS, '', TRUE);
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_ERROR);
        }
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   R O U T I N E S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validation routine for domain logons.
     *
     * @param boolean $state domain logons state
     *
     * @return string error message if domain logons is invalid
     */

    public function validate_domain_logons($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! clearos_is_valid_boolean($state))
            return lang('samba_domain_logons_setting_is_invalid');
    }

    /**
     * Validation routine for domain master.
     *
     * @param boolean $state domain master state
     *
     * @return string error message if domain master is invalid
     */

    public function validate_domain_master($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! clearos_is_valid_boolean($state))
            return lang('samba_domain_master_setting_is_invalid');
    }

    /**
     * Validation routine for local master.
     *
     * @param boolean $state local master state
     *
     * @return string error message if local master is invalid
     */

    public function validate_local_master($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! clearos_is_valid_boolean($state))
            return lang('samba_local_master_setting_is_invalid');
    }

    /**
     * Validation routine for modes.
     *
     * @param string $modes modes
     *
     * @return boolean TRUE if computer modes valid
     */

    public function validate_mode($mode)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! array_key_exists($mode, $this->modes))
            return lang('samba_server_mode_is_invalid');
    }

    /**
     * Validation routine for preferred master.
     *
     * @param boolean $state preferred master state
     *
     * @return string error message if preferred master is invalid
     */

    public function validate_preferred_master($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! clearos_is_valid_boolean($state))
            return lang('samba_preferred_master_setting_is_invalid');
    }

    /**
     * Validation routine for netbios name.
     *
     * @param string $netbios_name system name
     *
     * @return string error message if netbios name is invalid
     */

    public function validate_netbios_name($netbios_name)
    {
        clearos_profile(__METHOD__, __LINE__);

        $isvalid = TRUE;

        if (! (preg_match("/^([a-zA-Z][a-zA-Z0-9\-]*)$/", $netbios_name) && (strlen($netbios_name) <= 15)))
            return lang('samba_server_name_is_invalid');

        $workgroup = strtoupper($this->get_workgroup());
        $netbios_name = strtoupper($netbios_name);

        if ($workgroup === $netbios_name)
            return lang('samba_server_name_conflicts_with_windows_domain');
    }

    /**
     * Validation routine for realm.
     *
     * @param string $realm realm
     *
     * @return string error message if realm is invalid
     */

    public function validate_realm($realm)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! Network_Utils::is_valid_domain($realm))
            return lang('samba_realm_is_invalid');
    }

    /**
     * Validation routine for workgroup
    /**
     * Validation routine for share name
     *
     * @param  string  $name  share name name
     *
     * @return  boolean  TRUE if share name is valid
     */

    public function validate_share($name)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! preg_match("/^([a-zA-Z\-$]+)$/", $name))
            return lang('samba_share_is_invalid');
    }

    /**
     * Validation routine for workgroup
     *
     * To avoid issues on Windows networks:
     * - the netbios_name and workgroup must be different
     * - the host nickname (left-side of the hostname) must not match the workgroup
     *
     * @param  string  $workgroup  workgroup name
     *
     * @return  boolean  TRUE if workgroup is valid
     */

    public function validate_workgroup($workgroup)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! (preg_match("/^([a-zA-Z][a-zA-Z0-9\-]*)$/", $workgroup) && (strlen($workgroup) <= 15)))
            return lang('samba_windows_domain_is_invalid');

        $netbios_name = $this->get_netbios_name();

        $hostnameobj = new Hostname();
        $hostname = $hostnameobj->get();
        $nickname = preg_replace("/\..*/", "", $hostname);

        $nickname = strtoupper($nickname);
        $netbios_name = strtoupper($netbios_name);
        $workgroup = strtoupper($workgroup);

        if ($workgroup === $netbios_name)
            return lang('samba_server_name_conflicts_with_windows_domain');

        if ($workgroup === $nickname)
            return lang('samba_hostname_conflicts_with_windows_domain');
    }

    /**
     * Validation routine for security.
     *
     * @param string $type security type
     *
     * @return string error message if security type is invalid.
     */

    public function validate_security($type)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!(
            ($type === self::SECURITY_USER) ||
            ($type === self::SECURITY_SHARE) ||
            ($type === self::SECURITY_ADS) ||
            ($type === self::SECURITY_DOMAIN)
           ))
            return lang('samba_security_type_is_invalid');
    }

    /**
     * Validation routine for server string.
     *
     * @param string $server_string server string
     *
     * @return  boolean  TRUE if serverstring is valid
     */

    public function validate_server_string($server_string)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! preg_match("/^([a-zA-Z][\-\w ]*)$/", $server_string))
            return lang('samba_server_comment_is_invalid');
    }

    /**
     * Validation routine for winsserver
     *
     * @param  string  $winsserver  WINS server
     *
     * @return  boolean  TRUE if winsserver is valid
     */

    public function validate_wins_server($winsserver)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!preg_match("/^([a-zA-Z0-9\-\.]*)$/", $winsserver))
            return lang('samba_wins_server_is_invalid');
    }

    /**
     * Validation routine for WINS support
     *
     * @param  string  $state  state
     *
     * @return  boolean  TRUE if valid
     */

    public function validate_wins_support($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! clearos_is_valid_boolean($state))
            return lang('samba_wins_support_setting_is_invalid');
    }

    /**
     * Validation routine for logon drive
     *
     * @param  string  $drive  drive
     *
     * @return  boolean  TRUE if valid
     */

    public function validate_logon_drive($drive)
    {
        clearos_profile(__METHOD__, __LINE__);

        // TODO: hard-coded in current implementation
        return TRUE;
    }

    /**
     * Validation routine for logon home
     *
     * @param  string  $home  home
     *
     * @return  boolean  TRUE if valid
     */

    public function validate_logon_home($home)
    {
        clearos_profile(__METHOD__, __LINE__);

        // TODO: hard-coded in current implementation
        return TRUE;
    }

    /**
     * Validation routine for logon path.
     *
     * @param string $path path
     *
     * @return string error message if logon path is invalid
     */

    public function validate_logon_path($path)
    {
        clearos_profile(__METHOD__, __LINE__);

        // TODO: hard-coded in current implementation
        return TRUE;
    }

    /**
     * Validation routine for logon script
     *
     * @param  string  $script  script
     *
     * @return  boolean  TRUE if valid
     */

    public function validate_logon_script($script)
    {
        clearos_profile(__METHOD__, __LINE__);

        // TODO: tighten this up
        return TRUE;
    }

    /**
     * Validation routine for oslevel
     *
     * @param   oslevel         OS level
     *
     * @return  boolean  TRUE if oslevel is valid
     */

    public function validate_os_level($oslevel)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($oslevel && !preg_match("/^([0-9]+)$/", $oslevel))
            return lang('samba_os_level_setting_is_invalid');
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Returns a Samba boolean.
     *
     * One of the following will be returned:
     *  CONSTANT_ENABLED (yes, TRUE, 0)
     *  CONSTANT_DISABLED (no, FALSE, 1)
     *  CONSTANT_DEFAULT (not specified in config)
     *  CONSTANT_INVALID (invalid configuration data)
     *
     * @access private
     * @param string value value of a samba boolean (yes, no or auto)
     *
     * @return string constant value
     */

    protected function _get_boolean($value)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (empty($value))
            return self::CONSTANT_DEFAULT;
        else if (preg_match("/^(yes|TRUE|0)$/i", $value))
            return self::CONSTANT_ENABLED;
        else if (preg_match("/^(no|FALSE|1)$/i", $value))
            return self::CONSTANT_DISABLED;
        else
            return self::CONSTANT_INVALID;
    }

    /**
     * Load and parse configuration file.
     *
     * @access private
     *
     * @return void
     * @throws Engine_Exception
     */

    protected function _load()
    {
        clearos_profile(__METHOD__, __LINE__);

        // Reset our data structures
        //--------------------------

        $this->loaded = FALSE;
        $this->raw_lines = array();
        $this->shares = array();
        $this->values = array();

        $file = new File(self::FILE_CONFIG);

        if (! $file->exists())
            $file->create('root', 'root', '0600');

        $lines = $file->get_contents_as_array();

        $linecount = 0;
        $share = 'global';
        $match = array();
        $this->shares[$share] = array();

        foreach ($lines as $line) {
            $this->raw_lines[] = $line;

            if (preg_match('/^\s*\[(.*)\]/', $line, $match)) {
                $share = trim($match[1]);
                $this->shares[$share]['line'] = $linecount;
            } else if (!preg_match('/^\s*[;#]+.*$/', $line)) {
                if (preg_match('/^\s*([a-z0-9\s]+)\s*=\s*(.*$)/', $line, $match)) {
                    $key = trim($match[1]);
                    $this->shares[$share][$key]['line'] = $linecount;

                    $value = explode('#', preg_replace('/;/', '#', $match[2]));
                    $this->values[$share][$key] = trim($value[0]);
                    $this->shares[$share][$key]['value'] = $this->values[$share][$key];
                }
            }

            $linecount++;
        }

        $this->loaded = TRUE;
    }

    /**
     * Grants default privileges for the system.
     *
     * @access private
     * @param string $password password for winadmin
     *
     * @return void
     * @throws Engine_Exception
     */

    public function _net_grant_default_privileges($password)
    {
        clearos_profile(__METHOD__, __LINE__);

        $domain = $this->get_workgroup();
        $options['stdin'] = TRUE;

        $shell = new Shell();
        $shell->Execute(self::COMMAND_NET, 'rpc rights grant "' . $domain . '\Domain Admins" ' .
            self::DEFAULT_ADMIN_PRIVS . ' -U winadmin%' . $password, TRUE, $options);
    }

    /**
     * Runs net rpc join command.
     *
     * @param string $password winadmin password
     *
     * @access private
     * @return void
     * @throws Engine_Exception
     */

    protected function _net_rpc_join($password)
    {
        clearos_profile(__METHOD__, __LINE__);

        $domain = $this->get_workgroup();
        $netbios_name = $this->get_netbios_name();

        $options['stdin'] = TRUE;

        $shell = new Shell();
        $shell->Execute(self::COMMAND_NET, 'rpc join -W ' . $domain . ' -S ' .$netbios_name .
            ' -U winadmin%' . $password, TRUE, $options);
    }

    /**
     * Cleans up the secrets file.
     *
     * @param string $winpassword password
     *
     * @return void
     * @throws Engine_Exception
     */

    public function _clean_secrets_file($winpassword = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        // TODO: how do we want to present this in the UI without
        // having to constantly ask for winadmin password? Or should we
        // be asking for the password?

        $ldap = new OpenLDAP_Driver();
        if (! $ldap->is_directory_initialized())
            return;

/*
// FIXME
        if (!$this->is_local_system_initialized())
            return;
*/

        $nmbd = new Nmbd();
        $smbd = new Smbd();
        $winbind = new Winbind();

        $nmbd_wasrunning = FALSE;
        $smbd_wasrunning = FALSE;
        $winbind_wasrunning = FALSE;

        if ($winbind->is_installed()) {
            $winbind_wasrunning = $winbind->get_running_state();
            if ($winbind_wasrunning)
                $winbind->set_running_state(FALSE);
        }

        if ($smbd->is_installed()) {
            $smbd_wasrunning = $smbd->get_running_state();
            if ($smbd_wasrunning)
                $smbd->set_running_state(FALSE);
        }

        if ($nmbd->is_installed()) {
            $nmbd_wasrunning = $nmbd->get_running_state();
            if ($nmbd_wasrunning)
                $nmbd->set_running_state(FALSE);
        }

        $this->_save_bind_password();
        $this->_save_idmap_password();
        $this->set_domain_sid();
        $this->set_local_sid();

        if ($nmbd_wasrunning)
            $nmbd->set_running_state(TRUE);

        if ($smbd_wasrunning)
            $smbd->set_running_state(TRUE);

        if ($winbind_wasrunning)
            $winbind->set_running_state(TRUE);

        sleep(3); // TODO: Wait for samba ... replace this with a loop

        if (! empty($winpassword))
            $this->_net_rpc_join($winpassword);
    }

    /**
     * Saves configuration file.
     *
     * @access private
     *
     * @return void
     * @throws Engine_Exception
     */

    protected function _save()
    {
        clearos_profile(__METHOD__, __LINE__);

        // Set unload flag to FALSE... even if something goes wrong...
        //------------------------------------------------------------

        $this->loaded = FALSE;

        $filedata = '';

        // Set the key/value pairs and delete unwanted lines
        //--------------------------------------------------

        foreach ($this->shares as $share => $keys) {
            foreach ($keys as $key => $value) {
                if ($key == 'line') {
                    continue;
                } elseif ($value['value'] == self::CONSTANT_NULL_LINE) {
                    $this->raw_lines[$value['line']] = self::CONSTANT_NULL_LINE;
                } else {
                    $prefix = ($share == "global") ? "" : "\t";
                    // TODO: weird double backslash behavior
                    $newvalue = preg_replace("/^\\\\/", "\\\\\\\\", $value['value']);
                    $this->raw_lines[$value['line']] = preg_replace("/^\s*$key\s*=.*/", "$prefix$key = " . $newvalue, $this->raw_lines[$value['line']]);
                }
            }
        }

        // Add raw lines while checking for additions to *existing* shares
        //----------------------------------------------------------------

        for ($i = 0; $i < sizeof($this->raw_lines); $i++) {
            if (isset($this->raw_lines[$i]) && $this->raw_lines[$i] != self::CONSTANT_NULL_LINE)
                $filedata .= $this->raw_lines[$i] . "\n";

            foreach ($this->shares as $share => $keys) {
                # Continue if we are at the end of this particular share
                if ($keys['line'] != $i)
                    continue;

                # Look for key/value pairs without a line number
                # Weird... the "line" constant is used for blank lines?
                foreach ($keys as $key => $value) {
                    if (!$value['line'] && ($value['value'] != self::CONSTANT_NULL_LINE) && ($key != 'line')) {
                        $prefix = ($share == "global") ? "" : "\t";
                        $filedata .= "$prefix$key = $value[value]\n";
                    }
                }
            }
        }

        // Add any new shares
        //-------------------

        foreach ($this->shares as $share => $keys) {
            if ($keys['line'] != self::CONSTANT_NULL_LINE)
                continue;

            $filedata .= "\n[$share]\n";

            foreach ($keys as $key => $value) {
                if ($key == "line")
                    continue;

                $filedata .= "$key = $value[value]\n";
            }
        }

        $filedata = trim($filedata) . "\n";

        // Delete any old temp file lying around
        //--------------------------------------

        $new_config = new File(self::FILE_CONFIG . '.cctmp');

        if ($new_config->exists())
            $new_config->delete();

        // Create temp file
        //-----------------
        $new_config->create('root', 'root', '0644');

        // Write out the file
        //-------------------

        $new_config->add_lines($filedata);

        // Copy the new config over the old config
        //----------------------------------------

        $new_config->move_to(self::FILE_CONFIG);
    }

    /**
     * Saves LDAP bind password to Samba secrets file.
     *
     * @access private
     *
     * @return void
     * @throws Engine_Exception
     */

    public function _save_bind_password()
    {
        clearos_profile(__METHOD__, __LINE__);

        $ldap = new OpenLDAP_Driver();

        $bind_password = $ldap->get_bind_password();

        // Use pipe to avoid showing password in command line
        $options['stdin'] = TRUE;

        $shell = new Shell();
        $shell->execute(self::COMMAND_SMBPASSWD, "-w " . $bind_password, TRUE, $options);
    }

    /**
     * Saves password required for Idmap.
     *
     * @access private
     *
     * @return void
     * @throws Engine_Exception
     */

    protected function _save_idmap_password()
    {
        clearos_profile(__METHOD__, __LINE__);

        $ldap = new OpenLDAP_Driver();

        $password = $ldap->get_bind_password();
        $options['stdin'] = TRUE;

        $shell = new Shell();
        $exitcode = $shell->Execute(self::COMMAND_NET, "idmap secret '*' $password", TRUE, $options);
    }

    /**
     * Sets LDAP includes.
     *
     * @param boolean $state state of includes
     *
     * @return void
     * @throws Engine_Exception
     */

    public function _set_ldap_includes($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->_save();

        $file = new File(self::FILE_CONFIG);

        try {
            if ($state) {
                $file->replace_lines('/#.*include.*smb.ldap.conf/', "include = /etc/samba/smb.ldap.conf\n");
                $file->replace_lines('/#.*include.*smb.winbind.conf/', "include = /etc/samba/smb.winbind.conf\n");
            } else {
                $file->prepend_lines('/^include.*smb.ldap.conf/', '# ');
                $file->prepend_lines('/^include.*smb.winbind.conf/', '# ');
            }
        } catch (File_No_Match_Exception $e) {
            // Not fatal
        }
    }

    /**
     * Sets key/value pair for a given share.
     *
     * @access private
     * @param string  $share  share name
     * @param string  $key  config file key
     * @param string  $value  config file value
     *
     * @return void
     * @throws Samba_Share_Not_Found_Exception, Validation_Exception, Engine_Exception
     */

    public function _set_share_info($share, $key, $value)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->loaded)
            $this->_load();

        if (!$this->share_exists($share))
            throw new Samba_Share_Not_Found_Exception($share);

        // TODO: Some keys should not be deleted, but left blank instead.
        // Handle this situation in a more generalized way.

        if (in_array($key, $this->booleans)) {
            $this->shares[$share][$key]['value'] = $value;
        } else if ((! $value) && ($key != 'wins server') && ($key != 'logon path')) {
            $this->shares[$share][$key]['value'] = self::CONSTANT_NULL_LINE;
        } else {
            $this->shares[$share][$key]['value'] = $value;
        }

        $this->_save();
    }
}
