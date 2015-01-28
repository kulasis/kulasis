<?php

namespace Kula\Bundle\Core\ConstituentBundle\Record;

class ConstituentRecord extends \Kula\Component\Record\BaseRecord implements \Kula\Component\Record\RecordDelegateInterface {
	
	public function getSelectedRecordBarTemplate() {
		return '';
	}
	
	public function getRecordBarTemplate() {
		return 'KulaConstituentBundle::Record/record_constituent.html.twig';
	}
	
	public function getFromDifferentType($record_type, $record_id) {
		if ($record_type == 'STUDENT_STATUS') {
			$result = $this->db()->select('STUD_STUDENT_STATUS')
				->fields(null, array('STUDENT_ID'))
				->predicate('STUDENT_STATUS_ID', $record_id);
			$result = $result->execute()->fetch();
			return $result['STUDENT_ID'];
		}
		if ($record_type == 'STUDENT') {
			return $record_id;
		}
	}
	
	public function getRecordIDStack() {
		
		$result = $this->db()->select('CONS_CONSTITUENT', 'constituent')
		->fields('constituent', array('CONSTITUENT_ID' => 'ID'))
		->order_by('LAST_NAME')
		->order_by('FIRST_NAME')
		->order_by('MIDDLE_NAME')
		->execute()->fetchAll();
		return $result;	
	}
	
	public function get($record_id) {
		
		$result = $this->db()->select('CONS_CONSTITUENT', 'cons')
			->predicate('CONSTITUENT_ID', $record_id)
			->execute()->fetch();
		return $result;
	}

	public function getBaseTable() {
		return 'CONS_CONSTITUENT';
	}
	
	public function getBaseKeyFieldName() {
		return 'CONSTITUENT_ID';
	}
	
	public function modifySearchDBOBject($db_obj) {
		$db_obj =	$db_obj->order_by('LAST_NAME', 'ASC');
		$db_obj =	$db_obj->order_by('FIRST_NAME', 'ASC');
		return $db_obj;
	}
	
}