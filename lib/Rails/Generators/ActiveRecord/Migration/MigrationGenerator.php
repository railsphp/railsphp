<?php
namespace Rails\Generators\ActiveRecord\Migration;

use Rails\Generators\NamedBase;

class MigrationGenerator extends NamedBase
{
    protected $migrationClassName;
    
    static public function generate($name)
    {
        $g = new self($name);
        $g->createFile();
    }
    
    public function createFile()
    {
        $inflector = \Rails::services()->get('inflector');
        $this->migrationClassName = $inflector->camelize($this->fileName);
        
        $this->fileName = gmdate('YmdHis') . '_' . $this->fileName;
        
        parent::createFile();
    }
    
    protected function template()
    {
        return __DIR__ . '/templates/migration.php';
    }
    
    protected function filePath()
    {
        return 'db/migrate/' . $this->fileName . '.php';
    }
}
