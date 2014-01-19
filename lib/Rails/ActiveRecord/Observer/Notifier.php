<?php
namespace Rails\ActiveRecord\Observer;

use Rails;

class Notifier
{
    static protected $instance;
    
    static public function instance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function notify($callbackName, $model, $arguments = [])
    {
        $modelClass = get_class($model);
        
        $methodName = Rails::services()->get('inflector')->camelize($callbackName, false);
        
        foreach ($this->getCallbacks($modelClass) as $observerClass => $callbacks) {
            if (in_array($methodName, $callbacks)) {
                $this->runCallback($observerClass, $methodName, $arguments ?: [$model]);
            }
        }
    }
    
    protected function getCallbacks($modelClass)
    {
        $callbacks = [];
        
        if ($observers = Rails::config()->active_record->observers->toArray()) {
            $observerMethodRegex = '/^(before|after)/';
            
            foreach ($observers as $observerClass) {
                $callbacks[$observerClass] = [];
                
                if (in_array($modelClass, (array)$observerClass::observe())) {
                    $refl = new \ReflectionClass($observerClass);
                    
                    foreach ($refl->getMethods() as $method) {
                        if (
                            $method->isPublic()
                            && !$method->isStatic()
                            && preg_match($observerMethodRegex, $method->name)
                        ) {
                            $callbacks[$observerClass][] = $method->name;
                        }
                    }
                }
            }
        }
        
        return $callbacks;
    }
    
    protected function runCallback($observerClass, $methodName, $arguments)
    {
        $observer = new $observerClass();
        call_user_func_array([$observer, $methodName], $arguments);
    }
}
