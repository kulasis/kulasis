<?php

namespace Kula\Core\Component\Schema;

class Field {
  
  private $table;
  private $name;
  private $description;
  private $primary;
  private $parent;
  private $db_columnName;
  private $db_columnType;
  private $db_columnSize;
  private $db_columnLength;
  private $db_columnPrecision;
  private $db_columnNull;
  private $class;
  private $field_type;
  private $field_size;
  private $field_cols;
  private $field_rows;
  private $chooserClass;
  private $lookup;
  private $columnName;
  private $labelName;
  private $labelPosition;
  private $updateField;
  private $log = array();
  
  public function __construct($table, $name, $description = null) {
    $this->table = $table;
    $this->name = $name;
    $this->description = $description;
  }
  
  public function getName() {
    return $this->name;
  }
  
  public function getDescription() {
    return $this->description;
  }
  
  public function getPrimary() {
    return $this->primary;
  }
  
  public function setPrimary($primary) {
    $this->primary = $primary;
  }
  
  public function setParent($parent) {
    $this->parent = $parent;
  }
  
  public function getParent() {
    return $this->parent;
  }
  
  public function getDBColumnName() {
    return $this->db_columnName;
  }
  
  public function getDBColumnLength() {
    return $this->db_columnLength;
  }
  
  public function getDBColumnType() {
    return $this->db_columnType;
  }
  
  public function getDBColumnSize() {
    return $this->db_columnSize;
  }
  
  public function getDBColumnNull() {
    return $this->db_columnNull;
  }
  
  public function setColumnName($columnName) {
    $this->columnName = $columnName;
  }
  
  public function setDBColumnName($columnName) {
    $this->db_columnName = $columnName;
  }
  
  public function setDBColumnType($columnType) {
    $this->db_columnType = $columnType;
  }
  
  public function setDBColumnSize($columnSize) {
    $this->db_columnSize = $columnSize;
  }
  
  public function setDBColumnNull($columnNull) {
    $this->db_columnNull = $columnNull;
  }
  
  public function setDBColumnLength($columnLength) {
    $this->db_columnLength = $columnLength;
  }
  
  public function setDBColumnPrecision($columnPrecision) {
    $this->db_columnPrecision = $columnPrecision;
  }
  
  public function setClass($class) {
    $this->class = $class;
  }
  
  public function setFieldType($field_type) {
    $this->field_type = $field_type;
  }
  
  public function setFieldSize($field_size) {
    $this->field_size = $field_size;
  }
  
  public function setFieldCols($field_cols) {
    $this->field_cols = $field_cols;
  }
  
  public function setFieldRows($field_rows) {
    $this->field_rows = $field_rows;
  }
  
  public function setChooserClass($chooserClass) {
    $this->chooserClass = $chooserClass;
  }
  
  public function setLookup($lookup) {
    $this->lookup = $lookup;
  }
  
  public function setLabelName($labelName) {
    $this->labelName = $labelName;
  }
  
  public function setLabelPosition($labelPosition) {
    $this->labelPosition = $labelPosition;
  }
  
  public function setUpdateField($updateField) {
    $this->updateField = $updateField;
  }
  
  public function log($bundlePath, $action) {
    $this->log[] = array('bundle_path' => $bundlePath, 'action' => $action);
  }
  
