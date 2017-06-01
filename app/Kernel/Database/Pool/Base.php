<?php
/**
 * @author Leon J
 * @since 2017/5/19
 */

namespace App\Kernel\Database\Pool;

use App\Kernel\Database\Exceptions\ConnectException;

/**
 * Class Base
 * @package App\Kernel\Database\Pool
 */
abstract class Base
{
    /**
     * @var int
     */
    protected $total = 0;
    
    /**
     * @var string
     */
    protected $client = '';
    
    /**
     * @var string
     */
    protected $conn = 'default';
    
    /**
     * @var \SplQueue
     */
    protected $connections;
    
    /**
     * Base constructor.
     * @param string $conn
     */
    public function __construct($conn = 'default')
    {
        $this->connections = new \SplQueue;
        $this->conn = $conn;
    }
    
    /**
     * @return int
     */
    public function freeLength()
    {
        return $this->connections->count();
    }
    
    /**
     * 关闭所有连接
     */
    public function closeAll()
    {
        while (!$this->connections->isEmpty()) {
            $conn = $this->connections->dequeue();
            $this->closeConn($conn);
            $this->total--;
        }
    }
    
    /**
     * 在当前请求中保持连接
     */
    public function keepConn()
    {
        coroVal(["{$this->client}.{$this->conn}.conn" => $conn = $this->getConn(true)]);
        events()->fire("{$this->client}.{$this->conn}.conn.keep", [$conn]);
    }
    
    /**
     * @param bool $throwException
     * @return bool|mixed|null
     */
    public function getConn($throwException = false)
    {
        if ($conn = coroVal("{$this->client}.{$this->conn}.conn")) {
            events()->fire("{$this->client}.{$this->conn}.conn.get-from-keep", [$conn]);
            return $conn;
        }
        
        if ($this->connections->isEmpty() && !$this->createConn($throwException)) {
            return false;
        }
        $conn = $this->connections->dequeue();
        events()->fire("{$this->client}.{$this->conn}.conn.get", [$conn]);
        
        if ($this->isConnected($conn) === false) {
            $this->total--;
            return $this->getConn($throwException);
        }
        
        return $conn;
    }
    
    /**
     * @return int
     */
    public function length()
    {
        return $this->total;
    }
    
    /**
     * 弃用保持的连接
     */
    public function dropConn()
    {
        events()->fire("{$this->client}.{$this->conn}.conn.drop", [$conn = coroVal("{$this->client}.{$this->conn}.conn")]);
        coroVal(["{$this->client}.{$this->conn}.conn" => null]);
        $this->recycleConn($conn);
    }
    
    /**
     * @param $conn
     */
    public function recycleConn($conn)
    {
        if ($conn === coroVal("{$this->client}.{$this->conn}.conn")) {
            events()->fire("{$this->client}.{$this->conn}.conn.recycle-skipped", [$conn]);
            return;
        }
        events()->fire("{$this->client}.{$this->conn}.conn.recycle", [$conn]);
        if ($this->isConnected($conn)) {
            $this->connections->enqueue($conn);
        } else {
            $this->total--;
        }
    }
    
    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        $conn = $this->getConn();
        events()->fire("{$this->client}.{$this->conn}.{$name}.start", [$conn, $arguments]);
        $rtn = $conn->$name(...$arguments);
        events()->fire("{$this->client}.{$this->conn}.{$name}.end", [$conn, $rtn]);
        $this->recycleConn($conn);
        return $rtn;
    }
    
    /**
     * @param $conn
     * @return bool
     */
    abstract public function isConnected($conn);
    
    /**
     * @param $conn
     * @return string
     */
    abstract public function getErrorMsg($conn);
    
    /**
     * @param $conn
     * @return int
     */
    abstract public function getErrorCode($conn);
    
    /**
     * @param $conn
     */
    abstract public function closeConn($conn);
    
    /**
     * @param bool $throwException
     * @return bool
     * @throws ConnectException
     */
    protected function createConn($throwException = false)
    {
        if ($this->length() >= $this->getConfig('maxConnections', 100)) {
            if ($throwException) {
                throw new ConnectException('too many connections', 1040);
            } else {
                return false;
            }
        }
        list($conn, $connResult) = $this->makeConn();
        if ($connResult) {
            events()->fire("{$this->client}.{$this->conn}.conn.create", [$conn]);
            $this->connections->enqueue($conn);
            $this->total++;
            return true;
        } elseif ($throwException) {
            throw new ConnectException($this->getErrorMsg($conn), $this->getErrorCode($conn));
        } else {
            return false;
        }
    }
    
    protected function getConfig($key = null, $default = null)
    {
        $configKey = "database.{$this->client}.{$this->conn}";
        return config($key ? $configKey . ".{$key}" : $configKey, $default);
    }
    
    /**
     * @return array( $conn, $connResult )
     */
    abstract protected function makeConn();
}
