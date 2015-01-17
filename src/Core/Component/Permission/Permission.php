<?php

namespace Kula\Core\Component\Permission;

class Permission {
  
  private $navigation;
  
  private $tables;
  private $fields;
  
  const READ = 1; // field level
  const WRITE = 2; // field level
  const MASS_CHANGE = 3; // field level
  
  const ADD = 8; // table level
  const DELETE = 9; // table level
  
  public function __construct($db, $session) {
    $this->db = $db;
    $this->session = $session;
  }
  
  public function getPermissionForNavigationObject($navigation_name) {
    
    $this->loadNavigationPermissionObject();
    
    // If item not set in array, full permissions to object
    if (!isset($this->navigation[$navigation_name]) OR $this->navigation[$navigation_name] == '')
      return true;
    else {
      if ($this->navigation[$navigation_name] == 'Y')
        return true;
      else
        return false;
    }
  }
  
  public function getPermissionForSchemaObject($db_table, $db_field, $permission) {
    
    $this->loadSchemaPermissionObject();
    
    if ($permission == self::ADD) {
      
      // if add permission not set
      if (!isset($this->tables[$db_table]['add']) AND $permission == self::ADD)
        return true;
      
      if (self::ADD AND $this->tables[$db_table]['add'] == 'Y')
        return true;
      else
        return false;
    
    } elseif ($permission == self::DELETE) {
      // if delete permission not set
      if (!isset($this->tables[$db_table]['delete']) AND $permission == self::DELETE)
        return true;
      
      if (self::DELETE AND $this->tables[$db_table]['delete'] == 'Y')
        return true;
      else
        return false;
      
    } else {
    
      // If item not set in array, full permissions to object
      if (!isset($this->fields[$db_table][$db_field]) AND !isset($this->tables[$db_table]['edit']))
        return true;
      else {
        // Check for field first
        if (isset($this->fields[$db_table][$db_field]))
          return $this->checkSchemaPermission($this->fields[$db_table][$db_field], $permission);
        else
          return $this->checkSchemaPermission($this->tables[$db_table]['edit'], $permission);
        }
      }
  }
  
  private function loadSchemaPermissionObject() {
    if (!$this->tables)
      $this->loadTablePermissions();
    if (!$this->fields)
      $this->loadFieldPermissions();
  }
  
  private function loadNavigationPermissionObject() {
    if (!$this->navigation)
      $this->loadNavigationPermissions();
  }

  private function loadNavigationPermissions() {

    $usergroup_condition = $this->db->db_or('OR');
    $usergroup_condition = $usergroup_condition->condition('USERGROUP_ID', $this->session->get('usergroup_id'));
    $usergroup_condition = $usergroup_condition->condition('USERGROUP_ID', NULL);
    
    $role_condition = $this->db->db_or('OR');
    $role_condition = $role_condition->condition('ROLE_ID', $this->session->get('role_id'));
    $role_condition = $role_condition->condition('ROLE_ID', NULL);
    
    $public_condition = $this->db->db_or('OR');
    $public_condition = $public_condition->condition('USERGROUP_ID', NULL);
    $public_condition = $public_condition->condition('ROLE_ID', NULL);
    
    // schema table query
    $result = $this->db->db_select('CORE_PERMISSION_NAVIGATION')
      ->fields('CORE_PERMISSION_NAVIGATION', array('USERGROUP_ID', 'ROLE_ID', 'PERMISSION'))
      ->join('CORE_NAVIGATION', 'CORE_NAVIGATION', 'CORE_NAVIGATION.NAVIGATION_ID = CORE_PERMISSION_NAVIGATION.NAVIGATION_ID')
      ->fields('CORE_NAVIGATION', array('NAVIGATION_NAME'))
      ->condition($usergroup_condition)
      ->condition($role_condition)
      ->condition($public_condition)
      ->orderBy('ROLE_ID', 'DESC')
      ->orderBy('USERGROUP_ID', 'DESC');
    //echo $table_result->sql();
    $result =  $result->execute();
    while ($row = $result->fetch()) {
      
      if (!isset($this->navigation[$row['NAVIGATION_NAME']]) && $row['ROLE_ID'] && $row['PERMISSION'] != '') {
        $this->navigation[$row['NAVIGATION_NAME']] = $row['PERMISSION'];
      } elseif (!isset($this->navigation[$row['NAVIGATION_NAME']]) && $row['USERGROUP_ID'] && $row['PERMISSION'] != '') {
        $this->navigation[$row['NAVIGATION_NAME']] = $row['PERMISSION'];
      } elseif (!isset($this->navigation[$row['NAVIGATION_NAME']]) && $row['PERMISSION'] != '') {
        $this->navigation[$row['NAVIGATION_NAME']] = $row['PERMISSION'];
      }
      
    }
  }
  
