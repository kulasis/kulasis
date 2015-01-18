<?php

namespace Kula\Core\Component\Record;

class RecordType {

  private $db_id;
  private $name;
  private $portal;
  private $class;
  
  public function __construct($db_id, $name, $portal, $class) {
    $this->db_id = $db_id;
    $this->name = $name;
    $this->portal = $portal;
    $this->class = $class;
  }
  
  public function getName() {
    return $this->name;
  }
  
  public function getClass() {
    return $this->class;
  }
  
  public function getPortal() {
    return $this->portal;
  }

}