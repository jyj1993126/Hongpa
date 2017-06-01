<?php
/**
 * @author Leon J
 * @since 2017/5/15
 *
 * Log :
 * - \Illuminate\Log\Events\MessageLogged       ( \Illuminate\Log\Events\MessageLogged $log )
 *
 * Functions :
 * - coroVal
 * - dropCoro
 *
 * Swoole :
 * - swoole.request.start                       ( \Illuminate\Http\Request $request )
 * - swoole.request.end                         ( \Illuminate\Http\Response $response )
 *
 * Pool :
 * - {client}.{conn}.{operation}.start          ( \Swoole\Coroutine\MySQL $conn, ...$args )
 * - {client}.{conn}.{operation}.end            ( \Swoole\Coroutine\MySQL $conn, mixed $return )
 * - {client}.{conn}.conn.*                     ( \Swoole\Coroutine\MySQL $conn )
 *
 */
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Log\Events\MessageLogged;

return [
    [
        'events' => [MessageLogged::class],
        'listener' => function (MessageLogged $log) {
        },
    ],
    /**
     * Functions
     */
    [
        'events' => 'coroVal',
        'listener' => function ($key, $rtn) {
            $cuid = coroUid();
            logs()->debug('coroVal', compact('cuid', 'key', 'rtn'));
        },
    ],
    [
        'events' => 'dropCoro',
        'listener' => function () {
            $cuid = coroUid();
            $var = config('_var');
            logs()->debug('dropCoro', compact('cuid', 'var'));
        },
    ],
    /**
     * Request
     */
    [
        'events' => 'swoole.request.start',
        'listener' => function (Request $request) {
            coroVal(['request.start.time' => $time = microseconds()]);
            logs()->info(
                'swoole.request.start',
                [
                    'cuid' => coroUid(),
                    'time' => $time,
                    'pool.mysql' => [
                        'total' => mysqlPool()->length(),
                        'free' => mysqlPool()->freeLength()
                    ],
                    'pool.redis' => [
                        'total' => redisPool()->length(),
                        'free' => redisPool()->freeLength()
                    ],
                ]
            );
        },
    ],
    [
        'events' => 'swoole.request.end',
        'listener' => function (Response $response) {
            $time = microseconds();
            logs()->info(
                'swoole.request.end',
                [
                    'cuid' => coroUid(),
                    'time' => $time,
                    'duration' => $time - coroVal('request.start.time', $time),
                    'pool.mysql' => [
                        'total' => mysqlPool()->length(),
                        'free' => mysqlPool()->freeLength()
                    ],
                    'pool.redis' => [
                        'total' => redisPool()->length(),
                        'free' => redisPool()->freeLength()
                    ],
                ]
            );
        },
    ],
    /**
     * Pool
     */
    [
        'events' => ['*.*.*.start', '*.*.*.end'],
        'listener' => function ($event, $params) {
            list($conn, $extra) = $params;
            list($client, $connName, $operation, $state) = explode('.', $event);
            
            $connAttrs = [];
            
            if ($client == 'redis') {
                $connAttrs = ['errCode', 'errMsg'];
            } elseif ($client == 'mysql') {
                $connAttrs = $state == 'start'
                    ? ['sock', 'connected', 'connect_error', 'connect_errno']
                    : ['sock', 'affected_rows', 'insert_id', 'error', 'errno'];
            }
    
            $context['time'] = microseconds();
            $context['conn'] = array_intersect_key((array)$conn, array_flip($connAttrs));
            
            if ($state == 'start') {
                $context['args'] = (array)$extra;
                coroVal(["{$client}.{$connName}.{$operation}.start.time" => $context['time']]);
            } else {
                $context['duration'] = $context['time'] -
                    coroVal("{$client}.{$connName}.{$operation}.start.time", $context['time']);
                $context['rtn'] = (array)$extra;
            }
            
            logs()->debug($event, $context);
        },
    ],
    [
        'events' => '*.*.conn.*',
        'listener' => function ($event, $params) {
            list($conn) = $params;
            list($client, $connName,, $operation ) = explode('.', $event);
            
            $connAttrs = [];
    
            if ($client == 'redis') {
                $connAttrs = ['errCode', 'errMsg'];
            } elseif ($client == 'mysql') {
                $connAttrs = ['sock', 'connected', 'connect_error', 'connect_errno'];
            }
    
            $context['time'] = microseconds();
            $context['conn'] = array_intersect_key((array)$conn, array_flip($connAttrs));
            
            logs()->debug($event, $context);
        },
    ],
];
