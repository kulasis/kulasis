<?php

namespace Kula\Core\Bundle\SystemBundle\Chooser;

use Kula\Core\Component\Chooser\Chooser;

class OrganizationTermChooser extends Chooser {
  
  public function search($q) {
    
    $query_conditions = $this->db()->db_or();
    $query_conditions = $query_conditions->condition('ORGANIZATION_ABBREVIATION', '%'.$q.'%', 'LIKE');
    $query_conditions = $query_conditions->condition('TERM_ABBREVIATION', '%'.$q.'%', 'LIKE');
    
    $data = array();
    
    $search = $this->db()->db_select('CORE_ORGANIZATION_TERMS', 'orgterm')
      ->fields('orgterm', array('ORGANIZATION_TERM_ID'))
      ->join('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = orgterm.ORGANIZATION_ID')
      ->fields('org', array('ORGANIZATION_ABBREVIATION'))
      ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterm.TERM_ID')
      ->fields('term', array('TERM_ABBREVIATION'))
      ->condition($query_conditions)
      ->orderBy('ORGANIZATION_NAME', 'ASC')
      ->orderBy('TERM_ABBREVIATION', 'ASC')
      ->execute();
    while ($row = $search->fetch()) {
      $this->addToChooserMenu($row['ORGANIZATION_TERM_ID'], $row['ORGANIZATION_ABBREVIATION'].' / '.$row['TERM_ABBREVIATION']);
    }
    
  }
  
  public function choice($id) {
    $row = $this->db()->db_select('CORE_ORGANIZATION_TERMS', 'orgterm')
      ->fields('orgterm', array('ORGANIZATION_TERM_ID'))
      ->join('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = orgterm.ORGANIZATION_ID')
      ->fields('org', array('ORGANIZATION_ABBREVIATION'))
      ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterm.TERM_ID')
      ->fields('term', array('TERM_ABBREVIATION'))
      ->condition('ORGANIZATION_TERM_ID', $id)
      ->execute()
      ->fetch();
    return $this->currentValue($row['ORGANIZATION_TERM_ID'], $row['ORGANIZATION_ABBREVIATION'].' / '.$row['TERM_ABBREVIATION']);
  }
  
  public function searchRoute() {
    return 'Core_System_Organization_OrgTerm_Chooser';
  }
  
}