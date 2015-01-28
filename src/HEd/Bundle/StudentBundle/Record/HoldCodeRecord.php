<?php

namespace Kula\Bundle\HEd\StudentBundle\Record;

class HoldCodeRecord extends \Kula\Component\Record\BaseRecord implements \Kula\Component\Record\RecordDelegateInterface {
	
	public function getSelectedRecordBarTemplate() {
		return '';
	}
	
	public function getRecordBarTemplate() {
		return 'KulaHEdStudentBundle::Record/record_hold_code.html.twig';
	}
	
	public function getRecordIDStack() {
		
		//$or_query_conditions = new \Kula\Core\Database\Query\Predicate('OR');
		//$or_query_conditions = $or_query_conditions->predicate('EFFECTIVE_DATE', null);
		//$or_query_conditions = $or_query_conditions->predicate('EFFECTIVE_DATE', date('Y-m-d'), '>=');
		
		$result = $this->db()->select('STUD_HOLD', 'hold')
		->distinct()
	  ->fields('hold', array('HOLD_ID' => 'ID'))
		->predicate('INACTIVE', 'N')
		->order_by('HOLD_CODE')
		->execute()->fetchAll();
		return $result;	
	}
	
	public function get($record_id) {
		
		$result = $this->db()->select('STUD_HOLD', 'hold')
			->fields('hold', array('HOLD_ID', 'HOLD_CODE', 'HOLD_NAME'))
			->predicate('HOLD_ID', $record_id)
			->execute()->fetch();
		
		return $result;
	}
	
	public function getBaseTable() {
		return 'STUD_HOLD';
	}
	
	public function getBaseKeyFieldName() {
		return 'HOLD_ID';
	}
	
	public function modifySearchDBOBject($db_obj) {
		$db_obj =	$db_obj->order_by('HOLD_CODE', 'ASC');
		return $db_obj;
	}
	
}