<?php

namespace Kula\Core\Bundle\SystemBundle\Record;

use Kula\Core\Component\Record\Record;
use Kula\Core\Component\Record\RecordDelegateInterface;

class LookupRecord extends Record implements RecordDelegateInterface {
  
  public function getSelectedRecordBarTemplate() {
    
  }
  
  public function getRecordBarTemplate() {
    return 'KulaCoreSystemBundle::Record/record_lookup.html.twig';
  }
  
  public function getRecordIDStack() {
    
    $result = $this->db->db_select('CORE_LOOKUP_TABLES')
    ->fields('CORE_LOOKUP_TABLES', array('LOOKUP_TABLE_ID' => 'ID'))
    ->orderBy('LOOKUP_TABLE_NAME', 'ASC')
    ->execute()->fetchAll();
    
    return $result;
  }
  
  public function get($record_id) {
    
    $result = $this->db->db_select('CORE_LOOKUP_TABLES')
      ->fields('CORE_LOOKUP_TABLES')
      ->condition('LOOKUP_TABLE_ID', $record_id)
      ->execute()->fetch();
    
    return $result;
  }
  
  public function getBaseTable() {
    return 'Core.Lookup.Table';
  }
  
  public function getBaseKeyFieldName() {
    return 'Core.Lookup.Table.ID';
  }
  
  public function modifySearchDBOBject($db_obj) {
    $db_obj =  $db_obj->orderBy('LOOKUP_TABLE_NAME', 'ASC');
    return $db_obj;
  }
  
}