<?php

$app['basename'] = 'cron';
$app['version'] = '5.9.9.0';
$app['release'] = '1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['summary'] = 'Cron...'; // FIXME
$app['description'] = 'Cron...'; // FIXME: translate

/*
$app['name'] = lang('date_time_and_date');
$app['category'] = lang('base_category_system');
$app['subcategory'] = lang('base_subcategory_settings');
*/

// Packaging
$app['core_dependencies'] = array('app-base-core', 'cronie >= 1.4.4');
