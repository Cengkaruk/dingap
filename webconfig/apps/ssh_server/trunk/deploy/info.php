<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'ssh_server';
$app['version'] = '5.9.9.5';
$app['release'] = '1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['description'] = lang('ssh_server_app_description');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('ssh_server_app_name');
$app['category'] = lang('base_category_network');
$app['subcategory'] = lang('base_subcategory_infrastructure');

/////////////////////////////////////////////////////////////////////////////
// Controllers
/////////////////////////////////////////////////////////////////////////////

$app['controllers']['ssh_server']['title'] = lang('ssh_server_app_name');

// FIXME: redirect is broken?
// $app['controllers']['settings']['title'] = lang('base_settings');

// FIXME: not working in summary view?
// $app['controllers']['ssh_server']['tooltip'] = lang('ssh_server_app_tooltip');

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['requires'] = array(
    'app-network',
);

$app['core_requires'] = array(
    'app-network-core',
    'openssh-server >= 5.3p1',
);

$app['core_directory_manifest'] = array(
    '/var/clearos/ssh_server' => array(),
);

$app['core_file_manifest'] = array(
    'sshd.php'=> array('target' => '/var/clearos/base/daemon/sshd.php'),
);
