<?php

namespace Kula\HEd\Bundle\SchoolBundle\Record;

use Kula\Core\Component\Record\Record;

class SISStaffSchoolTermRecord extends Record {
  
  public function getSelectedRecordBarTemplate() {
    
  }
  
  public function getRecordBarTemplate() {
    return 'KulaHEdSchoolBundle::SISRecord/record_staff.html.twig';
  }
  
  public function getFromDifferentType($record_type, $record_id) {
    if ($record_type == 'SIS.HEd.Staff') {
      $result = $this->db()->db_select('STUD_STAFF_ORGANIZATION_TERMS')
        ->fields('STUD_STAFF_ORGANIZATION_TERMS', array('STAFF_ORGANIZATION_TERM_ID'))
        ->condition('STAFF_ID', $record_id)
        ->condition('ORGANIZATION_TERM_ID', $this->focus->getOrganizationTermIDs())
        ->execute()->fetch();
      return $result['STAFF_ORGANIZATION_TERM_ID'];
    }
  }
  
  public function getRecordIDStack() {
    
    $result = $this->db()->db_select('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgtrm')
    ->fields('stafforgtrm', array('STAFF_ORGANIZATION_TERM_ID' => 'ID'))
    ->join('STUD_STAFF', 'staff', 'stafforgtrm.STAFF_ID = staff.STAFF_ID')
    ->join('CONS_CONSTITUENT', 'constituent', 'constituent.CONSTITUENT_ID = staff.STAFF_ID')
    ->condition('stafforgtrm.ORGANIZATION_TERM_ID', $this->focus->getOrganizationTermIDs())
    ->orderBy('LAST_NAME', 'ASC')
    ->orderBy('FIRST_NAME', 'ASC');
    $result = $result->execute()->fetchAll();
    
    return $result;    
  }
  
  public function get($record_id) {
    
    $result = $this->db()->db_select('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgtrm')
    ->fields('stafforgtrm', array('STAFF_ORGANIZATION_TERM_ID', 'FTE'))
    ->join('STUD_STAFF', 'staff', 'stafforgtrm.STAFF_ID = staff.STAFF_ID')
    ->fields('staff', array('ABBREVIATED_NAME', 'STAFF_ID'))
    ->join('CONS_CONSTITUENT', 'constituent', 'constituent.CONSTITUENT_ID = staff.STAFF_ID')
    ->fields('constituent', array('LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME'))
    ->condition('stafforgtrm.STAFF_ORGANIZATION_TERM_ID', $record_id)
    ->execute()->fetch();
    return $result;
    
  }
  
  public function getBaseTable() {
    return 'HEd.Staff.OrganizationTerm';
  }
  
  public function getBaseKeyFieldName() {
    return 'HEd.Staff.OrganizationTerm.ID';
  }
  
  public function modifySearchDBOBject($db_obj) {
    $db_obj = $db_obj->join('STUD_STAFF', null, 'STUD_STAFF.STAFF_ID = STUD_STAFF_ORGANIZATION_TERMS.STAFF_ID');
    $db_obj = $db_obj->join('CONS_CONSTITUENT', null, 'STUD_STAFF.STAFF_ID = CONS_CONSTITUENT.CONSTITUENT_ID');
    $db_obj = $db_obj->condition('ORGANIZATION_TERM_ID', $this->focus->getOrganizationTermIDs());
    $db_obj = $db_obj->orderBy('LAST_NAME', 'ASC');
    $db_obj = $db_obj->orderBy('FIRST_NAME', 'ASC');

    return $db_obj;
  }
  
}