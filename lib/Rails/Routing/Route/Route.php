<?php
namespace Rails\Routing\Route;

use Rails\Routing\PathToken;

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
    
    protected $requirements = [];
    
    /**
     * Valid options:
     *
     * alias => string
     *  A name for this route.
     *
     * verbs => array
     *  HTTP verbs that work with this route.
     *
     * format => boolean|string
     *  If format is null or missing, the route var "format" is optional in the URL.
     *  If false, the var "format" isn't generated.
     *  If true or string, format is required and must pass constraint validation.
     *  If a string, it's taken as a constraint:
     *    If starting with '/' is taken as a regexp.
     *    Otherwise, it's taken as a literal constraint.
     * So passing a string as value is the same as passing 'format' => $str to the
     * 'constraints' option.
     *
     * constraints => array
     *  Holds constraints "validations" for route vars. For example, passing 'id' => '/\d+/'
     *  to a route like '/post/:id' will allow only numbers as id. Passing a string
     *  that does not start with '/' is taken as a literal constraint.
     *
     * defaults => array
     *  Set default values to route vars if they missing. Would work only with
     *  optional vars.
     */
    protected $options = [];
    
    protected $scope = [];
    
    # It's turned to array later.
    protected $segmentKeys;
    
    /**
     * Flag to avoid "building" the route more than once.
     *
     * @see build()
     */
    protected $isBuilt = false;
    
    /**
     * Avoiding __construct because of __set_state().
     * So create a route object, then call initialize() passing the params.
     */
    public function initialize(
        $path, $to,
        array $options = [], array $scope = []
    ) {
        
        $this->path = $path;
        $this->to   = $to;
        $this->options = $options;
        $this->scope = $scope;
    }
    
    public function path()
    {
        return $this->path();
    }
    
    public function pathRegex()
    {
        return $this->pathRegex;
    }
    
    public function alias()
    {
        return $this->options['alias'];
    }
    
    public function verbs()
    {
        return $this->options['verbs'];
    }
    
    public function vars()
    {
        return $this->vars;
    }
    
    public function defaults()
    {
        return $this->defaults;
    }
    
    public function build()
    {
        if (!$this->isBuilt) {
            $this->normalizeOptions();
            $this->normalizePath();
            $this->extractVars();
            $this->normalizeDefaults();
            $this->normalizeRequirements();
            $this->generatePathRegex();
            $this->isBuilt = true;
        }
    }
    
    public function optionalFormat()
    {
        return (!isset($this->options['format']) || $this->options['format']) &&
               is_bool(strpos($this->path, ':format')) &&
               substr($this->path, -1) != '/';
    }
    
    protected function generatePathRegex()
    {
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
        
        $repls = $subjs = array();
        
        foreach ($this->vars as $name => $var) {
            if ($var['constraint']) {
                if ($this->isRegexp($var['constraint'])) {
                    $repl = '(' . trim($var['constraint'], '/') . ')';
                } else {
                    $repl = '(' . preg_quote($var['constraint']) . ')';
                }
            } else {
                if ($var['type'] == '*') {
                    $repl = '(.*?)';
                } else {
                    $repl = '([^\/\.]+?)';
                }
            }
            $repls[] = $repl;
            $type    = '\\' . $var['type'];
            $subjs[] = '/' . $type . $name . '/';
        }
        
        $pathRegex = preg_replace($subjs, $repls, $pathRegex);
        $this->pathRegex = '/\A' . $pathRegex . '\Z/';
    }
    
    protected function normalizeOptions()
    {
        if (!isset($this->options['verbs'])) {
            throw new Exception\InvalidArgumentException(
                "Array option 'verbs' must be present"
            );
        } elseif (!is_array($this->options['verbs'])) {
            throw new Exception\InvalidArgumentException(
                sprintf(
                    "Verbs option must be an array, %s passed",
                    gettype($this->options['verbs'])
                )
            );
        }
        
        if (!isset($this->options['alias'])) {
            $this->options['alias'] = null;
        } elseif (!is_string($this->options['alias'])) {
            throw new Exception\InvalidArgumentException(
                sprintf(
                    "Alias option must be string, %s passed",
                    gettype($this->options['alias'])
                )
            );
        } elseif (!preg_match('/\A\w+\Z/', $this->options['alias'])) {
            throw new Exception\InvalidArgumentException(
                "Alias option must be of format \\w+"
            );
        }
        
        if (!isset($this->options['format'])) {
            $this->options['format'] = null;
        }
        
        $pathWithoutFormat = preg_replace('/\(\.:format\)$/', '', $this->path);
        
        if (
            preg_match(self::WILDCARD_PATH, $pathWithoutFormat, $m) &&
            $this->options['format'] === true
        ) {
            if (!isset($options[$m[1]])) {
                $this->options[$m[1]] = '/.+?/';
            }
        }
        
        if (is_int(strpos($pathWithoutFormat, ':controller'))) {
            if (!empty($this->scope['namespace'])) {
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
        
        if ($this->options['format']) {
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
        
        if (is_string($this->options['format'])) {
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
        
        if ($this->options['format']) {
            if ($this->options['format'] === true) {
                if (empty($requirements['format'])) {
                    $requirements['format'] = '/.+/';
                }
            } elseif (is_string($this->options['format'])) {
                if ($this->isRegexp($this->options['format'])) {
                    $requirements['format'] = $this->options['format'];
                } else {
                    $requirements['format'] = '/' . preg_quote($this->options['format']) . '/';
                }
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
                if (!is_bool($option) && is_scalar($option) && strpos((string)$option, 0, 1) == '/') {
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
    
    protected function extractVars($path = null)
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
                
                $this->vars[$var_name] = array(
                    'type'        => $type,
                    'constraint'  => $constraint,
                    'default'     => $default,
                    'optional'    => false
                );
            }
            $vars[] = $ms[2];
        } else
            $vars[] = [];
        
        foreach ($parts as $subpart) {
            if ($c = $this->extractVars($subpart)) {
                $vars[] = $c;
            }
        }
        return $vars;
    }

    protected function extractGroups($str)
    {
        $parts = array();
        while ($part = $this->extractGroup($str)) {
            $str = substr($str, strpos($str, $part) + strlen($part));
            $parts[] = $part;
        }
        return $parts;
    }
    
    protected function extractGroup($str)
    {
        if (false === ($pos = strpos($str, '('))) {
            return;
        }
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
