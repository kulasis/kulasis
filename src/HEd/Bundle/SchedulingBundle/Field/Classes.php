<?php

namespace Kula\HEd\Bundle\SchedulingBundle\Field;

use Kula\Core\Component\Field\Field;

class Classes extends Field {
  
  public function select($schema, $param) {
    
    $classes = array();
    $classes_result = $this->db()->db_select('STUD_STUDENT_CLASSES', 'classes')
      ->fields('classes', array('STUDENT_CLASS_ID'))
      ->join('STUD_SECTION', 'sec', 'classes.SECTION_ID = sec.SECTION_ID')
      ->fields('sec', array('SECTION_NUMBER'))
      ->join('STUD_COURSE', 'crs', 'crs.COURSE_ID = sec.COURSE_ID')
      ->fields('crs', array('COURSE_TITLE'))
      ->join('STUD_STUDENT_STATUS', 'status', 'status.STUDENT_STATUS_ID = classes.STUDENT_STATUS_ID')
      ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = sec.ORGANIZATION_TERM_ID')
      ->join('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
      ->fields('org', array('ORGANIZATION_ABBREVIATION'))
      ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
      ->fields('term', array('TERM_ABBREVIATION'))
      ->join('STUD_STUDENT_COURSE_HISTORY', 'crshis', 'crshis.STUDENT_CLASS_ID = classes.STUDENT_CLASS_ID')
      ->fields('crshis', array('COURSE_HISTORY_ID'))
      ->condition('status.STUDENT_ID', $param['STUDENT_ID'])
      ->orderBy('TERM_ABBREVIATION', 'ASC')
      ->orderBy('ORGANIZATION_ABBREVIATION', 'ASC')
      ->orderBy('COURSE_TITLE', 'ASC');
    $classes_result = $classes_result->execute();
    $i = 0;
    while ($classes_row = $classes_result->fetch()) {
      if ($classes_row['COURSE_HISTORY_ID'])
        $msg = 'Selected';
      else
        $msg = 'Not Selected';
      $classes[$msg][$classes_row['STUDENT_CLASS_ID']] = $classes_row['TERM_ABBREVIATION'] . ' / ' . $classes_row['ORGANIZATION_ABBREVIATION'] . ' / ' . $classes_row['SECTION_NUMBER'] . ' / ' . $classes_row['COURSE_TITLE'];
      
    $i++;
    }
    
    return $classes;
    
  }
  
}