<?php
/**
 * @author Leon J
 * @since 2017/5/25
 */

namespace App\Kernel\Database\Pool;

/**
 * Class Redis
 * @package App\Kernel\Database\Pool
 * @method get( $key )
 * @method set( $key, $value )
 */
class Redis extends Base
{
    protected $client = 'redis';
    
    /**
     * @return array
     */
    protected function makeConn()
    {
        $conn = new \Swoole\Coroutine\Redis();
        $connResult = $conn->connect($this->getConfig('host'), $this->getConfig('port'));
        return array($conn, $connResult);
    }
    
    /**
     * @param $conn
     * @return bool
     */
    public function isConnected($conn)
    {
        return $conn->errCode == 0;
    }
    
    /**
     * @param $conn
     * @return string
     */
    public function getErrorMsg($conn)
    {
        return $conn->errMsg;
    }
    
    /**
     * @param $conn
     * @return int
     */
    public function getErrorCode($conn)
    {
        return $conn->errCode;
    }
    
    /**
     * @param $conn
     */
    public function closeConn($conn)
    {
        $conn->close();
    }
}
