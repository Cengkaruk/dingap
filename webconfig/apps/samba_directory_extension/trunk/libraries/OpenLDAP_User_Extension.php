<?php

/**
 * Samba OpenLDAP user extension.
 *
 * @category   Apps
 * @package    Samba_Directory_Extension
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/samba_directory_extension/
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

namespace clearos\apps\samba_directory_extension;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('base');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

use \clearos\apps\base\Engine as Engine;
use \clearos\apps\openldap\Utilities as Utilities;
use \clearos\apps\samba\Samba as Samba;

clearos_load_library('base/Engine');
clearos_load_library('openldap/Utilities');
clearos_load_library('samba/Samba');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Samba OpenLDAP user extension.
 *
 * @category   Apps
 * @package    Samba_Directory_Extension
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/samba_directory_extension/
 */

class OpenLDAP_User_Extension extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const COMMAND_PDBEDIT = '/usr/bin/pdbedit';

    // UID/GID/RID ranges -- see http://www.clearfoundation.com/docs/developer/features/cleardirectory/uids_gids_and_rids
    const CONSTANT_SPECIAL_RID_MAX = '1000'; // RIDs below this number are reserved
    const CONSTANT_SPECIAL_RID_OFFSET = '1000000'; // Offset used to map <1000 RIDs to UIDs

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $info_map = array();
    protected $name = 'Samba (Extension2)'; // FIXME: translate

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Samba OpenLDAP_Extension constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        // FIXME: field_type_options
        $this->info_map = array(
            'home_drive' => array(
                'type' => 'string',
                'field_type' => 'list',
                'field_options' => array('W:', 'X:', 'Y:'),
                'required' => FALSE,
                'validator' => 'validate_home_drive',
                'validator_class' => 'samba_directory_extension/OpenLDAP_User_Extension',
                'description' => lang('samba_logon_drive'),
                'object_class' => 'sambaSamAccount',
                'attribute' => 'sambaHomeDrive'
            ),
            'home_path' => array(
                'type' => 'string',
                'field_type' => 'text',
                'required' => FALSE,
                'validator' => 'validate_home_path',
                'validator_class' => 'samba_directory_extension/OpenLDAP_User_Extension',
                'description' => lang('samba_logon_path'),
                'object_class' => 'sambaSamAccount',
                'attribute' => 'sambaHomePath'
            ),
            'sid' => array(
                'type' => 'string',
                'field_type' => 'text',
                'required' => FALSE,
                'validator' => 'validate_sid',
                'validator_class' => 'samba_directory_extension/OpenLDAP_User_Extension',
                'description' => lang('samba_sid'),
                'object_class' => 'sambaSamAccount',
                'attribute' => 'sambaSID'
            ),
        );
    }

    /** 
     * Add LDAP attributes hook.
     *
     * The following Samba attributes can be set:
     * - flags_disabled
     * - home_drive (sambaHomeDrive)
     * - home_path (sambaHomePath)
     * - home_path_state flag to indicate roaming profile state
     * - sid (sambaSID)
     *
     * @param array $user_info user information in hash array
     * @param array $ldap_object LDAP object
     *
     * @return array LDAP attributes
     * @throws Engine_Exception
     */

    public function add_attributes_hook($user_info, $ldap_object)
    {
        clearos_profile(__METHOD__, __LINE__);

        // FIXME: what to do if SambaLDAP is not initialized? 

        /*
        // TODO: this should be the PDC name, not the local server
        $pdc = $this->GetNetbiosName();
        $domain = $this->GetWorkgroup();
        $drive = $this->GetLogonDrive();
        $sid = $this->GetDomainSid();
        $home_path = $this->GetLogonPath();
        */
        // FIXME
        $pdc = 'TORONTO';
        $domain = 'CLEARCENTER';
        $sid = 'S-1-5-21-3632762846-2595688129-414830061';
        $drive = 'X:';
        $home_path = '\\%L\profiles\%U';

        // Set defaults
        //-------------

        if (! isset($user_info['samba']['home_drive']))
            $user_info['samba']['home_drive'] = $drive;

        // TODO: review logic
        if (isset($user_info['samba']['home_path_state']) && $user_info['samba']['home_path_state']) {
            if (! isset($user_info['samba']['home_path']))
                $user_info['samba']['home_path'] = '\\\\' . $pdc . '\\profiles\\' . $user_info['core']['username'];
        }

        if (! isset($user_info['samba']['sid'])) {
            $rid = ($ldap_object['uidNumber'] < self::CONSTANT_SPECIAL_RID_MAX) ? self::CONSTANT_SPECIAL_RID_OFFSET + $ldap_object['uidNumber'] : $ldap_object['uidNumber'];
            $user_info['samba']['sid'] = $sid . '-' . $rid;
        }

        // Convert to LDAP attributes
        //---------------------------

        $attributes = Utilities::convert_array_to_attributes($user_info['samba'], $this->info_map);

        // Handle special flag attributes
        //-------------------------------

        if (isset($user_info['samba']['flags_disabled']) && $user_info['samba']['flags_disabled'])
            $attributes['sambaAcctFlags'] = '[UD         ]';
        else
            $attributes['sambaAcctFlags'] = '[U          ]';

        // Add built-in attributes
        //------------------------

        $attributes['sambaPrimaryGroupSID'] = "$sid-" . Samba::CONSTANT_DOMAIN_USERS_RID;
        $attributes['sambaDomainName'] = $domain;
        $attributes['sambaNTPassword'] = $ldap_object['clearMicrosoftNTPassword'];
        $attributes['sambaPwdLastSet'] = time();
        $attributes['sambaBadPasswordCount'] = 0;
        $attributes['sambaBadPasswordTime'] = 0;

        return $attributes;
    }

    /**
     * Returns user info hash array.
     *
     * @param array $attributes LDAP attributes
     *
     * @return array user info array
     * @throws Engine_Exception
     */

    public function get_info_hook($attributes)
    {
        clearos_profile(__METHOD__, __LINE__);

        $info = Utilities::convert_attributes_to_array($attributes, $this->info_map);

        /*
        FIXME
            // The 'D' flag indicates a disabled account
            if (isset($attributes['sambaAcctFlags']) && !preg_match('/D/', $attributes['sambaAcctFlags'][0]))
                $userinfo['sambaFlag'] = TRUE;
            else
                $userinfo['sambaFlag'] = FALSE;

            // The 'L' flag indicates a locaked account
            if (isset($attributes['sambaAcctFlags']) && preg_match('/L/', $attributes['sambaAcctFlags'][0]))
                $userinfo['sambaAccountLocked'] = TRUE;
            else
                $userinfo['sambaAccountLocked'] = FALSE;
        */
        $info['disabled'] = FALSE;
        $info['locked'] = TRUE;

        return $info;
    }

    public function get_info_map_hook()
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->info_map;
    }

    /** 
     * Update LDAP attributes hook.
     *
     * @param array $user_info user information in hash array
     * @param array $ldap_object LDAP object
     *
     * @return array LDAP attributes
     * @throws Engine_Exception
     */

    public function update_attributes_hook($user_info, $ldap_object)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Return if nothing needs to be done
        //-----------------------------------

        if (! isset($user_info['samba']))
            return array();

        // Convert to LDAP attributes
        //---------------------------

        $attributes = Utilities::convert_array_to_attributes($user_info['samba'], $this->info_map);

        // Handle special flag attributes
        //-------------------------------

        if (isset($user_info['samba']['flags_disabled'])) {
            if ($user_info['samba']['flags_disabled'])
                $attributes['sambaAcctFlags'] = '[UD         ]';
            else
                $attributes['sambaAcctFlags'] = '[U          ]';
        }

        return $attributes;
    }

    /**
     * Set password hook.
     *
     * @param string $password password
     * @return void
     * @throws Engine_Exception, Validation_Exception 
     */

    public function set_password_attributes_hook($password, $ldap_object)
    {
        clearos_profile(__METHOD__, __LINE__);

        $attributes = array();

        // FIXME
        // if (isset($old_attributes['sambaSID'])) {
            $attributes['sambaNTPassword'] = $ldap_object['clearMicrosoftNTPassword'];
            $attributes['sambaPwdLastSet'] = time();
        // }
    
        return $attributes;
    }

    /** 
     * Unlock LDAP attributes hook.
     *
     * @param array $user_info user information in hash array
     * @param array $ldap_object LDAP object
     *
     * @return array LDAP attributes
     * @throws Engine_Exception
     */

    public function unlock_hook()
    {
        clearos_profile(__METHOD__, __LINE__);

/*
        try {
            $shell = new Shell();
            $exitcode = $shell->Execute(self::CMD_PDBEDIT, '-c "[]" -z -u ' . $username, TRUE);

            if ($exitcode != 0)
                throw new Engine_Exception("unlock failed: " . $shell->GetFirstOutputLine(), COMMON_WARNING);
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_WARNING);
        }

*/
    }

    public function validate_home_drive($drive)
    {
        clearos_profile(__METHOD__, __LINE__);

  //      return "das ist bad drive.";
    }

    public function validate_home_path($path)
    {
        clearos_profile(__METHOD__, __LINE__);

//        return "das ist bad path.";
    }

    public function validate_sid($sid)
    {
        clearos_profile(__METHOD__, __LINE__);

        // return "das ist bad sid.";
    }
}
