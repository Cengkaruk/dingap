<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'mail_archive';
$app['version'] = '5.9.9.3';
$app['release'] = '2.1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['summary'] = lang('mail_archive_summary');
$app['description'] = lang('mail_archive_page_intro');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('mail_archive_mail_archive');
$app['category'] = lang('base_category_system');
$app['subcategory'] = lang('base_subcategory_storage');

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['core_requires'] = array(
    'philesight'
);

$app['core_file_manifest'] = array( 
   'app-mail-archive.cron' => array(
        'target' => '/etc/cron.d/app-mail-archive',
        'mode' => '0644',
        'owner' => 'root',
        'group' => 'root',
    )
);
