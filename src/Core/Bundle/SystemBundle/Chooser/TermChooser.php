<?php

namespace Kula\Core\Bundle\SystemBundle\Chooser;

use Kula\Core\Component\Chooser\Chooser;

class TermChooser extends Chooser {
  
  public function search($q) {

    $data = array();
    
    $search = $this->db()->db_select('CORE_TERM', 'CORE_TERM')
      ->fields('CORE_TERM', array('TERM_ID', 'TERM_NAME', 'TERM_ABBREVIATION'))
      ->condition($this->db()->db_or()->condition('TERM_NAME', '%'.$q.'%', 'LIKE')->condition('TERM_ABBREVIATION', '%'.$q.'%', 'LIKE'))
      ->orderBy('TERM_NAME', 'ASC')
      ->orderBy('TERM_ABBREVIATION', 'ASC')
      ->execute();
    while ($row = $search->fetch()) {
      $this->addToChooserMenu($row['TERM_ID'], $row['TERM_NAME'].' - '.$row['TERM_ABBREVIATION']);
    }
    
  }
  
  public function choice($id) {
    $row = $this->db()->db_select('CORE_TERM', 'CORE_TERM')
      ->fields('CORE_TERM', array('TERM_ID', 'TERM_NAME', 'TERM_ABBREVIATION'))
      ->condition('TERM_ID', $id)
      ->execute()
      ->fetch();
    return $this->currentValue($row['TERM_ID'], $row['TERM_NAME'].' - '.$row['TERM_ABBREVIATION']);
  }
  
  public function searchRoute() {
    return 'Core_System_Terms_Chooser';
  }
  
}