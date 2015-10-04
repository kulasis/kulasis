<?php

namespace Kula\HEd\Bundle\StudentBundle\Record;

use Kula\Core\Component\Record\Record;

class TeacherAdvisorStudentRecord extends Record {
  
  public function getSelectedRecordBarTemplate() {
    return 'KulaHEdStudentBundle::StudentRecord/selected_record_student_status.html.twig';
  }
  
  public function getRecordBarTemplate() {
    return 'KulaHEdStudentBundle::TeacherRecord/teacher_record_advisor_student.html.twig';
  }
  
  public function getRecordIDStack() {
    
    $result = $this->db()->db_select('STUD_STUDENT_STATUS', 'stustatus')
    ->fields('stustatus', array('STUDENT_STATUS_ID' => 'ID'))
    ->join('CONS_CONSTITUENT', 'stucon', 'stucon.CONSTITUENT_ID = stustatus.STUDENT_ID')
    ->condition('stustatus.ORGANIZATION_TERM_ID', $this->focus->getOrganizationTermIDs())
    ->condition('stustatus.ADVISOR_ID', $this->focus->getTeacherStaffOrganizationTermID())
    ->orderBy('LAST_NAME', 'ASC')
    ->orderBy('FIRST_NAME', 'ASC');
    $result = $result->execute()->fetchAll();
    
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
    return $result;
    
  }
  
  public function getBaseTable() {
    return 'HEd.Student.Status';
  }
  
  public function getBaseKeyFieldName() {
    return 'HEd.Student.Status.ID';
  }
  
}