<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'web';
$app['version'] = '5.9.9.5';
$app['release'] = '1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['description'] = lang('web_app_description');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('web_app_name');
$app['category'] = lang('base_category_server');
$app['subcategory'] = lang('base_subcategory_web');

/////////////////////////////////////////////////////////////////////////////
// Controllers
/////////////////////////////////////////////////////////////////////////////

$app['controllers']['web']['title'] = $app['name'];
$app['controllers']['settings']['title'] = lang('base_settings');

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['requires'] = array(
    'app-accounts',
    'app-groups',
    'app-users',
    'app-network',
);

$app['core_requires'] = array(
    'app-network-core',
    'httpd >= 2.2.15',
);

$app['core_directory_manifest'] = array(
    '/var/clearos/httpd' => array(),
);

$app['core_file_manifest'] = array(
    'httpd.php'=> array('target' => '/var/clearos/base/daemon/httpd.php'),
);
