<?php
namespace Rails\ActiveRecord\Connection\Pdo;

use Pdo;

class DsnBuilder
{
    protected $dsn;
    
    static public function build(array $config)
    {
        return (new static($config))->dsn();
    }
    
    public function __construct(array $config)
    {
        $config = array_merge($this->defaultConfig(), $config);
        
        if (!$this->dsn = $this->buildDsn($config)) {
            throw new Exception\ErrorException(
                "Not enough info to create DSN string"
            );
        }
    }
    
    public function dsn()
    {
        return $this->dsn;
    }
    
    // public function createConnection()
    // {
        // $config         = $this->config;
        // $this->resource = new PDO($config['dsn'], $config['username'], $config['password'], $config['driver_options']);
        
        // if ($config['pdo_attributes']) {
            // foreach ($config['pdo_attributes'] as $attr => $val) {
                // $this->resource->setAttribute($attr, $val);
            // }
        // }
        
        // return $this->resource;
    // }
    
    protected function buildDsn($config)
    {
        if (!isset($config['adapter'])) {
            return;
        }
        
        switch ($config['adapter']) {
            case 'mysql':
                $str = $config['adapter'];
                
                $params = [];
                if (isset($config['host'])) {
                    $params['host'] = $config['host'];
                }
                if (isset($config['database'])) {
                    $params['dbname'] = $config['database'];
                }
                if (isset($config['port'])) {
                    $params['port'] = $config['port'];
                }
                if (isset($config['charset'])) {
                    $params['charset'] = $config['charset'];
                }
                if (!isset($config['dsn_params'])) {
                    $config['dsn_params'] = [];
                }
                
                $params = array_merge($params, $config['dsn_params']);
                
                if ($params) {
                    $str .= ':';
                    foreach ($params as $key => $value) {
                        $str .= $key . '=' . $value . ';';
                    }
                }
                
                return $str;
            
            case 'sqlite':
                $str = $config['adapter'];
                /**
                 * Full path to database file.
                 */
                if (isset($config['database'])) {
                    $str .= ':' . $config['database'];
                }
                return $str;
        }
    }
    
    protected function defaultConfig()
    {
        return [
            'username'       => null,
            'password'       => null,
            'driver_options' => [],
            'pdo_attributes' => []
        ];
    }
}
