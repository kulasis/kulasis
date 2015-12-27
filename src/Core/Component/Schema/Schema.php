<?php

namespace Kula\Core\Component\Schema;

use Kula\Core\Component\Cache\DBCacheConfig;
use Kula\Core\Component\Schema\Table;
use Kula\Core\Component\Schema\Field;

class Schema {
  
  private $tables = array();
  private $fields = array();
  private $db_tables = array();
  
  private $db;
  private $cache;
  
  public function __construct($db, $cache) {
    $this->db = $db;
    $this->cache = $cache;
  }
  
  public function setCache($cache) {
    $this->cache = $cache;
  }
  
  public function loadTables() {
    
    $tableResults = $this->db->db_select('CORE_SCHEMA_TABLES', 'tables')
      ->fields('tables')
      ->leftJoin('CORE_SCHEMA_FIELDS', 'fields', 'fields.SCHEMA_TABLE_ID = tables.SCHEMA_TABLE_ID AND fields.DB_COLUMN_PRIMARY = 1')
      ->fields('fields', array('FIELD_NAME', 'DB_COLUMN_NAME'))
      ->execute();
    while ($tableRow = $tableResults->fetch()) {
      $table = new Table($tableRow['TABLE_NAME'], $tableRow['SCHEMA_TABLE_ID'], $tableRow['DB_TABLE_NAME'], $tableRow['SCHEMA_CLASS'], $tableRow['TIMESTAMPS']);
      $table->setPrimary($tableRow['FIELD_NAME'], $tableRow['DB_COLUMN_NAME']);
      
      $this->cache->add('schema.'.$tableRow['TABLE_NAME'], $table);
      
      //$this->tables[$tableRow['TABLE_NAME']] = $table;
      //$this->db_tables[$tableRow['DB_TABLE_NAME']] = $table;
      unset($table);
    }
    
  }
  
  public function loadFields() {
    
    $fieldResults = $this->db->db_select('CORE_SCHEMA_FIELDS', 'fields')
      ->fields('fields')
      ->join('CORE_SCHEMA_TABLES', 'tables', 'tables.SCHEMA_TABLE_ID = fields.SCHEMA_TABLE_ID')
      ->fields('tables', array('TABLE_NAME'))
      ->leftJoin('CORE_SCHEMA_FIELDS', 'parentfield', 'parentfield.SCHEMA_FIELD_ID = fields.PARENT_SCHEMA_FIELD_ID')
      ->fields('parentfield', array('FIELD_NAME' => 'parentfield_FIELD_NAME'))
      ->leftJoin('CORE_SCHEMA_FIELDS', 'updatefield', 'updatefield.SCHEMA_FIELD_ID = fields.UPDATE_FIELD_ID')
      ->fields('updatefield', array('FIELD_NAME' => 'updatefield_FIELD_NAME'))
      ->execute();
    while ($fieldRow = $fieldResults->fetch()) {
      
      //$this->fields[$fieldRow['FIELD_NAME']] = new Field($fieldRow['TABLE_NAME'], $fieldRow['FIELD_NAME'], $fieldRow['SCHEMA_FIELD_ID'], $fieldRow['DB_COLUMN_NAME'], $fieldRow['DB_COLUMN_TYPE'], $fieldRow['DB_COLUMN_LENGTH'], $fieldRow['DB_COLUMN_PRECISION'], $fieldRow['DB_COLUMN_NULL'], $fieldRow['DB_COLUMN_DEFAULT'], $fieldRow['DB_COLUMN_PRIMARY'], $fieldRow['parentfield_FIELD_NAME'], $fieldRow['FIELD_NAME'], $fieldRow['FIELD_TYPE'], $fieldRow['FIELD_SIZE'], $fieldRow['FIELD_COLUMN_LENGTH'], $fieldRow['FIELD_ROW_HEIGHT'], $fieldRow['CLASS'], $fieldRow['LOOKUP'], $fieldRow['CHOOSER'], $fieldRow['COLUMN_NAME'], $fieldRow['LABEL_NAME'], $fieldRow['LABEL_POSITION'], $fieldRow['updatefield_FIELD_NAME']);
      
      $this->cache->add('schema.'.$fieldRow['FIELD_NAME'], new Field($fieldRow['TABLE_NAME'], $fieldRow['FIELD_NAME'], $fieldRow['SCHEMA_FIELD_ID'], $fieldRow['DB_COLUMN_NAME'], $fieldRow['DB_COLUMN_TYPE'], $fieldRow['DB_COLUMN_LENGTH'], $fieldRow['DB_COLUMN_PRECISION'], $fieldRow['DB_COLUMN_NULL'], $fieldRow['DB_COLUMN_DEFAULT'], $fieldRow['DB_COLUMN_PRIMARY'], $fieldRow['parentfield_FIELD_NAME'], $fieldRow['FIELD_NAME'], $fieldRow['FIELD_TYPE'], $fieldRow['FIELD_SIZE'], $fieldRow['FIELD_COLUMN_LENGTH'], $fieldRow['FIELD_ROW_HEIGHT'], $fieldRow['CLASS'], $fieldRow['LOOKUP'], $fieldRow['CHOOSER'], $fieldRow['COLUMN_NAME'], $fieldRow['LABEL_NAME'], $fieldRow['LABEL_POSITION'], $fieldRow['updatefield_FIELD_NAME']));
      
      //$this->tables[$fieldRow['TABLE_NAME']]->addField($this->fields[$fieldRow['FIELD_NAME']]);
      
    }
    
  }
  
  public function getField($fieldName) {
    //return $this->fields[$fieldName];
    return $this->cache->get('schema.'.$fieldName);
  }
  
  public function getTable($tableName) {
    //return $this->tables[$tableName];
    return $this->cache->get('schema.'.$tableName);
  }
  
  public function getClass($fieldName) {
    //return $this->fields[$fieldName]->getClass();
    return $this->cache->get('schema.'.$fieldName)->getClass();
  }
  
  public function getFieldType($fieldName) {
    return $this->cache->get('schema.'.$fieldName)->getFieldType();
    //return $this->fields[$fieldName]->getFieldType();
  }
  
  public function getDBTable($tableName) {
    return $this->cache->get('schema.'.$tableName)->getDBName();
    //return $this->tables[$tableName]->getDBName();
  }
  
  public function getDBField($fieldName) {
    return $this->cache->get('schema.'.$fieldName)->getDBName();
    //return $this->fields[$fieldName]->getDBName();
  }
  
  public function getDBPrimaryColumnForTable($tableName) {
    return $this->cache->get('schema.'.$tableName)->getDBPrimaryColumnName();
    //return $this->tables[$tableName]->getDBPrimaryColumnName();
  }
  
  public function getDBPrimaryColumnForDBTable($tableName) {
    return $this->cache->get('schema.'.$tableName)->getDBPrimaryColumnName();
    //return $this->db_tables[$tableName]->getDBPrimaryColumnName();
  }
  
  public function __sleep() {
    $this->db = null;
    
    return array('tables', 'fields', 'db_tables');
  }
  
}