<?php

namespace App\Kernel\Task;

use App\Kernel\Task\Exceptions\NotValidExecutor;

/**
 * @author Leon J
 * @since 2017/6/1
 */
class Dispatcher
{
    protected $swooleServer;
    
    const TYPE_CLOSURE = 1;
    
    const TYPE_EXECUTOR = 2;
    
    /**
     * Dispatcher constructor.
     * @param $swooleServer
     */
    public function __construct($swooleServer)
    {
        $this->swooleServer = $swooleServer;
    }
    
    public function dispatch($executor)
    {
        if ($executor instanceof Executor) {
            $this->swooleServer->task([
                'type' => self::TYPE_EXECUTOR,
                'exec' => serialize($executor),
            ]);
        } elseif ($executor instanceof \Closure) {
            $this->swooleServer->task([
                'type' => self::TYPE_CLOSURE,
                'exec' => superSerialize( $executor ),
            ]);
        } else {
            throw new NotValidExecutor();
        }
    }
}
