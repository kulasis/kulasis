<?php

namespace Kula\HEd\Bundle\StudentBundle\Record;

use Kula\Core\Component\Record\Record;

class StudentStudentStatusRecord extends Record {
  
  public function getSelectedRecordBarTemplate() {
    return 'KulaHEdStudentBundle::StudentRecord/selected_record_student_status.html.twig';
  }
  
  public function getRecordBarTemplate() {
    //return 'KulaHEdStudentBundle::SISRecord/record_student_status.html.twig';
  }
  
  public function getFromDifferentType($record_type, $record_id) {
  }
  
  public function get($record_id) {
    if ($result = $this->getInfo($record_id))
      return $result;
    else {
      // Get Student ID
      $studentID = $this->db()->db_select('STUD_STUDENT_STATUS', 'stustatus')
        ->fields('stustatus', array('STUDENT_ID'))
        ->condition('stustatus.STUDENT_STATUS_ID', $record_id)
        ->execute()->fetch();
      
      // Get Student Status ID
      $statusStudentID = $this->db()->db_select('STUD_STUDENT_STATUS', 'stustatus')
        ->fields('stustatus', array('STUDENT_STATUS_ID'))
        ->join('CORE_ORGANIZATION_TERMS', 'orgterm', 'stustatus.ORGANIZATION_TERM_ID = orgterm.ORGANIZATION_TERM_ID')
        ->condition('stustatus.STUDENT_ID', $studentID['STUDENT_ID'])
        ->condition('orgterm.ORGANIZATION_ID', $this->focus->getSchoolIDs());
        if ($this->focus->getTermID())
          $statusStudentID = $statusStudentID->condition('orgterm.TERM_ID', $this->focus->getTermID());
      $statusStudentID = $statusStudentID->execute()->fetch();
    
      return $this->getInfo($statusStudentID['STUDENT_STATUS_ID']);
    }
  }
  
  public function getAdditional($record_id) {
    
    $result = array();
    
    $holds_array = array();
    
    $holds_result = $this->db()->db_select('STUD_STUDENT_HOLDS', 'stuholds')
      ->distinct()
      ->fields('stuholds', array())
      ->join('STUD_HOLD', 'hold', 'stuholds.HOLD_ID = hold.HOLD_ID')
      ->fields('hold', array('ALERT_DISPLAY'))
      ->join('STUD_STUDENT_STATUS', 'status', 'status.STUDENT_ID = stuholds.STUDENT_ID')
      ->fields('status', array('STUDENT_ID'))
      ->condition('status.STUDENT_STATUS_ID', $record_id)
      ->condition('stuholds.VOIDED', 0)
      ->orderBy('HOLD_DATE', 'DESC', 'stuholds')
      ->execute();
    while ($hold_row = $holds_result->fetch()) {
      $holds_array[] = $hold_row['ALERT_DISPLAY'];
    }
    $result['holds'] = implode(", ", $holds_array);
    
    return $result;
  }
  
  public function getInfo($record_id) {
    $result = $this->db()->db_select('STUD_STUDENT_STATUS', 'STUD_STUDENT_STATUS')
      ->fields('STUD_STUDENT_STATUS', array('STUDENT_STATUS_ID', 'STUDENT_ID', 'STATUS', 'GRADE', 'RESIDENT', 'ORGANIZATION_TERM_ID'))
      ->join('CONS_CONSTITUENT', 'constituent', 'constituent.CONSTITUENT_ID = STUD_STUDENT_STATUS.STUDENT_ID')
      ->fields('constituent', array('PERMANENT_NUMBER', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'GENDER', 'RACE'))
      ->join('STUD_STUDENT', 'stu', 'stu.STUDENT_ID = STUD_STUDENT_STATUS.STUDENT_ID')
      ->fields('stu', array('STUDENT_ID', 'DIRECTORY_PERMISSION'))
      ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = STUD_STUDENT_STATUS.ORGANIZATION_TERM_ID')
      ->join('CORE_ORGANIZATION', 'org', 'orgterms.ORGANIZATION_ID = org.ORGANIZATION_ID')
      ->fields('org', array('ORGANIZATION_NAME'))
      ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
      ->fields('term', array('TERM_ABBREVIATION'))
      ->condition('STUDENT_STATUS_ID', $record_id)
      ->condition('orgterms.ORGANIZATION_ID', $this->focus->getSchoolIDs());
      if ($this->focus->getTermID())
        $result = $result->condition('orgterms.TERM_ID', $this->focus->getTermID());
      $result = $result->execute()->fetch();
    
    if ($result) {
      $additional = $this->getAdditional($record_id);
      $result = array_merge($result, $additional);
    }
    
    return $result;
  }
  
  public function getBaseTable() {
    return 'HEd.Student.Status';
  }
  
  public function getBaseKeyFieldName() {
    return 'HEd.Student.Status.ID';
  }
  
}