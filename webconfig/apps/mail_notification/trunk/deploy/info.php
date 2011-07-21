<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'mail_notification';
$app['version'] = '5.9.9.3';
$app['release'] = '1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['summary'] = 'ClearOS Mail Notification';
$app['description'] = 'Mail Notification....'; // FIXME

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('mail_notification_mail_notification');
$app['category'] = lang('base_category_system');
$app['subcategory'] = lang('base_subcategory_settings');

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['core_requires'] = array(
    'postfix',
);

$app['core_file_manifest'] = array(
    'mailer.conf' => array(
        'target' => '/etc/mailer.conf',
        'mode' => '0755',
        'config' => TRUE,
        'config_params' => 'noreplace',
    ),
);
