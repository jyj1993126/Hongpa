<?php

/**
 * @author Leon J
 * @since 2017/5/17
 */
class helperTest extends TestCase
{
    public function testEvents()
    {
        $this->assertInstanceOf(\Illuminate\Events\Dispatcher::class, events());
    }
    
    public function testLogs()
    {
        $this->assertInstanceOf(\Illuminate\Log\Writer::class, logs());
    }
    
    public function testApp()
    {
        $this->assertInstanceOf(\App\Kernel\Application::class, app());
        $this->assertInstanceOf(\Illuminate\Config\Repository::class, app('config'));
    }
    
    public function testConfig()
    {
        $this->assertInstanceOf(\Illuminate\Config\Repository::class, config());
        $this->assertInternalType('array', config('swoole.config'));
        $this->assertInternalType('string', config('app.name'));
        $this->assertEquals(123, config('_test', 123));
        
        config(['_test' => 456]);
        $this->assertEquals(456, config('_test', 123));
    }
    
    public function testPath()
    {
        $this->assertEquals(BASE_DIR, path());
        $this->assertEquals(BASE_DIR . '/app', path('app'));
    }
    
    public function testEnv()
    {
        $this->assertNotEmpty(env('swoole.port'));
        $this->assertNotEmpty(env('app.env'));
        $this->assertNotEmpty(env('database.port'));
        $this->assertEquals(123, env('_test', 123));
    }
    
    public function testEnvironment()
    {
        $this->assertInternalType('string', environment());
        $this->assertInternalType('bool', environment('local'));
        $this->assertFalse(environment('production'));
    }
    
    public function testIsserOr()
    {
        $value = ['test' => 123];
        $this->assertEquals(123, issetOr($value, 'test', 456));
        $this->assertEquals(456, issetOr($value, 'test2', 456));
    }
}
