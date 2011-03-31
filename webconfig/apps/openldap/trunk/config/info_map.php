<?php

$info_map = array(
    'city' => array(
        'type' => 'string',
        'required' => FALSE,
        'validator' => 'validate_city',
        'object_class' => 'clearAccount',
        'attribute' => 'l' 
    ),

    'certificate' => array(
        'type' => 'string',
        'required' => FALSE,
        'validator' => 'validate_certificate',
        'object_class' => 'clearAccount',
        'attribute' => 'userCertificate'
    ),

    'country' => array(
        'type' => 'string',
        'required' => FALSE,
        'validator' => 'validate_country',
        'object_class' => 'clearAccount',
        'attribute' => 'c'
    ),

    'description' => array(
        'type' => 'string',
        'required' => FALSE,
        'validator' => 'validate_description',
        'object_class' => 'clearAccount',
        'attribute' => 'description'
    ),

    'display_name' => array(
        'type' => 'string',
        'required' => FALSE,
        'validator' => 'validate_display_name',
        'object_class' => 'clearAccount',
        'attribute' => 'displayName' 
    ),

    'fax' => array(
        'type' => 'string',
        'required' => FALSE,
        'validator' => 'validate_fax_number',
        'object_class' => 'clearAccount',
        'attribute' => 'facsimileTelephoneNumber' 
    ),

    'first_name' => array(
        'type' => 'string',
        'required' => FALSE,
        'validator' => 'validate_first_name',
        'object_class' => 'clearAccount',
        'attribute' => 'givenName'
    ),

    'gid_number' => array(
        'type' => 'integer',
        'required' => FALSE,
        'validator' => 'validate_gid_number',
        'object_class' => 'clearAccount',
        'attribute' => 'gidNumber'
    ),

    'home_directory' => array(
        'type' => 'string',
        'required' => FALSE,
        'validator' => 'validate_home_directory',
        'object_class' => 'clearAccount',
        'attribute' => 'homeDirectory'
    ),

    'last_name' => array(
        'type' => 'string',
        'required' => TRUE,
        'validator' => 'validate_last_name',
        'object_class' => 'clearAccount',
        'attribute' => 'sn',
        'locale' => lang('directory_last_name')
    ),

    'login_shell' => array(
        'type' => 'string',
        'required' => FALSE,
        'validator' => 'validate_login_shell',
        'object_class' => 'clearAccount',
        'attribute' => 'loginShell'
    ),

    'mail' => array(
        'type' => 'string',
        'required' => FALSE,
        'validator' => 'validate_mail',
        'object_class' => 'clearAccount',
        'attribute' => 'mail'
    ),

    'mobile' => array(
        'type' => 'string',
        'required' => FALSE,
        'validator' => 'validate_mobile',
        'object_class' => 'clearAccount',
        'attribute' => 'mobile'
    ),

    'organization' => array(
        'type' => 'string',
        'required' => FALSE,
        'validator' => 'validate_organization',
        'object_class' => 'clearAccount',
        'attribute' => 'o'
    ),

    'password' => array(
        'type' => 'string',
        'required' => FALSE,
        'validator' => 'validate_password',
        'object_class' => 'clearAccount',
        'attribute' => 'userPassword',
        'locale' => lang('base_password')
    ),

    'pkcs12' => array(
        'type' => 'string',
        'required' => FALSE,
        'validator' => 'validate_pkcs12',
        'object_class' => 'clearAccount',
        'attribute' => 'userPKCS12'
    ),

    'postal_code' => array(
        'type' => 'string',
        'required' => FALSE,
        'validator' => 'validate_postal_code',
        'object_class' => 'clearAccount',
        'attribute' => 'postalCode'
    ),

    'post_office_box' => array(
        'type' => 'string',
        'required' => FALSE,
        'validator' => 'validate_post_office_box',
        'object_class' => 'clearAccount',
        'attribute' => 'postOfficeBox'
    ),

    'region' => array(
        'type' => 'string',
        'required' => FALSE,
        'validator' => 'validate_region',
        'object_class' => 'clearAccount',
        'attribute' => 'st'
    ),

    'room_number' => array(
        'type' => 'string',
        'required' => FALSE,
        'validator' => 'validate_room_number',
        'object_class' => 'clearAccount',
        'attribute' => 'roomNumber'
    ),

    'street' => array(
        'type' => 'string',
        'required' => FALSE,
        'validator' => 'validate_street',
        'object_class' => 'clearAccount',
        'attribute' => 'street'
    ),

    'telephone' => array(
        'type' => 'string',
        'required' => FALSE,
        'validator' => 'validate_telephone_number',
        'object_class' => 'clearAccount',
        'attribute' => 'telephoneNumber'
    ),

    'title' => array(
        'type' => 'string',
        'required' => FALSE,
        'validator' => 'validate_title',
        'object_class' => 'clearAccount',
        'attribute' => 'title'
    ),

    'uid' => array(
        'type' => 'integer',
        'required' => FALSE,
        'validator' => 'IsValidUsername',
        'object_class' => 'clearAccount',
        'attribute' => 'uid'
    ),

    'uid_number' => array(
        'type' => 'integer',
        'required' => FALSE,
        'validator' => 'IsValidUidNumber',
        'object_class' => 'clearAccount',
        'attribute' => 'uidNumber'
    ),

    'unit' => array(
        'type' => 'string',
        'required' => FALSE,
        'validator' => 'validate_organization_unit',
        'object_class' => 'clearAccount',
        'attribute' => 'ou'
    ),
);

