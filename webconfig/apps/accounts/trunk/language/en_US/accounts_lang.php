<?php

$lang['accounts_accounts_driver_is_invalid'] = 'Accounts driver is invalid';
$lang['accounts_account_information_is_unavailable'] = 'Account information is unavailable.';
$lang['accounts_extension'] = 'Extension';
$lang['accounts_plugin'] = 'Plugin';
$lang['accounts_extensions'] = 'Extensions';
$lang['accounts_plugins'] = 'Plugins';


///////////////////////////////////////
// From LDAP class
///////////////////////////////////////
// Change ldap to directory

$lang['accounts_master_server'] = 'Master Server';
$lang['accounts_server_mode'] = 'Server Mode';

// Reset
$lang['accounts_errmsg_connection_failed'] = 'Connection to directory server failed.';

// Keep
$lang['accounts_base_dn'] = 'LDAP Base DN';
$lang['accounts_bind_dn'] = 'LDAP Bind DN';
$lang['accounts_bind_password'] = 'LDAP Bind Password';

// New
$lang['accounts_initialization'] = 'Initialization';
$lang['accounts_account_information_is_online'] = 'Accounts system is online.';
$lang['accounts_account_information_is_offline'] = 'Account system is offline.';
$lang['accounts_account_system_is_initializing'] = 'Account system is initializing.';
$lang['accounts_account_system_is_not_initialized'] = 'Account system is not initialized.'; 
$lang['accounts_initialize_now'] = 'Initialize Now';

$lang['accounts_account_manager_status'] = 'Account Manager Status';
$lang['accounts_state'] = 'State';
$lang['accounts_directory_mode_is_invalid'] = 'Directory mode is invalid';
$lang['accounts_directory_driver_is_invalid'] = 'Directory driver is invalid';
$lang['accounts_master_directory_hostname'] = 'Master Hostname';
$lang['accounts_master_directory_password'] = 'Master Password';
$lang['accounts_realm'] = 'Realm';
$lang['accounts_simple_master'] = 'Master (Simple)';
$lang['accounts_active_directory'] = 'Active Directory';
$lang['accounts_base_domain'] = 'Base Domain';
$lang['accounts_publish_policy'] = 'Publish Policy';
$lang['accounts_directory_settings'] = 'Directory Settings';
$lang['accounts_ldap_information'] = 'LDAP Information';
$lang['accounts_security_policy'] = 'Security Policy';

$lang['accounts_validate_security_policy_invalid'] = 'Security policy is invalid.';



$lang['accounts_exception_function_failed'] = 'LDAP function failed';
$lang['accounts_exception_username_already_exists'] = 'Username already exists.';
$lang['accounts_exception_full_name_already_exists'] = 'A user with the same full name already exists.';
$lang['accounts_exception_user_not_found'] = 'User not found.';

$lang['accounts_accounts'] = 'Directory Manager';

///////////////////////////////////////
// From ClearDirectory class
///////////////////////////////////////
// Change cleardirectory to directory

// Delete
// $lang['accounts_alias_already_exists'] = 'Alias already exists.';
// $lang['accounts_group_already_exists'] = 'Group already exists.';
// $lang['accounts_username_already_exists'] = 'Username already exists.';
// $lang['accounts_replicate'] = 'Replicate';

// Keep
$lang['accounts_mode'] = 'Mode';
$lang['accounts_master'] = 'Master';
$lang['accounts_slave'] = 'Slave';
$lang['accounts_standalone'] = 'Standalone';

///////////////////////////////////////
// From User class
///////////////////////////////////////

// New 
$lang['accounts_validate_username_invalid'] = 'Username is invalid.';
$lang['accounts_validate_username_reserved'] = 'Username is reserved for the system.';
$lang['accounts_validate_user_info_invalid'] = 'User information is invalid.';

$lang['accounts_validate_city_invalid'] = 'City is invalid.';
$lang['accounts_validate_country_invalid'] = 'Country is invalid.';
$lang['accounts_validate_description_invalid'] = 'Description is invalid.';
$lang['accounts_validate_display_name_invalid'] = 'Display name is invalid.';
$lang['accounts_validate_fax_invalid'] = 'Fax number is invalid.';
$lang['accounts_validate_first_name_invalid'] = 'First name is invalid.';
$lang['accounts_validate_gid_number_invalid'] = 'Group ID is invalid.';
$lang['accounts_validate_home_directory_invalid'] = 'Home directory is invalid.';
$lang['accounts_validate_last_name_invalid'] = 'Last name is invalid.';

