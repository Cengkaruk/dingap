<?php

$app['basename'] = 'smtp';
$app['version'] = '6.0';
$app['release'] = '0.2';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_libraries'] = 'LGPLv3';
$app['description'] = 'SMTP description...';

$app['name'] = lang('smtp_smtp_server');
$app['category'] = lang('base_category_server');
$app['subcategory'] = lang('base_subcategory_mail');

$app['forms']['smtp']['title'] = $app['name'];
$app['forms']['general']['title'] = lang('base_general_settings');
$app['forms']['general']['title'] = lang('base_general_settings');
$app['forms']['trusted']['title'] = lang('smtp_trusted_networks');
$app['forms']['additional']['title'] = lang('smtp_additional_domains');
$app['forms']['forwarding']['title'] = lang('smtp_mail_forwarding');
