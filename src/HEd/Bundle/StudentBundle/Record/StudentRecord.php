<?php

namespace Kula\Bundle\HEd\StudentBundle\Record;

class StudentRecord extends \Kula\Component\Record\BaseRecord implements \Kula\Component\Record\RecordDelegateInterface {
	
	public function getSelectedRecordBarTemplate() {
		return 'KulaHEdStudentBundle::Record/selected_record_student.html.twig';
	}
	
	public function getRecordBarTemplate() {
		return 'KulaHEdStudentBundle::Record/record_student.html.twig';
	}
	
	public function getFromDifferentType($record_type, $record_id) {
		if ($record_type == 'STUDENT_STATUS') {
			$result = $this->db()->select('STUD_STUDENT_STATUS')
				->fields(null, array('STUDENT_ID'))
				->predicate('STUDENT_STATUS_ID', $record_id);
			$result = $result->execute()->fetch();
			return $result['STUDENT_ID'];
		}
	}
	
	public function getRecordIDStack() {
		
		$result = $this->db()->select('STUD_STUDENT', 'stu')
		->distinct()
	  ->fields('stu', array('STUDENT_ID' => 'ID'))
		->join('CONS_CONSTITUENT', 'constituent', array('LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'GENDER'), 'constituent.CONSTITUENT_ID = stu.STUDENT_ID')
		->join('STUD_STUDENT_STATUS', 'studentstatus', null, 'studentstatus.STUDENT_ID = stu.STUDENT_ID')
		->join('CORE_ORGANIZATION_TERMS', 'orgterm', null, 'studentstatus.ORGANIZATION_TERM_ID = orgterm.ORGANIZATION_TERM_ID')
		->predicate('ORGANIZATION_ID', $this->focus->getSchoolIDs());
		
		if ($this->focus->getTermID()) {
			$result = $result->predicate('TERM_ID', $this->focus->getTermID());
		}
		$result = $result->order_by('LAST_NAME')
			->order_by('FIRST_NAME')
			->order_by('MIDDLE_NAME')
			->execute()->fetchAll();
		return $result;	
	}
	
	public function get($record_id) {
		
		$result = $this->db()->select('STUD_STUDENT', 'stu')
			->fields('stu', array('STUDENT_ID', 'DIRECTORY_PERMISSION'))
			->join('CONS_CONSTITUENT', 'constituent', array('PERMANENT_NUMBER', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'GENDER', 'RACE', 'MAIDEN_NAME', 'PREFERRED_NAME'), 'constituent.CONSTITUENT_ID = stu.STUDENT_ID');
		if ($this->focus->getTermID()) {
			$result = $result->join('STUD_STUDENT_STATUS', 'stustatus', array('STUDENT_STATUS_ID', 'STATUS','GRADE', 'RESIDENT'), 'stu.STUDENT_ID = stustatus.STUDENT_ID');
			$result = $result->join('CORE_ORGANIZATION_TERMS', 'orgterm', array('ORGANIZATION_TERM_ID'), 'stustatus.ORGANIZATION_TERM_ID = orgterm.ORGANIZATION_TERM_ID');
			$result = $result->join('CORE_ORGANIZATION', 'org', array('ORGANIZATION_NAME'), 'orgterm.ORGANIZATION_ID = org.ORGANIZATION_ID');
			$result = $result->join('CORE_TERM', 'term', array('TERM_ABBREVIATION'), 'term.TERM_ID = orgterm.TERM_ID');
	  	$result = $result->predicate('orgterm.TERM_ID', $this->focus->getTermID());
		}
		$result = $result->predicate('stu.STUDENT_ID', $record_id);
	  $result = $result->execute()->fetch();
		
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
			->predicate('stuholds.STUDENT_ID', $record_id)
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
		return 'STUD_STUDENT';
	}
	
	public function getBaseKeyFieldName() {
		return 'STUDENT_ID';
	}
	
	public function modifySearchDBOBject($db_obj) {
		$db_obj =	$db_obj->join('CONS_CONSTITUENT', null, null, 'CONS_CONSTITUENT.CONSTITUENT_ID = STUD_STUDENT.STUDENT_ID');
		if ($this->focus->getTermID()) {
			$db_obj =	$db_obj->join('STUD_STUDENT_STATUS', null, null, 'STUD_STUDENT.STUDENT_ID = STUD_STUDENT_STATUS.STUDENT_ID');
			$db_obj =	$db_obj->join('CORE_ORGANIZATION_TERMS', null, null, 'STUD_STUDENT_STATUS.ORGANIZATION_TERM_ID = CORE_ORGANIZATION_TERMS.ORGANIZATION_TERM_ID');
			$db_obj = $db_obj->predicate('CORE_ORGANIZATION_TERMS.ORGANIZATION_ID', $this->focus->getSchoolIDs());
	  	$db_obj =	$db_obj->predicate('TERM_ID', $this->focus->getTermID());
		}
		$db_obj =	$db_obj->order_by('LAST_NAME', 'ASC');
		$db_obj =	$db_obj->order_by('FIRST_NAME', 'ASC');
		return $db_obj;
	}
	
}