// Keep
$lang['accounts_last_name'] = 'Last Name';


// Delete
// $lang['accounts_errmsg_reserved_system_user'] = 'This account name is reserved for the system.';

// TODO
$lang['accounts_account_locked'] = 'Locked';
$lang['accounts_account_lock_status'] = 'Account Lock Status';
$lang['accounts_account_unlocked'] = 'Unlocked';
$lang['accounts_check_first_last_name'] = 'Check the first and last name';
$lang['accounts_display_name'] = 'Display Name';
$lang['accounts_email'] = 'Mailbox';
$lang['accounts_errmsg_access_control_violation'] = 'Access control violation.';
$lang['accounts_errmsg_duplicate_full_name'] = 'User manager must not contain duplicate full names';
$lang['accounts_errmsg_flexshare_exists'] = 'Delete failed - the following Flexshare depends on this user';
$lang['accounts_errmsg_flexshare_with_this_name_exists'] = 'Username is already in use by Flexshare system';
$lang['accounts_errmsg_password_error'] = 'An error occurred setting the password.';
$lang['accounts_errmsg_username_not_exist'] = 'User does not exist.';
$lang['accounts_errmsg_user_already_exists'] = 'User already exists';
$lang['accounts_errmsg_user_not_found'] = 'User not found';
$lang['accounts_errmsg_user_synchronization_error'] = 'User database synchronization error.';
$lang['accounts_extension'] = 'Extension';
$lang['accounts_first_name'] = 'First Name';
$lang['accounts_ftp'] = 'FTP';
$lang['accounts_fullname'] = 'Full Name';
$lang['accounts_gidnumber'] = 'Group ID Number';
$lang['accounts_google_apps'] = 'Google Apps';
$lang['accounts_homedir'] = 'Home Directory';
$lang['accounts_lock'] = 'Lock';
$lang['accounts_mail_address'] = 'E-mail Address';
$lang['accounts_mail_alias'] = 'Mail Alias';
$lang['accounts_mail_aliases'] = 'Mail Aliases';
$lang['accounts_mail_forwarder'] = 'Mail Forwarder';
$lang['accounts_mail_forwarders'] = 'Mail Forwarders';
$lang['accounts_mail_services'] = 'Mail Services';
$lang['accounts_name'] = 'Name';
$lang['accounts_old_password_invalid'] = 'Old password invalid';
$lang['accounts_openvpn'] = 'OpenVPN';
$lang['accounts_options'] = 'Options';
$lang['accounts_password_in_history'] = 'Password is in history of old passwords';
$lang['accounts_password_not_changed'] = 'Password is not being changed from existing value';
$lang['accounts_password_too_young'] = 'Password is too young to change';
$lang['accounts_password_violates_quality_check'] = 'Password fails quality checking policy';
$lang['accounts_password_was_reset'] = 'Password required a reset.';
$lang['accounts_pbx'] = 'PBX';
$lang['accounts_phone_numbers'] = 'Telephone Numbers';
$lang['accounts_pptp'] = 'PPTP VPN';
$lang['accounts_presence'] = 'Presence';
$lang['accounts_proxy'] = 'Proxy Server';
$lang['accounts_quota'] = 'Quota';
$lang['accounts_samba'] = 'Windows Networking';
$lang['accounts_secure_password'] = 'Secure Password';
$lang['accounts_server'] = 'Server';
$lang['accounts_services'] = 'Services';
$lang['accounts_shell'] = 'Login Shell';
$lang['accounts_status_converted'] = 'Converted';
$lang['accounts_status_exists'] = 'Exists';
$lang['accounts_title'] = 'Title';
$lang['accounts_uidnumber'] = 'User ID Number';
$lang['accounts_unlock'] = 'Unlock';
$lang['accounts_user_details'] = 'User Details';
$lang['accounts_verify'] = 'Verify';
$lang['accounts_web'] = 'Web';
