<?php
/**
 * @author Leon J
 * @since 2017/5/12
 */

namespace App\Kernel\Exceptions;

use Exception;

class NotFoundException extends \Exception
{
    public function __construct($message = "404 Not Found", $code = 404, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
