<?php

namespace Kula\Core\Bundle\ConstituentBundle\Record;

use Kula\Core\Component\Record\Record;

class ConstituentRecord extends Record {
  
  public function getSelectedRecordBarTemplate() {
  }
  
  public function getRecordBarTemplate() {
    return 'KulaCoreConstituentBundle::Record/record_constituent.html.twig';
  }
  
  public function getFromDifferentType($record_type, $record_id) {
    if ($record_type == 'SIS.HEd.Student.Status') {
      $result = $this->db()->db_select('STUD_STUDENT_STATUS')
        ->fields('STUD_STUDENT_STATUS', array('STUDENT_ID'))
        ->condition('STUDENT_STATUS_ID', $record_id);
      $result = $result->execute()->fetch();
      return $result['STUDENT_ID'];
    }
    if ($record_type == 'SIS.HEd.Student') {
      return $record_id;
    }
  }
  
  public function getRecordIDStack() {
    
    $result = $this->db()->db_select('CONS_CONSTITUENT', 'constituent')
    ->fields('constituent', array('CONSTITUENT_ID' => 'ID'))
    ->orderBy('LAST_NAME')
    ->orderBy('FIRST_NAME')
    ->orderBy('MIDDLE_NAME')
    ->execute()->fetchAll();
    return $result;  
  }
  
  public function get($record_id) {
    
    $result = $this->db()->db_select('CONS_CONSTITUENT', 'cons')
      ->fields('cons')
      ->condition('CONSTITUENT_ID', $record_id)
      ->execute()->fetch();
    return $result;
  }

  public function getBaseTable() {
    return 'Core.Constituent';
  }
  
  public function getBaseKeyFieldName() {
    return 'Core.Constituent.ID';
  }
  
  public function modifySearchDBOBject($db_obj) {
    $db_obj =  $db_obj->orderBy('LAST_NAME', 'ASC');
    $db_obj =  $db_obj->orderBy('FIRST_NAME', 'ASC');
    return $db_obj;
  }
  
}