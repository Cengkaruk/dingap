<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'intrusion_prevention';
$app['version'] = '6.1.0.beta2';
$app['release'] = '1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['description'] = lang('intrusion_prevention_app_description');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('intrusion_prevention_app_name');
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

$app['core_file_manifest'] = array(
    'snortsam.php'=> array('target' => '/var/clearos/base/daemon/snortsam.php'),
);
