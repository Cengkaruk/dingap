<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'disk_usage';
$app['version'] = '6.1.0.beta2';
$app['release'] = '1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['description'] = lang('disk_usage_app_description');
$app['tooltip'] = lang('disk_usage_app_tooltip');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('disk_usage_app_name');
$app['category'] = lang('base_category_system');
$app['subcategory'] = lang('base_subcategory_storage');

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['core_requires'] = array(
    'philesight >= 20111015-3'
);

$app['core_file_manifest'] = array( 
    'app-disk-usage.cron' => array(
        'target' => '/etc/cron.d/app-disk-usage',
        'mode' => '0644',
    ),
    'philesight-updatedb' => array(
        'target' => '/usr/sbin/philesight-updatedb',
        'mode' => '0755',
    )
);

$app['core_directory_manifest'] = array(
   '/var/clearos/disk_usage' => array('mode' => '755', 'owner' => 'webconfig', 'group' => 'webconfig')
);