/*
    'aliases'        => array( 'type' => 'stringarray',  'required' => FALSE, 'validator' => 'IsValidAlias', 'object_class' => 'pcnMailAccount', 'attribute' => 'pcnMailAliases' ),
    'forwarders'    => array( 'type' => 'stringarray',  'required' => FALSE, 'validator' => 'IsValidForwarder', 'object_class' => 'pcnMailAccount', 'attribute' => 'pcnMailForwarders' ),
    'ftpFlag'        => array( 'type' => 'boolean', 'required' => FALSE, 'validator' => 'IsValidFlag', 'object_class' => 'pcnFTPAccount', 'attribute' => 'pcnFTPFlag' , 'passwordfield' => 'pcnFTPPassword', 'passwordtype' => User::PASSWORD_TYPE_SHA ),
    'mailFlag'        => array( 'type' => 'boolean', 'required' => FALSE, 'validator' => 'IsValidFlag', 'object_class' => 'pcnMailAccount', 'attribute' => 'pcnMailFlag' , 'passwordfield' => 'pcnMailPassword', 'passwordtype' => User::PASSWORD_TYPE_SHA ),
    'googleAppsFlag'    => array( 'type' => 'boolean', 'required' => FALSE, 'validator' => 'IsValidFlag', 'object_class' => 'pcnGoogleAppsAccount', 'attribute' => 'pcnGoogleAppsFlag' , 'passwordfield' => 'pcnGoogleAppsPassword', 'passwordtype' => User::PASSWORD_TYPE_SHA1 ),
    'openvpnFlag'    => array( 'type' => 'boolean', 'required' => FALSE, 'validator' => 'IsValidFlag', 'object_class' => 'pcnOpenVPNAccount', 'attribute' => 'pcnOpenVPNFlag' , 'passwordfield' => 'pcnOpenVPNPassword', 'passwordtype' => User::PASSWORD_TYPE_SHA ),
    'pptpFlag'        => array( 'type' => 'boolean', 'required' => FALSE, 'validator' => 'IsValidFlag', 'object_class' => 'pcnPPTPAccount', 'attribute' => 'pcnPPTPFlag' , 'passwordfield' => 'pcnPPTPPassword', 'passwordtype' => User::PASSWORD_TYPE_NT ),
    'proxyFlag'        => array( 'type' => 'boolean', 'required' => FALSE, 'validator' => 'IsValidFlag', 'object_class' => 'pcnProxyAccount', 'attribute' => 'pcnProxyFlag' , 'passwordfield' => 'pcnProxyPassword', 'passwordtype' => User::PASSWORD_TYPE_SHA ),
    'webconfigFlag'    => array( 'type' => 'boolean', 'required' => FALSE, 'validator' => 'IsValidFlag', 'object_class' => 'pcnWebconfigAccount', 'attribute' => 'pcnWebconfigFlag' , 'passwordfield' => 'pcnWebconfigPassword', 'passwordtype' => User::PASSWORD_TYPE_SHA ),
    'webFlag'        => array( 'type' => 'boolean', 'required' => FALSE, 'validator' => 'IsValidFlag', 'object_class' => 'pcnWebAccount', 'attribute' => 'pcnWebFlag' , 'passwordfield' => 'pcnWebPassword', 'passwordtype' => User::PASSWORD_TYPE_SHA ),
*/
