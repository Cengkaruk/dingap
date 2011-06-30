<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'network';
$app['version'] = '5.9.9.2';
$app['release'] = '4';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['summary'] = 'Network configuration tool.';
$app['description'] = 'Network description... blah blah';

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('network_ip_settings');
$app['category'] = lang('base_category_network');
$app['subcategory'] = lang('base_subcategory_settings');

/////////////////////////////////////////////////////////////////////////////
// Controller info
/////////////////////////////////////////////////////////////////////////////

$app['controllers']['general']['title'] = 'General Settings';
$app['controllers']['iface']['title'] = 'Network Interfaces';

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['core_requires'] = array(
    'avahi',
    'bind-utils',
    'dhclient',
    'ethtool',
    'net-tools',
    'ppp',
    'rp-pppoe',
);

$app['core_directory_manifest'] = array(
    '/var/clearos/network' => array(),
    '/var/clearos/network/backup' => array(),
);

$app['core_file_manifest'] = array(
   'dhclient-exit-hooks' => array(
        'target' => '/etc/dhclient-exit-hooks',
        'mode' => '0644',
        'owner' => 'root',
        'group' => 'root',
    ),
   'network.conf' => array(
        'target' => '/etc/clearos/network.conf',
        'mode' => '0644',
        'owner' => 'root',
        'group' => 'root',
        'config' => TRUE,
        'config_params' => 'noreplace',
    ),
);
