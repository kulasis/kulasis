<?php

namespace Kula\HEd\Bundle\GradingBundle\Field;

use Kula\Core\Component\Field\Field;

class DegreeName extends Field {
  
  public function select($schema, $param) {
    
    $menu = array();
    
    $result = $this->db()->db_select('STUD_DEGREE', 'degree')
      ->fields('degree', array('DEGREE_ID', 'DEGREE_NAME'))
      ->orderBy('DEGREE_NAME', 'ASC')->execute();
    while ($row = $result->fetch()) {
      $menu[$row['DEGREE_ID']] = $row['DEGREE_NAME'];
    }
    
    return $menu;
    
  }
  
}