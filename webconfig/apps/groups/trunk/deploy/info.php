<?php

$app['basename'] = 'groups';
$app['version'] = '6.0';
$app['release'] = '0.2';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['summary'] = 'Group manager'; // FIXME: translate
$app['description'] = 'Group manager description blah blah...'; // FIXME: translate

$app['name'] = lang('groups_group_manager');
$app['category'] = lang('base_category_server');
$app['subcategory'] = lang('base_subcategory_accounts');

// Packaging
$app['core_dependencies'] = array('app-base-core', 'app-directory-manager');
