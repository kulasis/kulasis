<?php

namespace Kula\HEd\Bundle\StudentBundle\Field;

use Kula\Core\Component\Field\Field;

class Hold implements Field {
  
  public function select($schema, $param) {

    $menu = array();
    
    $result = $this->db()->db_select('STUD_HOLD', 'hold')
  ->fields('hold', array('HOLD_ID', 'HOLD_CODE', 'HOLD_NAME'))
  ->condition('INACTIVE', '0')
  ->orderBy('HOLD_CODE', 'ASC')->execute();
    while ($row = $result->fetch()) {
      $menu[$row['HOLD_ID']] = $row['HOLD_CODE'].' - '.$row['HOLD_NAME'];
    }
    
    return $menu;
    
  }
  
}

