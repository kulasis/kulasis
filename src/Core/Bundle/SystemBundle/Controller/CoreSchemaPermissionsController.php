<?php

namespace Kula\Core\Bundle\SystemBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class CoreSchemaPermissionsController extends Controller {
  
  public function usergroupAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.Usergroup');
    
    $perm_preposter = $this->prePoster()->load($this->request->request->get('add_perm'));
    $perm_poster = $this->poster();
    foreach($perm_preposter as $record) {
      if ($record->getField('Core.Permission.Schema.Table.Permission.Add') OR 
          $record->getField('Core.Permission.Schema.Table.Permission.Edit') OR 
          $record->getField('Core.Permission.Schema.Table.Permission.Delete')) {
        $perm_poster->add('Core.Permission.Schema.Table', 'new', array(
          'Core.Permission.Schema.Table.SchemaTableID' => $record->getID(),
          'Core.Permission.Schema.Table.UsergroupID' => $this->record->getSelectedRecordID(),
          'Core.Permission.Schema.Table.Permission.Add' => $record->getField('Core.Permission.Schema.Table.Permission.Add'),
          'Core.Permission.Schema.Table.Permission.Edit' => $record->getField('Core.Permission.Schema.Table.Permission.Edit'),
          'Core.Permission.Schema.Table.Permission.Delete' => $record->getField('Core.Permission.Schema.Table.Permission.Delete')
        ));
        $return_charge_poster = $perm_poster->process();
      }
    }
    
    $table_permissions = array();
    if ($this->record->getSelectedRecordID()) {
    // Get table permissions
    $table_permissions = $this->db()->db_select('CORE_SCHEMA_TABLES', 'tables')
      ->fields('tables', array('SCHEMA_TABLE_ID', 'TABLE_NAME'))
      ->leftJoin('CORE_PERMISSION_SCHEMA_TABLES', 'permtables', 'permtables.SCHEMA_TABLE_ID = tables.SCHEMA_TABLE_ID AND USERGROUP_ID = '.$this->record->getSelectedRecordID())
      ->fields('permtables', array('TABLE_PERMISSION_ID', 'PERMISSION_ADD', 'PERMISSION_DELETE', 'PERMISSION_EDIT'))
      ->orderBy('TABLE_NAME', 'ASC')
      ->execute()->fetchAll();
    }
    
    return $this->render('KulaCoreSystemBundle:SchemaPermissions:table_permissions.html.twig', array('table_permissions' => $table_permissions));
  }
  
  public function public_permissionsAction() {
    $this->authorize();
    $this->processForm();
    
    $perm_preposter = $this->prePoster()->load($this->request->request->get('add_perm'));
    $perm_poster = $this->poster();
    foreach($perm_preposter as $record) {
      if ($record->getField('Core.Permission.Schema.Table.Permission.Add') OR 
          $record->getField('Core.Permission.Schema.Table.Permission.Edit') OR 
          $record->getField('Core.Permission.Schema.Table.Permission.Delete')) {
        $perm_poster->add('Core.Permission.Schema.Table', 'new', array(
          'Core.Permission.Schema.Table.SchemaTableID' => $record->getID(),
          'Core.Permission.Schema.Table.Permission.Add' => $record->getField('Core.Permission.Schema.Table.Permission.Add'),
          'Core.Permission.Schema.Table.Permission.Edit' => $record->getField('Core.Permission.Schema.Table.Permission.Edit'),
          'Core.Permission.Schema.Table.Permission.Delete' => $record->getField('Core.Permission.Schema.Table.Permission.Delete')
        ));
        $return_charge_poster = $perm_poster->process();
      }
    }
    
    // Get table permissions
    $table_permissions = $this->db()->db_select('CORE_SCHEMA_TABLES', 'tables')
      ->fields('tables', array('SCHEMA_TABLE_ID', 'TABLE_NAME'))
      ->leftJoin('CORE_PERMISSION_SCHEMA_TABLES', 'permtables', 'permtables.SCHEMA_TABLE_ID = tables.SCHEMA_TABLE_ID AND USERGROUP_ID IS NULL AND ROLE_ID IS NULL')
      ->fields('permtables', array('TABLE_PERMISSION_ID', 'PERMISSION_ADD', 'PERMISSION_DELETE', 'PERMISSION_EDIT'))
      ->orderBy('TABLE_NAME', 'ASC')
      ->execute()->fetchAll();

    return $this->render('KulaCoreSystemBundle:SchemaPermissions:table_permissions.html.twig', array('table_permissions' => $table_permissions));
  }
  
  public function roleAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.User.Role');
    
    $perm_add = $this->request->request->get('add_perm');
    
    $perm_preposter = $this->prePoster()->load($this->request->request->get('add_perm'));
    $perm_poster = $this->poster();
    foreach($perm_preposter as $record) {
      if ($record->getField('Core.Permission.Schema.Table.Permission.Add') OR 
          $record->getField('Core.Permission.Schema.Table.Permission.Edit') OR 
          $record->getField('Core.Permission.Schema.Table.Permission.Delete')) {
        $perm_poster->add('Core.Permission.Schema.Table', 'new', array(
          'Core.Permission.Schema.Table.SchemaTableID' => $record->getID(),
          'Core.Permission.Schema.Table.RoleID' => $this->record->getSelectedRecordID(),
          'Core.Permission.Schema.Table.Permission.Add' => $record->getField('Core.Permission.Schema.Table.Permission.Add'),
          'Core.Permission.Schema.Table.Permission.Edit' => $record->getField('Core.Permission.Schema.Table.Permission.Edit'),
          'Core.Permission.Schema.Table.Permission.Delete' => $record->getField('Core.Permission.Schema.Table.Permission.Delete')
        ));
        $return_charge_poster = $perm_poster->process();
      }
    }
    
    $table_permissions = array();
    if ($this->record->getSelectedRecordID()) {
    // Get table permissions
    $table_permissions = $this->db()->db_select('CORE_SCHEMA_TABLES', 'tables')
      ->fields('tables', array('SCHEMA_TABLE_ID', 'TABLE_NAME'))
      ->leftJoin('CORE_PERMISSION_SCHEMA_TABLES', 'permtables', 'permtables.SCHEMA_TABLE_ID = tables.SCHEMA_TABLE_ID AND ROLE_ID ='.$this->record->getSelectedRecordID())
      ->fields('permtables', array('TABLE_PERMISSION_ID', 'PERMISSION_ADD', 'PERMISSION_DELETE', 'PERMISSION_EDIT'))
      ->orderBy('TABLE_NAME', 'ASC')
      ->execute()->fetchAll();
    }
    
    return $this->render('KulaCoreSystemBundle:SchemaPermissions:table_permissions.html.twig', array('table_permissions' => $table_permissions));
  }
  
  
}