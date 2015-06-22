<?php

namespace Kula\Core\Bundle\ConstituentBundle\Service;

class CombineService {
  
  public function __construct($db) {
    $this->db = $db;
  }
  
  public function combine($table_name, $delete_id, $keep_id, $conversion_field = null) {
    $transaction = $this->db->db_transaction();
    
    // Get CONS_CONSTITUENT.CONSTITUENT_ID FIELD ID
    $field = $this->db->db_select('CORE_SCHEMA_FIELDS', 'fields')
      ->fields('fields', array('SCHEMA_FIELD_ID', 'DB_COLUMN_NAME'))
      ->join('CORE_SCHEMA_TABLES', 'tables', 'tables.SCHEMA_TABLE_ID = fields.SCHEMA_TABLE_ID')
      ->fields('tables', array('DB_TABLE_NAME'))
      ->condition('tables.DB_TABLE_NAME', $table_name)
      ->condition('fields.DB_COLUMN_PRIMARY', 1)
      ->execute()->fetch();
    
    $field_id = $field['SCHEMA_FIELD_ID'];
    $column_name = $field['DB_COLUMN_NAME'];
    $table_name = $field['DB_TABLE_NAME'];
    $old_data = array();
    // Reassign records in one-to-many tables
    // Delete records in one-to-one tables if record exists
    
    // get deleted CONV_NUMBER
    if ($conversion_field) {
      $deleted_conv_number = $this->db->db_select($table_name)
        ->fields($table_name, array($conversion_field))
        ->condition($column_name, $delete_id)
        ->execute()->fetch()[$conversion_field];
    } else {
      $deleted_conv_number = null;
    }
    
    // Get all one-to-one tables
    $direct_onetoone_result = $this->db->db_select('CORE_SCHEMA_FIELDS', 'fields')
      ->fields('fields', array('SCHEMA_FIELD_ID', 'SCHEMA_TABLE_ID', 'DB_COLUMN_NAME'))
      ->join('CORE_SCHEMA_TABLES', 'tables', 'tables.SCHEMA_TABLE_ID = fields.SCHEMA_TABLE_ID')
      ->fields('tables', array('DB_TABLE_NAME'))
      ->condition('PARENT_SCHEMA_FIELD_ID', $field_id)
      ->condition('DB_COLUMN_PRIMARY', 1)
      ->execute();
    while ($direct_onetoone_row = $direct_onetoone_result->fetch()) {
      // Delete from one-to-one table
      // Check if exists in one-to-one table
      $check_if_exists_direct_onetoone = $this->db->db_select($direct_onetoone_row['DB_TABLE_NAME'])
        ->fields($direct_onetoone_row['DB_TABLE_NAME'], array($direct_onetoone_row['DB_COLUMN_NAME']))
        ->condition($direct_onetoone_row['DB_COLUMN_NAME'], $delete_id)
        ->execute()->fetch()[$direct_onetoone_row['DB_COLUMN_NAME']];
      if ($check_if_exists_direct_onetoone != '') {
        // Get delete data
        $old_data[$direct_onetoone_row['DB_TABLE_NAME']] = $this->db->db_select($direct_onetoone_row['DB_TABLE_NAME'])
          ->fields($direct_onetoone_row['DB_TABLE_NAME'])
          ->condition($direct_onetoone_row['DB_COLUMN_NAME'], $delete_id)
          ->execute()->fetch();
        
        // Get children of those tables
        $children_of_onetoone_result = $this->db->db_select('CORE_SCHEMA_FIELDS', 'fields')
        ->fields('fields', array('SCHEMA_FIELD_ID', 'SCHEMA_TABLE_ID', 'DB_COLUMN_NAME'))
        ->join('CORE_SCHEMA_TABLES', 'tables', 'tables.SCHEMA_TABLE_ID = fields.SCHEMA_TABLE_ID')
        ->fields('tables', array('DB_TABLE_NAME'))
        ->condition('PARENT_SCHEMA_FIELD_ID', $direct_onetoone_row['SCHEMA_FIELD_ID'])
        ->execute();
        while ($children_of_onetoone_row = $children_of_onetoone_result->fetch()) {
          //echo $children_of_onetoone_row['DB_TABLE_NAME'].'<br />';
          // Reassign ID#
          $this->db->db_update($children_of_onetoone_row['DB_TABLE_NAME'])
            ->fields(array($children_of_onetoone_row['DB_COLUMN_NAME'] => $keep_id))
            ->condition($children_of_onetoone_row['DB_COLUMN_NAME'], $delete_id)
            ->execute();
        }
       // echo $direct_onetoone_row['DB_TABLE_NAME'].' '.$direct_onetoone_row['DB_COLUMN_NAME'].'<br />';
        // Delete from one-to-one table
        $this->db->db_delete($direct_onetoone_row['DB_TABLE_NAME'])
          ->condition($direct_onetoone_row['DB_COLUMN_NAME'], $delete_id)
          ->execute();
      }
    
    } // End one-to-one tables
      
    // Get direct children of one-to-one
    $children_of_cons_onetoone_result = $this->db->db_select('CORE_SCHEMA_FIELDS', 'fields')
    ->fields('fields', array('SCHEMA_FIELD_ID', 'SCHEMA_TABLE_ID', 'DB_COLUMN_NAME'))
    ->join('CORE_SCHEMA_TABLES', 'tables', 'tables.SCHEMA_TABLE_ID = fields.SCHEMA_TABLE_ID')
    ->fields('tables', array('DB_TABLE_NAME'))
    ->condition('PARENT_SCHEMA_FIELD_ID', $field_id)
    ->condition('DB_COLUMN_PRIMARY', 0)
    ->execute();
    while ($children_of_cons_onetoone_row = $children_of_cons_onetoone_result->fetch()) {
      // Reassign ID#
      $update = $this->db->db_update($children_of_cons_onetoone_row['DB_TABLE_NAME'])
        ->fields(array($children_of_cons_onetoone_row['DB_COLUMN_NAME'] => $keep_id))
        ->condition($children_of_cons_onetoone_row['DB_COLUMN_NAME'], $delete_id)
        ->execute();
    }
    
    // Insert into conversion table
    $this->db->db_insert('LOG_COMBINED')->fields(array(
      'DELETED_TABLE' => $table_name,
      'DELETED_ID' => $delete_id,
      'DELETED_CONV_NUMBER' => $deleted_conv_number,
      'MERGED_ID' => $keep_id,
      'DELETED_DATA' => print_r($old_data, true)
    ))->execute();
    
    // Delete from CONS_CONSTITUENT
    $affectedRows = $this->db->db_delete($table_name)
      ->condition($column_name, $delete_id)
      ->execute();
    
    if ($affectedRows) {
      $transaction->commit();
      return true;
    } else {
      $transaction->rollback();
    }
  }
  
}