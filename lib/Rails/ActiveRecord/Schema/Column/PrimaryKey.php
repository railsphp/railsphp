<?php
namespace Rails\ActiveRecord\Schema\Column;

use Zend\Db\Sql\Ddl\Column\Column as ZfColumn;

class PrimaryKey extends ZfColumn
{
    protected $length = 11;

    protected $adapter;
    
    protected $unsigned = false;

    /**
     * @param null|string $name
     * @param int $length
     */
    public function __construct($name, $adapter)
    {
        $this->name    = $name;
        $this->adapter = $adapter;
    }
    
    public function setLength($length)
    {
        $this->length = $length;
    }
    
    public function setUnsigned($value)
    {
        $this->unsigned = (bool)$value;
    }

    /**
     * @return array
     */
    public function getExpressionData()
    {
        $params = [];
        
        if ($this->adapter == 'mysql') {
            $types = [self::TYPE_IDENTIFIER, self::TYPE_LITERAL, self::TYPE_LITERAL];
            $spec  = '%s INT(%s) NOT NULL AUTO_INCREMENT %s';
            
            $params[] = $this->name;
            $params[] = $this->length;
            
            if ($this->unsigned) {
                $params[] = 'UNSIGNED';
            } else {
                $params[] = '';
            }
        }
        
        return array(array(
            $spec,
            $params,
            $types,
        ));
    }
}
