<?php

namespace Kula\Bundle\HEd\SchedulingBundle\Record;

class SectionTeacherRecord extends \Kula\Component\Record\BaseRecord implements \Kula\Component\Record\RecordDelegateInterface {
	
	public function getSelectedRecordBarTemplate() {
		//return 'KulaHEdSchedulingBundle::Record/selected_record_section.html.twig';
	}
	
	public function getRecordBarTemplate() {
		return 'KulaHEdSchedulingBundle::Record/teacher_record_section.html.twig';
	}
	
	public function getRecordIDStack() {
		
		$result = $this->db()->select('STUD_SECTION', 'section')
	  ->fields('section', array('SECTION_ID' => 'ID'))
		->predicate('section.ORGANIZATION_TERM_ID', $this->focus->getOrganizationTermIDs())
		->order_by('SECTION_NUMBER', 'ASC');
		$result = $result->execute()->fetchAll();
		
		return $result;		
	}
	
	public function get($record_id) {
		
		$result = $this->db()->select('STUD_SECTION', 'section')
		->fields('section', array('SECTION_ID', 'SECTION_NUMBER', 'STAFF_ORGANIZATION_TERM_ID', 'COURSE_ID', 'ORGANIZATION_TERM_ID', 'STATUS'))
		->left_join('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgtrm', null, 'stafforgtrm.STAFF_ORGANIZATION_TERM_ID = section.STAFF_ORGANIZATION_TERM_ID')
		->left_join('STUD_STAFF', 'staff', array('ABBREVIATED_NAME'), 'stafforgtrm.STAFF_ID = staff.STAFF_ID')
		->join('CORE_ORGANIZATION_TERMS', 'orgterms', null, 'orgterms.ORGANIZATION_TERM_ID = section.ORGANIZATION_TERM_ID')
		->join('CORE_ORGANIZATION', 'org', array('ORGANIZATION_NAME'), 'orgterms.ORGANIZATION_ID = org.ORGANIZATION_ID')
		->join('CORE_TERM', 'term', array('TERM_ABBREVIATION'), 'term.TERM_ID = orgterms.TERM_ID')
		->predicate('section.SECTION_ID', $record_id)
	  ->execute()->fetch();
		return $result;
		
	}
	
	public function getBaseTable() {
		return 'STUD_SECTION';
	}
	
	public function getBaseKeyFieldName() {
		return 'SECTION_ID';
	}
	
	public function modifySearchDBOBject($db_obj) {
		//$db_obj =	$db_obj->join('STAF_STAFF', null, null, 'STAF_STAFF.STAFF_ID = STAF_STAFF_ORGANIZATION_TERMS.STAFF_ID');
		//$db_obj =	$db_obj->join('CONS_CONSTITUENT', null, null, 'STAF_STAFF.STAFF_ID = CONS_CONSTITUENT.CONSTITUENT_ID');
		$db_obj = $db_obj->predicate('ORGANIZATION_TERM_ID', $this->focus->getOrganizationTermIDs());
		//$db_obj =	$db_obj->order_by('LAST_NAME', 'ASC');
		$db_obj =	$db_obj->order_by('SECTION_NUMBER', 'ASC');

		return $db_obj;
	}
	
}