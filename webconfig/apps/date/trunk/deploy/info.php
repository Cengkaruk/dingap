<?php

$app['basename'] = 'date';
$app['version'] = '6.0';
$app['release'] = '0.2';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_libraries'] = 'LGPLv3';
$app['description'] = 'Synchronize the clock and set the date and time zone.'; // FIXME: translate

$app['name'] = lang('date_time_and_date');
$app['category'] = lang('base_category_system');
$app['subcategory'] = lang('base_subcategory_settings');

$app['forms']['date']['title'] = $app['name'];
