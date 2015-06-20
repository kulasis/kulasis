<?php

namespace Kula\Core\Bundle\SystemBundle\Record;

use Kula\Core\Component\Record\Record;
use Kula\Core\Component\Record\RecordDelegateInterface;

class NonOrganizationRecord extends Record implements RecordDelegateInterface {
  
  public function getSelectedRecordBarTemplate() {
    
  }
  
  public function getRecordBarTemplate() {
    return 'KulaCoreSystemBundle::Record/record_nonorganization.html.twig';
  }
  
  public function getRecordIDStack() {
    $result = $this->db->db_select('CORE_NON_ORGANIZATION', 'CORE_NON_ORGANIZATION')
      ->fields('CORE_NON_ORGANIZATION', array('NON_ORGANIZATION_ID' => 'ID'))
      ->orderBy('NON_ORGANIZATION_NAME', 'ASC')
      ->execute()->fetchAll();
    return $result;
  }
  
  public function get($record_id) {
    $result = $this->db->db_select('CORE_NON_ORGANIZATION')
      ->fields('CORE_NON_ORGANIZATION')
      ->condition('NON_ORGANIZATION_ID', $record_id)
      ->execute()->fetch();
    return $result;
  }
  
  public function getBaseTable() {
    return 'Core.NonOrganization';
  }
  
  public function getBaseKeyFieldName() {
    return 'Core.NonOrganization.ID';
  }
  
  public function modifySearchDBOBject($db_obj) {
    $db_obj = $db_obj->orderBy('NON_ORGANIZATION_NAME', 'ASC');
    return $db_obj;
  }
  
}