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
        if (Rails::env() == 'production') {
            return Rails::cache()->fetch('Rails.ActiveRecord.Observer.Callbacks.' . $modelClass, function() use ($modelClass) {
                return $this->findCallbacks($modelClass);
            });
        } else {
            return $this->findCallbacks($modelClass);
        }
    }
    
    protected function findCallbacks($modelClass)
    {
        $callbacks = [];
        
        if ($observers = Rails::config()->active_record->observers->toArray()) {
            foreach ($observers as $observerClass) {
                $callbacks[$observerClass] = [];
                
                if (in_array($modelClass, (array)$observerClass::observe())) {
                    $refl = new \ReflectionClass($observerClass);
                    
                    foreach ($refl->getMethods() as $method) {
                        if (
                            $method->isPublic() &&
                            !$method->isStatic() &&
                            (0 === strpos($method->name, 'before')
                             || 0 === strpos($method->name, 'after'))
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
