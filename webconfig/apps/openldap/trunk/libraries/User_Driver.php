<?php

/**
 * OpenLDAP user driver.
 *
 * @category   Apps
 * @package    OpenLDAP
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
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
clearos_load_language('directory');
clearos_load_language('users');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Country as Country;
use \clearos\apps\base\Engine as Engine;
use \clearos\apps\base\Folder as Folder;
use \clearos\apps\base\Shell as Shell;
use \clearos\apps\openldap\Directory_Driver as Directory_Driver;
use \clearos\apps\openldap\Utilities as Utilities;

clearos_load_library('base/Country');
clearos_load_library('base/Engine');
clearos_load_library('base/Folder');
clearos_load_library('base/Shell');
clearos_load_library('openldap/Directory_Driver');
clearos_load_library('openldap/OpenLDAP');
clearos_load_library('openldap/Utilities');

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;
use \clearos\apps\users\User_Not_Found_Exception as User_Not_Found_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('base/Validation_Exception');
clearos_load_library('users/User_Not_Found_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * User class.
 *
 * @category   Apps
 * @package    OpenLDAP
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/openldap/
 */

class User_Driver extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const LOG_TAG = 'user';

    const PATH_EXTENSIONS = 'config/extensions';

    const DEFAULT_HOMEDIR_PATH = '/home';
    const DEFAULT_HOMEDIR_PERMS = '0755';
    const DEFAULT_LOGIN = '/sbin/nologin';
    const DEFAULT_USER_GROUP = 'allusers';
    const DEFAULT_USER_GROUP_ID = '63000';

    const COMMAND_LDAPPASSWD = '/usr/bin/ldappasswd';
    const COMMAND_SYNCMAILBOX = '/usr/sbin/syncmailboxes';
    const COMMAND_SYNCUSERS = '/usr/sbin/syncusers';

    const CONSTANT_TYPE_SHA = 'sha';
    const CONSTANT_TYPE_SHA1 = 'sha1';
    const CONSTANT_TYPE_LANMAN = 'lanman';
    const CONSTANT_TYPE_NT = 'nt';

    const STATUS_LOCKED = 'locked';
    const STATUS_UNLOCKED = 'unlocked';
    const STATUS_ENABLED = 'enabled';
    const STATUS_DISABLED = 'disabled';

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $ldaph = NULL;
    protected $username;
    protected $core_classes;
    protected $attribute_map;
    protected $info_map;
    protected $reserved_usernames = array('root', 'manager');
    protected $plugins = NULL;
    protected $extensions = NULL;
    protected $path_extensions = NULL;

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * User constructor.
     */

    public function __construct($username)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->username = $username;

        $this->core_classes = array(
            'top',
            'posixAccount',
            'shadowAccount',
            'inetOrgPerson',
            'clearAccount'
        );

        $this->path_extensions = clearos_app_base('openldap') . '/config/extensions';

        // The info_map array maps user_info to LDAP attributes and object classes.
        // In the future, the object class might need to be an array... a simple
        // one-to-one ratio will do for now.  The "core" objects are: 
        //
        // - top
        // - posixAccount
        // - shadowAccount
        // - inetOrgPerson
        // - pcnAccount

        // FIXME: no mobile phone attribute?

        $this->info_map = array(
            'city' => array(
                'type' => 'string',
                'required' => FALSE,
                'validator' => 'validate_city',
                'objectclass' => 'core',
                'attribute' => 'l' 
            ),

            'country' => array(
                'type' => 'string',
                'required' => FALSE,
                'validator' => 'validate_country',
                'objectclass' => 'core',
                'attribute' => 'c'
            ),

            'description' => array(
                'type' => 'string',
                'required' => FALSE,
                'validator' => 'validate_description',
                'objectclass' => 'core',
                'attribute' => 'description'
            ),

            'display_name' => array(
                'type' => 'string',
                'required' => FALSE,
                'validator' => 'validate_display_name',
                'objectclass' => 'core',
                'attribute' => 'displayName' 
            ),

            'fax' => array(
                'type' => 'string',
                'required' => FALSE,
                'validator' => 'validate_fax_number',
                'objectclass' => 'core',
                'attribute' => 'facsimileTelephoneNumber' 
            ),

            'first_name' => array(
                'type' => 'string',
                'required' => FALSE,
                'validator' => 'validate_first_name',
                'objectclass' => 'core',
                'attribute' => 'givenName'
            ),

            'gid_number' => array(
                'type' => 'integer',
                'required' => FALSE,
                'validator' => 'validate_gid_number',
                'objectclass' => 'core',
                'attribute' => 'gidNumber'
            ),

            'home_directory' => array(
                'type' => 'string',
                'required' => FALSE,
                'validator' => 'validate_home_directory',
                'objectclass' => 'core',
                'attribute' => 'homeDirectory'
            ),

            'last_name' => array(
                'type' => 'string',
                'required' => TRUE,
                'validator' => 'validate_last_name',
                'objectclass' => 'core',
                'attribute' => 'sn',
                'locale' => lang('directory_last_name')
            ),

            'login_shell' => array(
                'type' => 'string',
                'required' => FALSE,
                'validator' => 'validate_login_shell',
                'objectclass' => 'core',
                'attribute' => 'loginShell'
            ),

            'mail' => array(
                'type' => 'string',
                'required' => FALSE,
                'validator' => 'validate_mail',
                'objectclass' => 'core',
                'attribute' => 'mail'
            ),

            'organization' => array(
                'type' => 'string',
                'required' => FALSE,
                'validator' => 'validate_organization',
                'objectclass' => 'core',
                'attribute' => 'o'
            ),

            'password' => array(
                'type' => 'string',
                'required' => FALSE,
                'validator' => 'validate_password',
                'objectclass' => 'core',
                'attribute' => 'userPassword',
                'locale' => lang('base_password')
            ),

            'postal_code' => array(
                'type' => 'string',
                'required' => FALSE,
                'validator' => 'validate_postal_code',
                'objectclass' => 'core',
                'attribute' => 'postalCode'
            ),

            'post_office_box' => array(
                'type' => 'string',
                'required' => FALSE,
                'validator' => 'validate_post_office_box',
                'objectclass' => 'core',
                'attribute' => 'postOfficeBox'
            ),

            'region' => array(
                'type' => 'string',
                'required' => FALSE,
                'validator' => 'validate_region',
                'objectclass' => 'core',
                'attribute' => 'st'
            ),

            'room_number' => array(
                'type' => 'string',
                'required' => FALSE,
                'validator' => 'validate_room_number',
                'objectclass' => 'core',
                'attribute' => 'roomNumber'
            ),

            'street' => array(
                'type' => 'string',
                'required' => FALSE,
                'validator' => 'validate_street',
                'objectclass' => 'core',
                'attribute' => 'street'
            ),

            'telephone' => array(
                'type' => 'string',
                'required' => FALSE,
                'validator' => 'validate_telephone_number',
                'objectclass' => 'core',
                'attribute' => 'telephoneNumber'
            ),

            'title' => array(
                'type' => 'string',
                'required' => FALSE,
                'validator' => 'validate_title',
                'objectclass' => 'core',
                'attribute' => 'title'
            ),

            'uid_number' => array(
                'type' => 'integer',
                'required' => FALSE,
                'validator' => 'IsValidUidNumber',
                'objectclass' => 'core',
                'attribute' => 'uidNumber'
            ),

            'unit' => array(
                'type' => 'string',
                'required' => FALSE,
                'validator' => 'validate_organization_unit',
                'objectclass' => 'core',
                'attribute' => 'ou'
            ),
        );
