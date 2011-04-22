<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'openldap_accounts';
$app['version'] = '5.9.9.0';
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

$app['name'] = 'OpenLDAP Accounts'; // FIXME
$app['category'] = lang('base_category_system');
$app['subcategory'] = 'Account Manager';

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['core_provides'] = array(
    'system-accounts',
);

$app['core_requires'] = array(
    'app-cron-core',
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
