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
$app['summary'] = lang('mysql_app_summary');
$app['description'] = lang('mysql_app_long_description');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('mysql_mysql_database');
$app['category'] = lang('base_category_server');
$app['subcategory'] = lang('base_subcategory_database');

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['core_requires'] = array(
    'app-network-core', 
    'mysql-server >= 5.1.52'
);
$app['core_file_manifest'] = array( 
    'mysql-default.conf' => array ( 'target' => '/etc/storage.d/mysql-default.conf' ),
    'mysql.php' => array( 'target' => '/var/clearos/storage/plugins/mysql.php' ),
);
