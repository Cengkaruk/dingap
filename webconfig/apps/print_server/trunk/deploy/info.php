<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'print_server';
$app['version'] = '6.1.0.beta2';
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
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['requires'] = array(
    'app-network', 
);

$app['core_requires'] = array(
    'app-certificate-manager-core',
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
    'filewatch-print-server.conf'=> array('target' => '/etc/clearsync.d/filewatch-print-server.conf'),
);
/*
    'authorize' => array(
        'target' => '/etc/clearos/print_server.d/authorize',
        'mode' => '0644',
        'owner' => 'root',
        'group' => 'root',
        'config' => TRUE,
        'config_params' => 'noreplace',
    ),
*/
