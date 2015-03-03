<?php

namespace Kula\K12\Bundle\StudentBundle\Record;

use Kula\Core\Component\Record\Record;

class SISParentRecord extends Record {
  
  public function getSelectedRecordBarTemplate() {
    //return 'KulaK12StudentBundle::SISRecord/selected_record_parent.html.twig';
  }
  
  public function getRecordBarTemplate() {
    return 'KulaK12StudentBundle::SISRecord/record_parent.html.twig';
  }
  
  public function getRecordIDStack() {
    
    $result = $this->db()->db_select('STUD_PARENT', 'par')
    ->distinct()
    ->fields('par', array('PARENT_ID' => 'ID'))
    ->join('CONS_CONSTITUENT', 'constituent', 'constituent.CONSTITUENT_ID = par.PARENT_ID')
    ->fields('constituent', array('LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'GENDER'));
    $result = $result->orderBy('LAST_NAME')
      ->orderBy('FIRST_NAME')
      ->orderBy('MIDDLE_NAME')
      ->execute()->fetchAll();
    return $result;  
  }
  
  public function get($record_id) {
    
    $result = $this->db()->db_select('STUD_PARENT', 'par')
      ->fields('par', array('PARENT_ID'))
      ->join('CONS_CONSTITUENT', 'constituent', 'constituent.CONSTITUENT_ID = par.PARENT_ID')
      ->fields('constituent', array('PERMANENT_NUMBER', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'GENDER', 'RACE', 'MAIDEN_NAME', 'PREFERRED_NAME'));
    $result = $result->condition('par.PARENT_ID', $record_id);
    $result = $result->execute()->fetch();
    
    return $result;
  }
  
  public function getBaseTable() {
    return 'K12.Parent';
  }
  
  public function getBaseKeyFieldName() {
    return 'K12.Parent.ID';
  }
  
  public function modifySearchDBOBject($db_obj) {
    $db_obj =  $db_obj->join('CONS_CONSTITUENT', null, 'CONS_CONSTITUENT.CONSTITUENT_ID = STUD_PARENT.PARENT_ID');
    $db_obj =  $db_obj->orderBy('LAST_NAME', 'ASC');
    $db_obj =  $db_obj->orderBy('FIRST_NAME', 'ASC');
    return $db_obj;
  }
  
}