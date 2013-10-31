<?php
namespace Rails\ActiveRecord\Schema;

use Closure;
use Zend\Db;
use Zend\Db\Sql\Ddl;
use Rails;
use Rails\ActiveRecord\ActiveRecord;

class Schema
{
    protected $connection;
    
    protected $adapter;
    
    protected $sql;
    
    public function __construct(ActiveRecord\Connection $connection = null)
    {
        if (!$connection) {
            $connection = ActiveRecord::connection(Rails::env());
        }
        $this->connection = $connection;
        
        $this->buildZfAdapter();
    }
    
    public function sql()
    {
        return $this->sql;
    }
    
    public function adapter()
    {
        return $this->sql;
    }
    
    public function createTable($tableName, $options = [], Closure $block = null)
    {
        if ($options && $options instanceof Closure) {
            $block   = $options;
            $options = [];
        }
        
        $createDdl = new Ddl\CreateTable($tableName);
        $td        = new TableDefinition($this, $createDdl);
        
        if (!empty($options['force'])) {
            $this->queryAdapter(
                new Ddl\DropTable($tableName)
            );
        }
        
        if (!isset($options['id']) || !empty($options['id'])) {
            $pk = isset($options['primary_key']) ? $options['primary_key'] : 'id';
            $td->primaryKey($pk);
        }
        
        if ($block) {
            $block($td);
        }
        
        $this->queryAdapter($createDdl);
    }
    
    public function addColumn($tableName, $name, $type, array $options = [])
    {
        
    }
    
    public function addIndex($tableName, $columnName, array $options = [])
    {
        
    }
    
    protected function buildZfAdapter()
    {
        $pdoCon = new Db\Adapter\Driver\Pdo\Connection();
        $pdoCon->setResource($this->connection->resource());
        
        $pdo = new Db\Adapter\Driver\Pdo\Pdo($pdoCon);
        $this->adapter = new Db\Adapter\Adapter($pdo);
        
        $this->sql = new Db\Sql\Sql(
            $this->adapter
        );
        
        // $select = $this->zfSql->select('posts')->where(['id' => 1]);
        // $stmt = $this->zfSql->prepareStatementForSqlObject($select);
        
        // vp($stmt->execute()->getResource()->fetchAll());
    }
    
    protected function queryAdapter($ddl)
    {
        $adapter = $this->adapter;
        $adapter->query(
            $this->sql->getSqlStringForSqlObject($ddl),
            $adapter::QUERY_MODE_EXECUTE
        );
    }
}
