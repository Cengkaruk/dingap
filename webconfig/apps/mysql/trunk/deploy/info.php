<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'mysql';
$app['version'] = '5.9.9.0';
$app['release'] = '1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['description'] = lang('mysql_app_description');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('mysql_app_name');
$app['category'] = lang('base_category_server');
$app['subcategory'] = lang('base_subcategory_database');

/////////////////////////////////////////////////////////////////////////////
// Controllers
/////////////////////////////////////////////////////////////////////////////

// FIXME: add tooltip about "root" account, it's the DB root, not system root
// $app['controllers']['mysql']['tooltip'] = lang('mysql_app_tooltip');

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['core_requires'] = array(
    'app-network-core', 
    'mysql-server >= 5.1.52',
    'phpMyAdmin >= 3.4.7'
);

$app['core_file_manifest'] = array( 
    'mysql-default.conf' => array ( 'target' => '/etc/storage.d/mysql-default.conf' ),
    'mysql.php' => array( 'target' => '/var/clearos/storage/plugins/mysql.php' ),
    'mysqld.php'=> array('target' => '/var/clearos/base/daemon/mysqld.php'),
);
