<?php

namespace App\Traits;

/**
 * @author Leon J
 * @since 2016/11/3
 */
trait NewInstance
{
    public static function newInstance()
    {
        return new static;
    }
}
