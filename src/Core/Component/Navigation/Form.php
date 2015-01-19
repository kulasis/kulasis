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
  
  public function getMenuActions() {
    return $this->menuActions;
  }
  
  public function getMenuReports() {
    return $this->menuReports;
  }
  
  public function getTabs() {
    return $this->tabs;
  }
  
  public function getRoute() {
    
    if (parent::getRoute() == '' AND count($this->tabs) > 0) {
      $first = key($this->tabs);
      return $this->tabs[$first]->getRoute();
    } else {
      return parent::getRoute();
    }
    
  }
  
}