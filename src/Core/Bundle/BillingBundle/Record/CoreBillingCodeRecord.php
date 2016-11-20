<?php

namespace Kula\Core\Bundle\BillingBundle\Record;

use Kula\Core\Component\Record\Record;

class CoreBillingCodeRecord extends Record {
  
  public function getSelectedRecordBarTemplate() {
    
  }
  
  public function getRecordBarTemplate() {
    return 'KulaCoreBillingBundle::CoreRecord/record_bill_code.html.twig';
  }
  
  public function getRecordIDStack() {
    
    $result = $this->db()->db_select('BILL_CODE', 'billcode')
    ->fields('billcode', array('CODE_ID' => 'ID'))
    ->orderBy('CODE', 'ASC');
    $result = $result->execute()->fetchAll();
    
    return $result;    
  }
  
  public function get($record_id) {
    
    $result = $this->db()->db_select('BILL_CODE', 'billcode')
    ->fields('billcode', array('CODE_ID', 'CODE_TYPE', 'CODE', 'CODE_DESCRIPTION'))
    ->condition('billcode.CODE_ID', $record_id)
    ->execute()->fetch();
    return $result;
    
  }
  
  public function getBaseTable() {
    return 'Core.Billing.Code';
  }
  
  public function getBaseKeyFieldName() {
    return 'Core.Billing.Code.ID';
  }
  
  public function modifySearchDBOBject($db_obj) {
    return $db_obj;
  }

}