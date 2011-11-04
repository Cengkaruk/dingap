<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'samba_extension';
$app['version'] = '6.1.0.beta2';
$app['release'] = '1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['summary'] = 'Contact account extension summary';
$app['description'] = 'Contact account extension description ... blah blah blah.'; // FIXME: translate

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('samba_extension_samba_account_extension');
$app['category'] = lang('base_category_system');
$app['subcategory'] = 'Accounts Manager'; // FIXME
$app['menu_enabled'] = FALSE;

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['core_only'] = TRUE;

$app['core_requires'] = array(
    'app-openldap-directory-core',
    'app-samba-core',
);

$app['core_file_manifest'] = array( 
   'samba.php' => array(
        'target' => '/var/clearos/openldap_directory/extensions/20_samba.php'
    ),
);
