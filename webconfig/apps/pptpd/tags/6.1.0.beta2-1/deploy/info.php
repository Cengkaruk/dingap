<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'pptpd';
$app['version'] = '6.1.0.beta2';
$app['release'] = '1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['description'] = lang('pptpd_app_description');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('pptpd_app_name');
$app['category'] = lang('base_category_network');
$app['subcategory'] = lang('base_subcategory_vpn');

/////////////////////////////////////////////////////////////////////////////
// Controllers
/////////////////////////////////////////////////////////////////////////////

$app['controllers']['pptpd']['title'] = $app['name'];
$app['controllers']['settings']['title'] = lang('base_settings');

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['requires'] = array(
    'app-accounts',
    'app-incoming-firewall',
    'app-groups',
    'app-users',
    'app-network',
);

$app['core_requires'] = array(
    'app-network-core',
    'app-pptpd-plugin-core',
    'app-samba-extension-core',
    'app-incoming-firewall-core',
    'csplugin-routewatch',
    'pptpd >= 1.3.4',
    'samba-winbind',
);

$app['core_directory_manifest'] = array(
    '/etc/clearos/pptpd.d' => array(),
    '/var/clearos/pptpd' => array(),
    '/var/clearos/pptpd/backup' => array(),
);

$app['core_file_manifest'] = array(
    'pptpd.php'=> array('target' => '/var/clearos/base/daemon/pptpd.php'),
    'authorize' => array(
        'target' => '/etc/clearos/pptpd.d/authorize',
        'mode' => '0644',
        'owner' => 'root',
        'group' => 'root',
        'config' => TRUE,
        'config_params' => 'noreplace',
    ),
);
