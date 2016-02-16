<?php

namespace Kula\HEd\Bundle\SchedulingBundle\Record;

use Kula\Core\Component\Record\Record;

class TeacherSectionRecord extends Record {
  
  public function getSelectedRecordBarTemplate() {
    return 'KulaHEdSchedulingBundle::TeacherRecord/selected_teacher_record_section.html.twig';
  }
  
  public function getRecordBarTemplate() {
    return 'KulaHEdSchedulingBundle::TeacherRecord/teacher_record_section.html.twig';
  }
  
  public function get($record_id) {

    $result = $this->db()->db_select('STUD_SECTION', 'section')
    ->fields('section', array('SECTION_ID', 'SECTION_NUMBER', 'STAFF_ORGANIZATION_TERM_ID', 'COURSE_ID', 'ORGANIZATION_TERM_ID', 'STATUS'))
    ->leftJoin('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgtrm', 'stafforgtrm.STAFF_ORGANIZATION_TERM_ID = section.STAFF_ORGANIZATION_TERM_ID')
    ->leftJoin('STUD_STAFF', 'staff', 'stafforgtrm.STAFF_ID = staff.STAFF_ID')
    ->fields('staff', array('ABBREVIATED_NAME', 'STAFF_ID'))
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
  
  public function getAllSchools($staff_id) {

    $statuses = array();

    $statuses_result = $this->db()->db_select('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgterms')
      ->distinct()
      ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = stafforgterms.ORGANIZATION_TERM_ID')
      ->join('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
      ->fields('org', array('ORGANIZATION_ID', 'ORGANIZATION_NAME'))
      ->condition('stafforgterms.staff_id', $staff_id)
      ->orderBy('ORGANIZATION_NAME')
      ->execute();
    while ($statuses_row = $statuses_result->fetch()) {
      $statuses[$statuses_row['ORGANIZATION_ID']] = $statuses_row['ORGANIZATION_NAME'];
    }

    return $statuses;
  }
  
  public function getAllTermsForSchool($staff_id) {

    $statuses = array();

    $statuses_result = $this->db()->db_select('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgterms')
      ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = stafforgterms.ORGANIZATION_TERM_ID')
      ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
      ->fields('term', array('TERM_ID', 'TERM_ABBREVIATION'))
      ->condition('orgterms.ORGANIZATION_ID', $this->session->getFocus('organization_id'))
      ->condition('stafforgterms.STAFF_ID', $staff_id)
      ->orderBy('START_DATE', 'ASC', 'term')
      ->execute();
    while ($statuses_row = $statuses_result->fetch()) {
      $statuses[$statuses_row['TERM_ID']] = $statuses_row['TERM_ABBREVIATION'];
    }
    
    return $statuses;
  }
  
}