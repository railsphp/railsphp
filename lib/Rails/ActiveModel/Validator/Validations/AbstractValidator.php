<?php
namespace Rails\Validator\Validations;

abstract class AbstractValidator
{
    protected $options;
    
    abstract public function validate($record, $attribute, $value);
    
    public function __construct($options)
    {
        $this->options = $options;
        $this->validateOptions();
    }
    
    /**
     * Check if $options are alright; else, an exception may be thrown.
     */
    public function validateOptions()
    {
    }
    
    public function setOptions($options)
    {
        $this->options = $options;
        $this->validateOptions();
    }
}
