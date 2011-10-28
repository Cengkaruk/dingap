<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'content_filter';
$app['version'] = '5.9.9.5';
$app['release'] = '4';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['description'] = lang('content_filter_app_description');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('content_filter_app_name');
$app['category'] = lang('base_category_gateway');
$app['subcategory'] = lang('base_subcategory_content_filter_and_proxy');

/////////////////////////////////////////////////////////////////////////////
// Controllers
/////////////////////////////////////////////////////////////////////////////

$app['controllers']['content_filter']['title'] = lang('content_filter_app_name');

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
    '/var/clearos/content_filter' => array(),
    '/var/clearos/content_filter/backup/' => array(),
);

$app['core_file_manifest'] = array(
    'dansguardian-av.php'=> array('target' => '/var/clearos/base/daemon/dansguardian-av.php'),
);
