<?php
namespace Rails\ActionController;

use ReflectionClass;

class MainController
{
    protected $controller;
    
    protected $action;
    
    protected $filters = [];
    
    public function __construct(array $config)
    {
    }
    
    public function setController(Base $controller)
    {
        $this->controller = $controller;
    }
    
    public function setAction($action)
    {
        $this->action = $action;
    }
    
    public function runRequestAction()
    {
        if (!$this->actionMethodExists()) {
            throw new Exception\UnknownActionException(
                sprintf(
                    "The action '%s' could not be found for %s",
                    $this->action,
                    get_called_class()
                )
            );
        }
        $this->controller->runAction($this->action);
    }
    
    protected function actionMethodExists()
    {
        $methodExists = false;
        $ctrlrClass   = get_class($this->controller);
        $refl         = new ReflectionClass($ctrlrClass);
        
        if ($refl->hasMethod($this->action)) {
            $method = $refl->getMethod($this->action);
            if (
                $method->getDeclaringClass()->getName() == $ctrlrClass &&
                $method->isPublic()
            ) {
                $methodExists = true;
            }
        }
        
        return $methodExists;
    }
}
