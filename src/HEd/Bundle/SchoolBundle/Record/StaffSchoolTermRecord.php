<?php

namespace Kula\Bundle\Core\StaffBundle\Record;

class StaffSchoolTermRecord extends \Kula\Component\Record\BaseRecord implements \Kula\Component\Record\RecordDelegateInterface {
	
	public function getSelectedRecordBarTemplate() {
		
	}
	
	public function getRecordBarTemplate() {
		return 'KulaStaffBundle::Record/record_staff.html.twig';
	}
	
	public function getFromDifferentType($record_type, $record_id) {
		if ($record_type == 'STAFF') {
			$result = $this->db()->select('STUD_STAFF_ORGANIZATION_TERMS')
				->fields(null, array('STAFF_ORGANIZATION_TERM_ID'))
				->join('CORE_ORGANIZATION_TERMS', 'orgterm', null, 'STUD_STAFF_ORGANIZATION_TERMS.ORGANIZATION_TERM_ID = orgterm.ORGANIZATION_TERM_ID')
				->predicate('STAFF_ID', $record_id)
			  ->predicate('ORGANIZATION_ID', $this->focus->getOrganizationTermIDs());
			if ($this->session->get('term_id'))
				$result = $result->predicate('TERM_ID', $this->focus->getTermID());
			$result = $result->execute()->fetch();
			return $result['STAFF_ORGANIZATION_TERM_ID'];
		}
	}
	
	public function getRecordIDStack() {
		
		$result = $this->db()->select('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgtrm')
	  ->fields('stafforgtrm', array('STAFF_ORGANIZATION_TERM_ID' => 'ID'))
		->join('STUD_STAFF', 'staff', null, 'stafforgtrm.STAFF_ID = staff.STAFF_ID')
		->join('CONS_CONSTITUENT', 'constituent', null, 'constituent.CONSTITUENT_ID = staff.STAFF_ID')
		->predicate('stafforgtrm.ORGANIZATION_TERM_ID', $this->focus->getOrganizationTermIDs())
		->order_by('LAST_NAME', 'ASC')
		->order_by('FIRST_NAME', 'ASC');
		$result = $result->execute()->fetchAll();
		
		return $result;		
	}
	
	public function get($record_id) {
		
		$result = $this->db()->select('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgtrm')
		->fields('stafforgtrm', array('STAFF_ORGANIZATION_TERM_ID', 'FTE'))
		->join('STUD_STAFF', 'staff', array('ABBREVIATED_NAME', 'STAFF_ID'), 'stafforgtrm.STAFF_ID = staff.STAFF_ID')
		->join('CONS_CONSTITUENT', 'constituent', array('LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME'), 'constituent.CONSTITUENT_ID = staff.STAFF_ID')
		->predicate('stafforgtrm.STAFF_ORGANIZATION_TERM_ID', $record_id)
	  ->execute()->fetch();
		return $result;
		
	}
	
	public function getBaseTable() {
		return 'STUD_STAFF_ORGANIZATION_TERMS';
	}
	
	public function getBaseKeyFieldName() {
		return 'STAFF_ORGANIZATION_TERM_ID';
	}
	
	public function modifySearchDBOBject($db_obj) {
		$db_obj =	$db_obj->join('STUD_STAFF', null, null, 'STUD_STAFF.STAFF_ID = STUD_STAFF_ORGANIZATION_TERMS.STAFF_ID');
		$db_obj =	$db_obj->join('CONS_CONSTITUENT', null, null, 'STUD_STAFF.STAFF_ID = CONS_CONSTITUENT.CONSTITUENT_ID');
		$db_obj = $db_obj->predicate('ORGANIZATION_TERM_ID', $this->focus->getOrganizationTermIDs());
		$db_obj =	$db_obj->order_by('LAST_NAME', 'ASC');
		$db_obj =	$db_obj->order_by('FIRST_NAME', 'ASC');

		return $db_obj;
	}
	
}