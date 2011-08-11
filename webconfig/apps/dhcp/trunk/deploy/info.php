<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'dhcp';
$app['version'] = '5.9.9.4';
$app['release'] = '1.1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['description'] = lang('dhcp_app_description');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('dhcp_app_name');
$app['category'] = lang('base_category_network');
$app['subcategory'] = lang('base_subcategory_infrastructure');

/////////////////////////////////////////////////////////////////////////////
// Controllers
/////////////////////////////////////////////////////////////////////////////

$app['controllers']['dhcp']['title'] = lang('dhcp_app_name');
$app['controllers']['settings']['title'] = lang('base_settings');
$app['controllers']['subnets']['title'] = lang('dhcp_subnets');
$app['controllers']['leases']['title'] = lang('dhcp_leases');

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['requires'] = array(
    'app-network',
);

$app['core_requires'] = array(
    'app-network-core',
    'dhcping',
    'dnsmasq >= 2.48',
    'iptables',
);

$app['core_file_manifest'] = array(
    'dhcptest' => array( 
        'target' => '/usr/sbin/dhcptest',
        'mode' => '0755',
    ),
);
