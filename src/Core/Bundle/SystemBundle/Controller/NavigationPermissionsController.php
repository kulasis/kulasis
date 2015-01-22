<?php

namespace Kula\Core\Bundle\SystemBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class NavigationPermissionsController extends Controller {
  
  public function usergroupAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.UserGroup');
    
    $perm_add = $this->request->request->get('add_perm');
    
    if (count($perm_add) > 0) {
      $perm_poster = new \Kula\Component\Database\PosterFactory;
      foreach($perm_add as $table => $table_row) {
        foreach($table_row as $table_id => $row) {
          if ($row['PERMISSION']) {
          $return_charge_poster = $perm_poster->newPoster(array('CORE_PERMISSION_NAVIGATION' => array('new' => array(
            'NAVIGATION_ID' => $table_id,
            'USERGROUP_ID' => $this->record->getSelectedRecordID(),
            'PERMISSION' => $row['PERMISSION']
          ))));
          }
        }
      }
      
    }
    
    $nav_permissions = array();
    if ($this->record->getSelectedRecordID()) {
    // Get table permissions
    $nav_permissions = $this->db()->db_select('CORE_NAVIGATION', 'nav')
      ->fields('nav', array('NAVIGATION_ID', 'NAVIGATION_NAME', 'NAVIGATION_TYPE', 'PORTAL'))
      //->left_join('CORE_NAVIGATION', 'navparent', array('NAVIGATION_ID'), 'nav.PARENT_NAVIGATION_ID = navparent.NAVIGATION_ID')
      ->left_join('CORE_PERMISSION_NAVIGATION', 'permnav', array('NAVIGATION_PERMISSION_ID', 'PERMISSION'), 'permnav.NAVIGATION_ID = nav.NAVIGATION_ID AND USERGROUP_ID = '.$this->record->getSelectedRecordID())
        ->predicate('nav.PORTAL', $this->record->getSelectedRecord()['PORTAL'])
      ->order_by('NAVIGATION_NAME', 'ASC')
      ->execute()->fetchAll();
    }
    
    return $this->render('KulaCoreSystemBundle:NavigationPermissions:navigation_permissions.html.twig', array('nav_permissions' => $nav_permissions));
  }
  
  public function public_permissionsAction() {
    $this->authorize();
    $this->processForm();
    
    $perm_add = $this->request->request->get('add_perm');
    
    if (count($perm_add) > 0) {
      $perm_poster = new \Kula\Component\Database\PosterFactory;
      foreach($perm_add as $table => $table_row) {
        foreach($table_row as $table_id => $row) {
          if ($row['PERMISSION']) {
          $return_charge_poster = $perm_poster->newPoster(array('CORE_PERMISSION_NAVIGATION' => array('new' => array(
            'NAVIGATION_ID' => $table_id,
            'PERMISSION' => $row['PERMISSION']
          ))));
          }
        }
      }
      
    }
    
    // Get table permissions
    $nav_permissions = $this->db()->db_select('CORE_NAVIGATION', 'nav')
      ->fields('nav', array('NAVIGATION_ID', 'NAVIGATION_NAME', 'NAVIGATION_TYPE', 'PORTAL'))
      ->left_join('CORE_PERMISSION_NAVIGATION', 'permnav', array('NAVIGATION_PERMISSION_ID', 'PERMISSION'), 'permnav.NAVIGATION_ID = nav.NAVIGATION_ID AND USERGROUP_ID IS NULL AND ROLE_ID IS NULL')
      ->order_by('NAVIGATION_NAME', 'ASC')
      ->execute()->fetchAll();

    return $this->render('KulaCoreSystemBundle:NavigationPermissions:navigation_permissions.html.twig', array('nav_permissions' => $nav_permissions));
  }
  
  public function roleAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.User.Role');
    
    $perm_add = $this->request->request->get('add_perm');
    
    if (count($perm_add) > 0) {
      $perm_poster = new \Kula\Component\Database\PosterFactory;
      foreach($perm_add as $table => $table_row) {
        foreach($table_row as $table_id => $row) {
          if ($row['PERMISSION']) {
          $return_charge_poster = $perm_poster->newPoster(array('CORE_PERMISSION_NAVIGATION' => array('new' => array(
            'NAVIGATION_ID' => $table_id,
            'ROLE_ID' => $this->record->getSelectedRecordID(),
            'PERMISSION' => $row['PERMISSION']
          ))));
          }
        }
      }
      
    }
    
    $nav_permissions = array();
    if ($this->record->getSelectedRecordID()) {
    // Get table permissions
    $nav_permissions = $this->db()->db_select('CORE_NAVIGATION', 'nav')
      ->fields('nav', array('NAVIGATION_ID', 'NAVIGATION_NAME', 'NAVIGATION_TYPE', 'PORTAL'))
      ->left_join('CORE_PERMISSION_NAVIGATION', 'permnav', array('NAVIGATION_PERMISSION_ID', 'PERMISSION'), 'permnav.NAVIGATION_ID = nav.NAVIGATION_ID AND ROLE_ID ='.$this->record->getSelectedRecordID())
      ->order_by('NAVIGATION_NAME', 'ASC')
      ->execute()->fetchAll();
    }
    
    return $this->render('KulaCoreSystemBundle:NavigationPermissions:navigation_permissions.html.twig', array('nav_permissions' => $nav_permissions));
  }
  
  
}