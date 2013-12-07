<?php
namespace Rails\Console;

class Console
{
    public function __call($method, $params)
    {
        return $this->zend($method, $params);
    }
    
    public function zend($method, $params)
    {
        call_user_func_array([$this->instance(), $method], $params);
    }
    
    /**
     * Alias for writeLine
     */
    public function put($text = "", $color = null, $bgColor = null)
    {
        $this->zend('writeLine', [$text, $color, $bgColor]);
    }
    
    public function terminate($text = "", $color = null, $bgColor = null)
    {
        $this->zend('writeLine', [$text, $color, $bgColor]);
        exit;
    }
    
    /**
     * Ask the user to confirm something.
     */
    public function confirm()
    {
        return call_user_func_array('Zend\Console\Prompt\Confirm::prompt', func_get_args());
    }
    
    /**
     * Ask the user to hit a key.
     */
    public function key()
    {
        return call_user_func_array('Zend\Console\Prompt\Char::prompt', func_get_args());
    }
    
    /**
     * Ask the user for a text.
     */
    public function input()
    {
        return call_user_func_array('Zend\Console\Prompt\Line::prompt', func_get_args());
    }
    
    public function number()
    {
        return call_user_func_array('Zend\Console\Prompt\Number::prompt', func_get_args());
    }
    
    public function select()
    {
        return call_user_func_array('Zend\Console\Prompt\Select::prompt', func_get_args());
    }
    
    /**
     * Returns Zend console instance.
     */
    public function instance()
    {
        return \Zend\Console\Console::getInstance();
    }
}
