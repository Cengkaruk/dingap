<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'pptpd';
$app['version'] = '5.9.9.2';
$app['release'] = '2.2';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['summary'] = lang('pptpd_app_summary');
$app['description'] = lang('pptpd_app_long_description');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('pptpd_pptp_server');
$app['category'] = lang('base_category_network');
$app['subcategory'] = lang('base_subcategory_vpn');

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['requires'] = array(
    'app-accounts',
    'app-groups',
    'app-users',
    'app-network',
);

$app['core_requires'] = array(
    'app-network-core',
    'app-pptpd-plugin-core',
    'pptpd >= 1.3.4',
);

$app['core_directory_manifest'] = array(
    '/var/clearos/pptpd' => array(),
);
