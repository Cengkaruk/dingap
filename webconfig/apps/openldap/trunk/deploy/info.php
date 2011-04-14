<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'openldap';
$app['version'] = '5.9.9.0';
$app['release'] = '1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['summary'] = 'LDAP Manager......';
$app['description'] = 'LDAP Manager...blah blah blah'; // FIXME: translate

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = 'LDAP Manager'; // FIXME
$app['category'] = lang('base_category_system');
$app['subcategory'] = lang('base_subcategory_settings');

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['core_only'] = TRUE;

$app['core_provides'] = array(
    'system-ldap-driver'
);

$app['core_dependencies'] = array(
    'openldap-servers >= 2.4.19',
    'openssl',
);

$app['core_directory_manifest'] = array(
   '/var/clearos/openldap' => array(),
   '/var/clearos/openldap/provision' => array(),
   '/var/clearos/openldap/synchronize' => array(),
);
