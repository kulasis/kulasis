<?php

namespace Kula\HEd\Bundle\BillingBundle\Field;

use Kula\Core\Component\Field\Field;

class TransactionCode extends Field {
  
  public function select($schema, $param) {

    $menu = array();
    
    //$or_condition = new \Kula\Component\Database\Query\Predicate('OR');
    //$or_condition = $or_condition->predicate('scale.INACTIVE_AFTER', null)
    //  ->predicate('scale.INACTIVE_AFTER', date('Y-m-d'), '>');
    
    $result = $this->db()->db_select('BILL_CODE', 'code')
      ->fields('code', array('CODE_ID', 'CODE', 'CODE_DESCRIPTION'));
  //->predicate($or_condition)
      if (isset($param['CODE_TYPE']))
        $result = $result->condition('CODE_TYPE', $param['CODE_TYPE']);
      $result = $result->orderBy('CODE', 'ASC')->execute();
    while ($row = $result->fetch()) {
      $menu[$row['CODE_ID']] = $row['CODE'] . ' - ' . $row['CODE_DESCRIPTION'];
    }
    
    return $menu;
    
  }
  
}