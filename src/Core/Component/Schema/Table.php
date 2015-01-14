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
  
  public function getDBName() {
    return $this->db_Name;
  }
  
  public function getDBPrimaryColumnName() {
    return $this->primary->getDBName();
  }
  
  public function addField(Field $field) {
    $this->fields[$field->getName()] = $field;
    
    if ($field->isPrimary()) {
      $this->primary = $field;
    }
  }
  
}