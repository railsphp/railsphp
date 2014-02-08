<?php
namespace Rails\ActiveModel;

class Attributes
{
    /**
     * This array holds the valid attributes for each registered class. They are set
     * through the static method setClassAttributes(), and they can only
     * be set once.
     */
    static protected $classAttributes = [];
    
    /**
     * The name of the class holding this instance.
     */
    protected $className;
    
    /**
     * Holds attributes as attributeName => value.
     */
    protected $attributes = [];
    
    /**
     * Whenever an attribute's value is changed, the old value
     * is held here as attributeName => value.
     */
    protected $changedAttributes = [];
    
    /**
     * Registers valid attributes for a class. This must be done before creating
     * an instance of Attributes for that class. Registration can be done only once.
     *
     * @throw Exception\RuntimeException
     */
    static public function setClassAttributes($className, array $attributes)
    {
        if (self::attributesSetFor($className)) {
            throw new Exception\RuntimeException(
                srptinf("Attributes already set for class %s", $className)
            );
        }
        self::$classAttributes[$className] = $attributes;
    }
    
    /**
     * Checks if attributes are registered for a certain class.
     *
     * @return bool
     */
    static public function attributesSetFor($className)
    {
        return array_key_exists($className, self::$classAttributes);
    }
    
    /**
     * Checks if an attribute is a valid attribute for a class.
     *
     * @return bool
     * @throw Exception\RuntimeException
     */
    static public function isClassAttribute($className, $attrName)
    {
        if (!self::attributesSetFor($className)) {
            throw new Exception\RuntimeException(
                srptinf("Attributes not set for class %s", $className)
            );
        }
        return array_key_exists($attrName, self::$classAttributes[$className]);
    }
    
    /**
     * Retrieves the registered attributes for a class. If the class
     * isn't registered, an exception is thrown.
     *
     * @return array
     * @throw Exception\RuntimeException
     */
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
     * Default values for attributes can be passed in $attributes.
     */
    public function __construct($className, array $attributes = [])
    {
        $this->className  = $className;
        $this->attributes = array_merge($this->nullAttributes(), $attributes);
    }
    
    /**
     * Returns the value of an attribute. If the attribute is invalid, an
     * exception is thrown.
     *
     * @return mixed
     * @throw Exception\RuntimeException
     */
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
    
    /**
     * Sets a new value for an attribute. If the attribute is invalid, an
     * exception is thrown.
     *
     * @throw Exception\RuntimeException
     */
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
    
    /**
     * Mass-assign atributes.
     */
    public function assignAttributes(array $attributes)
    {
        if (!$attributes) {
            return;
        }
        
        foreach ($attributes as $attrName => $value) {
            $this->set($attrName, $value);
        }
    }
    
    /**
     * Returns the attributes array.
     *
     * @return array
     */
    public function attributes()
    {
        return $this->attributes;
    }
    
    /**
     * Returns the changed attributes history.
     *
     * @return array
     */
    public function changedAttributes()
    {
        return $this->changedAttributes;
    }
    
    /**
     * Checks if an attribute has changed since the construction
     * of the instance.
     *
     * @return bool
     */
    public function attributeChanged($attrName)
    {
        return array_key_exists($attrName, $this->changedAttributes);
    }
    
    /**
     * Returns the previous value of an attribute. If the attribute
     * wasn't changed, null is returned.
     *
     * @return mixed
     */
    public function attributeWas($attrName)
    {
        return $this->attributeChanged($attr) ?
                $this->changedAttributes[$attr] :
                null;
    }
    
    /**
     * Checks if an attribute is a valid attribute for a class. This is
     * the instance-version of isClassAttribute().
     *
     * @return bool
     */
    public function isAttribute($attrName)
    {
        return self::isClassAttribute($this->className, $attrName);
    }
    
    /**
     * Registers the old value for a given attribute when a new value is
     * set to it.
     */
    protected function setChangedAttribute($attrName, $oldValue)
    {
        $this->changedAttributes[$attrName] = $oldValue;
    }
    
    /**
     * Used to initialize the instance. All attributes start with null values.
     */
    protected function nullAttributes()
    {
        return array_fill_keys(array_keys(self::getAttributesFor($this->className)), null);
    }
}
