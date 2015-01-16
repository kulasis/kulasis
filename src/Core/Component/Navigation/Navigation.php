<?php

namespace Kula\Core\Component\Navigation;

class Navigation {
  
  private $navigation = array();
  private $navigationArranged = array();
  
  private $db;
  
  public function __construct($db) {
    $this->db = $db;
  }
  
  public function loadNavigation() {
    
    $navResults = $this->db->db_select('CORE_NAVIGATION', 'navigation')
      ->fields('navigation')
      ->leftJoin('CORE_NAVIGATION', 'parent_nav', 'parent_nav.NAVIGATION_ID = navigation.PARENT_NAVIGATION_ID')
      ->fields('parent_nav', array('NAVIGATION_NAME' => 'parent_NAVIGATION_NAME'))
      ->orderBy('navigation.PORTAL')
      ->orderBy('navigation.NAVIGATION_TYPE')
      ->orderBy('navigation.SORT')
      ->execute();
    while ($navRow = $navResults->fetch()) {
      $navigationItem = new NavigationItem($navRow['NAVIGATION_NAME'], $navRow['parent_NAVIGATION_NAME'], $navRow['NAVIGATION_ID'], $navRow['NAVIGATION_TYPE'], $navRow['PORTAL'], $navRow['SORT'], $navRow['DISPLAY_NAME'], $navRow['DIVIDER_BEFORE'], $navRow['RECORD_LOADED'], $navRow['ROUTE'], $navRow['CONFIRMATION_MESSAGE']);
      
      $this->navigation[$navRow['NAVIGATION_NAME']] = $navigationItem;
      
      $this->navigationArranged[$navRow['PORTAL']][$navRow['NAVIGATION_TYPE']][$navRow['NAVIGATION_NAME']] = $navigationItem;
      
      unset($navigationItem);
      
    }
  }
  
  public function getNavigationForPortal($portal, $type) {
    
    return $this->navigationArranged[$portal][$type];
    
  }
  
  public function __sleep() {
    $this->db = null;
    
    return array('navigation');
  }
  
}