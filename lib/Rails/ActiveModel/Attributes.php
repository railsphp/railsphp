<?php
namespace Rails\ActiveModel;

class Attributes
{
    static protected $classAttributes = [];
    
    protected $className;
    
    protected $attributes = [];
    
    protected $changedAttributes = [];
    
    static public function setClassAttributes($className, array $attributes)
    {
        if (self::attributesSetFor($className)) {
            throw new Exception\RuntimeException(
                srptinf("Attributes already set for class %s", $className)
            );
        }
        self::$classAttributes[$className] = $attributes;
    }
    
    static public function attributesSetFor($className)
    {
        return array_key_exists($className, self::$classAttributes);
    }
    
    static public function isClassAttribute($className, $attrName)
    {
        if (!self::attributesSetFor($className)) {
            throw new Exception\RuntimeException(
                srptinf("Attributes not set for class %s", $className)
            );
        }
        return array_key_exists($attrName, self::$classAttributes[$className]);
    }
    
    static public function getAttributesFor($className)
    {
        if (!self::attributesSetFor($className)) {
            throw new Exception\RuntimeException(
                srptinf("Attributes not set for class %s", $className)
            );
        }
        return self::$classAttributes[$className];
    }
    
    /**
     * Class attributes for $className must be set before creating an instance for that class.
     */
    public function __construct($className, array $attributes = [])
    {
        $this->className  = $className;
        $this->attributes = array_merge($this->nullAttributes(), $attributes);
    }
    
    public function get($attrName)
    {
        if (!self::isClassAttribute($this->className, $attrName)) {
            throw new Exception\RuntimeException(
                sprintf(
                    "Trying to get unknown attribute '%s' for class '%s'",
                    $attrName,
                    $this->className
                )
            );
        }
        return $this->attributes[$attrName];
    }
    
    public function set($attrName, $value)
    {
        if (!self::isClassAttribute($this->className, $attrName)) {
            throw new Exception\RuntimeException(
                sprintf(
                    "Trying to set unknown attribute '%s' for class '%s'",
                    $attrName,
                    $this->className
                )
            );
        }
        if (
            $this->attributes[$attrName] !== null ||
            $this->attributeChanged($attrName)
        ) {
            $this->setChangedAttribute($attrName, $this->attributes[$attrName]);
        }
        $this->attributes[$attrName] = $value;
    }
    
    public function assignAttributes(array $attributes)
    {
        if (!$attributes) {
            return;
        }
        
        foreach ($attributes as $attrName => $value) {
            $this->set($attrName, $value);
        }
    }
    
    public function attributes()
    {
        return $this->attributes;
    }
    
    public function changedAttributes()
    {
        return $this->changedAttributes;
    }
    
    public function attributeChanged($attrName)
    {
        return array_key_exists($attrName, $this->changedAttributes);
    }
    
    public function attributeWas($attrName)
    {
        return $this->attributeChanged($attr) ?
                $this->changedAttributes[$attr] :
                null;
    }
    
    public function isAttribute($attrName)
    {
        return self::isClassAttribute($this->className, $attrName);
    }
    
    protected function setChangedAttribute($attrName, $oldValue)
    {
        $this->changedAttributes[$attrName] = $oldValue;
    }
    
    protected function nullAttributes()
    {
        return array_fill_keys(array_keys(self::getAttributesFor($this->className)), null);
    }
}
