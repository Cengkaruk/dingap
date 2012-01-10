<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'openldap_directory';
$app['version'] = '6.2.0.beta3';
$app['release'] = '1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['description'] = lang('openldap_directory_app_description');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('openldap_directory_app_name');
$app['category'] = lang('base_category_server');
$app['subcategory'] = lang('base_subcategory_directory');

/////////////////////////////////////////////////////////////////////////////
// Controllers
/////////////////////////////////////////////////////////////////////////////

$app['controllers']['openldap_directory']['title'] = lang('openldap_directory_app_name');

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

// FIXME: beta only - remove for final
$app['obsoletes'] = array(
    'app-directory-server',
);
$app['core_obsoletes'] = array(
    'app-directory-server-core',
);

$app['requires'] = array(
    'app-users',
    'app-groups',
);

$app['core_provides'] = array(
    'system-accounts',
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
    'app-users-core',
    'authconfig',
    'nss-pam-ldapd',
    'nscd',
    'openldap >= 2.4.19',
    'openldap-clients >= 2.4.19',
    'openldap-servers >= 2.4.19',
    'pam_ldap',
    'webconfig-php-ldap'
);

$app['core_file_manifest'] = array(
    'openldap_directory.php' => array( 'target' => '/var/clearos/accounts/drivers/openldap_directory.php' ),
    'nslcd.conf' => array( 'target' => '/var/clearos/ldap/synchronize/nslcd.conf' ),
    'pam_ldap.conf' => array( 'target' => '/var/clearos/ldap/synchronize/pam_ldap.conf' ),
    'initialize-plugins' => array(
        'target' => '/usr/sbin/initialize-plugins',
        'mode' => '0755',
        'owner' => 'root',
        'group' => 'root',
    )
);

$app['core_directory_manifest'] = array(
   '/var/clearos/openldap_directory' => array(),
   '/var/clearos/openldap_directory/backup' => array(),
   '/var/clearos/openldap_directory/extensions' => array(),
);
