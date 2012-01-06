<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'web_proxy_plugin';
$app['version'] = '6.2.0.beta3';
$app['release'] = '1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['description'] = lang('web_proxy_plugin_app_description');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('web_proxy_plugin_app_name');
$app['category'] = lang('base_category_server');
$app['subcategory'] = lang('base_subcategory_directory');
$app['menu_enabled'] = FALSE;

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['core_only'] = TRUE;

$app['core_requires'] = array(
    'app-accounts-core', 
);

$app['core_file_manifest'] = array( 
   'web_proxy.php' => array(
        'target' => '/var/clearos/accounts/plugins/web_proxy.php'
    ),
);
