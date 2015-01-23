<?php

namespace Kula\Core\Component\DB;


class PrePosterRecord {
  
  private $table;
  private $id;
  private $fields;
  
  public function __construct($table, $id, array $fields) {
    $this->table = $table;
    $this->id = $id;
    $this->fields = $fields;
  }
  
  public function getTable() {
    return $this->table;
  }
  
  public function getID() {
    return $this->id;
  }
  
  public function getFields() {
    return $this->fields;
  }
  
  public function getField($field) {
    return $this->fields[$field];
  }
  
}