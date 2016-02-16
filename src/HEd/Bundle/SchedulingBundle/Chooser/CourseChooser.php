<?php

namespace Kula\HEd\Bundle\SchedulingBundle\Chooser;

use Kula\Core\Component\Chooser\Chooser;

class CourseChooser extends Chooser {
  
  public function search($q) {
    
    $query_conditions = $this->db()->db_or();
    $query_conditions = $query_conditions->condition('COURSE_TITLE', '%'.$q.'%', 'LIKE');
    $query_conditions = $query_conditions->condition('COURSE_NUMBER', '%'.$q.'%', 'LIKE');
    $query_conditions = $query_conditions->condition('SHORT_TITLE', '%'.$q.'%', 'LIKE');
    
    $data = array();
    
    $search = $this->db()->db_select('STUD_COURSE', 'course')
      ->fields('course', array('COURSE_ID', 'COURSE_NUMBER', 'COURSE_TITLE'))
      ->condition($query_conditions)
      ->orderBy('COURSE_NUMBER', 'ASC');
    $search = $search  ->execute();
    while ($row = $search->fetch()) {
      $this->addToChooserMenu($row['COURSE_ID'], $row['COURSE_NUMBER'].' / '.$row['COURSE_TITLE']);
    }
    
  }
  
  public function choice($id) {
    $row = $this->db()->db_select('STUD_COURSE', 'course')
      ->fields('course', array('COURSE_ID', 'COURSE_NUMBER', 'COURSE_TITLE'))
      ->condition('course.COURSE_ID', $id)
      ->execute()
      ->fetch();
    return $this->currentValue($row['COURSE_ID'], $row['COURSE_NUMBER'].' / '.$row['COURSE_TITLE']);
  }
  
  public function searchRoute() {
    return 'Core_HEd_Course_Chooser';
  }
  
}