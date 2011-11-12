<?php

/**
 * Samba OpenLDAP driver class.
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

use \clearos\apps\base\Engine as Engine;
use \clearos\apps\base\File as File;
use \clearos\apps\base\Folder as Folder;
use \clearos\apps\base\Shell as Shell;
use \clearos\apps\ldap\LDAP_Utilities as LDAP_Utilities;
use \clearos\apps\openldap\LDAP_Driver as LDAP_Driver;
use \clearos\apps\openldap_directory\Accounts_Driver as Accounts_Driver;
use \clearos\apps\openldap_directory\Group_Driver as Group_Driver;
use \clearos\apps\openldap_directory\Group_Manager_Driver as Group_Manager_Driver;
use \clearos\apps\openldap_directory\OpenLDAP as OpenLDAP;
use \clearos\apps\openldap_directory\User_Manager_Driver as User_Manager_Driver;
use \clearos\apps\samba\Nmbd as Nmbd;
use \clearos\apps\samba\Samba as Samba;
use \clearos\apps\samba\Smbd as Smbd;
use \clearos\apps\samba\Winbind as Winbind;

clearos_load_library('base/Engine');
clearos_load_library('base/File');
clearos_load_library('base/Folder');
clearos_load_library('base/Shell');
clearos_load_library('ldap/LDAP_Utilities');
clearos_load_library('openldap/LDAP_Driver');
clearos_load_library('openldap_directory/Accounts_Driver');
clearos_load_library('openldap_directory/Group_Driver');
clearos_load_library('openldap_directory/Group_Manager_Driver');
clearos_load_library('openldap_directory/OpenLDAP');
clearos_load_library('openldap_directory/User_Manager_Driver');
clearos_load_library('samba/Nmbd');
clearos_load_library('samba/Samba');
clearos_load_library('samba/Smbd');
clearos_load_library('samba/Winbind');

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;
use \clearos\apps\samba\Samba_Not_Initialized_Exception as Samba_Not_Initialized_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('base/Validation_Exception');
clearos_load_library('samba/Samba_Not_Initialized_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Samba OpenLDAP driver class.
 *
 * @category   Apps
 * @package    Samba
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/samba/
 */

