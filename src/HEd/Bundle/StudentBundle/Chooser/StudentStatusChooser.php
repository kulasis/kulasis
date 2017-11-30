<?php

namespace Kula\HEd\Bundle\StudentBundle\Chooser;

use Kula\Core\Component\Chooser\Chooser;

class StudentStatusChooser extends Chooser {
  
  public function search($q) {
    
    $query_conditions = $this->db()->db_or()
      ->condition('cons.CONSTITUENT_ID', $q)
      ->condition('cons.LAST_NAME', $q.'%', 'LIKE')
      ->condition('cons.FIRST_NAME', $q.'%', 'LIKE')
      ->condition('cons.PERMANENT_NUMBER', $q.'%', 'LIKE')
      ->condition('stustatus.STUDENT_STATUS_ID', $q.'%', 'LIKE');
    
    $data = array();
    
    $search = $this->db()->db_select('CONS_CONSTITUENT', 'cons')
      ->fields('cons', array('CONSTITUENT_ID', 'LAST_NAME', 'FIRST_NAME', 'GENDER', 'PERMANENT_NUMBER'))
      ->join('STUD_STUDENT_STATUS', 'stustatus', 'stustatus.STUDENT_ID = cons.CONSTITUENT_ID')
      ->fields('stustatus', array('STUDENT_STATUS_ID'))
      ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = stustatus.ORGANIZATION_TERM_ID')
      ->join('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
      ->fields('org', array('ORGANIZATION_ABBREVIATION'))
      ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
      ->fields('term', array('TERM_ABBREVIATION'))
      ->condition($query_conditions)
      ->orderBy('LAST_NAME', 'ASC')
      ->orderBy('FIRST_NAME', 'ASC')
      ->orderBy('ORGANIZATION_ABBREVIATION', 'ASC')
      ->orderBy('TERM_ABBREVIATION', 'ASC')
      ->execute();
    while ($row = $search->fetch()) {
      $this->addToChooserMenu($row['STUDENT_STATUS_ID'], $row['LAST_NAME'].', '.$row['FIRST_NAME'].' | '.$row['GENDER'].' | '.$row['PERMANENT_NUMBER'].' | '.$row['ORGANIZATION_ABBREVIATION'].' | '.$row['TERM_ABBREVIATION'].' | '.$row['CONSTITUENT_ID'].' | '.$row['STUDENT_STATUS_ID']);
    }
    
  }
  
  public function choice($id) {
    
    $row = $this->db()->db_select('CONS_CONSTITUENT', 'cons')
      ->fields('cons', array('CONSTITUENT_ID', 'LAST_NAME', 'FIRST_NAME', 'GENDER', 'PERMANENT_NUMBER'))
      ->join('STUD_STUDENT_STATUS', 'stustatus', 'stustatus.STUDENT_ID = cons.CONSTITUENT_ID')
      ->fields('stustatus', array('STUDENT_STATUS_ID'))
      ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = stustatus.ORGANIZATION_TERM_ID')
      ->join('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
      ->fields('org', array('ORGANIZATION_ABBREVIATION'))
      ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
      ->fields('term', array('TERM_ABBREVIATION'))
      ->condition('cons.CONSTITUENT_ID', $id)
      ->execute()
      ->fetch();
    return $this->currentValue($row['STUDENT_STATUS_ID'], $row['LAST_NAME'].', '.$row['FIRST_NAME'].' | '.$row['GENDER'].' | '.$row['PERMANENT_NUMBER'].' | '.$row['ORGANIZATION_ABBREVIATION'].' | '.$row['TERM_ABBREVIATION'].' | '.$row['CONSTITUENT_ID'].' | '.$row['STUDENT_STATUS_ID']);
    
  }
  
  public function searchRoute() {
    return 'core_HEd_Student_Status_Chooser';
  }
  
}