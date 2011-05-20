<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'openldap';
$app['version'] = '5.9.9.0';
$app['release'] = '1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['summary'] = 'OpenLDAP Directory';
$app['description'] = 'OpenLDAP Directory...blah blah blah'; // FIXME: translate

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = 'OpenLDAP Driver'; // FIXME
$app['category'] = lang('base_category_server');
$app['subcategory'] = lang('base_subcategory_directory');
$app['menu_enabled'] = FALSE;

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['core_provides'] = array(
    'system-ldap-driver'
);

$app['core_requires'] = array(
    'app-network-core',
    'openldap-servers >= 2.4.19',
    'openssl',
);

$app['core_directory_manifest'] = array(
   '/var/clearos/openldap' => array(),
   '/var/clearos/openldap/provision' => array(),
   '/var/clearos/openldap/synchronize' => array(),
);

$app['core_file_manifest'] = array(
    'schema/clearfoundation.schema' => array( 'target' => '/etc/openldap/schema/clearfoundation.schema' ),
    'schema/clearcenter.schema' => array( 'target' => '/etc/openldap/schema/clearcenter.schema' ),
    'schema/horde.schema' => array( 'target' => '/etc/openldap/schema/horde.schema' ),
    'schema/kolab2.schema' => array( 'target' => '/etc/openldap/schema/kolab2.schema' ),
    'schema/pcn.schema' => array( 'target' => '/etc/openldap/schema/pcn.schema' ),
    'schema/RADIUS-LDAPv3.schema' => array( 'target' => '/etc/openldap/schema/RADIUS-LDAPv3.schema' ),
    'schema/rfc2307bis.schema' => array( 'target' => '/etc/openldap/schema/rfc2307bis.schema' ),
    'schema/rfc2739.schema' => array( 'target' => '/etc/openldap/schema/rfc2739.schema' ),
    'schema/samba.schema' => array( 'target' => '/etc/openldap/schema/samba.schema' ),
    'schema/zarafa.schema' => array( 'target' => '/etc/openldap/schema/zarafa.schema' ),
);
