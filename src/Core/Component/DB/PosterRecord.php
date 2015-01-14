<?php

namespace Kula\Component\DB;

use Kula\Core\Component\DB\DB as DB;
use Kula\Core\Component\Schema\Schema as Schema;
use Symfony\Component\HttpFoundation\RequestStack as RequestStack;

class PosterRecord {
  
  private $db;
  private $schema;
  
  private $crud;
  private $table;
  private $id;
  private $fields;
  
  private $originalRecord;
  
  const ADD = 1;
  const EDIT = 2;
  const DELETE = 3;
  
  public function __construct(DB $db, Schema $schema, $crud, $table, $id, $fields) {
    $this->db = $db;
    $this->schema = $schema;
    $this->table = $table;
    $this->id = $id;
    $this->fields = $fields;
  }
  
  public function process() {
    $this->getOriginalRecord();
    $this->processConfirmation();
    $this->processSynthetic();
    $this->processCheckboxes();
    $this->processDateFields();
    $this->processTimeFields();
    $this->processChoosers();
    
    $this->processBlankValues();
    if ($this->crud == self::EDIT) {
      $this->processSameValues();
    }
    
    
  }
  
  private function getOriginalRecord() {
    if ($this->crud == self::ADD)
      return false;
    $this->originalRecord = $this->db->db_select($this->table, 'originalTable')
      ->fields('originalTable')
      ->condition($this->schema->getDBPrimaryColumnForTable($this->table), $this->id);
  }
  
  private function processConfirmation() {
    foreach($this->fields as $fieldName => $field) {
      $confirmKey = $fieldName . '_confirmation';
      if (isset($this->fields[$confirmKey])) {
        if ($this->fields[$fieldName] != $this->fields[$confirmKey]) {
          throw new \Exception($key . ' and confirmation fields do not match.');
        }
        unset($this->fields[$confirmKey]);
      }
    }
  }
  
  private function processSynthetic() {
    foreach($this->fields as $fieldName => $field) {
      $class = '\\'.$this->schema->getClass($fieldName);
      if (method_exists($class, 'save')) {
        $returnedValue = call_user_func_array($class.'::save', array($field, $this->id));
        if ($returnedValue == 'remove_field')
          unset($this->fields[$fieldName]);
        else
          $this->fields[$fieldName] = $returnedValue;
      }
    }
  }
  
  // TO DO EDIT THE FOLLOWING
  
  private function processBlankValues() {
    foreach($this->fields as $fieldName => $field) {
      if (!is_array($field) AND trim($field) == '') {
        $this->fields[$fieldName] = null;
      }
    }
  }
  
  private function processSameValues() {
    foreach($this->fields as $fieldName => $field) {
      if (isset($this->originalRecord[$fieldName]) AND $this->originalRecord[$fieldName] == $this->fields[$fieldName]) {
        unset($this->fields[$fieldName]);
      }
    }
  }
  
  private function processCheckboxFields() {
    foreach($this->fields as $fieldName => $field) {
      if ($this->schema->getFieldType($fieldName) == 'checkbox') {
        if (isset($field['checkbox_hidden']) OR isset($field['checkbox'])) {
          // Checkbox originally unchecked, now checked.
          if ($field['checkbox_hidden'] == '' AND $field['checkbox'] == 1) {
            $this->fields[$fieldName] = 1;
          }
          // Checkbox originally checked, now unchecked.
          if ($field['checkbox_hidden'] == '1' AND !isset($field['checkbox'])) {
            $this->fields[$fieldName] = 0;
          }
        }
      }
    }
  }
  
  private function processDateFields() {
    foreach($this->fields as $fieldName => $field) {
      if ($this->schema->getFieldType($fieldName) == 'date') {
        if ($field != '') {
          // Check if slashes or dashes in place
          if (strpos($field, '/') === false AND strpos($field, '-') === false) {
            // split string and use mktime to determine date
            $newDate = mktime(0, 0, 0, substr($field, 0, 2), substr($field, 2, 2), substr($field, 4, strlen($field)-4));
          } else {
            $newDate = strtotime($field);
          }
          $this->fields[$fieldName] = date('Y-m-d', $newDate);
        }
      }
    }
  }
  
  private function processTimeFields() {
    foreach($this->fields as $fieldName => $field) {
      if ($this->schema->getFieldType($fieldName) == 'time') {
        if ($field != '') {
          $this->fields[$fieldName] = date('H:i:s', strtotime($field));
        }
      }
    }
  }
  
  private function processDateTimeFields() {
    foreach($this->fields as $fieldName => $field) {
      if ($this->schema->getFieldType($fieldName) == 'datetime') {
        if ($field != '') {
          $this->fields[$fieldName] = date('Y-m-d H:i:s', strtotime($field));
        }
      }
    }
  }
  
  private function processChoosers() {
    foreach($this->fields as $fieldName => $field) {
      if ($this->schema->getFieldType($fieldName) == 'chooser') {
        $this->fields[$fieldName] = $field['value'];
      }
    }
  }
  
  
}