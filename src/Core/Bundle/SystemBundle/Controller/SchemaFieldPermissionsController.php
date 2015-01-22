<?php

namespace Kula\Core\Bundle\SystemBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class SchemaFieldPermissionsController extends Controller {
  
  public function usergroupAction($schema_table_id) {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('USERGROUP');

    $perm_add = $this->request->request->get('add_perm');
    
    if (count($perm_add) > 0) {
      $perm_poster = new \Kula\Component\Database\PosterFactory;
      foreach($perm_add as $table => $table_row) {
        foreach($table_row as $table_id => $row) {
          if ($row['PERMISSION']) {
          $return_charge_poster = $perm_poster->newPoster(array('CORE_PERMISSION_SCHEMA_FIELDS' => array('new' => array(
            'SCHEMA_FIELD_ID' => $table_id,
            'USERGROUP_ID' => $this->record->getSelectedRecordID(),
            'PERMISSION' => $row['PERMISSION']
          ))));
          }
        }
      }
      
    }
    
    $field_permissions = array();
    if ($this->record->getSelectedRecordID()) {
    // Get table permissions
    $field_permissions = $this->db()->select('CORE_SCHEMA_FIELDS', 'fields')
      ->fields('fields', array('SCHEMA_FIELD_ID', 'FIELD_NAME'))
      ->left_join('CORE_PERMISSION_SCHEMA_FIELDS', 'permfields', array('FIELD_PERMISSION_ID', 'PERMISSION'), 'permfields.SCHEMA_FIELD_ID = fields.SCHEMA_FIELD_ID AND USERGROUP_ID = '.$this->record->getSelectedRecordID())
      ->predicate('fields.SCHEMA_TABLE_ID', $schema_table_id)
      ->order_by('FIELD_NAME', 'ASC')
      ->execute()->fetchAll();
    }
    
    return $this->render('KulaSystemBundle:SchemaFieldPermissions:field_permissions.html.twig', array('field_permissions' => $field_permissions));
  }
  
  public function public_permissionsAction($schema_table_id) {
    $this->authorize();
    $this->processForm();
    
    $perm_add = $this->request->request->get('add_perm');
    
    if (count($perm_add) > 0) {
      $perm_poster = new \Kula\Component\Database\PosterFactory;
      foreach($perm_add as $table => $table_row) {
        foreach($table_row as $table_id => $row) {
          if ($row['PERMISSION']) {
          $return_charge_poster = $perm_poster->newPoster(array('CORE_PERMISSION_SCHEMA_FIELDS' => array('new' => array(
            'SCHEMA_FIELD_ID' => $table_id,
            'PERMISSION' => $row['PERMISSION']
          ))));
          }
        }
      }
      
    }
    
    // Get table permissions
    $field_permissions = $this->db()->select('CORE_SCHEMA_FIELDS', 'fields')
      ->fields('fields', array('SCHEMA_FIELD_ID', 'FIELD_NAME'))
      ->left_join('CORE_PERMISSION_SCHEMA_FIELDS', 'permfields', array('FIELD_PERMISSION_ID', 'PERMISSION'), 'permfields.SCHEMA_FIELD_ID = fields.SCHEMA_FIELD_ID AND USERGROUP_ID IS NULL AND ROLE_ID IS NULL')
        ->predicate('fields.SCHEMA_TABLE_ID', $schema_table_id)
        ->order_by('FIELD_NAME', 'ASC')
      ->execute()->fetchAll();

    return $this->render('KulaSystemBundle:SchemaFieldPermissions:field_permissions.html.twig', array('field_permissions' => $field_permissions));
  }
  
  public function roleAction($schema_table_id) {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('ROLE');
    
    $perm_add = $this->request->request->get('add_perm');
    
    if (count($perm_add) > 0) {
      $perm_poster = new \Kula\Component\Database\PosterFactory;
      foreach($perm_add as $table => $table_row) {
        foreach($table_row as $table_id => $row) {
          if ($row['PERMISSION']) {
          $return_charge_poster = $perm_poster->newPoster(array('CORE_PERMISSION_SCHEMA_FIELDS' => array('new' => array(
            'SCHEMA_FIELD_ID' => $table_id,
            'ROLE_ID' => $this->record->getSelectedRecordID(),
            'PERMISSION' => $row['PERMISSION']
          ))));
          }
        }
      }
      
    }
    
    $field_permissions = array();
    if ($this->record->getSelectedRecordID()) {
    // Get table permissions
    $field_permissions = $this->db()->select('CORE_SCHEMA_FIELDS', 'fields')
      ->fields('fields', array('SCHEMA_FIELD_ID', 'FIELD_NAME'))
      ->left_join('CORE_PERMISSION_SCHEMA_FIELDS', 'permfields', array('FIELD_PERMISSION_ID', 'PERMISSION'), 'permfields.SCHEMA_FIELD_ID = fields.SCHEMA_FIELD_ID AND ROLE_ID = '.$this->record->getSelectedRecordID())
        ->predicate('fields.SCHEMA_TABLE_ID', $schema_table_id)
        ->order_by('FIELD_NAME', 'ASC')
      ->execute()->fetchAll();
    }
    
    return $this->render('KulaSystemBundle:SchemaFieldPermissions:field_permissions.html.twig', array('field_permissions' => $field_permissions));
  }
  
  
}