<?php

namespace Kula\HEd\Bundle\SchedulingBundle\Record;

use Kula\Core\Component\Record\Record;

class SectionRecord extends Record {
  
  public function getSelectedRecordBarTemplate() {
    return 'KulaHEdSchedulingBundle::Record/selected_record_section.html.twig';
  }
  
  public function getRecordBarTemplate() {
    return 'KulaHEdSchedulingBundle::Record/record_section.html.twig';
  }
  
  public function getRecordBarTeacherTemplate() {
    return 'KulaHEdSchedulingBundle::Record/teacher_record_section.html.twig';
  }
  
  public function getRecordIDStack() {
    
    $result = $this->db()->db_select('STUD_SECTION', 'section')
    ->fields('section', array('SECTION_ID' => 'ID'))
    ->condition('section.ORGANIZATION_TERM_ID', $this->focus->getOrganizationTermIDs())
    ->orderBy('SECTION_NUMBER', 'ASC');
    $result = $result->execute()->fetchAll();
    
    return $result;    
  }
  
  public function get($record_id) {
    
    $result = $this->db()->db_select('STUD_SECTION', 'section')
    ->fields('section', array('SECTION_ID', 'SECTION_NUMBER', 'STAFF_ORGANIZATION_TERM_ID', 'COURSE_ID', 'ORGANIZATION_TERM_ID', 'STATUS'))
    ->leftJoin('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgtrm', 'stafforgtrm.STAFF_ORGANIZATION_TERM_ID = section.STAFF_ORGANIZATION_TERM_ID')
    ->leftJoin('STUD_STAFF', 'staff', 'stafforgtrm.STAFF_ID = staff.STAFF_ID')
    ->fields('staff', array('ABBREVIATED_NAME'))
    ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = section.ORGANIZATION_TERM_ID')
    ->join('CORE_ORGANIZATION', 'org', 'orgterms.ORGANIZATION_ID = org.ORGANIZATION_ID')
    ->fields('org', array('ORGANIZATION_NAME'))
    ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
    ->fields('term', array('TERM_ABBREVIATION'))
    ->condition('section.SECTION_ID', $record_id)
    ->execute()->fetch();
    return $result;
    
  }
  
  public function getBaseTable() {
    return 'HEd.Section';
  }
  
  public function getBaseKeyFieldName() {
    return 'HEd.Section.ID';
  }
  
  public function modifySearchDBOBject($db_obj) {
    //$db_obj =  $db_obj->join('STAF_STAFF', null, null, 'STAF_STAFF.STAFF_ID = STAF_STAFF_ORGANIZATION_TERMS.STAFF_ID');
    //$db_obj =  $db_obj->join('CONS_CONSTITUENT', null, null, 'STAF_STAFF.STAFF_ID = CONS_CONSTITUENT.CONSTITUENT_ID');
    $db_obj = $db_obj->condition('ORGANIZATION_TERM_ID', $this->focus->getOrganizationTermIDs());
    //$db_obj =  $db_obj->order_by('LAST_NAME', 'ASC');
    $db_obj =  $db_obj->orderBy('SECTION_NUMBER', 'ASC');

    return $db_obj;
  }
  
}