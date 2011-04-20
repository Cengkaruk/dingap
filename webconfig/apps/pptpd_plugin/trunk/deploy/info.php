<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'pptpd_plugin';
$app['version'] = '5.9.9.0';
$app['release'] = '1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['summary'] = 'PPTP Server Directory Plugin';
$app['description'] = 'The PPTP server plugin ... blah blah blah.'; // FIXME: translate

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('pptpd_plugin_pptp_server_directory_plugin');
$app['category'] = lang('base_category_server');
$app['subcategory'] = lang('base_subcategory_directory');
$app['menu_enabled'] = FALSE;

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['core_only'] = TRUE;

$app['core_requires'] = array(
    'app-directory-manager', 
);

$app['core_file_manifest'] = array( 
   'pptpd.php' => array(
        'target' => '/var/clearos/directory_manager/plugins/pptpd.php'
    ),
);
