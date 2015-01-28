<?php

namespace Kula\Bundle\HEd\OfferingBundle\Field;

use Kula\Component\Database\CalculatedFieldInterface;

class MinorName implements CalculatedFieldInterface {
	
	public static function select($schema, $param) {
		
		$menu = array();
		
		$result = \Kula\Component\Database\DB::connect('read')->select('STUD_DEGREE_MINOR', 'minor')
	->fields('minor', array('MINOR_ID', 'MINOR_NAME'))
	->order_by('MINOR_NAME', 'ASC')->execute();
		while ($row = $result->fetch()) {
			$menu[$row['MINOR_ID']] = $row['MINOR_NAME'];
		}
		
		return $menu;
		
	}
	
}