<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'accounts';
$app['version'] = '5.9.9.0';
$app['release'] = '1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['summary'] = 'Accounts base engine'; // FIXME: translate
$app['description'] = 'The accounts base engine provides... blah blah blah'; // FIXME: translate

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = 'Account Manager';
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
    'system-accounts-driver',
);

$app['core_directory_manifest'] = array(
   '/var/clearos/accounts' => array(),
   '/var/clearos/accounts/drivers' => array(),
   '/var/clearos/accounts/plugins' => array(),
);
