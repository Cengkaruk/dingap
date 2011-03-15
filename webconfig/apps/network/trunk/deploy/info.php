<?php

$app['basename'] = 'network';
$app['version'] = '6.0';
$app['release'] = '0.2';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_libraries'] = 'LGPLv3';
$app['description'] = 'Network description...';

$app['name'] = 'IP Settings';
$app['category'] = lang('base_category_network');
$app['subcategory'] = lang('base_subcategory_settings');

$app['forms']['network']['title'] = $app['name'];
$app['forms']['general']['title'] = lang('base_general_settings');
$app['forms']['general']['title'] = lang('base_general_settings');
$app['forms']['trusted']['title'] = lang('network_trusted_networks');
$app['forms']['additional']['title'] = lang('network_additional_domains');
$app['forms']['forwarding']['title'] = lang('network_mail_forwarding');
