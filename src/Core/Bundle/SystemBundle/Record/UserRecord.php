<?php

namespace Kula\Core\Bundle\SystemBundle\Record;

use Kula\Core\Component\Record\Record;
use Kula\Core\Component\Record\RecordDelegateInterface;

class UserRecord extends Record implements RecordDelegateInterface {
  
  public function getSelectedRecordBarTemplate() { }
  
  public function getRecordBarTemplate() {
    return 'KulaCoreSystemBundle::Record/record_user.html.twig';
  }
  
  public function getRecordIDStack() {
    
    $result = $this->db->db_select('CORE_USER', 'user')
    ->fields('user', array('USER_ID' => 'ID'))
    ->join('CONS_CONSTITUENT', 'cons', 'cons.CONSTITUENT_ID = user.USER_ID')
    ->fields('cons', array('LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME'))
    ->orderBy('LAST_NAME', 'ASC', 'cons')
    ->orderBy('FIRST_NAME', 'ASC', 'cons')
    ->execute()->fetchAll();
    
    return $result;
    
  }
  
  public function get($record_id) {
    
    $result = $this->db->db_select('CORE_USER', 'user')
      ->fields('user', array('USER_ID', 'USERNAME'))
      ->join('CONS_CONSTITUENT', 'cons', 'cons.CONSTITUENT_ID = user.USER_ID')
      ->fields('cons', array('LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME'))
      ->condition('USER_ID', $record_id)
      ->execute()->fetch();
    
    return $result;
    
  }
  
  public function getBaseTable() {
    return 'Core.User';
  }
  
  public function getBaseKeyFieldName() {
    return 'Core.User.ID';
  }
  
  public function modifySearchDBOBject($db_obj) {
    $db_obj =  $db_obj->join('CORE_USER', 'CORE_USER', 'CONSTITUENT_ID = USER_ID')->fields('CORE_USER', array('USER_ID'));
    $db_obj =  $db_obj->orderBy('LAST_NAME', 'ASC');
    $db_obj =  $db_obj->orderBy('FIRST_NAME', 'ASC');
    
    return $db_obj;
  }
  
}