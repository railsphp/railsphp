<?php
namespace Rails\ActiveModel\Validator;

trait ValidatorTrait
{
    protected $validator;
    
    public function validator()
    {
        if (!$this->validator) {
            $this->setupValidator();
        }
        return $this->validator;
    }
    
    public function isValid()
    {
        return $this->validator()->validate($this);
    }
    
    protected function setupValidator()
    {
        $this->validator = new Validator();
        
    }
    
    protected function validations()
    {
        return [];
    }
}
