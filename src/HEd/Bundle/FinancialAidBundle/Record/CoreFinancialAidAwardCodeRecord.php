<?php

namespace Kula\HEd\Bundle\FinancialAidBundle\Record;

use Kula\Core\Component\Record\Record;

class CoreFinancialAidAwardCodeRecord extends Record {
  
  public function getSelectedRecordBarTemplate() {
    return '';
  }
  
  public function getRecordBarTemplate() {
    return 'KulaHEdFinancialAidBundle::CoreRecord/record_faid_award_code.html.twig';
  }
  
  public function getRecordIDStack() {
    
    //$or_query_conditions = new \Kula\Core\Database\Query\Predicate('OR');
    //$or_query_conditions = $or_query_conditions->condition('EFFECTIVE_DATE', null);
    //$or_query_conditions = $or_query_conditions->condition('EFFECTIVE_DATE', date('Y-m-d'), '>=');
    
    $result = $this->db()->db_select('FAID_AWARD_CODE', 'code')
    ->distinct()
    ->fields('code', array('AWARD_CODE_ID' => 'ID', 'AWARD_CODE'))
    ->condition('INACTIVE', '0')
    ->orderBy('AWARD_CODE')
    ->execute()->fetchAll();
    return $result;  
  }
  
  public function get($record_id) {
    
    $result = $this->db()->db_select('FAID_AWARD_CODE', 'code')
      ->fields('code', array('AWARD_CODE_ID', 'AWARD_CODE', 'AWARD_DESCRIPTION'))
      ->condition('AWARD_CODE_ID', $record_id)
      ->execute()->fetch();
    
    return $result;
  }
  
  public function getBaseTable() {
    return 'HEd.FAID.Code';
  }
  
  public function getBaseKeyFieldName() {
    return 'HEd.FAID.Code.ID';
  }
  
  public function modifySearchDBOBject($db_obj) {
    $db_obj =  $db_obj->orderBy('AWARD_CODE', 'ASC');
    return $db_obj;
  }
  
}