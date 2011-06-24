<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'port_forwarding';
$app['version'] = '5.9.9.2';
$app['release'] = '3.1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['summary'] = lang('port_forwarding_app_summary');
$app['description'] = lang('port_forwarding_app_long_description');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('port_forwarding_port_forwarding');
$app['category'] = lang('base_category_network');
$app['subcategory'] = lang('base_subcategory_firewall');

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['requires'] = array(
    'app-network',
);

$app['core_requires'] = array(
    'app-firewall-core',
    'app-network-core',
);
