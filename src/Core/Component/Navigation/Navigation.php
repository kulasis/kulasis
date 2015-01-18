<?php

namespace Kula\Core\Component\Navigation;

class Navigation {
  
  private $navigation = array();
  private $reportGroups = array();
  private $formGroups = array();
  private $pages = array();
  private $navigationByRoutes = array();
  
  private $db;
  
  public function __construct($db) {
    $this->db = $db;
  }
  
  public function loadNavigation() {
    
    $navReportGroupsResults = $this->db->db_select('CORE_NAVIGATION', 'navigation')
      ->fields('navigation')
      ->condition('navigation.NAVIGATION_TYPE', 'report_group')
      ->orderBy('navigation.PORTAL')
      ->orderBy('navigation.SORT')
      ->execute();
    while ($navReportGroupsRow = $navReportGroupsResults->fetch()) {
        $navigationItem = new Group($navReportGroupsRow['NAVIGATION_NAME'], null, $navReportGroupsRow['NAVIGATION_ID'], $navReportGroupsRow['PORTAL'], $navReportGroupsRow['SORT'], $navReportGroupsRow['DISPLAY_NAME'], $navReportGroupsRow['ROUTE']);
        
        $this->reportGroups[$navReportGroupsRow['PORTAL']][$navReportGroupsRow['NAVIGATION_NAME']] = $navigationItem;
        $this->navigation[$navReportGroupsRow['NAVIGATION_NAME']] = $navigationItem;
        
      unset($navigationItem);
    }
      
    $navReportGroupsResults = $this->db->db_select('CORE_NAVIGATION', 'navigation')
      ->fields('navigation')
      ->condition('navigation.NAVIGATION_TYPE', 'form_group')
      ->orderBy('navigation.PORTAL')
      ->orderBy('navigation.SORT')
      ->execute();
    while ($navFormGroupsRow = $navReportGroupsResults->fetch()) {
        $navigationItem = new Group($navFormGroupsRow['NAVIGATION_NAME'], null, $navFormGroupsRow['NAVIGATION_ID'], $navFormGroupsRow['PORTAL'], $navFormGroupsRow['SORT'], $navFormGroupsRow['DISPLAY_NAME'], $navFormGroupsRow['ROUTE']);
        
        $this->formGroups[$navFormGroupsRow['PORTAL']][$navFormGroupsRow['NAVIGATION_NAME']] = $navigationItem;
        $this->navigation[$navFormGroupsRow['NAVIGATION_NAME']] = $navigationItem;
        
      unset($navigationItem);
    }
    
    
    $navResults = $this->db->db_select('CORE_NAVIGATION', 'navigation')
      ->fields('navigation')
      ->leftJoin('CORE_NAVIGATION', 'parent_nav', 'parent_nav.NAVIGATION_ID = navigation.PARENT_NAVIGATION_ID')
      ->fields('parent_nav', array('NAVIGATION_NAME' => 'parent_NAVIGATION_NAME'))
      ->condition('navigation.NAVIGATION_TYPE', array('report_group', 'form_group'), 'NOT IN')
      ->orderBy('navigation.PORTAL')
      ->orderBy('navigation.NAVIGATION_TYPE')
      ->orderBy('navigation.SORT')
      ->execute();
    while ($navRow = $navResults->fetch()) {
      
      if ($navRow['NAVIGATION_TYPE'] == 'form') {
        $navigationItem = new Form($navRow['NAVIGATION_NAME'], $navRow['parent_NAVIGATION_NAME'], $navRow['NAVIGATION_ID'], $navRow['PORTAL'], $navRow['SORT'], $navRow['DISPLAY_NAME'], $navRow['ROUTE']);
        
        $this->navigation[$navRow['parent_NAVIGATION_NAME']]->addForm($navigationItem);
      }
      
      if ($navRow['NAVIGATION_TYPE'] == 'menu_action') {
        $navigationItem = new Menu($navRow['NAVIGATION_NAME'], $navRow['parent_NAVIGATION_NAME'], $navRow['NAVIGATION_ID'], $navRow['PORTAL'], $navRow['SORT'], $navRow['DISPLAY_NAME'], $navRow['ROUTE'], $navRow['DIVIDER_BEFORE'], $navRow['RECORD_LOADED'], $navRow['CONFIRMATION_MESSAGE']);
        
        $this->navigation[$navRow['parent_NAVIGATION_NAME']]->addMenuAction($navigationItem);
      }
      
      if ($navRow['NAVIGATION_TYPE'] == 'menu_report') {
        $navigationItem = new Menu($navRow['NAVIGATION_NAME'], $navRow['parent_NAVIGATION_NAME'], $navRow['NAVIGATION_ID'], $navRow['PORTAL'], $navRow['SORT'], $navRow['DISPLAY_NAME'], $navRow['ROUTE'], $navRow['DIVIDER_BEFORE'], $navRow['RECORD_LOADED'], $navRow['CONFIRMATION_MESSAGE']);
        
        $this->navigation[$navRow['parent_NAVIGATION_NAME']]->addMenuReport($navigationItem);
      }
      
      if ($navRow['NAVIGATION_TYPE'] == 'tab') {
        $navigationItem = new Tab($navRow['NAVIGATION_NAME'], $navRow['parent_NAVIGATION_NAME'], $navRow['NAVIGATION_ID'], $navRow['PORTAL'], $navRow['SORT'], $navRow['DISPLAY_NAME'], $navRow['ROUTE'], $navRow['DIVIDER_BEFORE'], $navRow['RECORD_LOADED'], $navRow['CONFIRMATION_MESSAGE']);
        
        $this->navigation[$navRow['parent_NAVIGATION_NAME']]->addTab($navigationItem);
      }
      
      if ($navRow['NAVIGATION_TYPE'] == 'report') {
        $navigationItem = new Tab($navRow['NAVIGATION_NAME'], $navRow['parent_NAVIGATION_NAME'], $navRow['NAVIGATION_ID'], $navRow['PORTAL'], $navRow['SORT'], $navRow['DISPLAY_NAME'], $navRow['ROUTE']);
        
        $this->navigation[$navRow['parent_NAVIGATION_NAME']]->addReport($navigationItem);
      }
      
      if ($navRow['NAVIGATION_TYPE'] == 'page') {
        $navigationItem = new Page($navRow['NAVIGATION_NAME'], $navRow['parent_NAVIGATION_NAME'], $navRow['NAVIGATION_ID'], $navRow['PORTAL'], $navRow['SORT'], $navRow['DISPLAY_NAME'], $navRow['ROUTE']);
        
        $this->pages[$navRow['PORTAL']][$navRow['NAVIGATION_NAME']] = $navigationItem;
      }
      
      $this->navigation[$navRow['NAVIGATION_NAME']] = $navigationItem;
      
      if ($navRow['ROUTE']) {
        $this->navigationByRoutes[$navRow['ROUTE']] = $navigationItem;
      }
      
      unset($navigationItem);
      
    }
  }
  
  public function awake($session, $permission, $request) {
    $this->session = $session;
    $this->permission = $permission;
    $this->request = $request;
  }
  
  public function getFormGroups() {
    if (isset($this->formGroups[$this->session->get('portal')])) {
      $groups = array();
      foreach($this->formGroups[$this->session->get('portal')] as $name => $group) {
        if ($this->permission->getPermissionForNavigationObject($name)) {
          $groups[] = $group;
        }
      }
      return $groups;
    }
  }
  
  public function getReportGroups() {
    if (isset($this->reportGroups[$this->session->get('portal')]))
      return $this->reportGroups[$this->session->get('portal')];
  }
  
  public function getRequestedForm() {
    $route = $this->request->getCurrentRequest()->attributes->get('_route');

    $routeNav = $this->navigationByRoutes[$route];

    if ($routeNav instanceof Form) {
      return $routeNav;
    }
    
    if ($routeNav instanceof Tab) {
      return $this->navigation[$routeNav->getParent()];
    } 
  }
  
  public function __sleep() {
    $this->db = null;
    
    return array('navigation', 'reportGroups', 'formGroups', 'navigationByRoutes');
  }
  
}