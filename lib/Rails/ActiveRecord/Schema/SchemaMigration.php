<?php
namespace Rails\ActiveRecord\Schema;

class SchemaMigration extends \Rails\ActiveRecord\Base
{
    static protected $connection;
    
    static protected $tableName;
    
    static public function connection()
    {
        return self::$connection;
    }
    
    static public function tableName()
    {
        return self::$tableName;
    }
    
    static public function setConnection($connection)
    {
        self::$connection = $connection;
    }
    
    static public function setTableName($tableName)
    {
        self::$tableName = $tableName;
    }
}
