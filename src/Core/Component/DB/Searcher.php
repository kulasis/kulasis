<?php

namespace Kula\Core\Component\DB;

use Kula\Core\Component\Permission\Permission;

class Searcher {
  
  private static $post;
  
  private static $result = array();
  
  private static $db;
  private static $permission;
  private static $request;
  private static $schema;
  
  public static function startProcessing($db, $schema, $permission, $request) {
    
    self::$db = $db;
    self::$schema = $schema;
    self::$permission = $permission;
    self::$request = $request;
    
    self::$post = self::$request->request->get('search');

    self::$post = self::cleanSearchVariable(self::$post);
    
    return self::$post;
  }
  
  public static function cleanSearchVariable($search) {
    foreach($search as $table => $table_row) {
      // remove key with new_num (template row)
      unset($search[$table]['new_num']);
      // take any hidden elements and bring up
      if (isset($search[$table]['hidden'])) {
        foreach($search[$table]['hidden'] as $key => $value) {
          $search[$table][$key] = $value;
        }
        unset($search[$table]['hidden']);
      }
      
      // Remove fields with no value
      if ($search[$table]) {
        foreach($search[$table] as $field => $value) {
          
          if (isset($search[$table][$field]['value'])) {
            $search[$table][$field] = $search[$table][$field]['value'];            
          }
          if (isset($search[$table][$field]['chooser'])) {
            unset($search[$table][$field]['chooser']);
          }
          
          if ($search[$table][$field] == '') {
            unset($search[$table][$field]);
          }
        }
      }
      
      // Remove any tables with nothing underneath
      if (count($search[$table]) == 0) {
        unset($search[$table]);
      }
      
    }
    return $search;
  }
  
  public static function prepareSearch($post_data, $base_table, $base_field) {
    
    $post_data = self::cleanSearchVariable($post_data);
    
    $select_obj = self::$db->db_select($base_table);
    $select_obj->addField($base_table, $base_field);
    // Get fields from base_table in array
    if (isset($post_data[$base_table])) {
      $select_obj = $select_obj->fields($base_table, array_keys($post_data[$base_table]));
    // Create predicates
    foreach($post_data[$base_table] as $key => $value) {
      // check for permission
      if (self::$permission->getPermissionForSchemaObject($base_table, $key, Permission::READ)) {
        if (is_array($value)) {  
          $select_obj = $select_obj->condition(self::$schema->getField($key)->getDBName(), $value, 'IN', $base_table);  
        } elseif (is_int($value)) { 
          $select_obj = $select_obj->condition(self::$schema->getField($key)->getDBName(), $value, '=', $base_table);  
        } else {
          $select_obj = $select_obj->condition(self::$schema->getField($key)->getDBName(), $value.'%', 'LIKE', $base_table);  
        }
      } else {
        $container = $GLOBALS['kernel']->getContainer();
        $container->get('session')->getFlashBag()->add('error', 'Searching in ' . $base_table . '.' . $key . ' with no permission.');
      }
    }
    unset($post_data[$base_table]);
    }
    
    // Get any other tables
    if (count($post_data) > 0) {
      $i = 0;
      // loop through each table
      foreach($post_data as $table => $table_data) {
        
        foreach($table_data as $field => $value) {
          // check for permission
          if (self::$permission->getPermissionForSchemaObject($table, $field, Permission::READ)) {
            if (is_array($value)) {  
              $select_obj = $select_obj->condition(self::$schema->getTable($table)->getDBName().'.'.$field, $value);
            } elseif (is_int($value)) { 
              $select_obj = $select_obj->condition(self::$schema->getTable($table)->getDBName().'.'.$field, $value);
            } else {
              $select_obj = $select_obj->condition(self::$schema->getTable($table)->getDBName().'.'.$field, $value . '%', 'LIKE');
            }
          } else {
            $container = $GLOBALS['kernel']->getContainer();
            $container->get('session')->getFlashBag()->add('error', 'Searching in ' . $table . '.' . $field . ' with no permission.');
          }
        }
        
        // get link to base table
        $i++;
      }
      
    }

    return $select_obj;
    
  }

  
}