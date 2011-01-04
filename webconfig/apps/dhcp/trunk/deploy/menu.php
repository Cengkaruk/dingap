<?php

clearos_load_language('date');

$menu['/app/date']['category'] = lang('base_system');
$menu['/app/date']['subcategory'] = lang('base_settings');
$menu['/app/date']['title'] = lang('date_date');

// FIXME - testing
$menu['/app/two']['category'] = lang('base_directory');
$menu['/app/two']['subcategory'] = 'Subcategory';
$menu['/app/two']['title'] = 'Test2';

$menu['/app/one']['category'] = lang('base_directory');
$menu['/app/one']['subcategory'] = 'Subcategory';
$menu['/app/one']['title'] = 'Test1';

// FIXME - translate
$menu['/app/dhcp']['category'] = 'Network';
$menu['/app/dhcp']['subcategory'] = 'Infrastructure';
$menu['/app/dhcp']['title'] = 'DHCP Server';

$menu['/app/dns']['category'] = 'Network';
$menu['/app/dns']['subcategory'] = 'Infrastructure';
$menu['/app/dns']['title'] = 'Local DNS Server';

// vim: ts=4 syntax=php
