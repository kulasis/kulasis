<?php

namespace Kula\Core\Bundle\BillingBundle\Field;

use Kula\Core\Component\Field\Field;

class SectionDiscount extends Field {
  
  public function select($schema, $param) {

    $menu = array();

    $discount_or = $this->db()->db_or();
    $discount_or = $discount_or->condition('sectiondiscount.END_DATE', date('Y-m-d'), '>=');
    $discount_or = $discount_or->isNull('sectiondiscount.END_DATE');
    
    $result = $this->db()->db_select('BILL_SECTION_FEE_DISCOUNT', 'sectiondiscount')
      ->fields('sectiondiscount', array('SECTION_FEE_DISCOUNT_ID', 'AMOUNT'))
      ->leftJoin('CORE_LOOKUP_VALUES', 'value', "value.CODE = sectiondiscount.DISCOUNT AND value.LOOKUP_TABLE_ID = (SELECT LOOKUP_TABLE_ID FROM CORE_LOOKUP_TABLES WHERE LOOKUP_TABLE_NAME = 'Core.Billing.Fee.Discount')")
      ->fields('value', array('DESCRIPTION' => 'discount'))
      ->join('BILL_CODE', 'codes', 'sectiondiscount.CODE_ID = codes.CODE_ID')
      ->fields('codes', array('CODE', 'CODE_TYPE'))
      ->condition('sectiondiscount.SECTION_ID', $param['SECTION_ID'])
      ->condition($discount_or)
      ->orderBy('DESCRIPTION', 'ASC', 'value')
      ->execute();
    while ($row = $result->fetch()) {
      $menu[$row['SECTION_FEE_DISCOUNT_ID']] = $row['discount'].' '.$row['AMOUNT'];
    }
    
    return $menu;
    
  }
  
}