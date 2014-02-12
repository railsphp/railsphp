<?php
namespace Rails\Routing\Route;

class Matcher
{
    /**
     * $path must not include query string.
     */
    public function match(Route $route, $path, $verb)
    {
        $route->build();
        
        /**
         * Normalize path. Same functionality is found
         * in Route#normalizePath.
         */
        $normalizedPath = trim($path, '/') ?: '/';
        if (substr($normalizedPath, 0, 1) != '/') {
            $normalizedPath = '/' . $normalizedPath;
        }
        
        if (!in_array(strtolower($verb), $route->verbs())) {
            return false;
        }
        
        $regex = $route->pathRegex();
        
        if (!preg_match($regex, $normalizedPath, $m)) {
            return false;
        }
        
        array_shift($m);
        
        $vars      = $route->vars();
        $params    = [];
        $varsNames = array_keys($vars);
        
        foreach ($m as $k => $value) {
            if (isset($vars[$varsNames[$k]]['constraint'])) {
                if (substr($vars[$varsNames[$k]]['constraint'], 0, 1) == '/') {
                    if (!preg_match($vars[$varsNames[$k]]['constraint'], $value)) {
                        return false;
                    }
                } else {
                    if ($value !== (string)$vars[$varsNames[$k]]['constraint']) {
                        return false;
                    }
                }
            }
            $params[$varsNames[$k]] = $value;
        }
        
        /**
         * Fill missing route variables with their
         * default values, if any.
         */
        $missingVars = array_diff($varsNames, array_keys($params));
        if ($missingVars && $route->defaults()) {
            foreach ($route->defaults() as $varName => $value) {
                if (in_array($varName, $missingVars)) {
                    $params[$varName] = $value;
                }
            }
        }
        
        return $params;
    }
}
