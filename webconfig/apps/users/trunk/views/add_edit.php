<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2010 ClearFoundation
//
///////////////////////////////////////////////////////////////////////////////
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// Load dependencies
///////////////////////////////////////////////////////////////////////////////

$this->lang->load('base');
$this->lang->load('users');

///////////////////////////////////////////////////////////////////////////////
// Form modes
///////////////////////////////////////////////////////////////////////////////

if ($form_type === 'edit') {
    $read_only = FALSE;
    $username_read_only = TRUE;

	$form_path = '/users/edit';
	$buttons = array(
		form_submit_update('submit'),
		anchor_cancel('/app/users/'),
		anchor_delete('/app/users/delete/' . $username)
	);
} else if ($form_type === 'view') {
    $read_only = TRUE;
    $username_read_only = TRUE;

	$form_path = '/users/view';
	$buttons = array(
		anchor_cancel('/app/users/')
	);
} else {
    $read_only = FALSE;
    $username_read_only = FALSE;

	$form_path = '/users/add';
	$buttons = array(
		form_submit_add('submit'),
		anchor_cancel('/app/users/')
	);
}

///////////////////////////////////////////////////////////////////////////////
// Form open
///////////////////////////////////////////////////////////////////////////////

echo form_open($form_path . '/' . $username);
echo form_header(lang('users_user'));

///////////////////////////////////////////////////////////////////////////////
// Form fields
///////////////////////////////////////////////////////////////////////////////

echo form_fieldset(lang('base_general_settings'));
echo field_input('username', $username, lang('users_username'), $username_read_only);
echo field_input('first_name', $user_info['core']['first_name'], lang('users_first_name'), $read_only);
echo field_input('last_name', $user_info['core']['last_name'], lang('users_last_name'), $read_only);
echo form_fieldset_close();

echo form_fieldset(lang('users_password'));
echo field_password('password', '', lang('users_password'), $read_only);
echo field_password('verify', '', lang('users_verify'), $read_only);
echo form_fieldset_close();

echo form_fieldset(lang('users_address'));
echo field_input('street', $user_info['core']['street'], lang('users_street'), $read_only);
echo field_input('city', $user_info['core']['city'], lang('users_city'), $read_only);
echo field_input('region', $user_info['core']['region'], lang('users_region'), $read_only);
echo field_dropdown('country', $countries, $user_info['core']['country'], lang('users_country'), $read_only);
echo field_input('postal_code', $user_info['core']['postal_code'], lang('users_postal_code'), $read_only);
echo field_input('organization', $user_info['core']['organization'], lang('users_organization'), $read_only);
echo field_input('organization_unit', $user_info['core']['organization_unit'], lang('users_organization_unit'), $read_only);
echo form_fieldset_close();

echo form_fieldset(lang('users_telephone_numbers'));
echo field_input('mobile', $user_info['core']['mobile'], lang('users_mobile'), $read_only);
echo field_input('telephone', $user_info['core']['telephone'], lang('users_telephone'), $read_only);
echo field_input('fax', $user_info['core']['fax'], lang('users_fax'), $read_only);
echo form_fieldset_close();

// Loop through all the fields described in the info_map
//------------------------------------------------------

foreach ($info_map as $extension => $parameters) {

    // Use the extension name for the title
    //-------------------------------------

    echo form_fieldset($extension_info[$extension]['name']);

    // Echo out the specific info field
    //---------------------------------

    foreach ($parameters as $key_name => $details) {
        $key = "user_info[$extension][$key_name]";
        $value = $user_info[$extension][$key_name];
        $description =  $details['description'];

        if ($details['field_type'] === 'list') {
            echo field_dropdown($key, $details['field_options'], $value, $description, $read_only);
        } else if ($details['field_type'] === 'input') {
            echo field_input($key, $value, $description, $read_only);
        }
    }

    echo form_fieldset_close();
}

echo button_set($buttons);

///////////////////////////////////////////////////////////////////////////////
// Form close
///////////////////////////////////////////////////////////////////////////////

echo form_footer();
echo form_close();
