<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'zarafa_extension';
$app['version'] = '5.9.9.1';
$app['release'] = '1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['summary'] = lang('zarafa_extension_app_summary');
$app['description'] = lang('zarafa_extension_app_long_description');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('zarafa_extension_zarafa_accounts_extension');
$app['category'] = lang('base_category_system');
$app['subcategory'] = 'Accounts Manager'; // FIXME
$app['menu_enabled'] = FALSE;

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['core_only'] = TRUE;

// FIXME 'app-zarafa-core',
$app['core_requires'] = array(
    'app-openldap-directory-core',
    'app-contact-extension-core',
);

$app['core_file_manifest'] = array( 
   'zarafa.php' => array(
        'target' => '/var/clearos/openldap_directory/extensions/10_zarafa.php'
    ),
);
