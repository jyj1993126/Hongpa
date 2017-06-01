<?php
/**
 * @author Leon J
 * @since 2017/5/25
 */

namespace App\Controller;

use Illuminate\Http\Request;

class RedisController
{
    public function get(Request $request, $key)
    {
        return redisPool()->get($key);
    }
    
    public function set(Request $request, $key, $value)
    {
        return redisPool()->set( $key, $value );
    }
}
