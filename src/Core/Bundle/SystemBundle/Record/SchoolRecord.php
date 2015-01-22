<?php

namespace Kula\Core\Bundle\SystemBundle\Record;

use Kula\Core\Component\Record\Record;
use Kula\Core\Component\Record\RecordDelegateInterface;

class SchoolRecord extends Record implements RecordDelegateInterface {
  
  public function getSelectedRecordBarTemplate() {
    
  }
  
  public function getRecordBarTemplate() {
    return 'KulaCoreSystemBundle::Record/record_school.html.twig';
  }
  
  public function getRecordIDStack() {
    $result = $this->db->db_select('CORE_ORGANIZATION', 'CORE_ORGANIZATION')
      ->fields('CORE_ORGANIZATION', array('ORGANIZATION_ID' => 'ID'))
      ->condition('ORGANIZATION_TYPE', 'SCHL')
      ->orderBy('ORGANIZATION_NAME', 'ASC')
      ->execute()->fetchAll();
    return $result;
  }
  
  public function get($record_id) {
    $result = $this->db->db_select('CORE_ORGANIZATION')
      ->fields('CORE_ORGANIZATION')
      ->condition('ORGANIZATION_ID', $record_id)
      ->condition('ORGANIZATION_TYPE', 'SCHL')
      ->execute()->fetch();
    return $result;
  }
  
  public function getBaseTable() {
    return 'Core.Organization';
  }
  
  public function getBaseKeyFieldName() {
    return 'Core.Organization.ID';
  }
  
  public function modifySearchDBOBject($db_obj) {
    $db_obj = $db_obj->condition('ORGANIZATION_TYPE', 'SCHL');
    $db_obj = $db_obj->orderBy('ORGANIZATION_NAME', 'ASC');
    return $db_obj;
  }
  
}