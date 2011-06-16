<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'configuration_backup';
$app['version'] = '5.9.9.0';
$app['release'] = '1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['summary'] = lang('configuration_backup_policies_app_summary');
$app['description'] = lang('configuration_backup_app_long_description');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = 'Configuration Backup/Restore';
$app['category'] = lang('base_category_system');
$app['subcategory'] = lang('base_subcategory_backup');

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['core_file_manifest'] = array( 
   'backup.conf' => array(
        'target' => '/etc/backup.conf',
        'mode' => '0644',
        'owner' => 'webconfig',
        'group' => 'webconfig',
        'config' => TRUE,
        'config_params' => 'noreplace',
    )
);