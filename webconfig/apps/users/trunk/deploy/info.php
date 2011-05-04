<?php

$app['basename'] = 'users';
$app['version'] = '6.0';
$app['release'] = '0.2';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['summary'] = 'User manager'; // FIXME: translate
$app['description'] = 'User manager description blah blah blah ...'; // FIXME: translate

$app['name'] = lang('users_user_manager');
$app['category'] = lang('base_category_system');
$app['subcategory'] = lang('base_subcategory_accounts');

// Packaging
$app['core_requires'] = array('app-directory-manager');
