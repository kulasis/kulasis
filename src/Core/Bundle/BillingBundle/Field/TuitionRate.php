<?php

namespace Kula\Core\Bundle\BillingBundle\Field;

use Kula\Core\Component\Field\Field;

class TuitionRate extends Field{
  
  public function select($schema, $param) {

    $menu = array();
    
    //$or_condition = new \Kula\Component\Database\Query\Predicate('OR');
    //$or_condition = $or_condition->condition('scale.INACTIVE_AFTER', null)
    //  ->condition('scale.INACTIVE_AFTER', date('Y-m-d'), '>');

    $result = $this->db()->db_select('BILL_TUITION_RATE', 'tuitionrate')
  ->fields('tuitionrate', array('TUITION_RATE_ID', 'TUITION_RATE_NAME'));
  //->condition($or_condition)
    $result = $result->condition('ORGANIZATION_TERM_ID', $param['ORGANIZATION_TERM_ID']);
    $result = $result->orderBy('TUITION_RATE_NAME', 'ASC')->execute();
    while ($row = $result->fetch()) {
      $menu[$row['TUITION_RATE_ID']] = $row['TUITION_RATE_NAME'];
    }

    return $menu;
    
  }
  
}