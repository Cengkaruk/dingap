<?php

$app['basename'] = 'imap';
$app['version'] = '6.0';
$app['release'] = '0.2';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_libraries'] = 'LGPLv3';
$app['description'] = 'The POP amd IMAP servers provide standard messaging... blah blah blah.';  // FIXME

$app['name'] = lang('imap_imap_and_pop_server');
$app['category'] = lang('base_category_server');
$app['subcategory'] = lang('base_subcategory_mail');

$app['forms']['imap']['title'] = $app['name'];
$app['forms']['imap']['tooltip'] = 'Using secure protocols is a good security practice and one that we strongly recommend.'; // FIXME translate
