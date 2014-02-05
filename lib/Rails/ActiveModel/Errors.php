<?php
namespace Rails\ActiveModel;

class Errors
{
    use Rails\ServiceManager\ServiceLocatorAwareTrait;
    
    const BASE_ERRORS_INDEX = 'recordBaseErrors';
    
    protected $errors = array();
    
    protected $model;
    
    public function __construct($model)
    {
        $this->model = $model;
    }
    
    public function add($attribute, $msg = null)
    {
        if (!isset($this->errors[$attribute])) {
            $this->errors[$attribute] = array();
        }
        
        $msg = $this->normalizeMsg($msg);
        
        $this->errors[$attribute][] = $msg;
    }
    
    public function base($msg)
    {
        $this->add(self::BASE_ERRORS_INDEX, $msg);
    }
    
    public function on($attribute)
    {
        if (!isset($this->errors[$attribute])) {
            return null;
        } elseif (count($this->errors[$attribute]) == 1) {
            return current($this->errors[$attribute]);
        } else {
            return $this->errors[$attribute];
        }
    }
    
    public function onBase()
    {
        return $this->on(self::BASE_ERRORS_INDEX);
    }
    
    /**
     * $glue is a string that, if present, will be used to
     * return the messages imploded.
     */
    public function fullMessages($glue = null)
    {
        $fullMessages = array();
        
        foreach ($this->errors as $attr => $errors) {
            foreach ($errors as $msg) {
                if ($attr == self::BASE_ERRORS_INDEX) {
                    $fullMessages[] = $msg;
                } else {
                    $fullMessages[] = $this->properAttrName($attr) . ' ' . $msg;
                }
            }
        }
        
        if ($glue !== null) {
            return implode($glue, $fullMessages);
        } else {
            return $fullMessages;
        }
    }
    
    public function invalid($attribute)
    {
        return isset($this->errors[$attribute]);
    }
    
    public function none()
    {
        return !(bool)$this->errors;
    }
    
    public function any()
    {
        return (bool)$this->errors;
    }
    
    public function all()
    {
        return $this->errors;
    }
    
    public function count()
    {
        $i = 0;
        foreach ($this->errors as $errors) {
            $i += count($errors);
        }
        return $i;
    }
    
    protected function properAttrName($attr)
    {
        $attr = ucfirst(strtolower($attr));
        if (is_int(strpos($attr, '_'))) {
            $attr = str_replace('_', ' ', $attr);
        }
        return $attr;
    }
    
    /**
     * $msg could be an array like this:
     * [ 'invalid', 'placeholder1' => 'value1', 'placeholder2' => 'value2', ... ]
     */
    protected normalizeMsg($msg, $attribute, $type = 'invalid')
    {
        if (!is_array($msg)) {
            return $msg;
        }
        if (!method_exists($this->record, 'i18nScope')) {
            $i18nKey = $this->getI18nKey();
            
            $defaults = [
                $this->i18nScope() . '.errors.models.' . $i18nKey . '.attributes.' . $attribute . '.' . $type,
                $this->i18nScope() . '.errors.models.' . $i18nKey . $type
            ];
        } else {
            $defaults = [];
        }
        
        
        return self::services()->get('i18n')->translate();
    }
    
    protected getI18nKey()
    {
        $className = explode('\\', get_class($this->record));
        $name      = end($className);
        return self::services()->get('inflector')->underscore($name);
    }
}
