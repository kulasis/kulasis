<?php

namespace Kula\Core\Component\Lookup;

class Lookup {
  
  private $lookup = array();
  
  private $db;
  
  public function __construct($db) {
    $this->db = $db;
  }
  
  public function setDependencies($db) {
    $this->db = $db;
  }
  
  public function getLookupMenu($lookup_id, $description = 'D', $all_values = false) {
    
    $menu = array();
    
    if ($all_values === false) {
      $query_conditions = $this->db->db_or();
      $query_conditions = $query_conditions->condition('INACTIVE_AFTER', null);
      $query_conditions = $query_conditions->condition('INACTIVE_AFTER', date('Y-m-d'), '>=');  
    }
    $result = $this->db->db_select('CORE_LOOKUP_VALUES')
      ->fields('CORE_LOOKUP_VALUES', array('CODE','DESCRIPTION'))
      ->join('CORE_LOOKUP_TABLES', 'CORE_LOOKUP_TABLES', 'CORE_LOOKUP_VALUES.LOOKUP_TABLE_ID = CORE_LOOKUP_TABLES.LOOKUP_TABLE_ID')
      ->condition('LOOKUP_TABLE_NAME', $lookup_id);
      if ($all_values === false) {
        $result = $result->condition($query_conditions);
      }
      $result = $result->orderBy('SORT', 'ASC');
      if ($description == 'C')
        $result = $result->orderBy('CODE', 'ASC');
      else
        $result = $result->orderBy('DESCRIPTION', 'ASC');
      $result = $result->execute();
    while ($row = $result->fetch()) {
      if ($description == 'C')
        $menu[$row['CODE']] = $row['CODE'];
      elseif ($description == 'CD')
        $menu[$row['CODE']] = $row['CODE'].' - '.$row['DESCRIPTION'];
      else
        $menu[$row['CODE']] = $row['DESCRIPTION'];
    }
    
    return $menu;
  }
  
  public function getLookupValue($lookup_id, $value, $description = 'D', $all_values = false) {

    if ($all_values === false) {
      $query_conditions = $this->db->db_or();
      $query_conditions = $query_conditions->condition('INACTIVE_AFTER', null);
      $query_conditions = $query_conditions->condition('INACTIVE_AFTER', date('Y-m-d'), '>=');
    }
    $result = $this->db->db_select('CORE_LOOKUP_VALUES')
      ->fields('CORE_LOOKUP_VALUES', array('CODE','DESCRIPTION'))
      ->join('CORE_LOOKUP_TABLES', 'CORE_LOOKUP_TABLES', 'CORE_LOOKUP_VALUES.LOOKUP_TABLE_ID = CORE_LOOKUP_TABLES.LOOKUP_TABLE_ID')
      ->condition('LOOKUP_TABLE_NAME', $lookup_id)
      ->condition('CODE', $value);
    if ($all_values === false) {
      $result = $result->condition($query_conditions);
    }
    $result = $result->orderBy('SORT', 'ASC')
      ->orderBy('CODE', 'ASC')
      ->execute();
    $row = $result->fetch();
      
      if ($description == 'C')
        return $row['CODE'];
      elseif ($description == 'CD')
        return $row['CODE'].' - '.$row['DESCRIPTION'];
      else
        return $row['DESCRIPTION'];
  }
  
  public function __sleep() {
    $this->db = null;
    
    return array('lookup');
  }
  
}