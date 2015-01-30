<?php

namespace Kula\HEd\Bundle\FinancialAidBundle\Field;

use Kula\Core\Component\Field\Field;

class Award extends Field {
  
  public function select($schema, $param) {

    $menu = array();
    
    //$or_condition = new \Kula\Component\Database\Query\Predicate('OR');
    //$or_condition = $or_condition->predicate('marks.INACTIVE_AFTER', null)
    //  ->predicate('marks.INACTIVE_AFTER', date('Y-m-d'), '>');
    
    $result = $this->db()->db_select('FAID_AWARD_CODE', 'award')
      ->fields('award', array('AWARD_CODE_ID', 'AWARD_CODE', 'AWARD_DESCRIPTION'))
      ->condition('INACTIVE', 0)
      ->orderBy('AWARD_CODE', 'ASC')->execute();
    while ($row = $result->fetch()) {
      $menu[$row['AWARD_CODE_ID']] = $row['AWARD_CODE'].' - '.$row['AWARD_DESCRIPTION'];
    }
    
    return $menu;
    
  }
  
}