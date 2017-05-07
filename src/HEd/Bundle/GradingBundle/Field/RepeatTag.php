<?php

namespace Kula\HEd\Bundle\GradingBundle\Field;

use Kula\Core\Component\Field\Field;

class RepeatTag extends Field {
  
  public function select($schema, $param) {

    $menu = array();
    
    $or_condition = $this->db()->db_or();
    $or_condition = $or_condition->condition('repeattag.INACTIVE_AFTER', null)
      ->condition('repeattag.INACTIVE_AFTER', date('Y-m-d'), '>');
    
    $result = $this->db()->db_select('STUD_REPEAT_TAG', 'repeattag')
  ->fields('repeattag', array('REPEAT_TAG_ID','REPEAT_TAG_CODE', 'REPEAT_TAG_NAME'))
  ->condition($or_condition)
  ->orderBy('REPEAT_TAG_CODE', 'ASC')
  ->orderBy('REPEAT_TAG_NAME', 'ASC')
  ->execute();
    while ($row = $result->fetch()) {
      $menu[$row['REPEAT_TAG_ID']] = $row['REPEAT_TAG_CODE'].' - '.$row['REPEAT_TAG_NAME'];
    }
    
    return $menu;
    
  }
  
}