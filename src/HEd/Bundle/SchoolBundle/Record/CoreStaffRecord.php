<?php

namespace Kula\HEd\Bundle\SchoolBundle\Record;

use Kula\Core\Component\Record\Record;

class CoreStaffRecord extends Record {
  
  public function getSelectedRecordBarTemplate() {
    
  }
  
  public function getRecordBarTemplate() {
    return 'KulaHEdSchoolBundle::CoreRecord/record_staff.html.twig';
  }
  
  public function getFromDifferentType($record_type, $record_id) {
    if ($record_type == 'Core.HEd.Staff.SchoolTerm') {
      $result = $this->db()->db_select('STUD_STAFF_ORGANIZATION_TERMS')
        ->fields('STUD_STAFF_ORGANIZATION_TERMS', array('STAFF_ID'))
        ->condition('STAFF_ORGANIZATION_TERM_ID', $record_id);
      $result = $result->execute()->fetch();
      return $result['STAFF_ID'];
    }
  }
  
  public function getRecordIDStack() {
    
    $result = $this->db()->db_select('STUD_STAFF', 'staff')
    ->distinct()
    ->fields('staff', array('STAFF_ID' => 'ID'))
    ->join('CONS_CONSTITUENT', 'constituent', 'constituent.CONSTITUENT_ID = staff.STAFF_ID')
    ->join('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgterms', 'stafforgterms.STAFF_ID = staff.STAFF_ID')
    ->condition('stafforgterms.ORGANIZATION_TERM_ID', $this->focus->getOrganizationTermIDs())
    ->orderBy('LAST_NAME', 'ASC')
    ->orderBy('FIRST_NAME', 'ASC');
    $result = $result->execute()->fetchAll();
    
    return $result;    
  }
  
  public function get($record_id) {
    
    $result = $this->db()->db_select('STUD_STAFF', 'staff')
    ->fields('staff', array('STAFF_ID', 'ABBREVIATED_NAME', 'DEPARTMENT', 'DEPARTMENT_HEAD'))
    ->join('CONS_CONSTITUENT', 'constituent', 'constituent.CONSTITUENT_ID = staff.STAFF_ID')
    ->fields('constituent', array('LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME'))
    ->condition('staff.STAFF_ID', $record_id)
    ->execute()->fetch();
    return $result;
    
  }
  
  public function getBaseTable() {
    return 'HEd.Staff';
  }
  
  public function getBaseKeyFieldName() {
    return 'HEd.Staff.ID';
  }
  
  public function modifySearchDBOBject($db_obj) {
    $db_obj = $db_obj->join('CONS_CONSTITUENT', null, 'STUD_STAFF.STAFF_ID = CONS_CONSTITUENT.CONSTITUENT_ID');
    $db_obj = $db_obj->join('STUD_STAFF_ORGANIZATION_TERMS', null, 'STUD_STAFF.STAFF_ID = STUD_STAFF_ORGANIZATION_TERMS.STAFF_ID');
    $db_obj = $db_obj->condition('ORGANIZATION_TERM_ID', $this->focus->getOrganizationTermIDs());
    $db_obj = $db_obj->distinct();
    $db_obj = $db_obj->orderBy('LAST_NAME', 'ASC');
    $db_obj = $db_obj->orderBy('FIRST_NAME', 'ASC');

    return $db_obj;
  }
  
}