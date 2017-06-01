<?php
/**
 * @author Leon J
 * @since 2017/5/11
 */

namespace App\Kernel\Database\Pool;

/**
 * Class Mysql
 * @package App\Kernel\Database\Pool
 * @method query($sql)
 */
class Mysql extends Base
{
    protected $client = 'mysql';
    
    /**
     * @return array
     */
    protected function makeConn()
    {
        $conn = new \Swoole\Coroutine\MySQL();
        $connResult = $conn->connect($this->getConfig());
        return array($conn, $connResult);
    }
    
    /**
     * @param $conn
     * @return bool
     */
    public function isConnected($conn)
    {
        return $conn->connected;
    }
    
    /**
     * @param $conn
     * @return string
     */
    public function getErrorMsg($conn)
    {
        return $conn->connect_error;
    }
    
    /**
     * @param $conn
     * @return int
     */
    public function getErrorCode($conn)
    {
        return $conn->connect_errno;
    }
    
    /**
     * @param $conn
     */
    public function closeConn($conn)
    {
        $conn->close();
    }
}
