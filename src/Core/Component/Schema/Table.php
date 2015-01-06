<?php

namespace Kula\Core\Component\Schema;

use Kula\Core\Component\Schema\Field;

class Table {
  
  private $name;
  private $description;
  private $db_tableName;
  private $class;
  private $qualified;
  private $timestamps;
  private $fields = array();
  private $log = array();
  
  public function __construct($bundlePath, $name, $description, $db_tableName, $class = null, $qualified = array(), $timestamps = null) {
    
    $this->name = $name;
    $this->description = $description;
    $this->db_tableName = $db_tableName;
    $this->class = $class;
    $this->qualified = $qualified;
    $this->timestamps = $timestamps;
    
    $this->log($bundlePath, 'Created table object.');
  }
  
  public function addField(Field $field) {
    $fieldName = $field->getName();
    
    if ($fieldName) {
      $this->fields[$fieldName] = $field;
      return true;
    }
    
    return false;
  }
  
  public function getField($fieldName) {
    
    if (isset($this->fields[$fieldName]))
      return $this->fields[$fieldName];
    return false;
  }
  
  public function log($bundlePath, $action) {
    $this->log[] = array('bundle_path' => $bundlePath, 'action' => $action);
  }
  
  public function createTable(\Kula\Core\Component\DB\DB $db, $schema) {
    
    $structure = array(
        'description' => $this->description,
        'fields' => array(),
        'primary key' => array()
    );  
    
    foreach($this->fields as $field) {
      
      $structure['fields'][$field->getDBColumnName()] = array(
        'description' => $field->getDescription(),
        'type' => $field->getDBColumnType(),
        'size' => $field->getDBColumnSize(),
        'length' => $field->getDBColumnLength(),
        'not null' => ($field->getDBColumnNull() == false) ? true : false
      );
      
      if ($field->getPrimary()) {
        $structure['primary key'][] = $field->getDBColumnName();
      }
      
      if ($field->getParent()) {
        // Table is the first part
        $parentTableName = substr($field->getParent(), 0, strrpos($field->getParent(), '.'));
        // Field is the last part
        $parentFieldName = substr($field->getParent(), strrpos($field->getParent(), '.')+1, strlen($field->getParent()));
        
        
        $parentTable = $schema->getTableForSchema($parentTableName);
        $parentField = $schema->getFieldForSchema($parentTableName, $parentFieldName);
        
        
        $structure['foreign keys']['FK_'.$field->getDBColumnName()] = array(
            'table' => $parentTable->db_tableName,
            'columns' => array($field->getDBColumnName() => $parentField->getDBColumnName()),
        );
        
        
        
      }
      
    }  // end foreach on fields
    echo "<pre>";
    print_r($structure);
    echo "</pre>";
    $db->db_create_table($this->db_tableName, $structure);
    
    $this->synchronizeDatabaseCatalog($db);
  }
  
  public function synchronizeDatabaseCatalog(\Kula\Core\Component\DB\DB $db) {
    
    // Check table exists in database
    $catalogTable = $db->db_select('CORE_SCHEMA_TABLES', 'schema_tables')
      ->fields('schema_tables')
      ->condition('TABLE_NAME', $this->name)
      ->execute()->fetch();
    
    $catalogFields = array();
    
    if ($catalogTable['TABLE_NAME']) {
      if ($catalogTable['DB_TABLE_NAME'] != $this->db_tableName) 
        $catalogFields['DB_TABLE_NAME'] = $this->db_tableName;
      if ($catalogTable['SCHEMA_CLASS'] != $this->class) 
        $catalogFields['SCHEMA_CLASS'] = ($this->class) ? $this->class : null;
      if ($catalogTable['TIMESTAMPS'] != $this->timestamps) 
        $catalogFields['TIMESTAMPS'] = ($this->timestamps) ? 'Y' : 'N';
      $db->db_update('CORE_SCHEMA_TABLES')->fields($catalogFields)->condition('TABLE_NAME', $this->name)->execute();
    } else {
      $catalogFields['TABLE_NAME'] = $this->name;
      if ($this->db_tableName) 
        $catalogFields['DB_TABLE_NAME'] = $this->db_tableName;
      if ($this->class)
        $catalogFields['SCHEMA_CLASS'] = $this->class;
      if ($this->timestamps)
        $catalogFields['TIMESTAMPS'] = ($this->timestamps) ? 'Y' : 'N';
      $db->db_insert('CORE_SCHEMA_TABLES')->fields($catalogFields)->execute();
    }
    
  }
  
}