<?php

namespace Kula\Core\Component\Chooser;

class Choosers {
  
  private $choosers = array();
  
  private $db;
  private $session;
  private $focus;
  
  public function __construct($db, $session, $focus) {
    $this->db = $db;
    $this->session = $session;
    $this->focus = $focus;
  }
  
  public function loadDependencies($db, $session, $focus) {
    $this->db = $db;
    $this->session = $session;
    $this->focus = $focus;
  }
  
  public function loadChoosers() {
    $choosersResults = $this->db->db_select('CORE_CHOOSER', 'chooser')
      ->fields('chooser')
      ->execute();
    while ($choosersRow = $choosersResults->fetch()) {
      $this->choosers[$choosersRow['CHOOSER_NAME']] = $choosersRow;
    }
  }
  
  public function get($chooser) {
    if ($this->choosers[$chooser]) {
      $class = $this->choosers[$chooser]['CLASS'];
      if (class_exists($class)) {
        return new $class($this->db, $this->session, $this->focus);
      }
    }
  }
  
  public function __sleep() {
    $this->db = null;
    $this->session = null;
    $this->focus = null;
    
    return array('choosers');
  }
  
}