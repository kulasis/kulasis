<?php

namespace Kula\Core\Component\Record;

class Record {
  
  private $record = array();
  
  private $db;
  
  public function __construct($db) {
    $this->db = $db;
  }
  
  public function loadRecord() {
    return array();
  }
  
  public function __sleep() {
    $this->db = null;
    
    return array('record');
  }
  
}