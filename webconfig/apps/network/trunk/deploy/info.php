<?php

$app['basename'] = 'network';
$app['version'] = '6.0';
$app['release'] = '0.2';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['summary'] = 'Network configuration tool.';
$app['description'] = 'Network description... blah blah';

$app['name'] = lang('network_ip_settings');
$app['category'] = lang('base_category_network');
$app['subcategory'] = lang('base_subcategory_settings');

// Packaging
$app['core_dependencies'] = array(
    'app-base-core',
    'bind-utils',
    'dhclient',
    'ethtool',
    'net-tools',
    'ppp',
    'rp-pppoe',
);
$app['manifest'] = array(
   'dhclient-exit-hooks' => array(
        'target' => '/etc/dhclient-exit-hooks',
        'mode' => '0644',
        'onwer' => 'root',
        'group' => 'root',
    ),
   'firewall-up' => array(
        'target' => '/usr/sbin/firewall-up',
        'mode' => '0755',
        'onwer' => 'root',
        'group' => 'root',
    )
);