
<?php

$app['basename'] = 'dns';
$app['version'] = '6.0';
$app['release'] = '0.2';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_libraries'] = 'LGPLv3';
$app['description'] = 'The local DNS server can be used for mapping IP addresses on your network to hostnames.'; // FIXME translate

$app['name'] = lang('dns_dns_server');
$app['category'] = lang('base_category_network');
$app['subcategory'] = lang('base_subcategory_infrastructure');

$app['forms']['dns']['title'] = $app['name'];
