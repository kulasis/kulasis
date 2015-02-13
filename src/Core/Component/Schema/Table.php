<?php

namespace Kula\Core\Component\Schema;

use Kula\Core\Component\Schema\Field;

class Table {
  
  private $name;
  private $db_ID;
  private $db_Name;
  private $db_Class;
  private $db_Timestamps;
  
  private $fields;
  
  private $primary;

  public function __construct($name, $db_id, $db_Name, $db_Class, $db_Timestamps) {
    
    $this->name = $name;
    $this->db_ID = $db_id;
    $this->db_Name = $db_Name;
    $this->db_Class = $db_Class;
    $this->db_Timestamps = $db_Timestamps;
    
  }
  
  public function getName() {
    return $this->name;
  }
  
  public function getDBTimestamps() {
    return $this->db_Timestamps;
  }
  
  public function getDBName() {
    return $this->db_Name;
  }
  
  public function getDBClass() {
    return $this->db_Class;
  }
  
  public function getDBPrimaryColumnName() {
    return $this->primary->getDBName();
  }
  
  public function getDBField($dbFieldName) {
    
    foreach($this->fields as $fieldName => $field) {
      if ($field->getDBName() == $dbFieldName) {
        return $this->fields[$fieldName];
        break;
      }
    }
  }
  
  public function addField(Field $field) {
    $this->fields[$field->getName()] = $field;
    
    if ($field->isPrimary()) {
      $this->primary = $field;
    }
  }
  
  public function __destruct() {
    $this->fields = null;
    $this->primary = null;
  }
  
}