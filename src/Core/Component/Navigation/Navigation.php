<?php

namespace Kula\Core\Component\Navigation;

class Navigation {
  
  private $navigation = array();
  
  private $db;
  
  public function __construct($db) {
    $this->db = $db;
  }
  
  public function loadNavigation() {
    return array();
  }
  
  public function __sleep() {
    $this->db = null;
    
    return array('navigation');
  }
  
}