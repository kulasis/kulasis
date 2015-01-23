<?php

namespace Kula\Core\Bundle\SystemBundle\Chooser;

use Kula\Core\Component\Chooser\Chooser;

class UsergroupChooser extends Chooser {
  
  public function search($q) {
    
    $query_conditions = $this->db()->db_or();
    $query_conditions = $query_conditions->condition('USERGROUP_NAME', '%'.$q.'%', 'LIKE');
    
    $data = array();
    
    $search = $this->db()->db_select('CORE_USERGROUP')
      ->fields('CORE_USERGROUP', array('USERGROUP_ID', 'USERGROUP_NAME'))
      ->condition($query_conditions)
      ->orderBy('USERGROUP_NAME', 'ASC')
      ->execute();
    while ($row = $search->fetch()) {
      self::addToChooserMenu($row['USERGROUP_ID'], $row['USERGROUP_NAME']);
    }
    
  }
  
  public function choice($id) {
    $row = $this->db()->db_select('CORE_USERGROUP')
      ->fields('CORE_USERGROUP', array('USERGROUP_ID', 'USERGROUP_NAME'))
      ->condition('USERGROUP_ID', $id)
      ->execute()
      ->fetch();
    return self::currentValue($row['USERGROUP_ID'], $row['USERGROUP_NAME']);
  }
  
  public function searchRoute() {
    return 'core_system_usergroups_chooser';
  }
  
}