<?php

namespace Kula\HEd\Bundle\StudentBundle\Record;

use Kula\Core\Component\Record\Record;

class SISStudentRecord extends Record {
  
  public function getSelectedRecordBarTemplate() {
    return 'KulaHEdStudentBundle::SISRecord/selected_record_student.html.twig';
  }
  
  public function getRecordBarTemplate() {
    return 'KulaHEdStudentBundle::SISRecord/record_student.html.twig';
  }
  
  public function getFromDifferentType($record_type, $record_id) {
    if ($record_type == 'STUDENT_STATUS') {
      $result = $this->db()->db_select('STUD_STUDENT_STATUS', 'STUD_STUDENT_STATUS')
        ->fields('STUD_STUDENT_STATUS', array('STUDENT_ID'))
        ->condition('STUDENT_STATUS_ID', $record_id);
      $result = $result->execute()->fetch();
      return $result['STUDENT_ID'];
    }
  }
  
  public function getRecordIDStack() {
    
    $result = $this->db()->db_select('STUD_STUDENT', 'stu')
    ->distinct()
    ->fields('stu', array('STUDENT_ID' => 'ID'))
    ->join('CONS_CONSTITUENT', 'constituent', 'constituent.CONSTITUENT_ID = stu.STUDENT_ID')
    ->fields('constituent', array('LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'GENDER'))
    ->join('STUD_STUDENT_STATUS', 'studentstatus', 'studentstatus.STUDENT_ID = stu.STUDENT_ID')
    ->join('CORE_ORGANIZATION_TERMS', 'orgterm', 'studentstatus.ORGANIZATION_TERM_ID = orgterm.ORGANIZATION_TERM_ID')
    ->condition('ORGANIZATION_ID', $this->focus->getSchoolIDs());
    
    if ($this->focus->getTermID()) {
      $result = $result->condition('TERM_ID', $this->focus->getTermID());
    }
    $result = $result->orderBy('LAST_NAME')
      ->orderBy('FIRST_NAME')
      ->orderBy('MIDDLE_NAME')
      ->execute()->fetchAll();
    return $result;  
  }
  
  public function get($record_id) {
    
    $result = $this->db()->db_select('STUD_STUDENT', 'stu')
      ->fields('stu', array('STUDENT_ID', 'DIRECTORY_PERMISSION'))
      ->join('CONS_CONSTITUENT', 'constituent', 'constituent.CONSTITUENT_ID = stu.STUDENT_ID')
      ->fields('constituent', array('PERMANENT_NUMBER', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'GENDER', 'RACE', 'MAIDEN_NAME', 'PREFERRED_NAME'));
    if ($this->focus->getTermID()) {
      $result = $result->join('STUD_STUDENT_STATUS', 'stustatus', 'stu.STUDENT_ID = stustatus.STUDENT_ID');
      $result = $result->fields('stustatus', array('STUDENT_STATUS_ID', 'STATUS','GRADE', 'RESIDENT'));
      $result = $result->join('CORE_ORGANIZATION_TERMS', 'orgterm', 'stustatus.ORGANIZATION_TERM_ID = orgterm.ORGANIZATION_TERM_ID');
      $result = $result->fields('orgterm', array('ORGANIZATION_TERM_ID'));
      $result = $result->join('CORE_ORGANIZATION', 'org', 'orgterm.ORGANIZATION_ID = org.ORGANIZATION_ID');
      $result = $result->fields('org', array('ORGANIZATION_NAME'));
      $result = $result->join('CORE_TERM', 'term', 'term.TERM_ID = orgterm.TERM_ID');
      $result = $result->fields('term', array('TERM_ABBREVIATION'));
      $result = $result->condition('orgterm.TERM_ID', $this->focus->getTermID());
    }
    $result = $result->condition('stu.STUDENT_ID', $record_id);
    $result = $result->execute()->fetch();
    
    if ($result) {
      $additional = $this->getAdditional($record_id);
      $result = array_merge($result, $additional);
    }
    
    return $result;
  }
  
  public function getAdditional($record_id) {
    
    $result = array();
    
    $holds_array = array();
    
    $holds_result = $this->db()->db_select('STUD_STUDENT_HOLDS', 'stuholds')
      ->distinct()
      ->fields('stuholds', array())
      ->join('STUD_HOLD', 'hold', 'stuholds.HOLD_ID = hold.HOLD_ID')
      ->fields('hold', array('ALERT_DISPLAY'))
      ->condition('stuholds.STUDENT_ID', $record_id)
      ->condition('stuholds.VOIDED', 'N')
      ->orderBy('HOLD_DATE', 'DESC', 'stuholds')
      ->execute();
    while ($hold_row = $holds_result->fetch()) {
      $holds_array[] = $hold_row['ALERT_DISPLAY'];
    }
    
    $result['holds'] = implode(", ", $holds_array);
    
    return $result;
  }
  
  public function getBaseTable() {
    return 'HEd.Student';
  }
  
  public function getBaseKeyFieldName() {
    return 'HEd.Student.ID';
  }
  
  public function modifySearchDBOBject($db_obj) {
    $db_obj =  $db_obj->join('CONS_CONSTITUENT', null, 'CONS_CONSTITUENT.CONSTITUENT_ID = STUD_STUDENT.STUDENT_ID');
    if ($this->focus->getTermID()) {
      $db_obj =  $db_obj->join('STUD_STUDENT_STATUS', null, 'STUD_STUDENT.STUDENT_ID = STUD_STUDENT_STATUS.STUDENT_ID');
      $db_obj =  $db_obj->join('CORE_ORGANIZATION_TERMS', null, 'STUD_STUDENT_STATUS.ORGANIZATION_TERM_ID = CORE_ORGANIZATION_TERMS.ORGANIZATION_TERM_ID');
      $db_obj = $db_obj->condition('CORE_ORGANIZATION_TERMS.ORGANIZATION_ID', $this->focus->getSchoolIDs());
      $db_obj =  $db_obj->condition('TERM_ID', $this->focus->getTermID());
    }
    $db_obj =  $db_obj->orderBy('LAST_NAME', 'ASC');
    $db_obj =  $db_obj->orderBy('FIRST_NAME', 'ASC');
    return $db_obj;
  }
  
}