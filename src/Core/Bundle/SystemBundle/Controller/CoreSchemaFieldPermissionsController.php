<?php

namespace Kula\Core\Bundle\SystemBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class CoreSchemaFieldPermissionsController extends Controller {
  
  public function usergroupAction($schema_table_id) {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.Usergroup');
    
    $perm_preposter = $this->prePoster()->load($this->request->request->get('add_perm'), 'Core.Permission.Schema.Field.Permission');
    $perm_poster = $this->poster();
    foreach($perm_preposter as $record) {
      $perm_poster->add('Core.Permission.Schema.Field', 'new', array(
        'Core.Permission.Schema.Field.SchemaFieldID' => $record->getID(),
        'Core.Permission.Schema.Field.UsergroupID' => $this->record->getSelectedRecordID(),
        'Core.Permission.Schema.Field.Permission' => $record->getField('Core.Permission.Schema.Field.Permission')
      ));
      $return_charge_poster = $perm_poster->process();
    }
    
    $field_permissions = array();
    if ($this->record->getSelectedRecordID()) {
    // Get table permissions
    $field_permissions = $this->db()->db_select('CORE_SCHEMA_FIELDS', 'fields')
      ->fields('fields', array('SCHEMA_FIELD_ID', 'FIELD_NAME'))
      ->leftJoin('CORE_PERMISSION_SCHEMA_FIELDS', 'permfields', 'permfields.SCHEMA_FIELD_ID = fields.SCHEMA_FIELD_ID AND USERGROUP_ID = '.$this->record->getSelectedRecordID())
      ->fields('permfields', array('FIELD_PERMISSION_ID', 'PERMISSION'))
      ->condition('fields.SCHEMA_TABLE_ID', $schema_table_id)
      ->orderBy('FIELD_NAME', 'ASC')
      ->execute()->fetchAll();
    }
    
    return $this->render('KulaCoreSystemBundle:SchemaFieldPermissions:field_permissions.html.twig', array('field_permissions' => $field_permissions));
  }
  
  public function public_permissionsAction($schema_table_id) {
    $this->authorize();
    $this->processForm();
    
    $perm_preposter = $this->prePoster()->load($this->request->request->get('add_perm'), 'Core.Permission.Schema.Field.Permission');
    $perm_poster = $this->poster();
    foreach($perm_preposter as $record) {
      $perm_poster->add('Core.Permission.Schema.Field', 'new', array(
        'Core.Permission.Schema.Field.SchemaFieldID' => $record->getID(),
        'Core.Permission.Schema.Field.Permission' => $record->getField('Core.Permission.Schema.Field.Permission')
      ));
      $return_charge_poster = $perm_poster->process();
    }
    
    // Get table permissions
    $field_permissions = $this->db()->db_select('CORE_SCHEMA_FIELDS', 'fields')
      ->fields('fields', array('SCHEMA_FIELD_ID', 'FIELD_NAME'))
      ->leftJoin('CORE_PERMISSION_SCHEMA_FIELDS', 'permfields', 'permfields.SCHEMA_FIELD_ID = fields.SCHEMA_FIELD_ID AND USERGROUP_ID IS NULL AND ROLE_ID IS NULL')
      ->fields('permfields', array('FIELD_PERMISSION_ID', 'PERMISSION'))
      ->condition('fields.SCHEMA_TABLE_ID', $schema_table_id)
      ->orderBy('FIELD_NAME', 'ASC')
      ->execute()->fetchAll();

    return $this->render('KulaCoreSystemBundle:SchemaFieldPermissions:field_permissions.html.twig', array('field_permissions' => $field_permissions));
  }
  
  public function roleAction($schema_table_id) {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.User.Role');
    
    $perm_add = $this->request->request->get('add_perm');
    
    $perm_preposter = $this->prePoster()->load($this->request->request->get('add_perm'), 'Core.Permission.Schema.Field.Permission');
    $perm_poster = $this->poster();
    foreach($perm_preposter as $record) {
      $perm_poster->add('Core.Permission.Schema.Field', 'new', array(
        'Core.Permission.Schema.Field.SchemaFieldID' => $record->getID(),
        'Core.Permission.Schema.Field.RoleID' => $this->record->getSelectedRecordID(),
        'Core.Permission.Schema.Field.Permission' => $record->getField('Core.Permission.Schema.Field.Permission')
      ));
      $return_charge_poster = $perm_poster->process();
    }
    
    $field_permissions = array();
    if ($this->record->getSelectedRecordID()) {
    // Get table permissions
    $field_permissions = $this->db()->db_select('CORE_SCHEMA_FIELDS', 'fields')
      ->fields('fields', array('SCHEMA_FIELD_ID', 'FIELD_NAME'))
      ->leftJoin('CORE_PERMISSION_SCHEMA_FIELDS', 'permfields', 'permfields.SCHEMA_FIELD_ID = fields.SCHEMA_FIELD_ID AND ROLE_ID = '.$this->record->getSelectedRecordID())
      ->fields('permfields', array('FIELD_PERMISSION_ID', 'PERMISSION'))
      ->condition('fields.SCHEMA_TABLE_ID', $schema_table_id)
      ->orderBy('FIELD_NAME', 'ASC')
      ->execute()->fetchAll();
    }
    
    return $this->render('KulaCoreSystemBundle:SchemaFieldPermissions:field_permissions.html.twig', array('field_permissions' => $field_permissions));
  }
  
  
}