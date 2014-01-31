<?php
namespace Rails\ActionDispatch\Http;

use Rails\ArrayHelper\GlobalVariableIndex;

class Session implements \IteratorAggregate
{
    public function getIterator()
    {
        return new \ArrayIterator($_SESSION);
    }
    
    public function __set($prop, $value)
    {
        $this->set($prop, $value);
    }
    
    public function __get($prop)
    {
        return $this->get($prop);
    }
    
    public function set($key, $value)
    {
        if (is_object($value)) {
            $this->$prop = $value;
            $_SESSION[$prop] = $value;
        } elseif (is_array($value)) {
            $arr = new GlobalVariableIndex($value, '_SESSION', $prop);
            $this->$prop = $arr;
        } else {
            $_SESSION[$prop] = $value;
        }
        return $this;
    }
    
    public function get($prop)
    {
        if (isset($_SESSION[$prop])) {
            if (is_array($_SESSION[$prop])) {
                $this->$prop = new GlobalVariableIndex($_SESSION[$prop], '_SESSION', $prop);
                return $this->$prop;
            } elseif (is_object($_SESSION[$prop])) {
                $this->$prop = $_SESSION[$prop];
                return $this->$prop;
            } else {
                return $_SESSION[$prop];
            }
        }
        return null;
    }
    
    public function delete($prop)
    {
        unset($this->$prop, $_SESSION[$prop]);
    }
}
