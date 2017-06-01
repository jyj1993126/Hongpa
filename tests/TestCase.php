<?php

define('BASE_DIR', __DIR__ . '/..');

/**
 * @author Leon J
 * @since 2017/5/17
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected function setUp()
    {
        app()->initialize();
        app()->bootstrap();
    }
    
}
