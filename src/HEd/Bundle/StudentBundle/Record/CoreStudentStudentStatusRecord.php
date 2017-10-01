<?php

namespace Kula\Bundle\HEd\StudentBundle\Record;

class StudentStudentStatusRecord extends \Kula\Component\Record\BaseRecord implements \Kula\Component\Record\RecordDelegateInterface {
	
	public function getSelectedRecordBarTemplate() {
		return 'KulaHEdStudentBundle::CoreRecord/student_selected_record_student_status.html.twig';
	}
	
	public function getRecordBarTemplate() {
		return 'KulaHEdStudentBundle::CoreRecord/student_record_student_status.html.twig';
	}
	
	public function getFromDifferentType($record_type, $record_id) {
		if ($record_type == 'STUDENT') {
			$result = $this->db()->select('STUD_STUDENT_STATUS')
				->fields(null, array('STUDENT_STATUS_ID'))
				->join('CORE_ORGANIZATION_TERMS', 'orgterm', null, 'STUD_STUDENT_STATUS.ORGANIZATION_TERM_ID = orgterm.ORGANIZATION_TERM_ID')
				->predicate('STUDENT_ID', $record_id)
			  ->predicate('ORGANIZATION_ID', $this->focus->getSchoolIDs());
			if ($this->focus->getTermID())
				$result = $result->predicate('TERM_ID', $this->focus->getTermID());
			$result = $result->execute()->fetch();
			return $result['STUDENT_STATUS_ID'];
		}
	}
	
	public function getRecordIDStack() {
		
		$result = $this->db()->select('STUD_STUDENT_STATUS')
	  ->fields(null, array('STUDENT_STATUS_ID' => 'ID'))
		->join('CORE_ORGANIZATION_TERMS', 'orgterm', null, 'STUD_STUDENT_STATUS.ORGANIZATION_TERM_ID = orgterm.ORGANIZATION_TERM_ID')
		->join('CONS_CONSTITUENT', 'constituent', array('LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'GENDER'), 'constituent.CONSTITUENT_ID = STUD_STUDENT_STATUS.STUDENT_ID')
		->join('CORE_TERM', 'term', null, 'orgterm.TERM_ID = term.TERM_ID')
		->predicate('ORGANIZATION_ID', $this->focus->getSchoolIDs());
		
		if ($this->focus->getTermID())
			$result = $result->predicate('orgterm.TERM_ID', $this->focus->getTermID());
		
		$result = $result->order_by('LAST_NAME')
			->order_by('FIRST_NAME')
			->order_by('MIDDLE_NAME')
			->order_by('START_DATE', 'ASC', 'term')
			->execute()->fetchAll();
		return $result;	
	}
	
	public function get($record_id) {
		
		$result = $this->db()->select('STUD_STUDENT_STATUS', 'STUD_STUDENT_STATUS')
			->fields('STUD_STUDENT_STATUS', array('STUDENT_STATUS_ID', 'STUDENT_ID', 'STATUS', 'GRADE', 'RESIDENT', 'ORGANIZATION_TERM_ID'))
			->join('CONS_CONSTITUENT', 'constituent', array('CONSTITUENT_ID', 'PERMANENT_NUMBER', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'IDENTIFIED_GENDER', 'GENDER', 'RACE', 'DEVELOPMENT_NUMBER'), 'constituent.CONSTITUENT_ID = STUD_STUDENT_STATUS.STUDENT_ID')
			->join('STUD_STUDENT', 'stu', array('STUDENT_ID', 'DIRECTORY_PERMISSION'), 'stu.STUDENT_ID = STUD_STUDENT_STATUS.STUDENT_ID')
			->join('CORE_ORGANIZATION_TERMS', 'orgterms', null, 'orgterms.ORGANIZATION_TERM_ID = STUD_STUDENT_STATUS.ORGANIZATION_TERM_ID')
			->join('CORE_ORGANIZATION', 'org', array('ORGANIZATION_NAME'), 'orgterms.ORGANIZATION_ID = org.ORGANIZATION_ID')
			->join('CORE_TERM', 'term', array('TERM_ABBREVIATION'), 'term.TERM_ID = orgterms.TERM_ID')		
	  ->predicate('STUDENT_STATUS_ID', $record_id)
	  ->execute()->fetch();
		
		if ($result) {
			$additional = $this->getAdditional($record_id);
			$result = array_merge($result, $additional);
		}
		
		return $result;
		
	}
	
	public function getAdditional($record_id) {
		
		$result = array();
		
		$holds_array = array();
		
		$holds_result = $this->db()->select('STUD_STUDENT_HOLDS', 'stuholds')
			->distinct()
			->fields('stuholds', array())
			->join('STUD_HOLD', 'hold', array('ALERT_DISPLAY'), 'stuholds.HOLD_ID = hold.HOLD_ID')
			->join('STUD_STUDENT_STATUS', 'status', array('STUDENT_ID'), 'status.STUDENT_ID = stuholds.STUDENT_ID')
			->predicate('status.STUDENT_STATUS_ID', $record_id)
			->predicate('stuholds.VOIDED', 'N')
			->order_by('HOLD_DATE', 'DESC', 'stuholds')
		  ->execute();
		while ($hold_row = $holds_result->fetch()) {
			$holds_array[] = $hold_row['ALERT_DISPLAY'];
		}
		$result['holds'] = implode(", ", $holds_array);
		
		return $result;
	}
	
	public function getBaseTable() {
		return 'STUD_STUDENT_STATUS';
	}
	
	public function getBaseKeyFieldName() {
		return 'STUDENT_STATUS_ID';
	}
	
	public function modifySearchDBOBject($db_obj) {
		$db_obj =	$db_obj->join('STUD_STUDENT', null, null, 'STUD_STUDENT.STUDENT_ID = STUD_STUDENT_STATUS.STUDENT_ID');
		$db_obj =	$db_obj->join('CONS_CONSTITUENT', null, null, 'CONS_CONSTITUENT.CONSTITUENT_ID = STUD_STUDENT_STATUS.STUDENT_ID');
		$db_obj = $db_obj->predicate('STUD_STUDENT_STATUS.ORGANIZATION_TERM_ID', $this->focus->getOrganizationTermIDs());
		$db_obj =	$db_obj->order_by('LAST_NAME', 'ASC');
		$db_obj =	$db_obj->order_by('FIRST_NAME', 'ASC');
		return $db_obj;
	}
	
}