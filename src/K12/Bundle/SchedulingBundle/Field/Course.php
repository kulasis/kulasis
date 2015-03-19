<?php

namespace Kula\K12\Bundle\SchedulingBundle\Field;

use Kula\Core\Component\Field\Field;

class Course implements Field {
  
  public function select($schema, $param) {
    
    $courses = array();
    
    $set_course = $this->db()->db_select('STUD_SECTION', 'sec')
      ->fields('sec', array())
      ->join('STUD_COURSE', 'crs', 'sec.COURSE_ID = crs.COURSE_ID')
      ->fields('crs', array('COURSE_ID', 'COURSE_TITLE', 'COURSE_NUMBER'))
      ->condition('sec.SECTION_ID', $param['SECTION_ID'])
      ->execute()->fetch();
    $courses[$set_course['COURSE_ID']] = $set_course['COURSE_NUMBER'].' '.$set_course['COURSE_TITLE'];
    
    $courses_result = $this->db()->db_select('STUD_SECTION_COURSES', 'seccourses')
      ->fields('seccourses', array('COURSE_ID'))
      ->join('STUD_COURSE', 'crs', 'crs.COURSE_ID = seccourses.COURSE_ID')
      ->fields('crs', array('COURSE_NUMBER', 'COURSE_TITLE'))
      ->condition('seccourses.SECTION_ID', $param['SECTION_ID'])
      ->orderBy('COURSE_NUMBER', 'ASC');
    $courses_result = $courses_result->execute();
    $i = 0;
    
    while ($courses_row = $courses_result->fetch()) {
      $courses[$courses_row['COURSE_ID']] = $courses_row['COURSE_NUMBER'].' '.$courses_row['COURSE_TITLE'];
      
    $i++;
    }
    
    if ($courses_row) {
      return $courses;
    } else {
      return array();
    }
    
  }
  
}