class OpenLDAP_Driver extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $ldaph = NULL;

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * OpenLDAP accounts constructor.
     *
     * @return void
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Adds a computer.
     *
     * @param string $name computer name
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function add_computer($name)
    {
        clearos_profile(__METHOD__, __LINE__);

        // TODO: the "AddMachine" method does not add the Samba attributes since
        // this is done automagically by Samba.  If this automagic is missed for
        // some reason, then a Computer object may not have the sambaSamAccount object.

        if ($this->ldaph == NULL)
            $this->_get_ldap_handle();

        $accounts = new Accounts_Driver();

        $ldap_object['objectClass'] = array(
            'top',
            'account',
            'posixAccount'
        );

        // TODO: move get_next_uid_number to accounts_driver?

        $ldap_object['cn'] = $name;
        $ldap_object['uid'] = $name;
        $ldap_object['description'] = SAMBA_LANG_COMPUTER . ' ' . preg_replace('/\$$/', '', $name);
        $ldap_object['uidNumber'] = $accounts->get_next_uid_number();
        $ldap_object['gidNumber'] = Samba::CONSTANT_GID_DOMAIN_COMPUTERS;
        $ldap_object['homeDirectory'] = '/dev/NULL';
        $ldap_object['loginShell'] = '/sbin/nologin';

        $dn = 'cn=' . $this->ldaph->dn_escape($name) . ',' . OpenLDAP::get_computers_container();

        if (! $this->ldaph->exists($dn))
            $this->ldaph->add($dn, $ldap_object);
    }

    /**
     * Deletes a computer from the domain.
     *
     * @param string $name computer name
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function delete_computer($name)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->ldaph === NULL)
            $this->_get_ldap_handle();

        if (! $this->is_directory_initialized())
            throw new Samba_Not_Initialized_Exception();

        $dn = 'cn=' . $this->ldaph->dn_escape($name) . ',' . OpenLDAP::get_computers_container();

        if ($this->ldaph->exists($dn))
            $this->ldaph->delete($dn);
    }

    /**
     * Returns bind password. 
     *
     * @return string bind password
     * @throws Engine_Exception, Samba_Not_Initialized_Exception
     */

    public function get_bind_password()
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->ldaph === NULL)
            $this->_get_ldap_handle();

        return $this->ldaph->get_bind_password();
    }

    /**
     * Gets a detailed list of computers for the domain.
     *
     * @return  array  detailed list of computers
     * @throws Engine_Exception
     */

    public function get_computers()
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->ldaph === NULL)
            $this->_get_ldap_handle();

        if (! $this->is_directory_initialized())
            throw new Samba_Not_Initialized_Exception();

        $computers = array();

        // TODO: the "AddMachine" method does not add the Samba attributes since
        // this is done automagically by Samba.  If this automagic is missed for
        // some reason, then a Computer object may not have the sambaSamAccount object.
        // To be safe, use the posixAccount object so that we can cleanup.

        $result = $this->ldaph->search(
            '(objectclass=posixAccount)',
            OpenLDAP::get_computers_container(),
            array('cn', 'sambaSID', 'uidNumber')
        );

        $entry = $this->ldaph->get_first_entry($result);

        while ($entry) {
            $attributes = $this->ldaph->get_attributes($entry);

            $computer = $attributes['cn']['0'];
            $computers[$computer]['SID'] = isset($attributes['sambaSID'][0]) ? $attributes['sambaSID'][0] : "";
            $computers[$computer]['uidNumber'] = $attributes['uidNumber'][0];

            $entry = $this->ldaph->next_entry($entry);
        }
        
        return $computers;
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

        if ($this->ldaph === NULL)
            $this->_get_ldap_handle();

        if (! $this->is_directory_initialized())
            throw new Samba_Not_Initialized_Exception();

        $result = $this->ldaph->search(
            "(objectclass=sambaDomain)",
            OpenLDAP::get_base_dn(),
            array("sambaDomainName", "sambaSID")
        );

        $entry = $this->ldaph->get_first_entry($result);

        if ($entry) {
            $attributes = $this->ldaph->get_attributes($entry);
            $sid = $attributes['sambaSID'][0];
        } else {
            throw new Engine_Exception(LOCALE_LANG_ERRMSG_SYNTAX_ERROR . " - sambaDomainName", COMMON_ERROR);
        }

        return $sid;
    }

    /**
     * Initializes master node with the necessary Samba elements.
     *
     * You do not need to have the server components of Samba installed
     * to run this initialization routine.  This simply initializes the
     * necessary bits to get LDAP up and running.
     *
     * @param stringa $domain   workgroup / domain
     * @param string  $password password for winadmin
     * @param boolean $force    force initialization
     *
     * @return void
     * @throws Engine_Exception
     */

    public function initialize_master_system($domain, $password = NULL, $force = FALSE)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->is_directory_initialized() && !$force)
            return;

        // Shutdown Samba daemons if they are installed/running

        $samba = new Samba(); 
        $nmbd = new Nmbd();
        $smbd = new Smbd();
        $winbind = new Winbind();

        $nmbd_was_running = FALSE;
        $smbd_was_running = FALSE;
        $winbind_was_running = FALSE;

        if ($winbind->is_installed()) {
            $winbind_was_running = $winbind->get_running_state();
            if ($winbind_was_running)
                $winbind->set_running_state(FALSE);
        }

        if ($smbd->is_installed()) {
            $smbd_was_running = $smbd->get_running_state();
            if ($smbd_was_running)
                $smbd->set_running_state(FALSE);
        }

        if ($nmbd->is_installed()) {
            $nmbd_was_running = $nmbd->get_running_state();
            if ($nmbd_was_running)
                $nmbd->set_running_state(FALSE);
        }

        // Archive the files (usually in /var/lib/samba)
        $this->_archive_state_files();

        // Set workgroup
        $samba->set_workgroup($domain);

        // Bootstrap the domain SID
        $domainsid = $this->_initialize_domain_sid();

        // Set local SID to be the same as domain SID
        $this->_initialize_local_sid($domainsid);

        // Implant all the Samba elements into LDAP
        $this->_initialize_ldap($domainsid, $password);

        // Groups
        $this->_initialize_windows_groups($domainsid);
        $this->_initialize_group_memberships($domainsid);
        $this->update_group_mappings($domainsid);

        // Save the LDAP password into secrets
        $samba->_save_bind_password();

        // Restart Samba if it was running 
        if ($nmbd_was_running)
            $nmbd->set_running_state(TRUE);

        if ($smbd_was_running)
            $smbd->set_running_state(TRUE);

        if ($winbind_was_running)
            $winbind->set_running_state(TRUE);

        // For good measure, update local file permissions
        try {
            $samba->update_local_file_permissions();
        } catch (Engine_Exception $e) {
            // Not fatal
        }
    }

    /**
     * Checks to see if Samba has been initialized in LDAP.
     *
     * @return boolean TRUE if Samba has been initialized in LDAP
     * @throws Engine_Exception, LDAP_Offline_Exception
     */

    public function is_directory_initialized()
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->ldaph === NULL)
            $this->_get_ldap_handle();

        $result = $this->ldaph->search(
            '(objectclass=sambaDomain)',
            OpenLDAP::get_base_dn(),
            array('sambaDomainName', 'sambaSID')
        );

        $entry = $this->ldaph->get_first_entry($result);

        if ($entry)
            return TRUE;
        else
            return FALSE;
    }

    /**
     * Updates existing groups with Windows Networking group information (mapping).
     *
     * The ClearOS directory is designed to work without the Windows Networking
     * overlay.  When Windows Networking is enabled, we need to go through all the
     * existing groups and add the required Windows fields.
     *
     * @param string $domain_sid domain SID
     *
     * @return void
     * @throws Engine_Exception, Samba_Not_Initialized_Exception
     */

    public function update_group_mappings($domain_sid = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->ldaph === NULL)
            $this->_get_ldap_handle();

        if (empty($domain_sid)) {
            $samba = new Samba();
            $domain_sid = $samba->get_domain_sid();
        }

        $group_manager = new Group_Manager_Driver();
        $group_details = $group_manager->get_details(Group_Driver::TYPE_ALL);

        // Add/Update the groups
        //----------------------

        $attributes['objectClass'] = array(
            'top',
            'posixGroup',
            'groupOfNames',
            'sambaGroupMapping'
        );

        foreach ($group_details as $group_name => $group_info) {

            // Skip system (non-LDAP) groups
            //------------------------------

            if ($group_info['type'] === Group_Driver::TYPE_SYSTEM)
                continue;

            // Skip groups with existing Samba attributes
            //-------------------------------------------

            if (isset($group_info['extensions']['samba']['sid']))
                continue;

            // Update group
            //-------------

            // TODO: push this to Group_Driver->update();
            $dn = "cn=" . $this->ldaph->dn_escape($group_info['group_name']) . "," . OpenLDAP::get_groups_container();
            $attributes['sambaSID'] = $domain_sid . '-' . $group_info['gid_number'];
            $attributes['sambaGroupType'] = 2;
            $attributes['displayName'] = $group_info['group_name'];

            if ($this->ldaph->exists($dn))
                $this->ldaph->modify($dn, $attributes);
        }
    }

    /**
     * Sets workgroup/domain name LDAP objects.
     *
     * @param string $workgroup workgroup name
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_workgroup($workgroup)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_directory_initialized())
            return;

        if ($this->ldaph == NULL)
            $this->_get_ldap_handle();

        // Update sambaDomainName object
        //------------------------------

        $sid = $this->get_domain_sid();
        $base_dn = OpenLDAP::get_base_dn();

        $result = $this->ldaph->search(
            "(sambaSID=$sid)",
            $base_dn,
            array("sambaDomainName")
        );

        $entry = $this->ldaph->get_first_entry($result);

        if ($entry) {
            $attributes = $this->ldaph->get_attributes($entry);

            if ($workgroup != $attributes['sambaDomainName'][0]) {
                $new_rdn = "sambaDomainName=" . $workgroup;
                $new_dn = $new_rdn . "," . $base_dn;
                $old_dn = "sambaDomainName=" . $attributes['sambaDomainName'][0] . "," . $base_dn;
                $newattrs['sambaDomainName'] = $workgroup;

                $this->ldaph->rename($old_dn, $new_rdn);
                $this->ldaph->modify($new_dn, $newattrs);
            }
        }

        // Update sambaDomain attribute for all users
        //-------------------------------------------

        $users_container = OpenLDAP::get_users_container();

        $result = $this->ldaph->search(
            "(sambaDomainName=*)",
            $users_container,
            array("cn")
        );

        $entry = $this->ldaph->get_first_entry($result);
        $newattrs['sambaDomainName'] = $workgroup;

        while ($entry) {
            $attributes = $this->ldaph->get_attributes($entry);
            $this->ldaph->modify('cn=' . $attributes['cn'][0] . "," . $users_container , $newattrs);
            $entry = $this->ldaph->next_entry($entry);
        }
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
        //------------------------

        $backup_path = Samba::PATH_STATE_BACKUP . '/varbackup-' . strftime('%m-%d-%Y-%H-%M-%S-%s', time());

        $backup_folder = new Folder($backup_path);

        if (! $backup_folder->exists())
            $backup_folder->create('root', 'root', '0755');

        // Perform backup
        //---------------

        $folder = new Folder(Samba::PATH_STATE);
        $state_files = $folder->get_recursive_listing();

        foreach ($state_files as $filename) {
            if (! preg_match('/(tdb)|(dat)$/', $filename))
                continue;

            if (preg_match('/\//', $filename)) {
                $dirname = dirname($filename);
                $backup_folder = new Folder($backup_path . '/' . $dirname);

                if (! $backup_folder->exists())
                    $backup_folder->create('root', 'root', '0755');
            }

            $file = new File(Samba::PATH_STATE . '/' . $filename);
            $file->move_to($backup_path . '/' . $filename);
        }
    }

    /**
     * Generates a SID.
     *
     * @param string $type SID type
     *
     * @access private
     * @return string SID
     * @throws Engine_Exception
     */

    protected function _generate_sid($type)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validate
        //---------

        if ($type == Samba::TYPE_SID_LOCAL)
            $param = 'getlocalsid';
        else if ($type == Samba::TYPE_SID_DOMAIN)
            $param = 'getdomainsid';
        else
            throw new Validation_Exception('Invalid SID type');

        // Create minimalist Samba config to generate a domain or local SID
        //-----------------------------------------------------------------

        $config_lines = "[global]\n";
        $config_lines .= "netbios name = mytempnetbios\n";
        $config_lines .= "workgroup = mytempdomain\n";
        $config_lines .= "domain logons = Yes\n";
        $config_lines .= "private dir = " . CLEAROS_TEMP_DIR . "\n";

        $config = new File(CLEAROS_TEMP_DIR . '/smb.conf');
            
        if ($config->exists())
            $config->delete();

        $config->create('root', 'root', '0644');
        $config->add_lines($config_lines);

        // Run net getdomainsid / getlocalsid
        //-----------------------------------

        $secrets = new File(CLEAROS_TEMP_DIR . '/secrets.tdb');

        if ($secrets->exists())
            $secrets->delete();

        $shell = new Shell();

        $shell->execute(Samba::COMMAND_NET, '-s ' . CLEAROS_TEMP_DIR . '/smb.conf ' . $param, TRUE);

        $sid = $shell->get_last_output_line();
        $sid = preg_replace('/.*: /', '', $sid);

        $config->delete();

        return $sid;
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

        $ldap = new LDAP_Driver();
        $this->ldaph = $ldap->get_ldap_handle();
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

        // If /etc/samba/domainsid exists, use it
        $file = new File(Samba::FILE_DOMAIN_SID, TRUE);

        if ($file->exists()) {
            $lines = $file->get_contents_as_array();
            $sid = $lines[0];
        } else {
            $sid = $this->_generate_sid(Samba::TYPE_SID_DOMAIN);

            $file->create('root', 'root', '400');
            $file->add_lines("$sid\n");
        }

        return $sid;
    }

    /**
     * Initializes and then saves local SID to file.
     *
     * @param string $sid local SID
     *
     * @access private
     * @return string local SID
     * @throws Engine_Exception
     */

    protected function _initialize_local_sid($sid)
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(Samba::FILE_LOCAL_SID, TRUE);

        // If no SID is specified, use the local copy

        if (empty($sid) && $file->exists()) {
            $lines = $file->get_contents_as_array();
            return $lines[0];
        }

        // If local copy does not exist, create a new SID
        if (empty($sid))
            $sid = $this->_generate_sid(Samba::TYPE_SID_LOCAL);

        // Create a local copy of the SID
        if ($file->exists())
            $file->delete();

        $file->create("root", "root", "400");
        $file->add_lines("$sid\n");

        return $sid;
    }

    /**
     * Initialize LDAP configuration for Samba.
     *
     * @param string $domainsid domain SID
     * @param string $password windows administrator password
     *
     * @access private
     * @return void
     * @throws Engine_Exception, Samba_Not_Initialized_Exception
     */

    protected function _initialize_ldap($domainsid, $password = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->ldaph === NULL)
            $this->_get_ldap_handle();

        // TODO: validate

        $samba = new Samba();

        $domain = $samba->get_workgroup();
        $logon_drive = $samba->get_logon_drive();
        $base_dn = OpenLDAP::get_base_dn();

        if (empty($password))
            $password = LDAP_Utilities::generate_password();

        $sha_password = '{sha}' . LDAP_Utilities::calculate_sha_password($password);
        $nt_password = LDAP_Utilities::calculate_nt_password($password);

        // Domain
        //--------------------------------------------------------

        $domainobj['objectClass'] = array(
            'top',
            'sambaDomain'
        );

        $dn = 'sambaDomainName=' . $domain . ',' . $base_dn;
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

        if (! $this->ldaph->exists($dn))
            $this->ldaph->add($dn, $domainobj);

        // Idmap
        //--------------------------------------------------------

        $dn = 'ou=Idmap,' . $base_dn;
        $idmap['objectClass'] = array(
            'top',
            'organizationalUnit'
        );
        $idmap['ou'] = 'Idmap';

        if (! $this->ldaph->exists($dn))
            $this->ldaph->add($dn, $idmap);

        // Users
        //--------------------------------------------------------

        $users_container = OpenLDAP::get_users_container();

        $winadmin_dn = 'cn=' . Samba::CONSTANT_WINADMIN_CN . ',' . $users_container;

        $userinfo[$winadmin_dn]['lastName'] = 'Administrator';
        $userinfo[$winadmin_dn]['firstName'] = 'Windows';
        $userinfo[$winadmin_dn]['uid'] = 'winadmin';

        $users[$winadmin_dn]['objectClass'] = array(
            'top',
            'posixAccount',
            'shadowAccount',
            'inetOrgPerson',
            'sambaSamAccount',
            'clearAccount'
        );
        $users[$winadmin_dn]['clearAccountStatus'] = TRUE;
        $users[$winadmin_dn]['clearSHAPassword'] = $sha_password;
        $users[$winadmin_dn]['clearSHAPassword'] = $sha_password;
        $users[$winadmin_dn]['clearMicrosoftNTPassword'] = $nt_password;
        $users[$winadmin_dn]['sambaPwdLastSet'] = 0;
        $users[$winadmin_dn]['sambaLogonTime'] = 0;
        $users[$winadmin_dn]['sambaLogoffTime'] = 2147483647;
        $users[$winadmin_dn]['sambaKickoffTime'] = 2147483647;
        $users[$winadmin_dn]['sambaPwdCanChange'] = 0;
        $users[$winadmin_dn]['sambaPwdLastSet'] = time();
        $users[$winadmin_dn]['sambaPwdMustChange'] = 2147483647;
        $users[$winadmin_dn]['sambaDomainName'] = $domain;
        $users[$winadmin_dn]['sambaHomeDrive'] = $logon_drive;
        $users[$winadmin_dn]['sambaPrimaryGroupSID'] = $domainsid . '-512';
        $users[$winadmin_dn]['sambaNTPassword'] = $nt_password;
        $users[$winadmin_dn]['sambaAcctFlags'] = '[U       ]';
        $users[$winadmin_dn]['sambaSID'] = $domainsid . '-500';

        $guest_dn = 'cn=' . Samba::CONSTANT_GUEST_CN . ',' . $users_container;

        $users[$guest_dn]['objectClass'] = array(
            'top',
            'posixAccount',
            'shadowAccount',
            'inetOrgPerson',
            'sambaSamAccount',
            'clearAccount'
        );
        $users[$guest_dn]['clearAccountStatus'] = TRUE;
        $users[$guest_dn]['clearSHAPassword'] = $sha_password;
        $users[$guest_dn]['clearMicrosoftNTPassword'] = 'NO PASSWORDXXXXXXXXXXXXXXXXXXXXX';
        $users[$guest_dn]['sambaPwdLastSet'] = 0;
        $users[$guest_dn]['sambaLogonTime'] = 0;
        $users[$guest_dn]['sambaLogoffTime'] = 2147483647;
        $users[$guest_dn]['sambaKickoffTime'] = 2147483647;
        $users[$guest_dn]['sambaPwdCanChange'] = 0;
        $users[$guest_dn]['sambaPwdLastSet'] = time();
        $users[$guest_dn]['sambaPwdMustChange'] = 2147483647;
        $users[$guest_dn]['sambaDomainName'] = $domain;
        $users[$guest_dn]['sambaHomeDrive'] = $logon_drive;
        $users[$guest_dn]['sambaPrimaryGroupSID'] = $domainsid . '-514';
        $users[$guest_dn]['sambaLMPassword'] = 'NO PASSWORDXXXXXXXXXXXXXXXXXXXXX';
        $users[$guest_dn]['sambaNTPassword'] = 'NO PASSWORDXXXXXXXXXXXXXXXXXXXXX';
        $users[$guest_dn]['sambaAcctFlags'] = '[NUD       ]';
        $users[$guest_dn]['sambaSID'] = $domainsid . '-501';

        foreach ($users as $dn => $object) {
            if ($this->ldaph->exists($dn))
                $this->ldaph->modify($dn, $object);
        }
    }

    /**
     * Initializes group memeberships.
     *
     * @access private
     * @return void
     * @throws Engine_Exception, Samba_Not_Initialized_Exception
     */

    protected function _initialize_group_memberships()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $user_manager = new User_Manager_Driver();
            $all_users = $user_manager->get_list();

            $group = new Group_Driver("domain_users");
            $group->set_members($all_users);
        } catch (Exception $e) {
            // TODO: make this fatal
        }
    }

    /**
     * Initializes LDAP groups for Samba.
     *
     * @param string $domainsid domain SID
     *
     * @access private
     * @return void
     * @throws Engine_Exception, Samba_Not_Initialized_Exception
     */

    protected function _initialize_windows_groups($domainsid = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->ldaph === NULL)
            $this->_get_ldap_handle();

        if (empty($domainsid)) {
            $samba = new Samba();
            $domainsid = $samba->get_domain_sid();
        }

        $users_container = OpenLDAP::get_users_container();
        $groups_container = OpenLDAP::get_groups_container();

        $guest_dn = 'cn=' . Samba::CONSTANT_GUEST_CN . ',' . $users_container;
        $winadmin_dn = 'cn=' . Samba::CONSTANT_WINADMIN_CN . ',' . $users_container;

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

        $dn = 'cn=domain_admins,' . $groups_container;
        $groups[$dn]['displayName'] = 'Domain Admins';
        $groups[$dn]['description'] = 'Domain Admins';
        $groups[$dn]['gidNumber'] = '1000512';
        $groups[$dn]['sambaSID'] = $domainsid . '-512';
        $groups[$dn]['sambaGroupType'] = 2;
        $groups[$dn]['member'] = array($winadmin_dn);

        $dn = 'cn=domain_users,' . $groups_container;
        $groups[$dn]['displayName'] = 'Domain Users';
        $groups[$dn]['description'] = 'Domain Users';
        $groups[$dn]['gidNumber'] = '1000513';
        $groups[$dn]['sambaSID'] = $domainsid . '-513';
        $groups[$dn]['sambaGroupType'] = 2;

        $dn = 'cn=domain_guests,' . $groups_container;
        $groups[$dn]['displayName'] = 'Domain Guests';
        $groups[$dn]['description'] = 'Domain Guests';
        $groups[$dn]['gidNumber'] = '1000514';
        $groups[$dn]['sambaSID'] = $domainsid . '-514';
        $groups[$dn]['sambaGroupType'] = 2;
        $groups[$dn]['member'] = array($guest_dn);

        $dn = 'cn=domain_computers,' . $groups_container;
        $groups[$dn]['displayName'] = 'Domain Computers';
        $groups[$dn]['description'] = 'Domain Computers';
        $groups[$dn]['gidNumber'] = Samba::CONSTANT_GID_DOMAIN_COMPUTERS;
        $groups[$dn]['sambaSID'] = $domainsid . '-515';
        $groups[$dn]['sambaGroupType'] = 2;

        /*
        $dn = 'cn=domain_controllers,' . $groups_container;
        $groups[$dn]['displayName'] = 'Domain Controllers';
        $groups[$dn]['description'] = 'Domain Controllers';
        $groups[$dn]['gidNumber'] = '1000516';
        $groups[$dn]['sambaSID'] = $domainsid . '-516';
        $groups[$dn]['sambaGroupType'] = 2;
        */

        ///////////////////////////////////////////////////////////////////////////////
        // B U I L T - I N   G R O U P S
        ///////////////////////////////////////////////////////////////////////////////

        $dn = 'cn=administrators,' . $groups_container;
        $groups[$dn]['displayName'] = 'Administrators';
        $groups[$dn]['description'] = 'Administrators';
        $groups[$dn]['gidNumber'] = '1000544';
        $groups[$dn]['sambaSID'] = 'S-1-5-32-544';
        $groups[$dn]['SambaSIDList'] = $domainsid . '-512';
        $groups[$dn]['sambaGroupType'] = 4;

        $dn = 'cn=users,' . $groups_container;
        $groups[$dn]['displayName'] = 'Users';
        $groups[$dn]['description'] = 'Users';
        $groups[$dn]['gidNumber'] = '1000545';
        $groups[$dn]['sambaSID'] = 'S-1-5-32-545';
        $groups[$dn]['sambaGroupType'] = 4;

        $dn = 'cn=guests,' . $groups_container;
        $groups[$dn]['displayName'] = 'Guests';
        $groups[$dn]['description'] = 'Guests';
        $groups[$dn]['gidNumber'] = '1000546';
        $groups[$dn]['sambaSID'] = 'S-1-5-32-546';
        $groups[$dn]['sambaGroupType'] = 4;

        $dn = 'cn=power_users,' . $groups_container;
        $groups[$dn]['displayName'] = 'Power Users';
        $groups[$dn]['description'] = 'Power Users';
        $groups[$dn]['gidNumber'] = '1000547';
        $groups[$dn]['sambaSID'] = 'S-1-5-32-547';
        $groups[$dn]['sambaGroupType'] = 4;

        $dn = 'cn=account_operators,' . $groups_container;
        $groups[$dn]['displayName'] = 'Account Operators';
        $groups[$dn]['description'] = 'Account Operators';
        $groups[$dn]['gidNumber'] = '1000548';
        $groups[$dn]['sambaSID'] = 'S-1-5-32-548';
        $groups[$dn]['sambaGroupType'] = 4;

        $dn = 'cn=server_operators,' . $groups_container;
        $groups[$dn]['displayName'] = 'Server Operators';
        $groups[$dn]['description'] = 'Server Operators';
        $groups[$dn]['gidNumber'] = '1000549';
        $groups[$dn]['sambaSID'] = 'S-1-5-32-549';
        $groups[$dn]['sambaGroupType'] = 4;

        $dn = 'cn=print_operators,' . $groups_container;
        $groups[$dn]['displayName'] = 'Print Operators';
        $groups[$dn]['description'] = 'Print Operators';
        $groups[$dn]['gidNumber'] = '1000550';
        $groups[$dn]['sambaSID'] = 'S-1-5-32-550';
        $groups[$dn]['sambaGroupType'] = 4;

        $dn = 'cn=backup_operators,' . $groups_container;
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
                if (! $this->ldaph->exists($dn)) {
                    $matches = array();
                    $groupname = preg_match("/^cn=([^,]*),/", $dn, $matches);

                    $group = new Group_Driver($matches[1]);
                    $group->add($object['description']);
                }

                $this->ldaph->modify($dn, array_merge($group_objectclasses, $object));
            } catch (Exception $e) {
                // TODO: should check the existence of these groups and handle accordingly
                // throw new Engine_Exception($e->GetMessage(), COMMON_ERROR);
            }
        }
    }
}
