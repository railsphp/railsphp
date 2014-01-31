<?php
namespace Rails\ActionController;

use ReflectionClass;

abstract class Base
{
    # TODO
    // use NamedPathAwareTrait;
    
    const APP_CONTROLLER_CLASS = 'ApplicationController';
    
    static protected $cache;
    
    protected $layout;
    
    protected $response;
    
    protected $parameters;
    
    protected $locals = [];
    
    protected $respondTo = [];
    
    protected $helpers = [];
    
    protected $renderParams;
    
    protected $redirectParams;
    
    protected $action;
    
    protected $actionRan = false;
    
    protected $status;
    
    protected $contentType;
    
    protected $charset;
    
    private $selfRefl;
    
    private $appControllerRefls = [];
    
    static public function setCache(\Rails\Cache\Cache $cache)
    {
        self::$cache = $cache;
    }
    
    /**
     * Children classes shouldn't override __construct(),
     * they should override init() instead.
     *
     * The classes "ApplicationController" are practically abstract classes.
     * Some methods declared on them (init() and filters()) will be bound to the
     * actual instance and executed.
     * This will happen with any class called "ApplicationController"
     * under any namespace.
     */
    public function __construct()
    {
        $class = get_called_class();
        
        if (!$this->isApplicationController($class)) {
            $this->selfRefl = new ReflectionClass($class);
            
            $reflection = $this->selfRefl;
            
            while (true) {
                $parent = $reflection->getParentClass();
                if ($this->isApplicationController($parent->getName())) {
                    $this->appControllerRefls[] = $parent;
                } elseif ($parent->getName() == __CLASS__) {
                    break;
                }
                $reflection = $parent;
            }
            $this->appControllerRefls = array_reverse($this->appControllerRefls);
            $this->runInitializers();
        }
    }
    
    public function __set($property, $value)
    {
        $this->setLocal($property, $value);
    }
    
    public function __get($property)
    {
        if (!array_key_exists($property, $this->locals)) {
            throw new Exception\RuntimeException(
                sprintf("Trying to get undefined local '%s'", $property)
            );
        }
        return $this->locals['$property'];
    }
    
    public function __call($method, $params)
    {
        # TODO
        // if ($this->isNamedPathMethod($method)) {
            // return $this->getNamedPath($method, $params);
        // }
        
        throw new Exception\BadMethodCallException(
            sprintf("Called to unknown method: %s", $method)
        );
    }
    
    public function init()
    {
    }
    
    public function setResponse(\Rails\ActionDispatch\Response $response)
    {
        $this->response = $response;
    }
    
    public function response()
    {
        $this->response = $response;
    }
    
    public function setParameters(\Rails\ActionDispatch\Http\Parameters $parameters)
    {
        $this->parameters = $parameters;
    }
    
    public function params()
    {
        return $this->parameters;
    }
    
    public function setStatus($status)
    {
        $this->status = $status;
    }
    
    public function status()
    {
        return $this->status;
    }
    
    public function setContentType($contentType)
    {
        $this->contentType = $contentType;
    }
    
    public function contentType()
    {
        return $this->contentType;
    }
    
    public function setCharset($charset)
    {
        $this->charset = $charset;
    }
    
    public function charset()
    {
        return $this->charset;
    }
    
    public function setLayout($layout)
    {
        $this->layout = $layout;
    }
    
    public function layout()
    {
        return $this->layout;
    }
    
    /**
     * Due to the nature of this functionality, set locals are practically read-only.
     */
    public function setLocal($name, $value)
    {
        $this->locals[$name] = $value;
    }
    
    public function locals()
    {
        return $this->locals;
    }
    
    public function helper()
    {
        $this->helpers = array_merge($this->helpers, func_get_args());
    }
    
    public function respondTo(array $responses)
    {
        $this->respondTo = $responses;
    }
    
    public function respondsTo()
    {
        return $this->respondTo;
    }
    
    public function respondWith($var)
    {
        $this->respondWith = $var;
    }
    
    public function respondsWith()
    {
        return $this->respondWith;
    }
    
    public function render($renderParams)
    {
        if ($this->hasResponded()) {
            throw new Exception\DoubleRenderException(
                'Can only render or redirect once per action.'
            );
        }
        $this->renderParams = $renderParams;
    }
    
    protected function redirectTo($redirectParams)
    {
        if ($this->responded()) {
            throw new Exception\DoubleRenderException(
                'Can only render or redirect once per action.'
            );
        }
        $this->redirectParams = $redirectParams;
    }
    
    protected function filters()
    {
        return [];
    }
    
    public function hasResponded()
    {
        return $this->renderParams || $this->redirectParams;
    }
    
    public function renderParams()
    {
        return $this->renderParams;
    }
    
