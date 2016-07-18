<?php

namespace Kula\HEd\Bundle\StudentBundle\Chooser;

use Kula\Core\Component\Chooser\Chooser;

class StudentChooser extends Chooser {
  
  public function search($q) {

    $query_conditions = $this->db()->db_or();
    $query_conditions = $query_conditions->condition('LAST_NAME', $q.'%', 'LIKE');
    $query_conditions = $query_conditions->condition('FIRST_NAME', $q.'%', 'LIKE');
    $query_conditions = $query_conditions->condition('MIDDLE_NAME', $q.'%', 'LIKE');
    $query_conditions = $query_conditions->condition('PERMANENT_NUMBER', $q.'%', 'LIKE');
    
    $data = array();
    
    $search = $this->db()->db_select('STUD_STUDENT', 'student')
      ->fields('student')
      ->join('CONS_CONSTITUENT', 'cons', 'cons.CONSTITUENT_ID = student.STUDENT_ID')
      ->fields('cons', array('PERMANENT_NUMBER', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'GENDER'))
      ->join('STUD_STUDENT_STATUS', 'stustatus', 'stustatus.STUDENT_ID = student.STUDENT_ID')
      ->fields('stustatus', array('STUDENT_STATUS_ID', 'LEVEL'))
      ->condition($query_conditions)
      ->orderBy('LAST_NAME', 'ASC')
      ->orderBy('FIRST_NAME', 'ASC');
    
    if ($this->focus()->getOrganizationTermIDs())
      $search = $search->condition('ORGANIZATION_TERM_ID', $this->focus()->getOrganizationTermIDs());

    $search = $search->execute();
    while ($row = $search->fetch()) {
      $this->addToChooserMenu($row['STUDENT_STATUS_ID'], $row['LAST_NAME'].', '.$row['FIRST_NAME'].' '.$row['MIDDLE_NAME'].' | '.$row['GENDER'].' | '.$row['LEVEL'].' | '.$row['PERMANENT_NUMBER']);
    }
    
  }
  
  public function choice($id) {
    $row = $this->db()->db_select('STUD_STUDENT', 'student')
      ->fields('student', array())
      ->join('CONS_CONSTITUENT', 'cons', 'cons.CONSTITUENT_ID = student.STUDENT_ID')
      ->fields('cons', array('PERMANENT_NUMBER', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'GENDER'))
      ->join('STUD_STUDENT_STATUS', 'stustatus', 'stustatus.STUDENT_ID = student.STUDENT_ID')
      ->fields('stustatus', array('STUDENT_STATUS_ID', 'LEVEL'))
      ->condition('stustatus.STUDENT_STATUS_ID', $id);
    if ($this->focus()->getOrganizationTermIDs())
      $row = $row->condition('ORGANIZATION_TERM_ID', $this->focus()->getOrganizationTermIDs());
    $row = $row->execute()->fetch();
    return $this->currentValue($row['STUDENT_STATUS_ID'], $row['LAST_NAME'].', '.$row['FIRST_NAME'].' '.$row['MIDDLE_NAME'].' | '.$row['GENDER'].' | '.$row['LEVEL'].' | '.$row['PERMANENT_NUMBER']);
  }
  
  public function searchRoute() {
    if ($this->session->get('portal') == 'core')
      return 'core_HEd_student_chooser';
    else
      return 'teacher_HEd_student_student_chooser';
  }
  
}