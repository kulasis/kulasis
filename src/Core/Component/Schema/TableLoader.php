<?php

namespace Kula\Core\Component\Schema;

use Kula\Core\Component\Schema\FieldLoader;

class TableLoader {
  
  private $name;
  private $description;
  private $db_tableName;
  private $class;
  private $qualified;
  private $timestamps;
  private $uniqueKeys = array();
  private $fields = array();
  private $log = array();
  
  public function __construct($bundlePath, $name, $description, $db_tableName, $class = null, $qualified = array(), $timestamps = null, $uniqueKeys = null) {
    
    $this->name = $name;
    $this->description = $description;
    $this->db_tableName = $db_tableName;
    $this->class = $class;
    $this->qualified = $qualified;
    $this->timestamps = $timestamps;
    $this->uniqueKeys = $uniqueKeys;
    
    $this->log($bundlePath, 'Created table object.');
  }
  
  public function getName() {
    return $this->name;
  }
  
  public function addField(FieldLoader $field) {
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
  
  private function addTimestamps() {
    if ($this->timestamps) {
      
      $created_userstamp = new FieldLoader($this, 'CreatedUserstamp', 'Created Userstamp');
      $created_userstamp->setDBColumnName('CREATED_USERSTAMP');
      $created_userstamp->setDBColumnType('serial');
      $created_userstamp->setDBColumnNull(true);
      $this->addField($created_userstamp);
      
      $created_timestamp = new FieldLoader($this, 'CreatedTimestamp', 'Created Timestamp');
      $created_timestamp->setDBColumnName('CREATED_TIMESTAMP');
      $created_timestamp->setDBColumnType('datetime');
      $created_timestamp->setDBColumnNull(true);
      $this->addField($created_timestamp);
      
      $updated_userstamp = new FieldLoader($this, 'UpdatedUserstamp', 'Updated Userstamp');
      $updated_userstamp->setDBColumnName('UPDATED_USERSTAMP');
      $updated_userstamp->setDBColumnType('serial');
      $updated_userstamp->setDBColumnNull(true);
      $this->addField($updated_userstamp);
      
      $updated_timestamp = new FieldLoader($this, 'UpdatedTimestamp', 'Updated Timestamp');
      $updated_timestamp->setDBColumnName('UPDATED_TIMESTAMP');
      $updated_timestamp->setDBColumnType('datetime');
      $updated_timestamp->setDBColumnNull(true);
      $this->addField($updated_timestamp);
    }
  }
  
  public function createTable(\Kula\Core\Component\DB\DB $db, $schema) {
    
    $this->addTimestamps();
    
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
      
      $default = $field->getDBColumnDefault();      
      if (isset($default)) {
        $structure['fields'][$field->getDBColumnName()]['default'] = $field->getDBColumnDefault();
      }
      
      $precision = $field->getDBColumnPrecision();
      if (isset($precision)) {
        unset($structure['fields'][$field->getDBColumnName()]['length']);
        $structure['fields'][$field->getDBColumnName()]['precision'] = $field->getDBColumnLength();
        $structure['fields'][$field->getDBColumnName()]['scale'] = $precision;
      }
      
      if ($field->getPrimary()) {
        $structure['primary key'][] = $field->getDBColumnName();
      }
      
    }  // end foreach on fields
    
    if (!$db->db_table_exists($this->db_tableName)) {
      $db->db_create_table($this->db_tableName, $structure);
    }
    
    if ($db->db_table_exists('CORE_SCHEMA_TABLES') AND $db->db_table_exists('CORE_SCHEMA_FIELDS'))
      $this->synchronizeDatabaseCatalog($db);
    
  }
  
  public function synchronizeDatabaseCatalog(\Kula\Core\Component\DB\DB $db) {
    
    $this->addTimestamps();
    
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
        $catalogFields['TIMESTAMPS'] = ($this->timestamps) ? 1 : 0;
      
      if (count($catalogFields) > 0) {
        $catalogFields['UPDATED_TIMESTAMP'] = date('Y-m-d H:i:s');
        $db->db_update('CORE_SCHEMA_TABLES')->fields($catalogFields)->condition('TABLE_NAME', $this->name)->execute();
      }
    } else {
      $catalogFields['TABLE_NAME'] = $this->name;
      if ($this->db_tableName) 
        $catalogFields['DB_TABLE_NAME'] = $this->db_tableName;
      if ($this->class)
        $catalogFields['SCHEMA_CLASS'] = $this->class;
      if ($this->timestamps)
        $catalogFields['TIMESTAMPS'] = ($this->timestamps) ? 1 : 0;
      $catalogFields['CREATED_TIMESTAMP'] = date('Y-m-d H:i:s');
      $db->db_insert('CORE_SCHEMA_TABLES')->fields($catalogFields)->execute();
    }
    
    foreach($this->fields as $field) {
      $field->synchronizeDatabaseCatalog($db);
    }
  
  }
  
  public function synchronizeDatabaseCatalogParentKeys(\Kula\Core\Component\DB\DB $db, $schema) {
    
    foreach($this->fields as $fieldName => $field) {
      
      if ($field->getParent()) {
        // Table is the first part
        $parentTableName = substr($field->getParent(), 0, strrpos($field->getParent(), '.'));
        // Field is the last part
        $parentFieldName = substr($field->getParent(), strrpos($field->getParent(), '.')+1, strlen($field->getParent()));

        $parentTable = $schema->getTableForSchema($parentTableName);
        $parentField = $schema->getFieldForSchema($parentTableName, $parentFieldName);

        $fkTableName = ''; // First letter
        $tokenziedTableName = strtok($this->db_tableName, '_');
        while ($tokenziedTableName !== false) {
          $fkTableName .= substr($tokenziedTableName, 0, 3);
          $tokenziedTableName = strtok('_');
        }
        
        $spec = array(
            'table' => $parentTable->db_tableName,
            'columns' => array($field->getDBColumnName() => $parentField->getDBColumnName()),
        );
        
        if (!$db->db_schema()->keyExists($this->db_tableName, 'FK_'.$fkTableName.'_'.$field->getDBColumnName())) {
          $db->db_schema()->addForeignKey($this->db_tableName, 'FK_'.$fkTableName.'_'.$field->getDBColumnName(), $spec);
        }
      
        $field->synchronizeDatabaseCatalogParentKeys($db);
      }
      
      // get unique keys
      if (count($this->uniqueKeys) > 0) {
      
        foreach($this->uniqueKeys as $uniqueKeyName => $uniqueKeys) {
        
          $uniqueKeysForDB = array();
        
          // Look up db name
          foreach($uniqueKeys as $field) {
          
            $tableName = substr($field, 0, strrpos($field, '.'));
            $fieldName = substr($field, strrpos($field, '.')+1, strlen($field));
          
            if ($tableName == $this->name) {
              $uniqueKeysForDB[] = $this->fields[$fieldName]->getDBColumnName();
            }
          
          }
        
          if (count($uniqueKeysForDB) > 1) {
            $ukTableName = '';
            $tokenziedTableName = strtok(implode('_', $uniqueKeysForDB), '_');
            while ($tokenziedTableName !== false) {
              $ukTableName .= substr($tokenziedTableName, 0, 3);
              $tokenziedTableName = strtok('_');
            }
          } else {
            $ukTableName = implode('_', $uniqueKeysForDB);
          }
          
          if (!$db->db_schema()->indexExists($this->db_tableName, 'UK_'.$ukTableName)) {
            $db->db_schema()->addUniqueKey($this->db_tableName, 'UK_'.$ukTableName, $uniqueKeysForDB);
          }
        
        }
      
      }
      
    }
    
  }
  
}