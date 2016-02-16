<?php

namespace Kula\Core\Bundle\SystemBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class CoreNavigationPermissionsController extends Controller {
  
  public function usergroupAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.Usergroup');
    
    $perm_preposter = $this->prePoster()->load($this->request->request->get('add_perm'), 'Core.Permission.Navigation.Permission');
    $perm_poster = $this->poster();
    foreach($perm_preposter as $record) {
      $perm_poster->add('Core.Permission.Navigation', 'new', array(
        'Core.Permission.Navigation.NavigationID' => $record->getID(),
        'Core.Permission.Navigation.UsergroupID' => $this->record->getSelectedRecordID(),
        'Core.Permission.Navigation.Permission' => $record->getField('Core.Permission.Navigation.Permission')
      ));
      $return_charge_poster = $perm_poster->process();
    }
    
    $nav_permissions = array();
    if ($this->record->getSelectedRecordID()) {
    // Get table permissions
    $nav_permissions = $this->db()->db_select('CORE_NAVIGATION', 'nav')
      ->fields('nav', array('NAVIGATION_ID', 'NAVIGATION_NAME', 'NAVIGATION_TYPE'))
      ->leftJoin('CORE_PERMISSION_NAVIGATION', 'permnav', 'permnav.NAVIGATION_ID = nav.NAVIGATION_ID AND USERGROUP_ID = '.$this->record->getSelectedRecordID())
      ->fields('permnav', array('NAVIGATION_PERMISSION_ID', 'PERMISSION'))
      ->condition('nav.NAVIGATION_NAME', $this->record->getSelectedRecord()['PORTAL'].'.%', 'LIKE')
      ->orderBy('NAVIGATION_NAME', 'ASC')
      ->execute()->fetchAll();
    }
    
    return $this->render('KulaCoreSystemBundle:NavigationPermissions:navigation_permissions.html.twig', array('nav_permissions' => $nav_permissions));
  }
  
  public function public_permissionsAction() {
    $this->authorize();
    $this->processForm();
    
    $perm_preposter = $this->prePoster()->load($this->request->request->get('add_perm'), 'Core.Permission.Navigation.Permission');
    $perm_poster = $this->poster();
    foreach($perm_preposter as $record) {
      $perm_poster->add('Core.Permission.Navigation', 'new', array(
        'Core.Permission.Navigation.NavigationID' => $record->getID(),
        'Core.Permission.Navigation.Permission' => $record->getField('Core.Permission.Navigation.Permission')
      ));
      $return_charge_poster = $perm_poster->process();
    }
    
    // Get table permissions
    $nav_permissions = $this->db()->db_select('CORE_NAVIGATION', 'nav')
      ->fields('nav', array('NAVIGATION_ID', 'NAVIGATION_NAME', 'NAVIGATION_TYPE'))
      ->leftJoin('CORE_PERMISSION_NAVIGATION', 'permnav', 'permnav.NAVIGATION_ID = nav.NAVIGATION_ID AND USERGROUP_ID IS NULL AND ROLE_ID IS NULL')
      ->fields('permnav', array('NAVIGATION_PERMISSION_ID', 'PERMISSION'))
      ->orderBy('NAVIGATION_NAME', 'ASC')
      ->execute()->fetchAll();

    return $this->render('KulaCoreSystemBundle:NavigationPermissions:navigation_permissions.html.twig', array('nav_permissions' => $nav_permissions));
  }
  
  public function roleAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.User.Role');
    
    $perm_preposter = $this->prePoster()->load($this->request->request->get('add_perm'), 'Core.Permission.Navigation.Permission');
    $perm_poster = $this->poster();
    foreach($perm_preposter as $record) {
      $perm_poster->add('Core.Permission.Navigation', 'new', array(
        'Core.Permission.Navigation.NavigationID' => $record->getID(),
        'Core.Permission.Navigation.RoleID' => $this->record->getSelectedRecordID(),
        'Core.Permission.Navigation.Permission' => $record->getField('Core.Permission.Navigation.Permission')
      ));
      $return_charge_poster = $perm_poster->process();
    }
    
    $nav_permissions = array();
    if ($this->record->getSelectedRecordID()) {
    // Get table permissions
    $nav_permissions = $this->db()->db_select('CORE_NAVIGATION', 'nav')
      ->fields('nav', array('NAVIGATION_ID', 'NAVIGATION_NAME', 'NAVIGATION_TYPE'))
      ->leftJoin('CORE_PERMISSION_NAVIGATION', 'permnav', 'permnav.NAVIGATION_ID = nav.NAVIGATION_ID AND ROLE_ID ='.$this->record->getSelectedRecordID())
      ->condition('nav.NAVIGATION_NAME', $this->record->getSelectedRecord()['PORTAL'].'.%', 'LIKE')
      ->fields('permnav', array('NAVIGATION_PERMISSION_ID', 'PERMISSION'))
      ->orderBy('NAVIGATION_NAME', 'ASC')
      ->execute()->fetchAll();
    }
    
    return $this->render('KulaCoreSystemBundle:NavigationPermissions:navigation_permissions.html.twig', array('nav_permissions' => $nav_permissions));
  }
  
  
}