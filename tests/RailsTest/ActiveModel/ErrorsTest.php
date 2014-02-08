<?php
namespace RailsTest\ActiveModel;

class ErrorsTest extends \PHPUnit_Framework_TestCase
{
    public function testAddError()
    {
        $errors = $this->getErrors();
        $errors->add('last_name', ['empty']);
        $this->assertSame("Last name is empty", $errors->fullMessages(''));
        $this->assertSame("Last name is invalid", $errors->fullMessage('last_name', 'is invalid'));
        
        $errors->clear();
        $errors->add('last_name', ['invalid']);
        $this->assertSame("Last name is needed for your profile!", $errors->fullMessages(''));
    }
    
    public function testAddBaseError()
    {
        $errors = $this->getErrors();
        $errors->base(['invalid_image_type']);
        $this->assertSame("The image type is invalid.", $errors->fullMessages(''));
    }
    
    public function testErrorFormat()
    {
        $errors = $this->getErrors();
        /**
         * Adding this translations will break the above tests.
         */
        \Rails::serviceManager()->get('i18n')->addTranslations([
            'en' => [
                'errors' => [
                    'format' => "Error with %{attribute}: it %{message}"
                ]
            ]
        ]);
        
        $errors->add('last_name', ['empty']);
        $this->assertSame("Error with Last name: it is empty", $errors->fullMessages(''));
    }
    
    protected function getErrors()
    {
        $model  = new \NullActiveModel();
        $errors = new \Rails\ActiveModel\Errors($model);
        return $errors;
    }
}
