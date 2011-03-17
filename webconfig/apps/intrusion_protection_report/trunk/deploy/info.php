<?php

$app['basename'] = 'intrusion_protection_report';
$app['version'] = '6.0';
$app['release'] = '0.2';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_libraries'] = 'LGPLv3';
$app['description'] = 'ClearCenter Intrusion Protection Updates... blah blah blah.';

$app['name'] = lang('intrusion_protection_report_intrusion_protection_report');
$app['category'] = lang('base_category_gateway');
$app['subcategory'] = lang('base_subcategory_intrusion_protection');

$app['forms']['intrusion_protection_updates']['title'] = $app['name'];
