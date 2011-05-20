<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'intrusion_prevention';
$app['version'] = '5.9.9.1';
$app['release'] = '1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['summary'] = lang('intrusion_prevention_app_summary');
$app['description'] = lang('intrusion_prevention_app_long_description');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('intrusion_prevention_intrusion_prevention');
$app['category'] = lang('base_category_gateway');
$app['subcategory'] = lang('base_subcategory_intrusion_protection');

/////////////////////////////////////////////////////////////////////////////
// Controllers
/////////////////////////////////////////////////////////////////////////////

$app['controllers']['blocked_list']['title'] = lang('intrusion_prevention_blocked_list');
$app['controllers']['white_list']['title'] = lang('intrusion_prevention_white_list');

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['requires'] = array(
    'app-intrusion-detection',
    'app-network',
);

$app['core_requires'] = array(
    'app-network-core',
    'app-intrusion-detection-core',
    'snort >= 2.9.0.4',
);
