<?php

namespace App\Kernel\Traits;

/**
 * @author Leon J
 * @since 2016/11/3
 */
trait Singleton
{
    /**
     * @var static
     */
    private static $instance = null;

    /**
     * @return static
     */
    final public static function instance()
    {
        return static::singleton();
    }

    final public static function singleton()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * @return static
     */
    final public static function getInstance()
    {
        return static::singleton();
    }

    final public static function swap($instance)
    {
        static::$instance = $instance;
    }
}