  public function synchronizeDatabaseCatalog(\Kula\Core\Component\DB\DB $db) {
    
    // Check field exists in database
    $catalogField = $db->db_select('CORE_SCHEMA_FIELDS', 'schema_fields')
      ->fields('schema_fields')
      ->condition('FIELD_NAME', $this->table->getName() . '.' .$this->name)
      ->execute()->fetch();
    
    $catalogFieldsForDB = array();
    
    if ($catalogField['FIELD_NAME']) {
      if ($catalogField['DB_COLUMN_NAME'] != $this->db_columnName) 
        $catalogFieldsForDB['DB_COLUMN_NAME'] = $this->db_columnName;
      if ($catalogField['DB_COLUMN_TYPE'] != $this->db_columnType) 
        $catalogFieldsForDB['DB_COLUMN_TYPE'] = $this->db_columnType;
      if ($catalogField['DB_COLUMN_LENGTH'] != $this->db_columnLength) 
        $catalogFieldsForDB['DB_COLUMN_LENGTH'] = $this->db_columnLength;
      if ($catalogField['DB_COLUMN_PRIMARY'] != $this->primary) 
        $catalogFieldsForDB['DB_COLUMN_PRIMARY'] = ($this->primary) ? 'Y' : 'N';
      if ($this->parent) {
        // Lookup parent schema field ID
        $parentSchemaField = $db->db_select('CORE_SCHEMA_FIELDS', 'schema_fields')
          ->fields('schema_fields', array('SCHEMA_FIELD_ID', 'FIELD_NAME'))
          ->condition('FIELD_NAME', $this->parent)
          ->execute()->fetch();
        
        $catalogFieldsForDB['PARENT_SCHEMA_FIELD_ID'] = ($parentSchemaField['SCHEMA_FIELD_ID'] AND $this->parent != $parentSchemaField['FIELD_NAME']) ? $parentSchemaField['SCHEMA_FIELD_ID'] : null;
      }
      if ($catalogField['FIELD_TYPE'] != $this->field_type)
        $catalogFieldsForDB['FIELD_TYPE'] = $this->field_type;
      if ($catalogField['FIELD_SIZE'] != $this->field_size)
        $catalogFieldsForDB['FIELD_SIZE'] = $this->field_size;
      if ($catalogField['LABEL_POSITION'] != $this->labelPosition)
        $catalogFieldsForDB['LABEL_POSITION'] = $this->labelPosition;
      if ($this->updateField) {
        // Lookup parent schema field ID
        $updateSchemaField = $db->db_select('CORE_SCHEMA_FIELDS', 'schema_fields')
          ->fields('schema_fields', array('SCHEMA_FIELD_ID', 'FIELD_NAME'))
          ->condition('FIELD_NAME', $this->updateField)
          ->execute()->fetch();
        
        $catalogFieldsForDB['UPDATE_FIELD_ID'] = ($updateSchemaField['SCHEMA_FIELD_ID'] AND $this->updateField != $updateSchemaField['FIELD_NAME']) ? $updateSchemaField['SCHEMA_FIELD_ID'] : null;
      }
      
      if (count($catalogFieldsForDB) > 0)
        $db->db_update('CORE_SCHEMA_FIELDS')->fields($catalogFieldsForDB)->condition('FIELD_NAME', $this->table->getName() . '.' .$this->name)->execute();
    } else {
      
      // Lookup table ID
      $tableID = $db->db_select('CORE_SCHEMA_TABLES', 'schema_tables')
          ->fields('schema_tables', array('SCHEMA_TABLE_ID'))
          ->condition('TABLE_NAME', $this->table->getName())
          ->execute()->fetch();
      $catalogFieldsForDB['SCHEMA_TABLE_ID'] = $tableID['SCHEMA_TABLE_ID'];
      
      $catalogFieldsForDB['FIELD_NAME'] = $this->table->getName().'.'.$this->name;
      if ($this->db_columnName) 
        $catalogFieldsForDB['DB_COLUMN_NAME'] = $this->db_columnName;
      if ($this->db_columnType) 
        $catalogFieldsForDB['DB_COLUMN_TYPE'] = $this->db_columnType;
      if ($this->db_columnLength) 
        $catalogFieldsForDB['DB_COLUMN_LENGTH'] = $this->db_columnLength;
      $catalogFieldsForDB['DB_COLUMN_PRIMARY'] = ($this->primary) ? 'Y' : 'N';
      if ($this->parent) {
        // Lookup parent schema field ID
        $parentSchemaField = $db->db_select('CORE_SCHEMA_FIELDS', 'schema_fields')
          ->fields('schema_fields', array('SCHEMA_FIELD_ID', 'FIELD_NAME'))
          ->condition('FIELD_NAME', $this->parent)
          ->execute()->fetch();
        
        $catalogFieldsForDB['PARENT_SCHEMA_FIELD_ID'] = ($parentSchemaField['SCHEMA_FIELD_ID']) ? $parentSchemaField['SCHEMA_FIELD_ID'] : null;
      }
      if ($this->field_type)
        $catalogFieldsForDB['FIELD_TYPE'] = $this->field_type;
      if ($this->field_size)
        $catalogFieldsForDB['FIELD_SIZE'] = $this->field_size;
      if ($this->labelPosition)
        $catalogFieldsForDB['LABEL_POSITION'] = $this->labelPosition;
      if ($this->updateField) {
        // Lookup parent schema field ID
        $updateSchemaField = $db->db_select('CORE_SCHEMA_FIELDS', 'schema_fields')
          ->fields('schema_fields', array('SCHEMA_FIELD_ID', 'FIELD_NAME'))
          ->condition('FIELD_NAME', $this->updateField)
          ->execute()->fetch();
        
        $catalogFieldsForDB['UPDATE_FIELD_ID'] = ($updateSchemaField['SCHEMA_FIELD_ID']) ? $updateSchemaField['SCHEMA_FIELD_ID'] : null;
      }
      $db->db_insert('CORE_SCHEMA_FIELDS')->fields($catalogFieldsForDB)->execute();
    }
    
  }
  /*
  public function verifyDatabaseSchema($db) {
    
    $fieldInfo = $db->db_schema()->fieldInfo($table, $this->getDBColumnName());
    
    // VERIFY COLUMN TYPE
    // Get field map
    $map = $db->db_schema()->getFieldTypeMap();
    
    // Get expected value
    if (!isset($this->db_columnSize)) {
      $size = 'normal';
    } else {
      $size = $this->db_columnSize;
    }
    
    $dbColumnType = $map[$this->db_columnType . ':' . $size];
    
    // VERIFY COLUMN LENGTH
    
    // VERIFY COLUMN NULLNESS
    
    // VERIFY COLUMN  
    
  }*/
  
  
}