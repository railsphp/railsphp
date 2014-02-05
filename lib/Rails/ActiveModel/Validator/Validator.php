<?php
namespace Rails\ActiveModel\Validator;

abstract class Validator
{
    protected $validators = [
        'absence' => 'Rails\ActiveModel\Validator\Validations\AbsenceValidator',
        'acceptance' => 'Rails\ActiveModel\Validator\Validations\AcceptanceValidator',
        'confirmation' => 'Rails\ActiveModel\Validator\Validations\ConfirmationValidator',
        'exclusion' => 'Rails\ActiveModel\Validator\Validations\ExclusionValidator',
        'format' => 'Rails\ActiveModel\Validator\Validations\FormatValidator',
        'inclusion' => 'Rails\ActiveModel\Validator\Validations\InclusionValidator',
        'length' => 'Rails\ActiveModel\Validator\Validations\LengthValidator',
        'numericality' => 'Rails\ActiveModel\Validator\Validations\NumericalityValidator',
        'presence' => 'Rails\ActiveModel\Validator\Validations\PresenceValidator',
    ];
    
    protected $validations = [];
    
    public function setValidations(array $validations)
    {
        $this->validations = $validations;
    }
    
    public function addValidation($attribute, $kind, $options)
    {
        if (!isset($this->validations[$attribute])) {
            $this->validations[$attribute] = [];
        }
        $this->validations[$attribute][] = [$kind, $options];
        return $this;
    }
    
    public function validate($record)
    {
        foreach ($validations as $attribute => $validators) {
            foreach ($validators as $kind => $options) {
                if (!isset($this->validators[$kind])) {
                    throw new Exception\UnknownValidatorException(
                        sprintf("Unknown validator kind '%s'", $kind)
                    );
                }
                $validatorName = $this->validators[$kind];
                $validator     = new $validatorName($options);
                $validator->validate($record, $attribute, $record->getAttributes()->get($attribute));
            }
        }
    }
}
