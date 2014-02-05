<?php
namespace Rails\ActiveRecord;

use Rails\ActiveModel\Attributes;

# TODO: trait for static function services()
class Base
{
    /**
     * Used when invoking magic methods to get/set model attributes.
     * The default convention is to call the camel-cased version of the attribute, for
     * example $record->createdAt() will return the "created_at" attribute.
     * In the case literal names must be used (call createdAt() to get createdAt
     * attribute) this property can be set to true.
     * However, the best practice would be to manually create getters for each attribute,
     * and even best would be to use actual setter methods for each attribute instead of
     * magically setting them.
     * This also affects assignAttributes().
     *
     * @var bool
     * @see assignAttributes()
     */
    static protected $literalAttributeNames = false;
    
    /**
     * Holds reflections of model classes so they don't have to be
     * created more than once.
     *
     * @var array
     */
    static private $createdReflections = [];
    
    /**
     * Holds data regarding public properties for model classes, so the
     * verification can be skipped if requested multiple times.
     *
     * @var array
     */
    static private $publicClassProperties = [];
    
    /**
     * @var Attributes
     */
    protected $attributes;
    
    static protected function properAttributeName($attrName)
    {
        if (!static::$literalAttributeNames) {
            return self::services()->get('inflector')->underscore($attrName);
        } else {
            return $attrName;
        }
    }
    
    static public function getReflection($class = null)
    {
        if (!$class) {
            $class = get_called_class();
        }
        if (!isset(self::$createdReflections[$class])) {
            self::$createdReflections[$class] = new \ReflectionClass($class);
        }
        return self::$createdReflections[$class];
    }
    
    static public function isPublicProperty($propName, $class = null)
    {
        if (!$class) {
            $class = get_called_class();
        }
        if (!isset(self::$publicClassProperties[$class][$propName])) {
            if (!isset(self::$publicClassProperties[$class])) {
                self::$publicClassProperties[$class] = [];
            }
            $reflection = self::getReflection();
            $ret = $reflection->hasProperty($propName) &&
                    $reflection->getProperty($propName)->isPublic();
            self::$publicClassProperties[$class][$propName] = $ret;
        }
        return self::$publicClassProperties[$class][$propName];
    }
    
    public function __set($prop, $value)
    {
        $attrName = self::properAttributeName($prop);
        
        if ($this->getAttributes()->isAttribute($attrName)) {
            return $this->getAttributes()->set($attrName, $value);
        }
        
        throw new Exception\RuntimeException(
            sprintf("Trying to set unknown property %s", $prop)
        );
    }
    
    public function __call($methodName, $params)
    {
        $attrName = self::properAttributeName($prop);
        
        if ($this->getAttributes()->isAttribute($attrName)) {
            return $this->getAttributes()->get($attrName);
        }
        
        # TODO: Check associations.
        
        throw new Exception\RuntimeException(
            sprintf("Trying to call unknown method %s", $methodName)
        );
    }
    
    /**
     * Returns the current attributes and their values.
     *
     * @return array
     */
    public function attributes()
    {
        return $this->getAttributes()->attributes();
    }
    
    /**
     * Returns the Attributes instance.
     *
     * @return Attributes
     */
    public function getAttributes()
    {
        if (!$this->attributes) {
            # TODO: pass column defaults as default attributes values.
            $this->attributes = new Attributes(get_called_class(), []);
        }
        return $this->attributes;
    }
    
    /**
     * Pass an array of key => value:
     *  If the key is an attribute, value will be set to it.
     *  If the key is a public property, value will be set to it.
     *  If a setter method like setValueName (notice the camelCase) exists,
     *   the value will be passed to it.
     * In MVC, $attributes would normally be the request parameters.
     */
    public function assignAttributes(array $attributes)
    {
        $modelAttrs = $this->getAttributes();
        
        foreach ($attributes as $key => $value) {
            $attrName = self::properAttributeName($key);
            
            if ($modelAttrs->isAttribute($attrName)) {
                $modelAttrs->set($attrName, $value);
            } elseif ($setterMethod = $this->setterExists($attrName)) {
                $this->$setterMethod($value);
            } elseif (self::isPublicProperty($attrName)) {
                $this->$attrName = $value;
            }
        }
        
        return $this;
    }
    
    protected function setterExists($attrName)
    {
        $setter = 'set' . ucfirst($attrName);
        $reflection = self::getReflection();
        
        if ($reflection->hasMethod($setter)) {
            $method = $reflection->getMethod($setter);
            if ($method->isPublic() && !$method->isStatic()) {
                return $setter;
            }
        }
        return false;
    }
}
