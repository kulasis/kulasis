<?php

namespace Kula\HEd\Bundle\GradingBundle\Field;

use Kula\Core\Component\Field\Field;

class MajorName extends Field {
  
  public function select($schema, $param) {
    
    $menu = array();
    
    $result = $this->db->db_select('STUD_DEGREE_MAJOR', 'major')
      ->fields('major', array('MAJOR_ID', 'MAJOR_NAME'))
      ->orderBy('MAJOR_NAME', 'ASC')->execute();
    while ($row = $result->fetch()) {
      $menu[$row['MAJOR_ID']] = $row['MAJOR_NAME'];
    }
    
    return $menu;
    
  }
  
}