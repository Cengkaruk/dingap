<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'zarafa';
$app['version'] = '5.9.9.3';
$app['release'] = '2.1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'GPLv3';
$app['description'] = lang('zarafa_app_description');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('zarafa_app_name');
$app['category'] = lang('base_category_server');
$app['subcategory'] = lang('base_subcategory_messaging_and_collaboration');

/////////////////////////////////////////////////////////////////////////////
// Controllers
/////////////////////////////////////////////////////////////////////////////

/*
$app['controllers']['zarafa']['title'] = $app['name'];
$app['controllers']['settings']['title'] = lang('base_settings');
*/

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['requires'] = array(
    'app-accounts',
    'app-groups',
    'app-users',
    'app-network',
    'app-postfix',
);

$app['core_requires'] = array(
    'app-zarafa-extension-core',
    'app-openldap-directory-core', 
    'system-mysql-server',
    'zarafa',
    'zarafa-webaccess',
);

$app['core_file_manifest'] = array(
    'zarafa-dagent.php'=> array('target' => '/var/clearos/base/daemon/zarafa-dagent.php'),
    'zarafa-gateway.php'=> array('target' => '/var/clearos/base/daemon/zarafa-gateway.php'),
    'zarafa-ical.php'=> array('target' => '/var/clearos/base/daemon/zarafa-ical.php'),
    'zarafa-indexer.php'=> array('target' => '/var/clearos/base/daemon/zarafa-indexer.php'),
    'zarafa-monitor.php'=> array('target' => '/var/clearos/base/daemon/zarafa-monitor.php'),
    'zarafa-server.php'=> array('target' => '/var/clearos/base/daemon/zarafa-server.php'),
    'zarafa-spooler.php'=> array('target' => '/var/clearos/base/daemon/zarafa-spooler.php'),
);
