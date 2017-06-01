<?php

namespace App\Kernel\Database;

use App\Kernel\Database\Exceptions\SqlException;
use SQRT\QueryBuilder;
use SQRT\QueryBuilder\Query;
use Swoole\Coroutine\MySQL;

/**
 * @author Leon J
 * @since 2017/5/11
 *
 * Select Update Delete
 * @method $this where($mixed)
 * @method $this limit(int $limit, int | null $offset)
 * @method $this page(int $page, int $onpage)
 * @method $this orderby(...$orderBy)
 *
 * Select
 * @method $this columns(...$column)
 * @method $this join($table, $on, $type = null)
 * @method $this groupby(...$column)
 * @method $this having($mixed)
 *
 * Update Insert
 * @method $this setEqual($column, $value)
 * @method $this setExpr($expr)
 * @method $this setFromArray(array $array)
 *
 * Insert
 * @method $this setOnDuplicateKeyUpdate(bool $on_duplicate_key_update)
 */
class Model extends QueryBuilder
{
    /**
     * @var Query
     */
    protected $query;
    
    /**
     * @var string
     */
    protected $table;
    
    /**
     * @var string
     */
    protected $conn = 'default';
    
    /**
     * @param bool $throwException
     * @return array ( array $rtn, bool $success )
     */
    public function begin($throwException = true)
    {
        mysqlPool($this->conn)->keepConn();
        return $this->queryRaw('BEGIN', $throwException);
    }
    
    /**
     * @param $sql
     * @param bool $throwException
     * @return array ( array $rtn, bool $success )
     * @throws SqlException
     */
    public function queryRaw($sql, $throwException = true)
    {
        $conn = mysqlPool($this->conn)->getConn($throwException);
        
        list($rtn, $success) = array([], $conn !== false);
        list($error, $errorNo) = array(0, 0);
        
        if ($success) {
            events()->fire("mysql.{$this->conn}.query.start", [$conn, [$sql]]);
            $rtn = $conn->query($sql);
            if ($rtn === false) {
                $success = false;
                list($error, $errorNo) = array($conn->error, $conn->errno);
            }
            events()->fire("mysql.{$this->conn}.query.end", [$conn, $rtn]);
            
            mysqlPool($this->conn)->recycleConn($conn);
            
            if (!$success && $throwException) {
                throw new SqlException($error, $errorNo);
            }
        }
        
        return array($rtn, $success);
    }
    
    /**
     * @param $name
     * @param $arguments
     * @return $this
     */
    function __call($name, $arguments)
    {
        $this->query->$name(...$arguments);
        return $this;
    }
    
    /**
     * @param $table
     * @return $this
     */
    public function select($table = null)
    {
        $this->query = parent::select($table ?: $this->table);
        return $this;
    }
    
    /**
     * @param $table
     * @return $this
     */
    public function update($table = null)
    {
        $this->query = parent::update($table ?: $this->table);
        return $this;
    }
    
    /**
     * @param $table
     * @return $this
     */
    public function insert($table = null)
    {
        $this->query = parent::insert($table ?: $this->table);
        return $this;
    }
    
    /**
     * @param $table
     * @return $this
     */
    public function delete($table = null)
    {
        $this->query = parent::delete($table ?: $this->table);
        return $this;
    }
    
    /**
     * @param bool $throwException
     * @return array
     */
    public function query($throwException = true)
    {
        return $this->queryRaw($this->query->asSQL(), $throwException);
    }
    
    /**
     * @param bool $throwException
     * @return array ( array $rtn, bool $success )
     */
    public function commit($throwException = true)
    {
        $rtn = $this->queryRaw('COMMIT', $throwException);
        mysqlPool($this->conn)->dropConn();
        return $rtn;
    }
    
    /**
     * @param bool $throwException
     * @return array ( array $rtn, bool $success )
     */
    public function rollback($throwException = true)
    {
        $rtn = $this->queryRaw('ROLLBACK', $throwException);
        mysqlPool($this->conn)->dropConn();
        return $rtn;
    }
}
