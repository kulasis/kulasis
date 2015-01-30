<?php

namespace Kula\HEd\Bundle\GradingBundle\Field;

use Kula\Core\Component\Field\Field;

class MinorName extends Field {
  
  public function select($schema, $param) {
    
    $menu = array();
    
    $result = $this->db()->db_select('STUD_DEGREE_MINOR', 'minor')
      ->fields('minor', array('MINOR_ID', 'MINOR_NAME'))
      ->orderBy('MINOR_NAME', 'ASC')->execute();
    while ($row = $result->fetch()) {
      $menu[$row['MINOR_ID']] = $row['MINOR_NAME'];
    }
    
    return $menu;
    
  }
  
}