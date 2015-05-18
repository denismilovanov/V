<?php

return [
    'fetch' => PDO::FETCH_CLASS,
    'default' => 'vmeste',
    'connections' => [
        'vmeste' => [
            'driver'   => 'pgsql',
            'host'     => env('DB_VMESTE_HOST', ''),
            'port'     => env('DB_VMESTE_PORT', '5432'),
            'database' => env('DB_VMESTE_DATABASE', ''),
            'username' => env('DB_VMESTE_USERNAME', ''),
            'password' => env('DB_VMESTE_PASSWORD', ''),
            'charset'  => 'utf8',
            'prefix'   => '',
            'schema'   => 'public',
            'options'   => array(
                PDO::ATTR_EMULATE_PREPARES => true,
            ),
        ],
        'logs' => [
            'driver'   => 'pgsql',
            'host'     => env('DB_LOGS_HOST', ''),
            'port'     => env('DB_LOGS_PORT', '5432'),
            'database' => env('DB_LOGS_DATABASE', ''),
            'username' => env('DB_LOGS_USERNAME', ''),
            'password' => env('DB_LOGS_PASSWORD', ''),
            'charset'  => 'utf8',
            'prefix'   => '',
            'schema'   => 'public',
            'options'   => array(
                PDO::ATTR_EMULATE_PREPARES => true,
            ),
        ],
    ],
    'migrations' => 'migrations',

    'images_storage_path' => env('ROOT_PATH', '') . env('PHOTOS_PATH', ''),
];
