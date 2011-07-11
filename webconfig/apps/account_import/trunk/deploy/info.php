<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'account_import';
$app['version'] = '5.9.9.0';
$app['release'] = '1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['summary'] = lang('account_import_app_summary');
$app['description'] = lang('account_import_app_long_description');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('account_import_account_import');
$app['category'] = lang('base_category_system');
$app['subcategory'] = lang('base_subcategory_accounts_manager');

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['requires'] = array(
    'app-users',
);
$app['core_directory_manifest'] = array(
   '/var/clearos/account_import' => array('mode' => '755', 'owner' => 'webconfig', 'group' => 'webconfig')
);
