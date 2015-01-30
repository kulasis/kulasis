<?php

namespace Kula\HEd\Bundle\GradingBundle\Field;

use Kula\Core\Component\Field\Field;

class MarkScale extends Field {
  
  public function select($schema, $param) {

    $menu = array();
    
    $or_condition = $this->db()->db_or();
    $or_condition = $or_condition->condition('scale.INACTIVE_AFTER', null)
      ->condition('scale.INACTIVE_AFTER', date('Y-m-d'), '>');
    
    $result = $this->db()->db_select('STUD_MARK_SCALE', 'scale')
  ->fields('scale', array('MARK_SCALE_ID','MARK_SCALE_NAME'))
  ->condition($or_condition)
  ->orderBy('MARK_SCALE_NAME', 'ASC')->execute();
    while ($row = $result->fetch()) {
      $menu[$row['MARK_SCALE_ID']] = $row['MARK_SCALE_NAME'];
    }
    
    return $menu;
    
  }
  
}