<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'accounts';
$app['version'] = '5.9.9.5';
$app['release'] = '1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['description'] = lang('accounts_app_description');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('accounts_app_name');
$app['category'] = lang('base_category_system');
$app['subcategory'] = 'Accounts Manager';

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['requires'] = array(
    'app-mode-core',
);

$app['core_requires'] = array(
    'app-mode-core',
    'app-storage-core',
    'system-accounts-driver',
);

$app['core_directory_manifest'] = array(
   '/var/clearos/accounts' => array(),
   '/var/clearos/accounts/drivers' => array(),
   '/var/clearos/accounts/plugins' => array(),
);

$app['core_file_manifest'] = array(
   'accounts-init' => array(
        'target' => '/usr/sbin/accounts-init',
        'mode' => '0755',
    ),
    'storage-home-default.conf' => array ('target' => '/etc/clearos/storage.d/home-default.conf'),
    'storage-home.php' => array('target' => '/var/clearos/storage/plugins/home.php'),
    'nscd.php'=> array('target' => '/var/clearos/base/daemon/nscd.php'),
    'nslcd.php'=> array('target' => '/var/clearos/base/daemon/nslcd.php'),
);