  private function loadTablePermissions() {
    
    $usergroup_condition = $this->db->db_or('OR');
    $usergroup_condition = $usergroup_condition->condition('USERGROUP_ID', $this->session->get('usergroup_id'));
    $usergroup_condition = $usergroup_condition->condition('USERGROUP_ID', NULL);
    
    $role_condition = $this->db->db_or('OR');
    $role_condition = $role_condition->condition('ROLE_ID', $this->session->get('role_id'));
    $role_condition = $role_condition->condition('ROLE_ID', NULL);
    
    $public_condition = $this->db->db_or('OR');
    $public_condition = $public_condition->condition('USERGROUP_ID', NULL);
    $public_condition = $public_condition->condition('ROLE_ID', NULL);
    
    // schema table query
    $result = $this->db->db_select('CORE_PERMISSION_SCHEMA_TABLES')
      ->fields('CORE_PERMISSION_SCHEMA_TABLES', array('USERGROUP_ID', 'ROLE_ID', 'PERMISSION_ADD', 'PERMISSION_DELETE', 'PERMISSION_EDIT'))
      ->join('CORE_SCHEMA_TABLES', 'CORE_SCHEMA_TABLES', 'CORE_SCHEMA_TABLES.SCHEMA_TABLE_ID = CORE_PERMISSION_SCHEMA_TABLES.SCHEMA_TABLE_ID')
      ->fields('CORE_SCHEMA_TABLES', array('SCHEMA_TABLE_NAME'))
      ->condition($usergroup_condition)
      ->condition($role_condition)
      ->condition($public_condition)
      ->orderBy('ROLE_ID', 'DESC')
      ->orderBy('USERGROUP_ID', 'DESC');
    //echo $table_result->sql();
    $result =  $result->execute();
    while ($row = $result->fetch()) {
      
      if (!isset($this->tables[$row['SCHEMA_TABLE_NAME']]) && $row['ROLE_ID']) {
        $this->tables[$row['SCHEMA_TABLE_NAME']]['add'] = $row['PERMISSION_ADD'];
        $this->tables[$row['SCHEMA_TABLE_NAME']]['edit'] = $row['PERMISSION_EDIT'];
        $this->tables[$row['SCHEMA_TABLE_NAME']]['delete'] = $row['PERMISSION_DELETE'];
      } elseif (!isset($this->tables[$row['SCHEMA_TABLE_NAME']]) && $row['USERGROUP_ID']) {
        $this->tables[$row['SCHEMA_TABLE_NAME']]['add'] = $row['PERMISSION_ADD'];
        $this->tables[$row['SCHEMA_TABLE_NAME']]['edit'] = $row['PERMISSION_EDIT'];
        $this->tables[$row['SCHEMA_TABLE_NAME']]['delete'] = $row['PERMISSION_DELETE'];
      } elseif (!isset($this->tables[$row['SCHEMA_TABLE_NAME']])) {
        $this->tables[$row['SCHEMA_TABLE_NAME']]['add'] = $row['PERMISSION_ADD'];
        $this->tables[$row['SCHEMA_TABLE_NAME']]['edit'] = $row['PERMISSION_EDIT'];
        $this->tables[$row['SCHEMA_TABLE_NAME']]['delete'] = $row['PERMISSION_DELETE'];
      }
      
    }
  }
  
