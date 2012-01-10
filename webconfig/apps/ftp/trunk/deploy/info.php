<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'ftp';
$app['version'] = '6.2.0.beta3';
$app['release'] = '1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['description'] = lang('ftp_app_description');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('ftp_app_name');
$app['category'] = lang('base_category_server');
$app['subcategory'] = lang('base_subcategory_file');

/////////////////////////////////////////////////////////////////////////////
// Controllers
/////////////////////////////////////////////////////////////////////////////

$app['controllers']['ftp']['title'] = lang('ftp_app_name');
$app['controllers']['settings']['title'] = lang('base_settings');
$app['controllers']['policy']['title'] = lang('base_app_policies');

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['requires'] = array(
    'app-network', 
    'app-users',
    'app-groups',
    'proftpd >= 1.3.3e'
);

$app['core_requires'] = array(
    'app-ftp-plugin-core',
);

$app['core_directory_manifest'] = array(
    '/etc/clearos/ftp.d' => array(),
    '/var/clearos/ftp' => array(),
    '/var/clearos/ftp/backup/' => array(),
);

$app['core_file_manifest'] = array(
    'proftpd.php'=> array('target' => '/var/clearos/base/daemon/proftpd.php'),
    'authorize' => array(
        'target' => '/etc/clearos/ftp.d/authorize',
        'mode' => '0644',
        'owner' => 'root',
        'group' => 'root',
        'config' => TRUE,
        'config_params' => 'noreplace',
    ),
);
