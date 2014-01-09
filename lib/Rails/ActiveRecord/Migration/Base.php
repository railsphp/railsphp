<?php
namespace Rails\ActiveRecord\Migration;

class Base
{
    protected $connection;
    
    protected $schema;
    
    public function __construct()
    {
        $this->connection = \Rails\ActiveRecord\ActiveRecord::connection();
    }
    
    public function __call($method, $params)
    {
        if (!$this->schema) {
            $this->setSchema();
        }
        call_user_func_array([$this->schema, $method], $params);
    }
    
    protected function setSchema()
    {
        $this->schema = new \Rails\ActiveRecord\Schema\Schema($this->connection);
    }
}
