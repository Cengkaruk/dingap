<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'ldap';
$app['version'] = '5.9.9.2';
$app['release'] = '4';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['summary'] = 'LDAP Manager';
$app['description'] = 'The LDAP mode manager... master/slave/standalone.'; // FIXME: translate

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = 'Mode'; // FIXME
$app['category'] = lang('base_category_system');
$app['subcategory'] = lang('base_subcategory_settings');
$app['menu_enabled'] = FALSE;

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['core_only'] = TRUE;

$app['core_requires'] = array(
    'app-mode-core',
    'openssl',
    'system-ldap-driver', 
);

$app['core_directory_manifest'] = array(
   '/var/clearos/ldap' => array(),
   '/var/clearos/ldap/synchronize' => array(),
);

$app['core_file_manifest'] = array(
   'ldap-init' => array(
        'target' => '/usr/sbin/ldap-init',
        'mode' => '0755',
    ),
   'ldap-manager' => array(
        'target' => '/usr/sbin/ldap-manager',
        'mode' => '0755',
    ),
   'ldap-synchronize' => array(
        'target' => '/usr/sbin/ldap-synchronize',
        'mode' => '0755',
    ),
);
