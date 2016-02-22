<?php

namespace Kula\HEd\Bundle\StudentBundle\Record;

use Kula\Core\Component\Record\Record;

class StudentStudentRecord extends Record {
  
  public function getBaseTable() {
    return 'HEd.Student';
  }
  
  public function getBaseKeyFieldName() {
    return 'HEd.Student.ID';
  }
  
  public function getSelectedRecordBarTemplate() {
    return 'KulaHEdStudentBundle::StudentRecord/selected_record_student.html.twig';
  }
  
  public function getRecordBarTemplate() {
    return ''; // KulaHEdStudentBundle::StudentRecord/record_student.html.twig
  }
  
  public function getFromDifferentType($record_type, $record_id) {
    if ($record_type == 'Student.HEd.Student.Status' OR $record_type == 'Student.K12.Student.Status') {
      $result = $this->db()->db_select('STUD_STUDENT_STATUS', 'STUD_STUDENT_STATUS')
        ->fields('STUD_STUDENT_STATUS', array('STUDENT_ID'))
        ->condition('STUDENT_STATUS_ID', $record_id);
      $result = $result->execute()->fetch();
      return $result['STUDENT_ID'];
    }
    if ($record_type == 'Student.K12.Student') {
      return $record_id;
    }
  }
  
  public function get($record_id) {
    
    $result = $this->db()->db_select('STUD_STUDENT', 'stu')
      ->fields('stu', array('STUDENT_ID', 'DIRECTORY_PERMISSION'))
      ->join('CONS_CONSTITUENT', 'constituent', 'constituent.CONSTITUENT_ID = stu.STUDENT_ID')
      ->fields('constituent', array('PERMANENT_NUMBER', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'GENDER', 'RACE', 'MAIDEN_NAME', 'PREFERRED_NAME'));
    if ($this->focus->getOrganizationTermID()) {
      $result = $result->join('STUD_STUDENT_STATUS', 'stustatus', 'stu.STUDENT_ID = stustatus.STUDENT_ID');
      $result = $result->fields('stustatus', array('STUDENT_STATUS_ID', 'STATUS','GRADE', 'RESIDENT'));
      $result = $result->join('CORE_ORGANIZATION_TERMS', 'orgterm', 'stustatus.ORGANIZATION_TERM_ID = orgterm.ORGANIZATION_TERM_ID');
      $result = $result->fields('orgterm', array('ORGANIZATION_TERM_ID'));
      $result = $result->join('CORE_ORGANIZATION', 'org', 'orgterm.ORGANIZATION_ID = org.ORGANIZATION_ID');
      $result = $result->fields('org', array('ORGANIZATION_NAME'));
      $result = $result->join('CORE_TERM', 'term', 'term.TERM_ID = orgterm.TERM_ID');
      $result = $result->fields('term', array('TERM_ABBREVIATION'));
      $result = $result->condition('orgterm.ORGANIZATION_TERM_ID', $this->focus->getOrganizationTermIDs());
     // $result = $result->condition('orgterm.TERM_ID', $this->focus->getTermID());
    }
    $result = $result->condition('stu.STUDENT_ID', $record_id);
    $result = $result->execute()->fetch();
    
    
    return $result;
  }
  

  
}