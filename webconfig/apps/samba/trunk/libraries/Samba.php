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

// Classes
//--------

use \clearos\apps\base\Daemon as Daemon;
use \clearos\apps\base\File as File;
use \clearos\apps\base\Folder as Folder;
use \clearos\apps\base\Shell as Shell;
use \clearos\apps\network\Hostname as Hostname;
use \clearos\apps\users\User as User;

clearos_load_library('base/Daemon');
clearos_load_library('base/File');
clearos_load_library('base/Folder');
clearos_load_library('base/Shell');
clearos_load_library('network/Hostname');
clearos_load_library('users/User');

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;
use \clearos\apps\samba\Samba_Not_Initialized_Exception as Samba_Not_Initialized_Exception;
use \clearos\apps\samba\Samba_Share_Not_Found_Exception as Samba_Share_Not_Found_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('base/Validation_Exception');
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


class Samba extends Daemon
{
    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $ldaph = NULL;
    protected $loaded = FALSE;
    protected $shares = array();
    protected $values = array();
    protected $booleans = array();
    protected $raw_lines = array();

    const FILE_CONFIG = '/etc/samba/smb.conf';
    const FILE_DOMAIN_SID = '/etc/samba/domainsid';
    const FILE_LOCAL_SID = '/etc/samba/localsid';
    const FILE_LOCAL_SYSTEM_INITIALIZED = '/etc/system/initialized/sambalocal';
    const PATH_STATE = '/var/lib/samba';
    const PATH_STATE_BACKUP = '/usr/share/system/modules/samba';
    const CMD_NET = '/usr/bin/net';
    const CMD_PDBEDIT = '/usr/bin/pdbedit';
    const CMD_SMBPASSWD = '/usr/bin/smbpasswd';
    const CMD_ADD_SAMBA_DIRS = '/usr/sbin/add-samba-directories';
    const MODE_PDC = 'pdc';
    const MODE_BDC = 'bdc';
    const MODE_SIMPLE_SERVER = 'simple';
    const MODE_CUSTOM = 'custom';
    const PRINTING_DISABLED = 'disabled';
    const PRINTING_POINT_AND_CLICK = 'pointnclick';
    const PRINTING_RAW = 'raw';
    const SECURITY_USER = 'user';
    const SECURITY_SHARE = 'share';
    const SECURITY_DOMAIN = 'domain';
    const TYPE_SID_DOMAIN = 'domain';
    const TYPE_SID_LOCAL = 'local';
    const CONSTANT_NULL_LINE = -1;
    const CONSTANT_ENABLED = 'Yes';
    const CONSTANT_DISABLED = 'No';
    const CONSTANT_DEFAULT = 'default';
    const CONSTANT_DOMAIN_USERS_RID = '513';
    const CONSTANT_WINADMIN_CN = 'Windows Administrator';
    const CONSTANT_WINADMIN_USERNAME = 'winadmin';
    const CONSTANT_GUEST_CN = 'Guest Account';
    // UID/GID/RID ranges -- see http://www.clearfoundation.com/docs/developer/features/cleardirectory/uids_gids_and_rids
    const CONSTANT_SPECIAL_RID_MAX = '1000'; // RIDs below this number are reserved
    const CONSTANT_SPECIAL_RID_OFFSET = '1000000'; // Offset used to map <1000 RIDs to UIDs
    const CONSTANT_GID_DOMAIN_COMPUTERS = '1000515';
    const DEFAULT_PASSWORD_CHAT = '*password:* %n\n *password:* %n\n *successfully.*';
    const DEFAULT_PASSWORD_PROGRAM = '/usr/sbin/userpasswd %u';
    const DEFAULT_ADD_MACHINE_SCRIPT = '/usr/sbin/samba-add-machine "%u"';
    const DEFAULT_OS_LEVEL = '20';
    const DEFAULT_ADMIN_PRIVS = 'SeMachineAccountPrivilege SePrintOperatorPrivilege SeAddUsersPrivilege SeDiskOperatorPrivilege SeMachineAccountPrivilege SeTakeOwnershipPrivilege';

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Samba constructor.
     *
     *
     * @return void
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        parent::__construct('smb');

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
     * Adds a machine.
     *
     * @param string $name machine name
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function add_machine($name)
    {
        clearos_profile(__METHOD__, __LINE__);

        // TODO: the "AddMachine" method does not add the Samba attributes since
        // this is done automagically by Samba.  If this automagic is missed for
        // some reason, then a Computer object may not have the sambaSamAccount object.

        if (! $this->IsValidMachineName($name))
            throw new Validation_Exception(SAMBA_LANG_COMPUTER . " - " . LOCALE_LANG_INVALID);

        if ($this->ldaph == NULL)
            $this->_get_ldap_handle();

        $user = new User("notused");
        $group = new Group("notused");

        $ldap_object = array();

        $ldap_object['objectClass'] = array(
            'top',
            'account',
            'posixAccount'
        );

        $ldap_object['cn'] = $name;
        $ldap_object['uid'] = $name;
        $ldap_object['description'] = SAMBA_LANG_COMPUTER . " " . preg_replace("/\$$/", "", $name);
        $ldap_object['uidNumber'] = $user->_GetNextUidNumber();
        $ldap_object['gidNumber'] = Samba::CONSTANT_GID_DOMAIN_COMPUTERS;
        $ldap_object['homeDirectory'] = '/dev/NULL';
        $ldap_object['loginShell'] = '/sbin/nologin';

        $dn = "cn=" . Ldap::DnEscape($name) . "," . ClearDirectory::GetComputersOu();

        try {
            if (! $this->ldaph->Exists($dn))
                $this->ldaph->Add($dn, $ldap_object);
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_ERROR);
        }
    }

    /**
     * Adds a share.
     *
     * @param string $name share name
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function add_share($name)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->loaded)
            $this->_Load();

        if (!$this->IsValidShare($name))
            throw new Validation_Exception(SAMBA_LANG_SHARE . " - " . LOCALE_LANG_INVALID);

        if ($this->ShareExists($name))
            throw new Validation_Exception(SAMBA_LANG_ERRMSG_SHARE_EXISTS);

        $this->shares[$name]['line'] = self::CONSTANT_NULL_LINE;

        $this->_Save();
    }

    /**
     * Deletes a computer from the domain.
     *
     * @param  string  $computer computer name
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function delete_computer($computer)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->ldaph == NULL)
            $this->_get_ldap_handle();

        if (! $this->IsDirectoryInitialized())
            throw new Samba_Not_Initialized_Exception();

        $dn = "cn=" . Ldap::DnEscape($computer) . "," . ClearDirectory::GetComputersOu();

        $this->ldaph->Delete($dn);
    }

    /**
     * Delete share.
     *
     * @param  string  $name  share name
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function delete_share($name)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->loaded)
            $this->_Load();

        if (! strlen($name))
            throw new Validation_Exception( SAMBA_LANG_SHARE . " - " . LOCALE_LANG_INVALID);

        if (! $this->ShareExists($name))
            throw new Validation_Exception(SAMBA_LANG_ERRMSG_SHARE_NOT_EXISTS);

        $i = 0;
        $lines = array();

        for ($i = 0; $i < $this->shares[$name]['line']; $i++)
            $lines[] = $this->raw_lines[$i];

        for ($i = $this->shares[$name]['line'] + 1; $i < sizeof($this->raw_lines); $i++) {
            if (ereg("^[[:space:]]*\[.*\]", $this->raw_lines[$i]))
                break;
        }

        for ( ; $i < sizeof($this->raw_lines); $i++)
            $lines[] = $this->raw_lines[$i];

        unset($this->shares[$name]);

        $this->raw_lines = $lines;

        $this->_Save();

        // Handle special case file permissions
        if ($name == "ftpsite") {
            $folder = new Folder("/var/ftp");
            if ($folder->Exists())
                $folder->Chown("root", "root");
        }
    }

    /**
     * Returns add machine script.
     *
     *
     * @return string add machine script
     * @throws Engine_Exception
     */

    public function get_add_machine_script()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->loaded)
            $this->_Load();

        if (empty($this->shares['global']['add machine script']['value']))
            return "";
        else
            return $this->shares['global']['add machine script']['value'];
    }

    /**
     * Gets a detailed list of computers for the domain.
     *
     *
     * @return  array  detailed list of computers
     * @throws Engine_Exception
     */

    public function get_computers()
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->ldaph == NULL)
            $this->_get_ldap_handle();

        if (! $this->IsDirectoryInitialized())
            throw new Samba_Not_Initialized_Exception();

        $computers = array();

        // TODO: the "AddMachine" method does not add the Samba attributes since
        // this is done automagically by Samba.  If this automagic is missed for
        // some reason, then a Computer object may not have the sambaSamAccount object.
        // To be safe, use the posixAccount object so that we can cleanup.

        try {
            $result = $this->ldaph->Search(
                "(objectclass=posixAccount)",
                ClearDirectory::GetComputersOu(),
                array("cn", "sambaSID", "uidNumber")
            );

            $entry = $this->ldaph->GetFirstEntry($result);

            while ($entry) {
                $attributes = $this->ldaph->GetAttributes($entry);

                $computer = $attributes['cn']['0'];
                $computers[$computer]['SID'] = isset($attributes['sambaSID'][0]) ? $attributes['sambaSID'][0] : "";
                $computers[$computer]['uidNumber'] = $attributes['uidNumber'][0];

                $entry = $this->ldaph->NextEntry($entry);
            }
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_ERROR);
        }

        return $computers;
    }

    /**
     * Gets domain logons.
     *
     *
     * @return  string  domain logons
     * @throws Engine_Exception
     */

    public function get_domain_logons()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->loaded)
            $this->_Load();

        $sambavalue = $this->_GetBoolean($this->shares['global']['domain logons']['value']);

        if ($sambavalue === self::CONSTANT_ENABLED)
            return TRUE;
        else
            return FALSE;
    }

    /**
     * Gets domain master setting.
     *
     *
     * @return  string  domain master
     * @throws Engine_Exception
     */

    public function get_domain_master()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->loaded)
            $this->_Load();

        $sambavalue = $this->_GetBoolean($this->shares['global']['domain master']['value']);

        if ($sambavalue === self::CONSTANT_ENABLED) {
            return TRUE;
        } else if ($sambavalue === self::CONSTANT_DEFAULT) {
            // From smb.conf man page:
            // If domain logons = yes, then the default behavior is to enable the domain master parameter. If domain
            // logons is not enabled (the default setting), then neither will domain master be enabled by default.
            return $this->GetDomainLogons();
        } else {
            return FALSE;
        }
    }

    /**
     * Returns domain SID.
     *
     *
     * @return string domain SID
     * @throws Engine_Exception, Samba_Not_Initialized_Exception
     */

    public function get_domain_sid()
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->ldaph == NULL)
            $this->_get_ldap_handle();

        if (! $this->IsDirectoryInitialized())
            throw new Samba_Not_Initialized_Exception();

        try {
            $result = $this->ldaph->Search(
                "(objectclass=sambaDomain)",
                $this->ldaph->GetBaseDn(),
                array("sambaDomainName", "sambaSID")
            );

            $entry = $this->ldaph->GetFirstEntry($result);

            if ($entry) {
                $attributes = $this->ldaph->GetAttributes($entry);
                $sid = $attributes['sambaSID'][0];
            } else {
                throw new Engine_Exception(LOCALE_LANG_ERRMSG_SYNTAX_ERROR . " - sambaDomainName", COMMON_ERROR);
            }

        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_ERROR);
        }

        return $sid;
    }

    /**
     * Gets network interfaces.
     *
     *
     * @return  string  network interfaces
     * @throws Engine_Exception
     */

    public function get_interfaces()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->loaded)
            $this->_Load();

        return $this->shares['global']['interfaces']['value'];
    }

    /**
     * Returns local master state.
     *
     *
     * @return string local master state
     * @throws Engine_Exception
     */

    public function get_local_master()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->loaded)
            $this->_Load();

        $sambavalue = $this->_GetBoolean($this->shares['global']['local master']['value']);

        if (($sambavalue === self::CONSTANT_ENABLED) || ($sambavalue === self::CONSTANT_DEFAULT))
            return TRUE;
        else
            return FALSE;
    }

    /**
     * Returns local SID.
     *
     *
     * @return string local SID
     * @throws Engine_Exception
     */

    public function get_local_sid()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $shell = new Shell();
            if ($shell->Execute(self::CMD_NET, 'getlocalsid', TRUE) != 0)
                throw Engine_Exception($shell->GetFirstOutputLine());

            $sid = $shell->GetLastOutputLine();
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_ERROR);
        }

        $sid = preg_replace("/.*: /", "", $sid);

        return $sid;
    }

    /**
     * Returns logon drive.
     *
     *
     * @return  string  logon drive
     * @throws Engine_Exception
     */

    public function get_logon_drive()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->loaded)
            $this->_Load();

        if (empty($this->shares['global']['logon drive']['value']))
            return "";
        else
            return $this->shares['global']['logon drive']['value'];
    }

    /**
     * Returns logon home.
     *
     *
     * @return  string  logon home
     * @throws Engine_Exception
     */

    public function get_logon_home()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->loaded)
            $this->_Load();

        if (empty($this->shares['global']['logon home']['value']))
            return "";
        else
            return $this->shares['global']['logon home']['value'];
    }

    /**
     * Returns logon path.
     *
     *
     * @return  string  logon path
     * @throws Engine_Exception
     */

    public function get_logon_path()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->loaded)
            $this->_Load();

        if (empty($this->shares['global']['logon path']['value']))
            return "";
        else
            return $this->shares['global']['logon path']['value'];
    }

    /**
     * Returns logon script.
     *
     *
     * @return  string  logon script
     * @throws Engine_Exception
     */

    public function get_logon_script()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->loaded)
            $this->_Load();

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
     *                    +  PDC  +  BDC  +  Simple
     * Preferred Master |   y   | auto  |    y
     *    Domain Master |   y   |   n   |    y
     *    Domain Logons |   y   |   y   |    n
     *         [netlogon] |   y   |   n   |    n
     *
     *
     * @return string mode
     * @throws Engine_Exception
     */

    public function get_mode()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $domainlogons = $this->GetDomainLogons();
            $preferredmaster = $this->GetPreferredMaster();
            $netlogoninfo = $this->GetShareInfo("netlogon");
        } catch (Samba_Share_Not_Found_Exception $e) {
            // Not fatal
        }

        $netlogon = (isset($netlogoninfo)) ? $netlogoninfo['available'] : FALSE;

        if ($preferredmaster && $domainlogons && $netlogon)
            return self::MODE_PDC;
        else if (!$preferredmaster && !$domainlogons && !$netlogon)
            return self::MODE_BDC;
        else if ($preferredmaster && $domainlogons && !$netlogon)
            return self::MODE_SIMPLE_SERVER;
        else
            return self::MODE_CUSTOM;
    }

    /**
     * Gets system/netbios name.
     *
     *
     * @return  string  system name
     * @throws Engine_Exception
     */

    public function get_netbios_name()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->loaded)
            $this->_Load();

        return $this->shares['global']['netbios name']['value'];
    }

    /**
     * Gets OS level.
     *
     *
     * @return  string  OS level
     * @throws Engine_Exception
     */

    public function get_os_level()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->loaded)
            $this->_Load();

        if (empty($this->shares['global']['os level']['value']))
            return Samba::DEFAULT_OS_LEVEL;
        else
            return $this->shares['global']['os level']['value'];
    }

    /**
     * Gets password program.
     *
     *
     * @return string password program
     * @throws Engine_Exception
     */

    public function get_password_chat()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->loaded)
            $this->_Load();

        if (empty($this->shares['global']['passwd chat']['value']))
            return "";
        else
            return $this->shares['global']['passwd chat']['value'];
    }

    /**
     * Gets password program.
     *
     *
     * @return string password program
     * @throws Engine_Exception
     */

    public function get_password_program()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->loaded)
            $this->_Load();

        if (empty($this->shares['global']['passwd program']['value']))
            return "";
        else
            return $this->shares['global']['passwd program']['value'];
    }

    /**
     * Gets preferred master.
     *
     *
     * @return  string  preferred master
     * @throws Engine_Exception
     */

    public function get_preferred_master()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->loaded)
            $this->_Load();

        $sambavalue = $this->_GetBoolean($this->shares['global']['preferred master']['value']);

        if ($sambavalue === self::CONSTANT_ENABLED) {
            return TRUE;
        } else if ($sambavalue === self::CONSTANT_DEFAULT) {
            // TODO: man page is unclear about the default behavior
            return FALSE;
        } else {
            return FALSE;
        }

        return $this->shares['global']['preferred master']['value'];
    }

    /**
     * Gets printing share information.
     *
     *
     * @return array information about printers
     * @throws Engine_Exception
     */

    public function get_printing_info()
    {
        clearos_profile(__METHOD__, __LINE__);

        $info = array();

        if ($this->ShareExists("printers")) {
            $info['printers'] = $this->GetShareInfo("printers");
            $info['enabled'] = (isset($info['printers']['available']) && $info['printers']['available']) ? TRUE : FALSE;
        } else {
            $info['enabled'] = FALSE;
        }

        if ($this->ShareExists("print$"))
            $info['print$'] = $this->GetShareInfo("print$");

        return $info;
    }

    /**
     * Returns roaming profiles state.
     *
     *
     * @return boolean state of roaming profiles
     * @throws Engine_Exception
     */

    public function get_roaming_profiles_state()
    {
        clearos_profile(__METHOD__, __LINE__);

        // TODO: this information should really come from LDAP, not smb.conf

        try {
            $info = $this->GetShareInfo("profiles");
        } catch (Samba_Share_Not_Found_Exception $e) {
            return FALSE;
        }

        if (! isset($info['available']) || $info['available'])
            return TRUE;
        else
            return FALSE;
    }

    /**
     * Gets security type.
     *
     *
     * @return  string  security type
     * @throws Engine_Exception
     */

    public function get_security()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->loaded)
            $this->_Load();

        return $this->shares['global']['security']['value'];
    }

    /**
     * Gets server string.
     *
     *
     * @return  string  server string
     * @throws Engine_Exception
     */

    public function get_server_string()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->loaded)
            $this->_Load();

        return $this->shares['global']['server string']['value'];
    }

    /**
     * Get shares.
     *
     *
     * @return  array  list of shares
     * @throws Engine_Exception
     */

    public function get_shares()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->loaded)
            $this->_Load();

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
            $this->_Load();

        if (! $this->ShareExists($share))
            throw new Samba_Share_Not_Found_Exception($share);

        $info = array();

        foreach ($this->shares[$share] as $key => $value) {
            if ($key == "line") // skip comments, blank lines
                continue;

            if (in_array($key, $this->booleans)) {
                $boolvalue = $this->_GetBoolean($value['value']);

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

        if ($this->IsValidSpecialShare($share))
            $info['special'] = TRUE;
        else
            $info['special'] = FALSE;

        return $info;
    }

    /**
     * Get list of special shares - homes, printers, etc.
     *
     *
     * @return  array  list of special shares
     */

    public function get_special_share_defaults()
    {
        clearos_profile(__METHOD__, __LINE__);

        $shareinfo = array();
        $sharelist = array();

        $shareinfo = array();
        $shareinfo['name'] = 'homes';
        $shareinfo['browseable'] = 'No';
        $shareinfo['read only'] = 'No';
        $shareinfo['valid users'] = '%D\%S';
        $shareinfo['available'] = 'Yes';
        $shareinfo['path'] = '/home/%U';
        $shareinfo['comment'] = SAMBA_LANG_HOMES;
        $sharelist[] = $shareinfo;

        $shareinfo = array();
        $shareinfo['name'] = 'netlogon';
        $shareinfo['browseable'] = 'No';
        $shareinfo['locking'] = 'No';
        $shareinfo['read only'] = 'No';
        $shareinfo['available'] = 'Yes';
        $shareinfo['path'] = "/var/samba/netlogon";
        $shareinfo['comment'] = SAMBA_LANG_NETLOGON;
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
        $shareinfo['comment'] = SAMBA_LANG_PROFILES;
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
        $shareinfo['comment'] = SAMBA_LANG_SHARE_PRINTERS;
        $sharelist[] = $shareinfo;

        $shareinfo = array();
        $shareinfo['name'] = 'print$';
        $shareinfo['browseable'] = 'No';
        $shareinfo['read only'] = 'No';
        $shareinfo['available'] = 'No';
        $shareinfo['path'] = "/var/samba/drivers";
        $shareinfo['comment'] = SAMBA_LANG_PRINTER_DRIVERS;
        $sharelist[] = $shareinfo;

        $shareinfo = array();
        $shareinfo['name'] = 'ftpsite';
        $shareinfo['public'] = 'Yes';
        $shareinfo['writable'] = 'Yes';
        $shareinfo['guest only'] = 'Yes';
        $shareinfo['browseable'] = 'Yes';
        $shareinfo['available'] = 'No';
        $shareinfo['path'] = "/var/ftp";
        $shareinfo['comment'] = SAMBA_LANG_SHARE_FTP;
        $sharelist[] = $shareinfo;

        return $sharelist;
    }

    /**
     * Gets WINS server.
     *
     *
     * @return  string  WINS server
     * @throws Engine_Exception
     */

    public function get_wins_server()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->loaded)
            $this->_Load();

        return $this->shares['global']['wins server']['value'];
    }

    /**
     * Gets WINS support.
     *
     *
     * @return  boolean  TRUE if WINS support is enabled
     * @throws Engine_Exception
     */

    public function get_wins_support()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->loaded)
            $this->_Load();

        $sambavalue = $this->_GetBoolean($this->shares['global']['wins support']['value']);

        if ($sambavalue === self::CONSTANT_ENABLED)
            return TRUE;
        else
            return FALSE;
    }

    /**
     * Initializes master node with the necessary Samba elements.
     *
     * You do not need to have the server components of Samba installed
     * to run this initialization routine.  This simply initializes the
     * necessary bits to get LDAP up and running.
     *
     * @param string $domain workgroup / domain
     * @param string $password password for winadmin
     * @param boolean $force force initialization
     *
     * @return void
     * @throws Engine_Exception
     */

    public function initialize_directory($domain, $password = NULL, $force = FALSE)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->IsDirectoryInitialized() && !$force)
            return;

        // Shutdown Samba daemons if they are installed/running

        $nmbd = new Nmbd();
        $winbind = new Winbind();

        $nmbd_wasrunning = FALSE;
        $smbd_wasrunning = FALSE;
        $winbind_wasrunning = FALSE;

        if ($winbind->IsInstalled()) {
            $winbind_wasrunning = $winbind->GetRunningState();
            if ($winbind_wasrunning)
                $winbind->SetRunningState(FALSE);
        }

        if ($this->IsInstalled()) {
            $smbd_wasrunning = $this->GetRunningState();
            if ($smbd_wasrunning)
                $this->SetRunningState(FALSE);
        }

        if ($nmbd->IsInstalled()) {
            $nmbd_wasrunning = $nmbd->GetRunningState();
            if ($nmbd_wasrunning)
                $nmbd->SetRunningState(FALSE);
        }

        // FIXME -- is this necessary?
        // $this->SetWorkgroup($domain);

        // Archive the files (usually in /var/lib/samba)
        $this->_ArchiveStateFiles();

        // Bootstrap the domain SID
        $domainsid = $this->_InitializeDomainSid();

        // Set local SID to be the same as domain SID
        $this->_InitializeLocalSid($domainsid);

        // Implant all the Samba elements into LDAP
        $this->_InitializeLdap($domainsid, $password);

        // Save the LDAP password into secrets
        $this->_SaveBindPassword();

        // Restart Samba if it was running 
        if ($nmbd_wasrunning)
            $nmbd->SetRunningState(TRUE);

        if ($smbd_wasrunning)
            $this->SetRunningState(TRUE);

        if ($winbind_wasrunning)
            $winbind->SetRunningState(TRUE);
    }

    /**
     * Initializes the local Samba system environment.
     *
     * @param string $netbiosname netbiosname
     * @param string $domain domain
     * @param string $password password for winadmin
     *
     * @return void
     * @throws Engine_Exception
     */

    public function initialize_local_system($netbiosname, $domain, $password)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Initialize directory if it has not already been don
        if (! $this->IsDirectoryInitialized())
            $this->InitializeDirectory($domain);

        // Set the winadmin password
        $user = new User(Samba::CONSTANT_WINADMIN_USERNAME);
        $user->ResetPassword($password, $password, "directory_initialize");

        // Set the netbios name and workgroup
        $this->SetNetbiosName($netbiosname);
        $this->SetWorkgroup($domain);

        // TODO: assuming PDC mode for now
        $this->SetMode(Samba::MODE_PDC);

        // Save the LDAP password
        $this->_SaveBindPassword();

        // Save the winbind password
        $this->_SaveIdmapPassword();

        // Set the domain SID
        $this->SetDomainSid();

        // Samba needs to be running for the next steps
        $nmbd = new Nmbd();
        $nmbd_wasrunning = $nmbd->GetRunningState();
        $wasrunning = $this->GetRunningState();

        if (! $wasrunning)
            $this->SetRunningState(TRUE);

        if (! $nmbd_wasrunning)
            $nmbd->SetRunningState(TRUE);

        sleep(3); // TODO: Wait for samba ... replace this with a loop

        try {
            // Grant default privileges to winadmin et al
            $this->_NetGrantDefaultPrivileges($password);

            // If PDC, join the local system to itself
            $this->_NetRpcJoin($password);
        } catch (Exception $e) {
            if (! $wasrunning)
                $this->SetRunningState(FALSE);
            if (! $nmbd_wasrunning)
                $nmbd->SetRunningState(FALSE);
            // TODO: too delicate?
            throw new Engine_Exception($e->GetMessage(), COMMON_ERROR);
        }

        // Stop samba if it was not running
        try {
            if (! $wasrunning)
                $this->SetRunningState(FALSE);
            if (! $nmbd_wasrunning)
                $nmbd->SetRunningState(FALSE);
        } catch (Exception $e) {
            // Not fatal
        }

        $this->UpdateLocalFilePermissions();

        // Set the local system initialized flag
        $this->SetLocalSystemInitialized(TRUE);
    }

    /**
     * Checks to see if Samba has been initialized in LDAP.
     *
     *
     * @return boolean TRUE if Samba has been initialized in LDAP
     * @throws Engine_Exception, SambaDirectoryUnavailableException
     */

    public function is_directory_initialized()
    {
        clearos_profile(__METHOD__, __LINE__);

        $directory = new ClearDirectory();

        // FIXME: do we need this exception?
        if (!$directory->IsAvailable())
            throw new SambaDirectoryUnavailableException();

        if ($this->ldaph == NULL)
            $this->_get_ldap_handle();

        try {
            $result = $this->ldaph->Search(
                "(objectclass=sambaDomain)",
                $this->ldaph->GetBaseDn(),
                array("sambaDomainName", "sambaSID")
            );

            $entry = $this->ldaph->GetFirstEntry($result);
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_ERROR);
        }

        if ($entry)
            return TRUE;
        else
            return FALSE;
    }

    /**
     * Checks to see if local Samba system had been initialized.
     *
     *
     * @return boolean TRUE if local Samba system has been initialized
     * @throws Engine_Exception
     */

    public function is_local_system_initialized()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->IsDirectoryInitialized())
            throw new Samba_Not_Initialized_Exception();

        try {
            $file = new File(Samba::FILE_LOCAL_SYSTEM_INITIALIZED);
            if ($file->exists())
                return TRUE;
            else
                return FALSE;
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_ERROR);
        }
    }

    /**
     * Gets workgroup name.
     *
     *
     * @return  string  workgroup name
     * @throws Engine_Exception
     */

    public function get_workgroup()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->loaded)
            $this->_Load();

        return $this->shares['global']['workgroup']['value'];
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

        $this->_SetShareInfo('global', 'add machine script', $script);
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

        if (! is_bool($state))
            throw new Validation_Exception(LOCALE_LANG_INVALID . " - " . SAMBA_LANG_DOMAIN_LOGONS);

        $state_value = ($state) ? 'Yes' : 'No';

        $this->_SetShareInfo('global', 'domain logons', $state_value);
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

        if (! is_bool($state))
            throw new Validation_Exception(SAMBA_LANG_ERRMSG_DOMAINMASTER_INVALID);

        $state_value = ($state) ? 'Yes' : 'No';

        $this->_SetShareInfo('global', 'domain master', $state_value);
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

        try {
            if (empty($sid)) {
                $file = new File(self::FILE_DOMAIN_SID, TRUE);
                if ($file->exists()) {
                    $lines = $file->GetContentsAsArray();
                    $sid = $lines[0];
                }
            }

            if (! empty($sid)) {
                $shell = new Shell();
                $shell->Execute(self::CMD_NET, 'setdomainsid ' . $sid, TRUE);
            }
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_ERROR);
        }
    }

    /**
     * Sets network interfaces.
     *
     * @param  string  $interfaces  network interfaces
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_interfaces($interfaces)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->IsValidInterfaces($interfaces))
            throw new Validation_Exception(SAMBA_LANG_ERRMSG_INTERFACES_INVALID);

        $this->_SetShareInfo('global', 'interfaces', $interfaces);
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

        if (! is_bool($state))
            throw new Validation_Exception(SAMBA_LANG_ERRMSG_LOCALMASTER_INVALID);

        $state_value = ($state) ? 'Yes' : 'No';

        $this->_SetShareInfo('global', 'local master', $state_value);
    }

    /**
     * Sets local SID.
     *
     * @param string $sid local SID
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_local_sid($sid = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            if (empty($sid))
                $localsid = $this->GetDomainSid();

            $shell = new Shell();
            $shell->Execute(self::CMD_NET, 'setlocalsid ' . $localsid, TRUE);
        } catch (Exception $e) {
            // Ignore for now
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

        if (! $this->IsValidLogonDrive($drive))
            throw new Validation_Exception(LOCALE_LANG_INVALID . " - " . SAMBA_LANG_LOGON_DRIVE);

        $this->_SetShareInfo('global', 'logon drive', $drive);

        // TODO: if (master)
        $this->SetLogonDriveLdap($drive);
    }

    /**
     * Sets logon drive for users in LDAP.
     *
     * @param string $drive logon drive
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_logon_drive_ldap($drive)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->ldaph == NULL)
            $this->_get_ldap_handle();

        try {
            $result = $this->ldaph->Search(
                "(objectclass=sambaSamAccount)",
                ClearDirectory::GetUsersOu(),
                array("cn", "dn")
            );

            $entry = $this->ldaph->GetFirstEntry($result);
            $ldapdrive['sambaHomeDrive'] = $drive;

            while ($entry) {
                $attributes = $this->ldaph->GetAttributes($entry);
                $this->ldaph->Modify('cn=' . $attributes['cn'][0] . "," . ClearDirectory::GetUsersOu(), $ldapdrive);
                $entry = $this->ldaph->NextEntry($entry);
            }
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_ERROR);
        }
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

        if (! $this->IsValidLogonHome($home))
            throw new Validation_Exception(LOCALE_LANG_INVALID . " - " . SAMBA_LANG_LOGON_HOME);

        $this->_SetShareInfo('global', 'logon home', $home);

        // TODO: if (master)
        $this->SetLogonHomeLdap();
    }

    /**
     * Sets logon home for users in LDAP.
     *
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_logon_home_ldap()
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->ldaph == NULL)
            $this->_get_ldap_handle();

        try {
            $result = $this->ldaph->Search(
                "(objectclass=sambaSamAccount)",
                ClearDirectory::GetUsersOu(),
                array("cn", "uid")
            );

            // TODO: this should be the PDC name, not the local server
            $pdc = $this->GetNetbiosName();
            $entry = $this->ldaph->GetFirstEntry($result);

            while ($entry) {
                $attributes = $this->ldaph->GetAttributes($entry);
                $ldaphome['sambaHomePath'] = '\\\\' . $pdc . '\\' . $attributes['uid'][0];
                $this->ldaph->Modify('cn=' . $attributes['cn'][0] . "," . ClearDirectory::GetUsersOu() , $ldaphome);
                $entry = $this->ldaph->NextEntry($entry);
            }
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_ERROR);
        }
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

        if (! $this->IsValidLogonPath($path))
            throw new Validation_Exception(LOCALE_LANG_INVALID . " - " . SAMBA_LANG_LOGON_PATH);

        // TODO: setting an empty path will delete "logon path"... that's not what
        // we want right now.  Set it to a space for now.  Yes, a kludge.
        $temppath = empty($path) ? " " : $path;

        $this->_SetShareInfo('global', 'logon path', $temppath);

        // TODO: if (master)
        $this->SetLogonPathLdap($path);
    }

    /**
     * Sets logon path (profiles) for users in LDAP.
     *
     * @param string $path logon path
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_logon_path_ldap($path)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->ldaph == NULL)
            $this->_get_ldap_handle();

        try {
            $result = $this->ldaph->Search(
                "(objectClass=sambaSamAccount)",
                ClearDirectory::GetUsersOu(),
                array("cn", "uid")
            );

            // TODO: this should be the PDC name, not the local server
            $pdc = $this->GetNetbiosName();
            $entry = $this->ldaph->GetFirstEntry($result);
            $usersou = ClearDirectory::GetUsersOu();

            while ($entry) {
                $attributes = $this->ldaph->GetAttributes($entry);

                if ($path)
                    $newattrs['sambaProfilePath'] = '\\\\' . $pdc . '\\profiles\\' . $attributes['uid'][0];
                else
                    $newattrs['sambaProfilePath'] = array();

                $this->ldaph->Modify('cn=' . $attributes['cn'][0] . "," . $usersou , $newattrs);
                $entry = $this->ldaph->NextEntry($entry);
            }
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_ERROR);
        }
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

        if (! $this->IsValidLogonScript($script))
            throw new Validation_Exception(LOCALE_LANG_INVALID . " - " . SAMBA_LANG_LOGON_SCRIPT);

        $this->_SetShareInfo('global', 'logon script', $script);
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

        // FIXME: move this somewhere else
        $this->SetLogonHome('\\\\%L\%U');

        if ($mode == self::MODE_PDC) {
            $this->SetDomainLogons(TRUE);
            $this->SetDomainMaster(TRUE);
            $this->SetPreferredMaster(TRUE);
            $this->SetShareAvailability('netlogon', TRUE);
            $this->SetSecurity(Samba::SECURITY_USER);
        } else if ($mode == self::MODE_BDC) {
            $this->SetDomainLogons(FALSE);
            $this->SetDomainMaster(FALSE);
            $this->SetPreferredMaster(FALSE);
            $this->SetSecurity(Samba::SECURITY_DOMAIN);
            $this->SetShareAvailability('netlogon', FALSE);
            $this->SetShareAvailability('profiles', FALSE);
            $this->SetRoamingProfilesState(FALSE);
        } else if ($mode == self::MODE_SIMPLE_SERVER) {
            $this->SetDomainLogons(TRUE);
            $this->SetDomainMaster(TRUE);
            $this->SetPreferredMaster(TRUE);
            $this->SetShareAvailability('netlogon', FALSE);
            $this->SetShareAvailability('profiles', FALSE);
            $this->SetRoamingProfilesState(FALSE);
            $this->SetSecurity(Samba::SECURITY_USER);
        } else {
            throw new Validation_Exception(LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - mode");
        }
    }

    /**
     * Sets system/netbios name.
     *
     * @param  string  netbiosname     system name
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_netbios_name($netbiosname)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->IsValidNetbiosName($netbiosname))
            return;

        // Change smb.conf
        $this->_SetShareInfo('global', 'netbios name', $netbiosname);

        // Update LDAP users
        // TODO: changing the netbios name means we have to change the relevant
        // entries in smb.conf and LDAP.  Do this in a more elegant way.
        if ($this->IsDirectoryInitialized()) {
            $reset = $this->GetRoamingProfilesState();
            $this->SetRoamingProfilesState($reset);
            $this->SetLogonHome('\\\\%L\%U');
        }

        // Clean up secrets file
        $this->_CleanSecretsFile();
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

        if (! $this->IsValidOsLevel($oslevel))
            throw new Validation_Exception(SAMBA_LANG_ERRMSG_OS_LEVEL_INVALID);

        $this->_SetShareInfo('global', 'os level', $oslevel);
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

        $this->_SetShareInfo('global', 'passwd chat', $chat);
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

        $this->_SetShareInfo('global', 'passwd program', $program);
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

        if (! is_bool($state))
            throw new Validation_Exception(SAMBA_LANG_ERRMSG_PREFERREDMASTER_INVALID);

        $state_value = ($state) ? 'Yes' : 'No';

        $this->_SetShareInfo('global', 'preferred master', $state_value);
    }

    /**
     * Sets printing info.
     *
     * @param string $mode print mode
     * @param array $info printing configuration information
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_printing_mode($mode)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($mode == self::PRINTING_DISABLED) {
            $this->_SetShareInfo('printers', 'available', 'No');
            $this->_SetShareInfo('print$', 'available', 'No');
        } else if ($mode == self::PRINTING_POINT_AND_CLICK) {
            $this->_SetShareInfo('printers', 'cups options', '');
            $this->_SetShareInfo('printers', 'use client driver', 'No');
            $this->_SetShareInfo('printers', 'available', 'Yes');
            $this->_SetShareInfo('print$', 'available', 'Yes');
        } else if ($mode == self::PRINTING_RAW) {
            $this->_SetShareInfo('printers', 'cups options', 'raw');
            $this->_SetShareInfo('printers', 'use client driver', 'Yes');
            $this->_SetShareInfo('printers', 'available', 'Yes');
            $this->_SetShareInfo('print$', 'available', 'Yes');
        }
    }

    /**
     * Sets security type.
     *
     * @param  string  $type  security type
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_security($type)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validate
        //---------

        if (! $this->IsValidSecurity($type))
            throw new Validation_Exception(SAMBA_LANG_ERRMSG_SECURITY_INVALID);

        $this->_SetShareInfo('global', 'security', $type);
    }

    /**
     * Sets server string.
     *
     * @param  string  $serverstring  server string
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_server_string($serverstring)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validate
        //---------

        if (! $this->IsValidServerString($serverstring))
            throw new Validation_Exception(SAMBA_LANG_ERRMSG_SERVERSTRING_INVALID);

        $this->_SetShareInfo('global', 'server string', $serverstring);
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

        if (! is_bool($state))
            throw new Validation_Exception(LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - state");

        try {
            $file = new File(Samba::FILE_LOCAL_SYSTEM_INITIALIZED);

            if ($state) {
                if (! $file->exists())
                    $file->create("root", "root", "0644");
            } else {
                if ($file->exists())
                    $file->Delete();
            }
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_ERROR);
        }
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

        $this->SetLogonPath($path);
        $this->SetShareAvailability('profiles', $profiles);
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

        $this->_SetShareInfo($share, 'available', $state_value);
    }

    /**
     * Sets WINS server and support.
     *
     * @param string $winsserver WINS server
     * @param boolean $is_wins WINS support flag
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_wins_server_and_support($winsserver, $is_wins)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validate
        //---------

        if (! $this->IsValidWinsServer($winsserver))
            throw new Validation_Exception(SAMBA_LANG_ERRMSG_WINSSERVER_INVALID);

        if (! is_bool($is_wins))
            throw new Validation_Exception(SAMBA_LANG_ERRMSG_WINSSUPPORT_INVALID);

        // You cannot have "wins server" and "wins support" at the same time
        if ($is_wins && $winsserver)
            throw new Validation_Exception(SAMBA_LANG_ERRMSG_WINS_INVALID);

        // WINS support
        //-------------

        $is_wins_param = ($is_wins) ? 'Yes' : 'No';

        $this->_SetShareInfo('global', 'wins support', $is_wins_param);
        $this->_SetShareInfo('global', 'wins server', trim($winsserver));
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

        if (! is_bool($state))
            throw new Validation_Exception(LOCALE_LANG_INVALID . " - " . "unix password sync");

        $state_val = ($state) ? 'Yes' : 'No';

        $this->_SetShareInfo('global', 'unix password sync', $state_val);
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

        if (! $this->IsValidWorkgroup($workgroup))
            return;

        $workgroup = strtoupper($workgroup);

        // Change smb.conf
        $this->_SetShareInfo('global', 'workgroup', $workgroup);

        // Update LDAP users
        if ($this->IsDirectoryInitialized())    
            $this->SetWorkgroupLdap($workgroup);

        // Clean up secrets file
        $this->_CleanSecretsFile();
    }

    /**
     * Sets workgroup/domain name LDAP objects.
     *
     * @param string $workgroup workgroup name
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_workgroup_ldap($workgroup)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->ldaph == NULL)
            $this->_get_ldap_handle();

        // Update sambaDomainName object
        //------------------------------

        try {
            $sid = $this->GetDomainSid();
        } catch (Samba_Not_Initialized_Exception $e) {
            return;
        }

        try {
            $result = $this->ldaph->Search(
                "(sambaSID=$sid)",
                $this->ldaph->GetBaseDn(),
                array("sambaDomainName")
            );

            $entry = $this->ldaph->GetFirstEntry($result);

            if ($entry) {
                $attributes = $this->ldaph->GetAttributes($entry);

                if ($workgroup != $attributes['sambaDomainName'][0]) {
                    $new_rdn = "sambaDomainName=" . $workgroup;
                    $new_dn = $new_rdn . "," . $this->ldaph->GetBaseDn();
                    $old_dn = "sambaDomainName=" . $attributes['sambaDomainName'][0] . "," . $this->ldaph->GetBaseDn();
                    $newattrs['sambaDomainName'] = $workgroup;

                    $this->ldaph->Rename($old_dn, $new_rdn);
                    $this->ldaph->Modify($new_dn , $newattrs);
                }
            }
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_ERROR);
        }

        // Update sambaDomain attribute for all users
        //-------------------------------------------

        try {
            $result = $this->ldaph->Search(
                "(sambaDomainName=*)",
                ClearDirectory::GetUsersOu(),
                array("cn")
            );

            $entry = $this->ldaph->GetFirstEntry($result);
            $usersou = ClearDirectory::GetUsersOu();
            $newattrs['sambaDomainName'] = $workgroup;

            while ($entry) {
                $attributes = $this->ldaph->GetAttributes($entry);
                $this->ldaph->Modify('cn=' . $attributes['cn'][0] . "," . $usersou , $newattrs);
                $entry = $this->ldaph->NextEntry($entry);
            }
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_ERROR);
        }
    }

    /**
     * Unlocks a user account.
     *
     * @param string $username username
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function unlock_account($username)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $shell = new Shell();
            $exitcode = $shell->Execute(self::CMD_PDBEDIT, '-c "[]" -z -u ' . $username, TRUE);

            if ($exitcode != 0)
                throw new Engine_Exception("unlock failed: " . $shell->GetFirstOutputLine(), COMMON_WARNING);
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_WARNING);
        }
    }

    /**
     * Updates existing groups with Windows Networking group information (mapping).
     *
     * The ClearDirectory is designed to work without the Windows Networking
     * overlay.  When Windows Networking is enabled, we need to go through all the
     * existing groups and add the required Windows fields.
     *
     * @param string $domainsid domain SID
     *
     * @return void
     * @throws Engine_Exception, Samba_Not_Initialized_Exception
     */

    public function update_directory_group_mappings($domainsid = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->ldaph == NULL)
            $this->_get_ldap_handle();

        if (empty($domainsid))
            $domainsid = $this->GetDomainSid();

        $group_ou = ClearDirectory::GetGroupsOu();

        try {
            $groupmanager = new GroupManager();
            $grouplist = $groupmanager->GetGroupList();
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_WARNING);
        }

        // Add/Update the groups
        //--------------------------------------------------------

        $attributes['objectClass'] = array(
            'top',
            'posixGroup',
            'groupOfNames',
            'sambaGroupMapping'
        );

        foreach ($grouplist as $groupinfo) {
            if (isset($groupinfo['sambaSID']))
                continue;

            $dn = "cn=" . Ldap::DnEscape($groupinfo['group']) . "," . $group_ou;
            $attributes['sambaSID'] = $domainsid . '-' . $groupinfo['gid'];
            $attributes['sambaGroupType'] = 2;
            $attributes['displayName'] = $groupinfo['group'];
 
            try {
                if ($this->ldaph->Exists($dn))
                    $this->ldaph->Modify($dn, $attributes);
            } catch (Exception $e) {
                throw new Engine_Exception($e->GetMessage(), COMMON_WARNING);
            }
        }

        // For good measure, update local file permissions too
        $this->UpdateLocalFilePermissions();
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
            $shell->Execute(self::CMD_ADD_SAMBA_DIRS, '', TRUE);
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_ERROR);
        }
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   R O U T I N E S
    ///////////////////////////////////////////////////////////////////////////////

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
     * Validation routine for machine/computers.
     *
     * @param string $name machine name
     *
     * @return boolean TRUE if machine name valid
     */

    public function is_valid_machine_name($name)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (preg_match('/^([a-z0-9_\-\.]+)\$$/', $name))
            return TRUE;
        else
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
     * Validation routine for netbiosname
     *
     * @param  string  $netbiosname  system name
     *
     * @return  boolean  TRUE if netbiosname is valid
     */

    public function is_valid_netbios_name($netbiosname)
    {
        clearos_profile(__METHOD__, __LINE__);

        $isvalid = TRUE;

        if (! (preg_match("/^([a-zA-Z][a-zA-Z0-9\-]*)$/", $netbiosname) && (strlen($netbiosname) <= 15))) {
            $this->AddValidationError(SAMBA_LANG_ERRMSG_NETBIOS_NAME_INVALID, __METHOD__ ,__LINE__);
            $isvalid = FALSE;
        }

        try {
            $workgroup = strtoupper($this->GetWorkgroup());
            $netbiosname = strtoupper($netbiosname);
        } catch (Exception $e) {
            $this->AddValidationError(LOCALE_LANG_ERRMSG_WEIRD, __METHOD__ ,__LINE__);
            $isvalid = FALSE;
        }

        if ($workgroup == $netbiosname) {
            $this->AddValidationError(SAMBA_LANG_ERRMSG_SERVER_NAME_AND_DOMAIN_DUPLICATE, __METHOD__ ,__LINE__);
            $isvalid = FALSE;
        }

        return $isvalid;
    }

    /**
     * Validation routine for share name
     *
     * @param  string  $name  share name name
     *
     * @return  boolean  TRUE if share name is valid
     */

    public function is_valid_share($name)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (preg_match("/^([a-zA-Z\-$]+)$/", $name))
            return TRUE;

        $this->AddValidationError(SAMBA_LANG_SHARE . " - " . LOCALE_LANG_INVALID, __METHOD__ ,__LINE__);
        return FALSE;
    }

    /**
     * Validation routine for special share name
     *
     * @param  string  $name  special share name name
     *
     * @return  boolean  TRUE if special share name is valid
     */

    public function is_valid_special_share($name)
    {
        clearos_profile(__METHOD__, __LINE__);

        $sharedata = array();
        $sharedata = $this->GetSpecialShareDefaults();

        foreach ($sharedata as $shareinfo) {
            if ($shareinfo["name"] == $name)
                return TRUE;
        }

        $this->AddValidationError(SAMBA_LANG_SHARE . " - " . LOCALE_LANG_INVALID, __METHOD__ ,__LINE__);

        return FALSE;
    }

    /**
     * Validation routine for workgroup
     *
     * To avoid issues on Windows networks:
     * - the netbiosname and workgroup must be different
     * - the host nickname (left-side of the hostname) must not match the workgroup
     *
     * @param  string  $workgroup  workgroup name
     *
     * @return  boolean  TRUE if workgroup is valid
     */

    public function is_valid_workgroup($workgroup)
    {
        clearos_profile(__METHOD__, __LINE__);

        $isvalid = TRUE;

        if (! (preg_match("/^([a-zA-Z][a-zA-Z0-9\-]*)$/", $workgroup) && (strlen($workgroup) <= 15))) {
            $this->AddValidationError(SAMBA_LANG_ERRMSG_WORKGROUP_INVALID, __METHOD__ ,__LINE__);
            $isvalid = FALSE;
        }

        try {
            $netbiosname = $this->GetNetbiosName();

            $hostnameobj = new Hostname();
            $hostname = $hostnameobj->Get();
            $nickname = preg_replace("/\..*/", "", $hostname);

            $nickname = strtoupper($nickname);
            $netbiosname = strtoupper($netbiosname);
            $workgroup = strtoupper($workgroup);
        } catch (Exception $e) {
            $this->AddValidationError(LOCALE_LANG_ERRMSG_WEIRD, __METHOD__ ,__LINE__);
            $isvalid = FALSE;
        }

        if ($workgroup == $netbiosname) {
            $this->AddValidationError(SAMBA_LANG_ERRMSG_SERVER_NAME_AND_DOMAIN_DUPLICATE, __METHOD__ ,__LINE__);
            $isvalid = FALSE;
        }

        if ($workgroup == $nickname) {
            $this->AddValidationError(SAMBA_LANG_ERRMSG_DOMAIN_AND_HOSTNAME_DUPLICATE, __METHOD__ ,__LINE__);
            $isvalid = FALSE;
        }

        return $isvalid;
    }

    /**
     * Validation routine for security
     *
     * @param  string  $type  security type
     *
     * @return  boolean  TRUE if type is valid
     */

    public function is_valid_security($type)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (
            ($type == self::SECURITY_USER) ||
            ($type == self::SECURITY_SHARE) ||
            ($type == self::SECURITY_DOMAIN)
           )
            return TRUE;
        $this->AddValidationError(SAMBA_LANG_ERRMSG_SECURITY_INVALID, __METHOD__ ,__LINE__);
        return FALSE;
    }

    /**
     * Validation routine for serverstring
     *
     * @param  string  $serverstring  server string
     *
     * @return  boolean  TRUE if serverstring is valid
     */

    public function is_valid_server_string($serverstring)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (preg_match("/^([a-zA-Z][\-\w ]*)$/", $serverstring))
            return TRUE;
        $this->AddValidationError(SAMBA_LANG_ERRMSG_SERVERSTRING_INVALID, __METHOD__ ,__LINE__);
        return FALSE;
    }

    /**
     * Validation routine for interfaces
     *
     * @param  string  $interfaces  network interfaces
     *
     * @return  boolean  TRUE if interfaces is valid
     */

    public function is_valid_interfaces($interfaces)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (preg_match("/^([a-zA-Z0-9 ]*)$/", $interfaces))
            return TRUE;
        $this->AddValidationError(SAMBA_LANG_ERRMSG_INTERFACES_INVALID, __METHOD__ ,__LINE__);
        return FALSE;
    }

    /**
     * Validation routine for winsserver
     *
     * @param  string  $winsserver  WINS server
     *
     * @return  boolean  TRUE if winsserver is valid
     */

    public function is_valid_wins_server($winsserver)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (preg_match("/^([a-zA-Z0-9\-\.]*)$/", $winsserver))
            return TRUE;
        $this->AddValidationError(SAMBA_LANG_ERRMSG_WINSSERVER_INVALID, __METHOD__ ,__LINE__);
        return FALSE;
    }

    /**
     * Validation routine for WINS support
     *
     * @param  string  $state  state
     *
     * @return  boolean  TRUE if valid
     */

    public function is_valid_wins_support($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (is_bool($state))
            return TRUE;

        $this->AddValidationError(SAMBA_LANG_ERRMSG_WINSSUPPORT_INVALID, __METHOD__ ,__LINE__);

        return FALSE;
    }

    /**
     * Validation routine for logon drive
     *
     * @param  string  $drive  drive
     *
     * @return  boolean  TRUE if valid
     */

    public function is_valid_logon_drive($drive)
    {
        clearos_profile(__METHOD__, __LINE__);

        // TODO: tighten this up
        return TRUE;
        /*
        if (preg_match("/^[a-zA-Z]:$/", $drive))
            return TRUE;
        else
            return FALSE;
        */
    }

    /**
     * Validation routine for logon home
     *
     * @param  string  $home  home
     *
     * @return  boolean  TRUE if valid
     */

    public function is_valid_logon_home($home)
    {
        clearos_profile(__METHOD__, __LINE__);

        // TODO: tighten this up
        return TRUE;
    }

    /**
     * Validation routine for logon path
     *
     * @param  string  $path  path
     *
     * @return  boolean  TRUE if valid
     */

    public function is_valid_logon_path($path)
    {
        clearos_profile(__METHOD__, __LINE__);

        // TODO: tighten this up
        return TRUE;
    }

    /**
     * Validation routine for logon script
     *
     * @param  string  $script  script
     *
     * @return  boolean  TRUE if valid
     */

    public function is_valid_logon_script($script)
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

    public function is_valid_os_level($oslevel)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!$oslevel)
            return TRUE;

        if (preg_match("/^([0-9]+)$/", $oslevel))
            return TRUE;

        $this->AddValidationError(SAMBA_LANG_ERRMSG_OS_LEVEL_INVALID, __METHOD__ ,__LINE__);
        return FALSE;
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Deletes state files used by Samba.
     *
     * @access private
     *
     * @return void
     */

    protected function _archive_state_files()
    {
        clearos_profile(__METHOD__, __LINE__);

        // Create backup directory

        try {
            $ntptime = new NTP_Time();
            date_default_timezone_set($ntptime->GetTimeZone());

            $backuppath = Samba::PATH_STATE_BACKUP . "/varbackup-" . strftime("%m-%d-%Y-%H-%M-%S-%s", time());
            $backupdir = new Folder($backuppath);

            if (! $backupdir->Exists())
                $backupdir->Create("root", "root", "0755");
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_WARNING);
        }

        // Perform backup

        try {
            $folder = new Folder(Samba::PATH_STATE);
            $statefiles = $folder->GetRecursiveListing();

            foreach ($statefiles as $filename) {
                if (! preg_match("/(tdb)|(dat)$/", $filename))
                    continue;

                if (preg_match("/\//", $filename)) {
                    $dirname = dirname($filename);
                    $backupdir = new Folder($backuppath . "/" . $dirname);
    
                    if (! $backupdir->Exists())
                        $backupdir->Create("root", "root", "0755");
                }

                $file = new File(Samba::PATH_STATE . "/" . $filename);
                $file->MoveTo($backuppath . "/" . $filename);
            }
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_WARNING);
        }
    }

    /**
     * Generates a SID.
     *
     * @access private
     * @param string $type SID type
     *
     * @return string SID
     * @throws Engine_Exception
     */

    protected function _generate_sid($type)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Create minimalist Samba config to generate a domain or local SID
        // Note: this can be simplified with more information on SIDs.

        if ($type == Samba::TYPE_SID_LOCAL)
            $param = "getlocalsid";
        else if ($type == Samba::TYPE_SID_DOMAIN)
            $param = "getdomainsid";
        else
            throw new Engine_Exception("Invalid SID type", COMMON_ERROR);

        try {
            $configlines = "[global]\n";
            $configlines .= "netbios name = mytempnetbios\n";
            $configlines .= "workgroup = mytempdomain\n";
            $configlines .= "domain logons = Yes\n";
            $configlines .= "private dir = " . COMMON_TEMP_DIR . "\n";

            $config = new File(COMMON_TEMP_DIR . "/smb.conf");
                
            if ($config->Exists())    
                $config->Delete();

            $config->Create("root", "root", "0644");
            $config->AddLines($configlines);
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_ERROR);
        }

        // Run net getdomainsid / getlocalsid

        try {
            $secrets = new File(COMMON_TEMP_DIR . "/secrets.tdb");

            if ($secrets->Exists())
                $secrets->Delete();

            $shell = new Shell();

            if ($shell->Execute(self::CMD_NET, '-s ' . COMMON_TEMP_DIR . '/smb.conf ' . $param, TRUE) != 0)
                throw Engine_Exception($shell->GetFirstOutputLine());

            $sid = $shell->GetLastOutputLine();
            $sid = preg_replace("/.*: /", "", $sid);

            $config->Delete();
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_ERROR);
        }

        return $sid;
    }

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
     * Creates an LDAP handle.
     *
     * @access private
     *
     * @return void
     * @throws Engine_Exception
     */

    protected function _get_ldap_handle()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $this->ldaph = new Ldap();
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_WARNING);
        }
    }

    /**
     * Initializes and then saves domain SID to file.
     *
     * @access private
     *
     * @return string domain SID
     * @throws Engine_Exception
     */

    protected function _initialize_domain_sid()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            // If /etc/samba/domainsid exists, use it
            $file = new File(self::FILE_DOMAIN_SID, TRUE);

            if ($file->exists()) {
                $lines = $file->GetContentsAsArray();
                $sid = $lines[0];
            } else {
                $sid = $this->_GenerateSid(Samba::TYPE_SID_DOMAIN);

                $file->create("root", "root", "400");
                $file->add_lines("$sid\n");
            }
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_ERROR);
        }

        return $sid;
    }

    /**
     * Initializes and then saves local SID to file.
     *
     * @access private
     * @param string $sid local SID
     *
     * @return string local SID
     * @throws Engine_Exception
     */

    protected function _initialize_local_sid($sid)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $file = new File(self::FILE_LOCAL_SID, TRUE);

            // If no SID is specified, use the local copy
            if (empty($sid) && $file->exists()) {
                $lines = $file->GetContentsAsArray();
                return $lines[0];
            }

            // If local copy does not exist, create a new SID
            if (empty($sid))
                $sid = $this->_GenerateSid(Samba::TYPE_SID_LOCAL);

            // Create a local copy of the SID
            if ($file->exists())
                $file->Delete();

            $file->create("root", "root", "400");
            $file->add_lines("$sid\n");
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_ERROR);
        }

        return $sid;
    }

    /**
     * Initialize LDAP configuration for Samba.
     *
     * @access private
     * @param string $domainsid domain SID
     * @param string $password windows administrator password
     *
     * @return void
     * @throws Engine_Exception, Samba_Not_Initialized_Exception
     */

    protected function _initialize_ldap($domainsid, $password = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->ldaph == NULL)
            $this->_get_ldap_handle();

        // TODO: validate

        try {
            $domain = $this->GetWorkgroup();
            $logondrive = $this->GetLogonDrive();
            $basedn = $this->ldaph->GetBaseDn();

            $directory = new ClearDirectory();

            if (empty($password))
                $password = $directory->GeneratePassword();

            // TODO: should be static method
            $user = new User("na");
            $sha_password = '{sha}' . $user->_CalculateShaPassword($password);
            $nt_password = $user->_CalculateNtPassword($password);
            $lanman_password = $user->_CalculateLanmanPassword($password);
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_ERROR);
        }

        // Domain
        //--------------------------------------------------------

        $domainobj['objectClass'] = array(
            'top',
            'sambaDomain'
        );

        $dn = 'sambaDomainName=' . $domain . ',' . $basedn;
        $domainobj['sambaDomainName'] = $domain;
        $domainobj['sambaSID'] = $domainsid;
        $domainobj['sambaNextGroupRid'] = 20000000;
        $domainobj['sambaNextUserRid'] = 20000000;
        $domainobj['sambaNextRid'] = 20000000;
        $domainobj['sambaAlgorithmicRidBase'] = 1000;
        $domainobj['sambaMinPwdLength'] = 5;
        $domainobj['sambaPwdHistoryLength'] = 5;
        $domainobj['sambaLogonToChgPwd'] = 0;
        $domainobj['sambaMaxPwdAge'] = -1;
        $domainobj['sambaMinPwdAge'] = 0;
        $domainobj['sambaLockoutDuration'] = 60;
        $domainobj['sambaLockoutObservationWindow'] = 5;
        $domainobj['sambaLockoutThreshold'] = 0;
        $domainobj['sambaForceLogoff'] = 0;
        $domainobj['sambaRefuseMachinePwdChange'] = 0;

        try {
            if (! $this->ldaph->Exists($dn))
                $this->ldaph->Add($dn, $domainobj);
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_ERROR);
        }

        // Idmap
        //--------------------------------------------------------

        $dn = 'ou=Idmap,' . $basedn;
        $idmap['objectClass'] = array(
            'top',
            'organizationalUnit'
        );
        $idmap['ou'] = 'Idmap';

        try {
            if (! $this->ldaph->Exists($dn))
                $this->ldaph->Add($dn, $idmap);
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_ERROR);
        }

        // Users
        //--------------------------------------------------------

        $users_ou = ClearDirectory::GetUsersOu();

        $winadmin_dn = 'cn=' . Samba::CONSTANT_WINADMIN_CN . ',' . $users_ou;

        $userinfo[$winadmin_dn]['lastName'] = 'Administrator';
        $userinfo[$winadmin_dn]['firstName'] = 'Windows';
        $userinfo[$winadmin_dn]['uid'] = 'winadmin';

        $users[$winadmin_dn]['objectClass'] = array(
            'top',
            'posixAccount',
            'shadowAccount',
            'inetOrgPerson',
            'sambaSamAccount',
            'pcnAccount'
        );
        $users[$winadmin_dn]['pcnSHAPassword'] = $sha_password;
        $users[$winadmin_dn]['pcnSHAPassword'] = $sha_password;
        $users[$winadmin_dn]['pcnMicrosoftNTPassword'] = $nt_password;
        $users[$winadmin_dn]['pcnMicrosoftLanmanPassword'] = $lanman_password;
        $users[$winadmin_dn]['sambaPwdLastSet'] = 0;
        $users[$winadmin_dn]['sambaLogonTime'] = 0;
        $users[$winadmin_dn]['sambaLogoffTime'] = 2147483647;
        $users[$winadmin_dn]['sambaKickoffTime'] = 2147483647;
        $users[$winadmin_dn]['sambaPwdCanChange'] = 0;
        $users[$winadmin_dn]['sambaPwdLastSet'] = time();
        $users[$winadmin_dn]['sambaPwdMustChange'] = 2147483647;
        $users[$winadmin_dn]['sambaDomainName'] = $domain;
        $users[$winadmin_dn]['sambaHomeDrive'] = $logondrive;
        $users[$winadmin_dn]['sambaPrimaryGroupSID'] = $domainsid . '-512';
        $users[$winadmin_dn]['sambaLMPassword'] = $lanman_password;
        $users[$winadmin_dn]['sambaNTPassword'] = $nt_password;
        $users[$winadmin_dn]['sambaAcctFlags'] = '[U       ]';
        $users[$winadmin_dn]['sambaSID'] = $domainsid . '-500';

        $guest_dn = 'cn=' . Samba::CONSTANT_GUEST_CN . ',' . $users_ou;

        $users[$guest_dn]['objectClass'] = array(
            'top',
            'posixAccount',
            'shadowAccount',
            'inetOrgPerson',
            'sambaSamAccount',
            'pcnAccount'
        );
        $users[$guest_dn]['pcnSHAPassword'] = $sha_password;
        $users[$guest_dn]['pcnMicrosoftNTPassword'] = 'NO PASSWORDXXXXXXXXXXXXXXXXXXXXX';
        $users[$guest_dn]['pcnMicrosoftLanmanPassword'] = 'NO PASSWORDXXXXXXXXXXXXXXXXXXXXX';
        $users[$guest_dn]['sambaPwdLastSet'] = 0;
        $users[$guest_dn]['sambaLogonTime'] = 0;
        $users[$guest_dn]['sambaLogoffTime'] = 2147483647;
        $users[$guest_dn]['sambaKickoffTime'] = 2147483647;
        $users[$guest_dn]['sambaPwdCanChange'] = 0;
        $users[$guest_dn]['sambaPwdLastSet'] = time();
        $users[$guest_dn]['sambaPwdMustChange'] = 2147483647;
        $users[$guest_dn]['sambaDomainName'] = $domain;
        $users[$guest_dn]['sambaHomeDrive'] = $logondrive;
        $users[$guest_dn]['sambaPrimaryGroupSID'] = $domainsid . '-514';
        $users[$guest_dn]['sambaLMPassword'] = 'NO PASSWORDXXXXXXXXXXXXXXXXXXXXX';
        $users[$guest_dn]['sambaNTPassword'] = 'NO PASSWORDXXXXXXXXXXXXXXXXXXXXX';
        $users[$guest_dn]['sambaAcctFlags'] = '[NUD       ]';
        $users[$guest_dn]['sambaSID'] = $domainsid . '-501';

        foreach ($users as $dn => $object) {
            try {
                if ($this->ldaph->Exists($dn))
                    $this->ldaph->Modify($dn, $object);
            } catch (Exception $e) {
                throw new Engine_Exception($e->GetMessage(), COMMON_ERROR);
            }
        }

        // Groups
        //--------------------------------------------------------

        $this->_InitializeDirectoryWindowsGroups($domainsid);
        $this->UpdateDirectoryGroupMappings($domainsid);
    }

    /**
     * Initialize LDAP groups for Samba.
     *
     * @access private
     * @param string $domainsid domain SID
     *
     * @return void
     * @throws Engine_Exception, Samba_Not_Initialized_Exception
     */

    protected function _initialize_directory_windows_groups($domainsid = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->ldaph == NULL)
            $this->_get_ldap_handle();

        if (empty($domainsid))
            $domainsid = $this->GetDomainSid();

        $group_ou = ClearDirectory::GetGroupsOu();
        $users_ou = ClearDirectory::GetUsersOu();

        $guest_dn = 'cn=' . Samba::CONSTANT_GUEST_CN . ',' . $users_ou;
        $winadmin_dn = 'cn=' . Samba::CONSTANT_WINADMIN_CN . ',' . $users_ou;

        ///////////////////////////////////////////////////////////////////////////////
        // D O M A I N   G R O U P S
        ///////////////////////////////////////////////////////////////////////////////
        //
        // Samba uses the following convention for group mappings:
        // - the base part of the DN is the Posix group
        // - the displayName is the Windows group
        //
        ///////////////////////////////////////////////////////////////////////////////

        $groups = array();

        $dn = 'cn=domain_admins,' . $group_ou;
        $groups[$dn]['displayName'] = 'Domain Admins';
        $groups[$dn]['description'] = 'Domain Admins';
        $groups[$dn]['gidNumber'] = '1000512';
        $groups[$dn]['sambaSID'] = $domainsid . '-512';
        $groups[$dn]['sambaGroupType'] = 2;
        $groups[$dn]['member'] = array($winadmin_dn);

        $dn = 'cn=domain_users,' . $group_ou;
        $groups[$dn]['displayName'] = 'Domain Users';
        $groups[$dn]['description'] = 'Domain Users';
        $groups[$dn]['gidNumber'] = '1000513';
        $groups[$dn]['sambaSID'] = $domainsid . '-513';
        $groups[$dn]['sambaGroupType'] = 2;

        $dn = 'cn=domain_guests,' . $group_ou;
        $groups[$dn]['displayName'] = 'Domain Guests';
        $groups[$dn]['description'] = 'Domain Guests';
        $groups[$dn]['gidNumber'] = '1000514';
        $groups[$dn]['sambaSID'] = $domainsid . '-514';
        $groups[$dn]['sambaGroupType'] = 2;
        $groups[$dn]['member'] = array($guest_dn);

        $dn = 'cn=domain_computers,' . $group_ou;
        $groups[$dn]['displayName'] = 'Domain Computers';
        $groups[$dn]['description'] = 'Domain Computers';
        $groups[$dn]['gidNumber'] = Samba::CONSTANT_GID_DOMAIN_COMPUTERS;
        $groups[$dn]['sambaSID'] = $domainsid . '-515';
        $groups[$dn]['sambaGroupType'] = 2;

        /*
        $dn = 'cn=domain_controllers,' . $group_ou;
        $groups[$dn]['displayName'] = 'Domain Controllers';
        $groups[$dn]['description'] = 'Domain Controllers';
        $groups[$dn]['gidNumber'] = '1000516';
        $groups[$dn]['sambaSID'] = $domainsid . '-516';
        $groups[$dn]['sambaGroupType'] = 2;
        */

        ///////////////////////////////////////////////////////////////////////////////
        // B U I L T - I N   G R O U P S
        ///////////////////////////////////////////////////////////////////////////////

        $dn = 'cn=administrators,' . $group_ou;
        $groups[$dn]['displayName'] = 'Administrators';
        $groups[$dn]['description'] = 'Administrators';
        $groups[$dn]['gidNumber'] = '1000544';
        $groups[$dn]['sambaSID'] = 'S-1-5-32-544';
        $groups[$dn]['SambaSIDList'] = $domainsid . '-512';
        $groups[$dn]['sambaGroupType'] = 4;

        $dn = 'cn=users,' . $group_ou;
        $groups[$dn]['displayName'] = 'Users';
        $groups[$dn]['description'] = 'Users';
        $groups[$dn]['gidNumber'] = '1000545';
        $groups[$dn]['sambaSID'] = 'S-1-5-32-545';
        $groups[$dn]['sambaGroupType'] = 4;

        $dn = 'cn=guests,' . $group_ou;
        $groups[$dn]['displayName'] = 'Guests';
        $groups[$dn]['description'] = 'Guests';
        $groups[$dn]['gidNumber'] = '1000546';
        $groups[$dn]['sambaSID'] = 'S-1-5-32-546';
        $groups[$dn]['sambaGroupType'] = 4;

        $dn = 'cn=power_users,' . $group_ou;
        $groups[$dn]['displayName'] = 'Power Users';
        $groups[$dn]['description'] = 'Power Users';
        $groups[$dn]['gidNumber'] = '1000547';
        $groups[$dn]['sambaSID'] = 'S-1-5-32-547';
        $groups[$dn]['sambaGroupType'] = 4;

        $dn = 'cn=account_operators,' . $group_ou;
        $groups[$dn]['displayName'] = 'Account Operators';
        $groups[$dn]['description'] = 'Account Operators';
        $groups[$dn]['gidNumber'] = '1000548';
        $groups[$dn]['sambaSID'] = 'S-1-5-32-548';
        $groups[$dn]['sambaGroupType'] = 4;

        $dn = 'cn=server_operators,' . $group_ou;
        $groups[$dn]['displayName'] = 'Server Operators';
        $groups[$dn]['description'] = 'Server Operators';
        $groups[$dn]['gidNumber'] = '1000549';
        $groups[$dn]['sambaSID'] = 'S-1-5-32-549';
        $groups[$dn]['sambaGroupType'] = 4;

        $dn = 'cn=print_operators,' . $group_ou;
        $groups[$dn]['displayName'] = 'Print Operators';
        $groups[$dn]['description'] = 'Print Operators';
        $groups[$dn]['gidNumber'] = '1000550';
        $groups[$dn]['sambaSID'] = 'S-1-5-32-550';
        $groups[$dn]['sambaGroupType'] = 4;

        $dn = 'cn=backup_operators,' . $group_ou;
        $groups[$dn]['displayName'] = 'Backup Operators';
        $groups[$dn]['description'] = 'Backup Operators';
        $groups[$dn]['gidNumber'] = '1000551';
        $groups[$dn]['sambaSID'] = 'S-1-5-32-551';
        $groups[$dn]['sambaGroupType'] = 4;

        // Add/Update the groups
        //--------------------------------------------------------

        $group_objectclasses['objectClass'] = array(
            'top',
            'posixGroup',
            'groupOfNames',
            'sambaGroupMapping'
        );

        foreach ($groups as $dn => $object) {
            try {
                if (! $this->ldaph->Exists($dn)) {
                    $matches = array();
                    $groupname = preg_match("/^cn=([^,]*),/", $dn, $matches);

                    $group = new Group($matches[1]);
                    $group->Add($object['description']);
                }

                $this->ldaph->Modify($dn, array_merge($group_objectclasses, $object));
            } catch (Exception $e) {
                // TODO: should check the existence of these groups and handle accordingly
                // throw new Engine_Exception($e->GetMessage(), COMMON_ERROR);
            }
        }

        try {
            $usermanager = new UserManager();
            $allusers = $usermanager->GetAllUsers();

            $group = new Group("domain_users");
            $group->SetMembers($allusers);
        } catch (Exception $e) {
            // TODO: make this fatal
        }
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

        try {
            $file = new File(self::FILE_CONFIG);

            if (! $file->exists())
                $file->create('root', 'root', '0600');

            $lines = $file->GetContentsAsArray();
        } catch (Exception $e) {
            throw new Engine_Exception ($e->GetMessage(), COMMON_ERROR);
        }

        $linecount = 0;
        $share = 'global';
        $match = array();
        $this->shares[$share] = array();

        foreach ($lines as $line) {
            $this->raw_lines[] = $line;

            if (ereg("^[[:space:]]*\[(.*)\]", $line, $match)) {
                $share = trim($match[1]);
                $this->shares[$share]['line'] = $linecount;
            } else if (!ereg("^[[:space:]]*[;#]+.*$", $line)) {
                if (ereg("^[[:space:]]*([[:alnum:] ]+)[[:space:]]*=[[:space:]]*(.*$)", $line, $match)) {
                    $key = trim($match[1]);
                    $this->shares[$share][$key]["line"] = $linecount;

                    $value = explode("#", ereg_replace(";", "#", $match[2]));
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

    protected function _net_grant_default_privileges($password)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $domain = $this->GetWorkgroup();
            $options['stdin'] = TRUE;

            $shell = new Shell();
            $exitcode = $shell->Execute(self::CMD_NET, 'rpc rights grant "' . $domain . '\Domain Admins" ' .
                self::DEFAULT_ADMIN_PRIVS . ' -U winadmin%' . $password, TRUE, $options);

            if ($exitcode != 0)
                throw new Engine_Exception("rpc rights grant failed: " . $shell->GetFirstOutputLine(), COMMON_WARNING);

        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_WARNING);
        }
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

    protected function _net_rpc_join($password)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $domain = $this->GetWorkgroup();
            $netbiosname = $this->GetNetbiosName();

            $options['stdin'] = TRUE;

            $shell = new Shell();
            $exitcode = $shell->Execute(self::CMD_NET, 'rpc join -W ' . $domain . ' -S ' .$netbiosname .
                ' -U winadmin%' . $password, TRUE, $options);

            if ($exitcode != 0)
                throw new Engine_Exception("rpc join failed: " . $shell->GetLastOutputLine(), COMMON_WARNING);

        } catch (Exception $e) {
            // FIXME -- too delicate
            // throw new Engine_Exception($e->GetMessage(), COMMON_WARNING);
        }
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

        // FIXME: need to fix LDAP password handling
        try {
            $shell = new Shell();
            $shell->Execute("/usr/sbin/ldapsync", "config smb", TRUE);
        } catch (Exception $e) {
            // Ignore for now
        }

        // TODO: how do we want to present this in the UI without
        // having to constantly ask for winadmin password? Or should we
        // be asking for the password?

        try {
            if (!$this->IsDirectoryInitialized() || !$this->IsLocalSystemInitialized())
                return;
        } catch (Exception $e) {
            // Ignore for now
        }

        $nmbd = new Nmbd();
        $winbind = new Winbind();

        $nmbd_wasrunning = FALSE;
        $smbd_wasrunning = FALSE;
        $winbind_wasrunning = FALSE;

        if ($winbind->IsInstalled()) {
            $winbind_wasrunning = $winbind->GetRunningState();
            if ($winbind_wasrunning)
                $winbind->SetRunningState(FALSE);
        }

        if ($this->IsInstalled()) {
            $smbd_wasrunning = $this->GetRunningState();
            if ($smbd_wasrunning)
                $this->SetRunningState(FALSE);
        }

        if ($nmbd->IsInstalled()) {
            $nmbd_wasrunning = $nmbd->GetRunningState();
            if ($nmbd_wasrunning)
                $nmbd->SetRunningState(FALSE);
        }

        $this->_SaveBindPassword();
        $this->_SaveIdmapPassword();
        $this->SetDomainSid();
        $this->SetLocalSid();

        if ($nmbd_wasrunning)
            $nmbd->SetRunningState(TRUE);

        if ($smbd_wasrunning)
            $this->SetRunningState(TRUE);

        if ($winbind_wasrunning)
            $winbind->SetRunningState(TRUE);

        sleep(3); // TODO: Wait for samba ... replace this with a loop

        if (! empty($winpassword))
            $this->_NetRpcJoin($winpassword);
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

        try {
            $newfile = new File(self::FILE_CONFIG . '.cctmp');
            if ($newfile->exists())
                $newfile->Delete();

            // Create temp file
            //-----------------
            $newfile->create('root', 'root', '0644');

            // Write out the file
            //-------------------

            $newfile->add_lines($filedata);

            // Copy the new config over the old config
            //----------------------------------------

            $newfile->MoveTo(self::FILE_CONFIG);
        } catch (Exception $e) {
            throw new Engine_Exception ($e->GetMessage(), COMMON_ERROR);
        }
    }

    /**
     * Saves LDAP bind password to Samba secrets file.
     *
     * @access private
     *
     * @return void
     * @throws Engine_Exception
     */

    protected function _save_bind_password()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $ldap = new Ldap();
            $bind_password = $ldap->GetBindPassword();

            // Use pipe to avoid showing password in command line
            $options['stdin'] = TRUE;

            $shell = new Shell();
            $shell->Execute(self::CMD_SMBPASSWD, "-w " . $bind_password, TRUE, $options);
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_WARNING);
        }
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

        try {
            $ldap = new Ldap();
            $password = $ldap->GetBindPassword();
            $options['stdin'] = TRUE;

            $shell = new Shell();
            $exitcode = $shell->Execute(self::CMD_NET, 'idmap secret alloc ' . $password, TRUE, $options);

            if ($exitcode != 0)
                throw new Engine_Exception("idmap secret alloc failed: " . $shell->GetFirstOutputLine(), COMMON_WARNING);

        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_WARNING);
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
            $this->_Load();

        if (!$this->ShareExists($share))
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

        $this->_Save();
    }
}
