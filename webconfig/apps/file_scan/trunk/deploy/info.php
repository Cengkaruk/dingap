<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'file_scan';
$app['version'] = '5.9.9.0';
$app['release'] = '1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['summary'] = 'Antivirus file scannner.';
$app['description'] = 'Antivirus file scanner...blah blah.'; // FIXME: translate

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = 'Antivirus File Scan'; // FIXME
$app['category'] = lang('base_category_server');
$app['subcategory'] = lang('base_subcategory_file_and_print');

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

/*
$app['core_requires'] = array(
    'app-cron-core', 
    'ntpdate >= 4.2.4p8'
);

$app['core_directory_manifest'] = array(
    '/etc/clearos/date' => array(),
);

$app['core_file_manifest'] = array( 
   'app-date.cron' => array(
        'target' => '/etc/cron.d/app-date',
        'mode' => '0644',
        'onwer' => 'root',
        'group' => 'root',
    ),

   'timesync' => array(
        'target' => '/usr/sbin/timesync',
        'mode' => '0755',
        'onwer' => 'root',
        'group' => 'root',
    ),
);
*/
