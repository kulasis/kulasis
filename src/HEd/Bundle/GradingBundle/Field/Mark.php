<?php

namespace Kula\HEd\Bundle\GradingBundle\Field;

use Kula\Core\Component\Field\Field;

class Mark extends Field {
  
  public function select($schema, $param) {

    $menu = array();
    
    $or_condition = $this->db()->db_or();
    $or_condition = $or_condition->condition('marks.INACTIVE_AFTER', null)
      ->condition('marks.INACTIVE_AFTER', date('Y-m-d'), '>');
    
    $result = $this->db()->db_select('STUD_MARK_SCALE_MARKS', 'marks')
      ->fields('marks', array('MARK'))
      ->condition('marks.MARK_SCALE_ID', $param['MARK_SCALE_ID'])
      ->condition($or_condition)
      ->orderBy('SORT', 'ASC');
    
    if (isset($param['TEACHER']) AND $param['TEACHER']) {
      $result = $result->condition('marks.ALLOW_TEACHER', '1');
    }
      
    $result = $result->execute();
    while ($row = $result->fetch()) {
      $menu[$row['MARK']] = $row['MARK'];
    }
    
    return $menu;
    
  }
  
}