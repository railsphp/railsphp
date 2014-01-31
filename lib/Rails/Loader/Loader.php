<?php
namespace Rails\Loader;

use Closure;
use Rails;
use Rails\Loader\Exception;

class Loader
{
    protected $paths = [];
    
    protected $classAutoloaders = [];
    
    protected $composerAutoloader;
    
    public function __construct($paths = [])
    {
        if ($paths) {
            $this->addPaths($paths);
        }
    }
    
    public function setComposerAutoloader(\Composer\Autoload\ClassLoader $loader)
    {
        $this->composerAutoloader = $loader;
    }
    
    public function addPath($path)
    {
        $this->addPaths((array)$path);
    }
    
    public function addPaths(array $paths)
    {
        $this->paths = array_merge($this->paths, $paths);
    }
    
    public function loadClass($className)
    {
        if ($this->runAutoloaders($className)) {
            return;
        }
        
        if (
            $this->composerAutoloader &&
            $this->composerAutoloader->loadClass($className)
        ) {
            return;
        }
        
        if (is_int(strpos($className, '\\'))) {
            $parts = explode('\\', $className);
            $file  = array_pop($parts);
            $classFilePath = implode(DIRECTORY_SEPARATOR, $parts) . DIRECTORY_SEPARATOR . $file . '.php';
        } else
            $classFilePath = str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
        
        $paths = $this->paths;
        $found = false;
        
        foreach ($paths as $path) {
            if ($found = is_file(($classFile = $path . DIRECTORY_SEPARATOR . $classFilePath))) {
                break;
            }
        }
        
        if (!$found) {
            require_once __DIR__ . '/Exception/FileNotFoundException.php';
            throw new Exception\FileNotFoundException(
                sprintf(
                    "Couldn't find file for class %s, searched for %s in:\n%s",
                    $className,
                    $classFilePath,
                    implode("\n", $paths)
                )
            );
        }
        
        require $classFile;
        
        if (
            !class_exists($className, false)     &&
            !interface_exists($className, false) &&
            !trait_exists($className, false)
        ) {
            require_once __DIR__ . '/Exception/ClassNotFoundException.php';
            throw new Exception\ClassNotFoundException(
                sprintf(
                    "File %s doesn't contain class/interface/trait %s.",
                    $classFile,
                    $className
                )
            );
        }
    }
    
    /**
     * Autoloaders must return true is the class was
     * successfuly loaded.
     */ 
    public function registerAutoloader(callable $function)
    {
        $this->classAutoloaders[] = $function;
    }
    
    public function runAutoloaders($className)
    {
        foreach ($this->classAutoloaders as $autoload) {
            if (is_array($autoload)) {
                $object = $autoload[0];
                $method = $autoload[1];
                
                if (true === $object->$method($className)) {
                    return true;
                }
            } elseif (is_string($autoload)) {
                if (true === call_user_func($autoload, $className)) {
                    return true;
                }
            } elseif ($autoload instanceof Closure) {
                if (true === $autoload($className)) {
                    return true;
                }
            } else {
                require_once __DIR__ . '/Exception/RuntimeException.php';
                throw new Exception\RuntimeException(
                    sprintf("Invalid autoloader type (%s)", gettype($autoload))
                );
            }
        }
        return false;
    }
}
