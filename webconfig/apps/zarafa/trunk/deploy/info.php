<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'zarafa';
$app['version'] = '5.9.9.3';
$app['release'] = '2.1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'GPLv3';
$app['description'] = lang('zarafa_app_description');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('zarafa_app_name');
$app['category'] = lang('base_category_server');
$app['subcategory'] = lang('base_subcategory_messaging_and_collaboration');

/////////////////////////////////////////////////////////////////////////////
// Controllers
/////////////////////////////////////////////////////////////////////////////

/*
$app['controllers']['zarafa']['title'] = $app['name'];
$app['controllers']['settings']['title'] = lang('base_settings');
*/

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['requires'] = array(
    'app-accounts',
    'app-groups',
    'app-users',
    'app-network',
    'app-postfix',
);

// 'app-system-mysql-core
$app['core_requires'] = array(
    'app-zarafa-extension-core',
    'app-openldap-directory-core', 
    'zarafa',
    'zarafa-webaccess',
);
