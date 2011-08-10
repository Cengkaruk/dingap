<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'disk_usage';
$app['version'] = '5.9.9.3';
$app['release'] = '2.1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['summary'] = lang('disk_usage_summary');
$app['description'] = lang('disk_usage_page_intro');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('disk_usage_disk_usage');
$app['category'] = lang('base_category_system');
$app['subcategory'] = lang('base_subcategory_storage');

/////////////////////////////////////////////////////////////////////////////
// Controllers
/////////////////////////////////////////////////////////////////////////////

$app['controllers']['disk_usage']['tooltip'] = lang('disk_usage_app_tooltip');

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['core_requires'] = array(
    'philesight'
);

$app['core_file_manifest'] = array( 
   'app-disk-usage.cron' => array(
        'target' => '/etc/cron.d/app-disk-usage',
        'mode' => '0644',
        'owner' => 'root',
        'group' => 'root',
    )
);

$app['core_directory_manifest'] = array(
   '/var/clearos/disk_usage' => array('mode' => '755', 'owner' => 'webconfig', 'group' => 'webconfig')
);
