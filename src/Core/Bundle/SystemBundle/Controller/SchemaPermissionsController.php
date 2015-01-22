<?php

namespace Kula\Core\Bundle\SystemBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class SchemaPermissionsController extends Controller {
  
  public function usergroupAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('USERGROUP');
    
    $perm_add = $this->request->request->get('add_perm');
    
    if (count($perm_add) > 0) {
      $perm_poster = new \Kula\Component\Database\PosterFactory;
      foreach($perm_add as $table => $table_row) {
        foreach($table_row as $table_id => $row) {
          if ($row['PERMISSION_ADD'] || $row['PERMISSION_EDIT'] || $row['PERMISSION_DELETE']) {
          $return_charge_poster = $perm_poster->newPoster(array('CORE_PERMISSION_SCHEMA_TABLES' => array('new' => array(
            'SCHEMA_TABLE_ID' => $table_id,
            'USERGROUP_ID' => $this->record->getSelectedRecordID(),
            'PERMISSION_ADD' => $row['PERMISSION_ADD'],
            'PERMISSION_EDIT' => $row['PERMISSION_EDIT'],
            'PERMISSION_DELETE' => $row['PERMISSION_DELETE']
          ))));
          }
        }
      }
      
    }
    
    $table_permissions = array();
    if ($this->record->getSelectedRecordID()) {
    // Get table permissions
    $table_permissions = $this->db()->select('CORE_SCHEMA_TABLES', 'tables')
      ->fields('tables', array('SCHEMA_TABLE_ID', 'SCHEMA_TABLE_NAME'))
      ->left_join('CORE_PERMISSION_SCHEMA_TABLES', 'permtables', array('TABLE_PERMISSION_ID', 'PERMISSION_ADD', 'PERMISSION_DELETE', 'PERMISSION_EDIT'), 'permtables.SCHEMA_TABLE_ID = tables.SCHEMA_TABLE_ID AND USERGROUP_ID = '.$this->record->getSelectedRecordID())
      ->order_by('SCHEMA_TABLE_NAME', 'ASC')
      ->execute()->fetchAll();
    }
    
    return $this->render('KulaSystemBundle:SchemaPermissions:table_permissions.html.twig', array('table_permissions' => $table_permissions));
  }
  
  public function public_permissionsAction() {
    $this->authorize();
    $this->processForm();
    
    $perm_add = $this->request->request->get('add_perm');
    
    if (count($perm_add) > 0) {
      $perm_poster = new \Kula\Component\Database\PosterFactory;
      foreach($perm_add as $table => $table_row) {
        foreach($table_row as $table_id => $row) {
          if ($row['PERMISSION_ADD'] || $row['PERMISSION_EDIT'] || $row['PERMISSION_DELETE']) {
          $return_charge_poster = $perm_poster->newPoster(array('CORE_PERMISSION_SCHEMA_TABLES' => array('new' => array(
            'SCHEMA_TABLE_ID' => $table_id,
            'PERMISSION_ADD' => $row['PERMISSION_ADD'],
            'PERMISSION_EDIT' => $row['PERMISSION_EDIT'],
            'PERMISSION_DELETE' => $row['PERMISSION_DELETE']
          ))));
          }
        }
      }
      
    }
    
    // Get table permissions
    $table_permissions = $this->db()->select('CORE_SCHEMA_TABLES', 'tables')
      ->fields('tables', array('SCHEMA_TABLE_ID', 'SCHEMA_TABLE_NAME'))
      ->left_join('CORE_PERMISSION_SCHEMA_TABLES', 'permtables', array('TABLE_PERMISSION_ID', 'PERMISSION_ADD', 'PERMISSION_DELETE', 'PERMISSION_EDIT'), 'permtables.SCHEMA_TABLE_ID = tables.SCHEMA_TABLE_ID AND USERGROUP_ID IS NULL AND ROLE_ID IS NULL')
      ->order_by('SCHEMA_TABLE_NAME', 'ASC')
      ->execute()->fetchAll();

    return $this->render('KulaSystemBundle:SchemaPermissions:table_permissions.html.twig', array('table_permissions' => $table_permissions));
  }
  
  public function roleAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('ROLE');
    
    $perm_add = $this->request->request->get('add_perm');
    
    if (count($perm_add) > 0) {
      $perm_poster = new \Kula\Component\Database\PosterFactory;
      foreach($perm_add as $table => $table_row) {
        foreach($table_row as $table_id => $row) {
          if ($row['PERMISSION_ADD'] || $row['PERMISSION_EDIT'] || $row['PERMISSION_DELETE']) {
          $return_charge_poster = $perm_poster->newPoster(array('CORE_PERMISSION_SCHEMA_TABLES' => array('new' => array(
            'SCHEMA_TABLE_ID' => $table_id,
            'ROLE_ID' => $this->record->getSelectedRecordID(),
            'PERMISSION_ADD' => $row['PERMISSION_ADD'],
            'PERMISSION_EDIT' => $row['PERMISSION_EDIT'],
            'PERMISSION_DELETE' => $row['PERMISSION_DELETE']
          ))));
          }
        }
      }
      
    }
    
    $table_permissions = array();
    if ($this->record->getSelectedRecordID()) {
    // Get table permissions
    $table_permissions = $this->db()->select('CORE_SCHEMA_TABLES', 'tables')
      ->fields('tables', array('SCHEMA_TABLE_ID', 'SCHEMA_TABLE_NAME'))
      ->left_join('CORE_PERMISSION_SCHEMA_TABLES', 'permtables', array('TABLE_PERMISSION_ID', 'PERMISSION_ADD', 'PERMISSION_DELETE', 'PERMISSION_EDIT'), 'permtables.SCHEMA_TABLE_ID = tables.SCHEMA_TABLE_ID AND ROLE_ID ='.$this->record->getSelectedRecordID())
      ->order_by('SCHEMA_TABLE_NAME', 'ASC')
      ->execute()->fetchAll();
    }
    
    return $this->render('KulaSystemBundle:SchemaPermissions:table_permissions.html.twig', array('table_permissions' => $table_permissions));
  }
  
  
}