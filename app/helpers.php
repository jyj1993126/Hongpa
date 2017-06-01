<?php
/**
 * @author Leon J
 * @since 2017/4/20
 */

use App\Kernel\Application;
use Illuminate\Support\Str;

/**
 * 以下用于代码提示
 */

if (!function_exists('events')) {
    /**
     * @return \Illuminate\Events\Dispatcher
     */
    function events()
    {
        return app('events');
    }
}

if (!function_exists('logs')) {
    /**
     * @return \Illuminate\Log\Writer
     */
    function logs()
    {
        return app('logs');
    }
}

if (!function_exists('console')) {
    /**
     * @return \Symfony\Component\Console\Application
     */
    function console()
    {
        return app('console');
    }
}

if (!function_exists('mysqlPool')) {
    /**
     * @param string $conn
     * @return \App\Kernel\Database\Pool\Mysql
     */
    function mysqlPool($conn = 'default')
    {
        return app("pool.mysql.{$conn}");
    }
}

if (!function_exists('redisPool')) {
    /**
     * @param string $conn
     * @return \App\Kernel\Database\Pool\Redis
     */
    function redisPool($conn = 'default')
    {
        return app("pool.redis.{$conn}");
    }
}

if (!function_exists('superSerialize')) {
    /**
     * 闭包序列化
     * @param Closure $closure
     * @return String
     */
    function superSerialize(Closure $closure)
    {
        return app('superSerializer')->serialize( $closure );
    }
}

if (!function_exists('superUnserialize')) {
    /**
     * 闭包反序列化
     * @param $string
     * @return stdClass|callable
     */
    function superUnserialize($string)
    {
        return app('superSerializer')->unserialize( $string );
    }
}


if (!function_exists('dispatchTask')) {
    /**
     * 派遣swoole任务
     * @param $executor \App\Kernel\Task\Executor | Closure
     * @return \Illuminate\Http\Response
     */
    function dispatchTask($executor)
    {
        return app('taskDispatcher')->dispatch( $executor );
    }
}

/**
 * 辅助方法
 */

if (!function_exists('app')) {
    /**
     * Get the available container instance.
     *
     * @param  string $abstract
     * @param  array $parameters
     * @return mixed|Application
     */
    function app($abstract = null, array $parameters = [])
    {
        if (is_null($abstract)) {
            return Application::getInstance();
        }
        
        return empty($parameters)
            ? Application::getInstance()->make($abstract)
            : Application::getInstance()->makeWith($abstract, $parameters);
    }
}

if (!function_exists('config')) {
    /**
     * @param null $key
     * @param null $default
     * @return Application|mixed
     */
    function config($key = null, $default = null)
    {
        if (is_null($key)) {
            return app('config');
        }
        
        if (is_array($key)) {
            return app('config')->set($key);
        }
        
        return app('config')->get($key, $default);
    }
}

if (!function_exists('path')) {
    /**
     * @param null $path
     * @return string
     */
    function path($path = null)
    {
        return $path ? BASE_DIR . '/' . $path : BASE_DIR;
    }
}

if (!function_exists('env')) {
    /**
     * Gets the value of an environment variable.
     *
     * @param  string $key
     * @param  mixed $default
     * @return mixed
     */
    function env($key, $default = null)
    {
        $value = getenv($key);
        
        if ($value === false) {
            return value($default);
        }
        
        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return;
        }
        
        if (strlen($value) > 1 && Str::startsWith($value, '"') && Str::endsWith($value, '"')) {
            return substr($value, 1, -1);
        }
        
        return $value;
    }
    
    if (!function_exists('environment')) {
        /**
         * @return bool|string
         */
        function environment()
        {
            if (func_num_args() > 0) {
                $patterns = is_array(func_get_arg(0)) ? func_get_arg(0) : func_get_args();
                
                foreach ($patterns as $pattern) {
                    if (Str::is($pattern, env('app.env'))) {
                        return true;
                    }
                }
                return false;
            }
            
            return env('app.env', 'local');
        }
    }
    
    if (!function_exists('issetOr')) {
        /**
         * @param $value
         * @param $key
         * @param null $or
         * @return null
         */
        function issetOr($value, $key, $or = null)
        {
            return isset($value[$key]) ? $value[$key] : $or;
        }
    }
    
    if (!function_exists('coroVal')) {
        
        /**
         * 用于管理协程内的变量
         * @param $key
         * @param null $default
         * @return null
         *
         * 已定义变量 :
         * - mysql.conn : 当前协程使用的数据库链接 ( 主要用于事务 )
         *
         */
        function coroVal($key = null, $default = null)
        {
            $getKey = function ($key) {
                return implode('.', array_filter(['_var', coroUid(), $key]));
            };
            if (is_array($key)) {
                $key = array_combine(array_map($getKey, array_keys($key)), array_values($key));
            } else {
                $key = $getKey($key);
            }
            $rtn = config($key, $default);
            events()->fire('coroVal', compact('key', 'rtn'));
            return $rtn;
        }
    }
    
    if (!function_exists('dropCoro')) {
        /**
         * 用于管理协程内的变量
         */
        function dropCoro()
        {
            $var = config('_var');
            unset($var[coroUid()]);
            config(['_var' => $var]);
            events()->fire('dropCoro');
        }
    }
    
    if (!function_exists('coroUid')) {
        /**
         * 用于管理协程内的变量
         */
        function coroUid()
        {
            return str_replace('.', '_', \Swoole\Coroutine::getUid());
        }
    }
    
    if (!function_exists('microseconds')) {
        /**
         * 毫秒数
         */
        function microseconds()
        {
            return microtime(true) * 1000;
        }
    }
}
