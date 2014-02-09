<?php
namespace Rails\Routing;

use Rails\Routing\Route\Exception;

class Route
{
    const WILDCARD_PATH = '/\*([^\/\)]+)\)?$/';
    
    const ANCHOR_CHARACTERS_REGEX = '/\A(\\A|\^)|(\\Z|\\z|\$)\Z/';
    
    static protected $IGNORE_OPTIONS = [
        'to', 'as', 'via', 'on', 'constraints', 'defaults', 'only',
        'except', 'anchor', 'shallow', 'shallowPath', 'shallowPrefix',
        'format'
    ];
    
    static protected $URL_OPTIONS = [
        'protocol', 'subdomain', 'domain', 'host', 'port'
    ];
    
    protected $path;
    
    protected $alias;
    
    protected $to;
    protected $controller;
    protected $action;
    # or:
    protected $endPoint;
    
    protected $pathRegex;
    
    # Allowed http verbs for this route.
    protected $verbs = [];
    
    # It's turned to array later.
    protected $constraints;
    
    protected $defaults = [];
    
    protected $vars = [];
    
    protected $varNames = [];
    
    protected $requirements = [];
    
    /**
     // * format => true is added automatically if
     // * format is missing.
     */
    protected $options = [];
    
    protected $scope = [];
    
    # It's turned to array later.
    protected $segmentKeys;
    
    /**
     * Avoiding __construct because of __set_state().
     * So create a route object, then call initialize() passing the params.
     */
    public function initialize(
        $path, $to,          /*$alias, */array $options = [], array $scope = []
        // array $verbs,       /*array $namespaces,*/
        // , array $constraints
        // , array $defaults
    ) {
        
        $this->path = $path;
        $this->to = $to;
        $this->options = $options;
        $this->scope = $scope;
        // $this->constraints = $constraints;
        // $this->namespaces = $namespaces;
    }
    
    public function path()
    {
        return $this->path();
    }
    
    public function pathRegex()
    {
        return $this->pathRegex;
    }
    
    public function build()
    {
        $this->normalizePath();
        $this->extractVars();
        $this->normalizeOptions();
        $this->normalizeDefaults();
        $this->normalizeRequirements();
        $this->generatePathRegex();
    }
    
    protected function generatePathRegex()
    {
        // vd($this->path);
        // $pathRegex = $this->path;
        // $pathRegex = preg_quote($this->path, '/');
        $pathRegex = str_replace(array(
            '(',
            ')',
            '.',
            '/',
        ), array(
            '(?:',
            ')?',
            '\.',
            '\/'
        ), $this->path);
        
        // vd($pathRegex);
        $repls = $subjs = array();
        // vpe($this->requirements);
        foreach ($this->vars as $name => $var) {
            if ($var['constraint']) {
                if ($this->isRegexp($var['constraint'])) {
                    $repl = '(' . trim($var['constraint'], '/') . ')';
                } else {
                    $repl = '(' . preg_quote($var['constraint']) . ')';
                }
            } else {
            
            // if ($var['constraint']) {
                // vpe($var);
            // }
                if ($name == 'format')
                    $repl = '([a-zA-Z0-9]{1,5})';
                elseif ($var['type'] == '*') {
                    $repl = '(.*?)';
                // elseif ($var['constraint']) {
                    // if (substr($var['constraint'], 0, 1) == '/') {
                        // $repl = $var['constraint'];
                    // } else {
                        // $repl = '(' . $var['constraint'] . ')';
                    // }
                } else {
                    $repl = '([^\/\.]+?)';
                }
            }
            $repls[] = $repl;
            $type    = '\\' . $var['type'];
            $subjs[] = '/' . $type . $name . '/';
        }
        
        $pathRegex = preg_replace($subjs, $repls, $pathRegex);
        // $this->pathRegex = '/' . preg_quote($pathRegex, '/') . '/';
        // vde($pathRegex, $subjs, $repls);
        $this->pathRegex = '/\A' . $pathRegex . '\Z/';
    }
    
    protected function normalizeOptions()
    {
        // if (!isset($this->options['format'])) {
            // $this->options['format'] = true;
        // }
        
        $pathWithoutFormat = str_replace('/\(\.:format\)$/', '', $this->path);
        
        if (
            preg_match(self::WILDCARD_PATH, $pathWithoutFormat, $m) &&
            (!isset($this->options['format']) || $this->options['format'])
        ) {
            if (!isset($options[$m[1]])) {
                $this->options[$m[1]] = '/.+?/';
            }
        }
        
        if (is_int(strpos($pathWithoutFormat, ':controller'))) {
            if (!empty($scope['module'])) {
                throw new Exception\InvalidArgumentError(
                    ":controller segment is not allowed within a namespace block"
                );
            }
            
            if (!isset($this->options[$m[1]])) {
                $this->options['controller'] = '/.+?/';
            }
        }
        
        $this->options = array_merge($this->options, $this->defaultControllerAndAction());
    }
    
    protected function normalizePath()
    {
        if (!trim($this->path)) {
            throw new Exception\InvalidArgumentError(
                "Path can't be empty"
            );
        }
        $this->path = trim($this->path, '/') ?: '/';
        if (substr($this->path, 0, 1) != '/') {
            $this->path = '/' . $this->path;
        }
        
        if (!empty($options['format'])) {
            $this->path .= '.:format';
        } elseif ($this->optionalFormat()) {
            $this->path .= '(.:format)';
        }
    }
    
