<?php

namespace Kula\Core\Bundle\SystemBundle\Field;

use Kula\Core\Component\Field\Field;

class LDAPServer extends Field {
  
  public function select($schema, $param) {
    
    $menu = array();
    
    $result = $this->db()->db_select('CORE_SYSTEM_LDAP', 'system_ldap')
  ->fields('system_ldap', array('LDAP_ID', 'SERVER_NAME'))
  ->orderBy('SERVER_NAME', 'ASC')
  ->execute();
    while ($row = $result->fetch()) {
      $menu[$row['LDAP_ID']] = $row['SERVER_NAME'];
    }
    
    return $menu;
    
  }
  
}