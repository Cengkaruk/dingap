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
// Core fields
///////////////////////////////////////////////////////////////////////////////
//
// Some directory drivers separate first and last names into separate fields,
// while others only support the full name (common name).  If the separate 
// fields don't exist, fall back to the full name.
//
///////////////////////////////////////////////////////////////////////////////

echo form_fieldset(lang('base_general_settings'));
echo field_input('username', $username, lang('users_username'), $username_read_only);

if (isset($user_info['core']['first_name']) || isset($user_info['core']['last_name'])) {
    echo field_input('first_name', $user_info['core']['first_name'], lang('users_first_name'), $read_only);
    echo field_input('last_name', $user_info['core']['last_name'], lang('users_last_name'), $read_only);
} else {
    echo field_input('full_name', $user_info['core']['full_name'], lang('users_full_name'), $read_only);
}

echo form_fieldset_close();

///////////////////////////////////////////////////////////////////////////////
// Password fields
///////////////////////////////////////////////////////////////////////////////
//
// Don't bother showing passwords when read_only.
//
///////////////////////////////////////////////////////////////////////////////

if (! $read_only) {
    echo form_fieldset(lang('users_password'));
    echo field_password('password', '', lang('users_password'), $read_only);
    echo field_password('verify', '', lang('users_verify'), $read_only);
    echo form_fieldset_close();
}

///////////////////////////////////////////////////////////////////////////////
// Plugin groups
///////////////////////////////////////////////////////////////////////////////

//echo form_fieldset(lang('users_plugins'));
echo form_fieldset('Plugin Groups'); //FIXME

foreach ($info_map['plugins'] as $plugin => $parameters) {
    $name = "user_info[plugins][$plugin][state]";
    $value = $user_info['plugins'][$plugin]['state'];
    $description = $plugin_info[$plugin]['description'];
    echo field_toggle_enable_disable($name, $value, $description, $read_only);
}

echo form_fieldset_close();

///////////////////////////////////////////////////////////////////////////////
// Extensions
///////////////////////////////////////////////////////////////////////////////

foreach ($info_map['extensions'] as $extension => $parameters) {

    // Use the extension name for the title
    //-------------------------------------

    echo form_fieldset($extension_info[$extension]['description']);

    // Echo out the specific info field
    //---------------------------------

    foreach ($parameters as $key_name => $details) {
        $name = "user_info[extensions][$extension][$key_name]";
        $value = $user_info['extensions'][$extension][$key_name];
        $description =  $details['description'];

        if ($details['field_type'] === 'list') {
            echo field_dropdown($name, $details['field_options'], $value, $description, $read_only);
        } else if ($details['field_type'] === 'text') {
            echo field_input($name, $value, $description, $read_only);
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
