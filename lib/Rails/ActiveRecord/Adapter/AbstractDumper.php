<?php
namespace Rails\ActiveRecord\Adapter;

abstract class AbstractDumper
{
    protected $connection;
    
    abstract public function export();
    
    /**
     * @var string
     */
    abstract public function import($sql);
    
    public function __construct($connection = null)
    {
        $this->connection = $connection;
    }
}
