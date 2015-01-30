<?php

namespace Kula\Core\Component\Navigation;

use Kula\Core\Component\DefinitionLoader\DefinitionLoader;
use Symfony\Component\Config\Resource\FileResource;

class NavigationLoader {
  
  private $navigation = array();
  public $paths = array();
  
  public function getNavigationFromBundles(array $bundles) {
    
    $navigation = DefinitionLoader::loadDefinitionsFromBundles($bundles, 'navigation');
    
    if ($navigation) {
      foreach($navigation as $path => $nav) {
        if ($nav) {
          $this->loadNavigation($nav, $path);
          $this->paths[] = new FileResource($path);
        }
      }
    }
    
  }
  
  public function getResources() {
    return $this->resources;
  }
  
  public function loadNavigation($navigation, $path) {
    
    foreach($navigation as $navName => $nav) {
      
      if ($nav['type'] == 'form_group') {
        $this->formGroup($nav, $navName);
      } elseif ($nav['type'] == 'report_group') { 
        $this->reportGroup($nav, $navName);
      } elseif ($nav['type'] == 'form') {
        $this->form($nav, $navName, $nav['parent'], $nav['portal']);
      } elseif ($nav['type'] == 'menu_actions') {
        $this->formMenuActions($nav, $navName, $nav['parent'], $nav['portal']);
      } elseif ($nav['type'] == 'menu_reports') {
        $this->formMenuReports($nav, $navName, $nav['parent'], $nav['portal']);
      } elseif ($nav['type'] == 'report') {
        $this->report($nav, $navName, $nav['parent'], $nav['portal']);
      } elseif ($nav['type'] == 'tab') {
        $this->tab($nav, $navName, $nav['parent'], $nav['portal']);
      } elseif ($nav['type'] == 'page') {
        $this->page($nav, $navName);
      }
      
      //$this->navigation[$navName] = $nav;
      
    }
  }
  
  private function formGroup($nav, $name) {
    
    $this->addNavigation($name, $nav);
    
    foreach($nav['forms'] as $formName => $form) {
      $this->form($form, $formName, $name, $nav['portal']);
    }
  }
  
  private function page($nav, $name) {
    $this->addNavigation($name, $nav);
  }
  
  private function reportGroup($nav, $name) {
    $this->addNavigation($name, $nav);
    
    foreach($nav['reports'] as $reportName => $report) {
      $this->report($report, $reportName, $name, $nav['portal']);
    }
  }
  
  private function formMenuActions($nav, $name, $parent, $portal) {
    
    $nav['parent'] = $parent;
    $nav['type'] = 'menu_action';
    $nav['portal'] = $portal;
    $this->addNavigation($parent.'.'.$name, $nav);
    
  }
  
  private function formMenuReports($nav, $name, $parent, $portal) {
    
    $nav['parent'] = $parent;
    $nav['type'] = 'menu_report';
    $nav['portal'] = $portal;
    $this->addNavigation($parent.'.'.$name, $nav);
    
  }
  
  private function report($nav, $name, $parent, $portal) {
    $nav['parent'] = $parent;
    $nav['type'] = 'report';
    $nav['portal'] = $portal;
    $this->addNavigation($parent.'.'.$name, $nav);
  }
  
  private function form($nav, $name, $parent, $portal) {

    $nav['parent'] = $parent;
    $nav['type'] = 'form';
    $nav['portal'] = $portal;
    $this->addNavigation($parent.'.'.$name, $nav);
    
    if (isset($nav['tabs'])) {
    foreach($nav['tabs'] as $tabName => $tab) {
      $this->tab($tab, $tabName, $nav['parent'].'.'.$name, $portal);
    }
    }
    
    if (isset($nav['menu_actions'])) {
    foreach($nav['menu_actions'] as $menuactionName => $menu) {
      $this->formMenuActions($menu, $menuactionName, $nav['parent'].'.'.$name, $portal);
    }
    }
    
    if (isset($nav['menu_reports'])) {
    foreach($nav['menu_reports'] as $menureportName => $menu) {
      $this->formMenuReports($menu, $menureportName, $nav['parent'].'.'.$name, $portal);
    }
    }
    
  }
  
  private function tab($nav, $name, $parent, $portal) {

    $nav['parent'] = $parent;
    $nav['portal'] = $portal;
    $nav['type'] = 'tab';
    $this->addNavigation($parent.'.'.$name, $nav);
    
  }
  
