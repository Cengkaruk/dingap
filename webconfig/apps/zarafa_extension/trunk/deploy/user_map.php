<?php

/**
 * Contact OpenLDAP user extension.
 *
 * @category   Apps
 * @package    Contact_Directory_Extension
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/contact_extension/
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

clearos_load_language('zarafa_extension');
clearos_load_language('base');

///////////////////////////////////////////////////////////////////////////////
// C O N F I G
///////////////////////////////////////////////////////////////////////////////

$lang_gb = lang('base_gigabytes');

$info_map = array(
    'account_flag' => array(
        'type' => 'integer',
        'field_type' => 'list',
        'field_options' => array(
            '0' => 'Disabled',
            '1' => 'Enabled',
        ),
        'required' => FALSE,
        'validator' => 'validate_account_flag',
        'validator_class' => 'zarafa_extension/OpenLDAP_User_Extension',
        'description' => lang('zarafa_extension_account'),
        'object_class' => 'zarafa-user',
        'attribute' => 'zarafaAccount' 
    ),

    'administrator_flag' => array(
        'type' => 'integer',
        'field_type' => 'list',
        'field_options' => array(
            '0' => 'Disabled',
            '1' => 'Enabled',
        ),
        'required' => FALSE,
        'validator' => 'validate_administrator_flag',
        'validator_class' => 'zarafa_extension/OpenLDAP_User_Extension',
        'description' => lang('zarafa_extension_administrator_privileges'),
        'object_class' => 'zarafa-user',
        'attribute' => 'zarafaAdmin' 
    ),

    'hard_quota' => array(
        'type' => 'integer',
        'field_type' => 'list',
        'field_options' => array(
            '1' => '1 ' . $lang_gb,
            '2' => '2 ' . $lang_gb,
            '3' => '3 ' . $lang_gb,
            '4' => '4 ' . $lang_gb,
            '5' => '5 ' . $lang_gb,
            '10' => '10 ' . $lang_gb,
            '20' => '20 ' . $lang_gb,
            '30' => '30 ' . $lang_gb,
            '40' => '40 ' . $lang_gb,
            '50' => '50 ' . $lang_gb,
            '100' => '100 ' . $lang_gb,
            '0' => lang('base_unlimited')
        ),
        'required' => TRUE,
        'validator' => 'validate_hard_quota',
        'validator_class' => 'zarafa_extension/OpenLDAP_User_Extension',
        'description' => lang('zarafa_extension_hard_quota'),
        'object_class' => 'zarafa-user',
        'attribute' => 'zarafaQuotaHard' 
    ),

    'quota_override' => array(
        'type' => 'integer',
        'field_type' => 'text',
        'field_priority' => 'hidden',
        'required' => FALSE,
        'description' => lang('zarafa_extension_quota_override'),
        'object_class' => 'zarafa-user',
        'attribute' => 'zarafaQuotaOverride'
    ),

    'soft_quota' => array(
        'type' => 'integer',
        'field_type' => 'text',
        'field_priority' => 'hidden',
        'required' => FALSE,
        'description' => lang('zarafa_extension_soft_quota'),
        'object_class' => 'zarafa-user',
        'attribute' => 'zarafaQuotaSoft'
    ),

    'warning_quota' => array(
        'type' => 'integer',
        'field_type' => 'text',
        'field_priority' => 'hidden',
        'required' => FALSE,
        'description' => lang('zarafa_extension_warning_quota'),
        'object_class' => 'zarafa-user',
        'attribute' => 'zarafaQuotaWarn'
    ),


/*
    'fax' => array(
        'type' => 'string',
        'field_type' => 'text',
        'required' => FALSE,
        'validator' => 'validate_fax_number',
        'validator_class' => 'contact_extension/OpenLDAP_User_Extension',
        'description' => lang('contact_extension_fax_number'),
        'object_class' => 'clearAccount',
        'attribute' => 'facsimileTelephoneNumber' 
    ),

    'mail' => array(
        'type' => 'string',
        'field_type' => 'text',
        'required' => FALSE,
        'validator' => 'validate_email',
        'validator_class' => 'contact_extension/OpenLDAP_User_Extension',
        'description' => lang('contact_extension_email'),
        'object_class' => 'clearAccount',
        'attribute' => 'mail'
    ),

    'organization' => array(
        'type' => 'string',
        'field_type' => 'text',
        'required' => FALSE,
        'validator' => 'validate_organization',
        'validator_class' => 'contact_extension/OpenLDAP_User_Extension',
        'description' => lang('contact_extension_organization'),
        'object_class' => 'clearAccount',
        'attribute' => 'o'
    ),

    'postal_code' => array(
        'type' => 'string',
        'field_type' => 'text',
        'required' => FALSE,
        'validator' => 'validate_postal_code',
        'validator_class' => 'contact_extension/OpenLDAP_User_Extension',
        'description' => lang('contact_extension_postal_code'),
        'object_class' => 'clearAccount',
        'attribute' => 'postalCode'
    ),

    'post_office_box' => array(
        'type' => 'string',
        'field_type' => 'text',
        'required' => FALSE,
        'validator' => 'validate_post_office_box',
        'validator_class' => 'contact_extension/OpenLDAP_User_Extension',
        'description' => lang('contact_extension_post_office_box'),
        'object_class' => 'clearAccount',
        'attribute' => 'postOfficeBox'
    ),

    'region' => array(
        'type' => 'string',
        'field_type' => 'text',
        'required' => FALSE,
        'validator' => 'validate_region',
        'validator_class' => 'contact_extension/OpenLDAP_User_Extension',
        'description' => lang('contact_extension_region'),
        'object_class' => 'clearAccount',
        'attribute' => 'st'
    ),

    'room_number' => array(
        'type' => 'string',
        'field_type' => 'text',
        'required' => FALSE,
        'validator' => 'validate_room_number',
        'validator_class' => 'contact_extension/OpenLDAP_User_Extension',
        'description' => lang('contact_extension_room_number'),
        'object_class' => 'clearAccount',
        'attribute' => 'roomNumber'
    ),

    'street' => array(
        'type' => 'string',
        'field_type' => 'text',
        'required' => FALSE,
        'validator' => 'validate_street',
        'validator_class' => 'contact_extension/OpenLDAP_User_Extension',
        'description' => lang('contact_extension_street'),
        'object_class' => 'clearAccount',
        'attribute' => 'street'
    ),

    'telephone' => array(
        'type' => 'string',
        'field_type' => 'text',
        'required' => FALSE,
        'validator' => 'validate_telephone_number',
        'validator_class' => 'contact_extension/OpenLDAP_User_Extension',
        'description' => lang('contact_extension_telephone_number'),
        'object_class' => 'clearAccount',
        'attribute' => 'telephoneNumber'
    ),

    'unit' => array(
        'type' => 'string',
        'field_type' => 'text',
        'required' => FALSE,
        'validator' => 'validate_unit',
        'validator_class' => 'contact_extension/OpenLDAP_User_Extension',
        'description' => lang('contact_extension_unit'),
        'object_class' => 'clearAccount',
        'attribute' => 'ou'
    ),
*/
);
