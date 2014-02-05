<?php
namespace Rails\Validator\Validations;

use Rails\Validator\Exception;

class FormatValidator extends AbstractValidator
{
    public function validate($record, $attribute, $value)
    {
        $validator = isset($this->options['with']) ?
                        $this->options['with'] :
                        $this->options['without'];
        
        if (is_callable($validator)) {
            $regexp = $validator($record, $attribute);
        } else {
            $regexp = $validator;
        }
        
        $valid = (bool)preg_match($regexp, $value);
        
        if (isset($this->options['without'])) {
            $valid = !$valid;
        }
        
        if (!$valid) {
            $record->errors()->add($attribute, 'invalid', array_merge($options, ['value' => $value]));
        }
    }
    
    public function validateOptions()
    {
        if (
                (isset($this->options['with']) &&  isset($this->options['without']))
            || (!isset($this->options['with']) && !isset($this->options['without']))
        ) {
            throw new Exception\LogicException(
                "Either 'with' or 'without' must be supplied, but not both"
            );
        }
        $this->validateOption('with');
        $this->validateOption('without');

    }
    
    protected function validateOption($name)
    {
        if (!isset($this->options[$name])) {
            return;
        }
        $option = $this->options[$name];
        
        if (!is_callable($option) || !is_string($option)) {
            throw new Exception\InvalidArgumentException(
                sprintf(
                    "Option '%s' must be either callable or a string, %s passed",
                    $name,
                    gettype($option)
                )
            );
        }
    }
}
