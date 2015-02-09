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
  
  public function getMenuActions($permission = null) {
    
    $menu = array();
    
    if ($this->menuActions) {
      
      foreach($this->menuActions as $id => $menuAction) {
        
        if ($permission AND $permission->getPermissionForNavigationObject($menuAction->getName())) {
          $menu[] = $menuAction;
        }
        
      }
      
    }
    
    return $menu;
  }
  
  public function getMenuReports($permission = null) {
    
    $menu = array();
    
    if ($this->menuReports) {
      
      foreach($this->menuReports as $id => $menuReport) {
        
        if ($permission AND $permission->getPermissionForNavigationObject($menuReport->getName())) {
          $menu[] = $menuReport;
        }
        
      }
      
    }
    
    return $menu;
  }
  
  public function getTabs($permission = null) {
    
    $tabs = array();
    
    if ($this->tabs) {
      foreach($this->tabs as $id => $tab) {
        if ($permission AND $permission->getPermissionForNavigationObject($tab->getName())) {
          $tabs[] = $tab;
        }
        
      }
      
    }
    
    return $tabs;
  }
  
  public function getRoute() {
    
    if (parent::getRoute() == '' AND count($this->tabs) > 0) {
      $first = key($this->tabs);
      return $this->tabs[$first]->getRoute();
    } else {
      return parent::getRoute();
    }
    
  }
  
  public function getType() {
    return 'form';
  }
  
}