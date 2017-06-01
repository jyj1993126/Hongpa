<?php

namespace App\Controller;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * @author Leon J
 * @since 2017/4/20
 */
class IndexController
{
    /**
     * @param Request $request
     * @return mixed
     */
    public function get(Request $request)
    {
        list($rtn, $success) = User::getInstance()->fetchAll();
        
        return $rtn;
    }
    
    /**
     * @param Request $request
     * @param array $names
     * @return array
     */
    public function insert(Request $request, ...$names)
    {
        return User::getInstance()->set($names);
    }
    
    public function close()
    {
        foreach (config('database') as $client => $connections) {
            foreach ($connections as $name => $config) {
                app("pool.{$client}.{$name}")->closeAll();
            }
        }
    }
    
    public function task()
    {
        dispatchTask(function () {
            echo 'huck!', PHP_EOL;
        });
    }
}
