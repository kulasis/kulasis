<?php

namespace Kula\Bundle\HEd\FinancialAidBundle\Record;

class FinancialAidAwardCodeRecord extends \Kula\Component\Record\BaseRecord implements \Kula\Component\Record\RecordDelegateInterface {
	
	public function getSelectedRecordBarTemplate() {
		return '';
	}
	
	public function getRecordBarTemplate() {
		return 'KulaHEdFinancialAidBundle::Record/record_faid_award_code.html.twig';
	}
	
	public function getRecordIDStack() {
		
		//$or_query_conditions = new \Kula\Core\Database\Query\Predicate('OR');
		//$or_query_conditions = $or_query_conditions->predicate('EFFECTIVE_DATE', null);
		//$or_query_conditions = $or_query_conditions->predicate('EFFECTIVE_DATE', date('Y-m-d'), '>=');
		
		$result = $this->db()->select('FAID_AWARD_CODE', 'code')
		->distinct()
	  ->fields('code', array('AWARD_CODE_ID' => 'ID'))
		->predicate('INACTIVE', 'N')
		->order_by('AWARD_CODE')
		->execute()->fetchAll();
		return $result;	
	}
	
	public function get($record_id) {
		
		$result = $this->db()->select('FAID_AWARD_CODE', 'code')
			->fields('code', array('AWARD_CODE_ID', 'AWARD_CODE', 'AWARD_DESCRIPTION'))
			->predicate('AWARD_CODE_ID', $record_id)
			->execute()->fetch();
		
		return $result;
	}
	
	public function getBaseTable() {
		return 'FAID_AWARD_CODE';
	}
	
	public function getBaseKeyFieldName() {
		return 'AWARD_CODE_ID';
	}
	
	public function modifySearchDBOBject($db_obj) {
		$db_obj =	$db_obj->order_by('AWARD_CODE', 'ASC');
		return $db_obj;
	}
	
}