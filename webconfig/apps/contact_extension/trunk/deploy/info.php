<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'contact_extension';
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

$app['name'] = lang('contact_extension_contact_account_extension');
$app['category'] = lang('base_category_server');
$app['subcategory'] = lang('base_subcategory_directory');
$app['menu_enabled'] = FALSE;

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['core_only'] = TRUE;

$app['requires'] = array(
    'app-accounts', 
);

$app['core_requires'] = array(
    'app-accounts-core', 
    'app-openldap-directory-core',
);

$app['core_file_manifest'] = array( 
   'contact.php' => array(
        'target' => '/var/clearos/openldap_directory/extensions/contact.php'
    ),
);
