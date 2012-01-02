<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'firewall';
$app['version'] = '6.1.0.beta2.1';
$app['release'] = '1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['description'] = lang('firewall_app_description');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('firewall_app_name');
$app['category'] = lang('base_category_network');
$app['subcategory'] = lang('base_subcategory_firewall');
$app['menu_enabled'] = FALSE;

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['core_only'] = TRUE;

$app['requires'] = array(
    'app-network',
);

$app['core_requires'] = array(
    'app-network-core',
    'csplugin-filewatch',
    'firewall >= 1.4.7-3',
    'iptables',
);

$app['core_directory_manifest'] = array(
   '/var/clearos/firewall' => array(),
   '/etc/clearos/firewall.d' => array(),
);

$app['core_file_manifest'] = array(
   'local' => array(
        'target' => '/etc/clearos/firewall.d/local',
        'mode' => '0755',
        'owner' => 'root',
        'group' => 'root',
        'config' => TRUE,
        'config_params' => 'noreplace',
    ),
   'firewall.conf' => array(
        'target' => '/etc/clearos/firewall.conf',
        'mode' => '0644',
        'owner' => 'root',
        'group' => 'root',
        'config' => TRUE,
        'config_params' => 'noreplace',
    ),
   'firewall.init' => array(
        'target' => '/etc/rc.d/init.d/firewall',
        'mode' => '0755',
        'owner' => 'root',
        'group' => 'root',
    ),
   'firewall-start' => array(
        'target' => '/usr/sbin/firewall-start',
        'mode' => '0755',
        'owner' => 'root',
        'group' => 'root',
    ),
   'types' => array(
        'target' => '/etc/clearos/firewall.d/types',
        'mode' => '0755',
        'owner' => 'root',
        'group' => 'root',
    ),
    'filewatch-firewall.conf' => array(
        'target' => '/etc/clearsync.d/filewatch-firewall.conf',
    ),
);