    protected function normalizeDefaults()
    {
        $defaults = [];
        
        if (isset($this->scope['defaults'])) {
            $defaults = $this->scope['defaults'];
        }
        if (isset($this->options['defaults'])) {
            $defaults = array_merge($defaults, $this->options['defaults']);
        }
        
        foreach ($this->options as $key => $default) {
            if (in_array($default, self::$IGNORE_OPTIONS) || !is_scalar($default) || substr($default, 0, 1) == '/') {
                continue;
            }
            $defaults[$key] = $default;
        }
        
        if (isset($this->options['constraints'])) {
            foreach ($this->options['constraints'] as $key => $default) {
                if (!in_array($default, self::$URL_OPTIONS)) {
                    $defaults[$key] = (string)$default;
                }
            }
        }
        
        if (isset($this->options['format'])) {
            if ($this->isRegexp($this->options['format'])) {
                $defaults['format'] = null;
            } else {
                $defaults['format'] = $this->options['format'];
            }
        }
        $this->defaults = $defaults;
    }
    
    protected function isRegexp($string)
    {
        return substr($string, 0, 1) == '/';
    }
    
    protected function normalizeRequirements()
    {
        $requirements = [];
        
        foreach ($this->constraints() as $key => $requirement) {
            if (in_array($key, $this->segmentKeys()) || $key == 'controller') {
                continue;
            }
            if ($this->isRegexp($requirement)) {
                $this->verifyRegexpRequirement($requirement);
            }
            $requirements[$key] = $requirement;
        }
        
        if (isset($this->options['format'])) {
            if ($this->options['format']) {
                if (empty($requirements['format'])) {
                    $requirements['format'] = '/.+';
                }
            } elseif ($this->isRegexp($this->options['format'])) {
                $requirements['format'] = $this->options['format'];
            } else {
                $requirements['format'] = '/' . preg_quote($this->options['format']) . '/';
            }
        }
        
        $this->requirements = $requirements;
    }
    
    protected function verifyRegexpRequirement($requirement)
    {
        if (preg_match(self::ANCHOR_CHARACTERS_REGEX, $requirement)) {
            throw new Exception\InvalidArgumentException(
                sprintf(
                    "Regexp anchor characters are not allowed in routing requirements: %s",
                    $requirement
                )
            );
        }
    }
    
    protected function constraints()
    {
        if (null === $this->constraints) {
            $constraints = [];
            if (isset($this->scope['constraints'])) {
                $constraints = $this->scope['constraints'];
            }
            foreach (array_diff_key($this->options, self::$IGNORE_OPTIONS) as $key => $option) {
                /**
                 * Only add those option values that are regexp, i.e.
                 * that start with an slash.
                 */
                if (is_scalar($option) && strpos((string)$option, 0, 1) == '/') {
                    $constraints[$key] = $option;
                }
            }
            
            if (!empty($this->options['constraints'])) {
                $constraints = array_merge($constraints, $this->options['constraints']);
            }
            
            $this->constraints = $constraints;
        }
        return $this->constraints;
    }
    
    protected function segmentKeys()
    {
        if (null === $this->segmentKeys) {
            $keys = [];
            foreach ($this->vars as $name => $var) {
                if (!$var['optional']) {
                    $keys[] = $name;
                }
            }
            $this->segmentKeys = $keys;
        }
        return $this->segmentKeys;
    }
    
    protected function optionalFormat()
    {
        return (!isset($this->options['format']) || $this->options['format']) &&
               is_bool(strpos($this->path, ':format')) &&
               substr($this->path, -1) != '/';
    }
    
    protected function defaultControllerAndAction()
    {
        if (is_callable($this->to)) {
            return [];
        } else {
            $token = new PathToken($this->to);
            $controller = $token->controller();
            $action     = $token->action();
            
            return ['controller' => $controller, 'action' => $action];
        }
    }
    
    private function extractVars($path = null)
    {
        $vars = array();
        if ($path === null) {
            $path = $this->path;
        } else {
             $vars['part'] = $path;
            # Remove parentheses.
            $path = substr($path, 1, -1);
        }
        
        $parts    = $this->extractGroups($path);
        $path = str_replace($parts, '%', $path);
        
        if (preg_match_all('/(\*|:)(\w+)/', $path, $ms)) {
            foreach ($ms[1] as $k => $type) {
                $var_name = $ms[2][$k];
                
                # Get constraint from properties.
                $constraint = isset($this->constraints()[$var_name])    ?
                                (string)$this->constraints()[$var_name] :
                                null;
                
                # Get default from properties.
                $default = isset($this->defaults[$var_name]) ? $this->defaults[$var_name] : null;
                
                $this->varsNames[] = $var_name;
                
                $this->vars[$var_name] = array(
                    'type'        => $type,
                    'constraint'  => $constraint,
                    'default'     => $default,
                    // 'value'       => null,
                    'optional'    => false
                );
            }
            $vars[] = $ms[2];
        } else
            $vars[] = [];
        
        foreach ($parts as $subpart) {
            if ($c = $this->extractVars($subpart))
                $vars[] = $c;
        }
        return $vars;
    }

    private function extractGroups($str)
    {
        $parts = array();
        while ($part = $this->extractGroup($str)) {
            $str = substr($str, strpos($str, $part) + strlen($part));
            $parts[] = $part;
        }
        return $parts;
    }
    
    private function extractGroup($str)
    {
        if (false === ($pos = strpos($str, '(')))
            return;
        $group = '';
        $open = 1;
        while ($open > 0) {
            $str = substr($str, $pos+1);
            $close_pos = strpos($str, ')');
            $part = substr($str, 0, $close_pos+1);
            if (is_bool(strpos($part, '('))) {
                $pos = $close_pos;
                $group .= $part;
                $open--;
            } else {
                $pos = strpos($str, '(');
                $group .= substr($str, 0, $pos+1);
                $open++;
            }
        }
        return '(' . $group;
    }
}
