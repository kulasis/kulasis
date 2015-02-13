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
  
  public function __construct($db) {
    $this->db = $db;
  }
  
  public function loadTables() {
    
    $tableResults = $this->db->db_select('CORE_SCHEMA_TABLES', 'tables')
      ->fields('tables')
      ->execute();
    while ($tableRow = $tableResults->fetch()) {
      $table = new Table($tableRow['TABLE_NAME'], $tableRow['SCHEMA_TABLE_ID'], $tableRow['DB_TABLE_NAME'], $tableRow['SCHEMA_CLASS'], $tableRow['TIMESTAMPS']);
      
      $this->tables[$tableRow['TABLE_NAME']] = $table;
      $this->db_tables[$tableRow['DB_TABLE_NAME']] = $table;
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
      
      $this->fields[$fieldRow['FIELD_NAME']] = new Field($this->tables[$fieldRow['TABLE_NAME']], $fieldRow['FIELD_NAME'], $fieldRow['SCHEMA_FIELD_ID'], $fieldRow['DB_COLUMN_NAME'], $fieldRow['DB_COLUMN_TYPE'], $fieldRow['DB_COLUMN_LENGTH'], $fieldRow['DB_COLUMN_PRECISION'], $fieldRow['DB_COLUMN_NULL'], $fieldRow['DB_COLUMN_DEFAULT'], $fieldRow['DB_COLUMN_PRIMARY'], $fieldRow['parentfield_FIELD_NAME'], $fieldRow['FIELD_NAME'], $fieldRow['FIELD_TYPE'], $fieldRow['FIELD_SIZE'], $fieldRow['FIELD_COLUMN_LENGTH'], $fieldRow['FIELD_ROW_HEIGHT'], $fieldRow['CLASS'], $fieldRow['LOOKUP'], $fieldRow['CHOOSER'], $fieldRow['COLUMN_NAME'], $fieldRow['LABEL_NAME'], $fieldRow['LABEL_POSITION'], $fieldRow['updatefield_FIELD_NAME']);
      
      $this->tables[$fieldRow['TABLE_NAME']]->addField($this->fields[$fieldRow['FIELD_NAME']]);
      
    }
    
  }
  
  public function getField($fieldName) {
    return $this->fields[$fieldName];
  }
  
  public function getTable($tableName) {
    return $this->tables[$tableName];
  }
  
  public function getClass($fieldName) {
    return $this->fields[$fieldName]->getClass();
  }
  
  public function getClassDB($dbTableName, $dbFieldName) {
    return $this->db_tables[$dbTableName]->getDBField($dbFieldName)->getClass();
  }
  
  public function getFieldType($fieldName) {
    return $this->fields[$fieldName]->getFieldType();
  }
  
  public function getDBTable($tableName) {
    return $this->tables[$tableName]->getDBName();
  }
  
  public function getDBField($fieldName) {
    return $this->fields[$fieldName]->getDBName();
  }
  
  public function getDBPrimaryColumnForTable($tableName) {
    return $this->tables[$tableName]->getDBPrimaryColumnName();
  }
  
  public function getDBPrimaryColumnForDBTable($tableName) {
    return $this->db_tables[$tableName]->getDBPrimaryColumnName();
  }
  
  public function __sleep() {
    $this->db = null;
    
    return array('tables', 'fields', 'db_tables');
  }
  
}