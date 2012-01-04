<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'samba';
$app['version'] = '6.2.0.beta3';
$app['release'] = '1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['description'] = lang('samba_app_description');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('samba_app_name');
$app['category'] = lang('base_category_server');
$app['subcategory'] = lang('base_subcategory_file');

/////////////////////////////////////////////////////////////////////////////
// Controllers
/////////////////////////////////////////////////////////////////////////////

$app['controllers']['samba']['title'] = $app['name'];
$app['controllers']['mode']['title'] = lang('samba_mode');
// $app['controllers']['settings']['title'] = lang('base_settings');

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['requires'] = array(
    'app-accounts',
    'app-groups',
    'app-users',
    'app-network',
    'samba >= 3.6.1',
);

$app['core_requires'] = array(
    'app-network-core', 
    'app-openldap-directory-core', 
    'samba-client >= 3.6.1',
    'samba-winbind >= 3.6.1',
    'tdb-tools >= 1.2.9'
);

$app['core_file_manifest'] = array( 
    'smb.ldap.conf' => array( 'target' => '/var/clearos/ldap/synchronize/smb.ldap.conf' ),
    'smb.winbind.conf' => array( 'target' => '/var/clearos/ldap/synchronize/smb.winbind.conf' ),
    'add-samba-directories' => array(
        'target' => '/usr/sbin/add-samba-directories',
        'mode' => '0755',
    ),
    'add-windows-group-info' => array(
        'target' => '/usr/sbin/add-windows-group-info',
        'mode' => '0755',
    ),
    'samba-add-machine' => array(
        'target' => '/usr/sbin/samba-add-machine',
        'mode' => '0755',
    ),
    'samba-init' => array(
        'target' => '/usr/sbin/samba-init',
        'mode' => '0755',
    ),
    'nmb.php'=> array('target' => '/var/clearos/base/daemon/nmb.php'),
    'smb.php'=> array('target' => '/var/clearos/base/daemon/smb.php'),
    'winbind.php'=> array('target' => '/var/clearos/base/daemon/winbind.php'),
);

$app['core_directory_manifest'] = array(
   '/var/clearos/samba' => array(),
   '/var/clearos/samba/backup' => array(),
);
