<?php

/*
|--------------------------------------------------------------------------
| Database
|--------------------------------------------------------------------------
*/
$config['db'] = [
    'default' => [
        'development' => [
            'driver' => 'mysql',
            'hostname' => 'localhost',
            'username' => 'root',
            'password' => '',
            'database' => 'plant_db',
            'port' => '3306',
            'charset' => 'utf8mb4',
        ],
        'staging' => [
            'driver' => 'mysql',
            'hostname' => 'localhost',
            'username' => 'root',
            'password' => '',
            'database' => '',
            'port' => '3306',
            'charset' => 'utf8mb4',
        ],
        'production' => [
            'driver' => 'mysql',
            'hostname' => '',
            'username' => 'root',
            'password' => '',
            'database' => '',
            'port' => '3306',
            'charset' => 'utf8mb4',
        ]
    ],

    'slave' => [
        'development' => [
            'driver' => 'mysql',
            'hostname' => 'localhost',
            'username' => 'root',
            'password' => '',
            'database' => 'schoolscan',
            'port' => '3306',
            'charset' => 'utf8mb4',
        ],
        'staging' => [
            'driver' => 'mysql',
            'hostname' => 'localhost',
            'username' => 'root',
            'password' => '',
            'database' => '',
            'port' => '3306',
            'charset' => 'utf8mb4',
        ],
        'production' => [
            'driver' => 'mysql',
            'hostname' => '',
            'username' => 'root',
            'password' => '',
            'database' => '',
            'port' => '3306',
            'charset' => 'utf8mb4',
        ]
    ]
];