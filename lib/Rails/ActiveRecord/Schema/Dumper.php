<?php
namespace Rails\ActiveRecord\Schema;

class Dumper
{
    protected $outputFile;
    
    protected $dumper;
    
    public function __construct($connection, $dumper = null, $outputFile = null)
    {
        if (!$dumper) {
            $adapter = strtolower($connection->adapterName());
            
            switch ($adapter) {
                case 'mysql':
                    $dumper = new \Rails\ActiveRecord\Adapter\MySql\Dumper($connection);
                    break;
                
                default:
                    throw new Exception\RuntimeException(
                        sprintf("Unsupported adapter %s", $adapter)
                    );
            }
            
            $this->dumper = $dumper;
        }
        
        $this->connection = $connection;
    }
    
    public function setDumper($dumper)
    {
        $this->dumper = $dumper;
    }
    
    public function export($file)
    {
        $sql = $this->dumper->export();
        file_put_contents($file, $sql);
    }
    
    // public function setOutputFile($path)
    // {
        // $this->outputFile = $path;
    // }
}
