<?php

$info_map = array(
    'certificate' => array(
        'type' => 'string',
        'required' => FALSE,
        'validator' => 'validate_certificate',
        'object_class' => 'clearAccount',
        'attribute' => 'userCertificate'
    ),

    'pkcs12' => array(
        'type' => 'string',
        'required' => FALSE,
        'validator' => 'validate_pkcs12',
        'object_class' => 'clearAccount',
        'attribute' => 'userPKCS12'
    ),
);
