<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'directory_manager';
$app['version'] = '5.9.9.0';
$app['release'] = '1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['summary'] = 'Directory management and setup.'; // FIXME: translate
$app['description'] = 'The Directory Manager provides... blah blah blah'; // FIXME: translate

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('directory_manager_directory_manager');
$app['category'] = lang('base_category_server');
$app['subcategory'] = lang('base_subcategory_directory');

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

/*
$app['core_requires'] = array(
    'system-directory-driver',
);
*/

$app['core_directory_manifest'] = array(
   '/var/clearos/directory_manager' => array(),
   '/var/clearos/directory_manager/drivers' => array(),
   '/var/clearos/directory_manager/plugins' => array(),
);
