<?php

/**
 * @author Leon J
 * @since 2017/4/20
 */
namespace App\Command;

use App\Kernel\Application;
use App\Kernel\Network\SwooleRequestConvertor;
use App\Kernel\Task\Dispatcher;
use App\Kernel\Task\Executor;
use Illuminate\Http\Response;
use PhpParser\Node\Expr\Closure;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Class SwooleHttpServer
 * @package App\Command
 */
class SwooleHttpServer extends Command
{
    /**
     * @var
     */
    protected $server;
    
    /**
     * @var Application|mixed
     */
    protected $host;
    
    /**
     * @var Application|mixed
     */
    protected $port;
    
    /**
     * SwooleHttpServer constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->host = config('swoole.host', '127.0.0.1');
        $this->port = config('swoole.port', '9050');
    }
    
    /**
     * @param $swooleHttpRequest
     * @param $swooleHttpResponse
     */
    public function onRequest($swooleHttpRequest, $swooleHttpResponse)
    {
        global $request;
        
        $request = SwooleRequestConvertor::convert($swooleHttpRequest);
        
        events()->fire('swoole.request.start', [$request]);
        
        $response = app()->dispatch($request);
        
        events()->fire('swoole.request.end', [$response]);
        
        $this->handleResponse(
            $swooleHttpResponse, $response,
            issetOr($swooleHttpRequest->header, 'Accept-Encoding', '')
        );
        
        dropCoro();
    }
    
    /**
     * @param $server
     */
    public function onServerStart($server)
    {
        cli_set_process_title(config('app.name') . ': master');
    }
    
    /**
     * @param $server
     */
    public function onManagerStart($server)
    {
        cli_set_process_title(config('app.name') . ': manager');
    }
    
    /**
     * @param $server
     */
    public function onWorkerStart($server)
    {
        cli_set_process_title(config('app.name') . $server->taskworker ? ': task' : ': worker');
        app()->singleton('taskDispatcher', function () use ($server) {
            return new Dispatcher($server);
        });
    }
    
    /**
     * @param $server
     */
    public function onWorkerStop($server)
    {
        foreach (config('database') as $client => $connections) {
            foreach ($connections as $name => $config) {
                app("pool.{$client}.{$name}")->closeAll();
            }
        }
    }
    
    /**
     * @param $server
     * @param $task_id
     * @param $from_id
     * @param $data
     */
    public function onTask($server, $task_id, $from_id, $data)
    {
        switch ($data['type']) {
            case Dispatcher::TYPE_EXECUTOR:
                $executor = serialize($data['exec']);
                if ($executor instanceof Executor) {
                    $executor->run();
                }
                break;
            case Dispatcher::TYPE_CLOSURE:
                $executor = superUnserialize($data['exec']);
                if ($executor instanceof \Closure) {
                    echo $executor();
                }
                break;
        }
    }
    
    /**
     * @param $server
     * @param $task_id
     * @param $data
     */
    public function onFinish($server, $task_id, $data)
    {
    }
    
    /**
     * @param $server
     * @param $workerId
     * @param $workerPid
     * @param $exitCode
     * @param $signal
     */
    public function onWorkerError($server, $workerId, $workerPid, $exitCode, $signal)
    {
        $error = error_get_last();
        logs()->error('worker abnormal exit', compact('workerId', 'workerPid', 'exitCode', 'signal', 'error'));
    }
    
    /**
     * @param \swoole_http_response $response
     * @param Response $illuminateResponse
     * @param string $accept_encoding
     */
    protected function handleResponse(
        \swoole_http_response $response,
        Response $illuminateResponse,
        $accept_encoding = ''
    ) {
        $accept_gzip = config('swoole.extra.use_gzip', false) && stripos($accept_encoding, 'gzip') !== false;
        
        // status
        $response->status($illuminateResponse->getStatusCode());
        foreach ($illuminateResponse->headers->allPreserveCase() as $name => $values) {
            foreach ($values as $value) {
                $response->header($name, $value);
            }
        }
        // cookies
        foreach ($illuminateResponse->headers->getCookies() as $cookie) {
            $response->rawcookie(
                $cookie->getName(),
                urlencode($cookie->getValue()),
                $cookie->getExpiresTime(),
                $cookie->getPath(),
                $cookie->getDomain(),
                $cookie->isSecure(),
                $cookie->isHttpOnly()
            );
        }
        // content
        if ($illuminateResponse instanceof BinaryFileResponse) {
            $content = function () use ($illuminateResponse) {
                return $illuminateResponse->getFile()->getPathname();
            };
            if ($accept_gzip && isset($response->header['Content-Type'])) {
                $size = $illuminateResponse->getFile()->getSize();
            }
        } else {
            $content = $illuminateResponse->getContent();
            //check gzip
            if ($accept_gzip && isset($response->header['Content-Type'])) {
                $mime = $response->header['Content-Type'];
                
                if (strlen($content) > config('swoole.extra.gzip_min_length', 1024) && $this->isMimeGzip($mime)) {
                    $response->gzip(config('swoole.extra.gzip_level', 1));
                }
            }
        }
        $this->endResponse($response, $content);
    }
    
    /**
     * @param $mime
     * @return bool
     */
    protected function isMimeGzip($mime)
    {
        static $mimes = [
            'text/plain' => true,
            'text/html' => true,
            'text/css' => true,
            'application/javascript' => true,
            'application/json' => true,
            'application/xml' => true,
        ];
        if ($pos = strpos($mime, ';')) {
            $mime = substr($mime, 0, $pos);
        }
        return isset($mimes[strtolower($mime)]);
    }
    
    /**
     * @param \swoole_http_response $response
     * @param $content
     */
    protected function endResponse(\swoole_http_response $response, $content)
    {
        if (!is_string($content)) {
            $response->sendfile(realpath($content()));
        } else {
            // send content & close
            $response->end($content);
        }
    }
    
    /**
     * 配置
     */
    protected function configure()
    {
        $this
            ->setName('swoole:serve')
            ->setDescription('run swoole http server')
            ->setHelp('This command allows you to run swoole http server...');
    }
    
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initServer();
        $this->server->on('request', [$this, 'onRequest']);
        $this->server->on('start', [$this, 'onServerStart']);
        $this->server->on('managerStart', [$this, 'onManagerStart']);
        $this->server->on('workerStart', [$this, 'onWorkerStart']);
        $this->server->on('workerStop', [$this, 'onWorkerStop']);
        $this->server->on('workerError', [$this, 'onWorkerError']);
        $this->server->on('task', [$this, 'onTask']);
        $this->server->on('finish', [$this, 'onFinish']);
        $output->writeln("Swoole is running at http://{$this->host}:{$this->port}");
        $this->server->start();
    }
    
    /**
     * 初始化
     */
    protected function initServer()
    {
        $this->server = new \swoole_http_server($this->host, $this->port);
        $this->server->set(config('swoole.config', []));
    }
}
