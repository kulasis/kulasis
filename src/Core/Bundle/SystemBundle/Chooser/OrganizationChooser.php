<?php

namespace Kula\Core\Bundle\SystemBundle\Chooser;

use Kula\Core\Component\Chooser\Chooser;

class OrganizationChooser extends Chooser {
  
  public function search($q) {
    
    $query_conditions = $this->db()->db_or();
    $query_conditions = $query_conditions->condition('ORGANIZATION_NAME', '%'.$q.'%', 'LIKE');
    
    $data = array();
    
    $search = $this->db()->db_select('CORE_ORGANIZATION')
      ->fields('CORE_ORGANIZATION', array('ORGANIZATION_ID', 'ORGANIZATION_NAME'))
      ->condition($query_conditions)
      ->orderBy('ORGANIZATION_NAME', 'ASC')
      ->execute();
    while ($row = $search->fetch()) {
      $this->addToChooserMenu($row['ORGANIZATION_ID'], $row['ORGANIZATION_NAME']);
    }
    
  }
  
  public function choice($id) {
    $row = $this->db()->db_select('CORE_ORGANIZATION')
      ->fields('CORE_ORGANIZATION', array('ORGANIZATION_ID', 'ORGANIZATION_NAME'))
      ->condition('ORGANIZATION_ID', $id)
      ->execute()
      ->fetch();
    return $this->currentValue($row['ORGANIZATION_ID'], $row['ORGANIZATION_NAME']);
  }
  
  public function searchRoute() {
    return 'Core_System_Organization_Chooser';
  }
  
}