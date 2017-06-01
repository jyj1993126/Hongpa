<?php

namespace App\Models;

use App\Kernel\Database\Model;
use App\Kernel\Traits\Singleton;

/**
 * @author Leon J
 * @since 2017/5/11
 */
class User extends Model
{
    use Singleton;
    
    protected $table = 'users';
    
    public function fetchAll()
    {
        return $this->select()->query();
    }
    
    public function set(array $names)
    {
        $this->begin();
    
        foreach ($names as $name) {
            $this->insert()
                ->setEqual('name', $name)
                ->query();
        }
    
        return $this->commit();
    }
}
