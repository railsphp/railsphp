<?php
namespace Rails\ActiveRecord\Adapter\MySql;

use Rails\ActiveRecord\Adapter\AbstractDumper as Base;

class Dumper extends Base
{
    protected $constraints = [];
    
    public function export()
    {
        $tableStmts = [];
        $sql = "SHOW TABLES";
        $tables = $this->connection->select($sql);
        $autoIncrementRegex = '/\s?AUTO_INCREMENT=\d+/';
        
        foreach ($tables as $table) {
            $table = array_shift($table);
            
            $sql = "SHOW CREATE TABLE `" . $table . "`";
            $row = $this->connection->selectRow($sql);
            $stmt = $row['Create Table'];
            
            if ($constraints = $this->extractConstraints($stmt)) {
                $this->constraints[$table] = $constraints;
            }
            
            # Remove Auto increment attribute.
            $stmt = preg_replace($autoIncrementRegex, '', $stmt);
            
            # Add trailing semicolon to table statements.
            $tableStmts[] = $stmt . ';';
        }
        
        $constraintStmts = [];
        foreach ($this->constraints as $tableName => $statements) {
            $sql = $statements;
            $constraintStmts[] = "ALTER TABLE `" . $tableName . "`\n  " . implode(",\n  ", $sql) . ';';
        }
        
        $sql = "SHOW TRIGGERS";
        $triggers = $this->connection->select($sql);
        
        if ($triggers) {
            $triggerStmts = [
                "DELIMITER //"
            ];
            
            foreach ($triggers as $trg) {
                $sql = [];
                $sql[] = "CREATE TRIGGER `" . $trg['Trigger']
                        . "` " . $trg['Timing'] . " "
                        . $trg['Event'] . ' ON `' . $trg['Table'] . '`';
                $sql[] = "FOR EACH ROW";
                $sql[] = $trg['Statement'];
                $sql[] = '//';
                $triggerStmts[] = implode("\n", $sql);
            }
            
            $triggerStmts[] = "DELIMITER ;";
        } else {
            $triggerStmts = [];
        }
        
        $dump = '';
        $dump .= implode("\n\n", $tableStmts);
        $dump .= "\n\n";
        $dump .= implode("\n\n", $constraintStmts);
        $dump .= "\n\n";
        $dump .= implode("\n\n", $triggerStmts);
        
        return $dump;
    }
    
    /**
     * Imports contents from a sql file created by export().
     */
    public function import($sql)
    {
        $queries   = [];
        $query     = [];
        $delimiter = '';
        
        foreach (explode("\n", $sql) as $line) {
            if ($delimiter) {
                if ($line == $delimiter) {
                    $queries[] = implode("\n", $query);
                    $query = [];
                } else {
                    $query[] = $line;
                }
            } elseif (preg_match('/^\s*DELIMITER ([^\;].*?)$/m', $line, $m)) {
                $queries[] = implode("\n", $query);
                $query = [];
                $delimiter = $m[1];
            } elseif ($line == 'DELIMITER ;') {
                $delimiter = '';
            } else {
                $query[] = $line;
                
                if (substr($line, -1) == ';') {
                    $queries[] = implode("\n", $query);
                    $query = [];
                }
            }
        }
        
        foreach ($queries as $query) {
            $this->connection->executeSql($query);
        }
    }
    
    protected function extractConstraints(&$stmt)
    {
        $regex = '/^(\s+?CONSTRAINT .*)/m';
        preg_match_all($regex, $stmt, $ms);
        $lines = [];
        
        if ($ms[0]) {
            foreach (array_reverse($ms[1]) as $m) {
                $line = "ADD " . trim(trim($m, ','));
                $lines[] = $line;
                
                # Remove constraint statement from create table statement.
                $stmt = str_replace("\n" . $m, '', $stmt);
            }
            $lines = array_reverse($lines);
            
            # Remove trailing comma from table statement
            $stmt = preg_replace('/,(\n\).*)$/', '\1', $stmt);
        }
        
        return $lines;
    }
}
