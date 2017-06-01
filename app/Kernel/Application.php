<?php
/**
 * @author Leon J
 * @since 2017/4/20
 */

namespace App\Kernel;

use App\Contract\Service;
use App\Kernel\Database\Pool\Mysql;
use App\Kernel\Database\Pool\Redis;
use App\Kernel\Exceptions\NotFoundException;
use Dotenv\Dotenv;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Log\Writer;
use Illuminate\Support\Composer;
use Illuminate\Support\Str;
use Monolog\Logger;
use Symfony\Component\Console\Application as consoleApp;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

/**
 * Class Application
 * @package App\Kernel
 */
class Application extends Container
{
    /**
     * initialize application
     * @param null $env
     */
    public function initialize($env = null)
    {
        if ($env && file_exists(path($env))) {
            (new Dotenv(path(), $env))->load();
        }
        
        $this->singleton('config', function () {
            return new Repository($this->loadConfig(path('config')));
        });
        
        $this->singleton('events', function ($app) {
            return (new Dispatcher($app));
        });
        
        $this->singleton('logs', function () {
            $log = new Writer(
                new Logger(env('app.env', 'local')), app('events')
            );
            $log->useDailyFiles(
                path('storage/logs/' . config('app.name', 'hongpa')),
                config('logs.maxFiles', 10),
                config('logs.level', 'notice')
            );
            return $log;
        });
        
        $this->singleton('console', function () {
            return new consoleApp;
        });
        
        $this->singleton('filesystem', function () {
            return new Filesystem;
        });
        
        $this->singleton('composer', function () {
            return new Composer($this->make('filesystem'));
        });
    
        $this->singleton('superSerializer', function () {
            return new \SuperClosure\Serializer();
        });
        
        foreach (config('database.mysql') as $name => $config) {
            $this->singleton("pool.mysql.{$name}", function () use ($name) {
                return new Mysql($name);
            });
        }
    
        foreach (config('database.redis') as $name => $config) {
            $this->singleton("pool.redis.{$name}", function () use ($name) {
                return new Redis($name);
            });
        }
        
        date_default_timezone_set(config('app.timezone', 'Asia/Shanghai'));
    
        error_reporting(E_ALL);
        ini_set('display_errors', environment('production') ? 'Off' : 'On');
        
        register_shutdown_function(function () {
            if ($err = error_get_last()) {
                logs()->error('onShutdown', $err);
            }
        });
    }
    
    /**
     * @param $path
     * @return array
     */
    private function loadConfig($path)
    {
        $rtn = [];
        foreach (new \DirectoryIterator($path) as $file) {
            if ($file->isDot()) {
                continue;
            } elseif ($file->isDir()) {
                $rtn[$file->getFilename()] = $this->loadConfig($file->getRealPath());
            } elseif ($file->isFile()) {
                $extLength = strlen($file->getExtension()) + 1;
                $name = substr($file->getFilename(), 0, strlen($file->getFilename()) - $extLength);
                $rtn[$name] = include $file->getRealPath();
            }
        }
        return $rtn;
    }
    
    /**
     * @return bool|string
     */
    public function environment()
    {
        return environment(...func_get_args());
    }
    
    /**
     * Boot application
     */
    public function bootstrap()
    {
        foreach (array_filter(config('services', [])) as $serviceName) {
            if (($service = app($serviceName)) instanceof Service) {
                $service->boot($this);
            } else {
                trigger_error($message = $serviceName . ' is not a Service implementation !', E_USER_WARNING);
                logs()->warning($message);
            }
        }
        
        foreach (array_filter(config('commands', [])) as $commandName) {
            if (($command = app($commandName)) instanceof Command) {
                app('console')->add($command);
            } else {
                trigger_error($message = $commandName . ' is not a Command implementation !', E_USER_WARNING);
                logs()->warning($message);
            }
        }
        
        foreach (array_filter(config('events', [])) as $event) {
            events()->listen($event['events'], $event['listener']);
        }
    }
    
    /**
     * @return string
     */
    public function databasePath()
    {
        return path('database');
    }
    
    /**
     * @param Request $request
     * @return Response
     */
    public function dispatch($request)
    {
        $response = new Response;
        try {
            $functions = array_map('urldecode', array_filter(explode('/', $request->getPathInfo()), 'strlen'));
            $controllerName = config('app.namespace.controller', 'App\\Controller\\') .
                ucfirst(Str::camel(array_shift($functions))) .
                'Controller';
            $method = Str::camel(array_shift($functions));
            if (class_exists($controllerName) && method_exists($controller = new $controllerName, $method)) {
                $data = $controller->$method($request, ...$functions);
                if ($data instanceof BaseResponse) {
                    $response = $data;
                } else {
                    $response->setContent($this->normalizeReturn($data));
                }
                return $response;
            } else {
                throw new NotFoundException;
            }
        } catch (\Exception $e) {
            $response->setContent($this->normalizeReturn([], $e->getMessage(), $e->getCode()));
            switch (get_class($e)) {
                case NotFoundException::class:
                    $response->setStatusCode(404);
                    logs()->warning($e->getMessage(), [
                        'uri' => $request->getUri(),
                    ]);
                    break;
                default:
                    $response->setStatusCode(502);
                    logs()->error($e->getMessage(), [
                        'uri' => $request->getUri(),
                        'code' => $e->getCode(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    return $response;
            }
            return $response;
        }
    }
    
    /**
     * @param $data
     * @param string $message
     * @param int $code
     * @return string
     */
    private function normalizeReturn($data, $message = '', $code = 0)
    {
        $normalizer = config('response.normalizer');
        return $normalizer($data, $message, $code);
    }
}
