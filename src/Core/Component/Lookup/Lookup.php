<?php

namespace Kula\Core\Component\Lookup;

class Lookup {
  
  private $lookup = array();
  
  private $db;
  
  public function __construct($db) {
    $this->db = $db;
  }
  
  public function loadLookup() {
    return array();
  }
  
  public function __sleep() {
    $this->db = null;
    
    return array('lookup');
  }
  
}