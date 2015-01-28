<?php

namespace Kula\Bundle\Core\StaffBundle\Chooser;

class StaffChooser extends \Kula\Component\Chooser\Chooser {
	
	public static function search($q) {

		$data = array();
		
		$search = self::db()->select('STUD_STAFF', 'staff')
			->fields('staff', array('STAFF_ID', 'ABBREVIATED_NAME'))
			->predicate('ABBREVIATED_NAME', $q.'%', 'LIKE')
			->order_by('ABBREVIATED_NAME', 'ASC');
		$search = $search	->execute();
		while ($row = $search->fetch()) {
			self::addToChooserMenu($row['STAFF_ID'], $row['ABBREVIATED_NAME']);
		}
		
	}
	
	public static function choice($id) {
		$row = self::db()->select('STUD_STAFF', 'staff')
			->fields('staff', array('STAFF_ID', 'ABBREVIATED_NAME'))
			->predicate('staff.STAFF_ID', $id)
			->execute()
			->fetch();
		return self::currentValue($row['STAFF_ID'], $row['ABBREVIATED_NAME']);
	}
	
	public static function searchRoute() {
		return 'sis_offering_staff_chooser';
	}
	
}