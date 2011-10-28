<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'openvpn';
$app['version'] = '5.9.9.1';
$app['release'] = '1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['summary'] = lang('openvpn_app_summary');
$app['description'] = lang('openvpn_app_long_description');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('openvpn_openvpn');
$app['category'] = lang('base_category_network');
$app['subcategory'] = lang('base_subcategory_vpn');

/////////////////////////////////////////////////////////////////////////////
// Controllers
/////////////////////////////////////////////////////////////////////////////


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
    'app-keys-extension-core',
    'openvpn >= 2.1.4',
);

$app['core_directory_manifest'] = array(
   '/var/clearos/openvpn' => array(),
   '/var/clearos/openvpn/backup' => array(),
);

$app['core_file_manifest'] = array(
    'openvpn.php'=> array('target' => '/var/clearos/base/daemon/openvpn.php'),
);
