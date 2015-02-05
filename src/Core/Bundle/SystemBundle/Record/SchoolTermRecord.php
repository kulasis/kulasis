<?php

namespace Kula\Core\Bundle\SystemBundle\Record;

use Kula\Core\Component\Record\Record;
use Kula\Core\Component\Record\RecordDelegateInterface;

class SchoolTermRecord extends Record implements RecordDelegateInterface {
  
  public function getSelectedRecordBarTemplate() {
    
  }
  
  public function getRecordBarTemplate() {
    return 'KulaCoreSystemBundle::Record/record_school_term.html.twig';
  }
  
  public function getRecordIDStack() {
    
    $result = $this->db->db_select('CORE_ORGANIZATION_TERMS', 'CORE_ORGANIZATION_TERMS')
      ->fields('CORE_ORGANIZATION_TERMS', array('ORGANIZATION_TERM_ID' => 'ID'))
      ->join('CORE_ORGANIZATION', 'CORE_ORGANIZATION', 'CORE_ORGANIZATION.ORGANIZATION_ID = CORE_ORGANIZATION_TERMS.ORGANIZATION_ID')
      ->condition('ORGANIZATION_TYPE', 'S')
      ->condition('CORE_ORGANIZATION_TERMS.ORGANIZATION_ID', $this->focus->getSchoolIDs());
    
    if ($this->focus->getTermID())
      $result = $result->condition('TERM_ID', $this->focus->getTermID());
    
    $result = $result->orderBy('ORGANIZATION_NAME', 'ASC')
    ->execute()->fetchAll();
    
    return $result;    
    
  }
  
  public function get($record_id) {
    $result = $this->db->db_select('CORE_ORGANIZATION_TERMS', 'CORE_ORGANIZATION_TERMS')
      ->fields('CORE_ORGANIZATION_TERMS', array('ORGANIZATION_TERM_ID', 'ORGANIZATION_ID', 'TERM_ID'))
      ->join('CORE_ORGANIZATION', 'CORE_ORGANIZATION', 'CORE_ORGANIZATION.ORGANIZATION_ID = CORE_ORGANIZATION_TERMS.ORGANIZATION_ID')
      ->condition('ORGANIZATION_TERM_ID', $record_id)
      ->execute()->fetch();
    return $result;
  }
  
  public function getBaseTable() {
    return 'Core.Organization.Term';
  }
  
  public function getBaseKeyFieldName() {
    return 'Core.Organization.Term.ID';
  }
  
  public function modifySearchDBOBject($db_obj) {
    $db_obj =  $db_obj->join('CORE_ORGANIZATION', 'CORE_ORGANIZATION', 'CORE_ORGANIZATION_TERMS.ORGANIZATION_ID = CORE_ORGANIZATION.ORGANIZATION_ID');
    $db_obj = $db_obj->condition('ORGANIZATION_TYPE', 'SCHL');
    if ($this->focus->getTermID())
      $db_obj = $db_obj->condition('CORE_ORGANIZATION_TERMS.TERM_ID', $this->focus->getTermID());
    $db_obj =  $db_obj->orderBy('ORGANIZATION_NAME', 'ASC', 'CORE_ORGANIZATION');
    
    return $db_obj;
  }
  
}