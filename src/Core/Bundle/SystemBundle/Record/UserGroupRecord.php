<?php

namespace Kula\Core\Bundle\SystemBundle\Record;

use Kula\Core\Component\Record\Record;
use Kula\Core\Component\Record\RecordDelegateInterface;

class UserGroupRecord extends Record implements RecordDelegateInterface {
  
  public function getSelectedRecordBarTemplate() { }
  
  public function getRecordBarTemplate() {
    return 'KulaCoreSystemBundle::Record/record_usergroup.html.twig';
  }
  
  public function getRecordIDStack() {
    $result = $this->db->db_select('CORE_USERGROUP', 'CORE_USERGROUP')
      ->fields('CORE_USERGROUP', array('USERGROUP_ID' => 'ID'))
      ->orderBy('USERGROUP_NAME', 'ASC')
      ->execute()->fetchAll();
    return $result;
  }
  
  public function get($record_id) {
    $result = $this->db->db_select('CORE_USERGROUP', 'CORE_USERGROUP')
      ->fields('CORE_USERGROUP', array('USERGROUP_ID', 'USERGROUP_NAME', 'PORTAL'))
      ->condition('USERGROUP_ID', $record_id)->execute()->fetch();
    return $result;
  }
  
  public function getBaseTable() {
    return 'Core.Usergroup';
  }
  
  public function getBaseKeyFieldName() {
    return 'Core.Usergroup.ID';
  }
  
  public function modifySearchDBOBject($db_obj) {
    $db_obj =  $db_obj->orderBy('USERGROUP_NAME', 'ASC');
    return $db_obj;
  }
  
}