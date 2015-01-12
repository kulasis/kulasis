<?php

namespace Kula\Core\Component\Schema;

use Kula\Core\Component\Cache\DBCacheConfig;
use Kula\Core\Component\Schema\Table;

class Schema {
  
  private $tables = array();
  private $fields = array();
  
  private $db;
  
  public function __construct($db) {
    $this->db = $db;
  }
  
  public function loadTables() {
    
    $tableResults = $this->db->db_select('CORE_SCHEMA_TABLES', 'tables')
      ->fields('tables')
      ->execute();
    while ($tableRow = $tableResults->fetch()) {
      
      $this->tables[$tableRow['TABLE_NAME']] = new Table($tableRow['TABLE_NAME'], $tableRow['SCHEMA_TABLE_ID'], $tableRow['DB_TABLE_NAME'], $tableRow['SCHEMA_CLASS'], $tableRow['TIMESTAMPS']);
    }
    
  }
  
  private function loadFields() {
    
    
    
  }
  
  public function __sleep() {
    $this->db = null;
    
    return array('tables', 'fields');
  }
  
}