<?php

namespace Kula\Core\Component\Navigation;

class Form extends Item {
  
  private $menuActions = array();
  private $menuReports = array();
  private $tabs = array();
  
  public function addMenuAction(Menu $menu) {
    $this->menuActions[] = $menu;
  }
  
  public function addMenuReport(Menu $menu) {
    $this->menuReports[] = $menu;
  }
  
  public function addTab(Tab $menu) {
    $this->tabs[] = $menu;
  }
  
}