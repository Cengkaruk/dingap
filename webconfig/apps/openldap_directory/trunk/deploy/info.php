<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'openldap_directory';
$app['version'] = '5.9.9.1';
$app['release'] = '1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['summary'] = 'OpenLDAP Directory Driver.';
$app['description'] = 'OpenLDAP directory driver... blah blah.'; // FIXME: translate

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = 'OpenLDAP Directory';
$app['category'] = lang('base_category_server');
$app['subcategory'] = lang('base_subcategory_directory');
$app['menu_enabled'] = FALSE;

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['core_only'] = TRUE;

$app['core_provides'] = array(
    'system-accounts-driver',
    'system-groups-driver',
    'system-users-driver',
);

$app['core_requires'] = array(
    'app-accounts-core',
    'app-groups-core',
    'app-ldap-core',
    'app-network-core',
    'app-openldap-core',
    'app-samba-core',
    'app-users-core',
    'nss-pam-ldapd',
    'nscd',
    'openldap >= 2.4.19',
    'openldap-clients >= 2.4.19',
    'openldap-servers >= 2.4.19',
    'pam_ldap',
    'samba-winbind-clients',
    'webconfig-php-ldap'
);

$app['core_file_manifest'] = array(
    'openldap_directory.php' => array( 'target' => '/var/clearos/accounts/drivers/openldap_directory.php' ),
    'nslcd.conf' => array( 'target' => '/var/clearos/ldap/synchronize/nslcd.conf' ),
    'pam_ldap.conf' => array( 'target' => '/var/clearos/ldap/synchronize/pam_ldap.conf' ),
);

$app['core_directory_manifest'] = array(
   '/var/clearos/openldap_directory' => array(),
   '/var/clearos/openldap_directory/extensions' => array(),
);
