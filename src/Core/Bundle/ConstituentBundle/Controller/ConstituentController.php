<?php

namespace Kula\Core\Bundle\ConstituentBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class ConstituentController extends Controller {
  
  public function indexAction() {
    $this->authorize();
    $this->setRecordType('Core.Constituent');
    
    return $this->render('KulaCoreConstituentBundle:Constituent:index.html.twig');
  }
  
  public function combineAndDeleteAction() {
    $this->authorize();
    $this->setRecordType('Core.Constituent');
    
    //$this->setSubmitMode($this->tpl, 'search');
    
    $constituents = array();
    $combine = $this->request->request->get('combine');
    
    if (isset($combine['CONS_CONSTITUENT']['CONSTITUENT_ID'])) {
      
      $this->db()->beginTransaction();
      
      // Student to keep
      $keep_student = $combine['CONS_CONSTITUENT']['CONSTITUENT_ID'];
      // Student to delete
      $delete_student = $this->record->getSelectedRecordID();
      
      // Reassign records in one-to-many tables
      // Delete records in one-to-one tables if record exists
      
      // get deleted CONV_NUMBER
      $deleted_conv_number = $this->db()->db_select('STUD_STUDENT')
        ->fields('STUD_STUDENT', array('CONV_STUDENT_NUMBER'))
        ->condition('STUDENT_ID', $delete_student)
        ->execute()->fetch()['CONV_STUDENT_NUMBER'];
      
      // Get CONS_CONSTITUENT.CONSTITUENT_ID FIELD ID
      $field_constituent_id = $this->db()->db_select('CORE_SCHEMA_FIELDS', 'fields')
        ->fields('fields', array('SCHEMA_FIELD_ID'))
        ->join('CORE_SCHEMA_TABLES', 'tables', 'tables.SCHEMA_TABLE_ID = fields.SCHEMA_TABLE_ID')
        ->condition('tables.SCHEMA_TABLE_NAME', 'CONS_CONSTITUENT')
        ->condition('fields.DB_FIELD_NAME', 'CONSTITUENT_ID')
        ->execute()->fetch()['SCHEMA_FIELD_ID'];
      
      // Get all one-to-one tables
      $direct_onetoone_result = $this->db()->db_select('CORE_SCHEMA_FIELDS', 'fields')
        ->fields('fields', array('SCHEMA_FIELD_ID', 'SCHEMA_TABLE_ID', 'DB_FIELD_NAME'))
        ->join('CORE_SCHEMA_TABLES', 'tables', 'tables.SCHEMA_TABLE_ID = fields.SCHEMA_TABLE_ID')
        ->fields('tables', array('SCHEMA_TABLE_NAME'))
        ->condition('PARENT_SCHEMA_FIELD_ID', $field_constituent_id)
        ->condition('DB_FIELD_PRIMARY', 1)
        ->execute();
      while ($direct_onetoone_row = $direct_onetoone_result->fetch()) {
        
        
        // Delete from one-to-one table
        // Check if exists in one-to-one table
        $check_if_exists_direct_onetoone = $this->db()->db_select($direct_onetoone_row['SCHEMA_TABLE_NAME'])
          ->fields($direct_onetoone_row['SCHEMA_TABLE_NAME'], array($direct_onetoone_row['DB_FIELD_NAME']))
          ->condition($direct_onetoone_row['DB_FIELD_NAME'], $keep_student)
          ->execute()->fetch()[$direct_onetoone_row['DB_FIELD_NAME']];
        if ($check_if_exists_direct_onetoone == '') {
          
          // Get delete data
          $old_data = $this->db()->db_select($direct_onetoone_row['SCHEMA_TABLE_NAME'])
            ->fields($direct_onetoone_row['SCHEMA_TABLE_NAME'])
            ->condition($direct_onetoone_row['DB_FIELD_NAME'], $delete_student)
            ->execute()->fetch();
          
          if ($old_data[$direct_onetoone_row['DB_FIELD_NAME']] != '') {
            $old_data[$direct_onetoone_row['DB_FIELD_NAME']] = $keep_student;
            // Insert into table
            $this->db()->db_insert($direct_onetoone_row['SCHEMA_TABLE_NAME'])
              ->fields($old_data)
                ->execute();
          }
        }
        
        // Get children of those tables
        $children_of_onetoone_result = $this->db()->db_select('CORE_SCHEMA_FIELDS', 'fields')
        ->fields('fields', array('SCHEMA_FIELD_ID', 'SCHEMA_TABLE_ID', 'DB_FIELD_NAME'))
        ->join('CORE_SCHEMA_TABLES', 'tables', 'tables.SCHEMA_TABLE_ID = fields.SCHEMA_TABLE_ID')
        ->fields('tables', array('SCHEMA_TABLE_NAME'))
        ->condition('PARENT_SCHEMA_FIELD_ID', $direct_onetoone_row['SCHEMA_FIELD_ID'])
        ->execute();
        while ($children_of_onetoone_row = $children_of_onetoone_result->fetch()) {
          // Reassign ID#
          $this->db()->db_update($children_of_onetoone_row['SCHEMA_TABLE_NAME'])
            ->fields(array($children_of_onetoone_row['DB_FIELD_NAME'] => $keep_student))
            ->condition($children_of_onetoone_row['DB_FIELD_NAME'], $delete_student)
            ->execute();
        }
        // Delete from one-to-one table
        // Check if exists in one-to-one table
        $check_if_exists_direct_onetoone = $this->db()->db_select($direct_onetoone_row['SCHEMA_TABLE_NAME'])
          ->fields($direct_onetoone_row['SCHEMA_TABLE_NAME'], array($direct_onetoone_row['DB_FIELD_NAME']))
          ->condition($direct_onetoone_row['DB_FIELD_NAME'], $keep_student)
          ->execute()->fetch()[$direct_onetoone_row['DB_FIELD_NAME']];
        if ($check_if_exists_direct_onetoone) {
          $this->db()->db_delete($direct_onetoone_row['SCHEMA_TABLE_NAME'])
            ->condition($direct_onetoone_row['DB_FIELD_NAME'], $delete_student)
            ->execute();
        }
      
      }
      
      $query_conditions = $this->db()->db_or();
      $query_conditions = $query_conditions->condition('DB_FIELD_PRIMARY', 0);
      $query_conditions = $query_conditions->condition('DB_FIELD_PRIMARY', null);
        
      // Get direct children of one-to-one
      $children_of_cons_onetoone_result = $this->db()->db_select('CORE_SCHEMA_FIELDS', 'fields')
      ->fields('fields', array('SCHEMA_FIELD_ID', 'SCHEMA_TABLE_ID', 'DB_FIELD_NAME'))
      ->join('CORE_SCHEMA_TABLES', 'tables', 'tables.SCHEMA_TABLE_ID = fields.SCHEMA_TABLE_ID')
      ->fields('tables', array('SCHEMA_TABLE_NAME'))
      ->condition('PARENT_SCHEMA_FIELD_ID', $field_constituent_id)
      ->condition($query_conditions)
      ->execute();
      while ($children_of_cons_onetoone_row = $children_of_cons_onetoone_result->fetch()) {
        // Reassign ID#
        $update = $this->db()->db_update($children_of_cons_onetoone_row['SCHEMA_TABLE_NAME'])
          ->fields(array($children_of_cons_onetoone_row['DB_FIELD_NAME'] => $keep_student))
          ->condition($children_of_cons_onetoone_row['DB_FIELD_NAME'], $delete_student)
          ->execute();
      }
      
      // Insert into conversion table
      $this->db()->db_insert('CONV_COMBINED')->fields(array(
        'DELETED_CONSTITUENT_ID' => $delete_student,
        'DELETED_CONV_NUMBER' => $deleted_conv_number,
        'MERGED_CONSTITUENT_ID' => $keep_student,
      ))->execute();
      
      // Delete from CONS_CONSTITUENT
      $this->db()->db_delete('CONS_CONSTITUENT')
        ->condition('CONSTITUENT_ID', $delete_student)
        ->execute();
      $this->flash->add('success', 'Deleted constituent. '.$delete_student.' -> '.$keep_student);
      
      $this->db()->commit();
      return $this->forward('sis_constituent_constituent', array('record_type' => 'Core.Constituent', 'record_id' => ''), array('record_type' => 'Core.Constituent', 'record_id' => ''));
    }
    
    if ($this->request->request->get('search')) {
      $query = $this->searcher->prepareSearch($this->request->request->get('search'), 'CONS_CONSTITUENT', 'CONSTITUENT_ID');
      $query = $query->fields('CONS_CONSTITUENT', array('CONSTITUENT_ID', 'PERMANENT_NUMBER', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'BIRTH_DATE', 'GENDER', 'SOCIAL_SECURITY_NUMBER'));
      $query = $query->leftJoin('STUD_STUDENT', 'stu', 'stu.STUDENT_ID = CONS_CONSTITUENT.CONSTITUENT_ID');
      $query = $query->leftJoin('STUD_STUDENT_STATUS', 'status', 'stu.STUDENT_ID = status.STUDENT_ID AND status.ORGANIZATION_TERM_ID IN (' . implode(', ', $this->focus->getOrganizationTermIDs()) . ')');
      $query = $query->fields('status', array('ORGANIZATION_TERM_ID', 'STUDENT_STATUS_ID'));
      $query = $query->orderBy('LAST_NAME', 'ASC');
      $query = $query->orderBy('FIRST_NAME', 'ASC');
      $query = $query->range(0, 100);
      $constituents = $query->execute()->fetchAll();
    }
    
    return $this->render('KulaCoreConstituentBundle:Constituent:combineAndDelete.html.twig', array('constituents' => $constituents));
  }
  
}
