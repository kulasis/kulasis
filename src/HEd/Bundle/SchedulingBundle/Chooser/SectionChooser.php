<?php

namespace Kula\HEd\Bundle\SchedulingBundle\Chooser;

use Kula\Core\Component\Chooser\Chooser;

class SectionChooser extends Chooser {
  
  public function search($q) {
    
    $query_conditions = $this->db()->db_or();
    $query_conditions = $query_conditions->condition('COURSE_TITLE', '%'.$q.'%', 'LIKE');
    $query_conditions = $query_conditions->condition('COURSE_NUMBER', '%'.$q.'%', 'LIKE');
    $query_conditions = $query_conditions->condition('SHORT_TITLE', '%'.$q.'%', 'LIKE');
    $query_conditions = $query_conditions->condition('SECTION_NUMBER', '%'.$q.'%', 'LIKE');
    $query_conditions = $query_conditions->condition('SECTION_NAME', '%'.$q.'%', 'LIKE');
    
    $data = array();
    
    $search = $this->db()->db_select('STUD_SECTION', 'sec')
      ->fields('sec', array('SECTION_ID', 'SECTION_NUMBER', 'SECTION_NAME'))
      ->join('STUD_COURSE', 'course', 'course.COURSE_ID = sec.COURSE_ID')
      ->fields('course', array('COURSE_ID', 'COURSE_NUMBER', 'COURSE_TITLE'))
      ->condition($query_conditions)
      ->orderBy('COURSE_NUMBER', 'ASC');

    if ($this->focus()->getOrganizationTermIDs())
      $search = $search->condition('sec.ORGANIZATION_TERM_ID', $this->focus()->getOrganizationTermIDs());
    
    $search = $search->execute();
    while ($row = $search->fetch()) {
      if ($row['SECTION_NAME']) $title = $row['SECTION_NAME']; else $title = $row['COURSE_TITLE'];
      $this->addToChooserMenu($row['SECTION_ID'], $row['SECTION_NUMBER'].' / '.$title);
    }
    
  }
  
  public function choice($id) {
    $row = $this->db()->db_select('STUD_SECTION', 'sec')
      ->fields('sec', array('SECTION_ID', 'SECTION_NUMBER', 'SECTION_NAME'))
      ->join('STUD_COURSE', 'course', 'course.COURSE_ID = sec.COURSE_ID')
      ->fields('course', array('COURSE_ID', 'COURSE_NUMBER', 'COURSE_TITLE'))
      ->condition('sec.SECTION_ID', $id)
      ->execute()
      ->fetch();
    if ($row['SECTION_NAME']) $title = $row['SECTION_NAME']; else $title = $row['COURSE_TITLE'];
    return $this->currentValue($row['SECTION_ID'], $row['SECTION_NUMBER'].' / '.$title);
  }
  
  public function searchRoute() {
    return 'Core_HEd_Section_Chooser';
  }
  
}