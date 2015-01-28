<?php

namespace Kula\Bundle\Core\StaffBundle\Chooser;

class StaffOrganizationTermChooser extends \Kula\Component\Chooser\Chooser {
	
	public static function search($q) {
		
		$container = $GLOBALS['kernel']->getContainer();
		
		$query_conditions = new \Kula\Component\Database\Query\Predicate('AND');
		$query_conditions = $query_conditions->predicate('stafforgtrm.ORGANIZATION_TERM_ID', $container->get('kula.focus')->getOrganizationTermIDs());
		
		$query_conditions_or = new \Kula\Component\Database\Query\Predicate('OR');
		$query_conditions_or = $query_conditions_or->predicate('ABBREVIATED_NAME', $q.'%', 'LIKE');
		
		$query_conditions = $query_conditions->predicate($query_conditions_or);
		
		$data = array();
		
		$search = self::db()->select('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgtrm')
			->fields('stafforgtrm', array('STAFF_ORGANIZATION_TERM_ID'))
			->join('STUD_STAFF', 'staff', array('ABBREVIATED_NAME'), 'stafforgtrm.STAFF_ID = staff.STAFF_ID')
			->where($query_conditions)
			->order_by('ABBREVIATED_NAME', 'ASC');
		$search = $search	->execute();
		while ($row = $search->fetch()) {
			self::addToChooserMenu($row['STAFF_ORGANIZATION_TERM_ID'], $row['ABBREVIATED_NAME']);
		}
		
	}
	
	public static function choice($id) {
		$row = self::db()->select('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgtrm')
			->fields('stafforgtrm', array('STAFF_ORGANIZATION_TERM_ID'))
			->join('STUD_STAFF', 'staff', array('ABBREVIATED_NAME'), 'stafforgtrm.STAFF_ID = staff.STAFF_ID')
			->predicate('stafforgtrm.STAFF_ORGANIZATION_TERM_ID', $id)
			->execute()
			->fetch();
		return self::currentValue($row['STAFF_ORGANIZATION_TERM_ID'], $row['ABBREVIATED_NAME']);
	}
	
	public static function searchRoute() {
		return 'sis_offering_staff_orgterm_chooser';
	}
	
}