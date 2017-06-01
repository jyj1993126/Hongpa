<?php
/**
 * @author Leon J
 * @since 2017/4/20
 */

return [
    'host' => env('swoole.host', environment('docker') ? '0.0.0.0' : '127.0.0.1'),
    'port' => env('swoole.port', 9050),
    'config' => [
        'worker_num' => 2,
        'task_worker_num' => 3,
        'max_coro_num' => 3000,
        'heartbeat_idle_time' => 600,
        'heartbeat_check_interval' => 60,
    ],
    'extra' => [
        'gzip_min_length' => 1024,
        'gzip_level' => 1,
        'use_gzip' => extension_loaded('zlib'),
    ],
];
