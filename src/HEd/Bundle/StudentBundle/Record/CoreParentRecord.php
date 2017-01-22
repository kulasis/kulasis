<?php

namespace Kula\HEd\Bundle\StudentBundle\Record;

use Kula\Core\Component\Record\Record;

class CoreParentRecord extends Record {
  
  public function getSelectedRecordBarTemplate() {
    //return 'KulaHEdStudentBundle::CoreRecord/selected_record_parent.html.twig';
  }
  
  public function getRecordBarTemplate() {
    return 'KulaHEdStudentBundle::CoreRecord/record_parent.html.twig';
  }
  
  public function getRecordIDStack() {
    
    $result = $this->db()->db_select('STUD_PARENT', 'par')
    ->distinct()
    ->fields('par', array('PARENT_ID' => 'ID'))
    ->join('CONS_CONSTITUENT', 'constituent', 'constituent.CONSTITUENT_ID = par.PARENT_ID')
    ->fields('constituent', array('LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'GENDER'))
    ->join('CONS_RELATIONSHIP', 'rel', 'rel.RELATED_CONSTITUENT_ID = par.PARENT_ID')
    ->join('STUD_STUDENT_STATUS', 'stustatus', 'stustatus.STUDENT_ID = rel.CONSTITUENT_ID')
    ->join('CORE_ORGANIZATION_TERMS', 'orgterm', 'stustatus.ORGANIZATION_TERM_ID = orgterm.ORGANIZATION_TERM_ID')
    ->condition('ORGANIZATION_ID', $this->focus->getSchoolIDs());
    if ($this->focus->getTermID())
      $result = $result->condition('TERM_ID', $this->focus->getTermID());
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
    return 'HEd.Parent';
  }
  
  public function getBaseKeyFieldName() {
    return 'HEd.Parent.ID';
  }
  
  public function modifySearchDBOBject($db_obj) {
    $db_obj =  $db_obj->join('CONS_CONSTITUENT', null, 'CONS_CONSTITUENT.CONSTITUENT_ID = STUD_PARENT.PARENT_ID');
    $db_obj =  $db_obj->join('CONS_RELATIONSHIP', 'rel', 'rel.RELATED_CONSTITUENT_ID = STUD_PARENT.PARENT_ID')
    ->join('STUD_STUDENT_STATUS', 'stustatus', 'stustatus.STUDENT_ID = rel.CONSTITUENT_ID')
    ->join('CORE_ORGANIZATION_TERMS', 'orgterm', 'stustatus.ORGANIZATION_TERM_ID = orgterm.ORGANIZATION_TERM_ID')
    ->condition('ORGANIZATION_ID', $this->focus->getSchoolIDs());
    if ($this->focus->getTermID())
      $db_obj = $db_obj->condition('TERM_ID', $this->focus->getTermID());
    $db_obj =  $db_obj->orderBy('LAST_NAME', 'ASC');
    $db_obj =  $db_obj->orderBy('FIRST_NAME', 'ASC');
    return $db_obj;
  }
  
}