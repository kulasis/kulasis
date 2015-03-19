<?php

namespace Kula\K12\Bundle\SchedulingBundle\Chooser;

use Kula\Core\Component\Chooser\Chooser;

class RoomChooser extends Chooser {
  
  public function search($q) {
    
    $query_conditions = $this->db()->db_and();
    $query_conditions = $query_conditions->condition('room.ORGANIZATION_TERM_ID', $this->focus()->getOrganizationTermIDs());
    
    $query_conditions_or = $this->db()->db_or();
    $query_conditions_or = $query_conditions_or->condition('ROOM_NAME', $q.'%', 'LIKE');
    $query_conditions_or = $query_conditions_or->condition('ROOM_NUMBER', $q.'%', 'LIKE');
    
    $query_conditions = $query_conditions->condition($query_conditions_or);
    
    $data = array();
    
    $search = $this->db()->db_select('STUD_ROOM', 'room')
      ->fields('room', array('ROOM_ID', 'BUILDING', 'ROOM_NAME', 'ROOM_NUMBER'))
      ->condition($query_conditions)
      ->orderBy('ROOM_NUMBER', 'ASC');
    $search = $search->execute();
    while ($row = $search->fetch()) {
      $this->addToChooserMenu($row['ROOM_ID'], $row['BUILDING'].' '.$row['ROOM_NUMBER'].' '.$row['ROOM_NAME']);
    }
    
  }
  
  public function choice($id) {
    $row = $this->db()->db_select('STUD_ROOM', 'room')
      ->fields('room', array('ROOM_ID', 'BUILDING', 'ROOM_NAME', 'ROOM_NUMBER'))
      ->condition('room.ROOM_ID', $id)
      ->execute()
      ->fetch();
    return $this->currentValue($row['ROOM_ID'], $row['BUILDING'].' '.$row['ROOM_NUMBER'].' / '.$row['ROOM_NAME']);
  }
  
  public function searchRoute() {
    return 'sis_K12_room_chooser';
  }
  
}