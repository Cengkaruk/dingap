<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'web_proxy';
$app['version'] = '6.2.0.beta3';
$app['release'] = '1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['description'] = lang('web_proxy_app_description');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('web_proxy_app_name');
$app['category'] = lang('base_category_gateway');
$app['subcategory'] = lang('base_subcategory_content_filter_and_proxy');

/////////////////////////////////////////////////////////////////////////////
// Controllers
/////////////////////////////////////////////////////////////////////////////

$app['controllers']['web_proxy']['title'] = lang('web_proxy_app_name');
$app['controllers']['settings']['title'] = lang('base_settings');

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['requires'] = array(
    'app-network',
);

$app['core_requires'] = array(
    'app-network-core',
    'app-firewall-core',
    'app-web-proxy-plugin-core',
    'csplugin-filewatch',
    'squid >= 3.1.10',
);

$app['core_directory_manifest'] = array(
    '/etc/clearos/web_proxy.d' => array(),
    '/var/clearos/web_proxy' => array(),
    '/var/clearos/web_proxy/backup' => array(),
    '/var/clearos/web_proxy/errors' => array(),
);

$app['core_file_manifest'] = array(
    'squid.php'=> array('target' => '/var/clearos/base/daemon/squid.php'),
    'web_proxy.acl'=> array('target' => '/var/clearos/base/access_control/public/web_proxy'),
    'filewatch-web-proxy-configuration.conf'=> array('target' => '/etc/clearsync.d/filewatch-web-proxy-configuration.conf'),
    'filewatch-web-proxy-network.conf'=> array('target' => '/etc/clearsync.d/filewatch-web-proxy-network.conf'),
    'authorize' => array(
        'target' => '/etc/clearos/web_proxy.d/authorize',
        'mode' => '0644',
        'owner' => 'root',
        'group' => 'root',
        'config' => TRUE,
        'config_params' => 'noreplace',
    ),
    'squid_acls.conf' => array(
        'target' => '/etc/squid/squid_acls.conf',
        'config' => TRUE,
        'config_params' => 'noreplace',
    ),
    'squid_auth.conf' => array(
        'target' => '/etc/squid/squid_auth.conf',
        'config' => TRUE,
        'config_params' => 'noreplace',
    ),
    'squid_http_access.conf' => array(
        'target' => '/etc/squid/squid_http_access.conf',
        'config' => TRUE,
        'config_params' => 'noreplace',
    ),
    'squid_http_port.conf' => array(
        'target' => '/etc/squid/squid_http_port.conf',
        'config' => TRUE,
        'config_params' => 'noreplace',
    ),
    'squid_lans.conf' => array(
        'target' => '/etc/squid/squid_lans.conf',
        'config' => TRUE,
        'config_params' => 'noreplace',
    ),
);
