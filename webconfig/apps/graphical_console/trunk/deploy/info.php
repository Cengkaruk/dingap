<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'graphical_console';
$app['version'] = '5.9.9.2';
$app['release'] = '1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['summary'] = 'Graphical console tool.';
$app['description'] = 'Graphical console tool for configuring the network.'; // FIXME: translate

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = 'Graphical Console'; // FIXME: translate
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
