<?php

namespace Kula\Core\Component\Navigation;

use Kula\Core\Component\DefinitionLoader\DefinitionLoader;
use Symfony\Component\Config\Resource\FileResource;

class NavigationLoader {
  
  private $navigation = array();
  private $resources = array();
  
  public function getNavigationFromBundles(array $bundles) {
    
    $navigation = DefinitionLoader::loadDefinitionsFromBundles($bundles, 'navigation');
    
    if ($navigation) {
      foreach($navigation as $path => $nav) {
        $this->loadNavigation($nav, $path);
        $this->resources[] = new FileResource($path);
      }
    }
    
  }
  
  public function getResources() {
    return $this->resources;
  }
  
  public function loadNavigation($navigation, $path) {
    
    foreach($navigation as $navName => $nav) {
      
      $this->navigation[$navName] = $nav;
      
    }
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
        
        if (count($navFields) > 0)
          $db->db_update('CORE_NAVIGATION')->fields($navFields)->condition('NAVIGATION_NAME', $navName)->execute();
        
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
        $navID = $db->db_insert('CORE_NAVIGATION')->fields($navFields)->execute();
        
      }
      
      
    }
  
  }
  
}