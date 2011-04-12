<?php

$app['basename'] = 'smtp';
$app['version'] = '6.0';
$app['release'] = '0.2';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['summary'] = 'SMTP server and gateway.';
$app['description'] = 'SMTP description blah blah blah...';

$app['name'] = lang('smtp_smtp_server');
$app['category'] = lang('base_category_server');
$app['subcategory'] = lang('base_subcategory_mail');

// Packaging
$app['core_dependencies'] = array('app-base-core', 'postfix >= 2.6.6');

$app['forms']['smtp']['title'] = $app['name'];
$app['forms']['general']['title'] = lang('base_general_settings');
$app['forms']['general']['title'] = lang('base_general_settings');
$app['forms']['trusted']['title'] = lang('smtp_trusted_networks');
$app['forms']['additional']['title'] = lang('smtp_additional_domains');
$app['forms']['forwarding']['title'] = lang('smtp_mail_forwarding');
