<?php
namespace Rails\ActiveRecord\Migration;

use Rails;
use Rails\ActiveRecord\Schema\SchemaMigration as STable;

class Migrator
{
    protected $connection;
    
    protected $migrationsTableName = 'schema_migrations';
    
    public function __construct()
    {
        $this->connection = \Rails\ActiveRecord\ActiveRecord::connection();
    }
    
    /**
     * db:migrate
     */
    public function run()
    {
        $this->ensureMigrationsTable();
        STable::setConnection($this->connection);
        STable::setTableName($this->migrationsTableName);
        
        $pending = $this->getPendingMigrations();
        
        foreach ($pending as $version) {
            $this->runUp($version);
        }
    }
    
    public function runSeeds()
    {
        if ($this->pendingMigrations()) {
            return;
        }
        $file = Rails::root() . '/db/seeds.php';
        if (is_file($file)) {
            require $file;
        }
    }
    
    public function runUp($version)
    {
        $patt = $this->migrationsDir() . '/' . $version . '_*.php';
        $files = glob($patt);
        
        if (!$files) {
            throw new Exception\RuntimeException(
                sprintf("Migration file for version %s not found", $version)
            );
        }
        
        $file = $files[0];
        require $file;
        
        $classes = get_declared_classes();
        $className = array_pop($classes);
        unset($classes);
        
        $migrator = new $className();
        $migrator->up();
        
        STable::create([
            'version' => $version
        ]);
    }
    
    public function loadSchema()
    {
        $file = Rails::root() . '/db/schema.sql';
        
        $dumper = new \Rails\ActiveRecord\Schema\Dumper(
            $this->connection
        );
        
        $dumper->import($file);
    }
    
    protected function getPendingMigrations()
    {
        $ranVersions = STable::all()->getAttributes('version');
        $availableVersions = $this->getAvailableMigrations();
        return array_diff($availableVersions, $ranVersions);
    }
    
    protected function ensureMigrationsTable()
    {
        if (!$this->connection->tableExists($this->migrationsTableName)) {
            $schema = new Rails\ActiveRecord\Schema\Schema($this->connection);
            $schema->createTable($this->migrationsTableName, ['id' => false], function($t) {
                $t->string('version');
            });
            $schema->addIndex($this->migrationsTableName, 'version', ['unique' => true]);
        }
    }
    
    protected function setConnection(\Rails\ActiveRecord\Connection $connection)
    {
        $this->connection = $connection;
    }
    
    protected function getAvailableMigrations()
    {
        $path = $this->migrationsDir();
        $files = glob($path . '/*.php');
        $versions = [];
        foreach ($files as $file) {
            $file = pathinfo($file, PATHINFO_BASENAME);
            preg_match('/^(\d+)/', $file, $m);
            $versions[] = $m[1];
        }
        return $versions;
    }
    
    protected function migrationsDir()
    {
        return Rails::root() . '/db/migrate';
    }
}
