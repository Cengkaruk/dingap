<?php
/**
 * OpenLDAP group mapping.
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
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('users');

///////////////////////////////////////////////////////////////////////////////
// C O N F I G
///////////////////////////////////////////////////////////////////////////////

$info_map = array(
    'description' => array(
        'type' => 'string',
        'field_type' => 'text',
        'required' => FALSE,
        'validator' => 'validate_description',
        'object_class' => 'clearAccount',
        'attribute' => 'description'
    ),

    'display_name' => array(
        'type' => 'string',
        'required' => FALSE,
        'validator' => 'validate_display_name',
        'object_class' => 'clearAccount',
        'attribute' => 'displayName' 
    ),

    'first_name' => array(
        'type' => 'string',
        'required' => FALSE,
        'validator' => 'validate_first_name',
        'object_class' => 'clearAccount',
        'attribute' => 'givenName'
    ),

    'gid_number' => array(
        'type' => 'integer',
        'required' => FALSE,
        'validator' => 'validate_gid_number',
        'object_class' => 'clearAccount',
        'attribute' => 'gidNumber'
    ),

    'home_directory' => array(
        'type' => 'string',
        'required' => FALSE,
        'validator' => 'validate_home_directory',
        'object_class' => 'clearAccount',
        'attribute' => 'homeDirectory'
    ),

    'last_name' => array(
        'type' => 'string',
        'required' => TRUE,
        'validator' => 'validate_last_name',
        'object_class' => 'clearAccount',
        'attribute' => 'sn',
        'locale' => lang('directory_last_name')
    ),

    'login_shell' => array(
        'type' => 'string',
        'required' => FALSE,
        'validator' => 'validate_login_shell',
        'object_class' => 'clearAccount',
        'attribute' => 'loginShell'
    ),

    'password' => array(
        'type' => 'string',
        'required' => FALSE,
        'validator' => 'validate_password',
        'object_class' => 'clearAccount',
        'attribute' => 'userPassword',
        'locale' => lang('base_password')
    ),

    'uid' => array(
        'type' => 'integer',
        'required' => FALSE,
        'validator' => 'IsValidUsername',
        'object_class' => 'clearAccount',
        'attribute' => 'uid'
    ),

    'uid_number' => array(
        'type' => 'integer',
        'required' => FALSE,
        'validator' => 'IsValidUidNumber',
        'object_class' => 'clearAccount',
        'attribute' => 'uidNumber'
    ),
);

/*
    'aliases'        => array( 'type' => 'stringarray',  'required' => FALSE, 'validator' => 'IsValidAlias', 'object_class' => 'pcnMailAccount', 'attribute' => 'pcnMailAliases' ),
    'forwarders'    => array( 'type' => 'stringarray',  'required' => FALSE, 'validator' => 'IsValidForwarder', 'object_class' => 'pcnMailAccount', 'attribute' => 'pcnMailForwarders' ),
    'ftpFlag'        => array( 'type' => 'boolean', 'required' => FALSE, 'validator' => 'IsValidFlag', 'object_class' => 'pcnFTPAccount', 'attribute' => 'pcnFTPFlag' , 'passwordfield' => 'pcnFTPPassword', 'passwordtype' => User::PASSWORD_TYPE_SHA ),
    'mailFlag'        => array( 'type' => 'boolean', 'required' => FALSE, 'validator' => 'IsValidFlag', 'object_class' => 'pcnMailAccount', 'attribute' => 'pcnMailFlag' , 'passwordfield' => 'pcnMailPassword', 'passwordtype' => User::PASSWORD_TYPE_SHA ),
    'googleAppsFlag'    => array( 'type' => 'boolean', 'required' => FALSE, 'validator' => 'IsValidFlag', 'object_class' => 'pcnGoogleAppsAccount', 'attribute' => 'pcnGoogleAppsFlag' , 'passwordfield' => 'pcnGoogleAppsPassword', 'passwordtype' => User::PASSWORD_TYPE_SHA1 ),
    'openvpnFlag'    => array( 'type' => 'boolean', 'required' => FALSE, 'validator' => 'IsValidFlag', 'object_class' => 'pcnOpenVPNAccount', 'attribute' => 'pcnOpenVPNFlag' , 'passwordfield' => 'pcnOpenVPNPassword', 'passwordtype' => User::PASSWORD_TYPE_SHA ),
    'pptpFlag'        => array( 'type' => 'boolean', 'required' => FALSE, 'validator' => 'IsValidFlag', 'object_class' => 'pcnPPTPAccount', 'attribute' => 'pcnPPTPFlag' , 'passwordfield' => 'pcnPPTPPassword', 'passwordtype' => User::PASSWORD_TYPE_NT ),
    'proxyFlag'        => array( 'type' => 'boolean', 'required' => FALSE, 'validator' => 'IsValidFlag', 'object_class' => 'pcnProxyAccount', 'attribute' => 'pcnProxyFlag' , 'passwordfield' => 'pcnProxyPassword', 'passwordtype' => User::PASSWORD_TYPE_SHA ),
    'webconfigFlag'    => array( 'type' => 'boolean', 'required' => FALSE, 'validator' => 'IsValidFlag', 'object_class' => 'pcnWebconfigAccount', 'attribute' => 'pcnWebconfigFlag' , 'passwordfield' => 'pcnWebconfigPassword', 'passwordtype' => User::PASSWORD_TYPE_SHA ),
    'webFlag'        => array( 'type' => 'boolean', 'required' => FALSE, 'validator' => 'IsValidFlag', 'object_class' => 'pcnWebAccount', 'attribute' => 'pcnWebFlag' , 'passwordfield' => 'pcnWebPassword', 'passwordtype' => User::PASSWORD_TYPE_SHA ),
*/
