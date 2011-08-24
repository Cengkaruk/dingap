<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'directory_server';
$app['version'] = '5.9.9.5';
$app['release'] = '1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['summary'] = 'Directory Server summary.';
$app['description'] = 'Directory server... blah blah.'; // FIXME: translate

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = 'Directory Server';
$app['category'] = lang('base_category_server');
$app['subcategory'] = lang('base_subcategory_directory');

/////////////////////////////////////////////////////////////////////////////
// Controllers
/////////////////////////////////////////////////////////////////////////////

$app['controllers']['openldap_directory']['title'] = $app['name'];
$app['controllers']['extensions']['title'] = lang('openldap_directory_extensions');
$app['controllers']['plugins']['title'] = lang('openldap_directory_plugins');

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['core_provides'] = array(
    'system-accounts',
);

$app['core_requires'] = array(
    'app-accounts-core',
    'app-groups-core',
    'app-network-core',
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
