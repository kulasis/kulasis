<?php

namespace Kula\Bundle\HEd\OfferingBundle\Record;

class CourseRecord extends \Kula\Component\Record\BaseRecord implements \Kula\Component\Record\RecordDelegateInterface {
	
	public function getSelectedRecordBarTemplate() {
		
	}
	
	public function getRecordBarTemplate() {
		return 'KulaHEdOfferingBundle::Record/record_course.html.twig';
	}
	
	public function getRecordIDStack() {
		
		$result = $this->db()->select('STUD_COURSE', 'course')
	  ->fields('course', array('COURSE_ID' => 'ID'))
		->order_by('COURSE_NUMBER', 'ASC');
		$result = $result->execute()->fetchAll();
		
		return $result;		
	}
	
	public function get($record_id) {
		
		$result = $this->db()->select('STUD_COURSE', 'course')
		->fields('course', array('COURSE_ID', 'COURSE_NUMBER', 'COURSE_TITLE', 'SHORT_TITLE', 'CONVERSION', 'COURSE_TYPE', 'CREDITS', 'DEPARTMENT', 'LEVEL', 'MARK_SCALE_ID'))
		->predicate('course.COURSE_ID', $record_id)
	  ->execute()->fetch();
		return $result;
		
	}
	
	public function getBaseTable() {
		return 'STUD_COURSE';
	}
	
	public function getBaseKeyFieldName() {
		return 'COURSE_ID';
	}
	
	public function modifySearchDBOBject($db_obj) {
		//$db_obj =	$db_obj->join('STAF_STAFF', null, null, 'STAF_STAFF.STAFF_ID = STAF_STAFF_ORGANIZATION_TERMS.STAFF_ID');
		//$db_obj =	$db_obj->join('CONS_CONSTITUENT', null, null, 'STAF_STAFF.STAFF_ID = CONS_CONSTITUENT.CONSTITUENT_ID');
		//$db_obj = $db_obj->predicate('ORGANIZATION_TERM_ID', $this->session->get('organization_term_ids'));
		//$db_obj =	$db_obj->order_by('LAST_NAME', 'ASC');
		$db_obj =	$db_obj->order_by('COURSE_NUMBER', 'ASC');

		return $db_obj;
	}
	
}