<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'content_filter';
$app['version'] = '6.2.0.beta3';
$app['release'] = '1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['description'] = lang('content_filter_app_description');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('content_filter_app_name');
$app['category'] = lang('base_category_gateway');
$app['subcategory'] = lang('base_subcategory_content_filter_and_proxy');

/////////////////////////////////////////////////////////////////////////////
// Controllers
/////////////////////////////////////////////////////////////////////////////

$app['controllers']['content_filter']['title'] = lang('content_filter_app_name');
$app['controllers']['policy']['title'] = lang('content_filter_policy');

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['requires'] = array(
    'app-antiphishing',
    'app-antivirus',
    'app-network',
    'app-groups',
    'app-web-proxy',
);

$app['core_requires'] = array(
    'app-antiphishing-core',
    'app-antivirus-core',
    'app-base-core >= 6.1.0-beta2.1',
    'app-firewall-core',
    'app-groups-core',
    'app-network-core',
    'app-web-proxy-core',
    'csplugin-filewatch',
    'dansguardian-av >= 2.10.1.1-5',
);

$app['core_directory_manifest'] = array(
    '/var/clearos/content_filter' => array(),
    '/var/clearos/content_filter/backup/' => array(),
);

$app['core_file_manifest'] = array(
    'filewatch-content-filter-configuration.conf'=> array('target' => '/etc/clearsync.d/filewatch-content-filter-configuration.conf'),
    'filewatch-content-filter-network.conf'=> array('target' => '/etc/clearsync.d/filewatch-content-filter-network.conf'),
    'dansguardian-av.php'=> array('target' => '/var/clearos/base/daemon/dansguardian-av.php'),
    'content_filter.acl'=> array('target' => '/var/clearos/base/access_control/public/content_filter'),
);
