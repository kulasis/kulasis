<?php

namespace Kula\Core\Component\Schema;

use Symfony\Component\Yaml\Yaml;

use Kula\Core\Component\Database\Database;
use Kula\Core\Component\Database\Query\Condition;

use Kula\Core\Component\Schema\Table;
use Kula\Core\Component\Schema\Field;

class Schema {
  
  public $schema = array();
  
  public function synchronize() {
    
  }
  
  public function getTableForSchema($tableName) {
    return $this->schema[$tableName];
  }
  
  public function getFieldForSchema($tableName, $fieldName) {
    return $this->schema[$tableName]->getField($fieldName);
  }
  
  public function getSchemaFromBundles(array $bundles) {
    
    foreach($bundles as $bundle) {
      $path = $bundle->getPath().'/Resources/config/schema.yml';
      if (file_exists($path)) {
        $bundledSchema = Yaml::parse($path);
        
        $this->loadSchema($bundledSchema, $path);
        
      }
    }
    
  }
  
  private function loadSchema(array $schemaArray, $bundlePath) {
    
    foreach($schemaArray as $tableName => $table) {
      
      $this->loadTable($bundlePath, $tableName, 
                       $table['description'],
                       isset($table['db_table_name']) ? $table['db_table_name'] : null, 
                       isset($table['class']) ? $table['class'] : null, 
                       isset($table['qualified']) ? $table['qualified'] : null, 
                       isset($table['timestamps']) ? $table['timestamps'] : null);
      
      if (isset($table['fields']) AND count($table['fields']) > 0) {
        
        foreach($table['fields'] as $fieldName => $field) {
          
          $this->loadField($bundlePath, $tableName, 
                           $fieldName, 
                           $field['description'], 
                           isset($field['primary']) ? true : null,
                           isset($field['parent']) ? $field['parent'] : null,
                           $field['db_column_name'], 
                           $field['db_column_type'], 
                           isset($field['db_column_size']) ? $field['db_column_size'] : null,
                           isset($field['db_column_length']) ? $field['db_column_length'] : null,
                           isset($field['db_column_precision']) ? $field['db_column_precision'] : null,
                           isset($field['db_column_null']) ? $field['db_column_null'] : null,
                           isset($field['class']) ? $field['class'] : null,
                           isset($field['field_type']) ? $field['field_type'] : null,
                           isset($field['field_size']) ? $field['field_size'] : null,
                           isset($field['field_cols']) ? $field['field_cols'] : null,
                           isset($field['field_rows']) ? $field['field_rows'] : null,
                           isset($field['chooser_class']) ? $field['chooser_class'] : null,
                           isset($field['lookup']) ? $field['lookup'] : null,
                           isset($field['column_name']) ? $field['column_name'] : null,
                           isset($field['label_name']) ? $field['label_name'] : null,
                           isset($field['label_position']) ? $field['label_position'] : null,
                           isset($field['update_field']) ? $field['update_field'] : null
          );
          
        } // end foreach fields
        
      }  // end if tables
      
      $this->schema[$tableName]->createTable($GLOBALS['kernel']->getContainer()->get('db'), $this);
      
    } // end foreach tables
    
  }
  
  private function loadTable($bundlePath, $name, $description, $db_tableName, $class, $qualified, $timestamps) {
    
    if (!isset($this->schema[$name])) {
    
      $this->schema[$name] = new Table($bundlePath, $name, $description, $db_tableName, $class, $qualified, $timestamps);
    
    }
    
  }
  
  private function loadField($bundlePath, $tableName, $name, $description, $primary, $parent, $db_columnName, $db_columnType, $db_columnSize, $db_columnLength, $db_columnPrecision, $db_columnNull, $class, $field_type, $field_size, $field_cols, $field_rows, $chooserClass, $lookup, $columnName, $labelName, $labelPosition, $updateField) {
    
    if (isset($this->schema[$tableName])) {
      
      if (!$this->schema[$tableName]->getField($name)) {
        
        $field = new Field($this->schema[$tableName], $name, $description);
        
        if ($primary) $field->setPrimary(true);
        if ($parent) $field->setParent($parent);
        if ($db_columnName) $field->setDBColumnName($db_columnName);
        if ($db_columnType) $field->setDBColumnType($db_columnType);
        if ($db_columnSize) $field->setDBColumnSize($db_columnSize);
        if ($db_columnLength) $field->setDBColumnLength($db_columnLength);
        if ($db_columnPrecision) $field->setDBColumnPrecision($db_columnPrecision);
        if ($db_columnNull) $field->setDBColumnNull($db_columnNull);
        if ($class) $field->setClass($class);
        if ($field_type) $field->setFieldType($field_type);
        if ($field_size) $field->setFieldSIze($field_size);
        if ($field_cols) $field->setFieldCols($field_cols);
        if ($field_rows) $field->setFieldRows($field_rows);
        if ($chooserClass) $field->setChooserClass($chooserClass);
        if ($lookup) $field->setLookup($lookup);
        if ($columnName) $field->setColumnName($columnName);
        if ($labelName) $field->setLabelName($labelName);
        if ($updateField) $field->setUpdateField($updateField);
        
        $field->log($bundlePath, 'Added field');
        
        $this->schema[$tableName]->addField($field);
        
        
        
      }
      
    }
    
  }
  
}