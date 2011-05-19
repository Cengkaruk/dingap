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

clearos_load_language('contact_extension');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

use \clearos\apps\base\Country as Country;

clearos_load_library('base/Country');

///////////////////////////////////////////////////////////////////////////////
// C O N F I G
///////////////////////////////////////////////////////////////////////////////
/*
// FIXME: mobile?

    'mobile' => array(
        'type' => 'string',
        'field_type' => 'text',
        'required' => FALSE,
        'validator' => 'validate_mobile_number',
        'validator_class' => 'contact_extension/OpenLDAP_User_Extension',
        'description' => lang('contact_extension_mobile_number'),
        'object_class' => 'clearAccount',
        'attribute' => 'mobileTelephoneNumber'
    ),
*/

// Load list of countries

try {
    $country = new Country();
    $country_list = $country->get_list();
} catch (Exception $e) {
    // 
}

$info_map = array(
    'city' => array(
        'type' => 'string',
        'field_type' => 'text',
        'required' => FALSE,
        'validator' => 'validate_city',
        'validator_class' => 'contact_extension/OpenLDAP_User_Extension',
        'description' => lang('contact_extension_city'),
        'object_class' => 'clearAccount',
        'attribute' => 'l' 
    ),

    'country' => array(
        'type' => 'string',
        'field_type' => 'list',
        'field_options' => $country_list,
        'required' => FALSE,
        'validator' => 'validate_country',
        'validator_class' => 'contact_extension/OpenLDAP_User_Extension',
        'description' => lang('contact_extension_country'),
        'object_class' => 'clearAccount',
        'attribute' => 'c'
    ),

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
);
