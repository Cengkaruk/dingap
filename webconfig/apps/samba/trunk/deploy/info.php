<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'samba';
$app['version'] = '5.9.9.2';
$app['release'] = '4';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['summary'] = lang('samba_app_summary');
$app['description'] = lang('samba_app_long_description');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = 'Windows Settings';
$app['category'] = lang('base_category_server');
$app['subcategory'] = lang('base_subcategory_file');
$app['menu_enabled'] = FALSE;

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['requires'] = array(
    'app-accounts',
    'app-groups',
    'app-users',
    'app-network',
    'samba >= 3.5.4',
);

$app['core_requires'] = array(
    'app-network-core', 
    'samba-client >= 3.5.4',
    'samba-winbind-clients >= 3.5.4',
);

$app['core_file_manifest'] = array( 
    'smb.ldap.conf' => array( 'target' => '/var/clearos/ldap/synchronize/smb.ldap.conf' ),
    'smb.winbind.conf' => array( 'target' => '/var/clearos/ldap/synchronize/smb.winbind.conf' ),
    'add-samba-directories' => array(
        'target' => '/usr/sbin/add-samba-directories',
        'mode' => '0755',
    ),
);

$app['core_directory_manifest'] = array(
   '/var/clearos/samba' => array(),
);
