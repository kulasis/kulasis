<?php

namespace Kula\Bundle\HEd\OfferingBundle\Field;

use Kula\Component\Database\CalculatedFieldInterface;

class DegreeName implements CalculatedFieldInterface {
	
	public static function select($schema, $param) {
		
		$menu = array();
		
		$result = \Kula\Component\Database\DB::connect('read')->select('STUD_DEGREE', 'degree')
	->fields('degree', array('DEGREE_ID', 'DEGREE_NAME'))
	->order_by('DEGREE_NAME', 'ASC')->execute();
		while ($row = $result->fetch()) {
			$menu[$row['DEGREE_ID']] = $row['DEGREE_NAME'];
		}
		
		return $menu;
		
	}
	
}