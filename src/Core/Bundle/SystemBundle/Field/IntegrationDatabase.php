<?php

namespace Kula\Core\Bundle\SystemBundle\Field;

use Kula\Core\Component\Field\Field;

class IntegrationDatabase extends Field {
  
  public function select($schema, $param) {
    
    $menu = array();
    
    $result = $this->db()->db_select('CORE_INTG_DATABASE', 'dbs')
  ->fields('dbs')
  ->condition('APPLICATION', $param['APPLICATION'])
  ->orderBy('APPLICATION', 'ASC')
  ->orderBy('HOST', 'ASC')
  ->orderBy('DATABASE_NAME', 'ASC')
  ->execute();
    while ($row = $result->fetch()) {
      $menu[$row['INTG_DATABASE_ID']] = $row['APPLICATION'].' - '.$row['HOST'].' - '.$row['DATABASE_NAME'];
    }
    
    return $menu;
    
  }
  
}