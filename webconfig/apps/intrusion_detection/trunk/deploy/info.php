<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'intrusion_detection';
$app['version'] = '5.9.9.2';
$app['release'] = '3.1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['summary'] = lang('intrusion_detection_app_summary');
$app['description'] = lang('intrusion_detection_app_long_description');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('intrusion_detection_intrusion_detection');
$app['category'] = lang('base_category_gateway');
$app['subcategory'] = lang('base_subcategory_intrusion_protection');

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['requires'] = array(
    'app-network',
);

$app['core_requires'] = array(
    'app-network-core',
    'snort >= 2.9.0.4',
    'snort-gpl-rules',
);

$app['core_directory_manifest'] = array(
    '/var/clearos/intrusion_detection' => array(),
);
