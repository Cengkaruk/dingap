<?php

/**
 * User account view.
 *
 * @category   ClearOS
 * @package    Users
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/users/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.  
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

$username = isset($user_info['core']['username'])? $user_info['core']['username'] : '';

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

echo form_open($form_path, array('autocomplete' => 'off'));
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

echo fieldset_header(lang('users_name'));

foreach ($info_map['core'] as $key_name => $details) {
    $name = "user_info[core][$key_name]";
    $value = $user_info['core'][$key_name];
    $description =  $details['description'];

    if ($details['field_priority'] !== 'normal')
        continue;

    if (($key_name === 'username') && ($form_type === 'edit'))
        $core_read_only = TRUE;
    else
        $core_read_only = $read_only;
    

    if ($details['field_type'] === 'list') {
        echo field_dropdown($name, $details['field_options'], $value, $description, $core_read_only);
    } else if ($details['field_type'] === 'simple_list') {
        echo field_simple_dropdown($name, $details['field_options'], $value, $description, $core_read_only);
    } else if ($details['field_type'] === 'text') {
        echo field_input($name, $value, $description, $core_read_only);
    } else if ($details['field_type'] === 'integer') {
        echo field_input($name, $value, $description, $core_read_only);
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

    // Echo out the specific info field
    //---------------------------------

    $fields = '';

    if (! empty($parameters)) {
        foreach ($parameters as $key_name => $details) {
            $name = "user_info[extensions][$extension][$key_name]";
            $value = $user_info['extensions'][$extension][$key_name];
            $description =  $details['description'];
            $field_read_only = $read_only;

            if (isset($details['field_priority']) && ($details['field_priority'] === 'hidden')) {
                continue;
            } else if (isset($details['field_priority']) && ($details['field_priority'] === 'read_only')) {
                if ($form_type === 'add')
                    continue;

                $field_read_only = TRUE;
            }

            if ($details['field_type'] === 'list') {
                $fields .= field_dropdown($name, $details['field_options'], $value, $description, $field_read_only);
            } else if ($details['field_type'] === 'simple_list') {
                $fields .= field_simple_dropdown($name, $details['field_options'], $value, $description, $field_read_only);
            } else if ($details['field_type'] === 'text') {
                $fields .= field_input($name, $value, $description, $field_read_only);
            } else if ($details['field_type'] === 'integer') {
                $fields .= field_input($name, $value, $description, $field_read_only);
            }
        }
    }

    if (! empty($fields)) {
        echo fieldset_header($extensions[$extension]['nickname']);
        echo $fields;
        echo fieldset_footer();
    }
}

echo field_button_set($buttons);

///////////////////////////////////////////////////////////////////////////////
// Form close
///////////////////////////////////////////////////////////////////////////////

echo form_footer();
echo form_close();
