<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'print_server';
$app['version'] = '6.2.0.beta3';
$app['release'] = '1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['description'] = lang('print_server_app_description');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('print_server_app_name');
$app['category'] = lang('base_category_server');
$app['subcategory'] = lang('base_subcategory_file');

/////////////////////////////////////////////////////////////////////////////
// Controllers
/////////////////////////////////////////////////////////////////////////////

$app['controllers']['print_server']['title'] = $app['name'];
$app['controllers']['settings']['title'] = lang('base_settings');
$app['controllers']['policy']['title'] = lang('base_app_policy');

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['requires'] = array(
    'app-groups',
    'app-users',
    'app-network', 
);

$app['core_requires'] = array(
    'app-certificate-manager-core',
    'app-groups',
    'app-print-server-plugin-core',
    'app-users',
    'cups >= 1.4.2',
    'csplugin-filewatch',
);

$app['core_directory_manifest'] = array(
    '/etc/clearos/print_server.d' => array(),
    '/var/clearos/print_server' => array(),
    '/var/clearos/print_server/backup/' => array(),
);

$app['core_file_manifest'] = array(
    'cups.php'=> array('target' => '/var/clearos/base/daemon/cups.php'),
    'filewatch-print-server-configuration.conf'=> array('target' => '/etc/clearsync.d/filewatch-print-server-configuration.conf'),
    'filewatch-print-server-network.conf'=> array('target' => '/etc/clearsync.d/filewatch-print-server-network.conf'),
);
