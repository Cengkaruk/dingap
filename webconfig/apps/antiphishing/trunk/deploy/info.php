<?php

$app['basename'] = 'antiphishing';
$app['version'] = '6.0';
$app['release'] = '0.2';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_libraries'] = 'LGPLv3';
$app['description'] = 'Antiphishing description...'; // FIXME

$app['name'] = lang('antiphishing_antiphishing');
$app['category'] = lang('base_category_gateway');
$app['subcategory'] = lang('base_subcategory_antimalware');

$app['forms']['antiphishing']['title'] = $app['name'];
$app['forms']['antiphishing']['tooltip'] = 'Even the most savvy Internet users can accidentally click on a phishing link.  We recommend leaving all antiphishing features enabled.'; // FIXME translate