  private function loadFieldPermissions() {
    
    $usergroup_condition = $this->db->db_or('OR');
    $usergroup_condition = $usergroup_condition->condition('USERGROUP_ID', $this->session->get('usergroup_id'));
    $usergroup_condition = $usergroup_condition->condition('USERGROUP_ID', NULL);
    
    $role_condition = $this->db->db_or('OR');
    $role_condition = $role_condition->condition('ROLE_ID', $this->session->get('role_id'));
    $role_condition = $role_condition->condition('ROLE_ID', NULL);
    
    $public_condition = $this->db->db_or('OR');
    $public_condition = $public_condition->condition('USERGROUP_ID', NULL);
    $public_condition = $public_condition->condition('ROLE_ID', NULL);
    
    // schema field query
    $result = $this->db->db_select('CORE_PERMISSION_SCHEMA_FIELDS')
      ->fields('CORE_PERMISSION_SCHEMA_FIELDS', array('USERGROUP_ID', 'ROLE_ID', 'PERMISSION'))
      ->join('CORE_SCHEMA_FIELDS', 'CORE_SCHEMA_FIELDS', 'CORE_SCHEMA_FIELDS.SCHEMA_FIELD_ID = CORE_PERMISSION_SCHEMA_FIELDS.SCHEMA_FIELD_ID')
      ->fields('CORE_SCHEMA_FIELDS', array('DB_FIELD_NAME'))
      ->join('CORE_SCHEMA_TABLES', 'CORE_SCHEMA_TABLES', 'CORE_SCHEMA_TABLES.SCHEMA_TABLE_ID = CORE_SCHEMA_FIELDS.SCHEMA_TABLE_ID')
      ->fields('CORE_SCHEMA_FIELDS', array('SCHEMA_TABLE_NAME'))
      ->condition($usergroup_condition)
      ->condition($role_condition)
      ->condition($public_condition)
      ->orderBy('ROLE_ID', 'DESC')
      ->orderBy('USERGROUP_ID', 'DESC');
    $result =  $result->execute();
    while ($row = $result->fetch()) {
      
      if (!isset($this->fields[$row['SCHEMA_TABLE_NAME']][$row['DB_FIELD_NAME']]) && $row['ROLE_ID'] && $row['PERMISSION'] != '') {
        $this->fields[$row['SCHEMA_TABLE_NAME']][$row['DB_FIELD_NAME']] = $row['PERMISSION'];
      } elseif (!isset($this->fields[$row['SCHEMA_TABLE_NAME']][$row['DB_FIELD_NAME']]) && $row['USERGROUP_ID'] && $row['PERMISSION'] != '') {
        $this->fields[$row['SCHEMA_TABLE_NAME']][$row['DB_FIELD_NAME']] = $row['PERMISSION'];
      } elseif (!isset($this->fields[$row['SCHEMA_TABLE_NAME']][$row['DB_FIELD_NAME']]) && $row['PERMISSION'] != '') {
        $this->fields[$row['SCHEMA_TABLE_NAME']][$row['DB_FIELD_NAME']] = $row['PERMISSION'];
      }
      
    }
  }
  
  private function checkSchemaPermission($db_permission, $permission) {
    
    // No permission
    if ($db_permission == 'N')
      return false;
    // Read permission
    if ($db_permission == 'R') {
      if ($permission == self::READ)
        return true;
      else
        return false;
    }
    // Write Permission
    if ($db_permission == 'W') {
      if ($permission == self::READ OR $permission == self::WRITE)
        return true;
      else
        return false;
    }
    // Full Permission
    if ($db_permission == 'F') {
      if ($permission == self::READ OR $permission == self::WRITE OR $permission == self::MASS_CHANGE)
        return true;
      else
        return false;
    }
  }
  
}