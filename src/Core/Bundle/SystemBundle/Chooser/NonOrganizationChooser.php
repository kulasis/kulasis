<?php

namespace Kula\Core\Bundle\SystemBundle\Chooser;

use Kula\Core\Component\Chooser\Chooser;

class NonOrganizationChooser extends Chooser {
  
  public function search($q) {
    
    $query_conditions = $this->db()->db_or();
    $query_conditions = $query_conditions->condition('NON_ORGANIZATION_NAME', $q.'%', 'LIKE');
    
    $data = array();
    
    $search = $this->db()->db_select('CORE_NON_ORGANIZATION', 'nonorganization')
      ->fields('nonorganization', array('NON_ORGANIZATION_ID', 'NON_ORGANIZATION_NAME'))
      ->condition($query_conditions)
      ->orderBy('NON_ORGANIZATION_NAME', 'ASC');
    $search = $search  ->execute();
    while ($row = $search->fetch()) {
      $this->addToChooserMenu($row['NON_ORGANIZATION_ID'], $row['NON_ORGANIZATION_NAME']);
    }
    
  }
  
  public function choice($id) {
    $row = $this->db()->db_select('CORE_NON_ORGANIZATION', 'nonorganization')
      ->fields('nonorganization', array('NON_ORGANIZATION_ID', 'NON_ORGANIZATION_NAME'))
      ->condition('NON_ORGANIZATION_ID', $id)
      ->execute()
      ->fetch();
    return $this->currentValue($row['NON_ORGANIZATION_ID'], $row['NON_ORGANIZATION_NAME']);
  }
  
  public function searchRoute() {
    return 'Core_System_NonOrganization_Chooser';
  }
  
}