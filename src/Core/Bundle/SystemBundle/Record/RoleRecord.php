<?php

namespace Kula\Core\Bundle\SystemBundle\Record;

use Kula\Core\Component\Record\Record;
use Kula\Core\Component\Record\RecordDelegateInterface;

class RoleRecord extends Record implements RecordDelegateInterface {
  
  public function getSelectedRecordBarTemplate() { }
  
  public function getRecordBarTemplate() {
    return 'KulaCoreSystemBundle::Record/record_role.html.twig';
  }
  
  public function getRecordIDStack() {
    
    $result = $this->db()->db_select('CORE_USER_ROLES', 'userrole')
    ->fields('userrole', array('ROLE_ID' => 'ID'))
    ->join('CORE_USERGROUP', 'usergroup', 'usergroup.USERGROUP_ID = userrole.USERGROUP_ID')
    ->fields('usergroup', array('USERGROUP_NAME'))
    ->join('CORE_USER', 'user', 'user.USER_ID = userrole.USER_ID')
    ->fields('user', array('USERNAME'))
    ->join('CONS_CONSTITUENT', 'cons', 'cons.CONSTITUENT_ID = user.USER_ID')
    ->fields('cons', array('EMAIL', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME'))
    ->orderBy('cons.LAST_NAME', 'ASC')
    ->orderBy('cons.FIRST_NAME', 'ASC')
    ->orderBy('user.USERNAME', 'ASC')
    ->orderBy('usergroup.USERGROUP_NAME', 'ASC')
    ->execute()->fetchAll();
    
    return $result;
  }
  
  public function get($record_id) {
    $result = $this->db()->db_select('CORE_USER_ROLES', 'userrole')
    ->fields('userrole', array('ROLE_ID'))
    ->join('CORE_USERGROUP', 'usergroup', 'usergroup.USERGROUP_ID = CORE_USER_ROLES.USERGROUP_ID')
    ->fields('usergroup', array('USERGROUP_NAME', 'PORTAL'))
    ->join('CORE_USER', 'user', 'user.USER_ID = CORE_USER_ROLES.USER_ID')
    ->fields('user', array('USERNAME'))
    ->join('CONS_CONSTITUENT', 'cons', 'cons.CONSTITUENT_ID = user.USER_ID')
    ->fields('cons', array('EMAIL', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME'))
    ->condition('ROLE_ID', $record_id)->execute()->fetch();

    return $result;
  }
  
  public function getBaseTable() {
    return 'Core.User.Role';
  }
  
  public function getBaseKeyFieldName() {
    return 'Core.User.Role.ID';
  }
  
  public function modifySearchDBOBject($db_obj) {
    $db_obj = $db_obj->join('CONS_CONSTITUENT', 'CONS_CONSTITUENT', 'CONSTITUENT_ID = CORE_USER_ROLES.USER_ID')
      ->fields('CONS_CONSTITUENT', array('EMAIL', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME'));
    $db_obj = $db_obj->join('CORE_USER', 'CORE_USER', 'CONS_CONSTITUENT.CONSTITUENT_ID = CORE_USER.USER_ID')
      ->fields('CORE_USER', array('USER_ID'));
    $db_obj = $db_obj->join('CORE_USERGROUP', 'CORE_USERGROUP', 'CORE_USER_ROLES.USERGROUP_ID = CORE_USERGROUP.USERGROUP_ID')
      ->fields('CORE_USERGROUP', array('USERGROUP_ID'));
    $db_obj = $db_obj->orderBy('LAST_NAME', 'ASC');
    $db_obj = $db_obj->orderBy('FIRST_NAME', 'ASC');
    $db_obj = $db_obj->orderBy('USERNAME', 'ASC');
    $db_obj = $db_obj->orderBy('USERGROUP_NAME', 'ASC');
    
    return $db_obj;
  }
  
}