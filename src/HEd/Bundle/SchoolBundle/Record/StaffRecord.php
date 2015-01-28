<?php

namespace Kula\Bundle\Core\StaffBundle\Record;

class StaffRecord extends \Kula\Component\Record\BaseRecord implements \Kula\Component\Record\RecordDelegateInterface {
	
	public function getSelectedRecordBarTemplate() {
		
	}
	
	public function getRecordBarTemplate() {
		return 'KulaStaffBundle::Record/record_staff.html.twig';
	}
	
	public function getFromDifferentType($record_type, $record_id) {
		if ($record_type == 'STAFF_SCHOOL_TERM') {
			$result = $this->db()->select('STUD_STAFF_ORGANIZATION_TERMS')
				->fields(null, array('STAFF_ID'))
				->predicate('STAFF_ORGANIZATION_TERM_ID', $record_id);
			$result = $result->execute()->fetch();
			return $result['STAFF_ID'];
		}
	}
	
	public function getRecordIDStack() {
		
		$result = $this->db()->select('STUD_STAFF', 'staff')
		->distinct()
	  ->fields('staff', array('STAFF_ID' => 'ID'))
		->join('CONS_CONSTITUENT', 'constituent', null, 'constituent.CONSTITUENT_ID = staff.STAFF_ID')
		->join('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgterms', null, 'stafforgterms.STAFF_ID = staff.STAFF_ID')
		->predicate('stafforgterms.ORGANIZATION_TERM_ID', $this->focus->getOrganizationTermIDs())
		->order_by('LAST_NAME', 'ASC')
		->order_by('FIRST_NAME', 'ASC');
		$result = $result->execute()->fetchAll();
		
		return $result;		
	}
	
	public function get($record_id) {
		
		$result = $this->db()->select('STUD_STAFF', 'staff')
		->fields('staff', array('STAFF_ID', 'ABBREVIATED_NAME'))
		->join('CONS_CONSTITUENT', 'constituent', array('LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME'), 'constituent.CONSTITUENT_ID = staff.STAFF_ID')
		->predicate('staff.STAFF_ID', $record_id)
	  ->execute()->fetch();
		return $result;
		
	}
	
	public function getBaseTable() {
		return 'STUD_STAFF';
	}
	
	public function getBaseKeyFieldName() {
		return 'STAFF_ID';
	}
	
	public function modifySearchDBOBject($db_obj) {
		$db_obj =	$db_obj->join('CONS_CONSTITUENT', null, null, 'STUD_STAFF.STAFF_ID = CONS_CONSTITUENT.CONSTITUENT_ID');
		$db_obj =	$db_obj->join('STUD_STAFF_ORGANIZATION_TERMS', null, null, 'STUD_STAFF.STAFF_ID = STUD_STAFF_ORGANIZATION_TERMS.STAFF_ID');
		$db_obj = $db_obj->predicate('ORGANIZATION_TERM_ID', $this->focus->getOrganizationTermIDs());
		$db_obj = $db_obj->distinct();
		$db_obj =	$db_obj->order_by('LAST_NAME', 'ASC');
		$db_obj =	$db_obj->order_by('FIRST_NAME', 'ASC');

		return $db_obj;
	}
	
}