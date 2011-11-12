<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'mysql';
$app['version'] = '6.1.0.beta2';
$app['release'] = '1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['description'] = lang('mysql_app_description');
$app['tooltip'] = lang('mysql_app_tooltip');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('mysql_app_name');
$app['category'] = lang('base_category_server');
$app['subcategory'] = lang('base_subcategory_database');

/////////////////////////////////////////////////////////////////////////////
// Controllers
/////////////////////////////////////////////////////////////////////////////


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
