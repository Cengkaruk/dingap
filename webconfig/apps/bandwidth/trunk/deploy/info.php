<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'bandwidth';
$app['version'] = '5.9.9.2';
$app['release'] = '4';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['description'] = lang('bandwidth_app_description');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('bandwidth_app_name');
$app['category'] = lang('base_category_network');
$app['subcategory'] = lang('base_subcategory_bandwidth_and_qos');

/////////////////////////////////////////////////////////////////////////////
// Controllers
/////////////////////////////////////////////////////////////////////////////

$app['controllers']['bandwidth']['title'] = lang('bandwidth_app_name');
$app['controllers']['ifaces']['title'] = lang('bandwidth_network_interfaces');
$app['controllers']['basic']['title'] = lang('bandwidth_basic_rules');
$app['controllers']['advanced']['title'] = lang('bandwidth_advanced_rules');

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['requires'] = array(
    'app-network',
);

$app['core_requires'] = array(
    'app-network-core',
    'app-firewall-core',
);

$app['core_directory_manifest'] = array(
    '/var/clearos/bandwidth' => array(),
    '/var/clearos/bandwidth/backup/' => array(),
);
