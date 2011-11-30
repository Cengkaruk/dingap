<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'graphical_console';
$app['version'] = '6.1.0.beta2';
$app['release'] = '1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['description'] = lang('graphical_console_app_description');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('graphical_console_app_name');
$app['category'] = lang('base_category_system');
$app['subcategory'] = lang('base_subcategory_settings');
$app['menu_enabled'] = FALSE;

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////
//    'mesa-dri-drivers',

$app['core_requires'] = array(
    'clearos-console',
    'dbus-x11',
    'gconsole',
    'ratpoison',
    'urw-fonts',
    'xorg-x11-drv-vesa',
    'xorg-x11-server-Xorg',
    'xorg-x11-xinit',
);

$app['core_file_manifest'] = array( 
   'graphical_console' => array(
        'target' => '/var/clearos/base/access_control/public/graphical_console',
        'mode' => '0644',
        'owner' => 'root',
        'group' => 'root',
    ),
   'hushlogin' => array(
        'target' => '/var/lib/clearconsole/.hushlogin',
        'mode' => '0644',
        'owner' => 'root',
        'group' => 'root',
    ),
   'xinitrc' => array(
        'target' => '/var/lib/clearconsole/.xinitrc',
        'mode' => '0644',
        'owner' => 'root',
        'group' => 'root',
    ),
   'Xdefaults' => array(
        'target' => '/var/lib/clearconsole/.Xdefaults',
        'mode' => '0644',
        'owner' => 'root',
        'group' => 'root',
    ),
);
