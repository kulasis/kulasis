<?php

namespace Kula\Core\Component\Schema;


class Table {
  
  private $name;
  private $db_ID;
  private $db_Name;
  private $db_Class;
  private $db_Timestamps;

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
  
}