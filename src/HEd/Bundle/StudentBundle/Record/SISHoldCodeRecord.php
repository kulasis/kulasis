<?php

namespace Kula\HEd\Bundle\StudentBundle\Record;

use Kula\Core\Component\Record\Record;

class SISHoldCodeRecord extends Record {
  
  public function getSelectedRecordBarTemplate() {
    return '';
  }
  
  public function getRecordBarTemplate() {
    return 'KulaHEdStudentBundle::SISRecord/record_hold_code.html.twig';
  }
  
  public function getRecordIDStack() {
    
    //$or_query_conditions = new \Kula\Core\Database\Query\Predicate('OR');
    //$or_query_conditions = $or_query_conditions->predicate('EFFECTIVE_DATE', null);
    //$or_query_conditions = $or_query_conditions->predicate('EFFECTIVE_DATE', date('Y-m-d'), '>=');
    
    $result = $this->db()->db_select('STUD_HOLD', 'hold')
    ->distinct()
    ->fields('hold', array('HOLD_ID' => 'ID'))
    ->condition('INACTIVE', 0)
    ->orderBy('HOLD_CODE')
    ->execute()->fetchAll();
    return $result;  
  }
  
  public function get($record_id) {
    
    $result = $this->db()->db_select('STUD_HOLD', 'hold')
      ->fields('hold', array('HOLD_ID', 'HOLD_CODE', 'HOLD_NAME'))
      ->condition('HOLD_ID', $record_id)
      ->execute()->fetch();
    
    return $result;
  }
  
  public function getBaseTable() {
    return 'HEd.Hold';
  }
  
  public function getBaseKeyFieldName() {
    return 'HEd.Hold.ID';
  }
  
  public function modifySearchDBOBject($db_obj) {
    $db_obj =  $db_obj->orderBy('HOLD_CODE', 'ASC');
    return $db_obj;
  }
  
}