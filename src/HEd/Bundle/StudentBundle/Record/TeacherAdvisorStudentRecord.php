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
      ->fields('constituent', array('PERMANENT_NUMBER', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'IDENTIFIED_GENDER', 'GENDER', 'RACE'))
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
  
  public function getAdvisingStudentsMenu() {

    $students = array();
    
    $staff_organization_term_id = $this->session->getFocus('Teacher.Staff.OrgTerm');

    if ($staff_organization_term_id) {
      
      $staff_id = $this->db()->db_select('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgterm')
        ->fields('stafforgterm', array('STAFF_ID', 'ORGANIZATION_TERM_ID'))
        ->condition('stafforgterm.STAFF_ORGANIZATION_TERM_ID', $staff_organization_term_id)
        ->execute()->fetch();
      
      $organization_term_id = $staff_id['ORGANIZATION_TERM_ID'];
      $staff_id = $staff_id['STAFF_ID'];
      
      $staff_organization_term_ids = $this->db()->db_select('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgterm')
        ->fields('stafforgterm', array('STAFF_ORGANIZATION_TERM_ID'))
        ->condition('stafforgterm.STAFF_ID', $staff_id)
        ->execute()->fetchAll();
            
    $students_result = $this->db()->db_select('STUD_STUDENT', 'stu')
      ->join('STUD_STUDENT_STATUS', 'stustatus', 'stustatus.STUDENT_ID = stu.STUDENT_ID')
      ->fields('stustatus', array('STUDENT_STATUS_ID', 'LEVEL', 'STATUS'))
      ->join('CONS_CONSTITUENT', 'cons', 'cons.CONSTITUENT_ID = stu.STUDENT_ID')
      ->fields('cons', array('LAST_NAME', 'FIRST_NAME', 'PERMANENT_NUMBER', 'GENDER'))
      ->join('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgterm', 'stafforgterm.STAFF_ORGANIZATION_TERM_ID = stustatus.ADVISOR_ID')
      ->join('STUD_STAFF', 'staff', 'staff.STAFF_ID = stafforgterm.STAFF_ID')
      ->condition('stustatus.ORGANIZATION_TERM_ID', $organization_term_id)
      ->condition('stustatus.ADVISOR_ID', $staff_organization_term_ids)
      ->orderBy('LAST_NAME')
      ->orderBy('FIRST_NAME')
      ->execute();
    while ($students_row = $students_result->fetch()) {
      $students[$students_row['STUDENT_STATUS_ID']] = 
      ($students_row['STATUS'] != '') ?
        '( '.$students_row['LAST_NAME'].', '.$students_row['FIRST_NAME'].' | '.$students_row['GENDER'].' | '.$students_row['LEVEL'] .' | '.$students_row['PERMANENT_NUMBER'].' )'
      : 
        $students_row['LAST_NAME'].', '.$students_row['FIRST_NAME'].' | '.$students_row['GENDER'].' | '.$students_row['LEVEL'] .' | '.$students_row['PERMANENT_NUMBER'].' ';
      
    }
    }
    return $students;
  }
  
}