<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'web_proxy';
$app['version'] = '6.1.0.beta2';
$app['release'] = '1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['description'] = lang('web_proxy_app_description');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('web_proxy_app_name');
$app['category'] = lang('base_category_gateway');
$app['subcategory'] = lang('base_subcategory_content_filter_and_proxy');

/////////////////////////////////////////////////////////////////////////////
// Controllers
/////////////////////////////////////////////////////////////////////////////

$app['controllers']['web_proxy']['title'] = lang('web_proxy_app_name');

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['requires'] = array(
    'app-network',
);

$app['core_requires'] = array(
    'app-network-core',
    'app-firewall-core',
    'squid >= 3.1.10',
);

$app['core_directory_manifest'] = array(
    '/var/clearos/web_proxy' => array(),
    '/var/clearos/web_proxy/backup/' => array(),
);

$app['core_file_manifest'] = array(
    'squid.php'=> array('target' => '/var/clearos/base/daemon/squid.php'),
);
