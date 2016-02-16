<?php

namespace Kula\Core\Bundle\ConstituentBundle\Chooser;

use Kula\Core\Component\Chooser\Chooser;

class ConstituentChooser extends Chooser {
  
  public function search($q) {
    
    $query_conditions = $this->db()->db_or()
      ->condition('CONSTITUENT_ID', $q)
      ->condition('LAST_NAME', $q.'%', 'LIKE')
      ->condition('FIRST_NAME', $q.'%', 'LIKE')
      ->condition('PERMANENT_NUMBER', $q.'%', 'LIKE');
    
    $data = array();
    
    $search = $this->db()->db_select('CONS_CONSTITUENT')
      ->fields('CONS_CONSTITUENT')
      ->condition($query_conditions)
      ->orderBy('LAST_NAME', 'ASC')
      ->orderBy('FIRST_NAME', 'ASC')
      ->execute();
    while ($row = $search->fetch()) {
      $this->addToChooserMenu($row['CONSTITUENT_ID'], $row['CONSTITUENT_ID'].' | '.$row['LAST_NAME'].', '.$row['FIRST_NAME'].' | '.$row['GENDER'].' | '.$row['PERMANENT_NUMBER']);
    }
    
  }
  
  public function choice($id) {
  }
  
  public function searchRoute() {
    return 'Core_Constituent_Chooser';
  }
  
}