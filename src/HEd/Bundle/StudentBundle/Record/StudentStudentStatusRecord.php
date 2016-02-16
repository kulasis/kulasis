<?php

namespace Kula\HEd\Bundle\StudentBundle\Record;

use Kula\Core\Component\Record\Record;

class StudentStudentStatusRecord extends Record {
  
  public function getBaseTable() {
    return 'HEd.Student.Status';
  }
  
  public function getBaseKeyFieldName() {
    return 'HEd.Student.Status.ID';
  }
  
  public function getSelectedRecordBarTemplate() {
    return 'KulaHEdStudentBundle::StudentRecord/selected_record_student_status.html.twig';
  }
  
  public function getRecordBarTemplate() {
    return 'KulaHEdStudentBundle::StudentRecord/record_student_status.html.twig';
  }
  
  public function getFromDifferentType($record_type, $record_id) {
    
    // Change to student status id
    if ($record_type == 'Student.Student') {
      // Get Student Status ID
      $statusStudentID = $this->db()->db_select('STUD_STUDENT_STATUS', 'stustatus')
        ->fields('stustatus', array('STUDENT_STATUS_ID'))
        ->join('CORE_ORGANIZATION_TERMS', 'orgterm', 'stustatus.ORGANIZATION_TERM_ID = orgterm.ORGANIZATION_TERM_ID')
        ->condition('stustatus.STUDENT_ID', $studentID['STUDENT_ID'])
        ->condition('orgterm.ORGANIZATION_ID', $this->focus->getSchoolIDs());
        if ($this->focus->getTermID())
          $statusStudentID = $statusStudentID->condition('orgterm.TERM_ID', $this->focus->getTermID());
      $statusStudentID = $statusStudentID->execute()->fetch();
      
      return $statusStudentID['STUDENT_STATUS_ID'];
    }
    
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
      ->condition('orgterms.ORGANIZATION_ID', $this->focus->getSchoolIDs());
      if ($this->focus->getTermID())
        $result = $result->condition('orgterms.TERM_ID', $this->focus->getTermID());
      $result = $result->execute()->fetch();
    return $result;
  }
  
  public function getAllSchools($studentID) {

    $statuses = array();

    $statuses_result = $this->db()->db_select('STUD_STUDENT_STATUS', 'status')
      ->distinct()
      ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = status.ORGANIZATION_TERM_ID')
      ->join('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
      ->fields('org', array('ORGANIZATION_ID', 'ORGANIZATION_NAME'))
      ->condition('status.STUDENT_ID', $studentID)
      ->orderBy('ORGANIZATION_NAME')
      ->execute();
    while ($statuses_row = $statuses_result->fetch()) {
      $statuses[$statuses_row['ORGANIZATION_ID']] = $statuses_row['ORGANIZATION_NAME'];
    }

    return $statuses;
  }
  
  public function getAllTermsForSchool($studentID) {

    $statuses = array();

    $statuses_result = $this->db()->db_select('STUD_STUDENT_STATUS', 'status')
      ->fields('status', array('STUDENT_STATUS_ID'))
      ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = status.ORGANIZATION_TERM_ID')
      ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
      ->fields('term', array('TERM_ID', 'TERM_ABBREVIATION'))
      ->condition('orgterms.ORGANIZATION_ID', $this->session->getFocus('organization_id'))
      ->condition('status.STUDENT_ID', $studentID)
      ->orderBy('START_DATE', 'ASC', 'term')
      ->execute();
    while ($statuses_row = $statuses_result->fetch()) {
      $statuses[$statuses_row['TERM_ID']] = $statuses_row['TERM_ABBREVIATION'];
    }
    
    return $statuses;
  }
  
}