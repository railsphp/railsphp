<?php
namespace Rails\ActiveRecord\Schema;

use Zend\Db\Sql\Ddl\Column as ZfColumn;
use Zend\Db\Sql\Ddl\Constraint;
use Rails\ActiveRecord\Exception;

class TableDefinition
{
    protected $schema;
    
    /**
     * @var Zend\Db\Sql\Ddl\CreateTable
     */
    protected $table;
    
    public function __construct(Schema $schema, \Zend\Db\Sql\Ddl\CreateTable $table)
    {
        $this->schema = $schema;
        $this->table = $table;
    }
    
    /**
     * Adds a column
     */
    public function column($name, $type, array $options = [])
    {
        switch ($type) {
            case 'varchar':
                $column = new ZfColumn\Varchar($name, $options['limit']);
                break;
            
            case 'integer':
                $column = new ZfColumn\Integer($name);
                break;
            
            case 'datetime':
                $column = new Column\DateTime($name);
                break;
            
            default:
                throw new Exception\RuntimeException(
                    sprintf("Unknown column type '%s'", $type)
                );
        }
        
        
        $this->table->addColumn($column);
    }
    
    public function timestamps(array $options = [])
    {
        $this->column('created_at', 'datetime', $options);
        $this->column('updated_at', 'datetime', $options);
    }
    
    public function string($name, $limit = 255, $type = 'varchar', array $options = [])
    {
        $this->column($name, $type, array_merge($options, ['limit' => $limit]));
    }
    
    public function text($name, array $options = [])
    {
        $this->column($name, 'text', $options);
    }
    
    public function integer($name, array $options = [])
    {
        $this->column($name, 'integer', $options);
    }
    
    public function float($name, array $options = [])
    {
        $this->column($name, 'float', $options);
    }
    
    public function decimal($name, array $options = [])
    {
        $this->column($name, 'decimal', $options);
    }
    
    public function datetime($name, array $options = [])
    {
        $this->column($name, 'datetime', $options);
    }
    
    public function timestamp($name, array $options = [])
    {
        $this->column($name, 'timestamp', $options);
    }
    
    public function time($name, array $options = [])
    {
        $this->column($name, 'time', $options);
    }
    
    public function date($name, array $options = [])
    {
        $this->column($name, 'date', $options);
    }
    
    public function binary($name, array $options = [])
    {
        $this->column($name, 'binary', $options);
    }
    
    public function boolean($name, array $options = [])
    {
        $this->column($name, 'boolean', $options);
    }
    
    public function primaryKey($name, $type = 'integer', array $options = [])
    {
        $this->column($name, $type, $options);
        $this->table->addConstraint(
            new Constraint\PrimaryKey($name)
        );
    }
}