  private function addNavigation($name, $nav) {
    
    $nav = $this->loadDefaultNav($nav);
    if (isset($this->navigation[$name])) {
      $this->navigation[$name] = array_merge($this->navigation[$name], $nav);
    } else {  
      $this->navigation[$name] = $nav;
    }
    
  }
  
  private function loadDefaultNav($navigation) {
    
    $defaultParams = array('type' => null, 'parent' => null, 'portal' => null, 'sort' => null, 'display_name' => null, 'divider_before' => null, 'record_loaded' => null, 'route' => null, 'confirmation_message' => null);
    return array_merge($defaultParams, $navigation);
    
  }
  
  public function synchronizeDatabaseCatalog(\Kula\Core\Component\DB\DB $db) {
    
    foreach($this->navigation as $navName => $nav) {
      
      // Check table exists in database
      $catalogNavigationTable = $db->db_select('CORE_NAVIGATION', 'nav')
        ->fields('nav')
        ->condition('NAVIGATION_NAME', $navName)
        ->execute()->fetch();
      
      $navFields = array();
      
      if ($catalogNavigationTable['NAVIGATION_ID']) {
        
        if ($catalogNavigationTable['NAVIGATION_TYPE'] != $nav['type']) 
          $navFields['NAVIGATION_TYPE'] = $nav['type'];
        if ($catalogNavigationTable['PORTAL'] != $nav['portal']) 
          $navFields['PORTAL'] = $nav['portal'];
        if (isset($nav['sort']) AND $catalogNavigationTable['SORT'] != $nav['sort']) 
          $navFields['SORT'] = $nav['sort'];
        if ($catalogNavigationTable['DISPLAY_NAME'] != $nav['display_name']) 
          $navFields['DISPLAY_NAME'] = $nav['display_name'];
        if (isset($nav['divider_before']) AND $catalogNavigationTable['DIVIDER_BEFORE'] != $nav['divider_before']) 
          $navFields['DIVIDER_BEFORE'] = (isset($nav['divider_before']) AND $nav['divider_before']) ? 1 : 0;
        if (isset($nav['record_loaded']) AND $catalogNavigationTable['RECORD_LOADED'] != $nav['record_loaded']) 
          $navFields['RECORD_LOADED'] = (isset($nav['record_loaded']) AND $nav['record_loaded']) ? 1 : 0;
        if (isset($nav['route']) AND $catalogNavigationTable['ROUTE'] != $nav['route']) 
          $navFields['ROUTE'] = $nav['route'];
        if (isset($nav['confirmation_message']) AND $catalogNavigationTable['CONFIRMATION_MESSAGE'] != $nav['confirmation_message']) 
          $navFields['CONFIRMATION_MESSAGE'] = $nav['confirmation_message'];
        
        if (count($navFields) > 0) {
          $navFields['UPDATED_TIMESTAMP'] = date('Y-m-d H:i:s');
          $db->db_update('CORE_NAVIGATION')->fields($navFields)->condition('NAVIGATION_NAME', $navName)->execute();
        }
        
      } else {
        
        $navFields['NAVIGATION_NAME'] = $navName;
        
        if (isset($nav['parent'])) {
          // Look up parent
          $navParent = $db->db_select('CORE_NAVIGATION', 'nav')
            ->fields('nav', array('NAVIGATION_ID'))
            ->condition('NAVIGATION_NAME', $nav['parent'])
            ->execute()->fetch();
          
          if ($navParent['NAVIGATION_ID']) 
            $navFields['PARENT_NAVIGATION_ID'] = $navParent['NAVIGATION_ID'];
        } 
        
        $navFields['NAVIGATION_TYPE'] = $nav['type'];
        $navFields['PORTAL'] = $nav['portal'];
        $navFields['SORT'] = (isset($nav['sort'])) ? $nav['sort'] : null;
        $navFields['DISPLAY_NAME'] = $nav['display_name'];
        $navFields['DIVIDER_BEFORE'] = (isset($nav['divider_before']) AND $nav['divider_before']) ? 1 : 0;
        $navFields['RECORD_LOADED'] = (isset($nav['record_loaded']) AND $nav['record_loaded']) ? 1 : 0;
        $navFields['ROUTE'] = (isset($nav['route'])) ? $nav['route'] : null;
        $navFields['CONFIRMATION_MESSAGE'] = (isset($nav['confirmation_message'])) ? $nav['confirmation_message'] : null;
        $navFields['CREATED_TIMESTAMP'] = date('Y-m-d H:i:s');
        $navID = $db->db_insert('CORE_NAVIGATION')->fields($navFields)->execute();
        
      }
      
      
    }
  
  }
  
}