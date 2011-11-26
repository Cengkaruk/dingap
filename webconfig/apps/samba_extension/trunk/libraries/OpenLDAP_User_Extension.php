<?php

/**
 * Samba OpenLDAP user extension.
 *
 * @category   Apps
 * @package    Samba_Extension
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/samba_extension/
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

namespace clearos\apps\samba_extension;

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
use \clearos\apps\base\Shell as Shell;
use \clearos\apps\openldap_directory\Group_Driver as Group_Driver;
use \clearos\apps\openldap_directory\Utilities as Utilities;
use \clearos\apps\samba\OpenLDAP_Driver as OpenLDAP_Driver;
use \clearos\apps\samba\Samba as Samba;

clearos_load_library('base/Engine');
clearos_load_library('base/Shell');
clearos_load_library('openldap_directory/Group_Driver');
clearos_load_library('openldap_directory/Utilities');
clearos_load_library('samba/OpenLDAP_Driver');
clearos_load_library('samba/Samba');

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;

clearos_load_library('base/Engine_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Samba OpenLDAP user extension.
 *
 * @category   Apps
 * @package    Samba_Extension
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/samba_extension/
 */

class OpenLDAP_User_Extension extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    // see http://www.clearfoundation.com/docs/developer/features/cleardirectory/uids_gids_and_rids
    const CONSTANT_SPECIAL_RID_MAX = '1000'; // RIDs below this number are reserved
    const CONSTANT_SPECIAL_RID_OFFSET = '1000000'; // Offset used to map <1000 RIDs to UIDs
    const COMMAND_PDBEDIT = '/usr/bin/pdbedit';

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $info_map = array();

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Samba OpenLDAP user extension constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        include clearos_app_base('samba_extension') . '/deploy/user_map.php';

        $this->name = lang('samba_extension_windows_networking');
        $this->info_map = $info_map;
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
     * @param array $user_info   user information in hash array
     * @param array $ldap_object LDAP object
     *
     * @return array LDAP attributes
     * @throws Engine_Exception
     */

    public function add_attributes_hook($user_info, $ldap_object)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Bail if Samba has not been initialized
        //---------------------------------------

        $samba_driver = new OpenLDAP_Driver();

        if (!$samba_driver->is_directory_initialized())
            return array();

        // Process attributes
        //-------------------

        $samba = new Samba();

        $sid = $samba->get_domain_sid();
        $domain = $samba->get_workgroup();

        // Set defaults
        //-------------

        if (empty($user_info['extensions']['samba']['sid'])) {
            if ($ldap_object['uidNumber'] < self::CONSTANT_SPECIAL_RID_MAX) 
                $rid =  self::CONSTANT_SPECIAL_RID_OFFSET + $ldap_object['uidNumber'];
            else
                $rid = $ldap_object['uidNumber'];
        
            $user_info['extensions']['samba']['sid'] = $sid . '-' . $rid;
        }

        // Convert to LDAP attributes
        //---------------------------

        $attributes = Utilities::convert_array_to_attributes($user_info['extensions']['samba'], $this->info_map, FALSE);

        // Handle special flag attributes
        //-------------------------------

        if (isset($user_info['extensions']['samba']['flags_disabled']) && $user_info['extensions']['samba']['flags_disabled'])
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
     * Runs after adding a user.
     *
     * Adds a user to Domain Users group.
     *
     * @param string $username  username
     * @param array  $user_info user information in hash array
     *
     * @return void
     */

    public function add_post_processing_hook($username, $user_info)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Bail if Samba has not been initialized
        //---------------------------------------

        $samba_driver = new OpenLDAP_Driver();

        if (!$samba_driver->is_directory_initialized())
            return;

        // Add user to domain_users group
        //-------------------------------

        $group = new Group_Driver('domain_users');

        $group->add_member($username);
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

        // Bail if Samba has not been initialized
        //---------------------------------------

        $samba_driver = new OpenLDAP_Driver();

        if (!$samba_driver->is_directory_initialized())
            return;

        // Generate info array
        //--------------------

        $info = Utilities::convert_attributes_to_array($attributes, $this->info_map);

        // Handle special flag attributes
        //-------------------------------

        // The 'D' flag indicates a disabled account
        if (isset($attributes['sambaAcctFlags']) && preg_match('/D/', $attributes['sambaAcctFlags'][0]))
            $info['disabled'] = TRUE;
        else
            $info['disabled'] = FALSE;

        // The 'L' flag indicates a locaked account
        if (isset($attributes['sambaAcctFlags']) && preg_match('/L/', $attributes['sambaAcctFlags'][0]))
            $info['locked'] = TRUE;
        else
            $info['locked'] = FALSE;

        // Return info array
        //------------------

        return $info;
    }

    /** 
     * Returns user info hash array.
     *
     * @return array user info array
     * @throws Engine_Exception
     */

    public function get_info_map_hook()
    {
        clearos_profile(__METHOD__, __LINE__);

        // Bail if Samba has not been initialized
        //---------------------------------------

        $samba_driver = new OpenLDAP_Driver();

        if (!$samba_driver->is_directory_initialized())
            return array();

        // Return info map
        //----------------

        return $this->info_map;
    }

    /** 
     * Update LDAP attributes hook.
     *
     * @param array $user_info   user information in hash array
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

        if (! isset($user_info['extensions']['samba']))
            return array();

        // Convert to LDAP attributes
        //---------------------------

        $attributes = Utilities::convert_array_to_attributes($user_info['extensions']['samba'], $this->info_map, FALSE);

        // Handle special flag attributes
        //-------------------------------

        return $attributes;
    }

    /**
     * Set password hook.
     *
     * @param array  $user_info       info user information in hash array
     * @param array  $password_object password LDAP object
     * @param string $password        password
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception 
     */

    public function set_password_attributes_hook($user_info, $password_object, $password)
    {
        clearos_profile(__METHOD__, __LINE__);

        $attributes = array();

        if (isset($user_info['extensions']['samba']['sid'])) {
            $attributes['sambaNTPassword'] = $password_object['clearMicrosoftNTPassword'];
            $attributes['sambaPwdLastSet'] = time();
        }
    
        return $attributes;
    }

    /** 
     * Unlock LDAP attributes hook.
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

    /**
     * Validates SID.
     *
     * @param string $sid SID
     *
     * @return error message if SID is invalid
     */

    public function validate_sid($sid)
    {
        clearos_profile(__METHOD__, __LINE__);

        // For testing exposing the SID in the GUI.  Disabled.
        // return "das ist bad sid.";
    }
}
