<?php
/**
 * @author Leon J
 * @since 2017/5/15
 */

$getLogLevel = function ($env) {
    switch ($env) {
        case 'production':
            return 'warning';
        case 'local':
        case 'docker':
            return 'debug';
        default:
            return 'notice';
    }
};

return [
    'maxFiles' => 10,
    'level' => env('log.level', $getLogLevel(env('app.env', 'local'))),
];
