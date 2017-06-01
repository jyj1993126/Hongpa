<?php
return [
    'default' => [
        'driver' => 'mysql',
        'host' => environment('docker')
            ? gethostbyname(env('database.mysql.host', 'mysql'))
            : env('database.mysql.host', '127.0.0.1')
        ,
        'port' => env('database.mysql.port', '3306'),
        'username' => 'root',
        'user' => 'root',
        'password' => 'root',
        'prefix' => '',
        'database' => 'hongpa',
        'maxConnections' => 200,
    ],
];
