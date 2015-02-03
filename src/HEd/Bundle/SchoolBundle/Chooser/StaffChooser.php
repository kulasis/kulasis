<?php

namespace Kula\HEd\Bundle\SchoolBundle\Chooser;

use Kula\Core\Component\Chooser\Chooser;

class StaffChooser extends Chooser {
  
  public function search($q) {

    $data = array();
    
    $search = $this->db()->db_select('STUD_STAFF', 'staff')
      ->fields('staff', array('STAFF_ID', 'ABBREVIATED_NAME'))
      ->condition('ABBREVIATED_NAME', $q.'%', 'LIKE')
      ->orderBy('ABBREVIATED_NAME', 'ASC');
    $search = $search->execute();
    while ($row = $search->fetch()) {
      $this->addToChooserMenu($row['STAFF_ID'], $row['ABBREVIATED_NAME']);
    }
    
  }
  
  public function choice($id) {
    $row = $this->db()->db_select('STUD_STAFF', 'staff')
      ->fields('staff', array('STAFF_ID', 'ABBREVIATED_NAME'))
      ->condition('staff.STAFF_ID', $id)
      ->execute()
      ->fetch();
    return $this->currentValue($row['STAFF_ID'], $row['ABBREVIATED_NAME']);
  }
  
  public function searchRoute() {
    return 'sis_HEd_school_staff_chooser';
  }
  
}