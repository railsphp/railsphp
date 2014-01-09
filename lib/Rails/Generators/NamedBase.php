<?php
namespace Rails\Generators;

abstract class NamedBase
{
    protected $fileName;
    
    abstract protected function template();
    
    abstract protected function filePath();
    
    public function __construct($fileName)
    {
        $this->fileName = $fileName;
    }
    
    protected function createFile()
    {
        ob_start();
        require $this->template();
        $contents = ob_get_clean();
        
        $this->createPath();
        
        file_put_contents($this->fullFilePath(), $contents);
    }

    protected function phpOpenTag()
    {
        return "<?php\n";
    }
    
    protected function createPath()
    {
        $fullPath = dirname($this->fullFilePath());
        
        if (!is_dir($fullPath)) {
            mkdir($fullPath, 0755, true);
        }
    }
    
    protected function fullFilePath()
    {
        return \Rails::root() . '/' . $this->filePath();
    }
}
