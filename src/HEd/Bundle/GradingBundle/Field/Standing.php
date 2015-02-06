<?php

namespace Kula\HEd\Bundle\GradingBundle\Field;

use Kula\Core\Component\Field\Field;

class Standing extends Field {
  
  public function select($schema, $param) {

    $menu = array();
    
    //$or_condition = new \Kula\Component\Database\Query\Predicate('OR');
    //$or_condition = $or_condition->predicate('scale.INACTIVE_AFTER', null)
    //  ->predicate('scale.INACTIVE_AFTER', date('Y-m-d'), '>');
    
    $result = $this->db()->db_select('STUD_STANDING', 'standing')
      ->fields('standing', array('STANDING_ID','STANDING_DESCRIPTION'))
  //->predicate($or_condition)
      ->orderBy('STANDING_CODE', 'ASC')->execute();
    while ($row = $result->fetch()) {
      $menu[$row['STANDING_ID']] = $row['STANDING_DESCRIPTION'];
    }
    
    return $menu;
    
  }
  
}