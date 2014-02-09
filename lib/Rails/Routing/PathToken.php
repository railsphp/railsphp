<?php
namespace Rails\Routing;

class PathToken
{
    const SEPARATOR = '#';
    
    const NAMESPACE_SEPARATOR = '\\';
    
    /**
     * Set a route with which tokens containing only the
     * action, like "#create", can be automatically completed
     * with this route's controller.
     *
     * @var object Route
     */
    static protected $route;
    
    protected $controller;
    
    protected $action = 'index';
    
    protected $namespaces = [];
    
    static public function setRoute(Route $route)
    {
        self::$route = $route;
    }
    
    public function __construct($token)
    {
        if (is_int($pos = strpos($token, self::NAMESPACE_SEPARATOR))) {
            if (!$pos) {
                $token = substr($token, 1);
            } else {
                $namespace = substr($token, 0, $pos);
                $token     = substr($token, $pos + 1);
                $this->namespaces = array_filter(explode(self::NAMESPACE_SEPARATOR, $namespace));
            }
        }
        
        if (is_bool(strpos($token, self::SEPARATOR))) {
            throw new Exception\InvalidArgumentException(
                sprintf("Missing separator in token '%s'", $token)
            );
        }
        
        $parts = explode(self::SEPARATOR, $token);
        
        if (empty($parts[0])) {
            if (!self::$route) {
                throw new Exception\RuntimeException(
                    sprintf(
                        "Can't complete path token as there's route set: '%s'",
                        $token
                    )
                );
            }
            $this->namespaces = $route->namespaces();
            $parts[0] = $route->controller();
        }
        $this->controller = $parts[0];
        
        if (!empty($parts[1])) {
            $this->action = $parts[1];
        }
    }
    
    public function parts()
    {
        return array($this->controller, $this->action, $this->namespaces);
    }
    
    public function controller()
    {
        return $this->controller;
    }
    
    public function action()
    {
        return $this->action;
    }
    
    public function namespaces()
    {
        return $this->namespaces;
    }
    
    public function toString()
    {
        $namespace = $this->namespaces ?
                        implode(self::NAMESPACE_SEPARATOR, $this->namespaces) 
                            . self::NAMESPACE_SEPARATOR
                        : '';
        return $namespace . $this->controller . self::SEPARATOR . $this->action;
    }
    
    public function toPath()
    {
        return str_replace(['#', self::NAMESPACE_SEPARATOR], '/', $this->toString());
    }
}
