<?php

namespace Kula\Bundle\HEd\OfferingBundle\Field;

use Kula\Component\Database\CalculatedFieldInterface;

class MajorName implements CalculatedFieldInterface {
	
	public static function select($schema, $param) {
		
		$menu = array();
		
		$result = \Kula\Component\Database\DB::connect('read')->select('STUD_DEGREE_MAJOR', 'major')
	->fields('major', array('MAJOR_ID', 'MAJOR_NAME'))
	->order_by('MAJOR_NAME', 'ASC')->execute();
		while ($row = $result->fetch()) {
			$menu[$row['MAJOR_ID']] = $row['MAJOR_NAME'];
		}
		
		return $menu;
		
	}
	
}