<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'ftp';
$app['version'] = '5.9.9.2';
$app['release'] = '4';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['summary'] = lang('ftp_app_summary');
$app['description'] = lang('ftp_app_long_description');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('ftp_ftp_server');
$app['category'] = lang('base_category_server');
$app['subcategory'] = lang('base_subcategory_file');

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['requires'] = array(
    'app-network', 
    'proftpd >= 1.3.3e'
);

$app['core_requires'] = array(
    'app-ftp-plugin-core',
);

$app['core_directory_manifest'] = array(
    '/var/clearos/ftp' => array(),
);


// FIXME: need default configuration file
