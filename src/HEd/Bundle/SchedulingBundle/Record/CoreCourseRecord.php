<?php

namespace Kula\HEd\Bundle\SchedulingBundle\Record;

use Kula\Core\Component\Record\Record;

class CoreCourseRecord extends Record {
  
  public function getSelectedRecordBarTemplate() {
    
  }
  
  public function getRecordBarTemplate() {
    return 'KulaHEdSchedulingBundle::CoreRecord/record_course.html.twig';
  }
  
  public function getRecordIDStack() {
    
    $result = $this->db()->db_select('STUD_COURSE', 'course')
    ->fields('course', array('COURSE_ID' => 'ID'))
    ->orderBy('COURSE_NUMBER', 'ASC');
    $result = $result->execute()->fetchAll();
    
    return $result;    
  }
  
  public function get($record_id) {
    
    $result = $this->db()->db_select('STUD_COURSE', 'course')
    ->fields('course', array('COURSE_ID', 'COURSE_NUMBER', 'COURSE_TITLE', 'SHORT_TITLE', 'CONV_COURSE_NUMBER', 'COURSE_TYPE', 'CREDITS', 'DEPARTMENT', 'LEVEL', 'MARK_SCALE_ID'))
    ->condition('course.COURSE_ID', $record_id)
    ->execute()->fetch();
    return $result;
    
  }
  
  public function getBaseTable() {
    return 'HEd.Course';
  }
  
  public function getBaseKeyFieldName() {
    return 'HEd.Course.ID';
  }
  
  public function modifySearchDBOBject($db_obj) {
    //$db_obj =  $db_obj->join('STAF_STAFF', null, null, 'STAF_STAFF.STAFF_ID = STAF_STAFF_ORGANIZATION_TERMS.STAFF_ID');
    //$db_obj =  $db_obj->join('CONS_CONSTITUENT', null, null, 'STAF_STAFF.STAFF_ID = CONS_CONSTITUENT.CONSTITUENT_ID');
    //$db_obj = $db_obj->predicate('ORGANIZATION_TERM_ID', $this->session->get('organization_term_ids'));
    //$db_obj =  $db_obj->order_by('LAST_NAME', 'ASC');
    $db_obj =  $db_obj->orderBy('COURSE_NUMBER', 'ASC');

    return $db_obj;
  }
  
}