/*

            'aliases'        => array( 'type' => 'stringarray',  'required' => FALSE, 'validator' => 'IsValidAlias', 'objectclass' => 'pcnMailAccount', 'attribute' => 'pcnMailAliases' ),
            'forwarders'    => array( 'type' => 'stringarray',  'required' => FALSE, 'validator' => 'IsValidForwarder', 'objectclass' => 'pcnMailAccount', 'attribute' => 'pcnMailForwarders' ),
            'pbxState'        => array( 'type' => 'integer', 'required' => FALSE, 'validator' => 'validate_room_number', 'objectclass' => 'pcnPbxAccount', 'attribute' => 'pcnPbxState' ),
            'ftpFlag'        => array( 'type' => 'boolean', 'required' => FALSE, 'validator' => 'IsValidFlag', 'objectclass' => 'pcnFTPAccount', 'attribute' => 'pcnFTPFlag' , 'passwordfield' => 'pcnFTPPassword', 'passwordtype' => self::CONSTANT_TYPE_SHA ),
            'mailFlag'        => array( 'type' => 'boolean', 'required' => FALSE, 'validator' => 'IsValidFlag', 'objectclass' => 'pcnMailAccount', 'attribute' => 'pcnMailFlag' , 'passwordfield' => 'pcnMailPassword', 'passwordtype' => self::CONSTANT_TYPE_SHA ),
            'googleAppsFlag'    => array( 'type' => 'boolean', 'required' => FALSE, 'validator' => 'IsValidFlag', 'objectclass' => 'pcnGoogleAppsAccount', 'attribute' => 'pcnGoogleAppsFlag' , 'passwordfield' => 'pcnGoogleAppsPassword', 'passwordtype' => self::CONSTANT_TYPE_SHA1 ),
            'openvpnFlag'    => array( 'type' => 'boolean', 'required' => FALSE, 'validator' => 'IsValidFlag', 'objectclass' => 'pcnOpenVPNAccount', 'attribute' => 'pcnOpenVPNFlag' , 'passwordfield' => 'pcnOpenVPNPassword', 'passwordtype' => self::CONSTANT_TYPE_SHA ),
            'pptpFlag'        => array( 'type' => 'boolean', 'required' => FALSE, 'validator' => 'IsValidFlag', 'objectclass' => 'pcnPPTPAccount', 'attribute' => 'pcnPPTPFlag' , 'passwordfield' => 'pcnPPTPPassword', 'passwordtype' => self::CONSTANT_TYPE_NT ),
            'proxyFlag'        => array( 'type' => 'boolean', 'required' => FALSE, 'validator' => 'IsValidFlag', 'objectclass' => 'pcnProxyAccount', 'attribute' => 'pcnProxyFlag' , 'passwordfield' => 'pcnProxyPassword', 'passwordtype' => self::CONSTANT_TYPE_SHA ),
            'webconfigFlag'    => array( 'type' => 'boolean', 'required' => FALSE, 'validator' => 'IsValidFlag', 'objectclass' => 'pcnWebconfigAccount', 'attribute' => 'pcnWebconfigFlag' , 'passwordfield' => 'pcnWebconfigPassword', 'passwordtype' => self::CONSTANT_TYPE_SHA ),
            'webFlag'        => array( 'type' => 'boolean', 'required' => FALSE, 'validator' => 'IsValidFlag', 'objectclass' => 'pcnWebAccount', 'attribute' => 'pcnWebFlag' , 'passwordfield' => 'pcnWebPassword', 'passwordtype' => self::CONSTANT_TYPE_SHA ),
*/

        // The attribute_map contains the reverse mapping of the above info_map.

        $this->attribute_map = array();
    
        foreach ($this->info_map as $info => $details)
            $this->attribute_map[$details['attribute']] = array( 'objectclass' => $details['objectclass'], 'info' => $info );
    }

    /**
     * Adds a user to the system.
     *
     * @param array $user_info user information
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function add($user_info)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->ldaph == NULL)
            $this->ldaph = Utilities::get_ldap_handle();

        // Validate user_info
        //-------------------

        Validation_Exception::is_valid($this->validate_username($this->username));
        Validation_Exception::is_valid($this->validate_user_info($user_info));

        // Convert user_info into LDAP attributes
        //---------------------------------------

        $ldap_object = $this->convert_user_array_to_attributes($user_info, FALSE);

        // Add LDAP attributes from extensions
        //------------------------------------

        foreach ($this->_get_extensions() as $extension_name) {
            clearos_load_library($extension_name . '/OpenLDAP_Extension');
            $class = '\clearos\apps\\' . $extension_name . '\OpenLDAP_Extension';
            $extension = new $class();

            $attributes = $extension->add_attributes_hook($user_info);

            $ldap_object = array_merge($attributes, $ldap_object);
        }

        // Validation revisited - check for DN uniqueness
        //-----------------------------------------------

        // The "common name" is usually a derived field (first name + last name)
        // and it is used for the DN (distinguished name) as a unique identifier.
        // That means two people with the same name cannot exist in the directory.

        $directory = new Directory_Driver();
        $dn = 'cn=' . $this->ldaph->dn_escape($ldap_object['cn']) . ',' . $directory->get_users_ou();

        Validation_Exception::is_valid($this->validate_dn($dn));

        // Add the LDAP user object
        //-------------------------

print_r($ldap_object);
        //$this->ldaph->add($dn, $ldap_object);

        // Initialize default group memberships
        /*
        // FIXME: revisit
        $groupmanager = new GroupManager();
        $groupmanager->InitalizeGroupMemberships($this->username);
        */

        // Run post-add methods in extensions
        //-----------------------------------

        foreach ($this->_get_extensions() as $extension_name) {
            clearos_load_library($extension_name . '/OpenLDAP_Extension');
            $class = '\clearos\apps\\' . $extension_name . '\OpenLDAP_Extension';
            $extension = new $class();

            if (method_exists($extension, 'add_post_processing_hook'))
                $attributes = $extension->add_post_processing_hook();
        }

        // Ping the synchronizer
        //----------------------

        $this->_synchronize();
    }

    /**
     * Checks the password for the user.
     *
     * @param string $password password for the user
     * @param string $attribute LDAP attribute
     *
     * @return boolean TRUE if password is correct
     * @throws Engine_Exception, User_Not_Found_Exception
     */

    public function check_password($password, $attribute)
    {
        clearos_profile(__METHOD__, __LINE__);

        sleep(2); // a small delay

        if ($attribute == 'pcnWebconfigPassword') {
            $shapassword = '{sha}' . $this->_calculate_sha_password($password);
        } else {
            return FALSE;
        }

        if ($this->ldaph == NULL)
            $this->ldaph = Utilities::get_ldap_handle();

        $attrs = $this->_get_user_attributes();

        if (isset($attrs[$attribute][0]) && ($shapassword == $attrs[$attribute][0]))
            return TRUE;
        else
            return FALSE;
    }

    /**
     * Deletes a user from the system.
     *
     * @return void
     */

    public function delete()
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->ldaph == NULL)
            $this->ldaph = Utilities::get_ldap_handle();

        try {
            $dn = $this->_get_dn_for_uid($this->username);

            // Delete the user from all the groups
            /*
            FIXME: revisit
            $groupmanager = new GroupManager();
            $groupmanager->DeleteGroupMemberships($this->username);
            */

            // Delete the IPlex PBX user
            /* FIXME: move to extension
            if (file_exists(CLEAROS_CORE_DIR . "/iplex/Users.class.php")) {
                require_once(CLEAROS_CORE_DIR . "/iplex/Users.class.php");
                $iplex_user = new IPlexUser();
                if($iplex_user->Exists($this->username))
                    $iplex_user->DeleteIPlexPBXUser($this->username);
            }

            // TODO: only set this if mailbox exists
            $ldap_object['kolabDeleteflag'] = $this->ldaph->GetDefaultHomeServer();
            */

            foreach ($this->_get_extensions() as $extension_name) {
                // FIXME: removed hard-coded paths
                clearos_load_library('directory/extensions/' . $extension_name . '_OpenLDAP');
                $class = '\clearos\apps\directory\extensions\\' . $extension_name . '_OpenLDAP';
                $extension = new $class($dn);

                // $extension->delete();
            }

            // FIXME: talk to David about this one.  In practice, every slave node
            // should signal a "delete complete" status.  When all the slave nodes
            // have checked in, the user object can be deleted.
            //
            // That won't work well if a box is offline

            // Disable the user now - delete asynchronously
            //---------------------------------------------
            // Write random garbage into passwd field to lock the user out

            $ldap_object = array();
            $ldap_object['userPassword'] = '{sha}' . base64_encode(pack('H*', sha1(mt_rand())));
            $ldap_object['clearAccountStatus'] = self::STATUS_DISABLED;

            $this->ldaph->modify($dn, $ldap_object);
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_WARNING);
        }

        $this->_synchronize();
    }

    /**
     * Updates a user on the system.
     *
     * @param array $user_info user information
     * @param array $acl access control list
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception, User_Not_Found_Exception
     */

    public function update($user_info, $acl = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->ldaph == NULL)
            $this->ldaph = Utilities::get_ldap_handle();

        // Validate
        //---------

        if (isset($acl)) {
            foreach ($user_info as $key => $value) {
                if (! in_array($key, $acl))
                    throw new Engine_Exception(USER_LANG_ERRMSG_ACCESS_CONTROL_VIOLATION, CLEAROS_WARNING);
            }
        }

        // User does not exist error
        //--------------------------

        $attrs = $this->_get_user_attributes();

        if (!isset($attrs['uid'][0]))
            throw new User_Not_Found_Exception();

        // Input validation errors
        //------------------------

        if (! $this->validate_user_info($user_info, TRUE))
            throw new Validation_Exception(LOCALE_LANG_INVALID);

        // Convert user info to LDAP object
        //---------------------------------

        $ldap_object = $this->convert_user_array_to_attributes($user_info, TRUE);

        // TODO: Update PBX user via plugin
        //---------------------------------

        if (file_exists(CLEAROS_CORE_DIR . "/iplex/Users.class.php")) {
            require_once(CLEAROS_CORE_DIR . "/iplex/Users.class.php");

            try {
                $iplex_user = new IPlexUser();

                if (array_key_exists('pbxFlag', $user_info)) {
                    // Delete PBX user
                    if (($user_info['pbxFlag'] != 1) && $iplex_user->Exists($this->username)) {
                        $iplex_user->DeleteIPlexPBXUser($this->username);
                    // Update PBX user
                    } else if (($user_info['pbxFlag'] == 1) && $iplex_user->Exists($this->username)) {
                        $iplex_user->UpdateIPlexPBXUser($user_info, $this->username);
                    // Add PBX user
                    } else if ($user_info['pbxFlag'] == 1) {
                        if ($iplex_user->CCAddUser($user_info, $this->username) == 0) {
                            // CCAddUser failed to add PBX user, clear pbx settings so they aren't saved
                            unset($ldap_object['pcnPbxExtension']);
                            unset($ldap_object['pcnPbxState']);
                            unset($ldap_object['pcnPbxPresenceState']);
                        }
                    }
                }
            } catch (Exception $e) {
                throw new Engine_Exception(clearos_exception_message($e), CLEAROS_WARNING);
            }
        }

        // Handle LDAP
        //------------

        $new_dn = "cn=" . Ldap::DnEscape($ldap_object['cn']) . "," . ClearDirectory::GetUsersOu();

        if ($new_dn != $attrs['dn']) {
            $rdn = "cn=" . Ldap::DnEscape($ldap_object['cn']);
            $this->ldaph->Rename($attrs['dn'], $rdn, ClearDirectory::GetUsersOu());
        }

        $this->ldaph->modify($new_dn, $ldap_object);

        $this->_synchronize();
    }

    /**
     * Checks if given user exists.
     *
     *
     * @return boolean TRUE if user exists
     * @throws Engine_Exception
     */

    public function exists()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $attrs = $this->_get_user_attributes();
        } catch (User_Not_Found_Exception $e) {
            // Expected
        }

        if (isset($attrs['uid'][0]))
            return TRUE;
        else
            return FALSE;
    }

    /**
     * Retrieves information for user from LDAP.
     *
     * @throws Engine_Exception
     *
     * @return array user details
     */

    public function get_info()
    {
        clearos_profile(__METHOD__, __LINE__);

        // Get user info
        //--------------

        $attributes = $this->_get_user_attributes();

        $info['core'] = Utilities::convert_attributes_to_array($attributes, $this->info_map);

        // TODO: should uid be put into the info_map?
        // TODO: should uid be returned given that it's already known (passed in to constructor)
        $info['core']['uid'] = $attributes['uid'][0];

        // Add user info from extensions
        //------------------------------

        foreach ($this->_get_extensions() as $extension_name) {
            clearos_load_library($extension_name . '/OpenLDAP_Extension');
            $class = '\clearos\apps\\' . $extension_name . '\OpenLDAP_Extension';
            $extension = new $class();

            $info[$extension_name] = $extension->get_info_hook($attributes);
        }

        return $info;
    }

    /**
     * Reset the passwords for the user.
     *
     * Similar to SetPassword, but it uses administrative privileges.  This is
     * typically used for resetting a password while bypassing password
     * policies.  For example, an administrator may need to set a password
     * even when the password policy dictates that the password is not allowed
     * to change (minimum password age).
     *
     * @param string $password password
     * @param string $verify password verify
     * @param string $requested_by username requesting the password change
     * @param boolean $includesamba workaround for Samba password changes
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function reset_password($password, $verify, $requested_by, $includesamba = TRUE)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validate
        //---------

        if (! $this->validate_username($requested_by, FALSE, FALSE))
            throw new Validation_Exception(LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . LOCALE_LANG_USERNAME);

        if (! $this->validate_password_and_verify($password, $verify)) {
            $errors = $this->GetValidationErrors();
            throw new Validation_Exception($errors[0]);
        }

        // Set passwords in LDAP
        //----------------------

        $this->_SetPassword($password, $includesamba);

        Logger::Syslog(self::LOG_TAG, "password reset for user - " . $this->username . " / by - " . $requested_by);
    }

    /**
     * Sets the password for the user.
     *
     * Ignore the includesamba flag,  It is a workaround required for password
     * changes using the change password tool from Windows desktops.
     *
     * @param string $oldpassword old password
     * @param string $password password
     * @param string $verify password verify
     * @param string $requested_by username requesting the password change
     * @param boolean $includesamba workaround for Samba password changes
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_password($oldpassword, $password, $verify, $requested_by, $includesamba = TRUE)
    {
        clearos_profile(__METHOD__, __LINE__);

        // TODO: something odd is going on when password histories are enabled.
        // The following block of code will fail if the sleep(1) is omitted.
        //
        //    $password = "password';
        //    $user = new User("test1");
        //    $user_info['telephone'] = '867-5309';
        //    $user->Update($user_info);
        //    $user->SetPassword($password, $password, "testscript");

        // Validate
        //---------

        if (! $this->validate_username($requested_by, FALSE, FALSE))
            throw new Validation_Exception(LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . LOCALE_LANG_USERNAME);

        if (! $this->validate_password_and_verify($password, $verify)) {
            $errors = $this->GetValidationErrors();
            throw new Validation_Exception($errors[0]);
        }

        // Sanity check the password using the ldappasswd command
        //-------------------------------------------------------

        if ($this->ldaph == NULL)
            $this->ldaph = Utilities::get_ldap_handle();

        try {
            $dn = $this->_get_dn_for_uid($this->username);

            sleep(2); // see comment above

            $shell = new Shell();
            $intval = $shell->Execute(User::COMMAND_LDAPPASSWD, 
                '-x ' .
                '-D "' . $dn . '" ' .
                '-w "' . $oldpassword . '" ' .
                '-s "' . $password . '" ' .
                '"' . $dn . '"', 
                FALSE);
        
            if ($intval != 0)
                $output = $shell->GetOutput();
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_WARNING);
        }

        if (! empty($output)) {
            // Dirty.  Try to catch common error strings so that we can translate.
            $errormessage = isset($output[1]) ? $output[1] : $output[0]; // Default if our matching fails

            foreach ($output as $line) {
                if (preg_match("/Invalid credentials/", $line))
                    $errormessage = USER_LANG_OLD_PASSWORD_INVALID;
                else if (preg_match("/Password is in history of old passwords/", $line))
                    $errormessage = USER_LANG_PASSWORD_IN_HISTORY;
                else if (preg_match("/Password is not being changed from existing value/", $line))
                    $errormessage = USER_LANG_PASSWORD_NOT_CHANGED;
                else if (preg_match("/Password fails quality checking policy/", $line))
                    $errormessage = USER_LANG_PASSWORD_VIOLATES_QUALITY_CHECK;
                else if (preg_match("/Password is too young to change/", $line))
                    $errormessage = USER_LANG_PASSWORD_TOO_YOUNG;
            }

            throw new Validation_Exception($errormessage);
        }

        // Set passwords in LDAP
        //----------------------

        $this->_SetPassword($password, $includesamba);

        Logger::Syslog(self::LOG_TAG, "password updated for user - " . $this->username . " / by - " . $requested_by);
    }

    /**
     * Unlocks a user account.
     *
     *
     * @return void
     * @throws Engine_Exception
     */

    public function unlock()
    {
        clearos_profile(__METHOD__, __LINE__);

        // This only applies to Samba right now
        if (file_exists(CLEAROS_CORE_DIR . "/api/Samba.class.php")) {
            require_once("Samba.class.php");

            $samba = new Samba();
            $samba->UnlockAccount($this->username);
        }
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validation routine for mail aliases.
     *
     * @param string $alias alias
     *
     * @return boolean TRUE if alias is valid
     */

    public function validate_alias($alias)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (empty($alias) || preg_match("/^([a-z0-9_\-\.\$]+)$/", $alias)) {
            return TRUE;
        } else {
            $this->AddValidationError(
                LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . USER_LANG_MAIL_ALIAS, __METHOD__, __LINE__
            );
            return FALSE;
        }
    }

    /**
     * Validation routine for city.
     *
     * @param string $city city
     *
     * @return string error message is city is invalid
     */

    public function validate_city($city)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (preg_match("/([:;\/#!@])/", $city))
            return lang('directory_validate_city_invalid');
    }

    /**
     * Validation routine for country.
     *
     * @param string $country country
     *
     * @return string error message if country is invalid
     */

    public function validate_country($country)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $country_object = new Country();
            $country_list = $country_object->get_list();
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_WARNING);
        }

        if (! array_key_exists($country, $country_list))
            return lang('directory_validate_country_invalid');
    }

    /**
     * Validation routine for description.
     *
     * @param string $description description
     *
     * @return string error message if description is invalid
     */

    public function validate_description($description)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (preg_match("/([:;\/#!@])/", $description))
            return lang('directory_validate_description_invalid');
    }

    /**
     * Validation routine for display name.
     *
     * @param string $display_name display name
     *
     * @return string error message if display name is invalid
     */

    public function validate_display_name($display_name)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (preg_match("/([:;\/#!@])/", $display_name))
            return lang('directory_validate_display_name_invalid');
    }

    /**
     * Validation routine for DN (distinguised name).
     *
     * @param string $dn distinguised name
     *
     * @return string error message if DN is invalid
     * @throws Engine_Exception
     */

    protected function validate_dn($dn)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->ldaph == NULL)
            $this->ldaph = Utilities::get_ldap_handle();

        if ($this->ldaph->exists($dn))
            return "FIXME: full name already exists";
    }

    /**
     * Validation routine for fax number.
     *
     * @param string $number fax number
     *
     * @return string error message if fax number is invalid
     */

    public function validate_fax_number($number)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (preg_match("/([:;\/#!@])/", $number))
            return lang('directory_validate_fax_invalid');
    }

    /**
     * Validation routine for first name.
     *
     * @param string $name first name
     *
     * @return string error message if first name is invalid
     */

    public function validate_first_name($name)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (preg_match("/([:;\/#!@])/", $name))
            return lang('directory_validate_first_name_invalid');
    }

    /**
     * Validation routine for GID number.
     *
     * @param integer $gid_number GID number
     *
     * @return string error message if GID number is invalid
     */

    public function validate_gid_number($gid_number)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! preg_match("/^\d+/", $gid_number))
            return lang('directory_validate_gid_number_invalid');
    }

    /**
     * Validation routine for home directory
     *
     * @param string $homedir home directory
     *
     * @return string error message if home directory is invalid
     */

    public function validate_home_directory($homedir)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (preg_match("/([:;#!@])/", $homedir))
            return lang('directory_validate_home_directory_invalid');
    }

    /**
     * Validation routine for last name.
     *
     * @param string $name last name
     *
     * @return string error message if last name is invalid
     */

    public function validate_last_name($name)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (preg_match("/([:;\/#!@])/", $name))
            return lang('directory_validate_last_name_invalid');
    }

    /**
     * Validation routine for login shell.
     *
     * @param string $shell login shell
     *
     * @return boolean TRUE if login shell is valid
     */

    public function validate_login_shell($loginshell)
    {
        clearos_profile(__METHOD__, __LINE__);

// FIXME
return '';
        try {
            $shell = new Shell();
            $allshells = $shell->GetList();
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_WARNING);
        }

        if (in_array($loginshell, $allshells)) {
            return TRUE;
        } else {
            $this->AddValidationError(
                LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . USER_LANG_SHELL, __METHOD__, __LINE__
            );
            return FALSE;
        }
    }

    /**
     * Validation routine for mail address.
     *
     * @param string $mail mail address
     *
     * @return boolean TRUE if mail address is valid
     */

    public function validate_mail($address)
    {
        clearos_profile(__METHOD__, __LINE__);

        // TODO: new regex
        if (preg_match("/^([a-z0-9_\-\.\$]+)@/", $address)) {
            return TRUE;
        } else {
            $this->AddValidationError(
                LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . USER_LANG_MAIL_ADDRESS, __METHOD__, __LINE__
            );
            return FALSE;
        }
    }

    /**
     * Validation routine for quota.
     *
     * @param integer $quota quota
     *
     * @return boolean TRUE if quota is valid
     */

    public function validate_mail_quota($quota)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ((! $quota) || preg_match("/\d+/", $quota)) {
            return TRUE;
        } else {
            $this->AddValidationError(
                LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . USER_LANG_QUOTA, __METHOD__, __LINE__
            );
            return FALSE;
        }
    }

    /**
     * Validation routine for organization.
     *
     * @param string $organization organization
     *
     * @return boolean TRUE if organization is valid
     */

    public function validate_organization($organization)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!preg_match("/([:;\/#!@])/", $organization)) {
            return TRUE;
        } else {
            $this->AddValidationError(
                LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . ORGANIZATION_LANG_ORGANIZATION, __METHOD__, __LINE__
            );
            return FALSE;
        }
    }

    /**
     * Validation routine for organization unit.
     *
     * @param string $unit organization unit
     *
     * @return boolean TRUE if organization unit is valid
     */

    public function validate_organization_unit($unit)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!preg_match("/([:;\/#!@])/", $unit)) {
            return TRUE;
        } else {
            $this->AddValidationError(
                LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . ORGANIZATION_LANG_ORGANIZATION_UNIT, __METHOD__, __LINE__
            );
            return FALSE;
        }
    }

    /**
     * Password validation routine.
     *
     * @param string $password password
     *
     * @return boolean TRUE if password is valid
     */

    public function validate_password($password)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (preg_match("/[\|;\*]/", $password) || !preg_match("/^[a-zA-Z0-9]/", $password)) {
            $this->AddValidationError(LOCALE_LANG_ERRMSG_PASSWORD_INVALID, __METHOD__, __LINE__);
            return FALSE;
        } else {
            return TRUE;
        }
    }

    /**
     * Password/verify validation routine.
     *
     * @param string $password password
     * @param string $verify verify
     *
     * @return boolean TRUE if password and verify are valid and equal
     */

    public function validate_password_and_verify($password, $verify)
    {
        clearos_profile(__METHOD__, __LINE__);

        $is_valid = TRUE;

        if (empty($password)) {
            $this->AddValidationError(LOCALE_LANG_ERRMSG_REQUIRED_PARAMETER_IS_MISSING . " - " . LOCALE_LANG_PASSWORD, __METHOD__, __LINE__);
            $is_valid = FALSE;
        }

        if (empty($verify)) {
            $this->AddValidationError(LOCALE_LANG_ERRMSG_REQUIRED_PARAMETER_IS_MISSING . " - " . LOCALE_LANG_VERIFY, __METHOD__, __LINE__);
            $is_valid = FALSE;
        }

        if ($is_valid) {
            if ($password == $verify) {
                $is_valid = $this->validate_password($password);
            } else {
                $this->AddValidationError(LOCALE_LANG_ERRMSG_PASSWORD_MISMATCH, __METHOD__, __LINE__);
                $is_valid = FALSE;
            }
        }

        return $is_valid;
    }

    /**
     * Validation routine for post office box.
     *
     * @param string $pobox post office box
     *
     * @return boolean TRUE if post office box is valid
     */

    public function validate_post_office_box($pobox)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!preg_match("/([:;\/#!@])/", $pobox)) {
            return TRUE;
        } else {
            $this->AddValidationError(
                LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . ORGANIZATION_LANG_POST_OFFICE_BOX, __METHOD__, __LINE__
            );
            return FALSE;
        }
    }

    /**
     * Validation routine for postal code.
     *
     * @param string $postalcode postal code
     *
     * @return boolean TRUE if postal code is valid
     */

    public function validate_postal_code($postalcode)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!preg_match("/([:;\/#!@])/", $postalcode)) {
            return TRUE;
        } else {
            $this->AddValidationError(
                LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . ORGANIZATION_LANG_POSTAL_CODE, __METHOD__, __LINE__
            );
            return FALSE;
        }
    }

    /**
     * Validation routine for room number.
     *
     * @param string $room room number
     *
     * @return boolean TRUE if room number is valid
     */

    public function validate_room_number($room)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!preg_match("/([:;\/#!@])/", $room)) {
            return TRUE;
        } else {
            $this->AddValidationError(
                LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . ORGANIZATION_LANG_ROOM_NUMBER, __METHOD__, __LINE__
            );
            return FALSE;
        }
    }

    /**
     * Validation routine for state or province.
     *
     * @param string $region region
     *
     * @return boolean TRUE if region is valid
     */

    public function validate_region($region)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!preg_match("/([:;\/#!@])/", $region)) {
            return TRUE;
        } else {
            $this->AddValidationError(
                LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . ORGANIZATION_LANG_REGION, __METHOD__, __LINE__
            );
            return FALSE;
        }
    }

    /**
     * Validation routine for street.
     *
     * @param string $street street
     *
     * @return boolean TRUE if street is valid
     */

    public function validate_street($street)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!preg_match("/([:;\/#!@])/", $street)) {
            return TRUE;
        } else {
            $this->AddValidationError(
                LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . ORGANIZATION_LANG_STREET, __METHOD__, __LINE__
            );
            return FALSE;
        }
    }

    /**
     * Validation routine for phone number extension.
     *
     * @param string $extension phone number extension
     *
     * @return boolean TRUE if phone number extension is valid
     */

    public function validate_telephone_extension($extension)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!preg_match("/([:;\/#!@])/", $extension)) {
            return TRUE;
        } else {
            $this->AddValidationError(
                LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . USER_LANG_EXTENSION, __METHOD__, __LINE__
            );
            return FALSE;
        }
    }

    /**
     * Validation routine for phone number.
     *
     * @param string $number phone number
     *
     * @return boolean TRUE if phone number is valid
     */

    public function validate_telephone_number($number)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!preg_match("/([:;\/#!@])/", $number)) {
            return TRUE;
        } else {
            $this->AddValidationError(
                LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . ORGANIZATION_LANG_PHONE, __METHOD__, __LINE__
            );
            return FALSE;
        }
    }

    /**
     * Validation routine for title.
     *
     * @param string $title title
     *
     * @return boolean TRUE if title is valid
     */

    public function validate_title($title)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!preg_match("/([:;\/#!@])/", $title)) {
            return TRUE;
        } else {
            $this->AddValidationError(
                LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . USER_LANG_TITLE, __METHOD__, __LINE__
            );
            return FALSE;
        }
    }

    /**
     * Validation routine for UID number.
     *
     * @param integer $uidnumber UID number
     *
     * @return boolean TRUE if UID number is valid
     */

    public function validate_uid_number($uidnumber)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (preg_match("/^\d+/", $uidnumber)) {
            return TRUE;
        } else {
            $this->AddValidationError(
                LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . USER_LANG_UIDNUMBER, __METHOD__, __LINE__
            );
            return FALSE;
        }
    }

    /**
     * Validation routine for username.
     *
     * @param string  $username         username
     * @param boolean $allow_reserved   check for reserved usernames
     * @param boolean $check_uniqueness check for uniqueness
     *
     * @return string error message if username is invalid
     */

    public function validate_username($username, $check_reserved = TRUE, $check_uniqueness = TRUE)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!preg_match("/^([a-z0-9_\-\.\$]+)$/", $username))
            return lang('users_username_is_invalid');

        if ($check_reserved && in_array($username, $this->reserved_usernames))
            return lang('users_username_is_reserved');

        if ($check_uniqueness) {
            $directory = new Directory_Driver();
            $message = $directory->check_uniqueness($username);

            if ($message)
                return $message;
        }
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Calculates Lanman password.
     *
     * @access private
     *
     * @return string Lanman password
     */

    public function _calculate_lanman_password($password)
    {
        clearos_profile(__METHOD__, __LINE__);

        // FIXME: need to build mcrypt into webconfig-php
        // FIXME: can we remove LanMan?
        return 'FIXME';

        $password = substr(strtoupper($password), 0, 14);

        while (strlen($password) < 14)
             $password .= "\0";

        $deshash = $this->_generate_des_hash(substr($password, 0, 7)) . $this->_generate_des_hash(substr($password, 7, 7));

        return strtoupper(bin2hex($deshash));
    }

    /**
     * Calculates NT password.
     *
     * @access private
     *
     * @return string NT password
     */

    public function _calculate_nt_password($password)
    {
        clearos_profile(__METHOD__, __LINE__);

        return strtoupper(bin2hex(hash('md4', self::_string_to_unicode($password))));
    }

    /**
     * Calculates SHA password.
     *
     * @access private
     *
     * @return string SHA password
     */

    public function _calculate_sha_password($password)
    {
        clearos_profile(__METHOD__, __LINE__);

        return base64_encode(pack('H*', sha1($password)));
    }

    /**
     * Converts SHA password to SHA1.
     *
     * @access private
     *
     * @return string SHA1 password
     */

    public function _convert_sha_to_sha1($shapassword)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Strip out prefix if it exists
        $shapassword = preg_replace("/^{sha}/", "", $shapassword);

        $sha1 = unpack("H*", base64_decode($shapassword));

        return $sha1[1];
    }

    /**
     * Converts a user_info array into LDAP attributes.
     *
     * @access private
     * @param array   $user_info user information array
     * @param boolean $is_modify set to TRUE if using results on LDAP modify
     *
     * @return array LDAP attribute array
     * @throws Engine_Exception, Validation_Exception
     */

    public function convert_user_array_to_attributes($user_info, $is_modify)
    {
        clearos_profile(__METHOD__, __LINE__);

        /**
         * This method is the meat and potatoes of the User class.  There
         * are quite a few non-intuitive steps in here, but hopefully the 
         * documentation will guide the way.
         */

        $ldap_object = array();
        $old_attributes = array();
        $directory = new Directory_Driver();

        try {
            $old_attributes = $this->_get_user_attributes();
        } catch (User_Not_Found_Exception $e) {
            // Not fatal
        }

        /**
         * Step 1 - convert user_info fields to LDAP fields
         *
         * Use the utility class for this job.
         */

        $ldap_object = Utilities::convert_array_to_attributes($user_info['core'], $this->info_map);
/*
        foreach ($user_info as $info => $value) {
            if (isset($this->info_map[$info]['attribute'])) {
                $attribute = $this->info_map[$info]['attribute'];

                // Delete
                if ($value === NULL) {
                    if ($is_modify)
                        $ldap_object[$attribute] = array();

                // Add/modify
                } else {
                    if ($this->info_map[$info]['type'] == 'boolean') {
                        $ldap_object[$attribute] = ($value) ? 'TRUE' : 'FALSE';
                    } else {
                        $ldap_object[$attribute] = $user_info[$info];
                    }
                }
            }
        }
*/

        /**
         * Step 2 - handle derived fields
         *
         * Some LDAP attributes are derived from other variables, notably:
         * - uid: this is the username given in the constructor
         * - cn: this is the "first name + last name"
         *
         * For some built-in accounts (e.g. Flexshare) it is more desirable
         * to explicitly set the 'cn' to something other than 
         * "first name + last name" , so we (quietly) allow it.
         */

        $ldap_object['uid'] = $this->username;

        if (isset($user_info['core']['cn'])) {
            $ldap_object['cn'] = $user_info['core']['cn'];
        } else {
            if (isset($user_info['core']['first_name']) || isset($user_info['core']['last_name']))
                $ldap_object['cn'] = $user_info['core']['first_name'] . ' ' . $user_info['core']['last_name'];
            else
                $ldap_object['cn'] = $old_attributes['cn'][0];
        }

        /**
         * Step 3 - handle defaults
         *
         * On a new user record, some attributes can be set to defaults.  For
         * the 'uid_number' and 'gid_number', we allow the developer to specify
         * the values.  For all other cases, defaults are forced to specific
         * values.
         */

        if (! $is_modify) {
            if (isset($user_info['core']['uid_number']))
                $ldap_object['nidNumber'] = $user_info['core']['uid_number'];
            else
                $ldap_object['uidNumber'] = $this->_get_next_uid_number();

            if (isset($user_info['core']['gid_number']))
                $ldap_object['gidNumber'] = $user_info['core']['gid_number'];
            else
                $ldap_object['gidNumber'] = self::DEFAULT_USER_GROUP_ID;

            if (isset($user_info['core']['login_shell']))
                $ldap_object['loginShell'] = $user_info['core']['login_shell'];
            else
                $ldap_object['loginShell'] = self::DEFAULT_LOGIN;
        
            if (isset($user_info['core']['home_directory'])) 
                $ldap_object['homeDirectory'] = $user_info['core']['home_directory'];
            else
                $ldap_object['homeDirectory'] = self::DEFAULT_HOMEDIR_PATH . '/' . $this->username;

            if (isset($user_info['core']['mail'])) 
                $ldap_object['mail'] = $user_info['core']['mail'];
            else
                $ldap_object['mail'] = $this->username . "@" . $directory->get_base_internet_domain();

            if (isset($user_info['core']['status'])) 
                $ldap_object['clearAccountStatus'] = $user_info['core']['status'];
            else
                $ldap_object['clearAccountStatus'] = self::STATUS_ENABLED;
        }

        /**
         * Step 4 - manage all the passwords
         *
         * Some services require different password encryption types, so we
         * keep track of common types.
         */

        // TODO: move this to SetPassword?
        if (! empty($user_info['core']['password'])) {
            $ldap_object['userPassword'] = '{sha}' . $this->_calculate_sha_password($user_info['core']['password']);
            $ldap_object['clearSHAPassword'] = $ldap_object['userPassword'];
            $ldap_object['clearSHA1Password'] = $this->_convert_sha_to_sha1($ldap_object['clearSHAPassword']);
            $ldap_object['clearMicrosoftNTPassword'] = $this->_calculate_nt_password($user_info['core']['password']);
            $ldap_object['clearMicrosoftLanmanPassword'] = $this->_calculate_lanman_password($user_info['core']['password']);
        }

        /**
         * Step 5 - determine which object classes are necessary
         *
         * To keep things tidy, we only add the object classes that we need.
         */

        $classes = array();

        foreach ($old_attributes as $attribute => $detail) {
            // If attribute has not been erased
            // and attribute is in the attribute map
            // and attribute is not part of the core attributes
            if (
                (!(isset($ldap_object[$attribute]) && ($ldap_object[$attribute] == array()))) &&
                isset($this->attribute_map[$attribute]) &&
                isset($this->attribute_map[$attribute]['objectclass']) &&
                ($this->attribute_map[$attribute]['objectclass'] != 'core')
                ) {
                $classes[] = $this->attribute_map[$attribute]['objectclass'];
            }
        }

// FIXME
        foreach ($user_info as $info => $detail) {
            if (isset($this->info_map[$info]['objectclass']) && ($this->info_map[$info]['objectclass'] != 'core'))
                $classes[] = $this->info_map[$info]['objectclass'];
        }

        // PHPism.  Merged arrays have gaps in the keys of the array;
        // LDAP does not like this, so we need to rekey:
        $merged = array_merge($this->core_classes, $classes);
        $merged = array_unique($merged);

        foreach ($merged as $class)
            $ldap_object['objectClass'][] = $class;

// FIXME
return $ldap_object;

        /**
         * Step 6 - handle external user_info fields.
         *
         * Samba and other user extensions.
         * TODO: create a plugin architecture in 6.0, lots of temporary hardcoding and hacks in here!
         */

        if (file_exists(CLEAROS_CORE_DIR . "/api/PasswordPolicy.class.php")) {
            require_once("PasswordPolicy.class.php");
            $policy = new PasswordPolicy();
            $policy->Initialize();
            $ldap_object['pwdPolicySubentry'] = "cn=" . LdapPasswordPolicy::DEFAULT_DIRECTORY_OBJECT . "," . ClearDirectory::GetPasswordPoliciesOu();
        }

        if (file_exists(CLEAROS_CORE_DIR . "/api/Samba.class.php")) {
            if (isset($user_info['sambaFlag'])) {
                require_once("Samba.class.php");

                try {
                    $samba = new Samba();
                    $initialized = $samba->IsDirectoryInitialized();
                } catch (Exception $e) {
                    throw new Engine_Exception(clearos_exception_message($e), CLEAROS_WARNING);
                }

                $samba_enabled = (isset($user_info['sambaFlag']) && $user_info['sambaFlag'] && $initialized) ? TRUE : FALSE;
                $oldclasses = isset($old_attributes['objectClass']) ? $old_attributes['objectClass'] : array();

                // Only change Samba attributes if enabled, or they already exist
                if ($samba_enabled || in_array("sambaSamAccount", $oldclasses)) {
                    // TODO: cleanup this logic
                    $samba_uid = isset($ldap_object['uidNumber']) ? $ldap_object['uidNumber'] : "";

                    if (empty($samba_uid))
                        $samba_uid = isset($old_attributes['uidNumber'][0]) ? $old_attributes['uidNumber'][0] : "";

                    $samba_ntpassword = isset($ldap_object['pcnMicrosoftNTPassword']) ? $ldap_object['pcnMicrosoftNTPassword'] : "";

                    if (empty($samba_ntpassword))
                        $samba_ntpassword = isset($old_attributes['pcnMicrosoftNTPassword'][0]) ? $old_attributes['pcnMicrosoftNTPassword'][0] : "";


                    $samba_lmpassword = isset($ldap_object['pcnMicrosoftLanmanPassword']) ? $ldap_object['pcnMicrosoftLanmanPassword'] : "";
                    if (empty($samba_lmpassword))
                        $samba_lmpassword = isset($old_attributes['pcnMicrosoftLanmanPassword'][0]) ? $old_attributes['pcnMicrosoftLanmanPassword'][0] : "";

                    try {
                        $samba = new Samba();
                        $samba_object = $samba->AddLdapUserAttributes(
                            $this->username,
                            $samba_enabled,
                            $samba_uid,
                            $samba_ntpassword,
                            $samba_lmpassword
                        );

                        $ldap_object = array_merge($ldap_object, $samba_object);
                        $ldap_object['objectClass'][] = 'sambaSamAccount';
                    } catch (Exception $e) {
                        throw new Engine_Exception(clearos_exception_message($e), CLEAROS_WARNING);
                    }
                }
            // TODO: when updating non-Samba info, this is necessary.  This whole
            // block of code and the Samba hooks need to be redone!
            } else {
                if (isset($old_attributes['sambaAcctFlags']))
                    $ldap_object['objectClass'][] = 'sambaSamAccount';
            }
        }

        // tODO: last minute 5.0 addition. Remove old pcnSambaPassword:
        if (isset($old_attributes['pcnSambaPassword']))
            $ldap_object['pcnSambaPassword'] = array();

        // TODO: PBX plugin
        if (file_exists(CLEAROS_CORE_DIR . "/iplex/Users.class.php")) {
            if (! in_array("pcnPbxAccount", $ldap_object['objectClass']))
                $ldap_object['objectClass'][] = "pcnPbxAccount";

            $ldap_object['pcnPbxState'] = (isset($user_info['pbxFlag']) && $user_info['pbxFlag']) ? '1' : '0';
            $ldap_object['pcnPbxPresenceState'] = (isset($user_info['pbxPresenceFlag']) && $user_info['pbxPresenceFlag']) ? '1' : '0';
            $ldap_object['pcnPbxExtension'] = (empty($user_info['pbxExtension'])) ? 'none' : $user_info['pbxExtension'];
        }

        // TODO: Legacy 4.x PBX LDAP cruft
        if (isset($ldap_object['pcnPbxState']) && empty($ldap_object['pcnPbxState']))
            $ldap_object['pcnPbxState'] = 0;

        return $ldap_object;
    }

    /**
     * Generates an irreversible hash.
     *
     * @access private
     * @return string
     */

    protected function _generate_des_hash($plain)
    {
        clearos_profile(__METHOD__, __LINE__);

        $key = $this->_add_parity_to_des($plain);
        $td = mcrypt_module_open(MCRYPT_DES, '', MCRYPT_MODE_ECB, '');
        $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        mcrypt_generic_init($td, $key, $iv);
        $hash = mcrypt_generic($td, 'KGS!@#$%');
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);

        return $hash;
    }

   /**
     * Adds the parity bit to the given DES key.
     *
     * @access private
     * @param  string  $key 7-Bytes Key without parity
     *
     * @return string
     */

    protected function _add_parity_to_des($key)
    {
        clearos_profile(__METHOD__, __LINE__);

        static $odd_parity = array(
                1,  1,  2,  2,  4,  4,  7,  7,  8,  8, 11, 11, 13, 13, 14, 14,
                16, 16, 19, 19, 21, 21, 22, 22, 25, 25, 26, 26, 28, 28, 31, 31,
                32, 32, 35, 35, 37, 37, 38, 38, 41, 41, 42, 42, 44, 44, 47, 47,
                49, 49, 50, 50, 52, 52, 55, 55, 56, 56, 59, 59, 61, 61, 62, 62,
                64, 64, 67, 67, 69, 69, 70, 70, 73, 73, 74, 74, 76, 76, 79, 79,
                81, 81, 82, 82, 84, 84, 87, 87, 88, 88, 91, 91, 93, 93, 94, 94,
                97, 97, 98, 98,100,100,103,103,104,104,107,107,109,109,110,110,
                112,112,115,115,117,117,118,118,121,121,122,122,124,124,127,127,
                128,128,131,131,133,133,134,134,137,137,138,138,140,140,143,143,
                145,145,146,146,148,148,151,151,152,152,155,155,157,157,158,158,
                161,161,162,162,164,164,167,167,168,168,171,171,173,173,174,174,
                176,176,179,179,181,181,182,182,185,185,186,186,188,188,191,191,
                193,193,194,194,196,196,199,199,200,200,203,203,205,205,206,206,
                208,208,211,211,213,213,214,214,217,217,218,218,220,220,223,223,
                224,224,227,227,229,229,230,230,233,233,234,234,236,236,239,239,
                241,241,242,242,244,244,247,247,248,248,251,251,253,253,254,254);

        $bin = '';
        for ($i = 0; $i < strlen($key); $i++)
            $bin .= sprintf('%08s', decbin(ord($key{$i})));

        $str1 = explode('-', substr(chunk_split($bin, 7, '-'), 0, -1));
        $x = '';

        foreach($str1 as $s)
            $x .= sprintf('%02s', dechex($odd_parity[bindec($s . '0')]));

        return pack('H*', $x);
    }

    /**
     * Returns the default group information details for new users.
     *
     * @throws Engine_Exception
     * @return void
     */

    public function get_directory_default_group_id()
    {
        clearos_profile(__METHOD__, __LINE__);

        return self::DEFAULT_USER_GROUP_ID;
    }

    /**
     * Returns DN for given user ID (username).
     *
     * @param string $uid user ID
     *
     * @return string DN
     * @throws Engine_Exception
     */

    public function _get_dn_for_uid($uid)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->ldaph === NULL)
            $this->ldaph = Utilities::get_ldap_handle();

        // FIXME: re-enable escape method
        //  $this->ldaph->search('(&(objectclass=posixAccount)(uid=' . $this->Escape($uid) . '))');
        $this->ldaph->search('(&(objectclass=posixAccount)(uid=' . $uid . '))');
        $entry = $this->ldaph->get_first_entry();

        $dn = '';

        if ($entry)
            $dn = $this->ldaph->get_dn($entry);

        return $dn;
    }

    /**
     * Returns extension list.
     *
     * @access private
     * @return array extension list
     */

    protected function _get_extensions()
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->extensions !== NULL)
            return $this->extensions;

        $folder = new Folder($this->path_extensions);

        $this->extensions = $folder->get_listing();

        return $this->extensions;
    }

    /**
     * Returns the next available user ID.
     *
     * @access private
     * @return integer next available user ID
     * @throws Engine_Exception
     */

    public function _get_next_uid_number()
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->ldaph == NULL)
            $this->ldaph = Utilities::get_ldap_handle();

        try {
            $directory = new Directory_Driver();

            // FIXME: discuss with David -- move "Master" node?
            $dn = 'cn=Master,' . $directory->get_servers_ou();

            $attributes = $this->ldaph->read($dn);

            // TODO: should have some kind of semaphore to prevent duplicate IDs
            $next['uidNumber'] = $attributes['uidNumber'][0] + 1;

            $this->ldaph->modify($dn, $next);
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_WARNING);
        }

        return $attributes['uidNumber'][0];
    }

    /**
     * Returns LDAP user information in hash array.
     *
     * @access private
     *
     * @return array hash array of user information
     * @throws Engine_Exception, User_Not_Found_Exception
     */

    protected function _get_user_attributes()
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->ldaph == NULL)
            $this->ldaph = Utilities::get_ldap_handle();

        $dn = $this->_get_dn_for_uid($this->username);
        $attributes = $this->ldaph->read($dn);
        $attributes['dn'] = $dn;

        if (!isset($attributes['uid'][0]))
            throw new User_Not_Found_Exception();

        return $attributes;
    }

    /**
     * Sets the password using ClearDirectory conventions.
     *
     * @access private
     * @param string $password password
     * @param boolean $includesamba workaround for Samba password changes
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    protected function _set_password($password, $includesamba = TRUE)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Already validated by SetPassword and ResetPassword

        // TODO: merge this with section in convert_array_to_attributes
        $ldap_object['userPassword'] = '{sha}' . $this->_calculate_sha_password($password);
        $ldap_object['pcnSHAPassword'] = $ldap_object['userPassword'];
        $ldap_object['pcnMicrosoftNTPassword'] = $this->_calculate_nt_password($password);
        $ldap_object['pcnMicrosoftLanmanPassword'] = $this->_calculate_lanman_password($password);

        $old_attributes = $this->_get_user_attributes();

        // If necessary, add pcnAccount object class for the above passwords
        if (! in_array('pcnAccount', $old_attributes['objectClass'])) {
            $classes = $old_attributes['objectClass'];
            array_shift($classes);
            $classes[] = 'pcnAccount';
            $ldap_object['objectClass']= $classes;
        }

        foreach ($this->info_map as $key => $value) {
            if (isset($this->info_map[$key]['passwordtype'])) {
                if (isset($old_attributes[$this->info_map[$key]['passwordfield']])) {
                    if ($this->info_map[$key]['passwordtype'] == self::CONSTANT_TYPE_SHA)
                        $ldap_object[$this->info_map[$key]['passwordfield']] = $ldap_object['pcnSHAPassword'];
                    elseif ($this->info_map[$key]['passwordtype'] == self::CONSTANT_TYPE_SHA1)
                        $ldap_object[$this->info_map[$key]['passwordfield']] = $this->_convert_sha_to_sha1($ldap_object['pcnSHAPassword']);
                    elseif ($this->info_map[$key]['passwordtype'] == self::CONSTANT_TYPE_LANMAN)
                        $ldap_object[$this->info_map[$key]['passwordfield']] = $ldap_object['pcnMicrosoftLanmanPassword'];
                    elseif ($this->info_map[$key]['passwordtype'] == self::CONSTANT_TYPE_NT)
                        $ldap_object[$this->info_map[$key]['passwordfield']] = $ldap_object['pcnMicrosoftNTPassword'];
                }
            }
        }

        // TODO / Samba hook should be removed if possible
        if ($includesamba) {
            if (isset($old_attributes['sambaSID'])) {
                $ldap_object['sambaLMPassword'] = $ldap_object['pcnMicrosoftLanmanPassword'] ;
                $ldap_object['sambaNTPassword'] = $ldap_object['pcnMicrosoftNTPassword'];
                $ldap_object['sambaPwdLastSet'] = time();
            }
        }

        sleep(2); // see comment in SetPassword

        $this->ldaph->Modify($old_attributes['dn'], $ldap_object);
    }

    /**
     * Converts string to unicode.
     *
     * This will be a native PHP method in the not too distant future.
     *
     * @access private
     *
     * @return void
     */

    protected function _string_to_unicode($string)
    {
        clearos_profile(__METHOD__, __LINE__);

        $unicode = "";

        for ($i = 0; $i < strlen($string); $i++) {
            $a = ord($string{$i}) << 8;
            $unicode .= sprintf("%X", $a);
        }

        return pack('H*', $unicode);
    }

    /**
     * Signals LDAP synchronize daemon.
     *
     * @access private
     * @return void
     */

    protected function _synchronize()
    {
        clearos_profile(__METHOD__, __LINE__);

        // FIXME: move this to an external daemon... just a hack for now
        /*
        try {
            $options['background'] = TRUE;
            $shell = new Shell();
            if ($homedirs)
                $shell->Execute(User::COMMAND_SYNCUSERS, '', TRUE, $options);
            $shell->Execute(User::COMMAND_SYNCMAILBOX, '', TRUE, $options);
        } catch (Exception $e) {
            // Not fatal
        }
        */
    }

    /**
     * Validates a user_info array.
     *
     * @param array $user_info user information array
     * @param boolean $is_modify set to TRUE if using results on LDAP modif
     *
     * @return boolean TRUE if user_info is valid
     * @throws Engine_Exception
     */

    protected function validate_user_info($user_info, $is_modify = FALSE)
    {
        clearos_profile(__METHOD__, __LINE__);

        $is_valid = TRUE;
        $invalid_attrs = array();

        // Check user_info type
        //---------------------

        if (!is_array($user_info))
            throw new Validation_Exception(lang('directory_validate_user_info_invalid'));

        // Validate user information using validator defined in $this->info_map
        //--------------------------------------------------------------------

        foreach ($user_info as $attribute => $detail) {
            if (isset($this->info_map[$attribute]) && isset($this->info_map[$attribute]['validator'])) {
                // TODO: afterthought -- password/verify check is done below
                if ($attribute == 'password')
                    continue;

                $validator = $this->info_map[$attribute]['validator'];

                Validation_Exception::is_valid($this->$validator($detail));
            }
        }
//pete
return;

        // Validate passwords
        //-------------------

        if (!empty($user_info['password']) || !empty($user_info['verify'])) {
            if (!($this->validate_password_and_verify($user_info['password'], $user_info['verify']))) {
                $is_valid = FALSE;
                $invalid_attrs[] = 'password';
            }
        }

        // When adding a new user, check for missing attributes
        //-----------------------------------------------------

        if (! $is_modify) {
            foreach ($this->info_map as $attribute => $details) {
                if (empty($user_info[$attribute]) && 
                    ($details['required'] == TRUE) &&
                    (!in_array($attribute, $invalid_attrs))
                    ) {
                        $is_valid = FALSE;
                        $this->AddValidationError(
                            LOCALE_LANG_ERRMSG_REQUIRED_PARAMETER_IS_MISSING . " - " . $details['locale'], __METHOD__, __LINE__
                        );
                } 
            }
        }

        if ($is_valid)
            return TRUE;
        else
            return FALSE;
    }
}
