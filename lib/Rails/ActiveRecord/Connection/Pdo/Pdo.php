<?php
namespace Rails\ActiveRecord\Connection\Pdo;

use PDO;
use Rails;

class Pdo
{
    protected $resource;
    
    protected $adapterName;
    
    /**
     * Connection name.
     *
     * @var string
     */
    protected $name;
    
    protected $lastError;
    
    public function __construct(Pdo $resource, $name = '')
    {
        $this->name        = $name;
        $this->resource    = $resource;
        $this->adapterName = $this->resource->getAttribute(PDO::ATTR_DRIVER_NAME);
    }
    
    public function lastError()
    {
        return $this->lastError;
    }
    
    public function resource()
    {
        return $this->resource;
    }
    
    public function adapterName()
    {
        return $this->adapterName;
    }
    
    public function name()
    {
        return $this->name;
    }
    
    /**
     * Executes SQL and returns the statement.
     * If an error occurs in the SQL server, it is stored in $lastError.
     *
     * @throw Exception\LogicException
     * @throw Exception\QueryException
     */
    public function executeSql()
    {
        $params = func_get_args();
        $sql    = array_shift($params);
        
        if (!$sql) {
            throw new Exception\LogicException("Can't execute SQL without SQL");
        } elseif (is_array($sql)) {
            $params = $sql;
            $sql    = array_shift($params);
        }
        $this->parseQueryMultimark($sql, $params);
        
        if (!$stmt = $this->resource->prepare($sql)) {
            list($sqlstate, $errCode, $errMsg) = $this->resource->errorInfo();
            $e = new Exception\QueryException(
                sprintf("[PDOStatement error] [SQLSTATE %s] (%s) %s", $sqlstate, $errCode, $errMsg)
            );
            throw $e;
        } elseif (!$stmt->execute($params)) {
            list($sqlstate, $errCode, $errMsg) = $stmt->errorInfo();
            $e = new Exception\QueryException(
                sprintf("[PDOStatement error] [SQLSTATE %s] (%s) %s", $sqlstate, $drvrcode, $errMsg)
            );
            $e->setStatement($stmt, $params);
            throw $e;
        }
        
        $err = $stmt->errorInfo();
        
        if ($err[2]) {
            $this->lastError = $err;
        } else {
            $this->lastError = null;
        }
        
        return $stmt;
    }
    
    /**
     * Executes the sql and returns the results.
     */
    public function query()
    {
        $stmt = call_user_func_array([$this, 'executeSql'], func_get_args());
        if ($this->lastError) {
            return false;
        }
        return $stmt->fetchAll();
    }
    
    /**
     * Returns multiple rows.
     */
    public function select()
    {
        $stmt = call_user_func_array([$this, 'executeSql'], func_get_args());
        if ($this->lastError) {
            return false;
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Returns one row.
     */
    public function selectRow()
    {
        $stmt = call_user_func_array([$this, 'executeSql'], func_get_args());
        if ($this->lastError) {
            return false;
        }
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Returns one column from multiple rows.
     */
    public function selectValues()
    {
        $stmt = call_user_func_array([$this, 'executeSql'], func_get_args());
        if ($this->lastError) {
            return false;
        }
        
        $cols = array();
        if ($data = $stmt->fetchAll(PDO::FETCH_ASSOC)) {
            foreach ($data as $d) {
                $cols[] = current($d);
            }
        }
        return $cols;
    }
    
    /**
     * Returns one column from one row. I.e., a single value.
     */
    public function selectValue()
    {
        $stmt = call_user_func_array([$this, 'executeSql'], func_get_args());
        if ($this->lastError) {
            return false;
        }
        if ($data = $stmt->fetch()) {
            $data = array_shift($data);
        }
        return $data;
    }
    
    public function lastInsertId()
    {
        return $this->resource->lastInsertId();
    }
    
    /**
     * Begins a transaction and runs the Closure. If any exception is thrown,
     * the transaction is rolled back and the exception is thrown again.
     */
    public function transaction(\Closure $block)
    {
        $this->resource()->beginTransaction();
        try {
            $block();
            $this->resource()->commit();
        } catch (\Exception $e) {
            $this->resource()->rollBack();
            throw $e;
        }
    }
    
    protected function parseQueryMultimark(&$query, array &$params)
    {
        if (
            is_bool(strpos($query, '?')) ||
            /**
             * If the number of tokens isn't equal to parameters, ignore
             * the query and return. PDOStatement will trigger a Warning.
             */
            substr_count($query, '?') != count($params)
        ) {
            return;
        }
        
        $parts = explode('?', $query);
        $parsedParams = array();
        
        foreach ($params as $k => $v) {
            if (is_array($v)) {
                $k++;
                $count = count($v);
                $parts[$k] = ($count > 1 ? ', ' . implode(', ', array_fill(0, $count - 1, '?')) : '') . $parts[$k];
                $parsedParams = array_merge($parsedParams, $v);
            } else {
                $parsedParams[] = $v;
            }
        }
        
        $params = $parsedParams;
        $query  = implode('?', $parts);
    }
}
