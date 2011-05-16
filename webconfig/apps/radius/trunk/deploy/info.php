<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'radius';
$app['version'] = '5.9.9.0';
$app['release'] = '1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['summary'] = lang('radius_app_summary');
$app['description'] = lang('radius_app_long_description');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('radius_radius_server');
$app['category'] = lang('base_category_network');
$app['subcategory'] = lang('base_subcategory_infrastructure');

/////////////////////////////////////////////////////////////////////////////
// Controller info
/////////////////////////////////////////////////////////////////////////////

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['core_requires'] = array(
    'app-accounts',
);

$app['core_requires'] = array(
    'app-network-core',
    'app-accounts-core',
    'freeradius',
    'freeradius-ldap',
    'freeradius-utils',
);

$app['core_directory_manifest'] = array( 
    '/etc/raddb/clearos-certs' => array(),
);

$app['core_file_manifest'] = array( 
   'clearos-clients.conf' => array(
        'target' => '/etc/raddb/clearos-clients.conf',
        'mode' => '0640',
        'onwer' => 'root',
        'group' => 'radiusd',
    ),

   'clearos-eap.conf' => array(
        'target' => '/etc/raddb/clearos-eap.conf',
        'mode' => '0640',
        'onwer' => 'root',
        'group' => 'radiusd',
    ),

   'clearos-users' => array(
        'target' => '/etc/raddb/clearos-users',
        'mode' => '0640',
        'onwer' => 'root',
        'group' => 'radiusd',
    ),

   'clearos-inner-tunnel' => array(
        'target' => '/etc/raddb/sites-available/clearos-inner-tunnel',
        'mode' => '0640',
        'onwer' => 'root',
        'group' => 'radiusd',
    ),
);