    public function redirectParams()
    {
        return $this->redirectParams;
    }
    
    public function urlFor($params)
    {
        $urlFor = new UrlFor($params);
        return $urlFor->url();
    }
    
    public function runAction($action)
    {
        $this->action = $action; # Used by canRunFilterMethod().
        $filters      = $this->getFiltersMethods();
        
        $this->runFilterType($filters, 'before');
        /**
         * Check if response params where set by the
         * before filters.
         */
        if (!$this->hasResponded()) {
            $this->$action();
            $this->actionRan = true;
            $this->runFilterType($filters, 'after');
        }
    }
    
    public function actionRan()
    {
        return $this->actionRan;
    }
    
    /**
     * Filters will be cached if the static $cache is set.
     */
    protected function getFiltersMethods()
    {
        if (self::$cache) {
            $key = 'Rails.Filters.' . get_called_class();
            if ($filters = self::$cache->read($key)) {
                return $filters;
            }
        }
        
        $closures = array_merge(
            $this->getAppControllersMethod('filters'),
            $this->getOtherFilters()
        );
        
        if ($closures) {
            $filters = [];
            foreach ($closures as $closure) {
                $filters = array_merge_recursive($closure(), $filters);
            }
            $filters = array_merge_recursive($filters, $this->filters());
        } else {
            $filters = $this->filters();
        }
        
        if (self::$cache) {
            self::$cache->write($key, $filters);
        }
        
        return $filters;
    }
    
    protected function runFilterType($filters, $type)
    {
        if (isset($filters[$type])) {
            /**
             * We have to filter duped methods. We can't use array_unique
             * because the the methods could be like 'method_name' => [ 'only' => [ actions ... ] ]
             * and that will generate "Array to string conversion" error.
             */
            $ranMethods = [];
            
            foreach ($filters[$type] as $methodName => $params) {
                if (!is_array($params)) {
                    $methodName = $params;
                    $params = [];
                }
                
                if ($this->canRunFilterMethod($params) && !in_array($methodName, $ranMethods)) {
                    $this->$methodName();
                    /**
                     * Before-filters may set response params. Running filters stop if one of them does.
                     */
                    if ($type == 'before' && $this->hasResponded()) {
                        break;
                    }
                    $ranMethods[] = $methodName;
                }
            }
        }
    }
    
    protected function canRunFilterMethod(array $params = [])
    {
        if (isset($params['only']) && !in_array($this->action, $params['only'])) {
            return false;
        } elseif (isset($params['except']) && in_array($this->action, $params['except'])) {
            return false;
        }
        return true;
    }
    
    /**
     * Runs initializers from the called class and its
     * ApplicationController parents, if any.
     */
    private function runInitializers()
    {
        $methodName = 'init';
        $cn = get_called_class();
        
        # Run ApplicationController's init method.
        if ($inits = $this->getAppControllersMethod($methodName)) {
            foreach ($inits as $init) {
                $init = $init->bindTo($this);
                $init();
            }
        }
        
        $method = $this->selfRefl->getMethod($methodName);
        if ($method->getDeclaringClass()->getName() == $cn) {
            $this->$methodName();
        }
    }
    
    /**
     * Searches through all the ApplicationControllers classes for a method,
     * and returns them all.
     *
     * @return array
     */
    private function getAppControllersMethod($methodName, $scope = '')
    {
        $methods = [];
        
        if ($this->appControllerRefls) {
            foreach ($this->appControllerRefls as $appRefl) {
                if ($appRefl->hasMethod($methodName)) {
                    $method = $appRefl->getMethod($methodName);
                    
                    if ($this->isApplicationController($method->getDeclaringClass()->getName())) {
                        if ($scope) {
                            $isScope = 'is' . ucfirst($scope);
                            if (!$method->$isScope()) {
                                continue;
                            }
                        }
                        $methods[] = $method->getClosure($this);
                    }
                }
            }
        }
        
        return $methods;
    }
    
    /**
     * Any method that ends with "Filters" can return an array of
     * filters. This is useful for traits that require to add some filters,
     * so they don't have to be manually called by the class implementing the trait.
     */
    private function getOtherFilters()
    {
        $filters = [];
        
        foreach ($this->selfRefl->getMethods() as $method) {
            $methodName = $method->getName();
            if (
                strpos($methodName, 'Filters') === strlen($methodName) - 7
                && strpos($method->getDeclaringClass()->getName(), 'Rails') !== 0
            ) {
                $filters = array_merge($filters, $this->$methodName());
            }
        }
        
        return $filters;
    }
    
    private function isApplicationController($class)
    {
        return
            strpos($class, self::APP_CONTROLLER_CLASS) ===
            (strlen($class) - strlen(self::APP_CONTROLLER_CLASS));
    }
}
