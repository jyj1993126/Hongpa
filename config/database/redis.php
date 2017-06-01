<?php
return [
    'default' => [
        'host' => environment('docker')
            ? gethostbyname(env('database.redis.host', 'redis'))
            : env('database.redis.host', '127.0.0.1')
        ,
        'port' => env('database.redis.port', '6379'),
        'maxConnections' => 200,
    ],
];
