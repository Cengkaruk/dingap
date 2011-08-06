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

	$form_path = '/users/edit/' . $username;
	$buttons = array(
		form_submit_update('submit'),
		anchor_cancel('/app/users/'),
		anchor_delete('/app/users/delete/' . $username)
	);
} else if ($form_type === 'view') {
    $read_only = TRUE;
    $username_read_only = TRUE;

	$form_path = '/users/view/' . $username;
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

echo form_open($form_path);
echo form_header(lang('users_users'));

///////////////////////////////////////////////////////////////////////////////
// Core fields
///////////////////////////////////////////////////////////////////////////////
//
// Some directory drivers separate first and last names into separate fields,
// while others only support the full name (common name).  If the separate 
// fields don't exist, fall back to the full name.
//
///////////////////////////////////////////////////////////////////////////////

echo fieldset_header(lang('users_name'));
echo field_input('username', $username, lang('users_username'), $username_read_only);

foreach ($info_map['core'] as $key_name => $details) {
        $name = "user_info[core][$key_name]";
        $value = $user_info['core'][$key_name];
        $description =  $details['description'];

        if ($details['field_priority'] !== 'normal')
            continue;

        if ($details['field_type'] === 'list') {
            echo field_dropdown($name, $details['field_options'], $value, $description, $read_only);
        } else if ($details['field_type'] === 'simple_list') {
            echo field_simple_dropdown($name, $details['field_options'], $value, $description, $read_only);
        } else if ($details['field_type'] === 'text') {
            echo field_input($name, $value, $description, $read_only);
        } else if ($details['field_type'] === 'integer') {
            echo field_input($name, $value, $description, $read_only);
        }
}

echo fieldset_footer();

///////////////////////////////////////////////////////////////////////////////
// Password fields
///////////////////////////////////////////////////////////////////////////////
//
// Don't bother showing passwords when read_only.
//
///////////////////////////////////////////////////////////////////////////////

if (! $read_only) {
    echo fieldset_header(lang('users_password'));
    echo field_password('password', '', lang('users_password'), $read_only);
    echo field_password('verify', '', lang('users_verify'), $read_only);
    echo fieldset_footer();
}

///////////////////////////////////////////////////////////////////////////////
// Plugin groups
///////////////////////////////////////////////////////////////////////////////

if (! empty($plugins)) {
    echo fieldset_header(lang('users_plugins'));

    foreach ($plugins as $plugin => $details) {
        $name = "user_info[plugins][$plugin][state]";
        $value = $user_info['plugins'][$plugin];
        echo field_toggle_enable_disable($name, $value, $details['nickname'], $read_only);
    }

    echo fieldset_footer();
}

///////////////////////////////////////////////////////////////////////////////
// Extensions
///////////////////////////////////////////////////////////////////////////////

foreach ($info_map['extensions'] as $extension => $parameters) {

    // FIXME: skip Samba for now
    if ($extension === 'samba')
        continue;

    // Use the extension name for the title
    //-------------------------------------

    echo fieldset_header($extensions[$extension]['nickname']);

    // Echo out the specific info field
    //---------------------------------

    foreach ($parameters as $key_name => $details) {
        $name = "user_info[extensions][$extension][$key_name]";
        $value = $user_info['extensions'][$extension][$key_name];
        $description =  $details['description'];

        if (isset($details['field_priority']) && ($details['field_priority'] !== 'normal'))
            continue;

        if ($details['field_type'] === 'list') {
            echo field_dropdown($name, $details['field_options'], $value, $description, $read_only);
        } else if ($details['field_type'] === 'simple_list') {
            echo field_simple_dropdown($name, $details['field_options'], $value, $description, $read_only);
        } else if ($details['field_type'] === 'text') {
            echo field_input($name, $value, $description, $read_only);
        } else if ($details['field_type'] === 'integer') {
            echo field_input($name, $value, $description, $read_only);
        }
    }

    echo fieldset_footer();
}

echo field_button_set($buttons);

///////////////////////////////////////////////////////////////////////////////
// Form close
///////////////////////////////////////////////////////////////////////////////

echo form_footer();
echo form_close();
