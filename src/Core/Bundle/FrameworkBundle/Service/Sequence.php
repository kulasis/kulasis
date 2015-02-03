<?php

namespace Kula\Core\Bundle\FrameworkBundle\Service;

class Sequence {
  
  protected $db;
  
  public function __construct(\Kula\Core\Component\DB\DB $db) {
    $this->db = $db;
  }
  
  public function getNextSequenceForKey($key) {
    
    $connect = $this->db->db_transaction();
    
    $student_number = $this->db->db_select('CORE_SEQUENCE')->fields('CORE_SEQUENCE')->condition('SEQUENCE_KEY', $key)->execute()->fetch();
    $next_number = $student_number['NEXT_NUMBER'];
    
    // Move to next number
    $new_number = str_pad($next_number + 1, 6, '0', STR_PAD_LEFT);
    $this->db->db_update('CORE_SEQUENCE')
      ->fields(array('NEXT_NUMBER' => $new_number))
      ->condition('SEQUENCE_KEY', $key)
      ->execute();
    
    $connect->commit();
    
    return $next_number;
  }
  
  
}