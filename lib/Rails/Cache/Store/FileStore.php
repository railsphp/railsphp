<?php
namespace Rails\Cache\Store;

use Rails;
use Rails\Toolbox;
use Rails\Cache\Store\FileStore\Entry;

/**
 * This class is intended to be used only by Rails.
 */
class FileStore extends AbstractStore
{
    protected $basePath;
    
    public function __construct(array $config)
    {
        $this->basePath = $config[0];
    }
    
    public function read($key, array $params = [])
    {
        return $this->getEntry($key, $params)->read();
    }
    
    public function write($key, $value, array $params)
    {
        return $this->getEntry($key, $params)->write($value);
    }
    
    public function delete($key, array $params)
    {
        return $this->getEntry($key, $params)->delete();
    }
    
    public function exists($key, array $params)
    {
        return $this->getEntry($key, $params)->fileExists();
    }
    
    /**
     * Removes cache files from directory.
     */
    public function deleteDirectory($dirname)
    {
        $dirpath = $this->path() . '/' . $dirname;
        
        if (is_dir($dirpath)) {
            ToolboxºFileTools::emptyDir($dirpath);
        }
    }
    
    public function basePath()
    {
        return $this->basePath;
    }
    
    public function cleanup(array $options = [])
    {
        $files = glob($this->basePath . '/*');
        foreach ($files as $path) {
            $key = urldecode(end(explode(DIRECTORY_SEPARATOR, $path)));
            $entry = $this->getEntry($key, []);
            if ($entry->expired()) {
                $entry->delete();
            }
        }
    }
    
    public function clear(array $options = [])
    {
        Toolbox\FileTools::emptyDir($this->basePath);
    }
    
    public function deleteMatched($matcher, array $options = [])
    {
        $allFiles = Toolbox\FileTools::searchFile($this->basePath);
        foreach ($allFiles as $file) {
            $key = urldecode(substr($file, strrpos($file, DIRECTORY_SEPARATOR) + 1));
            if (preg_match($matcher, $key)) {
                $this->getEntry($key, [])->delete();
            }
        }
    }
    
    protected function getEntry($key, array $params)
    {
        return new Entry($key, $params, $this);
    }
}
