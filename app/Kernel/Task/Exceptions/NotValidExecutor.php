<?php

namespace App\Kernel\Task\Exceptions;

use Exception;

/**
 * @author Leon J
 * @since 2017/6/1
 */
class NotValidExecutor extends \Exception
{
    public function __construct($message = "exector 必须是exector实例或者闭包", $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
