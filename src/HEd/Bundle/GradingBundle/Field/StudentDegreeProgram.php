<?php

namespace Kula\HEd\Bundle\GradingBundle\Field;

use Kula\Core\Component\Field\Field;

class StudentDegreeProgram implements Field {
  
  public function select($schema, $param) {

    $menu = array();

    $or_condition = $this->db()->db_or();
    $or_condition = $or_condition->condition('marks.INACTIVE_AFTER', null)
      ->condition('marks.INACTIVE_AFTER', date('Y-m-d'), '>');
    
    $result = $this->db()->db_select('STUD_STUDENT_DEGREES', 'studegrees')
  ->fields('studegrees', array('STUDENT_DEGREE_ID', 'EFFECTIVE_DATE'))
  ->join('STUD_DEGREE', 'degree', 'studegrees.DEGREE_ID = degree.DEGREE_ID')
  ->fields('degree', array('DEGREE_NAME'))
  ->condition('studegrees.STUDENT_ID', $param['STUDENT_ID'])
  ->orderBy('EFFECTIVE_DATE', 'DESC')->execute();
    while ($row = $result->fetch()) {
      $menu[$row['STUDENT_DEGREE_ID']] = $row['DEGREE_NAME'];
      if ($row['EFFECTIVE_DATE']) $menu[$row['STUDENT_DEGREE_ID']] .= ' - '.date('m/d/Y', strtotime($row['EFFECTIVE_DATE']));
    }
    
    return $menu;
    
  }
  
}