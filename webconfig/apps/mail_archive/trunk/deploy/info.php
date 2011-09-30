<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'mail_archive';
$app['version'] = '5.9.9.3';
$app['release'] = '1';
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
$app['category'] = lang('base_category_server');
$app['subcategory'] = lang('base_subcategory_mail');

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['requires'] = array(
    'app-smtp',
    'app-imap'
);

$app['core_file_manifest'] = array(
   'marketplace.conf' => array(
        'target' => '/etc/clearos/mail_archive.conf',
        'mode' => '0640',
        'owner' => 'webconfig',
        'group' => 'webconfig',
        'config' => TRUE,
        'config_params' => 'noreplace',
    ),
   'app-mail-archive.cron' => array(
        'target' => '/etc/cron.d/app-mail-archive',
        'mode' => '0644',
        'owner' => 'root',
        'group' => 'root',
    )
);
