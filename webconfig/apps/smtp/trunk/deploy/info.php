<?php

$package['type'] = 'app';
$package['basename'] = 'smtp';
$package['title'] = lang('smtp_smtp_server');
$package['description'] = 'SMTP description...';

$package['version'] = '6.0';
$package['release'] = '0.2';

$package['vendor'] = 'ClearFoundation';
$package['packager'] = 'ClearFoundation';
$package['license'] = 'GPLv3';
$package['license_libraries'] = 'LGPLv3';

$package['category'] = 'System';
$package['subcategory'] = 'Settings';

$package['forms']['smtp']['title'] = lang('smtp_smtp_server');
$package['forms']['general']['title'] = lang('base_general_settings');
$package['forms']['trusted']['title'] = lang('smtp_trusted_networks');
