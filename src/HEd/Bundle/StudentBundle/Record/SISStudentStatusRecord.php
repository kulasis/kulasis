<?php

namespace Kula\HEd\Bundle\StudentBundle\Record;

use Kula\Core\Component\Record\Record;

class SISStudentStatusRecord extends Record {
  
  public function getSelectedRecordBarTemplate() {
    return 'KulaHEdStudentBundle::Record/selected_record_student_status.html.twig';
  }
  
  public function getRecordBarTemplate() {
    return 'KulaHEdStudentBundle::Record/record_student_status.html.twig';
  }
  
  public function getFromDifferentType($record_type, $record_id) {
    if ($record_type == 'STUDENT') {
      $result = $this->db()->db_select('STUD_STUDENT_STATUS', 'stustatus')
        ->fields('stustatus', array('STUDENT_STATUS_ID'))
        ->join('CORE_ORGANIZATION_TERMS', 'orgterm', 'STUD_STUDENT_STATUS.ORGANIZATION_TERM_ID = orgterm.ORGANIZATION_TERM_ID')
        ->condition('STUDENT_ID', $record_id)
        ->condition('ORGANIZATION_ID', $this->focus->getSchoolIDs());
      if ($this->focus->getTermID())
        $result = $result->condition('TERM_ID', $this->focus->getTermID());
      $result = $result->execute()->fetch();
      return $result['STUDENT_STATUS_ID'];
    }
  }
  
  public function getRecordIDStack() {
    
    $result = $this->db()->db_select('STUD_STUDENT_STATUS', 'stustatus')
    ->fields('stustatus', array('STUDENT_STATUS_ID' => 'ID'))
    ->join('CORE_ORGANIZATION_TERMS', 'orgterm', 'STUD_STUDENT_STATUS.ORGANIZATION_TERM_ID = orgterm.ORGANIZATION_TERM_ID')
    ->join('CONS_CONSTITUENT', 'constituent', 'constituent.CONSTITUENT_ID = STUD_STUDENT_STATUS.STUDENT_ID')
    ->fields('constituent', array('LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'GENDER'))
    ->join('CORE_TERM', 'term', 'orgterm.TERM_ID = term.TERM_ID')
    ->condition('ORGANIZATION_ID', $this->focus->getSchoolIDs());
    
    if ($this->focus->getTermID())
      $result = $result->condition('orgterm.TERM_ID', $this->focus->getTermID());
    
    $result = $result->orderBy('LAST_NAME')
      ->orderBy('FIRST_NAME')
      ->orderBy('MIDDLE_NAME')
      ->orderBy('START_DATE', 'ASC', 'term')
      ->execute()->fetchAll();
    return $result;  
  }
  
  public function get($record_id) {
    
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
      ->execute()->fetch();
    
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
      ->fields('stuholds', array('ALERT_DISPLAY'))
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
  
  public function getBaseTable() {
    return 'HEd.Student.Status';
  }
  
  public function getBaseKeyFieldName() {
    return 'HEd.Student.Status.ID';
  }
  
  public function modifySearchDBOBject($db_obj) {
    $db_obj =  $db_obj->join('STUD_STUDENT', null, 'STUD_STUDENT.STUDENT_ID = STUD_STUDENT_STATUS.STUDENT_ID');
    $db_obj =  $db_obj->join('CONS_CONSTITUENT', null, 'CONS_CONSTITUENT.CONSTITUENT_ID = STUD_STUDENT_STATUS.STUDENT_ID');
    $db_obj = $db_obj->condition('STUD_STUDENT_STATUS.ORGANIZATION_TERM_ID', $this->focus->getOrganizationTermIDs());
    $db_obj =  $db_obj->orderBy('LAST_NAME', 'ASC');
    $db_obj =  $db_obj->orderBy('FIRST_NAME', 'ASC');
    return $db_obj;
  }
  
}