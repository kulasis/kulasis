<?php

namespace Kula\Core\Component\Navigation;

class Navigation {
  
  private $db;
  
  public function __construct($db, $session, $permission, $request, $cache) {
    $this->db = $db;
    $this->session = $session;
    $this->permission = $permission;
    $this->request = $request;
    $this->cache = $cache;
  }
  
  public function getFirstRoute() {
    
    $nav = $this->cache->get('navigation.first')[$this->session->get('portal')];
    if ($this->permission->getPermissionForNavigationObject($nav))
    	return $this->cache->get('navigation.'.$nav)['route'];
    
  }
  
  public function getNavigationTreeTop() {
    
    $start_nav = array();
    
    // Get top for portal
    $top = $this->cache->get('navigation.top');

    if (count($top)) {

      foreach($top as $nav_top) {
        
        // check if part of portal
        if ($this->session->get('portal') == $this->getProperty($nav_top, 'portal')) {
        if (!$this->session->getFocus('target') OR 
            !$this->getProperty($nav_top, 'target') OR 
            ($this->getProperty($nav_top, 'target') == $this->session->getFocus('target'))) {
              if ($this->permission->getPermissionForNavigationObject($nav_top)) {
                $start_nav[] = $nav_top;
              }
          }
        }

      } // end foreach
      
    } // end if on count
    
    return $start_nav;
  }
  
  public function getNavigationTreeTopWithoutPortal() {
    
    $start_nav = $this->getNavigationTreeTop();
    
    // Loop through each top and remove the top where portal == target
    foreach($start_nav as $index => $nav_top) {
      if ($this->getProperty($nav_top, 'target') == $this->session->getFocus('target')) {
        foreach($this->getProperty($nav_top, 'children') as $nav_target) {
          if ($this->permission->getPermissionForNavigationObject($nav_target)) {
            $start_nav[] = $nav_target;
          }
        }
        unset($start_nav[$index]);
      }
    }
    
    $start_nav = array_values($start_nav);
    return $start_nav;
  }
  
  public function getCurrentRequestNav() {
    return $this->request->getCurrentRequest()->get('_navigation');
  }
  
  public function getProperty($nav_key, $property) {
    if ($this->permission->getPermissionForNavigationObject($nav_key) AND $this->cache->exists('navigation.'.$nav_key)) {
      $nav = $this->cache->get('navigation.'.$nav_key);
      if (isset($nav[$property]))
        return $nav[$property];
    }
  }
  
  public function getRoute($nav_key) {
    if ($this->permission->getPermissionForNavigationObject($nav_key) AND $this->cache->exists('navigation.'.$nav_key)) {
      $nav = $this->cache->get('navigation.'.$nav_key);
      if (isset($nav['route'])) {
        return $nav['route'];
      } else {
        // find route of first available tab
        if (isset($nav['tabs'])) {
          $tab_key = current($nav['tabs']);
          $tab = $this->cache->get('navigation.'.$tab_key);
          if (isset($tab['route'])) {
            return $tab['route'];
          }
        }
      }
    }
  }
  
  public function getTabs($nav_key) {
    if ($this->permission->getPermissionForNavigationObject($nav_key)) {

      $tabsToOutput = array();

      if ($this->getProperty($nav_key, 'tabs')) {
        $tabs = $this->getProperty($nav_key, 'tabs');
      } else {
        // Get parent
        $tabs = $this->getProperty($this->getProperty($nav_key, 'parent'), 'tabs');
      }

      // Get tabs from parent
      if ($tabs) {
        
        foreach($tabs as $tab) {
          if (!$this->getProperty($tab, 'target') OR ($this->getProperty($tab, 'target') AND $this->getProperty($tab, 'target') == $this->session->getFocus('target')))
            $tabsToOutput[] = $tab;
        }
        return $tabsToOutput;
      }
      
    }
  }
  
  public function getActionMenu($nav_key) {
    
    $nav_key = $this->determineParentKey($nav_key);
    
    if ($this->permission->getPermissionForNavigationObject($nav_key)) {
      // Get parent
      $tabs = $this->getProperty($nav_key, 'menu_actions');

      // Get tabs from parent
      if ($tabs) {
        return $tabs;
      }
      
    }
  } 
  
  public function getReportsMenu($nav_key) {
    if ($this->getProperty($nav_key, 'type') != 'menu_report') {    
      $nav_key = $this->determineParentKey($nav_key);
      
      if ($this->permission->getPermissionForNavigationObject($nav_key)) {
        // Get parent
        $tabs = $this->getProperty($nav_key, 'menu_reports');
        
        // Get tabs from parent
        if ($tabs) {
          return $tabs;
        }
        
      }
    }
  }
  
  public function getAddButton($nav_key) {
    
    $nav_key = $this->determineParentKey($nav_key);
    
    if ($this->permission->getPermissionForNavigationObject($nav_key)) {
      
      // Get tabs from parent
      if ($tabs = $this->getProperty($nav_key, 'button_add')) {
        return $tabs;
      }
      
    }
  } 
  
  public function getDeleteButton($nav_key) {
    
    $nav_key = $this->determineParentKey($nav_key);
    
    if ($this->permission->getPermissionForNavigationObject($nav_key)) {
    
      // Get tabs from parent
      if ($tabs = $this->getProperty($nav_key, 'button_delete')) {
        return $tabs;
      }
      
    }
  } 
  
  private function determineParentKey($nav_key) {
    
    if ($this->getProperty($nav_key, 'type') == 'form') {
      return $nav_key;
    } else {
      return $this->getProperty($nav_key, 'parent');
    }
    
  }
  
  public function shouldOpenNode($navigation_name, $node_name) {
    if (strpos($navigation_name, $node_name) >= 0)
      return true;
    
  }
}