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
    
    // turn all blank values to null
    if (!is_array($this->edit[$table][$row_id][$key]) && trim($this->edit[$table][$row_id][$key]) == '') {
      $this->edit[$table][$row_id][$key] = null;
    }
    
  }
  
  private function processSameValues() {
    
    if (strpos($key, 'CALC_') !== false || (array_key_exists($key, $db_data[$row_id]) && 
        !in_array($key, $checkbox_fields) && 
        $db_data[$row_id][$key] == $this->edit[$table][$row_id][$key])) {
      unset($this->edit[$table][$row_id][$key]);
    }
    
  }
  
  private function processCheckboxFields() {
    
   
    
    // check for unchecked checkboxes
    if ($checkbox_fields) {
      foreach($checkbox_fields as $checkbox_field) {
        
        if (array_key_exists($checkbox_field, $this->edit[$table][$row_id]) AND 
            isset($this->edit[$table][$row_id][$checkbox_field]['checkbox_hidden']) AND
            $db_data[$row_id][$checkbox_field] != 'Y' AND 
            isset($this->edit[$table][$row_id][$checkbox_field]['checkbox']) AND
            $this->edit[$table][$row_id][$checkbox_field]['checkbox'] == 'Y') {
          $this->edit[$table][$row_id][$checkbox_field] = 'Y';
        } elseif (array_key_exists($checkbox_field, $this->edit[$table][$row_id]) AND 
            isset($this->edit[$table][$row_id][$checkbox_field]['checkbox_hidden']) AND
            $db_data[$row_id][$checkbox_field] == 'Y' AND 
            !isset($this->edit[$table][$row_id][$checkbox_field]['checkbox'])) {
          $this->edit[$table][$row_id][$checkbox_field] = 'N';
        } else {
          unset($this->edit[$table][$row_id][$checkbox_field]);
        }
      }
    }
    
  }
  
  private function processDateFields() {
    
    if (in_array($key, $date_fields)) {
      if (isset($this->edit[$table][$row_id][$key]) AND $this->edit[$table][$row_id][$key] != '') {
        
        // Check if slashes or dashes in place
        $value = $this->edit[$table][$row_id][$key];
        if (strpos($value, '/') === false AND strpos($value, '-') === false) {
          // split string and use mktime to determine date
          $new_date = mktime(0, 0, 0, substr($value, 0, 2), substr($value, 2, 2), substr($value, 4, strlen($value)-4));
        } else {
          $new_date = strtotime($value);
        }
        $this->edit[$table][$row_id][$key] = date('Y-m-d', $new_date);
      }
    }
  }
  
  private function processTimeFields() {
    if (in_array($key, $time_fields)) {
      if (isset($this->edit[$table][$row_id][$key]) AND $this->edit[$table][$row_id][$key] != '') {
        $this->edit[$table][$row_id][$key] = date('H:i:s', strtotime($this->edit[$table][$row_id][$key]));
      }
    }
    
  }
  
  private function processDateTimeFields() {
    
  }
  
  private function processChoosers() {
    
    if (isset($this->edit[$table][$row_id][$key]['value'])) {
      $this->edit[$table][$row_id][$key] = $this->edit[$table][$row_id][$key]['value'];
    }
    if (isset($this->edit[$table][$row_id][$key]['chooser'])) {
      unset($this->edit[$table][$row_id][$key]['chooser']);
    }
    
  }
  
  
}