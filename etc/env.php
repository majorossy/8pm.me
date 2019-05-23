<?php
return [
    'backend' => [
        'frontName' => 'admin_1rvlfm'
    ],
    'crypt' => [
        'key' => 'e8b7081780d3b5351045ed7a56b66026'
    ],
    'db' => [
        'table_prefix' => '',
        'connection' => [
            'default' => [
                'host' => 'eight-pm-db.cqlkbrm7beii.us-east-1.rds.amazonaws.com',
                'dbname' => 'newmagento2',
                'username' => 'major',
                'password' => 'major420',
                'active' => '1'
            ]
        ]
    ],
    'resource' => [
        'default_setup' => [
            'connection' => 'default'
        ]
    ],
    'x-frame-options' => 'SAMEORIGIN',
    'MAGE_MODE' => 'default',
    'session' => [
        'save' => 'files'
    ],
    'cache' => [
        'frontend' => [
            'default' => [
                'id_prefix' => '75f_'
            ],
            'page_cache' => [
                'id_prefix' => '75f_'
            ]
        ]
    ],
    'cache_types' => [
        'config' => 1,
        'layout' => 1,
        'block_html' => 1,
        'collections' => 1,
        'reflection' => 1,
        'db_ddl' => 1,
        'compiled_config' => 1,
        'eav' => 1,
        'customer_notification' => 1,
        'config_integration' => 1,
        'config_integration_api' => 1,
        'full_page' => 1,
        'config_webservice' => 1,
        'translate' => 1,
        'vertex' => 1
    ],
    'install' => [
        'date' => 'Thu, 16 May 2019 22:52:08 +0000'
    ]
];
