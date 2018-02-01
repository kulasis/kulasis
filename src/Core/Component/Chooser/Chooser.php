<?php

namespace Kula\Core\Component\Chooser;

abstract class Chooser {
  
  protected $db;
  protected $session;
  protected $focus;
  protected $chooser_menu = array();
  
  public function __construct($db, $session, $focus) {
    $this->db = $db;
    $this->session = $session;
    $this->focus = $focus;
  }
  
  protected function session() {
    return $this->session;
  }
  
  protected function focus() {
    return $this->focus;
  }
  
  protected function db() {
    return $this->db;
  }
  
  protected function addToChooserMenu($value, $option) {
    //if (count($this->chooser_menu) == 0)
    //  $this->chooser_menu[] = array('id' => '', 'text' => '');
    
    $this->chooser_menu[] = array('id' => $value, 'text' => $option);
  }
  
  public function createChooserMenu($search_string) {
    $this->addToChooserMenu('(blank)', ('(Blank)'));
    if ($search_string) {
      $this->search($search_string);
    }
    return $this->getChooserMenu();
  }
  
  public function getChooserMenu() {
    return $this->chooser_menu;
  }
  
  public function currentValue($value, $option) {
    return array($value => $option);
  }
  
}