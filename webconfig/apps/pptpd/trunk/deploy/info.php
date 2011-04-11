<?php

$app['basename'] = 'pptpd';
$app['version'] = '5.9.9.0';
$app['release'] = '1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['summary'] = 'PPTP VPN Server.';
$app['description'] = 'The PPTP provides a VPN server for end users.'; // FIXME

$app['name'] = lang('pptpd_pptp_server');
$app['category'] = lang('base_category_network');
$app['subcategory'] = lang('base_subcategory_vpn');

// Packaging
$app['core_dependencies'] = array('app-base', 'app-network', 'pptpd >= 1.3.4');
