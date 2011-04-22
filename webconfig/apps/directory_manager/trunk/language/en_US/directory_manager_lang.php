<?php

///////////////////////////////////////
// From LDAP class
///////////////////////////////////////
// Change ldap to directory

$lang['directory_manager_master_server'] = 'Master Server';
$lang['directory_manager_server_mode'] = 'Server Mode';

// Reset
$lang['directory_manager_errmsg_connection_failed'] = 'Connection to directory server failed.';

// Delete
// $lang['directory_manager_directory_unavailable'] = 'Directory is unavailable.';
// $lang['directory_manager_bind_policy'] = 'Security Policy';
// $lang['directory_manager_errmsg_function_failed'] = 'LDAP function failed';

// Keep
$lang['directory_manager_base_dn'] = 'LDAP Base DN';
$lang['directory_manager_bind_dn'] = 'LDAP Bind DN';
$lang['directory_manager_bind_password'] = 'LDAP Bind Password';

// New
$lang['directory_manager_extension'] = 'Extension';
$lang['directory_manager_plugin'] = 'Plugin';
$lang['directory_manager_extensions'] = 'Extensions';
$lang['directory_manager_plugins'] = 'Plugins';
$lang['directory_manager_state'] = 'State';
$lang['directory_manager_directory_mode_is_invalid'] = 'Directory mode is invalid';
$lang['directory_manager_directory_driver_is_invalid'] = 'Directory driver is invalid';
$lang['directory_manager_master_directory_hostname'] = 'Master Hostname';
$lang['directory_manager_master_directory_password'] = 'Master Password';
$lang['directory_manager_realm'] = 'Realm';
$lang['directory_manager_simple_master'] = 'Master (Simple)';
$lang['directory_manager_active_directory'] = 'Active Directory';
$lang['directory_manager_base_domain'] = 'Base Domain';
$lang['directory_manager_publish_policy'] = 'Publish Policy';
$lang['directory_manager_directory_settings'] = 'Directory Settings';
$lang['directory_manager_ldap_information'] = 'LDAP Information';
$lang['directory_manager_security_policy'] = 'Security Policy';

$lang['directory_manager_validate_security_policy_invalid'] = 'Security policy is invalid.';

$lang['directory_manager_exception_directory_is_unavailable'] = 'Directory is unavailable.';
$lang['directory_manager_exception_function_failed'] = 'LDAP function failed';
$lang['directory_manager_exception_username_already_exists'] = 'Username already exists.';
$lang['directory_manager_exception_full_name_already_exists'] = 'A user with the same full name already exists.';
$lang['directory_manager_exception_user_not_found'] = 'User not found.';

$lang['directory_manager_directory_manager'] = 'Directory Manager';

///////////////////////////////////////
// From ClearDirectory class
///////////////////////////////////////
// Change cleardirectory to directory

// Delete
// $lang['directory_manager_alias_already_exists'] = 'Alias already exists.';
// $lang['directory_manager_group_already_exists'] = 'Group already exists.';
// $lang['directory_manager_username_already_exists'] = 'Username already exists.';
// $lang['directory_manager_replicate'] = 'Replicate';

// Keep
$lang['directory_manager_mode'] = 'Mode';
$lang['directory_manager_master'] = 'Master';
$lang['directory_manager_slave'] = 'Slave';
$lang['directory_manager_standalone'] = 'Standalone';

///////////////////////////////////////
// From User class
///////////////////////////////////////

// New 
$lang['directory_manager_validate_username_invalid'] = 'Username is invalid.';
$lang['directory_manager_validate_username_reserved'] = 'Username is reserved for the system.';
$lang['directory_manager_validate_user_info_invalid'] = 'User information is invalid.';

$lang['directory_manager_validate_city_invalid'] = 'City is invalid.';
$lang['directory_manager_validate_country_invalid'] = 'Country is invalid.';
$lang['directory_manager_validate_description_invalid'] = 'Description is invalid.';
$lang['directory_manager_validate_display_name_invalid'] = 'Display name is invalid.';
$lang['directory_manager_validate_fax_invalid'] = 'Fax number is invalid.';
$lang['directory_manager_validate_first_name_invalid'] = 'First name is invalid.';
$lang['directory_manager_validate_gid_number_invalid'] = 'Group ID is invalid.';
$lang['directory_manager_validate_home_directory_invalid'] = 'Home directory is invalid.';
$lang['directory_manager_validate_last_name_invalid'] = 'Last name is invalid.';

