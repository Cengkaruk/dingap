<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'incoming_firewall';
$app['version'] = '5.9.9.2';
$app['release'] = '3.1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['summary'] = lang('incoming_firewall_app_summary');
$app['description'] = lang('incoming_firewall_app_long_description');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('incoming_firewall_incoming_firewall');
$app['category'] = lang('base_category_network');
$app['subcategory'] = lang('base_subcategory_firewall');


/////////////////////////////////////////////////////////////////////////////
// Controllers
/////////////////////////////////////////////////////////////////////////////

$app['controllers']['incoming_firewall']['title'] = lang('incoming_firewall_incoming_firewall');
$app['controllers']['allow']['title'] = lang('incoming_firewall_allowed_incoming_connections');
$app['controllers']['block']['title'] = lang('incoming_firewall_blocked_incoming_connections');

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
