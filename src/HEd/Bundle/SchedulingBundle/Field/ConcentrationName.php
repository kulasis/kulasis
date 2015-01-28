<?php

namespace Kula\Bundle\HEd\OfferingBundle\Field;

use Kula\Component\Database\CalculatedFieldInterface;

class ConcentrationName implements CalculatedFieldInterface {
	
	public static function select($schema, $param) {
		
		$menu = array();
		
		$result = \Kula\Component\Database\DB::connect('read')->select('STUD_DEGREE_CONCENTRATION', 'concentration')
	->fields('concentration', array('CONCENTRATION_ID', 'CONCENTRATION_NAME'))
	->order_by('CONCENTRATION_NAME', 'ASC')->execute();
		while ($row = $result->fetch()) {
			$menu[$row['CONCENTRATION_ID']] = $row['CONCENTRATION_NAME'];
		}
		
		return $menu;
		
	}
	
}