// Keep
$lang['directory_manager_last_name'] = 'Last Name';


// Delete
// $lang['directory_manager_errmsg_reserved_system_user'] = 'This account name is reserved for the system.';

// TODO
$lang['directory_manager_account_locked'] = 'Locked';
$lang['directory_manager_account_lock_status'] = 'Account Lock Status';
$lang['directory_manager_account_unlocked'] = 'Unlocked';
$lang['directory_manager_check_first_last_name'] = 'Check the first and last name';
$lang['directory_manager_display_name'] = 'Display Name';
$lang['directory_manager_email'] = 'Mailbox';
$lang['directory_manager_errmsg_access_control_violation'] = 'Access control violation.';
$lang['directory_manager_errmsg_duplicate_full_name'] = 'User manager must not contain duplicate full names';
$lang['directory_manager_errmsg_flexshare_exists'] = 'Delete failed - the following Flexshare depends on this user';
$lang['directory_manager_errmsg_flexshare_with_this_name_exists'] = 'Username is already in use by Flexshare system';
$lang['directory_manager_errmsg_password_error'] = 'An error occurred setting the password.';
$lang['directory_manager_errmsg_username_not_exist'] = 'User does not exist.';
$lang['directory_manager_errmsg_user_already_exists'] = 'User already exists';
$lang['directory_manager_errmsg_user_not_found'] = 'User not found';
$lang['directory_manager_errmsg_user_synchronization_error'] = 'User database synchronization error.';
$lang['directory_manager_extension'] = 'Extension';
$lang['directory_manager_first_name'] = 'First Name';
$lang['directory_manager_ftp'] = 'FTP';
$lang['directory_manager_fullname'] = 'Full Name';
$lang['directory_manager_gidnumber'] = 'Group ID Number';
$lang['directory_manager_google_apps'] = 'Google Apps';
$lang['directory_manager_homedir'] = 'Home Directory';
$lang['directory_manager_lock'] = 'Lock';
$lang['directory_manager_mail_address'] = 'E-mail Address';
$lang['directory_manager_mail_alias'] = 'Mail Alias';
$lang['directory_manager_mail_aliases'] = 'Mail Aliases';
$lang['directory_manager_mail_forwarder'] = 'Mail Forwarder';
$lang['directory_manager_mail_forwarders'] = 'Mail Forwarders';
$lang['directory_manager_mail_services'] = 'Mail Services';
$lang['directory_manager_name'] = 'Name';
$lang['directory_manager_old_password_invalid'] = 'Old password invalid';
$lang['directory_manager_openvpn'] = 'OpenVPN';
$lang['directory_manager_options'] = 'Options';
$lang['directory_manager_password_in_history'] = 'Password is in history of old passwords';
$lang['directory_manager_password_not_changed'] = 'Password is not being changed from existing value';
$lang['directory_manager_password_too_young'] = 'Password is too young to change';
$lang['directory_manager_password_violates_quality_check'] = 'Password fails quality checking policy';
$lang['directory_manager_password_was_reset'] = 'Password required a reset.';
$lang['directory_manager_pbx'] = 'PBX';
$lang['directory_manager_phone_numbers'] = 'Telephone Numbers';
$lang['directory_manager_pptp'] = 'PPTP VPN';
$lang['directory_manager_presence'] = 'Presence';
$lang['directory_manager_proxy'] = 'Proxy Server';
$lang['directory_manager_quota'] = 'Quota';
$lang['directory_manager_samba'] = 'Windows Networking';
$lang['directory_manager_secure_password'] = 'Secure Password';
$lang['directory_manager_server'] = 'Server';
$lang['directory_manager_services'] = 'Services';
$lang['directory_manager_shell'] = 'Login Shell';
$lang['directory_manager_status_converted'] = 'Converted';
$lang['directory_manager_status_exists'] = 'Exists';
$lang['directory_manager_title'] = 'Title';
$lang['directory_manager_uidnumber'] = 'User ID Number';
$lang['directory_manager_unlock'] = 'Unlock';
$lang['directory_manager_user_details'] = 'User Details';
$lang['directory_manager_verify'] = 'Verify';
$lang['directory_manager_web'] = 'Web';
