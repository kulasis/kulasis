<?php

namespace Kula\HEd\Bundle\GradingBundle\Field;

use Kula\Core\Component\Field\Field;

class ConcentrationName extends Field {
  
  public function select($schema, $param) {
    
    $menu = array();
    
    $result = $this->db()->db_select('STUD_DEGREE_CONCENTRATION', 'concentration')
      ->fields('concentration', array('CONCENTRATION_ID', 'CONCENTRATION_NAME'))
      ->orderBy('CONCENTRATION_NAME', 'ASC')->execute();
    while ($row = $result->fetch()) {
      $menu[$row['CONCENTRATION_ID']] = $row['CONCENTRATION_NAME'];
    }
    
    return $menu;
    
  }
  
}